<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Join the Prediction Game — World Cup 2026</title>
  <link rel="stylesheet" href="styles.css" />
  <script>
    window.APISPORTS_KEY = <?= json_encode(API_KEY) ?>;
    window.WC_LEAGUE     = <?= json_encode(LEAGUE_ID) ?>;
    window.WC_SEASON     = <?= json_encode(SEASON) ?>;
    window.PICK_LOCKED   = <?= (new DateTime('now') >= new DateTime(PICK_LOCK)) ? 'true' : 'false' ?>;
  </script>
</head>
<body>
  <header>
    <div class="wrap nav">
      <div class="brand"><div class="mark">WORLD<span>CUP</span></div><div class="badge">2026</div></div>
      <nav class="nav-links">
        <a href="index.html">Home</a>
        <a href="register.php" class="active">Play</a>
        <a href="leaderboard.php">Leaderboard</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <div class="page-head">
      <div class="eyebrow">Prediction Game</div>
      <h1>Pick <span class="out">Three</span></h1>
      <p>Choose three teams. Every match they play earns you points — 3 for a win, 1 for a draw, 0 for a loss, judged on the score after 90 minutes. Picks can be changed until the first kickoff.</p>
      <div id="lockpill" style="margin-top:18px"></div>
    </div>

    <div class="section">
      <div class="card" style="max-width:520px">

        <!-- REGISTER -->
        <div id="view-register">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:26px;text-transform:uppercase">Register</h2>
          <p class="note">Enter the access code you were given, then choose a nickname and a 6-digit personal PIN to protect your picks.</p>
          <p class="note" id="r_latenote" style="display:none;color:var(--gold);border:1px solid rgba(255,206,58,.4);border-radius:11px;padding:10px 12px">⚠ The tournament has already started. You can still join, but your picks are <strong>final once you register</strong>, and you'll only earn points from matches that kick off <strong>after</strong> you join — completed matches won't count.</p>
          <label>Access Code</label>
          <input id="r_uid" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="" style="max-width:120px" />
          <label>Nickname</label>
          <input id="r_nick" maxlength="40" autocomplete="off" placeholder="Shown on the leaderboard" />
          <label>Choose a 6-digit personal PIN</label>
          <input id="r_pin" inputmode="numeric" maxlength="6" autocomplete="off" placeholder="Used to log back in" />
          <label>Team 1</label><select id="r_t1" class="team"></select>
          <label>Team 2</label><select id="r_t2" class="team"></select>
          <label>Team 3</label><select id="r_t3" class="team"></select>
          <div style="margin-top:20px"><button class="btn" id="btn-register">Join the game</button></div>
          <div class="linkrow">Already registered? <a id="to-login">Log in to edit picks</a></div>
          <div class="msg" id="r_msg"></div>
        </div>

        <!-- LOGIN -->
        <div id="view-login" style="display:none">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:26px;text-transform:uppercase">Log in</h2>
          <p class="note">Enter your access code and 6-digit personal PIN to edit your picks.</p>
          <label>Access Code</label>
          <input id="l_uid" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="" style="max-width:120px" />
          <label>6-digit personal PIN</label>
          <input id="l_pin" inputmode="numeric" maxlength="6" autocomplete="off" />
          <div style="margin-top:20px"><button class="btn" id="btn-login">Log in</button></div>
          <div class="linkrow">Have an access code but not registered yet? <a id="to-register">Register instead</a></div>
          <div class="msg" id="l_msg"></div>
        </div>

        <!-- EDIT PICKS -->
        <div id="view-edit" style="display:none">
          <h2 style="font-family:'Anton',sans-serif;font-weight:400;font-size:26px;text-transform:uppercase">Your picks</h2>
          <p class="note">Signed in as <strong id="e_nick"></strong>.</p>

          <!-- Pre-kickoff: free editing -->
          <div id="e_edit_mode">
            <label>Team 1</label><select id="e_t1" class="team"></select>
            <label>Team 2</label><select id="e_t2" class="team"></select>
            <label>Team 3</label><select id="e_t3" class="team"></select>
            <div style="margin-top:20px"><button class="btn" id="btn-save">Save picks</button></div>
          </div>

          <!-- Post-kickoff: switch one team at a time -->
          <div id="e_switch_mode" style="display:none">
            <p class="note" style="color:var(--gold);border:1px solid rgba(255,206,58,.4);border-radius:11px;padding:10px 12px">⚠ The tournament has started. You can <strong>switch</strong> any team — your current team <strong>keeps the points it has earned</strong>, and the new team scores <strong>only from its next kickoff</strong> (it won't get points it already earned).</p>
            <div id="e_switch"></div>
          </div>

          <!-- Change PIN (e.g. replace a temporary PIN from the organiser) -->
          <div style="margin-top:22px;padding-top:18px;border-top:1px solid var(--line)">
            <h3 style="font-family:'Anton',sans-serif;font-weight:400;font-size:18px;text-transform:uppercase;margin-bottom:4px">Change PIN</h3>
            <p class="note">Set a new personal PIN — for example, to replace a temporary one from the organiser.</p>
            <label>Current PIN</label>
            <input id="cp_cur" inputmode="numeric" maxlength="6" autocomplete="off" />
            <label>New PIN (6 digits)</label>
            <input id="cp_new" inputmode="numeric" maxlength="6" autocomplete="off" />
            <label>Confirm new PIN</label>
            <input id="cp_new2" inputmode="numeric" maxlength="6" autocomplete="off" />
            <div style="margin-top:14px"><button class="btn ghost" id="btn-changepin" style="width:auto">Change PIN</button></div>
            <div class="msg" id="cp_msg"></div>
          </div>

          <div class="linkrow" style="margin-top:18px"><a href="leaderboard.php">View leaderboard →</a> &nbsp;·&nbsp; <a id="btn-logout">Log out</a></div>
          <div class="msg" id="e_msg"></div>
        </div>

      </div>
    </div>
  </main>

  <footer><div class="wrap foot">
    <div>Data &amp; widgets by <a href="https://api-sports.io/" target="_blank" rel="noopener">API-SPORTS</a>.</div>
    <div>FIFA World Cup 2026 · Prediction Game</div>
  </div></footer>

  <script src="common.js"></script>
  <script>
    const $ = id => document.getElementById(id);
    const show = v => ['register','login','edit'].forEach(x => $('view-'+x).style.display = (x===v?'block':'none'));
    function flash(el, text, ok){ el.textContent = text; el.className = 'msg show ' + (ok?'ok':'err'); }

    let TEAMS = [];
    function fillSelect(sel, selectedId){
      sel.innerHTML = '<option value="">— select —</option>' +
        TEAMS.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
      if (selectedId) sel.value = String(selectedId);
    }
    function readPicks(ids){
      const v = ids.map(id => parseInt($(id).value, 10)).filter(Boolean);
      if (v.length !== 3) return { err: 'Please choose three teams.' };
      if (new Set(v).size !== 3) return { err: 'Please choose three different teams.' };
      return { picks: v };
    }
    function padUID(v){ return v.length === 1 ? '0' + v : v; }

    if (window.PICK_LOCKED) {
      $('lockpill').innerHTML = '<span class="pill lock">● Tournament started — late join open</span>';
      $('r_latenote').style.display = 'block';
    } else {
      $('lockpill').innerHTML = '<span class="pill">Picks open until first kickoff</span>';
    }

    (async () => {
      try {
        TEAMS = await loadTeams();
        document.querySelectorAll('select.team').forEach(s => fillSelect(s));
      } catch (e) {
        flash($('r_msg'), 'Could not load the team list: ' + e.message, false);
      }
      const me = await api('me');
      if (me.auth) enterEdit(me);
    })();

    let FIXTURES = null, ELIM = new Set();
    async function ensureFixtures(){
      if (FIXTURES) return;
      try {
        FIXTURES = await loadFixtures();
        ELIM = new Set();
        FIXTURES.forEach(f => {
          if (!FINISHED.has(f.fixture.status.short)) return;
          const st = f.fixture.stage, w = f.fixture.winner;
          if (!st || st === 'GROUP_STAGE' || (w !== 'H' && w !== 'A')) return;
          ELIM.add((w === 'H' ? f.teams.away : f.teams.home).id);
        });
      } catch (e) { FIXTURES = []; }
    }

    function enterEdit(me){
      show('edit');
      $('e_nick').textContent = me.nickname;
      CURRENT = me.picks.slice();
      SEGMENTS = me.segments || null;
      if (me.locked) {
        $('e_edit_mode').style.display = 'none';
        $('e_switch_mode').style.display = 'block';
        renderSwitch();
      } else {
        $('e_edit_mode').style.display = 'block';
        $('e_switch_mode').style.display = 'none';
        fillSelect($('e_t1'), me.picks[0]); fillSelect($('e_t2'), me.picks[1]); fillSelect($('e_t3'), me.picks[2]);
      }
    }

    let CURRENT = [], SEGMENTS = null;
    function teamName(id){ const t = TEAMS.find(x => x.id === id); return t ? t.name : ('#' + id); }

    async function renderSwitch(){
      await ensureFixtures();
      const slotPts = (SEGMENTS && FIXTURES) ? scoreParticipant(FIXTURES, SEGMENTS).slot : {};
      const box = $('e_switch');
      box.innerHTML = CURRENT.map((id, idx) => {
        const slot = idx + 1;
        const out = ELIM.has(id);
        const pts = slotPts[slot] || 0;
        const others = CURRENT.filter((_, j) => j !== idx);
        const opts = TEAMS
          .filter(t => !ELIM.has(t.id) && !others.includes(t.id) && t.id !== id)
          .map(t => `<option value="${t.id}">${t.name}</option>`).join('');
        return `<div class="switch-slot">
          <div><strong>Slot ${slot}: ${teamName(id)}</strong> <span class="note" style="display:inline">· ${pts} pts so far${out ? ' · <span style="color:var(--coral)">OUT</span>' : ''}</span></div>
          <div style="display:flex;gap:8px;margin-top:8px">
            <select id="sw_${slot}" class="team" style="flex:1"><option value="">— switch to —</option>${opts}</select>
            <button class="btn" data-slot="${slot}" style="width:auto">Switch</button>
          </div>
        </div>`;
      }).join('');
      box.querySelectorAll('button[data-slot]').forEach(b => {
        b.onclick = () => doSwitch(parseInt(b.dataset.slot, 10));
      });
    }

    async function doSwitch(slot){
      const sel = $('sw_' + slot);
      const tid = parseInt(sel.value, 10);
      if (!tid) return flash($('e_msg'), 'Choose a team to switch to.', false);
      if (!confirm('Switch Slot ' + slot + ' to ' + teamName(tid) + '? Your current team keeps its points; the new team scores only from its next match.')) return;
      const res = await api('switch_pick', { slot, team_id: tid });
      if (res.error) return flash($('e_msg'), res.error, false);
      CURRENT = res.picks.slice();
      const me = await api('me'); SEGMENTS = me.segments || SEGMENTS;
      await renderSwitch();
      flash($('e_msg'), 'Switched! Slot ' + slot + ' is now ' + teamName(tid) + '.', true);
    }

    $('to-login').onclick    = () => show('login');
    $('to-register').onclick = () => show('register');

    $('btn-register').onclick = async () => {
      const r = readPicks(['r_t1','r_t2','r_t3']); if (r.err) return flash($('r_msg'), r.err, false);
      const res = await api('claim', {
        user_id:  padUID($('r_uid').value.trim()),
        nickname: $('r_nick').value.trim(),
        pin:      $('r_pin').value.trim(),
        picks:    r.picks,
      });
      if (res.error) return flash($('r_msg'), res.error, false);
      // Late joiners (res.late) have final picks immediately; pre-kickoff joiners can still edit.
      enterEdit({ nickname: res.nickname, picks: res.picks, locked: !!res.late });
    };

    $('btn-login').onclick = async () => {
      const res = await api('login', {
        user_id: padUID($('l_uid').value.trim()),
        pin:     $('l_pin').value.trim(),
      });
      if (res.error) return flash($('l_msg'), res.error, false);
      const me = await api('me'); enterEdit(me);
    };

    $('btn-save').onclick = async () => {
      const r = readPicks(['e_t1','e_t2','e_t3']); if (r.err) return flash($('e_msg'), r.err, false);
      const res = await api('save_picks', { picks: r.picks });
      if (res.error) return flash($('e_msg'), res.error, false);
      flash($('e_msg'), 'Saved!', true);
    };

    $('btn-changepin').onclick = async () => {
      const cur = $('cp_cur').value.trim(), nw = $('cp_new').value.trim(), nw2 = $('cp_new2').value.trim();
      if (!/^\d{6}$/.test(nw)) return flash($('cp_msg'), 'New PIN must be exactly 6 digits.', false);
      if (nw !== nw2)          return flash($('cp_msg'), 'The two new PINs do not match.', false);
      const res = await api('change_pin', { current: cur, new: nw });
      if (res.error) return flash($('cp_msg'), res.error, false);
      $('cp_cur').value = $('cp_new').value = $('cp_new2').value = '';
      flash($('cp_msg'), 'PIN changed — use your new PIN next time you log in.', true);
    };

    $('btn-logout').onclick = async () => { await api('logout'); location.reload(); };
  </script>
</body>
</html>
