# World Cup 2026 — Hub + Prediction Game

A small site for the FIFA World Cup 2026: a public page with the live
schedule and standings (via API-SPORTS widgets), plus an invite-only
prediction game with a shared leaderboard.

## Stack

- Static front page (`index.html`) using API-SPORTS **Widgets v3**
- Prediction game on **PHP 7.4+ / MySQL**
- Match results are read **in the browser** from the API-SPORTS REST API,
  so the host never needs to make outbound calls (works on hosts that
  block server-side HTTP, e.g. some free tiers).

## Files

| File | Purpose |
|------|---------|
| `index.html` | Public hub: schedule + standings widgets |
| `register.php` | Register (invite PIN → nickname + personal PIN + 3 picks), log in, edit picks |
| `leaderboard.php` | Shared leaderboard; computes scores live in the browser |
| `admin.php` | Issue invite PINs, view participants (password-gated) |
| `api.php` | Same-origin JSON API (register / login / save picks / leaderboard feed) |
| `db.php` | PDO connection + auto-creates the table on first run |
| `common.js` | Browser helpers: load teams/fixtures, scoring logic |
| `styles.css` | Shared theme |
| `config.sample.php` | Copy to `config.php` and fill in (config.php is git-ignored) |

## Scoring

Each participant picks **3 teams**. Across **every match** those teams play:

- **Win → 3 points**, **Draw → 1**, **Loss → 0**
- Judged on the **90-minute (regulation) score**. A knockout match level
  after 90 minutes and decided on penalties counts as a **draw** for both teams.

A participant's total is the sum across their three teams. The leaderboard
recomputes from the full fixture list each load, so it is always consistent.

Picks are **editable until the first kickoff** (`PICK_LOCK` in config), then
locked. Picks stay hidden on the leaderboard until kickoff.

## Setup

1. Create a MySQL database on your host. Copy `config.sample.php` to
   `config.php` and fill in the DB details, your API-SPORTS key, and an
   admin password.
2. Ensure the host runs **PHP 7.4+**.
3. Upload all files to the web root (e.g. `htdocs` / `public_html`). The
   database table is created automatically on first use.
4. In the **API-SPORTS dashboard**, add your site's domain to the allowed
   domains for the API key (the browser reads results, like the widgets do).
5. Open `admin.php`, log in, generate invite PINs, and give one to each
   participant. They register at `register.php`.

## Notes

- API-SPORTS' **free plan has a low daily request cap**. The leaderboard
  caches results for 90s and auto-refreshes every 3 minutes; heavy live use
  may need a paid API tier.
- The API key is exposed client-side by design (same as the widgets) —
  domain restriction in the dashboard is what protects it.
