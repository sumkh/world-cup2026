<?php
/* Database connection + one-time schema creation and seeding (PDO / PostgreSQL).
   On Render the DATABASE_URL env var is injected automatically when a Postgres
   database is linked to the service.  For local dev set DATABASE_URL or define
   individual DB_* constants in config.local.php. */
require_once __DIR__ . '/config.php';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $db_url = getenv('DATABASE_URL') ?: '';
    if ($db_url !== '') {
      // Render supplies  postgresql://user:pass@host:5432/dbname
      $u    = parse_url($db_url);
      $dsn  = 'pgsql:host=' . $u['host']
            . ';port='       . ($u['port'] ?? 5432)
            . ';dbname='     . ltrim($u['path'] ?? '', '/');
      $user = $u['user'] ?? '';
      $pass = isset($u['pass']) ? urldecode($u['pass']) : '';
    } else {
      // Local fallback — fill DB_* constants in config.local.php
      $dsn  = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME;
      $user = DB_USER;
      $pass = DB_PASS;
    }

    $pdo = new PDO($dsn, $user, $pass, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 32 pre-assigned participant slots (UserID "01"–"32").
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

    // Seed the 32 slots once (idempotent on reruns).
    $pdo->exec("INSERT INTO participants (user_id) VALUES
      ('01'),('02'),('03'),('04'),('05'),('06'),('07'),('08'),
      ('09'),('10'),('11'),('12'),('13'),('14'),('15'),('16'),
      ('17'),('18'),('19'),('20'),('21'),('22'),('23'),('24'),
      ('25'),('26'),('27'),('28'),('29'),('30'),('31'),('32')
      ON CONFLICT (user_id) DO NOTHING");
  }
  return $pdo;
}
