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

/* Team ids knocked out of the tournament (lost a finished knockout match). */
function eliminated_ids($pdo) {
  $sql = "SELECT DISTINCT CASE WHEN winner='H' THEN away_id ELSE home_id END AS out_id
          FROM wc_fixtures
          WHERE status='FT' AND stage IS NOT NULL AND stage <> 'GROUP_STAGE'
            AND winner IN ('H','A')";
  $ids = [];
  foreach ($pdo->query($sql) as $r) $ids[(int)$r['out_id']] = true;
  return $ids;
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

    // Open a pick segment per slot (starts now; only future matches score).
    $seg = $pdo->prepare('INSERT INTO pick_segments (participant_id, slot, team_id, start_at) VALUES (?, ?, ?, NOW())');
    foreach ([1 => $picks[0], 2 => $picks[1], 3 => $picks[2]] as $slot => $tid) $seg->execute([$row['id'], $slot, $tid]);

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
    // Segments let the Switch screen show points earned per slot so far.
    $segSt = $pdo->prepare("SELECT slot, team_id,
                to_char(start_at AT TIME ZONE 'UTC','YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS s,
                to_char(end_at   AT TIME ZONE 'UTC','YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS e
                FROM pick_segments WHERE participant_id = ? ORDER BY slot, start_at");
    $segSt->execute([$_SESSION['pid']]);
    $segs = [];
    foreach ($segSt as $r) $segs[] = ['slot' => (int)$r['slot'], 'team' => (int)$r['team_id'], 'start' => $r['s'], 'end' => $r['e']];
    out(['auth' => true, 'nickname' => $row['nickname'],
         'picks' => [(int)$row['team1'], (int)$row['team2'], (int)$row['team3']],
         'locked' => locked(), 'segments' => $segs]);
  }

  case 'save_picks': {
    // Pre-kickoff only: free editing. No matches have been played yet, so we
    // just replace the team in each open segment (no history to preserve).
    if (empty($_SESSION['pid'])) out(['error' => 'You are not logged in.'], 403);
    if (locked())                out(['error' => 'Picks are locked — use Switch to change a team now.'], 403);
    $picks = clean_picks(body()['picks'] ?? null);
    if (!$picks) out(['error' => 'Pick exactly 3 different teams.'], 400);
    $pid = $_SESSION['pid'];
    $st = $pdo->prepare('UPDATE participants SET team1=?, team2=?, team3=? WHERE id=?');
    $st->execute([$picks[0], $picks[1], $picks[2], $pid]);
    $up = $pdo->prepare('UPDATE pick_segments SET team_id=? WHERE participant_id=? AND slot=? AND end_at IS NULL');
    foreach ([1 => $picks[0], 2 => $picks[1], 3 => $picks[2]] as $slot => $tid) $up->execute([$tid, $pid, $slot]);
    out(['ok' => true, 'picks' => $picks]);
  }

  case 'switch_pick': {
    // Post-kickoff: change one slot's team. Old team keeps points up to now;
    // new team scores only from its next kickoff. Unlimited switches.
    if (empty($_SESSION['pid'])) out(['error' => 'You are not logged in.'], 403);
    if (!locked())               out(['error' => 'Switching opens once the tournament starts. Edit your picks freely until then.'], 403);
    $b    = body();
    $slot = (int)($b['slot'] ?? 0);
    $tid  = (int)($b['team_id'] ?? 0);
    if (!in_array($slot, [1, 2, 3], true)) out(['error' => 'Invalid slot.'], 400);
    if ($tid <= 0)                          out(['error' => 'Choose a team.'], 400);

    $st = $pdo->prepare('SELECT team1, team2, team3 FROM participants WHERE id = ? LIMIT 1');
    $st->execute([$_SESSION['pid']]);
    $cur = $st->fetch();
    if (!$cur) out(['error' => 'You are not logged in.'], 403);
    $current = [1 => (int)$cur['team1'], 2 => (int)$cur['team2'], 3 => (int)$cur['team3']];

    if ($current[$slot] === $tid) out(['error' => 'That team is already in this slot.'], 400);
    foreach ($current as $sl => $t) if ($sl !== $slot && $t === $tid) out(['error' => 'You already hold that team in another slot.'], 409);

    $st = $pdo->prepare('SELECT 1 FROM wc_teams WHERE id = ? LIMIT 1');
    $st->execute([$tid]);
    if (!$st->fetch()) out(['error' => 'Unknown team.'], 400);
    if (isset(eliminated_ids($pdo)[$tid])) out(['error' => 'That team is already knocked out — pick one still in the tournament.'], 409);

    // Close the open segment for this slot, open a new one, update current team.
    $pdo->prepare('UPDATE pick_segments SET end_at = NOW() WHERE participant_id = ? AND slot = ? AND end_at IS NULL')
        ->execute([$_SESSION['pid'], $slot]);
    $pdo->prepare('INSERT INTO pick_segments (participant_id, slot, team_id, start_at) VALUES (?, ?, ?, NOW())')
        ->execute([$_SESSION['pid'], $slot, $tid]);
    $col = 'team' . $slot;
    $pdo->prepare("UPDATE participants SET $col = ? WHERE id = ?")->execute([$tid, $_SESSION['pid']]);

    $current[$slot] = $tid;
    out(['ok' => true, 'picks' => [$current[1], $current[2], $current[3]]]);
  }

  case 'logout': {
    session_destroy();
    out(['ok' => true]);
  }

  case 'participants': {
    // Leaderboard feed. Picks are hidden until kickoff; after kickoff we send
    // each player's CURRENT teams plus their full pick-segment history (with
    // ISO-8601 UTC start/end) so the browser can score by kickoff window.
    $reveal = locked();
    $rows = $pdo->query('SELECT id, nickname, team1, team2, team3 FROM participants WHERE claimed = 1 ORDER BY created_at ASC')->fetchAll();

    $segByPid = [];
    if ($reveal) {
      $segSt = $pdo->query("SELECT participant_id, slot, team_id,
                 to_char(start_at AT TIME ZONE 'UTC','YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS s,
                 to_char(end_at   AT TIME ZONE 'UTC','YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"') AS e
                 FROM pick_segments ORDER BY participant_id, slot, start_at");
      foreach ($segSt as $r) {
        $segByPid[(int)$r['participant_id']][] = [
          'slot' => (int)$r['slot'], 'team' => (int)$r['team_id'], 'start' => $r['s'], 'end' => $r['e'],
        ];
      }
    }

    $list = [];
    foreach ($rows as $r) {
      $pid = (int)$r['id'];
      $list[] = [
        'nickname' => $r['nickname'],
        'current'  => $reveal ? [(int)$r['team1'], (int)$r['team2'], (int)$r['team3']] : null,
        'segments' => $reveal ? ($segByPid[$pid] ?? []) : null,
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
