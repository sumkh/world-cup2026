/* ============================================================
   common.js — runs in the visitor's browser.
   - Reads World Cup teams + fixtures directly from API-SPORTS
     (same client-side path the widget uses).
   - Computes points using the REGULAR-TIME (90') result:
       win = 3, draw = 1, loss = 0.
   window.APISPORTS_KEY / WC_LEAGUE / WC_SEASON are injected by PHP.
   ============================================================ */
const AS = {
  base:   'https://v3.football.api-sports.io',
  key:    window.APISPORTS_KEY,
  league: window.WC_LEAGUE || 1,
  season: window.WC_SEASON || 2026,
};

async function asGet(path) {
  const r = await fetch(AS.base + path, { headers: { 'x-apisports-key': AS.key } });
  if (!r.ok) throw new Error('API-SPORTS returned ' + r.status);
  const j = await r.json();
  if (j.errors && Object.keys(j.errors).length) {
    throw new Error(Object.values(j.errors).join(' '));
  }
  return j.response || [];
}

/* The 48 teams, alphabetical, cached for a day to save quota. */
async function loadTeams() {
  const ck = 'wc_teams_' + AS.season;
  try { const c = JSON.parse(localStorage.getItem(ck)); if (c && c.t > Date.now() - 86400000) return c.d; } catch (e) {}
  const res = await asGet(`/teams?league=${AS.league}&season=${AS.season}`);
  const teams = res.map(x => ({ id: x.team.id, name: x.team.name, logo: x.team.logo }))
                   .sort((a, b) => a.name.localeCompare(b.name));
  try { localStorage.setItem(ck, JSON.stringify({ t: Date.now(), d: teams })); } catch (e) {}
  return teams;
}

let _fxCache = null;
async function loadFixtures(force) {
  if (!force && _fxCache && _fxCache.t > Date.now() - 90000) return _fxCache.d; // 90s cache
  const d = await asGet(`/fixtures?league=${AS.league}&season=${AS.season}`);
  _fxCache = { t: Date.now(), d };
  return d;
}

/* A match counts once its regular time is decided. */
const FINISHED = new Set(['FT', 'AET', 'PEN']);

/*
  Returns { points:{teamId:pts}, names:{teamId:{name,logo}} }.
  Uses score.fulltime (the score after 90'), so a knockout tie that
  is settled on penalties counts as a DRAW for both sides.
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
    if (fh === null || fa === null) { fh = f.goals.home; fa = f.goals.away; } // safety net
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
