<?php
require_once __DIR__.'/../lib/db.php';
$db = get_db();
$db->prepare("INSERT IGNORE INTO badges (slug,title,description) VALUES ('7_day_streak','7-Day Streak','Studied 7 days in a row')")->execute();
$badgeId = $db->prepare("SELECT id FROM badges WHERE slug=?");
$badgeId->execute(['7_day_streak']);
$badgeId = $badgeId->fetchColumn();
$users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
foreach ($users as $uid) {
    $stmt = $db->prepare("
      SELECT DATE(started_at) as d FROM study_sessions WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
      GROUP BY d ORDER BY d DESC
    ");
    $stmt->execute([$uid]);
    $days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $streak = 0; $prev = null;
    foreach ($days as $d) {
        if ($prev === null) { $streak = 1; $prev = $d; continue; }
        $diff = (new DateTime($prev))->diff(new DateTime($d))->days;
        if ($diff === 1) { $streak++; $prev = $d; } else break;
    }
    if ($streak >= 7) {
        $db->prepare("INSERT IGNORE INTO user_badges (user_id,badge_id) VALUES (?,?)")->execute([$uid,$badgeId]);
    }
}
echo "Streaks processed\n";
close_db();
?>