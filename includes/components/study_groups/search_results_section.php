<?php
// Component: Search Results / Recommendations Section
// Variables expected: $db, $current_user_id, $study_mate_ids
?>
<div class="search-results">
    <?php
    if (isset($_GET['search_user']) && !empty($_GET['search_user'])) {
        $search_user = trim($_GET['search_user']);
        
        // Enhanced search with multiple strategies
        $search_term = "%$search_user%";
        $search_lower = "%" . strtolower($search_user) . "%" ;
        
        // Try case-insensitive search first
        $stmt = $db->prepare(" 
            SELECT id, first_name, last_name, email FROM users 
            WHERE (
                LOWER(first_name) LIKE ? OR 
                LOWER(last_name) LIKE ? OR 
                LOWER(email) LIKE ? OR
                LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?
            ) AND id != ? 
            ORDER BY 
                CASE 
                    WHEN LOWER(first_name) = LOWER(?) THEN 1
                    WHEN LOWER(last_name) = LOWER(?) THEN 1
                    WHEN LOWER(first_name) LIKE ? THEN 2
                    WHEN LOWER(last_name) LIKE ? THEN 2
                    ELSE 3
                END
            LIMIT 10
        ");
        $stmt->execute([
            $search_lower, $search_lower, $search_lower, $search_lower, $current_user_id,
            $search_user, $search_user, $search_lower, $search_lower
        ]);
        $found_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If still no results, get recommendations from the system
        if (empty($found_users)) {
            $stmt_rec = $db->prepare(" 
                SELECT u.id, u.first_name, u.last_name, u.email, r.score 
                FROM recommendations r 
                JOIN users u ON r.candidate_user_id = u.id 
                WHERE r.user_id = ? 
                ORDER BY r.score DESC 
                LIMIT 10
            ");
            $stmt_rec->execute([$current_user_id]);
            $found_users = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);
            
            // If no recommendations, show random users
            if (empty($found_users)) {
                $stmt_all = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id != ? ORDER BY RAND() LIMIT 10");
                $stmt_all->execute([$current_user_id]);
                $found_users = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
            }
            $show_no_match_message = true;
        }

        if ($found_users): ?>
            <?php if (isset($show_no_match_message)): ?>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> No matches found for "<?php echo htmlspecialchars($search_user); ?>". 
                    <?php if (isset($stmt_rec) && $stmt_rec->rowCount() > 0):
                    ?>
                        Showing recommended study partners based on your profile:
                    <?php else:
                    ?>
                        Showing other available users:
                    <?php endif; ?>
                </div>
            <?php else:
            ?>
                <h3 style="margin-bottom: 1rem; color: #1f2937;">Search Results for "<?php echo htmlspecialchars($search_user); ?>"</h3>
            <?php endif; ?>
            <div class="user-results-list">
                <?php foreach ($found_users as $user) {
                    // Check if a request has already been sent
                    $stmt_check_request = $db->prepare("SELECT * FROM study_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                    $stmt_check_request->execute([$current_user_id, $user['id'], $user['id'], $current_user_id]);
                    $existing_request = $stmt_check_request->fetch(PDO::FETCH_ASSOC);

                    $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                ?>
                    <a href="user_profile.php?id=<?php echo $user['id']; ?>" class="user-result-item-link">
                    <div class="user-result-item">
                        <div class="user-info">
                            <div class="user-avatar"><?php echo $initials; ?></div>
                            <div class="user-details">
                                <h4>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <?php if (in_array($user['id'], $study_mate_ids)):
                                    ?>
                                        <span class="badge" style="background-color: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem;">Study Mate</span>
                                    <?php endif; ?>
                                    <?php if (isset($user['score'])):
                                    ?>
                                        <span style="color: #10b981; font-size: 0.8rem; font-weight: 500;">
                                            (<?php echo number_format($user['score'] * 100, 1); ?>% match)
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="request-status">
                            <?php if ($existing_request && $existing_request['status'] == 'pending' && $existing_request['sender_id'] == $current_user_id):
                            ?>
                                <span class="status-text pending">
                                    <i class="fas fa-clock"></i> Request Sent
                                </span>
                                <form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                                    <input type="hidden" name="action" value="cancel_study_request">
                                    <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                    <input type="hidden" name="original_search_user" value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>">
                                    <button type="submit" class="cta-button danger small">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            <?php elseif ($existing_request && $existing_request['status'] == 'pending' && $existing_request['receiver_id'] == $current_user_id):
                            ?>
                                <form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                                    <input type="hidden" name="action" value="accept_request">
                                    <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                    <button type="submit" class="cta-button primary small">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>
                                <form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                                    <input type="hidden" name="action" value="decline_request">
                                    <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                    <button type="submit" class="cta-button danger small">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </form>
                            <?php elseif ($existing_request && $existing_request['status'] == 'accepted'): ?>
                                <span class="badge" style="background-color: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem;">Study Mate</span>
                            <?php else:
                            ?>
                                <form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                                    <input type="hidden" name="action" value="send_study_request">
                                    <input type="hidden" name="receiver_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="original_search_user" value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>">
                                    <button type="submit" id="send-request-<?php echo $user['id']; ?>" class="cta-button primary small">
                                        <i class="fas fa-paper-plane"></i> Send Request
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    </a>
                <?php } ?>
            </div>
        <?php else:
        ?>
            <div class="alert info">
                <i class="fas fa-search"></i> No users available at the moment.
            </div>
        <?php endif; ?>
    <?php } else { ?>
        <div id="recommendations-section"></div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const recommendationsSection = document.getElementById('recommendations-section');
                const searchResultsDiv = document.querySelector('.search-results');

                function loadRecommendations() {
                    if (recommendationsSection) {
                        recommendationsSection.innerHTML = '<div class="loader"><i class="fas fa-spinner fa-spin"></i> Refreshing...</div>';
                        fetch('ajax/study_groups_partials.php?partial=recommendations&_=' + new Date().getTime())
                            .then(response => response.text())
                            .then(html => {
                                recommendationsSection.innerHTML = html;
                            }).catch(error => {
                                console.error('Failed to load recommendations:', error);
                                recommendationsSection.innerHTML = '<p style="color: red;">Error loading recommendations.</p>';
                            });
                    }
                }

                if (recommendationsSection) {
                    loadRecommendations(); // Initial load
                }

                if(searchResultsDiv) {
                    searchResultsDiv.addEventListener('click', function(e) {
                        if (e.target && e.target.id === 'refresh-suggestions-button') {
                            loadRecommendations();
                        }
                    });
                }
            });
        </script>
    <?php }
    ?>
</div>