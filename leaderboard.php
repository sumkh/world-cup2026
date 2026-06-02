<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Leaderboard — World Cup 2026 Prediction Game</title>
  <link rel="stylesheet" href="styles.css" />
  <script>
    window.APISPORTS_KEY = <?= json_encode(API_KEY) ?>;
    window.WC_LEAGUE     = <?= json_encode(LEAGUE_ID) ?>;
    window.WC_SEASON     = <?= json_encode(SEASON) ?>;
  </script>
</head>
<body>
  <header>
    <div class="wrap nav">
      <div class="brand"><div class="mark">WORLD<span>CUP</span></div><div class="badge">2026</div></div>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="register.php">Play</a>
        <a href="leaderboard.php" class="active">Leaderboard</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <div class="page-head">
      <div class="eyebrow">Standings of Champions</div>
      <h1>Leader<span class="out">board</span></h1>
      <p>3 points for each win, 1 for a draw, scored on the 90-minute result and summed across all three of your teams' matches.</p>
    </div>

    <div class="section">
      <div class="toolbar">
        <div id="status"><span class="updated">Loading…</span></div>
        <button class="btn ghost" id="syncBtn" style="width:auto">↻ Sync Scores</button>
        <button class="btn ghost" id="refresh" style="width:auto">Refresh</button>
      </div>
      <div class="card" style="padding:14px 10px">
        <div id="board"><div class="empty"><span class="spin"></span></div></div>
      </div>
      <p class="note" id="lockline"></p>
    </div>
  </main>

  <footer><div class="wrap foot">
    <div>Data &amp; widgets by <a href="https://api-sports.io/" target="_blank" rel="noopener">API-SPORTS</a>.</div>
    <div>FIFA World Cup 2026 · Prediction Game</div>
  </div></footer>

  <script src="common.js"></script>
  <script>
    const $ = id => document.getElementById(id);
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function render(force) {
      $('status').innerHTML = '<span class="updated"><span class="spin"></span> Updating…</span>';
      let people, fixtures;
      try {
        [people, fixtures] = await Promise.all([ api('participants'), loadFixtures(force) ]);
      } catch (e) {
        $('board').innerHTML = '<div class="empty">Couldn\'t load scores: ' + esc(e.message) + '</div>';
        $('status').innerHTML = '<span class="updated">Update failed</span>';
        return;
      }

      const { points, names } = computeTeamPoints(fixtures);
      const locked = people.locked;
      const rows = (people.participants || []).map(p => {
        const picks = p.picks || [];
        const total = picks.reduce((s, id) => s + (points[id] || 0), 0);
        return { nick: p.nickname, picks, total, hidden: p.picks === null };
      });

      // rank: points desc, then name; ties share a rank
      rows.sort((a, b) => b.total - a.total || a.nick.localeCompare(b.nick));
      let lastPts = null, lastRank = 0;
      rows.forEach((r, i) => { r.rank = (r.total === lastPts) ? lastRank : (lastRank = i + 1); lastPts = r.total; });

      if (!rows.length) {
        $('board').innerHTML = '<div class="empty">No participants yet. Be the first to <a href="register.php" style="color:var(--teal)">join</a>.</div>';
      } else {
        const body = rows.map(r => {
          const cls = r.rank === 1 ? 'top1' : r.rank === 2 ? 'top2' : r.rank === 3 ? 'top3' : '';
          let picksHtml;
          if (r.hidden) {
            picksHtml = '<span style="color:var(--muted);font-size:12px">picks hidden until kickoff</span>';
          } else {
            picksHtml = r.picks.map(id => {
              const t = names[id] || {};
              const pts = points[id] || 0;
              const logo = t.logo ? `<img src="${esc(t.logo)}" alt="">` : '';
              return `<span>${logo}${esc(t.name || ('#' + id))} · ${pts}</span>`;
            }).join('');
          }
          return `<tr class="${cls}">
            <td class="rank">${r.rank}</td>
            <td><div class="who">${esc(r.nick)}</div><div class="picks">${picksHtml}</div></td>
            <td class="pts">${r.total}<small>PTS</small></td>
          </tr>`;
        }).join('');
        $('board').innerHTML =
          `<table class="lb"><thead><tr><th>#</th><th>Participant</th><th style="text-align:right">Points</th></tr></thead><tbody>${body}</tbody></table>`;
      }

      const now = new Date();
      $('status').innerHTML = '<span class="updated">Updated ' + now.toLocaleTimeString() + '</span>';
      $('lockline').textContent = locked
        ? 'Picks are locked. Scores update from live results.'
        : 'The tournament hasn\'t started — everyone sits on 0 and picks stay hidden until kickoff.';
    }

    $('refresh').onclick = () => render(true);

    $('syncBtn').onclick = async () => {
      const btn = $('syncBtn');
      btn.disabled = true;
      $('status').innerHTML = '<span class="updated"><span class="spin"></span> Syncing…</span>';
      try {
        const r = await fetch('cron.php', { method: 'GET' });
        const j = await r.json();
        if (j.skipped) {
          $('status').innerHTML = '<span class="updated">Scores already up to date — try again shortly</span>';
          btn.disabled = false;
        } else if (j.error) {
          $('status').innerHTML = '<span class="updated">Sync failed: ' + esc(j.error) + '</span>';
          btn.disabled = false;
        } else {
          // Sync succeeded — reload the board from fresh DB data.
          await render(true);
          btn.disabled = false;
        }
      } catch (e) {
        $('status').innerHTML = '<span class="updated">Sync error — ' + esc(e.message) + '</span>';
        btn.disabled = false;
      }
    };

    render();
    setInterval(render, 180000); // auto-refresh every 3 min
  </script>
</body>
</html>
