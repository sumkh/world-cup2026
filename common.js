/* ============================================================
   common.js — runs in the visitor's browser.
   - Teams and fixtures are served from the local api.php cache
     (populated by cron.php) so the browser never calls API-SPORTS
     directly for game data.
   - computeTeamPoints() uses our internal wc_teams IDs, which
     match the IDs stored in participants.team1/team2/team3.
   window.APISPORTS_KEY / WC_LEAGUE / WC_SEASON are still injected
   by PHP (used by the widgets on index.html).
   ============================================================ */

/* Team list — cached in localStorage for 1 hour.
   Served from api.php?action=teams (wc_teams table). */
async function loadTeams() {
  const ck = 'wc_teams_local_v1';
  try {
    const c = JSON.parse(localStorage.getItem(ck));
    if (c && c.t > Date.now() - 3600000) return c.d;
  } catch (e) {}
  const res = await api('teams');
  if (res.error) throw new Error(res.error);
  const teams = res.teams || [];
  try { localStorage.setItem(ck, JSON.stringify({ t: Date.now(), d: teams })); } catch (e) {}
  return teams;
}

/* Fixture results — cached in memory for 90 s.
   Served from api.php?action=fixtures (wc_fixtures table). */
let _fxCache = null;
async function loadFixtures(force) {
  if (!force && _fxCache && _fxCache.t > Date.now() - 90000) return _fxCache.d;
  const res = await api('fixtures');
  if (res.error) throw new Error(res.error);
  const data = res.fixtures || [];
  _fxCache = { t: Date.now(), d: data };
  return data;
}

/* A match counts once its regular time is decided. */
const FINISHED = new Set(['FT', 'AET', 'PEN']);

/* End-of-tournament bonus points (added to a team's total once the
   relevant knockout match is finished). */
const BONUS = { CHAMPION: 20, RUNNER_UP: 10, THIRD: 5 };

/*
  Returns { points:{teamId:pts}, bonus:{teamId:pts}, names:{teamId:{name,logo}} }.
  - points = match points + any earned bonus (the grand total per team).
  - bonus  = just the bonus portion, so the UI can label it separately.
  teamId is our internal wc_teams.id, matching participants.team1/2/3.
  Match points use score.fulltime (90'): a penalty shootout = draw for both.
  Bonuses use fixture.winner (true result incl. ET/penalties) on the
  FINAL and THIRD_PLACE matches.

  sinceMs (optional): for late joiners — only count matches that kick off at
  or after this epoch time. Matches completed before they joined don't score.
  names is always built for every team, regardless of sinceMs.
*/
function computeTeamPoints(fixtures, sinceMs) {
  sinceMs = sinceMs || 0;
  const points = {}, bonus = {}, names = {};
  const add = (id, n) => { points[id] = (points[id] || 0) + n; };
  const addBonus = (id, n) => { bonus[id] = (bonus[id] || 0) + n; add(id, n); };

  for (const f of fixtures) {
    const h = f.teams.home, a = f.teams.away;
    names[h.id] = { name: h.name, logo: h.logo };
    names[a.id] = { name: a.name, logo: a.logo };
    if (!FINISHED.has(f.fixture.status.short)) continue;
    // Skip matches that kicked off before the join time (late joiners).
    if (sinceMs && f.fixture.date && new Date(f.fixture.date).getTime() < sinceMs) continue;

    // ── Match points (90-minute result) ──
    let fh = f.score && f.score.fulltime ? f.score.fulltime.home : null;
    let fa = f.score && f.score.fulltime ? f.score.fulltime.away : null;
    if (fh === null || fa === null) { fh = f.goals.home; fa = f.goals.away; }
    if (fh === null || fa === null) continue;

    points[h.id] = points[h.id] || 0;
    points[a.id] = points[a.id] || 0;
    if      (fh > fa) { add(h.id, 3); }
    else if (fh < fa) { add(a.id, 3); }
    else              { add(h.id, 1); add(a.id, 1); }

    // ── End-of-tournament bonuses ──
    const stage = f.fixture.stage, w = f.fixture.winner;
    if (stage === 'FINAL' && (w === 'H' || w === 'A')) {
      const champ = w === 'H' ? h.id : a.id;
      const runner = w === 'H' ? a.id : h.id;
      addBonus(champ, BONUS.CHAMPION);
      addBonus(runner, BONUS.RUNNER_UP);
    } else if (stage === 'THIRD_PLACE' && (w === 'H' || w === 'A')) {
      addBonus(w === 'H' ? h.id : a.id, BONUS.THIRD);
    }
  }
  return { points, bonus, names };
}

