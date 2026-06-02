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

/*
  Returns { points:{teamId:pts}, names:{teamId:{name,logo}} }.
  teamId here is our internal wc_teams.id, which matches the
  values stored in participants.team1/2/3.
  Uses score.fulltime (90'), so a penalty shootout = draw for both.
*/
function computeTeamPoints(fixtures) {
  const points = {}, names = {};
  for (const f of fixtures) {
    const h = f.teams.home, a = f.teams.away;
    names[h.id] = { name: h.name, logo: h.logo };
    names[a.id] = { name: a.name, logo: a.logo };
    if (!FINISHED.has(f.fixture.status.short)) continue;

    let fh = f.score && f.score.fulltime ? f.score.fulltime.home : null;
    let fa = f.score && f.score.fulltime ? f.score.fulltime.away : null;
    if (fh === null || fa === null) { fh = f.goals.home; fa = f.goals.away; }
    if (fh === null || fa === null) continue;

    points[h.id] = points[h.id] || 0;
    points[a.id] = points[a.id] || 0;
    if      (fh > fa) { points[h.id] += 3; }
    else if (fh < fa) { points[a.id] += 3; }
    else              { points[h.id] += 1; points[a.id] += 1; }
  }
  return { points, names };
}

/* Same-origin call to api.php */
async function api(action, data) {
  const r = await fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data || {}),
  });
  return r.json();
}
