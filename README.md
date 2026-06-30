# World Cup 2026 — Hub + Prediction Game

A small site for the FIFA World Cup 2026: a public hub with the live schedule,
group standings, and team/player squads, plus an invite-only prediction game
with a shared leaderboard.

## Stack

- Public hub (`index.html`) — **native** schedule, standings and squad browser,
  built in the browser from our own data (no third-party widgets).
- Prediction game on **PHP 8.2 / PostgreSQL**.
- Match results are synced server-side by `cron.php` from the
  **football-data.org** REST API (free plan covers World Cup 2026) into a local
  fixture cache. Every page (hub + game) reads that cache, so visitors never
  call an external API directly.
- Squad lists are shipped as static data (`squads.js`) with team images hosted
  locally — no API calls needed.
- Deployable to **Render** via Docker (see [Deployment](#deployment)).

> **Why not API-SPORTS widgets?** The site originally used API-SPORTS Widgets
> v3 for the schedule/standings/teams. Their **free plan cannot access the 2026
> season** (`"Free plans do not have access to this season, try from 2022 to
> 2024."`), which blanks every World Cup widget. So the hub was rebuilt natively
> on football-data.org (free, 2026-enabled) and the bundled squad data.

## Sources & credit

- **Match data (schedule, results, standings):** [football-data.org](https://www.football-data.org) — used server-side by `cron.php`.
- **Squad lists & team images:** API-SPORTS — [*FIFA World Cup 2026 Lineups: All Teams, Coaches and Players*](https://www.api-football.com/news/post/fifa-world-cup-2026-lineups-all-teams-coaches-and-players).
- The football-data.org key is used **server-side only**. Team images are stored locally in `img/teams/`.

## Files

| File | Purpose |
|------|---------|
| `index.html` | Public hub: native schedule (Singapore time), group standings, and a squad browser — all built from the cached fixtures + `squads.js` |
| `squads.js` | Static data: all 48 squads (1,247 players) grouped by position, from the API-SPORTS lineups blog |
| `img/teams/` | 48 team images (from API-SPORTS), hosted locally |
| `register.php` | Register (Access Code → nickname + 6-digit PIN + 3 picks), log in, edit picks |
| `leaderboard.php` | Shared leaderboard with a **Sync Scores** button; scores + bonuses computed in the browser; eliminated picks flagged |
| `admin.php` | View all 32 slots + picks, Sync Scores, toggle visualisations, Export CSV, guarded reset (password-gated) |
| `api.php` | Same-origin JSON API (register / login / save picks / **switch_pick** / leaderboard / teams / fixtures) |
| `cron.php` | Score sync — one football-data.org call updates the fixture cache (status, score, stage, winner, date, group); rate-limited |
| `export.php` | Admin-only CSV export of every slot, picks, and total points |
| `db.php` | PDO/PostgreSQL connection; auto-creates tables (incl. `pick_segments`), seeds 32 slots + 48 teams, migrates existing picks into segments |
| `common.js` | Browser helpers: load teams/fixtures from `api.php`, scoring + bonus logic |
| `styles.css` | Shared theme (responsive) |
| `config.php` | Reads environment variables (production), with `config.local.php` override for local dev |
| `config.sample.php` | Copy to `config.local.php` and fill in for local development |
| `Dockerfile` | PHP 8.2 + Apache image for Render (or any Docker host) |
| `render.yaml` | Render service + PostgreSQL definition |
| `.github/workflows/scores-sync.yml` | Scheduled score sync — auto-runs only during the tournament (see [below](#automated-score-sync)) |

## Public Hub (`index.html`)

All three sections are native and work on the free tier — they read the same
cached fixtures the game uses.

- **Schedule** — every match grouped by date, in **Singapore time (SGT, UTC+8)**.
  Tabs: **All** (default — finished games show their score inline, upcoming show
  kickoff time, so results appear as games progress without switching tabs),
  **Upcoming** (fixtures only), **Results** (finished only). The current day is
  marked **"· Today"**.
- **Standings** — group tables for all 12 groups, computed from results
  (P/W/D/L/GD/Pts, sorted by points → GD → goals); the top two of each group are
  highlighted as qualifying. Renders at 0 before kickoff, fills in as results sync.
- **Teams & Players** — pick any of the 48 nations to see its team image and full
  squad by position (GK/DEF/MID/FWD). Eliminated teams are flagged once the
  knockouts begin.

## Registration Flow

32 participant slots (Access Codes `01`–`32`) are pre-seeded on first run.

1. **Register:** participant enters their assigned Access Code (e.g. `07`), chooses a nickname and 6-digit personal PIN, then picks 3 teams.
2. **Log in:** participant enters Access Code + personal PIN to return and edit picks.
3. **Picks lock** at the first kickoff (`PICK_LOCK` in config) and stay hidden on the leaderboard until then.
4. **Late join (after kickoff):** registration stays open. A latecomer only earns
   points from matches that kick off after they join — already-completed matches
   don't count for them.
5. **Switch teams (after kickoff):** log in any time to swap a team. The old team
   keeps the points it earned up to the switch; the new team scores only from its
   next kickoff. Unlimited switches; the switch list shows active teams only.

Both rules use the same engine: each slot is a **timeline of segments**
(`pick_segments`), and a match scores for a slot only if its kickoff falls inside
the segment that held the team. Existing registrations are migrated into a
starting segment automatically (idempotent).

## Scoring

Each participant picks **3 teams**. Across **every match** those teams play,
in **every round** (all 104 games):

- **Win → 3 points**, **Draw → 1**, **Loss → 0**
- Judged on the **90-minute (regulation) score only** — **extra-time goals and
  penalty shootouts do not count**. Any knockout level after 90 minutes is a
  **draw** (1 pt each), even if it's then decided in extra time or on penalties.
  (`cron.php` reads `score.regularTime`, since football-data.org's `fullTime`
  includes ET + penalty kicks.) The Champion/Runner-up/Third **bonuses** still
  use the true winner, including penalty-shootout results.
- **Display vs scoring.** The schedule shows the 90′ score with the shootout in
  brackets (e.g. `0–0 (pens 3–0)`) or `(a.e.t.)`, and highlights the team that
  advanced — but only the 90′ result feeds the points.

**End-of-tournament bonuses** (added to a team's total once that match finishes):

- **Champion → +20**, **Runner-up → +10**, **Third place → +5**
- Decided on the true result (incl. extra time / penalties) of the Final and
  third-place play-off. Bonuses stay hidden until those matches complete.

**Switching teams (after kickoff).** A participant can log in and change any of
their 3 teams at any time during the tournament (unlimited). Scoring is **time-
windowed**: a match counts for a slot only if that slot held the team at the
match's **kickoff**. So the team you switch *away* from keeps every point it
earned up to the switch, and the team you switch *to* scores only from its next
kickoff onward (never its earlier matches). Bonuses go to whoever holds that
team when the final / third-place match kicks off. The switch pool shows
**active teams only** (eliminated teams and your other two are hidden).

A participant's total is the sum across their three slots' segment history. The
leaderboard recomputes from the fixture cache each load, so it is always
consistent.

## How a Season Plays Out

A narrative walkthrough using three of the 32 players:

| Code | Nickname | Picks |
|---|---|---|
| `07` | **Maya** | France · Brazil · Morocco |
| `12` | **Diego** | Argentina · Portugal · Croatia |
| `21` | **Sam** | Spain · England · Mexico |

**Week before — registration.** The admin hands each friend a private Access
Code. Maya opens `register.php`, enters `07`, picks a nickname and 6-digit
PIN, and selects her three teams. She can log back in and change picks freely;
the leaderboard reveals nothing yet.

**June 11, kickoff — everything locks.** Picks freeze and the leaderboard
reveals every player's picks, all sitting on **0**.

**Group stage (72 matches).** Anyone can tap **↻ Sync Scores** to pull the
latest results (one football-data.org call, rate-limited). After the groups:

| # | Player | Picks & points | Total |
|---|---|---|---|
| 1 | **Sam** | Spain 7 · England 7 · Mexico 7 | **21** |
| 2 | **Diego** | Argentina 9 · Portugal 6 · Croatia 5 | **20** |
| 3 | **Maya** | France 7 · Brazil 7 · Morocco 5 | **19** |

**Knockouts — the 90′ rule bites.** France draws its quarter-final 1–1 and
wins on penalties: France *advances*, but because the 90′ result was a draw it
earns only **1 point**, not 3. Teams that lose stop earning entirely.

**The Final + third-place play-off.**
- Sam's **Spain** wins the third-place play-off → +3 points **and +5 bonus**.
- **Final:** France 1–1 Argentina, France win on penalties → both teams get
  **1 match point**; **France = Champion (+20)**, **Argentina = Runner-up (+10)**.
  Gold **★** chips appear on the leaderboard the moment it syncs.

**Final leaderboard:**

| # | Player | Breakdown | Total |
|---|---|---|---|
| 🥇 | **Maya** | France 18 ★+20 · Brazil 10 · Morocco 5 | **53** |
| 🥈 | **Diego** | Argentina 21 ★+10 · Portugal 9 · Croatia 5 | **45** |
| 🥉 | **Sam** | Spain 19 ★+5 · England 10 · Mexico 7 | **41** |

The twist: Sam led from the opening whistle through the group stage, but
Maya's bet on France winning the cup — the **+20 champion bonus** — vaulted
her from 3rd to 1st on the final night. The bonus structure means the
standings aren't settled until the last whistle.

**After the whistle.** The admin opens `admin.php` and clicks **⬇ Export CSV**
to download every player, their picks, and final totals. Before the next
season, the **Danger Zone** reset wipes registrations and cached scores.

## Leaderboard Visualisations

Beyond the ranked leaderboard, the app includes optional visualisations that
the **admin toggles on/off** in `admin.php` (so participants aren't
overwhelmed — all default to OFF). Each renders below the leaderboard:

- **Pick popularity heat map** — how many players backed each team; consensus
  vs. contrarian bets.
- **Teams still alive** — per player, which of their three picks remain vs. are
  knocked out (green = still in).
- **Bracket / path view** — each picked team's run through the rounds as W/D/L
  chips, showing where points came from.
- **Per-player detail** — tap a leaderboard row to expand a per-pick breakdown
  with points and pending bonus potential.
- **Projected finish (what-if)** — pick a team and see how the standings would
  reorder if it won the cup (+20 champion bonus applied).
- **Match-day digest** — a shareable text snapshot (leader, gap, teams alive,
  recent results) with a copy-to-clipboard button for the group chat.

Most need picks, so they activate once picks lock at kickoff. They reuse the
existing `api.php` fixtures + participants feeds — no extra storage required.

> A **points-over-time race chart** was considered but not built: it needs
> periodic snapshots of each player's total, unlike the above which derive
> everything from the current state.

## Admin Guide

Everything the organiser does happens on `admin.php` (log in with `ADMIN_PASSWORD`).

1. **Distribute Access Codes.** Give each participant one code from `01`–`32`.
   The codes are deliberately *not* shown in the public UI — the register page
   just asks for an "Access Code", so outsiders can't guess the format.
2. **Watch registrations.** The participants table shows each slot's status
   (open / registered), nickname, and the three team picks.
3. **Sync scores.** Click **↻ Sync Scores** any time to pull the latest results
   (one football-data.org call). Participants can also sync from the leaderboard;
   it's rate-limited to one external call per 60 seconds.
4. **Toggle visualisations.** In **Leaderboard Visualisations**, tick the visuals
   you want and **Save**. All default OFF — enable only what you want so players
   aren't overwhelmed. Changes appear on the public leaderboard on its next load.
5. **Export data.** Click **⬇ Export CSV** for a timestamped file of every slot,
   its picks, and current totals (including bonuses).
6. **Reset for testing.** The red **Danger Zone** clears all registrations *and*
   the cached scores. Guardrails: you must be logged in, type `RESET` to enable
   the button, and confirm a final dialog. Use it only before the real launch.

Automated syncing runs every 15 minutes **during the tournament only** via
`.github/workflows/scores-sync.yml` once you add an `ADMIN_PASSWORD` GitHub
Actions secret — see [Automated score sync](#automated-score-sync).

## Behaviour Notes

- **Visuals activate at kickoff.** Most visualisations need players' picks, which
  stay hidden until `PICK_LOCK` (first kickoff). Before then those cards show
  *"Available once picks lock at kickoff."* The match-day digest works earlier.
- **Eliminations = knockout losses only.** Eliminated teams are flagged across
  the site — greyed with an **OUT** tag on the leaderboard picks, **"— OUT"** in
  the Teams & Players dropdown, and in the "Teams still alive" visual. A team is
  marked out when it **loses a knockout match** (Round of 32 onward). Group-stage
  exits aren't auto-detected for this flag (a non-qualifier simply stops earning),
  though the group **standings** table does reflect group results fully.
- **Picks lock before any elimination.** Picks are frozen at the first kickoff,
  which is before any team is knocked out — so registration always offers the full
  team list; the OUT flags only ever appear on already-locked picks.
- **Time-windowed scoring (switches + late join).** Each slot is a timeline of
  `pick_segments` (team, start, end). A match scores for a slot only if its kickoff
  is inside the segment holding that team — this powers both **switching** (old team
  keeps points to the switch, new team scores forward) and **late join** (first
  segment just starts later). Scored in the browser via `scoreParticipant(fixtures,
  segments)` and in SQL for the CSV export. Each player's total is computed
  individually. Existing registrations are migrated into a starting segment on first
  load (idempotent — verified against Postgres).
- **Switching is unlimited and immediate.** A sharp player can move a slot onto
  whichever team plays next and bank that match — that's by design (active
  management). The switch list hides eliminated teams and your other two picks.
- **Times are in Singapore time.** The hub schedule shows kickoff times and date
  grouping in SGT (UTC+8), regardless of the viewer's location.
- **Bonuses appear only when earned.** Champion +20 / Runner-up +10 / Third +5
  are added the moment the Final and third-place play-off finish, and use the
  true result (incl. extra time / penalties). Match points always use the 90′
  score, so a penalty shootout is a draw (1 point each) for the match itself.
- **No extra storage or API cost.** Every visualisation is derived in the browser
  from the existing `api.php` fixtures + participants feeds. Syncing writes only
  to the local DB cache; page reads never hit an external API.
- **Schema auto-migrates.** New columns (e.g. `wc_fixtures.utc_date`) are added
  via `ALTER TABLE ... IF NOT EXISTS` on the next page load; values populate on
  the next **Sync Scores**.
- **Free-tier spin-down.** On Render's free plan the service sleeps after ~15 min
  idle; the first request afterwards takes ~30–60s to wake.

## Setup (Local Dev)

1. Create a local PostgreSQL database.
2. Copy `config.sample.php` → `config.local.php` and fill in the DB details, your **football-data.org** key (`FD_API_KEY`), and an admin password.
3. Ensure the host runs **PHP 8.2+** with the `pdo_pgsql` and `curl` extensions.
4. The schema, 32 slots, and 48 teams are created automatically on first request.
5. Open `admin.php`, log in, and hit **Sync Scores** once to populate the fixture cache (schedule + standings). Then distribute Access Codes `01`–`32` to participants, who register at `register.php`.

### Validating changes without a PHP install

No local PHP needed — lint via the Docker image and check the JS with Node:

```bash
# PHP syntax check (uses the same PHP version as production)
docker run --rm -v "$PWD":/app php:8.2-cli bash -c 'for f in /app/*.php; do php -l "$f"; done'

# JS syntax check
node --check common.js
```

On Windows these run cleanly inside **WSL** (the repo is reachable from WSL only
if it lives on a drive WSL mounts, e.g. `C:`; Google-Drive-mounted paths are not
visible to WSL — copy the files to a local path first).

## Deployment

### Render

The repo includes a `Dockerfile` (PHP 8.2 + Apache) and `render.yaml` that
provisions a **free Render PostgreSQL** database automatically.

1. Push this repo to a GitHub repo (private recommended).
2. In [Render](https://render.com), connect the repo — Render detects `render.yaml` and creates both the web service and the database.
3. Set these environment variables in the Render dashboard:

   | Variable | What to enter |
   |---|---|
   | `ADMIN_PASSWORD` | Any strong password — used for `admin.php` and to authorise `cron.php` |
   | `FD_API_KEY` | Your football-data.org API key (used server-side by `cron.php`) |
   | `API_KEY` | *(optional, legacy)* API-SPORTS key — no longer used by the app |

   `DATABASE_URL` is **wired automatically** by Render from the linked PostgreSQL database — you do not fill it in manually.

4. Deploy. On first request `db.php` creates the schema and seeds the 32 slots + 48 teams.

### Automated score sync

`.github/workflows/scores-sync.yml` calls `cron.php` every 15 minutes, but only
when matches are actually being played. GitHub cron has no start/end date, so it
is bounded three ways:

- **Months:** the schedule only fires in June & July (`cron: '*/15 0-5,15-23 * 6,7 *'`).
- **Hours:** only during live-match hours, **15:00–05:59 UTC** (World Cup 2026 is
  in the Americas; games run ~16:00 UTC to ~04:00 UTC).
- **Dates:** a guard step skips the sync unless today is within
  **2026-06-11 … 2026-07-19**.

Net effect: it starts when the Cup begins, syncs every 15 min while games are on,
and goes dormant once it ends (and never fires in other months or future years).

To enable it, add an `ADMIN_PASSWORD` **GitHub Actions secret** (repo → Settings →
Secrets and variables → Actions) matching your Render value. You can also run it
on demand from **Actions → Run workflow**, or via the **Sync Scores** button in
the app — `cron.php` is rate-limited to one external call per 60 seconds.

> GitHub disables scheduled workflows after ~60 days of repo inactivity, so make
> sure there's a commit within ~60 days before 11 Jun 2026.

## Notes

- football-data.org's **free plan is rate-limited** (≈10 requests/minute). The
  sync is throttled to once per 60s and results are cached in the DB, so all page
  reads are free and instant.
- Render's **free PostgreSQL expires after 90 days** — export your data (admin
  CSV or `pg_dump`) or upgrade before then if you need it long-term.
- **`API_KEY` (API-SPORTS) is now legacy.** It remains a config/env default but is
  unused since the hub dropped the API-SPORTS widgets; you can ignore it. All live
  data comes from football-data.org via `FD_API_KEY`.
