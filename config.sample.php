<?php
/* ============================================================
   LOCAL DEV TEMPLATE
   Copy this file to  config.local.php  and fill in real values.
   config.local.php is git-ignored so your secrets stay local.

   On Render (production), set environment variables in the
   Render dashboard instead — config.php reads them automatically.
   ============================================================ */

/* ---- MySQL (from your host's "MySQL Databases" panel) ---- */
define('DB_HOST', 'sqlXXX.example-host.com');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

/* ---- API-SPORTS key (visible client-side by design; restrict to your domain).
        Docs: https://api-sports.io/documentation/widgets/v3#section/Before-You-Begin/Predefined-themes
        Blog: https://www.api-football.com/news/post/fifa-world-cup-2026-using-api-sports-widgets ---- */
define('API_KEY', 'YOUR_API_SPORTS_KEY');

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
