<?php
/* Database connection + one-time schema creation and seeding (PDO/MySQL). */
require_once __DIR__ . '/config.php';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 32 pre-assigned participant slots (UserID "01"–"32").
    // Participants claim a slot by supplying their UserID + choosing a nickname,
    // 6-digit personal PIN, and three team picks.
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      user_id     VARCHAR(2)   NOT NULL UNIQUE,
      claimed     TINYINT      NOT NULL DEFAULT 0,
      nickname    VARCHAR(40)  UNIQUE,
      pin_hash    VARCHAR(255),
      team1       INT,
      team2       INT,
      team3       INT,
      created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed exactly 32 slots once (INSERT IGNORE is idempotent on reruns).
    $pdo->exec("INSERT IGNORE INTO participants (user_id) VALUES
      ('01'),('02'),('03'),('04'),('05'),('06'),('07'),('08'),
      ('09'),('10'),('11'),('12'),('13'),('14'),('15'),('16'),
      ('17'),('18'),('19'),('20'),('21'),('22'),('23'),('24'),
      ('25'),('26'),('27'),('28'),('29'),('30'),('31'),('32')");
  }
  return $pdo;
}
