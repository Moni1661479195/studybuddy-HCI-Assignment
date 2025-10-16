<?php
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_name = trim($_POST['group_name'] ?? '');
    $member_ids = $_POST['members'] ?? [];

    if (empty($group_name) || empty($member_ids)) {
        $_SESSION['error'] = "Group name and at least one member are required.";
    } else {
        // Add the current user to the list of members
        if (!in_array($current_user_id, $member_ids)) {
            $member_ids[] = $current_user_id;
        }

        try {
            $db->beginTransaction();

            // 1. Create the study group
            $stmt_group = $db->prepare("INSERT INTO study_groups (name, created_by, created_at) VALUES (?, ?, NOW())");
            $stmt_group->execute([$group_name, $current_user_id]);
            $group_id = $db->lastInsertId();

            // 2. Add members to the group
            $stmt_members = $db->prepare("INSERT INTO study_group_members (group_id, user_id) VALUES (?, ?)");
            foreach ($member_ids as $user_id) {
                $stmt_members->execute([$group_id, (int)$user_id]);
            }

            // 3. Create a chat room for the group
            $stmt_room = $db->prepare("INSERT INTO chat_rooms (room_type, group_id) VALUES ('group', ?)");
            $stmt_room->execute([$group_id]);
            $room_id = $db->lastInsertId();

            $db->commit();

            // Redirect to the new group chat
            header("Location: group-chat.php?room_id=" . $room_id);
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Group creation error: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while creating the group.";
        }
    }
}

// Fetch study mates to display in the form
$stmt_mates = $db->prepare(" 
    SELECT u.id, u.first_name, u.last_name, u.email
    FROM users u
    JOIN (
        SELECT DISTINCT
            CASE
                WHEN sender_id = :current_user_id THEN receiver_id
                ELSE sender_id
            END as mate_id
        FROM study_requests
        WHERE (sender_id = :current_user_id OR receiver_id = :current_user_id) AND status = 'accepted'
    ) as mates ON u.id = mates.mate_id
");
$stmt_mates->execute([':current_user_id' => $current_user_id]);
$study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Group</title>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        h1 {
            font-size: 1.75rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        input[type='text'] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .members-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        .member-item {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
        }
        .member-item:hover {
            background-color: #f9fafb;
        }
        .btn-submit {
            background-color: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #5a67d8;
        }
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create a New Study Group</h1>

        <?php if (isset($_SESSION['error'])):
            echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        endif; ?>

        <form action="create-group.php" method="POST">
            <div class="form-group">
                <label for="group_name">Group Name</label>
                <input type="text" id="group_name" name="group_name" required>
            </div>

            <div class="form-group">
                <label>Select Members</label>
                <div class="members-list">
                    <?php if (empty($study_mates)):
                        echo '<p>You don\'t have any study mates to add.</p>';
                    else:
                        foreach ($study_mates as $mate):
                            echo '<label class="member-item">';
                            echo '<input type="checkbox" name="members[]" value="' . $mate['id'] . '">';
                            echo htmlspecialchars($mate['first_name'].' '.$mate['last_name']);
                            echo '</label>';
                        endforeach;
                    endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create Group</button>
        </form>
    </div>
</body>
</html>
