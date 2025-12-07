<?php
require_once __DIR__.'/db.php';

function get_user_profile(PDO $db, int $uid): array {
    static $cache = [];
    
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }
    
    $p = ['interests'=>[], 'skill'=>null, 'tz'=>null, 'last_active'=>null, 'streak'=>0, 'is_online'=>0, 'last_seen'=>null];
    
    // Single query with JOIN to get all data at once
    $stmt = $db->prepare("
        SELECT u.skill_level, u.timezone, u.last_active, u.is_online, u.last_seen, u.gender, u.subject_id,
               GROUP_CONCAT(CONCAT(i.name,':', ui.weight)) as interests,
               COALESCE(ss.streak, 0) as streak
        FROM users u
        LEFT JOIN user_interests ui ON ui.user_id = u.id
        LEFT JOIN interests i ON ui.interest_id = i.id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as streak 
            FROM study_sessions 
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY user_id
        ) ss ON ss.user_id = u.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$uid]);
    if ($r = $stmt->fetch()) {
        $p['skill'] = $r['skill_level'] ?? null;
        $p['tz'] = $r['timezone'] ?? null;
        $p['last_active'] = $r['last_active'] ?? null;
        $p['streak'] = (int)$r['streak'];
        $p['is_online'] = (int)$r['is_online'];
        $p['last_seen'] = $r['last_seen'] ?? null;
        $p['gender'] = $r['gender'] ?? null;
        $p['subject_id'] = $r['subject_id'] ?? null;
        
        if ($r['interests']) {
            foreach (explode(',', $r['interests']) as $interest_pair) {
                [$name, $weight] = explode(':', $interest_pair);
                $p['interests'][$name] = (float)$weight;
            }
        }
    }
    
    $cache[$uid] = $p;
    return $p;
}

function cosine_sim_arrays(array $a, array $b): float {
    if (empty($a) || empty($b)) return 0.0;
    
    $dot = 0.0; $na = 0.0; $nb = 0.0;
    $common_keys = array_intersect_key($a, $b);
    
    foreach ($common_keys as $k => $v) {
        $dot += $a[$k] * $b[$k];
    }
    
    foreach ($a as $v) $na += $v * $v;
    foreach ($b as $v) $nb += $v * $v;
    
    if ($na == 0 || $nb == 0) return 0.0;
    return $dot / (sqrt($na) * sqrt($nb));
}

function tz_distance_hours(?string $a, ?string $b): float {
    static $tz_cache = [];
    
    if (!$a || !$b) return 24.0;
    
    $cache_key = "$a|$b";
    if (isset($tz_cache[$cache_key])) {
        return $tz_cache[$cache_key];
    }
    
    try {
        $ta = new DateTimeZone($a);
        $tb = new DateTimeZone($b);
        $now = new DateTime();
        $offsetA = $ta->getOffset($now);
        $offsetB = $tb->getOffset($now);
        $result = abs($offsetA - $offsetB) / 3600.0;
        
        $tz_cache[$cache_key] = $result;
        return $result;
    } catch (Exception $e) {
        return 24.0;
    }
}

function compute_hybrid_score(array $a, array $b): float {
    $interest = cosine_sim_arrays($a['interests'], $b['interests']);
    
    $skillScore = 0.2;
    if ($a['skill'] && $b['skill']) {
        $skillScore = ($a['skill'] === $b['skill']) ? 1.0 : 0.6;
    }
    
    $tzHours = tz_distance_hours($a['tz'], $b['tz']);
    $tzScore = max(0, 1 - ($tzHours / 24));
    
    $smax = max(1, $a['streak'], $b['streak']);
    $streakScore = (($a['streak'] + $b['streak']) / 2) / $smax;
    
    $onlineScore = 0.0;
    if ($a['is_online'] && $b['is_online']) {
        $onlineScore = 1.0;
    } elseif ($a['is_online'] || $b['is_online']) {
        $onlineScore = 0.5;
    }
    
    return 0.5 * $interest + 0.15 * $skillScore + 0.10 * $tzScore + 0.15 * $streakScore + 0.10 * $onlineScore;
}

// Batch compute recommendations for multiple users
function compute_batch_recommendations(PDO $db, array $user_ids, int $batch_size = 100): array {
    $all_recommendations = [];
    $user_batches = array_chunk($user_ids, $batch_size);
    
    foreach ($user_batches as $batch) {
        // Pre-load all profiles for this batch
        $profiles = [];
        foreach ($batch as $uid) {
            $profiles[$uid] = get_user_profile($db, $uid);
        }
        
        foreach ($batch as $uid) {
            $scores = [];
            $profileA = $profiles[$uid];
            
            foreach ($user_ids as $cid) {
                if ($uid == $cid) continue;
                
                $profileB = isset($profiles[$cid]) ? $profiles[$cid] : get_user_profile($db, $cid);
                $score = compute_hybrid_score($profileA, $profileB);
                
                if ($score > 0.05) {
                    $scores[$cid] = $score;
                }
            }
            
            arsort($scores);
            $all_recommendations[$uid] = array_slice($scores, 0, 50, true);
        }
    }
    
    return $all_recommendations;
}

