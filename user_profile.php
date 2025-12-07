<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($profile_user_id <= 0) {
    // If no ID is specified, redirect to the current user's profile
    header("Location: user_profile.php?id=" . $_SESSION['user_id']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$db = get_db();

// Fetch profile user details
$stmt = $db->prepare("SELECT id, first_name, last_name, email, skill_level, created_at, is_online, show_online_status, last_seen, gender, bio, profile_picture_path, date_of_birth, country, major FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    header("Location: study-groups.php");
    exit();
}

// Calculate age
$age = 'Not specified';
if (!empty($profile_user['date_of_birth'])) {
    try {
        $dob = new DateTime($profile_user['date_of_birth']);
        $today = new DateTime('today');
        $age = $today->diff($dob)->y;
    } catch (Exception $e) {
        $age = 'Not specified';
    }
}

// Check relationship status
$is_study_mate = false;
$user1 = min($current_user_id, $profile_user_id);
$user2 = max($current_user_id, $profile_user_id);
$stmt_partner = $db->prepare("SELECT id FROM study_partners WHERE user1_id = ? AND user2_id = ? AND is_active = 1");
$stmt_partner->execute([$user1, $user2]);
if ($stmt_partner->fetch()) {
    $is_study_mate = true;
}

$existing_request = null;
if (!$is_study_mate) {
    $stmt_request = $db->prepare("SELECT * FROM study_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'pending' LIMIT 1");
    $stmt_request->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
    $existing_request = $stmt_request->fetch(PDO::FETCH_ASSOC);
}

// Find or create a direct chat room
$private_chat_room_id = null;
if ($current_user_id != $profile_user_id) {
    $stmt_find_room = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'direct' AND partner1_id = ? AND partner2_id = ?");
    $stmt_find_room->execute([$user1, $user2]);
    if ($room = $stmt_find_room->fetch(PDO::FETCH_ASSOC)) {
        $private_chat_room_id = $room['id'];
    } else {
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
    <title><?php echo htmlspecialchars($profile_user['first_name']); ?>'s Profile - Study Buddy</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/profile.css">

    <style>
        /* 借用 dashboard 的灰色背景 */
        body {
            background-color: #ffffff; /* Changed to white */
        }
        .main-container {
             /* 确保它在灰色背景上 */
            width: 100%;
            max-width: 1000px; /* 卡片最大宽度 */
            margin: 0 auto; /* 水平居中 */
            padding: 2.5rem; /* 40px */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container mt-24 md:mt-28">
        <div class="profile-card">
            <?php if (isset($_GET['new_user']) && $_GET['new_user'] === 'true'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
                    <h3 class="font-bold text-lg">Welcome to Study Buddy! 🎉</h3>
                    <p class="mt-2">Your account has been created successfully. Take a moment to complete your profile to help others connect with you. You can add a profile picture, a bio, your major, and more in the settings.</p>
                    <a href="settings.php" class="inline-block bg-green-500 text-white font-bold py-2 px-4 rounded mt-3 hover:bg-green-600 transition-colors">Go to Settings</a>
                </div>
            <?php endif; ?>
            <form id="update-profile-form" action="update_user_profile.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $profile_user_id; ?>">
                <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                    <input type="hidden" name="origin" value="admin_management">
                <?php endif; ?>

                <?php if (isset($_GET['success'])):
                ?>
                    <div class="success-message" style="margin-bottom: 1.5rem;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])):
                ?>
                    <div class="error-message" style="margin-bottom: 1.5rem;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <?php if (!empty($profile_user['profile_picture_path'])) : ?>
                        <img src="<?php echo htmlspecialchars($profile_user['profile_picture_path']); ?>" alt="Profile Picture" class="profile-avatar-img">
                    <?php else : ?>
                        <div class="profile-avatar"><?php echo strtoupper(substr($profile_user['first_name'], 0, 1) . substr($profile_user['last_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="profile-header-info">
                        <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                            <div class="admin-edit-group">
                                <input type="text" name="first_name" class="admin-edit-input" value="<?php echo htmlspecialchars($profile_user['first_name']); ?>">
                                <input type="text" name="last_name" class="admin-edit-input" value="<?php echo htmlspecialchars($profile_user['last_name']); ?>">
                            </div>
                        <?php else: ?>
                            <h1 class="profile-name">
                                <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>
                                <?php $is_visibly_online = $profile_user['is_online'] && $profile_user['show_online_status']; ?>
                                <span class="online-status <?php echo $is_visibly_online ? 'online' : 'offline'; ?>" title="<?php echo $is_visibly_online ? 'Online' : 'Offline'; ?>"></span>
                            </h1>
                        <?php endif; ?>
                        <p class="profile-email"><?php echo htmlspecialchars($profile_user['email']); ?></p>
                    </div>
                </div>

                <div class="profile-section profile-bio">
                    <h2 class="section-title">About Me</h2>
                    <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                        <textarea name="bio" class="admin-edit-textarea"><?php echo htmlspecialchars($profile_user['bio']); ?></textarea>
                    <?php else: ?>
                        <p><?php echo nl2br(htmlspecialchars($profile_user['bio'] ?? 'No bio provided.')); ?></p>
                    <?php endif; ?>
                </div>

                <div class="profile-section">
                    <h2 class="section-title">Details</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <i class="fas fa-graduation-cap detail-icon"></i><span class="detail-label">Major</span>
                            <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                                <input type="text" name="major" class="admin-edit-input detail-value" value="<?php echo htmlspecialchars($profile_user['major']); ?>">
                            <?php else: ?>
                                <span class="detail-value"><?php echo htmlspecialchars(!empty($profile_user['major']) ? $profile_user['major'] : 'N/A'); ?></span>
                            <?php endif; ?>
                        </div>
                         <div class="detail-item">
                            <i class="fas fa-chart-line detail-icon"></i><span class="detail-label">Skill Level</span>
                            <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                                <select name="skill_level" class="admin-edit-input detail-value">
                                    <option value="beginner" <?php echo ($profile_user['skill_level'] == 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo ($profile_user['skill_level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo ($profile_user['skill_level'] == 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            <?php else: ?>
                                <span class="detail-value"><?php echo htmlspecialchars(ucfirst($profile_user['skill_level'] ?? 'N/A')); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="detail-item"><i class="fas fa-globe detail-icon"></i><span class="detail-label">Country</span><span class="detail-value"><?php echo htmlspecialchars(!empty($profile_user['country']) ? $profile_user['country'] : 'N/A'); ?></span></div>
                        <div class="detail-item"><i class="fas fa-venus-mars detail-icon"></i><span class="detail-label">Gender</span><span class="detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $profile_user['gender'] ?? 'N/A'))); ?></span></div>
                        <div class="detail-item"><i class="fas fa-birthday-cake detail-icon"></i><span class="detail-label">Age</span><span class="detail-value"><?php echo htmlspecialchars($age); ?></span></div>
                        <div class="detail-item"><i class="fas fa-calendar-alt detail-icon"></i><span class="detail-label">Member Since</span><span class="detail-value"><?php echo date('M d, Y', strtotime($profile_user['created_at'])); ?></span></div>
                    </div>
                </div>

                </form>
                                <div id="action-buttons-section">
                                    <?php if ($is_admin && $current_user_id != $profile_user_id): ?>
                                        <p class="action-text">Admin Controls</p>
                                        <button type="submit" form="update-profile-form" class="cta-button primary"><i class="fas fa-save"></i> Save Changes</button>
                                        <a href="delete_user.php?id=<?php echo $profile_user_id; ?>" class="cta-button danger" onclick="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.');"><i class="fas fa-trash"></i> Delete User</a>
                                    <?php elseif ($current_user_id != $profile_user_id): ?>
                                        <?php if ($is_study_mate): ?>
                                            <p class="action-text">You are study mates with <?php echo htmlspecialchars($profile_user['first_name']); ?>.</p>
                                        <?php else: ?>
                                            <p class="action-text">You are not study mates yet.</p>
                                        <?php endif; ?>
                    
                                        <a href="report.php?reported_user_id=<?php echo $profile_user_id; ?>" class="cta-button danger" style="margin-left: 0.5rem;"><i class="fas fa-flag"></i> Report User</a>
                    
                                        <?php if ($is_study_mate): ?>
                                            <form id="remove-mate-form" method="POST" action="api/remove_study_mate.php" style="display: inline; margin-left: 0.5rem;">
                                                <input type="hidden" name="mate_id" value="<?php echo $profile_user_id; ?>">
                                                <button type="submit" class="cta-button danger"><i class="fas fa-user-minus"></i> Remove Mate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="study-groups.php" style="display: inline; margin-left: 0.5rem;">
                                                <input type="hidden" name="receiver_id" value="<?php echo $profile_user_id; ?>">
                                                <input type="hidden" name="action" value="send_study_request">
                                                <button type="submit" class="cta-button primary"><i class="fas fa-paper-plane"></i> Send Study Request</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="action-text">This is your public profile. Other users will see it this way.</p>
                                        <a href="settings.php" class="cta-button primary"><i class="fas fa-pencil-alt"></i> Edit Your Profile</a>
                                    <?php endif; ?>
                                </div>
                        </div>
                    </div>
                
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const removeMateForm = document.getElementById('remove-mate-form');
                        if (removeMateForm) {
                            removeMateForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                if (!confirm('Are you sure you want to remove this study mate? This action cannot be undone.')) {
                                    return;
                                }
                
                                const formData = new FormData(removeMateForm);
                                const button = removeMateForm.querySelector('button[type="submit"]');
                                button.disabled = true;
                                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                
                                fetch('api/remove_study_mate.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert(data.message);
                                        window.location.reload();
                                    } else {
                                        alert('Error: ' + data.message);
                                        button.disabled = false;
                                        button.innerHTML = '<i class="fas fa-user-minus"></i> Remove Mate';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An unexpected error occurred.');
                                    button.disabled = false;
                                    button.innerHTML = '<i class="fas fa-user-minus"></i> Remove Mate';
                                });
                            });
                        }
                    });
                    </script>
                
                </body>

    <?php include 'footer.php'; ?>


                </html>
                