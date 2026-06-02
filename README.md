# World Cup 2026 — Hub + Prediction Game

A small site for the FIFA World Cup 2026: a public page with the live
schedule and standings (via API-SPORTS widgets), plus an invite-only
prediction game with a shared leaderboard.

## Stack

- Static front page (`index.html`) using API-SPORTS **Widgets v3**
- Prediction game on **PHP 8.2 / MySQL**
- Match results are read **in the browser** from the API-SPORTS REST API,
  so the host never needs to make outbound calls (works on hosts that
  block server-side HTTP, e.g. some free tiers).
- Deployable to **Render** via Docker (see [Deployment](#deployment)).

## API-SPORTS Resources

- **Widgets v3 documentation:** https://api-sports.io/documentation/widgets/v3#section/Before-You-Begin/Predefined-themes
- **World Cup 2026 blog post (API-Football):** https://www.api-football.com/news/post/fifa-world-cup-2026-using-api-sports-widgets

The API key is exposed client-side by design (same pattern as the widgets) — domain restriction in the API-SPORTS dashboard is what protects your quota.

## Files

| File | Purpose |
|------|---------|
| `index.html` | Public hub: schedule + standings widgets |
| `register.php` | Register (UserID → nickname + 6-digit PIN + 3 picks), log in, edit picks |
| `leaderboard.php` | Shared leaderboard; computes scores live in the browser |
| `admin.php` | View all 32 participant slots and their picks (password-gated) |
| `api.php` | Same-origin JSON API (register / login / save picks / leaderboard feed) |
| `db.php` | PDO connection + auto-creates the table and seeds 32 UserID slots on first run |
| `common.js` | Browser helpers: load teams/fixtures, scoring logic |
| `styles.css` | Shared theme |
| `config.php` | Reads from environment variables (production) with `config.local.php` override for local dev |
| `config.sample.php` | Copy to `config.local.php` and fill in for local development |
| `Dockerfile` | PHP 8.2 + Apache image for Render (or any Docker host) |
| `render.yaml` | Render service definition |

## Registration Flow

32 participant slots (UserID `01`–`32`) are pre-seeded in the database on first run.

1. **Register:** participant enters their assigned UserID (e.g. `07`), chooses a nickname and 6-digit personal PIN, then picks 3 teams.
2. **Log in:** participant enters UserID + personal PIN to return and edit picks.
3. **Picks lock** at the first kickoff (`PICK_LOCK` in config) and remain hidden on the leaderboard until then.

## Scoring

Each participant picks **3 teams**. Across **every match** those teams play:

- **Win → 3 points**, **Draw → 1**, **Loss → 0**
- Judged on the **90-minute (regulation) score**. A knockout match level
  after 90 minutes and decided on penalties counts as a **draw** for both teams.

A participant's total is the sum across their three teams. The leaderboard
recomputes from the full fixture list each load, so it is always consistent.

## Setup (Local / Shared Hosting)

1. Create a MySQL database on your host.
2. Copy `config.sample.php` → `config.local.php` and fill in the DB details, API-SPORTS key, and admin password.
3. Ensure the host runs **PHP 8.2+**.
4. Upload all files to the web root. The database table and 32 UserID slots are created automatically on first use.
5. In the **API-SPORTS dashboard**, add your site's domain to the allowed domains for the API key.
6. Open `admin.php`, log in, and distribute UserIDs `01`–`32` to participants. They register at `register.php`.

## Deployment

### Render (Recommended)

The repo includes a `Dockerfile` (PHP 8.2 + Apache) and `render.yaml`.

1. Push this repo to GitHub (private repo recommended).
2. In [Render](https://render.com), create a new **Web Service** connected to the GitHub repo. Render will detect `render.yaml` automatically.
3. Set the following **Environment Variables** in the Render dashboard:

   | Variable | Value |
   |----------|-------|
   | `DB_HOST` | Your MySQL host (e.g. from PlanetScale, Railway, or any MySQL provider) |
   | `DB_NAME` | Database name |
   | `DB_USER` | Database user |
   | `DB_PASS` | Database password |
   | `ADMIN_PASSWORD` | A strong password for `admin.php` |
   | `API_KEY` | Your API-SPORTS key (already set as a default in `config.php`) |

4. Render does not provide MySQL natively. Use an external MySQL service such as [PlanetScale](https://planetscale.com) (free tier) or [Railway](https://railway.app).

## Notes

- API-SPORTS' **free plan has a low daily request cap**. The leaderboard
  caches results for 90s and auto-refreshes every 3 minutes; heavy live use
  may need a paid API tier.
- The API key is exposed client-side by design (same as the widgets) —
  domain restriction in the dashboard is what protects it.
