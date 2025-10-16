<?php
// ajax/find_partners_content.php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    // Since this is an AJAX request, we send an error message instead of redirecting
    die('<div class="empty-state"><p>Error: Not authenticated. Please login again.</p></div>');
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// --- Get Current User Info (for filtering) ---
$stmt = $db->prepare("SELECT skill_level FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Handle Filters ---
$preferred_skill = $_GET['skill_level'] ?? 'any';
$online_only = isset($_GET['online_only']) && $_GET['online_only'] == '1';

// --- Build Query for Potential Matches ---
$query_matches = "
    SELECT u.id, u.first_name, u.last_name, u.email, u.skill_level, u.interests, u.bio, uos.is_online, uos.last_seen,
           CASE WHEN uos.is_online = 1 THEN 2.0 ELSE 1.0 END as online_score,
           CASE WHEN u.skill_level = ? THEN 2.0 ELSE 1.0 END as skill_match_score
    FROM users u
    LEFT JOIN user_online_status uos ON u.id = uos.user_id
    WHERE u.id != ?
    AND NOT EXISTS (SELECT 1 FROM study_partners sp WHERE ((sp.user1_id = ? AND sp.user2_id = u.id) OR (sp.user1_id = u.id AND sp.user2_id = ?)) AND sp.is_active = 1)
    AND NOT EXISTS (SELECT 1 FROM study_requests sr WHERE ((sr.sender_id = ? AND sr.receiver_id = u.id) OR (sr.sender_id = u.id AND sr.receiver_id = ?)) AND sr.status = 'pending')
";
$params_matches = [$current_user['skill_level'], $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id];

if ($preferred_skill !== 'any') {
    $query_matches .= " AND u.skill_level = ?";
    $params_matches[] = $preferred_skill;
}
if ($online_only) {
    $query_matches .= " AND uos.is_online = 1";
}
$query_matches .= " ORDER BY (online_score + skill_match_score) DESC, uos.last_seen DESC LIMIT 20";

$stmt_matches = $db->prepare($query_matches);
$stmt_matches->execute($params_matches);
$matches = $stmt_matches->fetchAll(PDO::FETCH_ASSOC);

// This file now only outputs the HTML fragment for the modal body
?>
<form method="GET" action="ajax/find_partners_content.php" class="filter-section" id="find-partners-filter-form">
    <div class="filter-row">
        <div class="filter-group">
            <label><i class="fas fa-chart-line"></i> Preferred Skill Level</label>
            <select name="skill_level">
                <option value="any" <?php echo $preferred_skill === 'any' ? 'selected' : ''; ?>>Any Level</option>
                <option value="beginner" <?php echo $preferred_skill === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                <option value="intermediate" <?php echo $preferred_skill === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                <option value="advanced" <?php echo $preferred_skill === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
            </select>
        </div>
        <div class="filter-group checkbox-filter">
            <input type="checkbox" id="online_only" name="online_only" value="1" <?php echo $online_only ? 'checked' : ''; ?>>
            <label for="online_only"><i class="fas fa-wifi"></i> Online Users Only</label>
        </div>
        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Apply Filters</button>
    </div>
</form>

<?php if (empty($matches)): ?>
    <div class="empty-state">
        <i class="fas fa-user-friends"></i>
        <h3>No potential partners found</h3>
        <p>Try adjusting your filters.</p>
    </div>
<?php else: ?>
    <div class="matches-grid">
        <?php foreach ($matches as $match): 
            $initials = strtoupper(substr($match['first_name'], 0, 1) . substr($match['last_name'], 0, 1));
            $skill_class = 'skill-' . $match['skill_level'];
        ?>
            <div class="user-card">
                <div class="online-status">
                    <span class="online-dot <?php echo $match['is_online'] ? 'online' : 'offline'; ?>"></span>
                    <span><?php echo $match['is_online'] ? 'Online' : 'Offline'; ?></span>
                </div>
                
                <div class="user-header">
                    <div class="user-avatar"><?php echo $initials; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($match['first_name'] . ' ' . $match['last_name']); ?></div>
                        <span class="user-skill <?php echo $skill_class; ?>">
                            <?php echo ucfirst($match['skill_level'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="user-details">
                    <?php if ($match['interests']): ?>
                        <div class="user-interests">
                            <i class="fas fa-heart"></i> <?php echo htmlspecialchars($match['interests']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($match['bio']): ?>
                        <div class="user-bio">
                            <?php echo htmlspecialchars($match['bio']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-actions">
                    <a href="user_profile.php?id=<?php echo $match['id']; ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <form method="POST" action="api/send_study_request.php" data-ajax="true" style="flex: 1;">
                        <input type="hidden" name="receiver_id" value="<?php echo $match['id']; ?>">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Send Request
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>