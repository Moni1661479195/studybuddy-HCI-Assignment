<?php
// ===== 终极防重复版 =====
// 利用 study_partners 的唯一键来防止竞态条件

try {
    require_once '../session.php';
    require_once '../lib/db.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit();
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $db = get_db();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $request_data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? $request_data['action'] ?? null;

    function calculate_match_score(array $user1_data, array $user2_data): int {
        $user1_wants = $user1_data['desired_skill_level'];
        $user1_is = $user1_data['skill_level'];
        $user2_wants = $user2_data['desired_skill_level'];
        $user2_is = $user2_data['skill_level'];

        if ($user1_wants === 'any' || $user2_wants === 'any' || $user1_wants === $user2_is || $user2_wants === $user1_is) {
            return 100;
        }
        return 0;
    }

    switch ($action) {
        case 'join_queue':
            $desired_skill = $request_data['desiredSkillLevel'] ?? 'any';
            $sql = "INSERT INTO quick_match_queue (user_id, desired_skill_level, status) VALUES (?, ?, 'open') ON DUPLICATE KEY UPDATE desired_skill_level = ?, status = 'open', requested_at = NOW()";
            $stmt = $db->prepare($sql);
            $stmt->execute([$current_user_id, $desired_skill, $desired_skill]);
            echo json_encode(['success' => true]);
            break;

        case 'check_match':
            $stmt = $db->prepare("SELECT q.status, q.matched_with, q.match_type, u.id, u.first_name, u.last_name, u.skill_level FROM quick_match_queue q LEFT JOIN users u ON q.matched_with = u.id WHERE q.user_id = ?");
            $stmt->execute([$current_user_id]);
            $my_status = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($my_status && $my_status['status'] === 'matched') {
                echo json_encode([ 'matched' => true, 'match_type' => $my_status['match_type'], 'user' => [ 'id' => $my_status['id'], 'first_name' => $my_status['first_name'], 'last_name' => $my_status['last_name'], 'skill_level' => $my_status['skill_level'] ] ]);
                exit();
            }

            $db->beginTransaction();
            
            $stmt_me = $db->prepare("SELECT u.id, u.skill_level, q.desired_skill_level FROM users u JOIN quick_match_queue q ON u.id = q.user_id WHERE u.id = ? AND q.status = 'open' FOR UPDATE");
            $stmt_me->execute([$current_user_id]);
            $me = $stmt_me->fetch(PDO::FETCH_ASSOC);

            if (!$me) { $db->rollBack(); echo json_encode(['matched' => false]); exit(); }
            
            $stmt_others = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.skill_level, u.last_seen, q.desired_skill_level FROM users u JOIN quick_match_queue q ON u.id = q.user_id WHERE q.status = 'open' AND u.id != ?");
            $stmt_others->execute([$current_user_id]);
            $candidates = $stmt_others->fetchAll(PDO::FETCH_ASSOC);

            $best_match = null;
            $highest_score = -1;

            foreach ($candidates as $candidate) {
                $score = calculate_match_score($me, $candidate);
                if ($score > $highest_score) {
                    $highest_score = $score;
                    $best_match = $candidate;
                }
            }
            
            if ($best_match && $highest_score >= 100) {
                $partner_id = (int)$best_match['id'];

                // --- 【核心修改】利用 UNIQUE KEY 作为锁 ---
                $user1 = min($current_user_id, $partner_id);
                $user2 = max($current_user_id, $partner_id);
                $am_i_the_winner = false;

                try {
                    // 尝试插入伙伴关系。ON DUPLICATE KEY UPDATE 会让已存在的记录也被更新，但我们可以通过检查rowCount来判断。
                    // 一个更可靠的方法是先尝试INSERT，如果失败了再UPDATE。但对于低并发，这个方法也足够。
                    // 为了绝对的原子性，我们改为先删除旧的'ended'关系，再用INSERT IGNORE尝试插入。
                    
                    // 1. 删除可能存在的旧的、已结束的关系
                    $db->prepare("DELETE FROM study_partners WHERE user1_id = ? AND user2_id = ? AND is_active = 0")->execute([$user1, $user2]);

                    // 2. 尝试插入新的活跃关系，IGNORE会忽略重复键错误
                    $partner_stmt = $db->prepare("INSERT IGNORE INTO study_partners (user1_id, user2_id, is_active, last_activity) VALUES (?, ?, 1, NOW())");
                    $partner_stmt->execute([$user1, $user2]);

                    // 3. 检查是否是我们成功插入了记录
                    if ($partner_stmt->rowCount() > 0) {
                        $am_i_the_winner = true;
                    }

                } catch (PDOException $e) {
                    // 如果发生除了唯一键冲突之外的其他错误，就记录下来
                    if ($e->errorInfo[1] != 1062) { // 1062 is the error code for duplicate entry
                       error_log("Partner creation error: " . $e->getMessage());
                    }
                }
                
                // 只有“胜利者”才能创建会话
                if ($am_i_the_winner) {
                    $metadata = json_encode(['group' => [$current_user_id, $partner_id]]);
                    $create_session = $db->prepare("INSERT INTO study_sessions (user_id, is_group, metadata, started_at, status) VALUES (?, 1, ?, NOW(), 'active')");
                    $create_session->execute([$current_user_id, $metadata]);
                }
                // --- 【核心修改结束】 ---
                
                // 无论是否胜利，都将两个用户更新为 matched 状态以离开队列
                $match_type_result = 'session';
                $update = $db->prepare("UPDATE quick_match_queue SET status = 'matched', matched_with = ?, match_type = ? WHERE user_id = ?");
                $update->execute([$partner_id, $match_type_result, $current_user_id]);
                $update->execute([$current_user_id, $match_type_result, $partner_id]);

                $db->commit();

                echo json_encode([
                    'matched' => true,
                    'match_type' => $match_type_result,
                    'user' => [
                        'id' => $best_match['id'],
                        'first_name' => $best_match['first_name'],
                        'last_name' => $best_match['last_name'],
                        'skill_level' => $best_match['skill_level']
                    ]
                ]);
                exit();
            }
            
            $db->rollBack(); // No match found, roll back the transaction for the FOR UPDATE lock
            echo json_encode(['matched' => false]);
            break;

        case 'queue_count':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM quick_match_queue WHERE status = 'open'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'count' => (int)$result['count']]);
            break;

        default:
             echo json_encode(['success' => false, 'message' => 'Invalid action.']);
             break;
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    echo json_encode([ 'success' => false, 'message' => 'An error occurred.', 'error_details' => $e->getMessage() ]);
}