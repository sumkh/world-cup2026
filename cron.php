<?php
/* ============================================================
   cron.php — score-sync script (one API call per run).

   Uses football-data.org free plan (competition 2000 = FIFA WC,
   season 2026).  A single /matches call returns all 104 fixtures
   with team IDs and live/final scores.

   Triggered by GitHub Actions (.github/workflows/scores-sync.yml)
   or manually via the "Sync Scores" button in admin.php.

   Auth (two ways):
     - Admin web session: already logged into admin.php — no key needed.
     - External callers (GitHub Actions, curl): pass ADMIN_PASSWORD as
       ?key= query param or the X-Cron-Key HTTP header.
   ============================================================ */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Auth ─────────────────────────────────────────────────────────────────────
$session_ok = !empty($_SESSION['admin']);
$provided   = $_SERVER['HTTP_X_CRON_KEY'] ?? ($_GET['key'] ?? '');
$key_ok     = ADMIN_PASSWORD !== '' && hash_equals(ADMIN_PASSWORD, (string)$provided);
if (!$session_ok && !$key_ok) {
  http_response_code(403);
  echo json_encode(['error' => 'Forbidden — log into admin.php or pass ?key=ADMIN_PASSWORD']);
  exit;
}

// ── Single API call: all 104 WC 2026 fixtures ────────────────────────────────
$url = 'https://api.football-data.org/v4/competitions/2000/matches?season=2026';
$ch  = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => ['X-Auth-Token: ' . FD_API_KEY],
  CURLOPT_TIMEOUT        => 20,
]);
$body = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$body || $http !== 200) {
  http_response_code(502);
  echo json_encode(['error' => 'football-data.org returned HTTP ' . $http]);
  exit;
}

$payload = json_decode($body, true);
$matches = $payload['matches'] ?? [];
if (!$matches) {
  http_response_code(502);
  echo json_encode(['error' => 'Empty response — check FD_API_KEY and season']);
  exit;
}

$pdo   = db();
$stats = ['fixtures_upserted' => 0, 'total' => count($matches)];

// Build football-data.org team ID → our internal wc_teams.id lookup.
// as_id values are pre-populated in the DB seed, so this is always fast.
$map = [];
foreach ($pdo->query('SELECT id, as_id FROM wc_teams WHERE as_id IS NOT NULL') as $r) {
  $map[(int)$r['as_id']] = (int)$r['id'];
}

// football-data.org status → short code stored in wc_fixtures.
// Only FINISHED matches get 'FT' (which is in common.js's FINISHED set).
$status_map = [
  'FINISHED'  => 'FT',
  'IN_PLAY'   => 'LIVE',
  'PAUSED'    => 'HT',
  'TIMED'     => 'NS',
  'SCHEDULED' => 'NS',
  'POSTPONED' => 'PST',
  'SUSPENDED' => 'SUSP',
  'CANCELLED' => 'CANC',
];

$upsert = $pdo->prepare('
  INSERT INTO wc_fixtures (id, home_id, away_id, home_goals, away_goals, status)
  VALUES (?, ?, ?, ?, ?, ?)
  ON CONFLICT (id) DO UPDATE SET
    home_goals = EXCLUDED.home_goals,
    away_goals = EXCLUDED.away_goals,
    status     = EXCLUDED.status
');

foreach ($matches as $m) {
  $fd_home = (int)($m['homeTeam']['id'] ?? 0);
  $fd_away = (int)($m['awayTeam']['id'] ?? 0);
  if (!isset($map[$fd_home], $map[$fd_away])) continue;

  $status = $status_map[$m['status']] ?? 'NS';

  // score.fullTime is the 90-minute result.
  // For matches that go to extra time or penalties, fullTime still holds
  // the regulation score — which is exactly what our scoring rules need.
  $hg = $m['score']['fullTime']['home'] ?? null;
  $ag = $m['score']['fullTime']['away'] ?? null;

  $upsert->execute([$m['id'], $map[$fd_home], $map[$fd_away], $hg, $ag, $status]);
  $stats['fixtures_upserted']++;
}

echo json_encode($stats);
