<?php
require_once 'session.php';
require_once __DIR__ . '/includes/study_groups_actions.php';

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/lib/db.php';

// --- Data Fetching ---
try {
    $db = get_db();
} catch (Exception $e) {
    error_log("DB error on study-groups page: " . $e->getMessage());
    http_response_code(500);
    echo "Database connection failed. Please try again later.";
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Fetch all necessary data for the components

// Fetch study mates
$stmt_mates = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_partners sp JOIN users u ON u.id = IF(sp.user1_id = ?, sp.user2_id, sp.user1_id) WHERE (sp.user1_id = ? OR sp.user2_id = ?) AND sp.is_active = 1");
$stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
$study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);
$study_mate_ids = array_column($study_mates, 'id');

// Get pending group invitations
$invitations_stmt = $db->prepare("SELECT sgi.*, sg.group_name as group_name, CONCAT(u.first_name, ' ', u.last_name) as sender_name, u.profile_picture_path FROM study_group_invitations sgi JOIN study_groups sg ON sgi.group_id = sg.group_id JOIN users u ON sgi.sender_id = u.id WHERE sgi.receiver_id = ? AND sgi.status = 'pending' ORDER BY sgi.invited_at DESC");
$invitations_stmt->execute([$current_user_id]);
$invitations = $invitations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending received study requests
$stmt_received = $db->prepare("SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_requests sr JOIN users u ON sr.sender_id = u.id WHERE sr.receiver_id = ? AND sr.status = 'pending' ORDER BY sr.requested_at DESC");
$stmt_received->execute([$current_user_id]);
$received_requests = $stmt_received->fetchAll(PDO::FETCH_ASSOC);

// Get user's permanent groups
$stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
$stmt_get_ids->execute([$current_user_id]);
$group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);
$my_groups = [];
if (!empty($group_ids)) {
    $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
    $groups_stmt = $db->prepare("SELECT * FROM study_groups WHERE group_id IN ($in_clause) AND is_active = 1 ORDER BY created_at DESC");
    $groups_stmt->execute($group_ids);
    $my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM study_group_members WHERE group_id = ?");
    foreach ($my_groups as $i => $group) {
        $count_stmt->execute([$group['group_id']]);
        $my_groups[$i]['member_count'] = $count_stmt->fetchColumn();
    }
}

// Fetch sent study requests
$sent_requests_query = "SELECT sr.request_id, u.id AS user_id, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_requests sr JOIN users u ON sr.receiver_id = u.id WHERE sr.sender_id = ? AND sr.status = 'pending'";
$params = [$current_user_id];
if (!empty($study_mate_ids)) {
    $in_clause = implode(',', array_fill(0, count($study_mate_ids), '?'));
    $sent_requests_query .= " AND sr.receiver_id NOT IN ($in_clause)";
    $params = array_merge($params, $study_mate_ids);
}
$stmt_sent_requests = $db->prepare($sent_requests_query);
$stmt_sent_requests->execute($params);
$sent_requests = $stmt_sent_requests->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Study Groups</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/modern_auth.css">
    <link rel="stylesheet" href="assets/css/study-groups.css?v=<?php echo time(); ?>">

</head>

<style>
        /* Borrowed dashboard gray background */
        .dashboard-container {
            background-color: #f9fafb; /* 浅灰色 */
            padding-top: 2.5rem; /* 40px */
            padding-bottom: 4rem; /* 64px */
            min-height: calc(100vh - 100px);
        }
        /* Borrowed dashboard card style */
        .dashboard-card {
            background: #ffffff;
            border-radius: 1.5rem; /* 24px */
            padding: 2.5rem; /* 40px */
            max-width: 1000px; /* 卡片最大宽度 */
            margin: 0 auto; /* 水平居中 */
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        /* Fix Tailwind conflict for "Back to Home" link */
        .dashboard-card a {
            color: var(--primary-blue); /* Use primary blue */
        }
    </style>

<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container mt-24 md:mt-28">
        <div class="dashboard-card">
            <h1 class="welcome-title">Study Groups</h1>
            <p class="welcome-message">Connect with your study partners and collaborate on your learning journey.</p>

            <?php // Display success/error messages from redirects
            if (isset($_SESSION['success'])):
 ?>
                <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])):
 ?>
                <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php include __DIR__ . '/includes/components/study_groups/my_groups_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/invitations_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/study_mates_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/sent_requests_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/received_requests_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/find_partners_section.php'; ?>
            <?php include __DIR__ . '/includes/components/study_groups/search_results_section.php'; ?>

            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 500; margin-top: 2rem; display: inline-block;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Modals -->
    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header"><i class="fas fa-plus-circle"></i> Create Study Group</h2>
            <form method="POST" action="study-groups.php" class="no-ajax">
                <input type="hidden" name="create_group" value="1">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Group Name *</label>
                    <input type="text" name="group_name" required placeholder="e.g., CS50 Study Group">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" placeholder="What will your group study? What are the goals?"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeCreateModal()" class="cta-button secondary small">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="cta-button primary small">
                        <i class="fas fa-check"></i> Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Match Modal -->
    <div id="quick-match-modal" class="quick-match-modal">
        <div class="modal-content">
            <div id="searching-state">
                <div class="spinner-container"><div class="spinner"></div></div>
                <h2 class="searching-text">Finding your study partner...</h2>
                <p class="searching-description">We're matching you with someone who shares your interests</p>
                <div id="queue-info" class="queue-info" style="display: none;"><i class="fas fa-users"></i> <span id="queue-count">0</span> users in queue</div>
                <button id="cancel-search" class="modal-button danger"><i class="fas fa-times"></i> Cancel Search</button>
            </div>
            <div id="match-found-state" class="match-found">
                <div class="match-found-icon"><i class="fas fa-check-circle"></i></div>
                <h2 class="match-found-text">Match Found! 🎉</h2>
                <p class="searching-description">You've been matched with a study partner!</p>
                <div id="matched-user-card" class="matched-user-card"></div>
                <div id="match-found-buttons" style="display: flex; gap: 1rem; justify-content: center;">
                    <button id="send-request-from-match" class="modal-button primary"><i class="fas fa-paper-plane"></i> Send Study Request</button>
                    <button id="view-profile-from-match" class="modal-button secondary"><i class="fas fa-user"></i> View Profile</button>
                </div>
                <button id="close-match-modal" class="modal-button danger" style="margin-top: 1rem;">Close</button>
            </div>
        </div>
    </div>


    
    <script src="assets/js/responsive.js" defer></script>
    <script src="assets/js/study-groups.js" defer></script>
</body>
</html>