/* Points (match + bonus) a single team earned in one finished fixture.
   Returns 0 if the team isn't in this fixture or it isn't finished. */
function fixtureTeamPoints(f, teamId) {
  if (!FINISHED.has(f.fixture.status.short)) return 0;
  const h = f.teams.home, a = f.teams.away;
  if (teamId !== h.id && teamId !== a.id) return 0;

  let fh = f.score && f.score.fulltime ? f.score.fulltime.home : null;
  let fa = f.score && f.score.fulltime ? f.score.fulltime.away : null;
  if (fh === null || fa === null) { fh = f.goals.home; fa = f.goals.away; }
  if (fh === null || fa === null) return 0;

  const isHome = teamId === h.id;
  const my = isHome ? fh : fa, ot = isHome ? fa : fh;
  let pts = (my > ot) ? 3 : (my === ot ? 1 : 0);

  const stage = f.fixture.stage, w = f.fixture.winner;
  if (stage === 'FINAL' && (w === 'H' || w === 'A')) {
    const champ = w === 'H' ? h.id : a.id, runner = w === 'H' ? a.id : h.id;
    if (teamId === champ) pts += BONUS.CHAMPION; else if (teamId === runner) pts += BONUS.RUNNER_UP;
  } else if (stage === 'THIRD_PLACE' && (w === 'H' || w === 'A')) {
    if (teamId === (w === 'H' ? h.id : a.id)) pts += BONUS.THIRD;
  }
  return pts;
}

/* Score one participant from their pick-segment history.
   segments: [{slot, team, start, end}] — end null = still active.
   A match scores for a slot only if its kickoff is within that segment's
   window [start, end). Returns { total, slot:{1:pts,2:pts,3:pts} }. */
function scoreParticipant(fixtures, segments) {
  const slot = { 1: 0, 2: 0, 3: 0 };
  let total = 0;
  for (const seg of (segments || [])) {
    const s = new Date(seg.start).getTime();
    const e = seg.end ? new Date(seg.end).getTime() : Infinity;
    for (const f of fixtures) {
      if (!FINISHED.has(f.fixture.status.short)) continue;
      const k = f.fixture.date ? new Date(f.fixture.date).getTime() : null;
      if (k === null || k < s || k >= e) continue;
      const p = fixtureTeamPoints(f, seg.team);
      if (p) { slot[seg.slot] = (slot[seg.slot] || 0) + p; total += p; }
    }
  }
  return { total, slot };
}

/* ── Archive mode ────────────────────────────────────────────────────────────
   The live Render deployment (PHP + PostgreSQL) was retired after the
   tournament. When api.php is unreachable — e.g. this repo served straight
   from GitHub Pages, or opened as local files — the three read-only actions
   fall back to the final data snapshot in backup/api/, so the hub still
   renders the complete 2026 schedule, standings, squads and leaderboard.
   Write actions (register / login / save picks) need the backend and report
   that plainly. See backup/README.md. */
const ARCHIVE_ACTIONS = { teams: 1, fixtures: 1, participants: 1 };
const ARCHIVE_DIR     = 'backup/api/';
let _archiveMode = false;

function archiveNotice() {
  if (document.getElementById('archiveNotice')) return;
  const d = document.createElement('div');
  d.id = 'archiveNotice';
  d.textContent = 'Archived snapshot — the live backend is retired; showing final results as of 8 Jul 2026.';
  d.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:8px 14px;'
    + 'font:500 13px/1.4 system-ui,sans-serif;text-align:center;color:#0b1220;'
    + 'background:#facc15;box-shadow:0 -2px 12px rgba(0,0,0,.25)';
  (document.body || document.documentElement).appendChild(d);
}

/* Same-origin call to api.php, with a static-snapshot fallback (see above). */
async function api(action, data) {
  if (!_archiveMode) {
    try {
      const r = await fetch('api.php?action=' + encodeURIComponent(action), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data || {}),
      });
      // A static host serves api.php as text (or 404s) rather than running it,
      // so demand a real JSON response before trusting it.
      if (r.ok && (r.headers.get('content-type') || '').includes('json')) {
        return await r.json();
      }
    } catch (e) { /* network/CORS failure — fall through to the snapshot */ }
    _archiveMode = true;
    try { archiveNotice(); } catch (e) {}
  }

  if (!ARCHIVE_ACTIONS[action]) {
    return { error: 'This archived copy is read-only — the prediction game backend has been retired.' };
  }
  try {
    const r = await fetch(ARCHIVE_DIR + action + '.json');
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return await r.json();
  } catch (e) {
    return { error: 'Archived snapshot missing: ' + ARCHIVE_DIR + action + '.json' };
  }
}
