<?php
/* ============================================================
   api.php — same-origin JSON endpoints used by the pages.
   No external calls happen here (match results are read by the
   browser, not the server), so this only touches the local DB.
   ============================================================ */
require_once __DIR__ . '/db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function out($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }
function body() { $j = json_decode(file_get_contents('php://input'), true); return is_array($j) ? $j : []; }
function locked() { return new DateTime('now') >= new DateTime(PICK_LOCK); }

function clean_picks($raw) {
  if (!is_array($raw) || count($raw) !== NUM_PICKS) return null;
  $t = [];
  foreach ($raw as $x) {
    if (!is_int($x) && !ctype_digit((string)$x)) return null;
    $x = (int)$x;
    if ($x <= 0) return null;
    $t[] = $x;
  }
  if (count(array_unique($t)) !== NUM_PICKS) return null;
  return $t;
}

function valid_user_id($uid) {
  // Must be exactly two digits, "01"–"32"
  if (!preg_match('/^\d{2}$/', $uid)) return false;
  $n = (int)$uid;
  return $n >= 1 && $n <= 32;
}

try {
  $pdo = db();
} catch (Throwable $e) {
  out(['error' => 'Database connection failed. Check the DB settings in config.php.'], 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {

  case 'claim': {
    // Registration stays open after kickoff (late join). Late joiners' picks
    // are final immediately and only score from matches after joined_at.
    $b      = body();
    $uid    = trim($b['user_id']  ?? '');
    $nick   = trim($b['nickname'] ?? '');
    $pin    = trim($b['pin']      ?? '');
    $picks  = clean_picks($b['picks'] ?? null);

    if ($uid === '' || $nick === '' || $pin === '') out(['error' => 'All fields are required.'], 400);
    if (!valid_user_id($uid))                       out(['error' => 'User ID must be 01–32.'], 400);
    if (!preg_match('/^[A-Za-z0-9_ ]{2,40}$/', $nick)) out(['error' => 'Nickname: 2–40 letters, numbers, spaces or underscores.'], 400);
    if (!preg_match('/^\d{6}$/', $pin))             out(['error' => 'Personal PIN must be exactly 6 digits.'], 400);
    if (!$picks)                                    out(['error' => 'Pick exactly 3 different teams.'], 400);

    $st = $pdo->prepare('SELECT id, claimed FROM participants WHERE user_id = ? LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row)           out(['error' => 'Invalid User ID.'], 403);
    if ($row['claimed']) out(['error' => 'This User ID has already been registered.'], 403);

    $st = $pdo->prepare('SELECT id FROM participants WHERE nickname = ? LIMIT 1');
    $st->execute([$nick]);
    if ($st->fetch()) out(['error' => 'That nickname is already taken.'], 409);

    $st = $pdo->prepare('UPDATE participants SET claimed=1, nickname=?, pin_hash=?, team1=?, team2=?, team3=?, joined_at=NOW() WHERE id=?');
    $st->execute([$nick, password_hash($pin, PASSWORD_DEFAULT), $picks[0], $picks[1], $picks[2], $row['id']]);

    $_SESSION['pid'] = (int)$row['id'];
    out(['ok' => true, 'nickname' => $nick, 'picks' => $picks, 'late' => locked()]);
  }

  case 'login': {
    $b   = body();
    $uid = trim($b['user_id'] ?? '');
    $pin = trim($b['pin']     ?? '');
    if (!valid_user_id($uid)) out(['error' => 'User ID must be 01–32.'], 400);
    $st = $pdo->prepare('SELECT * FROM participants WHERE user_id = ? AND claimed = 1 LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !password_verify($pin, $row['pin_hash'])) out(['error' => 'Wrong User ID or PIN.'], 403);
    $_SESSION['pid'] = (int)$row['id'];
    out(['ok' => true, 'nickname' => $row['nickname'], 'picks' => [(int)$row['team1'], (int)$row['team2'], (int)$row['team3']]]);
  }

  case 'me': {
    if (empty($_SESSION['pid'])) out(['auth' => false, 'locked' => locked()]);
    $st = $pdo->prepare('SELECT * FROM participants WHERE id = ? LIMIT 1');
    $st->execute([$_SESSION['pid']]);
    $row = $st->fetch();
    if (!$row) out(['auth' => false, 'locked' => locked()]);
    out(['auth' => true, 'nickname' => $row['nickname'],
         'picks' => [(int)$row['team1'], (int)$row['team2'], (int)$row['team3']], 'locked' => locked()]);
  }

  case 'save_picks': {
    if (empty($_SESSION['pid'])) out(['error' => 'You are not logged in.'], 403);
    if (locked())                out(['error' => 'Picks are locked — the tournament has started.'], 403);
    $picks = clean_picks(body()['picks'] ?? null);
    if (!$picks) out(['error' => 'Pick exactly 3 different teams.'], 400);
    $st = $pdo->prepare('UPDATE participants SET team1=?, team2=?, team3=? WHERE id=?');
    $st->execute([$picks[0], $picks[1], $picks[2], $_SESSION['pid']]);
    out(['ok' => true, 'picks' => $picks]);
  }

  case 'logout': {
    session_destroy();
    out(['ok' => true]);
  }

  case 'participants': {
    // Leaderboard feed. Picks are hidden until kickoff.
    // joined is sent as ISO-8601 UTC so the browser can score late joiners
    // only from matches after they registered.
    $reveal = locked();
    $st = $pdo->query("SELECT nickname, team1, team2, team3,
                              to_char(joined_at AT TIME ZONE 'UTC', 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS joined
                       FROM participants WHERE claimed = 1 ORDER BY created_at ASC");
    $list = [];
    foreach ($st as $r) {
      $list[] = [
        'nickname' => $r['nickname'],
        'picks'    => $reveal ? [(int)$r['team1'], (int)$r['team2'], (int)$r['team3']] : null,
        'joined'   => $r['joined'],
      ];
    }
    out(['locked' => $reveal, 'participants' => $list]);
  }

  case 'teams': {
    // Return hardcoded+cron-updated team list for dropdowns.
    $st = $pdo->query('SELECT id, name, logo FROM wc_teams ORDER BY id ASC');
    $teams = [];
    foreach ($st as $r) {
      $teams[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'logo' => $r['logo']];
    }
    out(['teams' => $teams]);
  }

  case 'fixtures': {
    // Return cached fixtures in the same shape computeTeamPoints() expects,
    // but using our internal wc_teams IDs so picks match correctly.
    $st = $pdo->query('
      SELECT f.id, f.home_goals, f.away_goals, f.status, f.stage, f.winner, f.utc_date, f.grp,
             ht.id AS h_id, ht.name AS h_name, ht.logo AS h_logo,
             awt.id AS a_id, awt.name AS a_name, awt.logo AS a_logo
      FROM wc_fixtures f
      JOIN wc_teams ht  ON ht.id  = f.home_id
      JOIN wc_teams awt ON awt.id = f.away_id
    ');
    $fixtures = [];
    foreach ($st as $r) {
      $hg = $r['home_goals'] !== null ? (int)$r['home_goals'] : null;
      $ag = $r['away_goals'] !== null ? (int)$r['away_goals'] : null;
      $fixtures[] = [
        'teams'   => [
          'home' => ['id' => (int)$r['h_id'], 'name' => $r['h_name'], 'logo' => $r['h_logo']],
          'away' => ['id' => (int)$r['a_id'], 'name' => $r['a_name'], 'logo' => $r['a_logo']],
        ],
        'fixture' => ['status' => ['short' => $r['status']], 'stage' => $r['stage'],
                      'winner' => $r['winner'], 'date' => $r['utc_date'], 'group' => $r['grp']],
        'score'   => ['fulltime' => ['home' => $hg, 'away' => $ag]],
        'goals'   => ['home' => $hg, 'away' => $ag],
      ];
    }
    out(['fixtures' => $fixtures]);
  }

  default:
    out(['error' => 'Unknown action.'], 400);
}
