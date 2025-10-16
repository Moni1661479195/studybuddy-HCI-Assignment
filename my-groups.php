<?php
// my-groups.php - Create and manage study groups
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// Handle group creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $name = trim($_POST['group_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $skill_level = $_POST['skill_level'] ?? 'mixed';
    $max_members = (int)($_POST['max_members'] ?? 10);
    
    if ($name) {
        try {
            $stmt = $db->prepare("
                INSERT INTO study_groups (name, description, creator_id, skill_level, max_members, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $description, $current_user_id, $skill_level, $max_members]);
            $_SESSION['success'] = "Study group created successfully!";
            header("Location: my-groups.php");
            exit();
        } catch (Exception $e) {
            error_log("Create group error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to create group.";
        }
    }
}

// Handle group invitation response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invitation_action'])) {
    $action = $_POST['invitation_action'];
    $invitation_id = (int)($_POST['invitation_id'] ?? 0);
    
    if ($invitation_id) {
        try {
            $new_status = ($action === 'accept') ? 'accepted' : 'declined';
            $stmt = $db->prepare("
                UPDATE study_group_invitations 
                SET status = ?, responded_at = NOW()
                WHERE id = ? AND receiver_id = ?
            ");
            $stmt->execute([$new_status, $invitation_id, $current_user_id]);
            
            if ($action === 'accept') {
                // Get group ID and add user as member
                $inv_stmt = $db->prepare("SELECT group_id FROM study_group_invitations WHERE id = ?");
                $inv_stmt->execute([$invitation_id]);
                $inv = $inv_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($inv) {
                    $member_stmt = $db->prepare("
                        INSERT INTO study_group_members (group_id, user_id, role, joined_at)
                        VALUES (?, ?, 'member', NOW())
                    ");
                    $member_stmt->execute([$inv['group_id'], $current_user_id]);
                }
                $_SESSION['success'] = "You joined the study group!";
            } else {
                $_SESSION['success'] = "Invitation declined.";
            }
        } catch (Exception $e) {
            error_log("Invitation response error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to process invitation.";
        }
    }
    header("Location: my-groups.php");
    exit();
}

// Get user's groups (ultra-safe N+1 query approach, PK fix)
$stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
$stmt_get_ids->execute([$current_user_id]);
$group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);

$my_groups = [];
if (!empty($group_ids)) {
    $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
    // Step 1: Get the groups and creator name
    $groups_stmt = $db->prepare("
        SELECT sg.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name
        FROM study_groups sg
        JOIN users u ON sg.creator_id = u.id
        WHERE sg.group_id IN ($in_clause) AND sg.is_active = 1
        ORDER BY sg.created_at DESC
    ");
    $groups_stmt->execute($group_ids);
    $my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 2: Get member count for each group
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM study_group_members WHERE group_id = ?");
    foreach ($my_groups as $i => $group) {
        $count_stmt->execute([$group['group_id']]);
        $my_groups[$i]['member_count'] = $count_stmt->fetchColumn();
    }
}

// Get pending invitations
$invitations_stmt = $db->prepare("
    SELECT 
        sgi.*,
        sg.name as group_name,
        sg.description as group_description,
        sg.skill_level,
        CONCAT(u.first_name, ' ', u.last_name) as sender_name,
        (SELECT COUNT(*) FROM study_group_members WHERE group_id = sg.id) as current_members,
        sg.max_members
    FROM study_group_invitations sgi
    JOIN study_groups sg ON sgi.group_id = sg.id
    JOIN users u ON sgi.sender_id = u.id
    WHERE sgi.receiver_id = ? AND sgi.status = 'pending'
    ORDER BY sgi.invited_at DESC
");
$invitations_stmt->execute([$current_user_id]);
$invitations = $invitations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Study Groups - Study Buddy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        #navbar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-create {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .section-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
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
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .badge-count {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .group-card {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
            position: relative;
        }

        .group-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .group-header {
            margin-bottom: 1rem;
        }

        .group-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .group-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .group-skill {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .skill-beginner { background: #dbeafe; color: #1e40af; }
        .skill-intermediate { background: #fef3c7; color: #92400e; }
        .skill-advanced { background: #d1fae5; color: #065f46; }
        .skill-mixed { background: #e9d5ff; color: #6b21a8; }

        .group-description {
            color: #4b5563;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .group-stats {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .group-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            flex: 1;
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-primary:hover { background: #2563eb; }

        .btn-success {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover { background: #059669; }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover { background: #dc2626; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Modal styles */
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        @media (max-width: 768px) {
            .groups-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            Study Buddy
        </a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="quick-match.php"><i class="fas fa-search"></i> Find Partners</a>
            <a href="study-groups.php"><i class="fas fa-user-friends"></i> Partners</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-users"></i> My Study Groups</h1>
            <p>Create and manage your collaborative learning groups</p>
        </div>

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

        <div class="action-bar">
            <h2 style="color: white; font-size: 1.5rem;">
                <i class="fas fa-layer-group"></i> Your Groups (<?php echo count($my_groups); ?>)
            </h2>
            <button onclick="openCreateModal()" class="btn-create">
                <i class="fas fa-plus"></i> Create New Group
            </button>
        </div>

        <!-- Pending Invitations -->
        <?php if (!empty($invitations)): ?>
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-envelope"></i> Group Invitations</h2>
                <span class="badge-count"><?php echo count($invitations); ?></span>
            </div>
            <div class="groups-grid">
                <?php foreach ($invitations as $inv): 
                    $skill_class = 'skill-' . $inv['skill_level'];
                ?>
                    <div class="group-card">
                        <div class="group-header">
                            <h3 class="group-name"><?php echo htmlspecialchars($inv['group_name']); ?></h3>
                            <div class="group-meta">
                                <span><i class="fas fa-user"></i> Invited by <?php echo htmlspecialchars($inv['sender_name']); ?></span>
                            </div>
                        </div>
                        <span class="group-skill <?php echo $skill_class; ?>">
                            <?php echo ucfirst($inv['skill_level']); ?>
                        </span>
                        <div class="group-description">
                            <?php echo htmlspecialchars($inv['group_description']); ?>
                        </div>
                        <div class="group-stats">
                            <span><i class="fas fa-users"></i> <?php echo $inv['current_members']; ?>/<?php echo $inv['max_members']; ?> members</span>
                        </div>
                        <div class="group-actions">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="invitation_action" value="accept">
                                <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                            </form>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="invitation_action" value="decline">
                                <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Groups -->
        <div class="section-card">
            <?php if (empty($my_groups)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No study groups yet</h3>
                    <p>Create your first group to start collaborating with others</p>
                    <button onclick="openCreateModal()" class="btn-create" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Create Study Group
                    </button>
                </div>
            <?php else: ?>
                <div class="groups-grid">
                    <?php foreach ($my_groups as $group): 
                        $skill_class = 'skill-' . $group['skill_level'];
                        $is_creator = ($group['creator_id'] === $current_user_id);
                        
                        // Get chat room for this group
                        $room_stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'group' AND group_id = ?");
                        $room_stmt->execute([$group['group_id']]);
                        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                        <div class="group-card">
                            <?php if ($is_creator): ?>
                                <div style="position: absolute; top: 1rem; right: 1rem;">
                                    <span style="background: linear-gradient(45deg, #f59e0b, #d97706); color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">
                                        <i class="fas fa-crown"></i> Creator
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="group-header">
                                <h3 class="group-name"><?php echo htmlspecialchars($group['group_name']); ?></h3>
                                <div class="group-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($group['creator_name']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($group['created_at'])); ?></span>
                                </div>
                            </div>
                            <span class="group-skill <?php echo $skill_class; ?>">
                                <?php echo ucfirst($group['skill_level']); ?>
                            </span>
                            <?php if ($group['description']): ?>
                                <div class="group-description">
                                    <?php echo htmlspecialchars($group['description']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="group-stats">
                                <span><i class="fas fa-users"></i> <?php echo $group['member_count']; ?> members</span>
                            </div>
                            <div class="group-actions">
                                <?php if ($room): ?>
                                    <a href="group-chat.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-comments"></i> Group Chat
                                    </a>
                                <?php endif; ?>
                                <a href="group-details.php?id=<?php echo $group['group_id']; ?>" class="btn btn-success">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header"><i class="fas fa-plus-circle"></i> Create Study Group</h2>
            <form method="POST" action="my-groups.php">
                <input type="hidden" name="create_group" value="1">
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Group Name *</label>
                    <input type="text" name="group_name" required placeholder="e.g., CS50 Study Group">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" placeholder="What will your group study? What are the goals?"></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-chart-line"></i> Skill Level</label>
                    <select name="skill_level">
                        <option value="mixed">Mixed (All Levels)</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-users"></i> Maximum Members</label>
                    <input type="number" name="max_members" value="10" min="3" max="50">
                    <small style="color: #6b7280;">Minimum 3 members required</small>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeCreateModal()" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/responsive.js"></script>
    <script>
        function openCreateModal() {
            document.getElementById('createGroupModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createGroupModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('createGroupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });
    </script>
</body>
</html>
<?php $db = null; ?>