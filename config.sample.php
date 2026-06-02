<?php
/* ============================================================
   LOCAL DEV TEMPLATE
   Copy this file to  config.local.php  and fill in real values.
   config.local.php is git-ignored so your secrets stay local.

   On Render (production) DO NOT touch this file — Render injects
   DATABASE_URL automatically and you set the other vars in the
   Render dashboard under Environment.
   ============================================================ */

/* ---- PostgreSQL (local dev only) ---- */
/* If you set DATABASE_URL as an env var, db.php will use that
   instead and the constants below are ignored. */
define('DB_HOST', 'localhost');
define('DB_NAME', 'worldcup2026');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

/* ---- API-SPORTS key (visible client-side by design; restrict to your domain).
        Docs: https://api-sports.io/documentation/widgets/v3#section/Before-You-Begin/Predefined-themes
        Blog: https://www.api-football.com/news/post/fifa-world-cup-2026-using-api-sports-widgets ---- */
define('API_KEY', 'ec434bd351885afe9375a8ae3fd43003');

/* ---- Admin password for admin.php ---- */
define('ADMIN_PASSWORD', 'choose-a-strong-password');

/* ---- Game rules (optional overrides) ---- */
define('LEAGUE_ID', 1);                       // FIFA World Cup
define('SEASON',    2026);
define('NUM_PICKS', 3);
define('PICK_LOCK', '2026-06-11 00:00:00');   // picks lock at first kickoff (UTC)
define('WIN_PTS', 3);
define('DRAW_PTS', 1);
define('LOSS_PTS', 0);

date_default_timezone_set('UTC');
