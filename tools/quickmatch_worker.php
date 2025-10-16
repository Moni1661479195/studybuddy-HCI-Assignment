<?php
// File-based locking mechanism to ensure only one instance runs at a time
$lockFile = '/tmp/quickmatch_worker.lock';
$fp = fopen($lockFile, 'c');

// Try to get an exclusive non-blocking lock
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    // Another instance is still running, exit gracefully
    exit("Quickmatch worker: Another instance is already running. Exiting.\n");
}

// Ensure the lock is released when the script finishes
register_shutdown_function(function() use ($fp, $lockFile) {
    flock($fp, LOCK_UN); // Release the lock
    fclose($fp); // Close the file pointer
    // Optionally, delete the lock file if it's empty or not needed for debugging
    // if (file_exists($lockFile) && filesize($lockFile) == 0) {
    //     unlink($lockFile);
    // }
});


require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/matching.php';

$db = null; // Initialize $db

try {
    $db = get_db(); // Get connection

    // --- Worker logic (single iteration) ---
    // pick two open entries (simple approach)
    $rows = $db->query("SELECT id, user_id FROM quick_match_queue WHERE status='open' GROUP BY user_id ORDER BY requested_at ASC LIMIT 2")->fetchAll();
    
    if (count($rows) < 2) {
        // No matches, exit
        exit("Quickmatch worker: No pending matches. Exiting.\n");
    }
    
    $e1 = $rows[0]; $e2 = $rows[1];
    
    $db->beginTransaction();
    
    // lock rows for update to avoid races
    $lock1 = $db->prepare("SELECT * FROM quick_match_queue WHERE id = ? FOR UPDATE");
    $lock1->execute([$e1['id']]); 
    $entry1 = $lock1->fetch();
    
    $lock2 = $db->prepare("SELECT * FROM quick_match_queue WHERE id = ? FOR UPDATE");
    $lock2->execute([$e2['id']]); 
    $entry2 = $lock2->fetch();
    
    if (!$entry1 || !$entry2 || $entry1['status'] !== 'open' || $entry2['status'] !== 'open') { 
        $db->rollBack(); 
        exit("Quickmatch worker: Entries not valid or already matched. Exiting.\n");
    }
    
    $p1 = get_user_profile($db, (int)$entry1['user_id']);
    $p2 = get_user_profile($db, (int)$entry2['user_id']);
    $score = compute_hybrid_score($p1, $p2);
    
    if ($score < 0.12) { 
        $db->rollBack(); 
        exit("Quickmatch worker: Score too low. Exiting.\n");
    }
    
    $u1 = (int)$entry1['user_id']; 
    $u2 = (int)$entry2['user_id'];
    
    $upd = $db->prepare("UPDATE quick_match_queue SET status='matched', matched_with = ? WHERE id = ?");
    $upd->execute([$u2, $entry1['id']]);
    $upd->execute([$u1, $entry2['id']]);
    
    $create = $db->prepare("INSERT INTO study_sessions (user_id, started_at, is_group, metadata) VALUES (?,?,1,?)");
    $metadata = json_encode(['group'=>[$u1, $u2]]);
    $create->execute([$u1, date('Y-m-d H:i:s'), $metadata]);
    
    $db->commit();
    
    echo "Matched {$u1} with {$u2} score={$score}\n";
    
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Quickmatch worker error: " . $e->getMessage());
    exit("Quickmatch worker: An error occurred: " . $e->getMessage() . "\n");
} finally {
    // The close_db() is already registered as a shutdown function in lib/db.php
    // This will handle closing the DB connection when the script exits.
}
?>