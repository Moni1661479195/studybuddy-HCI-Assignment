<?php // Force cache refresh @ 1678886400
// group-details.php - View group details and invite members
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$group_id = (int)($_GET['id'] ?? 0);

if (!$group_id) {
    header("Location: my-groups.php");
    exit();
}

$db = get_db();

// Verify user is a member
$member_check = $db->prepare("SELECT role FROM study_group_members WHERE group_id = ? AND user_id = ?");
$member_check->execute([$group_id, $current_user_id]);
$membership = $member_check->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    $_SESSION['error'] = "You are not a member of this group.";
    header("Location: my-groups.php");
    exit();
}

$user_role = $membership['role'];

// Handle invite member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_user'])) {
    $invitee_id = (int)($_POST['invitee_id'] ?? 0);
    
    // FIX: Allow any group member to invite. The main check at the top of the file already ensures the current user is a member.
    if ($invitee_id) {
        try {
            // Check if already member
            $check_member = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
            $check_member->execute([$group_id, $invitee_id]);
            
            if ($check_member->fetch()) {
                $_SESSION['info'] = "User is already a member.";
            } else {
                // Check if invitation exists
                $check_inv = $db->prepare("SELECT 1 FROM study_group_invitations WHERE group_id = ? AND receiver_id = ? AND status = 'pending'");
                $check_inv->execute([$group_id, $invitee_id]);
                
                if ($check_inv->fetch()) {
                    $_SESSION['info'] = "Invitation already sent.";
                } else {
                    // Send invitation
                    $stmt = $db->prepare("
                        INSERT INTO study_group_invitations (group_id, sender_id, receiver_id, status, invited_at)
                        VALUES (?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([$group_id, $current_user_id, $invitee_id]);
                    $_SESSION['success'] = "Invitation sent successfully!";

                    // Create a notification for the receiver
                    $group_name = $group['group_name']; // $group is available from earlier in the script
                    $notification_message = "You have been invited to join the group: " . htmlspecialchars($group_name);
                    $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmt_notify->execute([$invitee_id, $notification_message]);
                }
            }
        } catch (Exception $e) {
            error_log("Invite error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to send invitation.";
        }
    }
    header("Location: group-details.php?id=" . $group_id);
    exit();
}

// Get group details
$group_stmt = $db->prepare("
    SELECT 
        sg.*,
        CONCAT(u.first_name, ' ', u.last_name) as creator_name,
        u.email as creator_email
    FROM study_groups sg
    JOIN users u ON sg.creator_id = u.id
    WHERE sg.group_id = ?
");
$group_stmt->execute([$group_id]);
$group = $group_stmt->fetch(PDO::FETCH_ASSOC);
$is_creator = ($group['creator_id'] == $current_user_id);

if (!$group) {
    header("Location: my-groups.php");
    exit();
}

// Get all members
$members_stmt = $db->prepare("
    SELECT 
        sgm.*,
        u.first_name,
        u.last_name,
        u.email,
        u.skill_level,
        u.is_online,
        u.profile_picture_path
    FROM study_group_members sgm
    JOIN users u ON sgm.user_id = u.id
    WHERE sgm.group_id = ?
    ORDER BY 
        CASE sgm.role 
            WHEN 'creator' THEN 1 
            WHEN 'admin' THEN 2 
            ELSE 3 
        END,
        sgm.joined_at ASC
");
$members_stmt->execute([$group_id]);
$members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get potential invitees (study partners not in group)
$invitees_stmt = $db->prepare("
    SELECT DISTINCT
        u.id,
        u.first_name,
        u.last_name,
        u.skill_level
    FROM users u
    WHERE u.id IN (
        SELECT CASE 
            WHEN sp.user1_id = ? THEN sp.user2_id
            ELSE sp.user1_id
        END
        FROM study_partners sp
        WHERE (sp.user1_id = ? OR sp.user2_id = ?)
        AND sp.is_active = 1
    )
    AND u.id NOT IN (
        SELECT user_id FROM study_group_members WHERE group_id = ?
    )
    AND u.id NOT IN (
        SELECT receiver_id FROM study_group_invitations 
        WHERE group_id = ? AND status = 'pending'
    )
");
$invitees_stmt->execute([$current_user_id, $current_user_id, $current_user_id, $group_id, $group_id]);
$potential_invitees = $invitees_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get chat room
$room_stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'group' AND group_id = ?");
$room_stmt->execute([$group_id]);
$chat_room = $room_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['group_name']); ?> - Study Buddy</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff; /* Changed to white */
            min-height: 100vh;
        }



        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background 0.3s;
            margin-left: 0.5rem;
        }

        .nav-links a:hover { background: rgba(255, 255, 255, 0.1); }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .group-header-content {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 2rem;
        }

        .group-title-section {
            flex: 1;
        }

        .group-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .group-meta-info {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
            color: #6b7280;
        }

        .group-description {
            color: #4b5563;
            line-height: 1.6;
            margin-top: 1rem;
        }

        .group-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        .btn-success {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .section-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
        }

        .member-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .member-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            transition: all 0.3s;
        }

        .member-item:hover {
            border-color: #667eea;
            transform: translateX(4px);
        }

        .member-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden; /* Ensure image stays within circle */
        }

        .member-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .online-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 14px;
            height: 14px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .member-role {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .role-creator { background: #fef3c7; color: #92400e; }
        .role-admin { background: #dbeafe; color: #1e40af; }
        .role-member { background: #f3f4f6; color: #4b5563; }

        .skill-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .skill-beginner { background: #dbeafe; color: #1e40af; }
        .skill-intermediate { background: #fef3c7; color: #92400e; }
        .skill-advanced { background: #d1fae5; color: #065f46; }
        .skill-mixed { background: #e9d5ff; color: #6b21a8; }

        .invite-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e5e7eb;
        }

        .invite-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .invite-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .modal-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .group-header-content {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-24 md:mt-28">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
            </div>
        <?php endif; ?>

        <!-- Group Header -->
        <div class="header-card">
            <div class="group-header-content">
                <div class="group-title-section">
                    <h1 class="group-title"><i class="fas fa-users"></i> <?php echo htmlspecialchars($group['group_name']); ?></h1>
                    <div class="group-meta-info">
                        <span><i class="fas fa-user"></i> Created by <?php echo htmlspecialchars($group['creator_name']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($group['created_at'])); ?></span>
                        <span><i class="fas fa-users"></i> <?php echo count($members); ?> members</span>
                    </div>
                    <?php if ($group['description']): ?>
                        <div class="group-description">
                            <?php echo nl2br(htmlspecialchars($group['description'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="group-actions">
                    <?php if ($is_creator): ?>
                        <button class="btn btn-success" onclick="openSettingsModal()">
                            <i class="fas fa-cog"></i> Settings
                        </button>
                    <?php else: // Add Leave button for non-creators ?>
                        <form method="POST" action="api/leave_group.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to leave this group?');">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Leave Group
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Members Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-users"></i> Members (<?php echo count($members); ?>)</h2>
                </div>
                <div class="member-list">
                    <?php foreach ($members as $member): 
                        $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
                        $role_class = 'role-' . $member['role'];
                    ?>
                        <div class="member-item">
                            <div class="member-avatar">
                            <?php if (!empty($member['profile_picture_path'])): ?>
                                <img src="<?php echo htmlspecialchars($member['profile_picture_path']); ?>" alt="<?php echo htmlspecialchars($member['first_name']); ?>'s avatar">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                            <?php if ($member['is_online']): ?>
                                <span class="online-indicator"></span>
                            <?php endif; ?>
                        </div>
                            <div class="member-info">
                                <div class="member-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                                <span class="member-role <?php echo $role_class; ?>">
                                    <?php 
                                    if ($member['role'] === 'creator') echo '👑 Creator';
                                    elseif ($member['role'] === 'admin') echo '⭐ Admin';
                                    else echo 'Member';
                                    ?>
                                </span>

                            </div>
                            <?php if ($member['user_id'] !== $current_user_id): ?>
                                <a href="user_profile.php?id=<?php echo $member['user_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Invite Members Section -->
            <?php if ($is_creator || $user_role === 'admin'): ?>
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-user-plus"></i> Invite Members</h2>
                </div>
                    <p style="color: #6b7280; margin-bottom: 1rem;">Search for a study partner to invite to this group.</p>
                    <div class="form-group">
                        <input type="text" id="invite-partner-search" placeholder="Search by name..." style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;">
                    </div>
                    <div id="invite-partner-suggestions" class="invite-list" style="margin-top: 1rem;">
                        <!-- Search results will be populated here by JavaScript -->
                    </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/responsive.js"></script>

    <!-- Group Settings Modal -->
    <div id="groupSettingsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 class="modal-header">Group Settings</h2>
            
            <!-- Edit Details Form -->
            <form method="POST" action="api/update_group_settings.php">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" name="group_name" value="<?php echo htmlspecialchars($group['group_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($group['description']); ?></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeSettingsModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Changes</button>
                </div>
            </form>

            <!-- Delete Group Section -->
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #ef4444;">Danger Zone</h3>
                <p style="color: #6b7280; margin: 0.5rem 0 1rem 0;">Deleting the group is permanent and cannot be undone.</p>
                <form method="POST" action="api/delete_group.php" onsubmit="return confirm('Are you sure you want to permanently delete this group and all its data?');">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <button type="submit" class="btn btn-danger">Delete This Group</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openSettingsModal() {
            document.getElementById('groupSettingsModal').style.display = 'flex';
        }

        function closeSettingsModal() {
            document.getElementById('groupSettingsModal').style.display = 'none';
        }

        // Close modal if clicking outside of the content
        document.getElementById('groupSettingsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSettingsModal();
            }
        });
    </script>

    <script>
    // Invite partner search functionality
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('invite-partner-search');
        const suggestionsContainer = document.getElementById('invite-partner-suggestions');
        const groupId = <?php echo $group_id; ?>;

        let debounceTimer;
        searchInput.addEventListener('keyup', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const searchTerm = searchInput.value.trim();

                if (searchTerm.length < 2) {
                    suggestionsContainer.innerHTML = '';
                    return;
                }

                fetch(`api/search_partners_for_invite.php?group_id=${groupId}&term=${searchTerm}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsContainer.innerHTML = ''; // Clear previous suggestions
                        if (data.error) {
                            suggestionsContainer.innerHTML = `<p style="color: #ef4444;">${data.error}</p>`;
                            return;
                        }
                        if (data.length === 0) {
                            suggestionsContainer.innerHTML = `<p style="color: #6b7280;">No matching partners found.</p>`;
                            return;
                        }

                        data.forEach(user => {
                            const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
                            const item = document.createElement('div');
                            item.className = 'invite-item';
                            item.innerHTML = `
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="member-avatar" style="width: 40px; height: 40px; font-size: 1rem;">${initials}</div>
                                    <div style="font-weight: 600; color: #1f2937;">
                                        ${user.first_name} ${user.last_name}
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="invite_user" value="1">
                                    <input type="hidden" name="invitee_id" value="${user.id}">
                                    <button type="submit" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                        <i class="fas fa-paper-plane"></i> Invite
                                    </button>
                                </form>
                            `;
                            suggestionsContainer.appendChild(item);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching invite suggestions:', error);
                        suggestionsContainer.innerHTML = `<p style="color: #ef4444;">Error loading results.</p>`;
                    });
            }, 300); // Debounce for 300ms
        });
    });
    </script>
</body>
</html>
<?php $db = null; ?>