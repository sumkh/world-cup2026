<?php
/* Admin-only CSV export of all participants with team picks and current scores. */
require_once __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Forbidden — log into admin.php first.');
}

$pdo = db();

// Compute each team's current points from the fixture cache, then sum per participant.
$rows = $pdo->query("
    WITH match_pts AS (
        SELECT
            t.id,
            COALESCE(SUM(
                CASE
                    WHEN f.status = 'FT' AND f.home_id = t.id AND f.home_goals > f.away_goals THEN 3
                    WHEN f.status = 'FT' AND f.away_id = t.id AND f.away_goals > f.home_goals THEN 3
                    WHEN f.status = 'FT' AND f.home_goals = f.away_goals
                         AND (f.home_id = t.id OR f.away_id = t.id)                           THEN 1
                    ELSE 0
                END
            ), 0) AS pts
        FROM wc_teams t
        LEFT JOIN wc_fixtures f ON f.home_id = t.id OR f.away_id = t.id
        GROUP BY t.id
    ),
    bonus_pts AS (
        -- +20 champion / +10 runner-up (FINAL), +5 third place (THIRD_PLACE).
        -- Uses the true winner (incl. ET/penalties); only counts finished games.
        SELECT
            t.id,
            COALESCE(SUM(
                CASE
                    WHEN f.stage = 'FINAL' AND f.status = 'FT'
                         AND ((f.winner = 'H' AND f.home_id = t.id) OR (f.winner = 'A' AND f.away_id = t.id)) THEN 20
                    WHEN f.stage = 'FINAL' AND f.status = 'FT'
                         AND ((f.winner = 'H' AND f.away_id = t.id) OR (f.winner = 'A' AND f.home_id = t.id)) THEN 10
                    WHEN f.stage = 'THIRD_PLACE' AND f.status = 'FT'
                         AND ((f.winner = 'H' AND f.home_id = t.id) OR (f.winner = 'A' AND f.away_id = t.id)) THEN 5
                    ELSE 0
                END
            ), 0) AS pts
        FROM wc_teams t
        LEFT JOIN wc_fixtures f ON f.home_id = t.id OR f.away_id = t.id
        GROUP BY t.id
    ),
    team_pts AS (
        SELECT m.id, m.pts + b.pts AS pts
        FROM match_pts m JOIN bonus_pts b ON b.id = m.id
    )
    SELECT
        p.user_id,
        COALESCE(p.nickname, '')          AS nickname,
        CASE WHEN p.claimed = 1 THEN 'Registered' ELSE 'Open' END AS status,
        COALESCE(t1.name, '')             AS team1,
        COALESCE(t2.name, '')             AS team2,
        COALESCE(t3.name, '')             AS team3,
        COALESCE(tp1.pts, 0) + COALESCE(tp2.pts, 0) + COALESCE(tp3.pts, 0) AS total_pts,
        TO_CHAR(p.created_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI') AS registered_at
    FROM participants p
    LEFT JOIN wc_teams  t1  ON t1.id  = p.team1
    LEFT JOIN wc_teams  t2  ON t2.id  = p.team2
    LEFT JOIN wc_teams  t3  ON t3.id  = p.team3
    LEFT JOIN team_pts  tp1 ON tp1.id = p.team1
    LEFT JOIN team_pts  tp2 ON tp2.id = p.team2
    LEFT JOIN team_pts  tp3 ON tp3.id = p.team3
    ORDER BY CAST(p.user_id AS INTEGER) ASC
")->fetchAll();

$filename = 'worldcup2026_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fputcsv($out, ['Access Code', 'Nickname', 'Status', 'Team 1', 'Team 2', 'Team 3', 'Total Points', 'Registered At (UTC)']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['user_id'],
        $r['nickname'],
        $r['status'],
        $r['team1'],
        $r['team2'],
        $r['team3'],
        $r['total_pts'],
        $r['registered_at'],
    ]);
}
fclose($out);
