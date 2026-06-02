<?php
/* ============================================================
   CONFIG TEMPLATE
   Copy this file to  config.php  and fill in the real values.
   config.php is git-ignored so your secrets never reach the repo.
   ============================================================ */

/* ---- MySQL (from your host's "MySQL Databases" panel) ---- */
define('DB_HOST', 'sqlXXX.example-host.com');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

/* ---- API-SPORTS key. Used by the browser to read results.
        Restrict it to your domain in the API-SPORTS dashboard. ---- */
define('API_KEY', 'YOUR_API_SPORTS_KEY');

/* ---- Admin password for admin.php (issuing invite PINs) ---- */
define('ADMIN_PASSWORD', 'choose-a-strong-password');

/* ---- Game rules ---- */
define('LEAGUE_ID', 1);                       // FIFA World Cup
define('SEASON',    2026);
define('NUM_PICKS', 3);
define('PICK_LOCK', '2026-06-11 00:00:00');   // picks lock at first kickoff (UTC)
define('WIN_PTS', 3);
define('DRAW_PTS', 1);
define('LOSS_PTS', 0);

date_default_timezone_set('UTC');