// Find compatible users for quick match
function find_quick_match_candidates(PDO $db, int $user_id, int $limit = 10): array {
    // First try to find from recommendations, prioritizing online users
    $stmt = $db->prepare("
        SELECT r.candidate_user_id, r.score, u.first_name, u.last_name
        FROM recommendations r
        JOIN users u ON r.candidate_user_id = u.id
        WHERE r.user_id = ? AND r.score > 0.12
        AND r.candidate_user_id NOT IN (
            SELECT user_id FROM quick_match_queue WHERE status = 'open'
        )
        ORDER BY u.is_online DESC, r.score DESC
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If not enough candidates, find other online users with similar interests
    if (count($candidates) < $limit) {
        $stmt2 = $db->prepare("
            SELECT DISTINCT u.id as candidate_user_id, u.first_name, u.last_name,
                   0.5 as score
            FROM users u
            JOIN user_interests ui ON u.id = ui.user_id
            WHERE u.id != ? 
            AND u.is_online = 1
            AND ui.interest_id IN (
                SELECT interest_id FROM user_interests WHERE user_id = ?
            )
            AND u.id NOT IN (
                SELECT user_id FROM quick_match_queue WHERE status = 'open'
            )
            ORDER BY u.last_seen DESC
            LIMIT ?
        ");
        $stmt2->execute([$user_id, $user_id, $limit - count($candidates)]);
        $additional = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $candidates = array_merge($candidates, $additional);
    }

    // If still not enough candidates, find offline users from recommendations
    if (count($candidates) < $limit) {
        $stmt3 = $db->prepare("
            SELECT r.candidate_user_id, r.score, u.first_name, u.last_name
            FROM recommendations r
            JOIN users u ON r.candidate_user_id = u.id
            WHERE r.user_id = ? AND r.score > 0.12
            AND u.is_online = 0
            AND r.candidate_user_id NOT IN (
                SELECT user_id FROM quick_match_queue WHERE status = 'open'
            )
            ORDER BY r.score DESC
            LIMIT ?
        ");
        $stmt3->execute([$user_id, $limit - count($candidates)]);
        $additional = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        $candidates = array_merge($candidates, $additional);
    }

    // If still not enough candidates, find other offline users with similar interests
    if (count($candidates) < $limit) {
        $stmt4 = $db->prepare("
            SELECT DISTINCT u.id as candidate_user_id, u.first_name, u.last_name,
                   0.5 as score
            FROM users u
            JOIN user_interests ui ON u.id = ui.user_id
            WHERE u.id != ? 
            AND u.is_online = 0
            AND u.last_active >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND ui.interest_id IN (
                SELECT interest_id FROM user_interests WHERE user_id = ?
            )
            AND u.id NOT IN (
                SELECT user_id FROM quick_match_queue WHERE status = 'open'
            )
            ORDER BY u.last_active DESC
            LIMIT ?
        ");
        $stmt4->execute([$user_id, $user_id, $limit - count($candidates)]);
        $additional = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        $candidates = array_merge($candidates, $additional);
    }
    
    return $candidates;
}

// Enhanced user search with fuzzy matching
function search_users(PDO $db, string $search_term, int $current_user_id, int $limit = 10): array {
    $search_term = trim($search_term);
    if (empty($search_term)) return [];
    
    // Prepare search variations
    $exact = $search_term;
    $fuzzy = "%$search_term%";
    $words = explode(' ', $search_term);
    
    $results = [];
    
    // 1. Exact name match (highest priority)
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email, 1.0 as relevance
        FROM users 
        WHERE (LOWER(CONCAT(first_name, ' ', last_name)) = LOWER(?) 
               OR LOWER(first_name) = LOWER(?) 
               OR LOWER(last_name) = LOWER(?))
        AND id != ?
        LIMIT ?
    ");
    $stmt->execute([$exact, $exact, $exact, $current_user_id, $limit]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Partial name match
    if (count($results) < $limit) {
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, email, 0.8 as relevance
            FROM users 
            WHERE (LOWER(first_name) LIKE LOWER(?) 
                   OR LOWER(last_name) LIKE LOWER(?)
                   OR LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?))
            AND id != ?
            AND id NOT IN (" . str_repeat('?,', count($results)) . "0)
            ORDER BY 
                CASE 
                    WHEN LOWER(first_name) LIKE LOWER(?) THEN 1
                    WHEN LOWER(last_name) LIKE LOWER(?) THEN 2
                    ELSE 3
                END
            LIMIT ?
        ");
        
        $params = [$fuzzy, $fuzzy, $fuzzy, $current_user_id];
        foreach ($results as $r) $params[] = $r['id'];
        $params[] = "$search_term%";
        $params[] = "$search_term%";
        $params[] = $limit - count($results);
        
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // 3. Email match
    if (count($results) < $limit && filter_var($search_term, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, email, 0.9 as relevance
            FROM users 
            WHERE LOWER(email) = LOWER(?)
            AND id != ?
            AND id NOT IN (" . str_repeat('?,', count($results)) . "0)
            LIMIT ?
        ");
        
        $params = [$search_term, $current_user_id];
        foreach ($results as $r) $params[] = $r['id'];
        $params[] = $limit - count($results);
        
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    return $results;
}
?>