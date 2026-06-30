<?php
/* ============================================================
   cron.php — score-sync script (one API call per run).

   Uses football-data.org free plan (competition 2000 = FIFA WC,
   season 2026).  A single /matches call returns all 104 fixtures
   with team IDs and live/final scores.

   Triggered by GitHub Actions (.github/workflows/scores-sync.yml)
   or manually via the "Sync Scores" button in admin.php.

   Open to all visitors (no auth required) — rate-limited to one call
   per 60 seconds to stay well within football-data.org's free-plan
   limit of 10 requests/minute.

   External automated callers (GitHub Actions, curl) can bypass the
   rate limit by passing ADMIN_PASSWORD as ?key= or X-Cron-Key header.
   ============================================================ */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

// ── Rate limit (public callers) / bypass (admin) ─────────────────────────────
$provided   = $_SERVER['HTTP_X_CRON_KEY'] ?? ($_GET['key'] ?? '');
$admin_call = !empty($_SESSION['admin'])
           || (ADMIN_PASSWORD !== '' && hash_equals(ADMIN_PASSWORD, (string)$provided));

if (!$admin_call) {
  $pdo  = db();
  $row  = $pdo->query("SELECT value FROM wc_meta WHERE key = 'last_sync'")->fetch();
  $last = $row ? (int)$row['value'] : 0;
  $wait = 60 - (time() - $last);
  if ($wait > 0) {
    echo json_encode(['skipped' => true, 'next_in' => $wait]);
    exit;
  }
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

// football-data.org score.winner → our single-char code.
// Reflects the true result incl. extra time / penalties — used only for
// the end-of-tournament champion/runner-up/third bonuses.
$winner_map = ['HOME_TEAM' => 'H', 'AWAY_TEAM' => 'A', 'DRAW' => 'D'];

$upsert = $pdo->prepare('
  INSERT INTO wc_fixtures (id, home_id, away_id, home_goals, away_goals, status, stage, winner, utc_date, grp)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ON CONFLICT (id) DO UPDATE SET
    home_goals = EXCLUDED.home_goals,
    away_goals = EXCLUDED.away_goals,
    status     = EXCLUDED.status,
    stage      = EXCLUDED.stage,
    winner     = EXCLUDED.winner,
    utc_date   = EXCLUDED.utc_date,
    grp        = EXCLUDED.grp
');

foreach ($matches as $m) {
  $fd_home = (int)($m['homeTeam']['id'] ?? 0);
  $fd_away = (int)($m['awayTeam']['id'] ?? 0);
  if (!isset($map[$fd_home], $map[$fd_away])) continue;

  $status = $status_map[$m['status']] ?? 'NS';
  $stage  = $m['stage'] ?? null;
  $winner = $winner_map[$m['score']['winner'] ?? ''] ?? null;
  $utc    = $m['utcDate'] ?? null;
  $grp    = $m['group'] ?? null;

  // 90-minute (regulation) result. IMPORTANT: football-data.org's
  // score.fullTime is the FULL result, INCLUDING extra-time goals AND penalty
  // shootout kicks (e.g. a 0-0 won on pens is reported as 3-0 in fullTime).
  // The 90' score lives in score.regularTime, present only for ET/penalty
  // games. We score on 90' only (ET & penalties count as a draw), so prefer
  // regularTime and fall back to fullTime for normal matches.
  $sc  = $m['score'] ?? [];
  $reg = $sc['regularTime'] ?? null;
  $ful = $sc['fullTime'] ?? null;
  $src = is_array($reg) ? $reg : (is_array($ful) ? $ful : []);
  $hg  = $src['home'] ?? null;
  $ag  = $src['away'] ?? null;

  $upsert->execute([$m['id'], $map[$fd_home], $map[$fd_away], $hg, $ag, $status, $stage, $winner, $utc, $grp]);
  $stats['fixtures_upserted']++;
}

// Stamp last-sync time so the rate-limit works for the next caller.
$pdo->exec("INSERT INTO wc_meta (key, value) VALUES ('last_sync', '" . time() . "')
            ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");

echo json_encode($stats);
