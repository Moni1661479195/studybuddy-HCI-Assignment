<?php
require_once __DIR__ . '/../lib/db.php';

class QuickMatchWorker {
    private $db;

    public function __construct() {
        $this->db = get_db();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function run() {
        echo "QuickMatchWorker started.\n";
        while (true) {
            try {
                $this->log("Starting new queue processing cycle.");
                $this->processQueue();
                $this->log("Finished queue processing cycle.");
            } catch (PDOException $e) {
                $log_message = "QuickMatchWorker PDO Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
                error_log($log_message);
                $this->log($log_message);
                echo $log_message . "\n";
            } catch (Exception $e) {
                $log_message = "QuickMatchWorker General Error: " . $e->getMessage();
                error_log($log_message);
                $this->log($log_message);
                echo $log_message . "\n";
            }
            sleep(5); // Poll every 5 seconds
        }
    }

    private function log($message) {
        file_put_contents(__DIR__ . '/../quickmatch_worker.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
    }

    private function processQueue() {
        $this->db->beginTransaction();
        try {
            // Cleanup old matched records (older than 24 hours)
            $cleanup_stmt = $this->db->prepare("DELETE FROM quick_match_queue WHERE status = 'matched' AND requested_at < NOW() - INTERVAL 24 HOUR");
            $cleanup_stmt->execute();

            $stmt = $this->db->prepare("SELECT q.*, u.skill_level, u.gender FROM quick_match_queue q JOIN users u ON q.user_id = u.id WHERE q.status = 'open' AND q.requested_at > NOW() - INTERVAL 1 HOUR ORDER BY q.requested_at ASC FOR UPDATE");
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($requests) > 0) {
                $this->log("Found " . count($requests) . " open requests in the queue.");
            }

            $matched_user_ids = [];
            $unmatched_requests = [];

            // Priority 1: Match users within the queue
            if (count($requests) >= 2) {
                for ($i = 0; $i < count($requests); $i++) {
                    $userA_request = $requests[$i];
                    $userA_id = (int)$userA_request['user_id'];

                    if (in_array($userA_id, $matched_user_ids)) {
                        continue;
                    }

                    for ($j = $i + 1; $j < count($requests); $j++) {
                        $userB_request = $requests[$j];
                        $userB_id = (int)$userB_request['user_id'];

                        if (in_array($userB_id, $matched_user_ids)) {
                            continue;
                        }

                        // Check for mutual preference match
                        $A_likes_B = ($userA_request['desired_skill_level'] === 'any' || $userA_request['desired_skill_level'] === $userB_request['skill_level']) &&
                                     ($userA_request['desired_gender'] === 'any' || $userA_request['desired_gender'] === $userB_request['gender']);

                        $B_likes_A = ($userB_request['desired_skill_level'] === 'any' || $userB_request['desired_skill_level'] === $userA_request['skill_level']) &&
                                     ($userB_request['desired_gender'] === 'any' || $userB_request['desired_gender'] === $userA_request['gender']);

                        if ($A_likes_B && $B_likes_A) {
                            $this->createMatch($userA_id, $userB_id);
                            $matched_user_ids[] = $userA_id;
                            $matched_user_ids[] = $userB_id;
                            break; // userA is matched, move to next user in outer loop
                        }
                    }
                }
            }

            // Collect unmatched users
            foreach ($requests as $request) {
                if (!in_array((int)$request['user_id'], $matched_user_ids)) {
                    $unmatched_requests[] = $request;
                }
            }

            // Priorities 2 & 3: Match remaining users externally
            if (count($unmatched_requests) > 0) {
                $this->log("Processing " . count($unmatched_requests) . " unmatched users for external matching.");

                foreach ($unmatched_requests as $userA_request) {
                    $userA_id = (int)$userA_request['user_id'];
                    $this->log("Searching for external match for user {$userA_id}.");

                    $userA_profile = [
                        'skill_level' => $userA_request['skill_level'],
                        'gender' => $userA_request['gender']
                    ];

                    $partner_id = $this->findExternalMatch($userA_request, $userA_profile, true); // Online
                    if (!$partner_id) {
                        $partner_id = $this->findExternalMatch($userA_request, $userA_profile, false); // Offline
                    }

                    if ($partner_id) {
                        $this->log("Found external match for {$userA_id}: {$partner_id}");
                        $this->createMatch($userA_id, $partner_id);
                    } else {
                        $this->log("No external match found for user {$userA_id}.");
                    }
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }





    private function findExternalMatch($userA_request, $userA_profile, $is_online) {
        $sql = "SELECT u.id
                FROM users u
                WHERE u.id != :userA_id_1 AND u.is_admin = 0";

        if ($is_online) {
            $sql .= " AND u.is_online = 1";
        } else {
            $sql .= " AND u.is_online = 0";
        }

        $sql .= " AND NOT EXISTS (
                    SELECT 1
                    FROM study_partners sp
                    WHERE ((sp.user1_id = :userA_id_2 AND sp.user2_id = u.id) OR (sp.user1_id = u.id AND sp.user2_id = :userA_id_3)) AND sp.is_active = 1
                )";

        $params = [
            ':userA_id_1' => $userA_request['user_id'],
            ':userA_id_2' => $userA_request['user_id'],
            ':userA_id_3' => $userA_request['user_id']
        ];

        $userA_desired_skill = $userA_request['desired_skill_level'] ?? 'any';
        $userA_desired_gender = $userA_request['desired_gender'] ?? 'any';

        if ($userA_desired_skill !== 'any') {
            $sql .= " AND u.skill_level = :userA_desired_skill";
            $params[':userA_desired_skill'] = $userA_desired_skill;
        }
        if ($userA_desired_gender !== 'any') {
            $sql .= " AND u.gender = :userA_desired_gender";
            $params[':userA_desired_gender'] = $userA_desired_gender;
        }

        $sql .= " AND NOT EXISTS (SELECT 1 FROM quick_match_queue WHERE user_id = u.id)";
        $sql .= " ORDER BY RAND() LIMIT 1";

        $this->log("Executing findExternalMatch query: " . $sql . " with params: " . json_encode($params));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($candidate) {
            $this->log("Found external candidate: user_id=" . $candidate['id']);
        } else {
            $this->log("No suitable external candidate found.");
        }
        return $candidate ? (int)$candidate['id'] : null;
    }

    private function createMatch($userA_id, $userB_id) {
        $this->log("Creating match between user {" . $userA_id . "} and user {" . $userB_id . "}");

        // Check if userB exists in the queue
        $stmt_check = $this->db->prepare("SELECT COUNT(*) FROM quick_match_queue WHERE user_id = ?");
        $stmt_check->execute([$userB_id]);
        $userB_in_queue = $stmt_check->fetchColumn() > 0;

        if ($userB_in_queue) {
            // Both users are in the queue, update them atomically.
            $this->log("Both users are in the queue. Updating atomically.");
            $stmt = $this->db->prepare(
                "UPDATE quick_match_queue
                 SET status = 'matched',
                     matched_with = CASE WHEN user_id = ? THEN ? ELSE ? END
                 WHERE user_id IN (?, ?)"
            );
            $stmt->execute([$userA_id, $userB_id, $userA_id, $userA_id, $userB_id]);
        } else {
            // User B is external, update A and insert B.
            $this->log("User {" . $userB_id . "} is external. Updating user {" . $userA_id . "} and inserting user {" . $userB_id . "}.");
            $stmt_A = $this->db->prepare("UPDATE quick_match_queue SET status = 'matched', matched_with = ? WHERE user_id = ?");
            $stmt_A->execute([$userB_id, $userA_id]);

            $stmt_B = $this->db->prepare("INSERT INTO quick_match_queue (user_id, status, matched_with, requested_at) VALUES (?, 'matched', ?, NOW())");
            $stmt_B->execute([$userB_id, $userA_id]);
        }

        $this->log("Successfully created match between user {" . $userA_id . "} and user {" . $userB_id . "}");
    }
}

$worker = new QuickMatchWorker();
$worker->run();
?>