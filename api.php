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
  if (count(array_unique($t)) !== NUM_PICKS) return null; // must be distinct
  return $t;
}

try {
  $pdo = db();
} catch (Throwable $e) {
  out(['error' => 'Database connection failed. Check the DB settings in config.php.'], 500);
}

$action = $_GET['action'] ?? '';

switch ($action) {

  case 'claim': {
    if (locked()) out(['error' => 'Registration is closed — the tournament has started.'], 403);
    $b = body();
    $invite = trim($b['invite_pin'] ?? '');
    $nick   = trim($b['nickname'] ?? '');
    $pin    = trim($b['pin'] ?? '');
    $picks  = clean_picks($b['picks'] ?? null);

    if ($invite === '' || $nick === '' || $pin === '') out(['error' => 'All fields are required.'], 400);
    if (!preg_match('/^[A-Za-z0-9_ ]{2,40}$/', $nick)) out(['error' => 'Nickname: 2–40 letters, numbers, spaces or underscores.'], 400);
    if (!preg_match('/^\d{4,8}$/', $pin))               out(['error' => 'PIN must be 4–8 digits.'], 400);
    if (!$picks)                                        out(['error' => 'Pick exactly 3 different teams.'], 400);

    $st = $pdo->prepare('SELECT id, claimed FROM participants WHERE invite_pin = ? LIMIT 1');
    $st->execute([$invite]);
    $row = $st->fetch();
    if (!$row)            out(['error' => 'Invalid invite PIN.'], 403);
    if ($row['claimed'])  out(['error' => 'This invite PIN has already been used.'], 403);

    $st = $pdo->prepare('SELECT id FROM participants WHERE nickname = ? LIMIT 1');
    $st->execute([$nick]);
    if ($st->fetch()) out(['error' => 'That nickname is already taken.'], 409);

    $st = $pdo->prepare('UPDATE participants SET claimed=1, nickname=?, pin_hash=?, team1=?, team2=?, team3=? WHERE id=?');
    $st->execute([$nick, password_hash($pin, PASSWORD_DEFAULT), $picks[0], $picks[1], $picks[2], $row['id']]);

    $_SESSION['pid'] = (int)$row['id'];
    out(['ok' => true, 'nickname' => $nick, 'picks' => $picks]);
  }

  case 'login': {
    $b = body();
    $nick = trim($b['nickname'] ?? '');
    $pin  = trim($b['pin'] ?? '');
    $st = $pdo->prepare('SELECT * FROM participants WHERE nickname = ? AND claimed = 1 LIMIT 1');
    $st->execute([$nick]);
    $row = $st->fetch();
    if (!$row || !password_verify($pin, $row['pin_hash'])) out(['error' => 'Wrong nickname or PIN.'], 403);
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
    // Leaderboard feed. Picks are hidden until kickoff, then revealed.
    // (No PINs are ever sent to the browser.)
    $reveal = locked();
    $st = $pdo->query('SELECT nickname, team1, team2, team3 FROM participants WHERE claimed = 1 ORDER BY created_at ASC');
    $list = [];
    foreach ($st as $r) {
      $list[] = [
        'nickname' => $r['nickname'],
        'picks'    => $reveal ? [(int)$r['team1'], (int)$r['team2'], (int)$r['team3']] : null,
      ];
    }
    out(['locked' => $reveal, 'participants' => $list]);
  }

  default:
    out(['error' => 'Unknown action.'], 400);
}
