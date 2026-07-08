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

// ── Save visualisation toggles ──
$viz_saved = false;
if ($is_admin && isset($_POST['save_viz'])) {
  $pdo = db();
  $up = $pdo->prepare("INSERT INTO wc_meta (key, value) VALUES (?, ?)
                       ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value");
  foreach (array_keys(viz_keys()) as $k) {
    $up->execute(['viz_' . $k, isset($_POST['viz'][$k]) ? '1' : '0']);
  }
  $viz_saved = true;
}
$viz = $is_admin ? viz_settings() : [];

// ── Danger zone: clear all registrations + cached scores (testing reset) ──
$reset_done = false;
if ($is_admin && isset($_POST['reset'])) {
  if (($_POST['confirm'] ?? '') === 'RESET') {
    $pdo = db();
    // Wipe registrations back to empty slots (keeps user_id 01–32).
    $pdo->exec("UPDATE participants
                SET claimed = 0, nickname = NULL, pin_hash = NULL,
                    team1 = NULL, team2 = NULL, team3 = NULL");
    // Clear the cached fixture scores so the next sync re-pulls fresh.
    $pdo->exec("DELETE FROM wc_fixtures");
    $pdo->exec("DELETE FROM wc_meta WHERE key = 'last_sync'");
    $reset_done = true;
  } else {
    $reset_error = 'Type RESET exactly to confirm.';
  }
}

// ── Reset one participant's PIN (keeps their picks/points/history) ──
$pin_reset = null;      // ['uid'=>, 'nick'=>, 'pin'=>] on success
$pin_reset_err = null;
if ($is_admin && isset($_POST['reset_pin'])) {
  $uid = trim((string)$_POST['reset_pin']);
  if (!preg_match('/^\d{2}$/', $uid)) {
    $pin_reset_err = 'Invalid slot.';
  } else {
    $pdo = db();
    $st = $pdo->prepare('SELECT id, nickname, claimed FROM participants WHERE user_id = ? LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !$row['claimed']) {
      $pin_reset_err = "Slot $uid is not registered yet — nothing to reset.";
    } else {
      $newpin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $pdo->prepare('UPDATE participants SET pin_hash = ? WHERE id = ?')
          ->execute([password_hash($newpin, PASSWORD_DEFAULT), $row['id']]);
      $pin_reset = ['uid' => $uid, 'nick' => $row['nickname'], 'pin' => $newpin];
    }
  }
}

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

      <!-- Summary pills + action buttons -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:24px">
        <span class="pill live">● <?= $claimed ?> registered</span>
        <span class="pill"><?= $total - $claimed ?> open slots</span>
        <span class="pill"><?= $total ?> total</span>
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
          <button class="btn ghost" id="syncBtn" style="width:auto">↻ Sync Scores</button>
          <a class="btn ghost" href="export.php" style="width:auto;text-decoration:none">⬇ Export CSV</a>
        </div>
        <span class="note" id="syncStatus" style="width:100%"></span>
      </div>

      <?php if ($pin_reset): ?>
        <div class="card" style="margin-bottom:18px;border-color:rgba(31,227,170,.5)">
          <strong>New PIN for slot <?= htmlspecialchars($pin_reset['uid']) ?><?= $pin_reset['nick'] ? ' (' . htmlspecialchars($pin_reset['nick']) . ')' : '' ?>:</strong>
          <span style="font-family:'JetBrains Mono',monospace;font-size:24px;color:var(--teal);letter-spacing:3px;margin-left:8px"><?= htmlspecialchars($pin_reset['pin']) ?></span>
          <p class="note" style="margin-top:8px">Give this to the participant — they log in with their Access Code + this new PIN (their picks and points are unchanged). Shown once, so copy it now.</p>
        </div>
      <?php elseif ($pin_reset_err): ?>
        <div class="msg show err" style="margin-bottom:18px"><?= htmlspecialchars($pin_reset_err) ?></div>
      <?php endif; ?>

      <!-- Participants table -->
      <div class="card" style="padding:14px 10px">
        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;padding:0 6px">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:22px;text-transform:uppercase">All Participants</h2>
          <span class="note" id="teamStatus">Loading team names…</span>
        </div>
        <div class="table-scroll">
        <table class="lb" id="adminTable">
          <thead>
            <tr>
              <th style="width:60px">ID</th>
              <th>Status</th>
              <th>Nickname</th>
              <th>Team 1</th>
              <th>Team 2</th>
              <th>Team 3</th>
              <th>PIN</th>
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
              <td>
                <?php if ($r['claimed']): ?>
                  <form method="post" style="margin:0" onsubmit="return confirm('Reset PIN for slot <?= htmlspecialchars($r['user_id']) ?>? A new temporary PIN will be shown to give the participant.');">
                    <button class="btn ghost" name="reset_pin" value="<?= htmlspecialchars($r['user_id']) ?>" style="width:auto;padding:6px 12px;font-size:12px">Reset PIN</button>
                  </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <!-- Visualisation toggles -->
      <div class="card" style="margin-top:22px">
        <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:22px;text-transform:uppercase">Leaderboard Visualisations</h2>
        <p class="note">Choose which extra visuals appear on the public leaderboard. Turn on only what you want so participants aren't overwhelmed.</p>
        <?php if ($viz_saved): ?><div class="msg show ok">Saved — the leaderboard will reflect your choices.</div><?php endif; ?>
        <form method="post" style="margin-top:14px">
          <div style="display:grid;gap:10px">
          <?php foreach (viz_keys() as $k => $label): ?>
            <label style="display:flex;align-items:center;gap:10px;margin:0;cursor:pointer;font-weight:600">
              <input type="checkbox" name="viz[<?= $k ?>]" value="1" style="width:auto;margin:0"<?= !empty($viz[$k]) ? ' checked' : '' ?> />
              <?= htmlspecialchars($label) ?>
            </label>
          <?php endforeach; ?>
          </div>
          <div style="margin-top:18px"><button class="btn" name="save_viz" value="1" style="width:auto">Save visualisations</button></div>
        </form>
      </div>

      <!-- Danger zone: reset everything for re-testing -->
      <div class="card" style="margin-top:22px;border-color:rgba(255,84,54,.45)">
        <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:22px;text-transform:uppercase;color:var(--coral)">Danger Zone</h2>
        <p class="note">Clears <strong>all registrations</strong> (nicknames, PINs, picks) and the cached match scores, resetting slots 01–32 to empty. Use only for testing before launch. This cannot be undone.</p>
        <?php if ($reset_done): ?>
          <div class="msg show ok">Done — all registrations and cached scores were cleared.</div>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('This permanently clears ALL registrations and scores. Continue?');" style="margin-top:14px">
          <label>Type <strong>RESET</strong> to enable the button</label>
          <input type="text" name="confirm" id="confirmInput" autocomplete="off" placeholder="RESET" style="max-width:220px" />
          <?php if (!empty($reset_error)): ?><div class="msg show err"><?= htmlspecialchars($reset_error) ?></div><?php endif; ?>
          <div style="margin-top:16px">
            <button class="btn coral" name="reset" value="1" id="resetBtn" style="width:auto" disabled>Clear everything</button>
          </div>
        </form>
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
            const r = await fetch('cron.php', { method: 'GET' });
            const j = await r.json();
            if (j.error)        { st.textContent = '✗ ' + j.error; }
            else if (j.skipped) { st.textContent = `Already up to date — try again in ${j.next_in}s`; }
            else                { st.textContent = `✓ ${j.fixtures_upserted} of ${j.total} fixtures synced`; }
          } catch (e) {
            st.textContent = '✗ ' + e.message;
          }
          btn.disabled = false;
        };

        // Reset button stays disabled until the admin types RESET exactly.
        const confirmInput = document.getElementById('confirmInput');
        const resetBtn     = document.getElementById('resetBtn');
        if (confirmInput && resetBtn) {
          confirmInput.addEventListener('input', () => {
            resetBtn.disabled = confirmInput.value !== 'RESET';
          });
        }
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
