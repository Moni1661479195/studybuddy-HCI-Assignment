<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 获取要查看的个人资料的用户ID
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($profile_user_id <= 0) {
    header("Location: study-groups.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// 获取个人资料用户的详细信息
$stmt = $db->prepare("SELECT id, first_name, last_name, email, skill_level, created_at, is_online, last_seen FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    // 如果用户不存在，跳转回列表页
    header("Location: study-groups.php");
    exit();
}

// 【核心逻辑】
// 1. 判断是否已经是学伴 (只查 study_partners 表)
$is_study_mate = false;
$user1 = min($current_user_id, $profile_user_id);
$user2 = max($current_user_id, $profile_user_id);
$stmt_partner = $db->prepare("SELECT id FROM study_partners WHERE user1_id = ? AND user2_id = ? AND is_active = 1");
$stmt_partner->execute([$user1, $user2]);
if ($stmt_partner->fetch()) {
    $is_study_mate = true;
}

// 2. 如果不是学伴，再检查是否有待处理的学习请求
$existing_request = null;
if (!$is_study_mate) {
    $stmt_request = $db->prepare("
        SELECT * FROM study_requests 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND status = 'pending'
        LIMIT 1
    ");
    $stmt_request->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
    $existing_request = $stmt_request->fetch(PDO::FETCH_ASSOC);
}

// Fetch current user's groups for the invitation modal (safer 2-step query, PK fix)
$stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
$stmt_get_ids->execute([$current_user_id]);
$group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);

$user_groups = [];
if (!empty($group_ids)) {
    $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
    $groups_stmt = $db->prepare("
        SELECT group_id AS id, group_name AS name
        FROM study_groups
        WHERE group_id IN ($in_clause) AND is_active = 1
        ORDER BY group_name ASC
    ");
    $groups_stmt->execute($group_ids);
    $user_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Find or create a direct chat room for private messaging
$private_chat_room_id = null;
if ($current_user_id != $profile_user_id) {
    $user1 = min($current_user_id, $profile_user_id);
    $user2 = max($current_user_id, $profile_user_id);

    $stmt_find_room = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'direct' AND partner1_id = ? AND partner2_id = ?");
    $stmt_find_room->execute([$user1, $user2]);
    $existing_room = $stmt_find_room->fetch(PDO::FETCH_ASSOC);

    if ($existing_room) {
        $private_chat_room_id = $existing_room['id'];
    } else {
        // Create a new chat room if one doesn't exist
        $stmt_create_room = $db->prepare("INSERT INTO chat_rooms (room_type, partner1_id, partner2_id) VALUES ('direct', ?, ?)");
        $stmt_create_room->execute([$user1, $user2]);
        $private_chat_room_id = $db->lastInsertId();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - <?php echo htmlspecialchars($profile_user['first_name']); ?>'s Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* 你的所有 CSS 样式都保留在这里，无需改动 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; flex-direction: column; color: #333; }
        #navbar { background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 2rem; font-weight: 700; color: white; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-links a { color: white; text-decoration: none; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: background 0.3s ease; }
        .main-container { flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 2rem; }
        .profile-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); border-radius: 1.5rem; padding: 3rem; width: 100%; max-width: 600px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); text-align: center; }
        .profile-avatar { width: 120px; height: 120px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2.5rem; margin: 0 auto 1.5rem auto; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); }
        .profile-name { font-size: 2rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
        .profile-email { color: #6b7280; font-size: 1.1rem; margin-bottom: 2rem; }
        .profile-details { background: #f8fafc; border-radius: 0.75rem; padding: 1.5rem; margin: 2rem 0; text-align: left; border: 1px solid #e2e8f0;}
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.5rem; }
        .detail-value { color: #6b7280; }
        .cta-button { display: inline-block; padding: 1rem 2rem; border-radius: 0.75rem; font-size: 1rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: none; cursor: pointer; margin: 0.5rem; }
        .cta-button.primary { background: linear-gradient(45deg, #10b981, #059669); color: white; }
        .cta-button.secondary { background: linear-gradient(45deg, #3b82f6, #2563eb); color: white; }
        .cta-button.danger { background: linear-gradient(45deg, #ef4444, #dc2626); color: white; }
        .cta-button.small { padding: 0.5rem 1rem; font-size: 0.9rem; }
        .status-text { font-weight: 500; color: #f59e0b; }
        .online-status { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-left: 5px; }
        .online { background-color: #28a745; }
        .offline { background-color: #6b7280; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="profile-card">
            
            <div class="profile-avatar"><?php echo strtoupper(substr($profile_user['first_name'], 0, 1) . substr($profile_user['last_name'], 0, 1)); ?></div>
            
            <h1 class="profile-name">
                <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>
                <span class="online-status <?php echo $profile_user['is_online'] ? 'online' : 'offline'; ?>"></span>
            </h1>

            <p class="profile-email"><?php echo htmlspecialchars($profile_user['email']); ?></p>

            <div class="profile-details">
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-user"></i> User ID</span>
                    <span class="detail-value">#<?php echo $profile_user['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-chart-line"></i> Skill Level</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst($profile_user['skill_level'] ?? 'Not specified')); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar-alt"></i> Member Since</span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($profile_user['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-circle"></i> Status</span>
                    <span class="detail-value">
                        <?php if ($profile_user['is_online']): ?>
                            <span style="color: #10b981;">Online</span>
                        <?php else: ?>
                            <span style="color: #6b7280;">Offline</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div id="action-buttons-section" style="margin-top: 2rem;">
            <?php if ($current_user_id != $profile_user_id): // 确保不是在看自己的页面 ?>

                <?php if ($is_study_mate): ?>
                    <span style="display:block; margin-bottom: 0.5rem; color: #10b981;">You are currently study mates.</span>
                    <form method="POST" action="api/partner_actions.php" style="display: inline;">
                        <input type="hidden" name="partner_id" value="<?php echo $profile_user_id; ?>">
                        <input type="hidden" name="action" value="remove_mate">
                        <button type="submit" class="cta-button danger" onclick="return confirm('Are you sure you want to remove this study mate?');">
                            <i class="fas fa-user-minus"></i> Remove Study Mate
                        </button>
                    </form>
                    <button type="button" class="cta-button secondary" onclick="openInviteModal()">
                        <i class="fas fa-user-plus"></i> Invite to Group
                    </button>

                <?php elseif ($existing_request && $existing_request['sender_id'] == $current_user_id): ?>
                    <p class="status-text"><i class="fas fa-clock"></i> Study request sent. Waiting for response.</p>
                    <form method="POST" action="study-groups.php" style="display: inline; margin-top: 1rem;">
                        <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                        <input type="hidden" name="action" value="cancel_study_request">
                        <button type="submit" class="cta-button danger small">
                            <i class="fas fa-times"></i> Cancel Request
                        </button>
                    </form>

                <?php elseif ($existing_request && $existing_request['receiver_id'] == $current_user_id): ?>
                    <p>This user has sent you a study request.</p>
                    <div style="margin-top: 1rem;">
                        <form method="POST" action="study-groups.php" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                            <input type="hidden" name="action" value="accept_request">
                            <button type="submit" class="cta-button primary small">
                                <i class="fas fa-check"></i> Accept
                            </button>
                        </form>
                        <form method="POST" action="study-groups.php" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                            <input type="hidden" name="action" value="decline_request">
                            <button type="submit" class="cta-button danger small">
                                <i class="fas fa-times"></i> Decline
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <form method="POST" action="study-groups.php" style="display: inline;">
                        <input type="hidden" name="receiver_id" value="<?php echo $profile_user_id; ?>">
                        <input type="hidden" name="action" value="send_study_request">
                        <button type="submit" class="cta-button primary">
                            <i class="fas fa-paper-plane"></i> Send Study Request
                        </button>
                    </form>
                <?php endif; ?>

            <?php endif; ?>
            </div>

            <?php if ($private_chat_room_id): ?>
                <a href="chat.php?room_id=<?php echo $private_chat_room_id; ?>" class="cta-button secondary" style="background: linear-gradient(45deg, #3b82f6, #2563eb); margin-top: 1rem;">
                    <i class="fas fa-paper-plane"></i> Private Message Him
                </a>
            <?php endif; ?>

            <div style="margin-top: 2rem;">
                <a href="study-groups.php" class="cta-button secondary">
                    <i class="fas fa-arrow-left"></i> Back to Study Groups
                </a>
            </div>
        </div>
    </div>

    <!-- Invite to Group Modal -->
    <div id="inviteGroupModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div style="background: white; border-radius: 1.5rem; padding: 2rem; max-width: 500px; width: 90%;">
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">Invite <?php echo htmlspecialchars($profile_user['first_name']); ?> to a Group</h2>
            <form method="POST" action="api/invite_to_group.php">
                <input type="hidden" name="user_to_invite" value="<?php echo $profile_user_id; ?>">
                
                <div style="margin-bottom: 1rem;">
                    <label for="group_id_select" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Select a Group:</label>
                    <select id="group_id_select" name="group_id" required style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;">
                        <?php if (empty($user_groups)):
                        ?>
                            <option value="" disabled>You are not in any groups to invite someone to.</option>
                        <?php else:
                        ?>
                            <?php foreach ($user_groups as $group):
                            ?>
                                <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                    <button type="button" onclick="closeInviteModal()" class="cta-button danger small">Cancel</button>
                    <button type="submit" class="cta-button primary small" <?php if (empty($user_groups)) echo 'disabled'; ?>>Send Invitation</button>
                </div>
            </form>
        </div>
    </div>

</body>

<script>
    function openInviteModal() {
        document.getElementById('inviteGroupModal').style.display = 'flex';
    }

    function closeInviteModal() {
        document.getElementById('inviteGroupModal').style.display = 'none';
    }
</script>

</html>