<?php
/* Database connection + one-time schema creation (PDO/MySQL). */
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
    // Each participant slot = one pre-issued invite PIN. The participant
    // "claims" it by setting a nickname + personal PIN + three team picks.
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      invite_pin  VARCHAR(32)  NOT NULL UNIQUE,
      claimed     TINYINT      NOT NULL DEFAULT 0,
      nickname    VARCHAR(40)  UNIQUE,
      pin_hash    VARCHAR(255),
      team1       INT,
      team2       INT,
      team3       INT,
      created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
  return $pdo;
}
