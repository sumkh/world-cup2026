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

/* Same-origin call to api.php */
async function api(action, data) {
  const r = await fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data || {}),
  });
  return r.json();
}
