<?php
/* ============================================================
   admin.php — view all 32 participant slots, picks, and scores.
   Gated by ADMIN_PASSWORD (set it in config.php / env var).
   ============================================================ */
require_once __DIR__ . '/db.php';
session_start();

if (isset($_POST['admin_login'])) {
  $given = (string)($_POST['password'] ?? '');
  if (ADMIN_PASSWORD !== '' && hash_equals(ADMIN_PASSWORD, $given)) $_SESSION['admin'] = true;
  else $login_error = 'Wrong password.';
}
if (isset($_GET['logout'])) { unset($_SESSION['admin']); header('Location: admin.php'); exit; }

$is_admin = !empty($_SESSION['admin']);

$rows = [];
if ($is_admin) {
  $rows = db()->query(
    'SELECT user_id, claimed, nickname, team1, team2, team3, created_at
     FROM participants ORDER BY CAST(user_id AS INTEGER) ASC'
  )->fetchAll();
}
$total   = count($rows);
$claimed = count(array_filter($rows, fn($r) => $r['claimed']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin — World Cup 2026 Prediction Game</title>
  <link rel="stylesheet" href="styles.css" />
<?php if ($is_admin): ?>
  <script>
    window.APISPORTS_KEY = <?= json_encode(API_KEY) ?>;
    window.WC_LEAGUE     = <?= json_encode(LEAGUE_ID) ?>;
    window.WC_SEASON     = <?= json_encode(SEASON) ?>;
  </script>
<?php endif; ?>
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

      <!-- Summary pills + manual sync button -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:24px">
        <span class="pill live">● <?= $claimed ?> registered</span>
        <span class="pill"><?= $total - $claimed ?> open slots</span>
        <span class="pill"><?= $total ?> total</span>
        <button class="btn ghost" id="syncBtn" style="width:auto;margin-left:auto">↻ Sync Scores</button>
        <span class="note" id="syncStatus"></span>
      </div>

      <!-- Participants table -->
      <div class="card" style="padding:14px 10px">
        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;padding:0 6px">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:22px;text-transform:uppercase">All Participants</h2>
          <span class="note" id="teamStatus">Loading team names…</span>
        </div>
        <table class="lb" id="adminTable">
          <thead>
            <tr>
              <th style="width:60px">ID</th>
              <th>Status</th>
              <th>Nickname</th>
              <th>Team 1</th>
              <th>Team 2</th>
              <th>Team 3</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700"><?= htmlspecialchars($r['user_id']) ?></td>
              <td><?= $r['claimed']
                    ? '<span class="pill live">● registered</span>'
                    : '<span class="pill">open</span>' ?></td>
              <td><?= htmlspecialchars($r['nickname'] ?? '—') ?></td>
              <td class="team-cell" data-id="<?= (int)$r['team1'] ?>">
                <?= $r['claimed'] ? '<span class="spin-sm"></span>' : '—' ?>
              </td>
              <td class="team-cell" data-id="<?= (int)$r['team2'] ?>">
                <?= $r['claimed'] ? '<span class="spin-sm"></span>' : '—' ?>
              </td>
              <td class="team-cell" data-id="<?= (int)$r['team3'] ?>">
                <?= $r['claimed'] ? '<span class="spin-sm"></span>' : '—' ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Resolve team IDs → names once the API responds -->
      <script src="common.js"></script>
      <script>
        (async () => {
          try {
            const teams = await loadTeams();
            const map = {};
            teams.forEach(t => map[t.id] = t.name);
            document.querySelectorAll('.team-cell[data-id]').forEach(td => {
              const id = parseInt(td.dataset.id, 10);
              td.textContent = id ? (map[id] || '#' + id) : '—';
            });
            document.getElementById('teamStatus').textContent = '';
          } catch (e) {
            document.getElementById('teamStatus').textContent = 'Could not load team names';
          }
        })();

        document.getElementById('syncBtn').onclick = async () => {
          const btn = document.getElementById('syncBtn');
          const st  = document.getElementById('syncStatus');
          btn.disabled = true;
          st.textContent = 'Syncing…';
          try {
            const r = await fetch('cron.php?key=<?= htmlspecialchars(ADMIN_PASSWORD, ENT_QUOTES) ?>', { method: 'GET' });
            const j = await r.json();
            if (j.error) { st.textContent = '✗ ' + j.error; }
            else { st.textContent = `✓ ${j.fixtures_upserted} fixtures, ${j.teams_mapped} teams mapped`; }
          } catch (e) {
            st.textContent = '✗ ' + e.message;
          }
          btn.disabled = false;
        };
      </script>

    <?php endif; ?>
    </div>
  </main>

  <footer><div class="wrap foot"><div>FIFA World Cup 2026 · Admin</div></div></footer>

  <style>
    .spin-sm {
      display: inline-block; width: 12px; height: 12px;
      border: 2px solid var(--line); border-top-color: var(--teal);
      border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</body>
</html>
