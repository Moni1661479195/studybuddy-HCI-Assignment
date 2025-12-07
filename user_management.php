<?php
require_once 'session.php';

// Admin-only page
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit();
}

require_once 'lib/db.php';

// Fetch all users except the current admin
try {
    $db = get_db();
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, gender, created_at FROM users WHERE id != :admin_id ORDER BY created_at DESC");
    $stmt->bindParam(':admin_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Study Buddy</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .user-management-container {
            max-width: 1200px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto; 
            margin-right: auto; 
            padding: 2rem; 
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .user-table thead {
            background-color: #f8fafc;
        }
        .user-table th {
            font-weight: 600;
            color: #4a5568;
        }
        .user-table tbody tr:hover {
            background-color: #f7fafc;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            margin-right: 5px;
            display: inline-block;
        }
        .edit-btn { background-color: #4299e1; }
        .delete-btn { background-color: #e53e3e; }

        .success-message {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        nav svg {
            width: 2.25rem !important;
            height: 2.25rem !important;
        }
        footer svg {
            width: 1.75rem !important;
            height: 1.75rem !important;
            max-width: 100%;
        }
        
        nav {
            z-index: 50 !important;
        }
    </style>
</head>
<body class="bg-gray-100"> 
    
    <?php include 'header.php'; ?>

    <div class="user-management-container" style="margin-top: 180px; margin-bottom: 50px;">
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">User Management</h1>
        <p class="text-gray-500 mb-6">Manage all registered users in the system.</p>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div style="overflow-x:auto;">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['gender']); ?></td>
                                <td><?php echo date("Y-m-d", strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="user_profile.php?id=<?php echo $user['id']; ?>" class="action-btn edit-btn">View/Edit</a>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    </body>
</html>