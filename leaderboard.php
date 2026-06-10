<?php
require_once __DIR__ . '/db.php';
$VIZ = ['heatmap' => false, 'alive' => false, 'bracket' => false, 'detail' => false, 'whatif' => false, 'digest' => false];
try { $VIZ = viz_settings(); } catch (Throwable $e) { /* DB down — show leaderboard only */ }
?>
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
    window.VIZ           = <?= json_encode($VIZ) ?>;
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
      <p>3 points for each win, 1 for a draw, scored on the 90-minute result and summed across all three of your teams' matches — every round, all 104 games. End-of-tournament bonuses: Champion +20, Runner-up +10, Third place +5.</p>
    </div>

    <div class="section">
      <div class="toolbar">
        <div id="status"><span class="updated">Loading…</span></div>
        <button class="btn ghost" id="syncBtn" style="width:auto">↻ Sync Scores</button>
        <button class="btn ghost" id="refresh" style="width:auto">Refresh</button>
      </div>
      <div class="card" style="padding:14px 10px">
        <div class="table-scroll"><div id="board"><div class="empty"><span class="spin"></span></div></div></div>
      </div>
      <p class="note" id="lockline"></p>

      <!-- Admin-toggled visualisations render here -->
      <div id="viz"></div>
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

    /* Shared state from the last render, used by the visualisations. */
    let LAST = {};
    const V = window.VIZ || {};

    const STAGE_RANK  = {GROUP_STAGE:0,LAST_32:1,ROUND_OF_32:1,LAST_16:2,QUARTER_FINALS:3,QUARTER_FINAL:3,SEMI_FINALS:4,SEMI_FINAL:4,THIRD_PLACE:5,FINAL:6};
    const STAGE_LABEL = {GROUP_STAGE:'Grp',LAST_32:'R32',ROUND_OF_32:'R32',LAST_16:'R16',QUARTER_FINALS:'QF',QUARTER_FINAL:'QF',SEMI_FINALS:'SF',SEMI_FINAL:'SF',THIRD_PLACE:'3rd',FINAL:'Fin'};
    const stageRank = f => (STAGE_RANK[f.fixture.stage] ?? 9);

    /* Derive eliminations + per-team fixtures + final positions from the feed. */
    function deriveTournament(fixtures) {
      const eliminated = new Set(), byTeam = {};
      let championId = null, runnerId = null, thirdId = null;
      for (const f of fixtures) {
        const h = f.teams.home.id, a = f.teams.away.id;
        (byTeam[h] = byTeam[h] || []).push(f);
        (byTeam[a] = byTeam[a] || []).push(f);
        const fin = FINISHED.has(f.fixture.status.short);
        const stage = f.fixture.stage, w = f.fixture.winner;
        if (fin && stage && stage !== 'GROUP_STAGE' && (w === 'H' || w === 'A')) {
          eliminated.add(w === 'H' ? a : h);                 // knockout loser is out
          if (stage === 'FINAL')       { championId = w === 'H' ? h : a; runnerId = w === 'H' ? a : h; }
          if (stage === 'THIRD_PLACE') { thirdId    = w === 'H' ? h : a; }
        }
      }
      return { eliminated, byTeam, championId, runnerId, thirdId };
    }

    /* Engaging, participant-focused descriptions — shown whether the visual is
       live or still waiting for kickoff. */
    const VIZ_DESC = {
      heatmap: 'See where the crowd went. Teams at the top are the popular, safe bets — if you backed someone far down this list, you\'re the contrarian who\'ll rocket up the table if they deliver.',
      alive:   'Your lifelines at a glance — green teams are still playing and can keep banking points for you; struck-through teams are knocked out and frozen. The more green you see, the more upside you have left. (Tracks knockout exits; group-stage eliminations aren\'t shown.)',
      bracket: 'Follow each picked team\'s journey, round by round. Every green W banked you 3 points, a gold D 1 point, and a red L nothing — a fast way to spot which of your teams is actually carrying your score.',
      whatif:  'Play out the ending. Crown any team champion and watch the table rebound with the +20 bonus — see exactly who you need to lift the trophy for you to climb (or hold on to) the top spots.',
      digest:  'The story so far in one glance — who\'s leading, by how much, how many teams are still standing, and the latest results. Hit copy to drop the update straight into your group chat and stir up some banter.',
    };

    const revealed = () => LAST.people && LAST.people.locked;
    const vizCard = (title, inner, sub) =>
      `<div class="card viz-card"><h2 class="viz-h">${esc(title)}</h2>${sub ? `<p class="note">${sub}</p>` : ''}${inner}</div>`;
    const notYet = (title, sub) => vizCard(title, '<div class="empty">Available once picks lock at kickoff.</div>', sub);

    /* ── Per-player detail (expandable leaderboard rows) ── */
    function detailHtml(r) {
      const { names, derived } = LAST;
      const segBySlot = { 1: [], 2: [], 3: [] };
      (r.segments || []).forEach(s => { (segBySlot[s.slot] = segBySlot[s.slot] || []).push(s); });
      const lines = [1, 2, 3].map(sl => {
        const id = r.current[sl - 1];
        const t = names[id] || {};
        const pts = (r.slotPts && r.slotPts[sl]) || 0;
        const segs = segBySlot[sl] || [];
        const note = derived.eliminated.has(id)
          ? '<span style="color:var(--coral)">out</span>'
          : '<span style="color:var(--teal)">alive</span>';
        let hist = '';
        if (segs.length > 1) {
          const chain = segs.map(s => esc((names[s.team] || {}).name || ('#' + s.team))).join(' → ');
          hist = `<div style="font-size:11px;color:var(--muted);margin-top:2px">switched: ${chain}</div>`;
        }
        return `<div class="detail-line"><span>Slot ${sl}: ${esc(t.name || ('#' + id))}${hist}</span><span>${pts} pts · ${note}</span></div>`;
      }).join('');
      return `<div class="detail-box">${lines}</div>`;
    }
    function wireDetail() {
      document.querySelectorAll('tr.viz-click').forEach(tr => {
        tr.onclick = () => {
          const d = document.querySelector(`tr.detail-row[data-detail="${tr.dataset.idx}"]`);
          if (d) d.style.display = d.style.display === 'none' ? 'table-row' : 'none';
        };
      });
    }

    /* ── Visualisation cards ── */
    function vizHeatmap() {
      if (!revealed()) return notYet('Pick Popularity', VIZ_DESC.heatmap);
      const tally = {};
      (LAST.people.participants || []).forEach(p => (p.current || []).forEach(id => tally[id] = (tally[id] || 0) + 1));
      const ids = Object.keys(tally).sort((a, b) => tally[b] - tally[a]);
      if (!ids.length) return vizCard('Pick Popularity', '<div class="empty">No picks yet.</div>');
      const max = tally[ids[0]];
      const inner = ids.map(id => {
        const t = LAST.names[id] || {}, n = tally[id], pct = Math.round(n / max * 100);
        const logo = t.logo ? `<img src="${esc(t.logo)}" alt="">` : '';
        return `<div class="bar-row"><span class="bar-lbl">${logo}${esc(t.name || ('#'+id))}</span>`
             + `<span class="bar-track"><span class="bar-fill" style="width:${pct}%"></span></span><span class="bar-num">${n}</span></div>`;
      }).join('');
      return vizCard('Pick Popularity', inner, VIZ_DESC.heatmap);
    }

    function vizAlive() {
      if (!revealed()) return notYet('Teams Still Alive', VIZ_DESC.alive);
      const el = LAST.derived.eliminated;
      const inner = (LAST.people.participants || []).map(p => {
        const chips = (p.current || []).map(id => {
          const t = LAST.names[id] || {}, out = el.has(id);
          return `<span class="chip ${out ? 'out' : 'alive'}">${esc(t.name || ('#'+id))}</span>`;
        }).join('');
        const aliveN = (p.current || []).filter(id => !el.has(id)).length;
        return `<div class="alive-row"><span class="who">${esc(p.nickname)}</span><span class="chips">${chips}</span><span class="bar-num">${aliveN}/3</span></div>`;
      }).join('');
      return vizCard('Teams Still Alive', inner, VIZ_DESC.alive);
    }

    function matchChip(f, id) {
      const isHome = f.teams.home.id === id, opp = isHome ? f.teams.away : f.teams.home;
      const lbl = STAGE_LABEL[f.fixture.stage] || '';
      if (!FINISHED.has(f.fixture.status.short)) return `<span class="mchip pend" title="vs ${esc(opp.name)}">${lbl}·–</span>`;
      const gh = f.score.fulltime.home, ga = f.score.fulltime.away;
      const my = isHome ? gh : ga, ot = isHome ? ga : gh;
      let res = 'D', cls = 'd';
      if (my > ot) { res = 'W'; cls = 'w'; } else if (my < ot) { res = 'L'; cls = 'l'; }
      return `<span class="mchip ${cls}" title="vs ${esc(opp.name)} ${gh}-${ga}">${lbl}·${res}</span>`;
    }

    function vizBracket() {
      if (!revealed()) return notYet('Team Paths', VIZ_DESC.bracket);
      const picked = new Set();
      (LAST.people.participants || []).forEach(p => (p.current || []).forEach(id => picked.add(id)));
      if (!picked.size) return vizCard('Team Paths', '<div class="empty">No picks yet.</div>');
      const ids = [...picked].sort((a, b) => (LAST.points[b] || 0) - (LAST.points[a] || 0));
      const inner = ids.map(id => {
        const t = LAST.names[id] || {};
        const fxs = (LAST.derived.byTeam[id] || []).slice().sort((x, y) =>
          stageRank(x) - stageRank(y) || (new Date(x.fixture.date || 0) - new Date(y.fixture.date || 0)));
        const logo = t.logo ? `<img src="${esc(t.logo)}" alt="">` : '';
        return `<div class="path-row"><span class="path-team">${logo}${esc(t.name || ('#'+id))}</span>`
             + `<span class="path-chips">${fxs.map(f => matchChip(f, id)).join('')}</span></div>`;
      }).join('');
      return vizCard('Team Paths', `<div class="table-scroll">${inner}</div>`, VIZ_DESC.bracket);
    }

    function vizWhatif() {
      if (!revealed()) return notYet('Projected Finish', VIZ_DESC.whatif);
      const el = LAST.derived.eliminated;
      const ids = Object.keys(LAST.names).filter(id => !el.has(+id))
        .sort((a, b) => (LAST.names[a].name || '').localeCompare(LAST.names[b].name || ''));
      if (!ids.length) return vizCard('Projected Finish', '<div class="empty">No teams left to project.</div>');
      const opts = ids.map(id => `<option value="${id}">${esc(LAST.names[id].name)}</option>`).join('');
      return vizCard('Projected Finish',
        `<label style="margin-top:0">If this team wins the cup…</label>`
        + `<select id="whatifSel" style="max-width:280px">${opts}</select><div id="whatifOut" style="margin-top:14px"></div>`,
        VIZ_DESC.whatif);
    }
    function wireWhatif() {
      const sel = $('whatifSel'); if (!sel) return;
      const run = () => {
        const champ = parseInt(sel.value, 10);
        const rows = (LAST.people.participants || []).filter(p => p.current).map(p => {
          let total = LAST.totals[p.nickname] || 0;           // join-filtered base
          const boosted = p.current.includes(champ);
          if (boosted) total += Math.max(0, 20 - (LAST.bonus[champ] || 0)); // don't double-count if already champion
          return { nick: p.nickname, total, boosted };
        });
        rows.sort((a, b) => b.total - a.total || a.nick.localeCompare(b.nick));
        $('whatifOut').innerHTML = rows.map((r, i) =>
          `<div class="proj-row${r.boosted ? ' boosted' : ''}"><span>${i + 1}. ${esc(r.nick)}</span><span>${r.total}${r.boosted ? ' ▲' : ''}</span></div>`).join('');
      };
      sel.onchange = run; run();
    }

    function vizDigest() {
      const finished = LAST.fixtures.filter(f => FINISHED.has(f.fixture.status.short));
      let leader = null, gap = null;
      if (revealed()) {
        const rows = Object.keys(LAST.totals)
          .map(n => ({ nick: n, total: LAST.totals[n] }))
          .sort((a, b) => b.total - a.total);
        if (rows.length) { leader = rows[0]; if (rows[1]) gap = rows[0].total - rows[1].total; }
      }
      const aliveCount = Object.keys(LAST.names).filter(id => !LAST.derived.eliminated.has(+id)).length;
      const recent = finished.slice().sort((a, b) => new Date(b.fixture.date || 0) - new Date(a.fixture.date || 0)).slice(0, 5)
        .map(f => `${f.teams.home.name} ${f.score.fulltime.home}–${f.score.fulltime.away} ${f.teams.away.name}`);
      let txt = '🏆 World Cup 2026 pool update\n';
      txt += `Matches scored: ${finished.length} / 104\n`;
      if (leader) txt += `Leader: ${leader.nick} (${leader.total} pts${gap != null ? `, +${gap} ahead` : ''})\n`;
      txt += `Teams still alive: ${aliveCount}\n`;
      if (recent.length) txt += 'Recent results:\n' + recent.map(r => '• ' + r).join('\n');
      return vizCard('Match-day Digest',
        `<pre class="digest" id="digestText">${esc(txt)}</pre>`
        + `<button class="btn ghost" id="digestCopy" style="width:auto;margin-top:10px">Copy for group chat</button>`,
        VIZ_DESC.digest);
    }
    function wireDigest() {
      const b = $('digestCopy'); if (!b) return;
      b.onclick = async () => {
        try { await navigator.clipboard.writeText($('digestText').textContent); b.textContent = 'Copied!'; }
        catch (e) { b.textContent = 'Copy failed'; }
        setTimeout(() => b.textContent = 'Copy for group chat', 1500);
      };
    }

    function renderViz() {
      const host = $('viz'); if (!host || !LAST.fixtures) return;
      host.innerHTML = '';
      if (V.heatmap) host.insertAdjacentHTML('beforeend', vizHeatmap());
      if (V.alive)   host.insertAdjacentHTML('beforeend', vizAlive());
      if (V.bracket) host.insertAdjacentHTML('beforeend', vizBracket());
      if (V.whatif)  { host.insertAdjacentHTML('beforeend', vizWhatif()); wireWhatif(); }
      if (V.digest)  { host.insertAdjacentHTML('beforeend', vizDigest()); wireDigest(); }
    }

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

      // Aggregate team points/names — used for names + the what-if bonus check.
      const { points, bonus, names } = computeTeamPoints(fixtures);
      const derived = deriveTournament(fixtures);
      const locked = people.locked;

      // Per-participant scoring from each player's pick-segment history:
      // a match scores for a slot only if it kicked off while that team was held.
      const totalsByNick = {};
      const rows = (people.participants || []).map(p => {
        const current = p.current || [];
        const sc = p.segments ? scoreParticipant(fixtures, p.segments) : { total: 0, slot: {} };
        if (p.nickname != null) totalsByNick[p.nickname] = sc.total;
        // how many distinct teams have held each slot (to flag switches)
        const segCount = {};
        (p.segments || []).forEach(s => { segCount[s.slot] = (segCount[s.slot] || 0) + 1; });
        return { nick: p.nickname, current, segments: p.segments, total: sc.total,
                 slotPts: sc.slot, segCount, hidden: p.current === null };
      });

      // rank: points desc, then name; ties share a rank
      rows.sort((a, b) => b.total - a.total || a.nick.localeCompare(b.nick));
      let lastPts = null, lastRank = 0;
      rows.forEach((r, i) => { r.rank = (r.total === lastPts) ? lastRank : (lastRank = i + 1); lastPts = r.total; });

      LAST = { people, fixtures, points, bonus, names, locked, derived, totals: totalsByNick };

      if (!rows.length) {
        $('board').innerHTML = '<div class="empty">No participants yet. Be the first to <a href="register.php" style="color:var(--teal)">join</a>.</div>';
      } else {
        const body = rows.map((r, i) => {
          const cls = r.rank === 1 ? 'top1' : r.rank === 2 ? 'top2' : r.rank === 3 ? 'top3' : '';
          let picksHtml;
          if (r.hidden) {
            picksHtml = '<span style="color:var(--muted);font-size:12px">picks hidden until kickoff</span>';
          } else {
            picksHtml = r.current.map((id, idx) => {
              const slot = idx + 1;
              const t = names[id] || {};
              const pts = (r.slotPts && r.slotPts[slot]) || 0;
              const out = derived.eliminated.has(id);
              const logo = t.logo ? `<img src="${esc(t.logo)}" alt="">` : '';
              const swap = (r.segCount[slot] || 1) > 1 ? ' <span style="color:var(--muted)" title="switched">⇄</span>' : '';
              const outTag = out ? ' <span style="color:var(--coral);font-weight:700">OUT</span>' : '';
              return `<span${out ? ' style="opacity:.55"' : ''}>${logo}${esc(t.name || ('#' + id))} · ${pts}${swap}${outTag}</span>`;
            }).join('');
          }
          const clickable = (V.detail && !r.hidden) ? ' viz-click' : '';
          let html = `<tr class="${cls}${clickable}" data-idx="${i}">
            <td class="rank">${r.rank}</td>
            <td><div class="who">${esc(r.nick)}${clickable ? ' <span class="caret">▾</span>' : ''}</div><div class="picks">${picksHtml}</div></td>
            <td class="pts">${r.total}<small>PTS</small></td>
          </tr>`;
          if (clickable) html += `<tr class="detail-row" data-detail="${i}" style="display:none"><td></td><td colspan="2">${detailHtml(r)}</td></tr>`;
          return html;
        }).join('');
        $('board').innerHTML =
          `<table class="lb"><thead><tr><th>#</th><th>Participant</th><th style="text-align:right">Points</th></tr></thead><tbody>${body}</tbody></table>`;
        if (V.detail) wireDetail();
      }

      const now = new Date();
      $('status').innerHTML = '<span class="updated">Updated ' + now.toLocaleTimeString() + '</span>';
      $('lockline').textContent = locked
        ? 'Scores update from live results. You can switch a team anytime (Log in to play) — your old team keeps the points it earned and the new team scores only from its next kickoff. Latecomers can still join too.'
        : 'The tournament hasn\'t started — everyone sits on 0 and picks stay hidden until kickoff.';

      renderViz();
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
