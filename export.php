<?php
/* Admin-only CSV export of all participants with team picks and current scores. */
require_once __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Forbidden — log into admin.php first.');
}

$pdo = db();

// Segment-aware scoring: a match scores for a slot only if its kickoff fell
// inside that team's stint (start_at <= kickoff < end_at). Match points +
// end-of-tournament bonuses, summed across every segment a participant held.
$rows = $pdo->query("
    WITH seg_pts AS (
        SELECT ps.participant_id, COALESCE(SUM(
            (CASE
                WHEN (f.home_id = ps.team_id AND f.home_goals > f.away_goals)
                  OR (f.away_id = ps.team_id AND f.away_goals > f.home_goals)         THEN 3
                WHEN f.home_goals = f.away_goals
                  AND (f.home_id = ps.team_id OR f.away_id = ps.team_id)              THEN 1
                ELSE 0
            END)
            + (CASE
                WHEN f.stage = 'FINAL'
                     AND ((f.winner='H' AND f.home_id=ps.team_id) OR (f.winner='A' AND f.away_id=ps.team_id)) THEN 20
                WHEN f.stage = 'FINAL'
                     AND ((f.winner='H' AND f.away_id=ps.team_id) OR (f.winner='A' AND f.home_id=ps.team_id)) THEN 10
                WHEN f.stage = 'THIRD_PLACE'
                     AND ((f.winner='H' AND f.home_id=ps.team_id) OR (f.winner='A' AND f.away_id=ps.team_id)) THEN 5
                ELSE 0
            END)
        ), 0) AS pts
        FROM pick_segments ps
        JOIN wc_fixtures f
          ON (f.home_id = ps.team_id OR f.away_id = ps.team_id)
         AND f.status = 'FT'
         AND f.utc_date >= ps.start_at
         AND (ps.end_at IS NULL OR f.utc_date < ps.end_at)
        GROUP BY ps.participant_id
    ),
    sw AS (
        SELECT participant_id, GREATEST(COUNT(*) - 3, 0) AS switches
        FROM pick_segments GROUP BY participant_id
    )
    SELECT
        p.user_id,
        COALESCE(p.nickname, '')          AS nickname,
        CASE WHEN p.claimed = 1 THEN 'Registered' ELSE 'Open' END AS status,
        COALESCE(t1.name, '')             AS team1,
        COALESCE(t2.name, '')             AS team2,
        COALESCE(t3.name, '')             AS team3,
        COALESCE(sp.pts, 0)               AS total_pts,
        COALESCE(sw.switches, 0)          AS switches,
        TO_CHAR(COALESCE(p.joined_at, p.created_at) AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI') AS registered_at
    FROM participants p
    LEFT JOIN wc_teams t1 ON t1.id = p.team1
    LEFT JOIN wc_teams t2 ON t2.id = p.team2
    LEFT JOIN wc_teams t3 ON t3.id = p.team3
    LEFT JOIN seg_pts  sp ON sp.participant_id = p.id
    LEFT JOIN sw          ON sw.participant_id = p.id
    ORDER BY CAST(p.user_id AS INTEGER) ASC
")->fetchAll();

$filename = 'worldcup2026_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fputcsv($out, ['Access Code', 'Nickname', 'Status', 'Current Team 1', 'Current Team 2', 'Current Team 3', 'Total Points', 'Switches', 'Registered At (UTC)']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['user_id'],
        $r['nickname'],
        $r['status'],
        $r['team1'],
        $r['team2'],
        $r['team3'],
        $r['total_pts'],
        $r['switches'],
        $r['registered_at'],
    ]);
}
fclose($out);
