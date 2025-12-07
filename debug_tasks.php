<?php
require_once 'lib/db.php';
$db = get_db();
$stmt = $db->query("SELECT * FROM tasks");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($tasks);
echo "</pre>";
?>