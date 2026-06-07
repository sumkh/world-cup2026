<?php
/* Database connection + one-time schema creation and seeding (PDO / PostgreSQL).
   On Render, DATABASE_URL is injected automatically from the linked database. */
require_once __DIR__ . '/config.php';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $db_url = getenv('DATABASE_URL') ?: '';
    if ($db_url !== '') {
      $u    = parse_url($db_url);
      $dsn  = 'pgsql:host=' . $u['host']
            . ';port='       . ($u['port'] ?? 5432)
            . ';dbname='     . ltrim($u['path'] ?? '', '/');
      $user = $u['user'] ?? '';
      $pass = isset($u['pass']) ? urldecode($u['pass']) : '';
    } else {
      $dsn  = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME;
      $user = DB_USER;
      $pass = DB_PASS;
    }

    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // ── Prediction game participants ─────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
      id         SERIAL        PRIMARY KEY,
      user_id    VARCHAR(2)    NOT NULL UNIQUE,
      claimed    SMALLINT      NOT NULL DEFAULT 0,
      nickname   VARCHAR(40)   UNIQUE,
      pin_hash   VARCHAR(255),
      team1      INT,
      team2      INT,
      team3      INT,
      created_at TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
      updated_at TIMESTAMPTZ   NOT NULL DEFAULT NOW()
    )");

    $pdo->exec("INSERT INTO participants (user_id) VALUES
      ('01'),('02'),('03'),('04'),('05'),('06'),('07'),('08'),
      ('09'),('10'),('11'),('12'),('13'),('14'),('15'),('16'),
      ('17'),('18'),('19'),('20'),('21'),('22'),('23'),('24'),
      ('25'),('26'),('27'),('28'),('29'),('30'),('31'),('32')
      ON CONFLICT (user_id) DO NOTHING");

    // ── World Cup team cache ─────────────────────────────────────────────────
    // as_id = football-data.org team ID (pre-populated from real API data).
    $pdo->exec("CREATE TABLE IF NOT EXISTS wc_teams (
      id    SERIAL        PRIMARY KEY,
      as_id INT           UNIQUE,
      name  VARCHAR(100)  NOT NULL UNIQUE,
      logo  TEXT
    )");

    // All 48 FIFA World Cup 2026 teams, verified from football-data.org
    // (competition 2000, season 2026).  as_id is the football-data.org team ID.
    // ON CONFLICT ... DO UPDATE ensures as_id is set even if the row existed
    // without it (e.g., from a previous placeholder seed).
    $pdo->exec("INSERT INTO wc_teams (name, as_id) VALUES
      ('Algeria',           778),
      ('Argentina',         762),
      ('Australia',         779),
      ('Austria',           816),
      ('Belgium',           805),
      ('Bosnia-Herzegovina',1060),
      ('Brazil',            764),
      ('Canada',            828),
      ('Cape Verde Islands',1930),
      ('Czechia',           798),
      ('Colombia',          818),
      ('Congo DR',          1934),
      ('Croatia',           799),
      ('Curaçao',           9460),
      ('Ecuador',           791),
      ('Egypt',             825),
      ('England',           770),
      ('France',            773),
      ('Germany',           759),
      ('Ghana',             763),
      ('Haiti',             836),
      ('Iran',              840),
      ('Iraq',              8062),
      ('Ivory Coast',       1935),
      ('Japan',             766),
      ('Jordan',            8049),
      ('Mexico',            769),
      ('Morocco',           815),
      ('Netherlands',       8601),
      ('New Zealand',       783),
      ('Norway',            8872),
      ('Panama',            1836),
      ('Paraguay',          761),
      ('Portugal',          765),
      ('Qatar',             8030),
      ('Saudi Arabia',      801),
      ('Scotland',          8873),
      ('Senegal',           804),
      ('South Africa',      774),
      ('South Korea',       772),
      ('Spain',             760),
      ('Sweden',            792),
      ('Switzerland',       788),
      ('Tunisia',           802),
      ('Turkey',            803),
      ('United States',     771),
      ('Uruguay',           758),
      ('Uzbekistan',        8070)
      ON CONFLICT (name) DO UPDATE SET as_id = EXCLUDED.as_id");

    // ── World Cup fixture/results cache ──────────────────────────────────────
    // stage  = GROUP_STAGE / LAST_16 / QUARTER_FINALS / SEMI_FINALS /
    //          THIRD_PLACE / FINAL (used for end-of-tournament bonuses).
    // winner = 'H' / 'A' / 'D' (true result incl. extra time & penalties;
    //          used only for the champion/runner-up/third bonuses, NOT for
    //          regular match points which stay on the 90' goals above).
    $pdo->exec("CREATE TABLE IF NOT EXISTS wc_fixtures (
      id         INT          PRIMARY KEY,
      home_id    INT          NOT NULL,
      away_id    INT          NOT NULL,
      home_goals INT,
      away_goals INT,
      status     VARCHAR(10)  NOT NULL DEFAULT 'NS',
      stage      VARCHAR(20),
      winner     CHAR(1),
      utc_date   TIMESTAMPTZ,
      grp        VARCHAR(20)
    )");
    // Add the columns to pre-existing tables (no-op if they already exist).
    $pdo->exec("ALTER TABLE wc_fixtures ADD COLUMN IF NOT EXISTS stage VARCHAR(20)");
    $pdo->exec("ALTER TABLE wc_fixtures ADD COLUMN IF NOT EXISTS winner CHAR(1)");
    $pdo->exec("ALTER TABLE wc_fixtures ADD COLUMN IF NOT EXISTS utc_date TIMESTAMPTZ");
    $pdo->exec("ALTER TABLE wc_fixtures ADD COLUMN IF NOT EXISTS grp VARCHAR(20)");

    // ── App-level key/value store (used for rate-limiting cron runs) ─────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS wc_meta (
      key   VARCHAR(50) PRIMARY KEY,
      value TEXT        NOT NULL
    )");
  }
  return $pdo;
}

/* The visualisation toggles the admin controls. Keys live in wc_meta as
   'viz_<name>' = '1' | '0'.  All default to OFF so the admin opts in. */
function viz_keys() {
  return [
    'heatmap' => 'Pick popularity heat map',
    'alive'   => 'Teams still alive',
    'bracket' => 'Bracket / path view',
    'detail'  => 'Per-player detail (expandable rows)',
    'whatif'  => 'Projected finish (what-if)',
    'digest'  => 'Match-day digest',
  ];
}

function viz_settings() {
  $out = [];
  foreach (array_keys(viz_keys()) as $k) $out[$k] = false;
  $st = db()->query("SELECT key, value FROM wc_meta WHERE key LIKE 'viz\\_%'");
  foreach ($st as $r) {
    $k = substr($r['key'], 4);
    if (array_key_exists($k, $out)) $out[$k] = ($r['value'] === '1');
  }
  return $out;
}
