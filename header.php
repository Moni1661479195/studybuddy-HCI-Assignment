<?php // Force cache refresh @ 1678886400
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        /* Add your global styles here */
        .notification-icon {
            position: relative;
            cursor: pointer;
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }

        .notification-dropdown {
            position: absolute;
            top: 60px;
            right: 20px;
            width: 300px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1001;
        }

        .notification-header {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .notification-item:hover {
            background-color: #f9f9f9;
        }

        .notification-item.unread {
            background-color: #f0f8ff;
        }
        /* Styles for messages.php (Conversation List) */
        .dashboard-container { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 2rem; text-align: left; }
        .dashboard-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); border-radius: 1.5rem; padding: 3rem; width: 100%; max-width: 900px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); margin-bottom: 2rem; }
        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .messages-header h1 {
            font-size: 1.75rem;
            color: #1f2937;
        }
        .btn-new-group {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-new-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        .conversations-list {
            background: #f8fafc;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-top: 1.5rem;
        }
        .conversation-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.2s;
        }
        .conversation-item:last-child {
            border-bottom: none;
        }
        .conversation-item:hover {
            background-color: #eef2f6;
        }
        .convo-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .convo-avatar.group {
            background: linear-gradient(45deg, #10b981, #059669);
        }
        .convo-avatar.direct {
            background: linear-gradient(45deg, #3b82f6, #2563eb);
        }
        .convo-details {
            flex-grow: 1;
            overflow: hidden;
        }
        .convo-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }
        .convo-last-message {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .convo-meta {
            text-align: right;
            flex-shrink: 0;
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .loader {
            text-align: center;
            padding: 2rem;
            font-size: 1.2rem;
            color: #6b7280;
        }
        /* New message badge style */
        .new-message-badge {
            background-color: #ef4444; /* Red color */
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.2em 0.6em;
            border-radius: 0.75rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            Study Buddy
        </a>

        <button id="nav-toggle" class="nav-toggle" type="button" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div id="nav-links" class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="cta-button primary <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="logout.php" class="cta-button primary">Logout</a>
            <?php else: ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
                <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
