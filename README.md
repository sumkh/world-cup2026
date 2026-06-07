# World Cup 2026 — Hub + Prediction Game

A small site for the FIFA World Cup 2026: a public page with the live
schedule and standings (via API-SPORTS widgets), plus an invite-only
prediction game with a shared leaderboard.

## Stack

- Static front page (`index.html`) using API-SPORTS **Widgets v3**
- Prediction game on **PHP 8.2 / PostgreSQL**
- Match results are synced server-side by `cron.php` from the
  **football-data.org** REST API (free plan covers World Cup 2026) into a
  local fixture cache. Pages then read the cache, so visitors never call an
  external API directly.
- Deployable to **Render** via Docker (see [Deployment](#deployment)).

## API Resources

- **API-SPORTS Widgets v3 docs:** https://api-sports.io/documentation/widgets/v3#section/Before-You-Begin/Predefined-themes
- **World Cup 2026 blog post (API-Football):** https://www.api-football.com/news/post/fifa-world-cup-2026-using-api-sports-widgets
- **football-data.org (score sync):** https://www.football-data.org

The API-SPORTS key is exposed client-side by design (same pattern as the
widgets) — domain restriction in the dashboard protects your quota. The
football-data.org key is used **server-side only** (in `cron.php`).

## Files

| File | Purpose |
|------|---------|
| `index.html` | Public hub: schedule, standings, and team/player widgets (API-SPORTS). Tap a team in the standings for its venue/stats/squad; tap a player for their profile |
| `register.php` | Register (Access Code → nickname + 6-digit PIN + 3 picks), log in, edit picks |
| `leaderboard.php` | Shared leaderboard with a **Sync Scores** button; scores computed in the browser from the cached fixtures |
| `admin.php` | View all 32 slots + picks, Sync Scores, Export CSV, and a guarded reset (password-gated) |
| `api.php` | Same-origin JSON API (register / login / picks / leaderboard / teams / fixtures) |
| `cron.php` | Score sync — one football-data.org call updates the fixture cache; rate-limited |
| `export.php` | Admin-only CSV export of every slot, picks, and total points |
| `db.php` | PDO/PostgreSQL connection; auto-creates tables, seeds 32 slots + 48 teams |
| `common.js` | Browser helpers: load teams/fixtures from `api.php`, scoring + bonus logic |
| `styles.css` | Shared theme (responsive) |
| `config.php` | Reads environment variables (production), with `config.local.php` override for local dev |
| `config.sample.php` | Copy to `config.local.php` and fill in for local development |
| `Dockerfile` | PHP 8.2 + Apache image for Render (or any Docker host) |
| `render.yaml` | Render service + PostgreSQL definition |
| `.github/workflows/scores-sync.yml` | Scheduled score sync (every 15 min) |

## Registration Flow

32 participant slots (Access Codes `01`–`32`) are pre-seeded on first run.

1. **Register:** participant enters their assigned Access Code (e.g. `07`), chooses a nickname and 6-digit personal PIN, then picks 3 teams.
2. **Log in:** participant enters Access Code + personal PIN to return and edit picks.
3. **Picks lock** at the first kickoff (`PICK_LOCK` in config) and stay hidden on the leaderboard until then.

## Scoring

Each participant picks **3 teams**. Across **every match** those teams play,
in **every round** (all 104 games):

- **Win → 3 points**, **Draw → 1**, **Loss → 0**
- Judged on the **90-minute (regulation) score**. A knockout match level
  after 90 minutes and decided on penalties counts as a **draw** for both teams.

**End-of-tournament bonuses** (added to a team's total once that match finishes):

- **Champion → +20**, **Runner-up → +10**, **Third place → +5**
- Decided on the true result (incl. extra time / penalties) of the Final and
  third-place play-off. Bonuses stay hidden until those matches complete.

A participant's total is the sum across their three teams. The leaderboard
recomputes from the full fixture cache each load, so it is always consistent.

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

Automated syncing (optional) runs every 15 minutes via
`.github/workflows/scores-sync.yml` once you add an `ADMIN_PASSWORD` GitHub
Actions secret — see [Deployment](#automated-score-sync-optional).

## Behaviour Notes

- **Visuals activate at kickoff.** Most visualisations need players' picks, which
  stay hidden until `PICK_LOCK` (first kickoff). Before then those cards show
  *"Available once picks lock at kickoff."* The match-day digest works earlier.
- **Eliminations = knockout losses only.** "Teams still alive" and the per-player
  *out / alive* labels mark a team eliminated when it **loses a knockout match**
  (Round of 32 onward). Group-stage non-qualification is *not* detected, because
  the app doesn't store group tables — a team simply stops earning once knocked
  out. This is called out in the card's subtitle.
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
2. Copy `config.sample.php` → `config.local.php` and fill in the DB details, API keys, and admin password.
3. Ensure the host runs **PHP 8.2+** with the `pdo_pgsql` and `curl` extensions.
4. The schema, 32 slots, and 48 teams are created automatically on first request.
5. In the **API-SPORTS dashboard**, add your site's domain to the allowed domains for the widgets key.
6. Open `admin.php`, log in, and distribute Access Codes `01`–`32` to participants. They register at `register.php`.

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
   | `API_KEY` | API-SPORTS widgets key (already defaulted in code; override here if you rotate it) |

   `DATABASE_URL` is **wired automatically** by Render from the linked PostgreSQL database — you do not fill it in manually.

4. Deploy. On first request `db.php` creates the schema and seeds the 32 slots + 48 teams.

### Automated score sync (optional)

`.github/workflows/scores-sync.yml` calls `cron.php` every 15 minutes. To
enable it, add an `ADMIN_PASSWORD` **GitHub Actions secret** (repo → Settings →
Secrets and variables → Actions) matching your Render value. Participants can
also trigger a sync any time with the **Sync Scores** button — `cron.php` is
rate-limited to one external call per 60 seconds.

## Notes

- football-data.org's **free plan is rate-limited** (≈10 requests/minute). The
  sync is throttled to once per 60s and results are cached in the DB, so the
  leaderboard reads are free and instant.
- Render's **free PostgreSQL expires after 90 days** — export your data (admin
  CSV or `pg_dump`) or upgrade before then if you need it long-term.
- The API-SPORTS key is exposed client-side by design (same as the widgets) —
  domain restriction in the dashboard is what protects it.
