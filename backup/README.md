# Data Archive — World Cup 2026 Prediction Game

Final snapshot of the live deployment, taken **10 September 2026**, immediately
ahead of the Render service and its PostgreSQL database being decommissioned.

The tournament ran **11 Jun – 19 Jul 2026**. All 104 matches finished, 25 of the
32 slots were claimed. Nothing here is derived or reconstructed — it is the
production database as it stood at the final whistle.

## What's in here

| File | Contents |
|------|----------|
| `worldcup2026.sql` | Full `pg_dump` — schema + data, restorable in one command. **`pin_hash` removed** (see below). |
| `schema.sql` | Structure only: 5 tables, indexes, sequences. Useful as a starting point for a similar build. |
| `data/participants.csv` | 32 slots — 25 registered, 7 never claimed. Nicknames, current picks, join times. No `pin_hash`. |
| `data/pick_segments.csv` | 112 rows — the full time-windowed pick history, including every mid-tournament team switch. |
| `data/wc_fixtures.csv` | 104 matches — 90′ score, after-extra-time score, penalties, stage, group, kickoff. |
| `data/wc_teams.csv` | 48 nations with their football-data.org team IDs. |
| `data/wc_meta.csv` | Key/value store: last cron sync timestamp + the six visualisation toggles (all were on). |
| `api/teams.json`, `api/fixtures.json`, `api/participants.json` | The exact JSON payloads `api.php` served to the browser. These are live — see *Archive mode*. |
| `final-leaderboard.csv` | Final standings, 25 players ranked, produced by `export.php`'s own scoring query. |

## Final standings (top 5)

| # | Player | Teams | Points |
|---|--------|-------|--------|
| 1 | Set Piece FC | England, Argentina, Spain | **94** |
| 2 | Jun | Spain, England, Argentina | 88 |
| 3 | GGMU | Spain, England, Argentina | 87 |
| 4= | Eunice | Argentina, France, Spain | 86 |
| 4= | Jane | France, Argentina, Spain | 86 |

**Final:** Spain 0–0 Argentina at 90′, Spain winning 1–0 in extra time — so under
the house rules the final scored as a **draw** (1 pt each), with Spain's players
taking the 20-pt champion bonus and Argentina's the 10-pt runner-up bonus.
**Third place:** England 6–4 France.

## Privacy

`pin_hash` was deliberately **excluded**. The column still exists in the schema
but every value is `NULL`. Those were bcrypt hashes of **6-digit** PINs — a
search space of only 10⁶, crackable in minutes, and people reuse PINs elsewhere.
Nicknames are kept: they are self-chosen handles and they are what makes the
archived leaderboard legible.

The dump therefore restores cleanly but **cannot authenticate anyone**. If you
ever revive this app, participants re-register from scratch.

## Restoring

No local PostgreSQL client is needed — Docker is enough. The database ran
**PostgreSQL 18**, so use a matching image:

```bash
docker run -d --name wc --rm -e POSTGRES_PASSWORD=pw -e POSTGRES_DB=worldcup postgres:18
docker exec -i wc psql -U postgres -d worldcup -v ON_ERROR_STOP=1 < backup/worldcup2026.sql
docker exec -it wc psql -U postgres -d worldcup -c "SELECT count(*) FROM wc_fixtures;"   # 104
```

To run the whole app locally against it, copy `config.sample.php` to
`config.local.php`, point `DB_*` at the container, and serve the repo with PHP 8.2.

## Archive mode

`common.js` falls back to `backup/api/*.json` whenever `api.php` can't be
reached — so the public hub still renders the complete schedule, standings and
squad browser with **no backend at all**, straight from GitHub Pages or even
`file://`. A banner marks it as a frozen snapshot. Registration and login
correctly refuse, since they need the retired database.

The fallback lives in `api()` at the bottom of `common.js`, the single choke
point every read passed through.

## Verification

This snapshot was checked three ways before the infrastructure was destroyed,
and all three agree:

1. **Row counts** — live database vs. restored dump: `participants` 32,
   `pick_segments` 112, `wc_teams` 48, `wc_fixtures` 104 (all `FT`), `wc_meta` 7.
2. **Scoring, re-derived** — `export.php`'s query run against the restored dump
   produced a leaderboard **byte-identical** to the one extracted from the live
   database.
3. **Scoring, independently** — the browser's own `scoreParticipant()` engine,
   run over `api/*.json` through a static HTTP server, reproduced the same top
   three: Set Piece FC 94, Jun 88, GGMU 87.

The restore was proven working *before* the source database was deleted. Render's
free tier keeps no backups of its own, so this directory is the only copy.

## Teardown checklist

What retiring this project involved, in the order it has to happen — the capture
and its verification come first, because Render's free tier keeps no backups.

- [x] Snapshot the live database (`pg_dump` via Docker; no local client needed)
- [x] Strip `pin_hash`; export per-table CSVs and the final leaderboard
- [x] Capture the live `api.php` JSON payloads
- [x] Prove the dump restores and re-derives the identical leaderboard
- [x] Add the static-snapshot fallback so the hub survives without a backend
- [x] Retire the scheduled GitHub Actions workflow
- [ ] Delete the Render web service `world-cup2026`
- [ ] Delete the Render database `world-cup2026-db`
- [ ] Rotate the football-data.org key (`FD_API_KEY`, Render env var only)
- [ ] Revoke the API-SPORTS key — it was committed in plaintext in `render.yaml`
      and `config.php`, so it is in git history; rotating at the provider is the
      only real fix
- [ ] Delete the `ADMIN_PASSWORD` GitHub Actions secret
- [ ] Tag `v1.0-final`

`render.yaml` and `Dockerfile` are deliberately left in place: together with
`schema.sql` they are the complete recipe for standing this back up.
