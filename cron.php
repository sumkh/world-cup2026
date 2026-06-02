<?php
/* ============================================================
   cron.php — score-sync script (one API-SPORTS call per run).

   Triggered by GitHub Actions (see .github/workflows/scores-sync.yml)
   or manually from admin.php.

   What it does in a single /fixtures call:
     1. Extracts team names + API-SPORTS IDs from the fixture payload
        and updates wc_teams.as_id by name-matching (so picks score).
     2. Upserts all fixture results into the wc_fixtures cache.

   Protection: pass the ADMIN_PASSWORD as the ?key= query param
   or as the X-Cron-Key HTTP header.
   ============================================================ */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ─────────────────────────────────────────────────────────────────────
$provided = $_SERVER['HTTP_X_CRON_KEY'] ?? ($_GET['key'] ?? '');
if (ADMIN_PASSWORD === '' || !hash_equals(ADMIN_PASSWORD, (string)$provided)) {
  http_response_code(403);
  echo json_encode(['error' => 'Forbidden — pass ?key=ADMIN_PASSWORD']);
  exit;
}

// ── Single API-SPORTS call ────────────────────────────────────────────────────
$url = 'https://v3.football.api-sports.io/fixtures?league=' . LEAGUE_ID . '&season=' . SEASON;
$ch  = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => ['x-apisports-key: ' . API_KEY],
  CURLOPT_TIMEOUT        => 20,
]);
$body = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$body || $http !== 200) {
  http_response_code(502);
  echo json_encode(['error' => 'API-SPORTS returned HTTP ' . $http]);
  exit;
}

$payload = json_decode($body, true);
if (!empty($payload['errors'])) {
  http_response_code(502);
  echo json_encode(['error' => implode('; ', (array)array_values($payload['errors'])[0])]);
  exit;
}

$fixtures = $payload['response'] ?? [];
$pdo      = db();
$stats    = ['teams_mapped' => 0, 'fixtures_upserted' => 0];

// ── Step 1: map API-SPORTS team IDs → our wc_teams.id ────────────────────────
// Extract every unique team from the fixture list.
$as_teams = [];
foreach ($fixtures as $f) {
  foreach (['home', 'away'] as $side) {
    $t = $f['teams'][$side];
    $as_teams[$t['id']] = ['name' => $t['name'], 'logo' => $t['logo']];
  }
}

// Match by team name (case-insensitive) and store the API-SPORTS ID + logo.
$upd_team = $pdo->prepare(
  'UPDATE wc_teams SET as_id = ?, logo = ? WHERE LOWER(name) = LOWER(?)'
);
foreach ($as_teams as $as_id => $info) {
  $upd_team->execute([$as_id, $info['logo'], $info['name']]);
  if ($upd_team->rowCount() > 0) $stats['teams_mapped']++;
}

// Build the reverse map: API-SPORTS ID → our internal wc_teams.id
$map = [];
foreach ($pdo->query('SELECT id, as_id FROM wc_teams WHERE as_id IS NOT NULL') as $r) {
  $map[(int)$r['as_id']] = (int)$r['id'];
}

// ── Step 2: upsert fixture results ───────────────────────────────────────────
$upsert = $pdo->prepare('
  INSERT INTO wc_fixtures (id, home_id, away_id, home_goals, away_goals, status)
  VALUES (?, ?, ?, ?, ?, ?)
  ON CONFLICT (id) DO UPDATE SET
    home_goals = EXCLUDED.home_goals,
    away_goals = EXCLUDED.away_goals,
    status     = EXCLUDED.status
');

foreach ($fixtures as $f) {
  $as_h = (int)$f['teams']['home']['id'];
  $as_a = (int)$f['teams']['away']['id'];
  if (!isset($map[$as_h], $map[$as_a])) continue; // unknown team — skip

  $status = $f['fixture']['status']['short'];
  $hg     = $f['score']['fulltime']['home'] ?? $f['goals']['home'] ?? null;
  $ag     = $f['score']['fulltime']['away'] ?? $f['goals']['away'] ?? null;

  $upsert->execute([$f['fixture']['id'], $map[$as_h], $map[$as_a], $hg, $ag, $status]);
  $stats['fixtures_upserted']++;
}

echo json_encode(array_merge($stats, [
  'total_fixtures_in_api' => count($fixtures),
  'teams_in_api'          => count($as_teams),
]));
