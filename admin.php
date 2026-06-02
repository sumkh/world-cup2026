<?php
/* ============================================================
   admin.php — issue invite PINs and view participants.
   Gated by ADMIN_PASSWORD (set it in config.php).
   ============================================================ */
require_once __DIR__ . '/db.php';
session_start();

if (isset($_POST['admin_login'])) {
  if (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) $_SESSION['admin'] = true;
  else $login_error = 'Wrong password.';
}
if (isset($_GET['logout'])) { unset($_SESSION['admin']); header('Location: admin.php'); exit; }

$is_admin = !empty($_SESSION['admin']);
$generated = [];

if ($is_admin && isset($_POST['make'])) {
  $n = max(1, min(200, (int)($_POST['count'] ?? 0)));
  $pdo = db();
  $st = $pdo->prepare('INSERT INTO participants (invite_pin) VALUES (?)');
  for ($i = 0; $i < $n; $i++) {
    for ($try = 0; $try < 6; $try++) {
      $pin = bin2hex(random_bytes(4)); // 8 hex chars
      try { $st->execute([$pin]); $generated[] = $pin; break; }
      catch (PDOException $e) { /* rare unique collision — try another */ }
    }
  }
}

$rows = [];
if ($is_admin) {
  $rows = db()->query('SELECT invite_pin, claimed, nickname, created_at FROM participants ORDER BY created_at DESC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin — Prediction Game</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header>
    <div class="wrap nav">
      <div class="brand"><div class="mark">WORLD<span>CUP</span></div><div class="badge">2026</div></div>
      <nav class="nav-links">
        <a href="index.html">Home</a><a href="register.php">Play</a><a href="leaderboard.php">Leaderboard</a>
        <?php if ($is_admin): ?><a href="admin.php?logout=1">Log out</a><?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <div class="page-head">
      <div class="eyebrow">Organiser</div>
      <h1>Admin</h1>
    </div>

    <div class="section">
    <?php if (!$is_admin): ?>
      <div class="card" style="max-width:420px">
        <form method="post">
          <label>Admin password</label>
          <input type="password" name="password" autocomplete="off" />
          <div style="margin-top:18px"><button class="btn" name="admin_login" value="1">Enter</button></div>
          <?php if (!empty($login_error)): ?><div class="msg show err"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
        </form>
      </div>
    <?php else: ?>
      <div class="grid two">
        <div class="card">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:24px;text-transform:uppercase">Issue invite PINs</h2>
          <p class="note">Each PIN lets one person register. Hand one PIN to each participant.</p>
          <form method="post">
            <label>How many?</label>
            <input type="number" name="count" min="1" max="200" value="10" />
            <div style="margin-top:18px"><button class="btn" name="make" value="1">Generate</button></div>
          </form>
          <?php if ($generated): ?>
            <p class="note" style="margin-top:18px;color:var(--teal)">New PINs (copy &amp; distribute now):</p>
            <div class="codeblock"><?= implode('  ·  ', array_map('htmlspecialchars', $generated)) ?></div>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:24px;text-transform:uppercase">Participants</h2>
          <p class="note"><?= count($rows) ?> slot(s) · <?= count(array_filter($rows, fn($r) => $r['claimed'])) ?> claimed</p>
          <table class="lb" style="margin-top:12px">
            <thead><tr><th>Invite PIN</th><th>Status</th><th>Nickname</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:13px"><?= htmlspecialchars($r['invite_pin']) ?></td>
                <td><?= $r['claimed'] ? '<span class="pill live">● claimed</span>' : '<span class="pill">open</span>' ?></td>
                <td><?= htmlspecialchars($r['nickname'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
    </div>
  </main>

  <footer><div class="wrap foot"><div>FIFA World Cup 2026 · Admin</div></div></footer>
</body>
</html>
