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
    // 32 pre-assigned slots (UserID "01"–"32").
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
    // Our sequential IDs (1-48) are the internal pick IDs.
    // as_id is the API-SPORTS team ID — NULL until cron.php populates it.
    $pdo->exec("CREATE TABLE IF NOT EXISTS wc_teams (
      id    SERIAL        PRIMARY KEY,
      as_id INT           UNIQUE,
      name  VARCHAR(100)  NOT NULL UNIQUE,
      logo  TEXT
    )");

    // Seed the 48 FIFA World Cup 2026 teams (alphabetical order = IDs 1-48).
    // as_id is left NULL here; cron.php fills it in via name-matching once
    // the user has API-SPORTS access for the 2026 season.
    $pdo->exec("INSERT INTO wc_teams (name) VALUES
      ('Albania'),('Algeria'),('Argentina'),('Australia'),('Austria'),
      ('Belgium'),('Bolivia'),('Brazil'),('Cameroon'),('Canada'),
      ('Colombia'),('Costa Rica'),('Croatia'),('Denmark'),('Ecuador'),
      ('Egypt'),('England'),('France'),('Germany'),('Ghana'),
      ('Honduras'),('Iran'),('Iraq'),('Ivory Coast'),('Jamaica'),
      ('Japan'),('Jordan'),('Mexico'),('Morocco'),('Netherlands'),
      ('New Zealand'),('Nigeria'),('Panama'),('Portugal'),('Romania'),
      ('Saudi Arabia'),('Scotland'),('Senegal'),('Serbia'),('South Africa'),
      ('South Korea'),('Spain'),('Switzerland'),('Turkey'),
      ('United States'),('Uruguay'),('Uzbekistan'),('Venezuela')
      ON CONFLICT (name) DO NOTHING");

    // ── World Cup fixture/results cache ──────────────────────────────────────
    // home_id / away_id reference wc_teams.id (our internal IDs, not as_id).
    // Populated and refreshed by cron.php.
    $pdo->exec("CREATE TABLE IF NOT EXISTS wc_fixtures (
      id         INT          PRIMARY KEY,
      home_id    INT          NOT NULL,
      away_id    INT          NOT NULL,
      home_goals INT,
      away_goals INT,
      status     VARCHAR(10)  NOT NULL DEFAULT 'NS'
    )");
  }
  return $pdo;
}
