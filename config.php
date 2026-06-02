<?php
/* ============================================================
   config.php — committed to the repo.
   Production (Render): values come from environment variables.
   Local dev: create config.local.php (git-ignored) with your
   own define() calls; those will take precedence over env vars.
   ============================================================ */

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

defined('DB_HOST')        || define('DB_HOST',        getenv('DB_HOST')        ?: '');
defined('DB_NAME')        || define('DB_NAME',        getenv('DB_NAME')        ?: '');
defined('DB_USER')        || define('DB_USER',        getenv('DB_USER')        ?: '');
defined('DB_PASS')        || define('DB_PASS',        getenv('DB_PASS')        ?: '');
defined('API_KEY')        || define('API_KEY',        getenv('API_KEY')        ?: 'ec434bd351885afe9375a8ae3fd43003');
defined('ADMIN_PASSWORD') || define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '');

defined('LEAGUE_ID') || define('LEAGUE_ID', 1);       // FIFA World Cup
defined('SEASON')    || define('SEASON',    2026);
defined('NUM_PICKS') || define('NUM_PICKS', 3);
defined('PICK_LOCK') || define('PICK_LOCK', '2026-06-11 00:00:00');  // UTC
defined('WIN_PTS')   || define('WIN_PTS',   3);
defined('DRAW_PTS')  || define('DRAW_PTS',  1);
defined('LOSS_PTS')  || define('LOSS_PTS',  0);

date_default_timezone_set('UTC');
