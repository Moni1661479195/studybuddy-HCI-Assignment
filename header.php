<?php
// 1. Session and Database requirements
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// --- TASK SYSTEM INTEGRATION ---
require_once __DIR__ . '/includes/TaskLogic.php';
// -------------------------------

$is_logged_in = isset($_SESSION['user_id']);
$current_user = null;
$initials = '';

if ($is_logged_in) {
    try {
        $db = get_db(); // Use the existing PDO connection
        $current_user_id = $_SESSION['user_id'];
        
        // --- TRIGGER DAILY & WEEKLY TASKS ---
        // updateTaskProgress now expects a PDO object
        updateTaskProgress($db, $current_user_id, 'daily_login', 1);
        updateTaskProgress($db, $current_user_id, 'weekly_login', 1);
        // ------------------------------------

        // 2. Fetch the latest user information from the database
        $stmt = $db->prepare("SELECT first_name, last_name, profile_picture_path FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. If there is no avatar, create initials
        if ($current_user && empty($current_user['profile_picture_path'])) {
            $first_initial = strtoupper(substr($current_user['first_name'], 0, 1));
            $last_initial = strtoupper(substr($current_user['last_name'], 0, 1));
            $initials = $first_initial . $last_initial;
        }
    } catch (PDOException $e) {
        // Handle db error gracefully
        error_log("Header DB Error: " . $e->getMessage());
        $current_user = ['first_name' => 'Error', 'last_name' => '', 'profile_picture_path' => null];
    }
}
?>

<link rel="stylesheet" href="assets/css/header.css?v=1.0.1">
<link rel="stylesheet" href="assets/css/notification.css?v=1.0.1">

<nav class="bg-blue-800 fixed w-full top-0 z-50 transition-all duration-300" id="navbar">
    <div class="max-w-7xl mx-auto px-6 sm:px-10 py-4 flex justify-between items-center">
      <a href="index.php" class="flex items-center space-x-2 text-white no-underline">
    <svg class="w-9 h-9" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="logoGradientNav" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#60A5FA" /> <stop offset="100%" stop-color="#E0E7FF" /> </linearGradient>
      </defs>
      <path d="M10 45 L10 5 L30 2 L50 5 L50 45 L30 48 Z" fill="url(#logoGradientNav)" />
      <path d="M10 5 L30 2 L30 48 L10 45 Z" fill="#1D4ED8" />
      <path d="M5 15 L25 5 L45 15 L25 25 Z" fill="#374151" />
      <path d="M20 32 Q 30 38, 40 32" stroke="#1D4ED8" stroke-width="2.5" fill="none" stroke-linecap="round" />
    </svg>
    <h1 class="text-lg sm:text-xl font-bold text-white">Study Buddy</h1>
</a>

      <div class="hidden md:flex items-center space-x-6">

        <!-- Focus Timer Widget -->
        <?php if ($is_logged_in): ?>
        <div id="focus-timer-container" class="flex items-center bg-blue-700 rounded-full px-3 py-1 border border-blue-500 shadow-inner hidden">
            <span id="focus-timer-display" class="text-white font-mono font-bold mr-2 text-sm">00:00</span>
            <button id="focus-timer-stop-btn" class="text-blue-300 hover:text-white transition focus:outline-none" title="Reset Timer">
                <i class="fas fa-redo-alt"></i>
            </button>
        </div>
        <button id="focus-timer-start-btn" class="hidden text-blue-100 hover:text-white transition focus:outline-none flex items-center gap-2 bg-blue-700 hover:bg-blue-600 px-3 py-1 rounded-full border border-blue-500 shadow-sm">
            <i class="fas fa-stopwatch"></i> <span class="text-sm font-semibold">Focus</span>
        </button>
        <?php endif; ?>

        <?php if ($is_logged_in && $current_user): ?>
            <div class="notification-container">
                <button id="notification-bell-btn" class="notification-bell-btn">
                    <i class="fas fa-bell"></i>
                    <span id="notification-count" class="notification-count hidden"></span>
                </button>
                <div id="notification-dropdown" class="notification-dropdown">
                    <div class="notification-header">Notifications</div>
                    <div id="notification-list" class="notification-list">
                        </div>
                    <div class="notification-footer">
                        <a href="#" id="clear-all-notifications">Clear all notifications</a>
                    </div>
                </div>
            </div>

            <div class="profile-dropdown-container">
                <button id="profile-toggle-btn" class="profile-toggle-btn">
                    <div class="profile-pic-container">
                        <?php if (!empty($current_user['profile_picture_path'])): ?>
                            <img src="<?php echo htmlspecialchars($current_user['profile_picture_path']); ?>" alt="Profile">
                        <?php else: ?>
                            <span><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="profile-name"><?php echo htmlspecialchars($current_user['first_name']); ?></span>
                    <i class="fas fa-chevron-down profile-chevron"></i>
                </button>
                
                <div id="profile-dropdown-menu" class="profile-dropdown-menu">
                    <a href="dashboard.php" class="dropdown-item">Dashboard</a>
                    <a href="settings.php" class="dropdown-item">Settings</a>
                    <a href="user_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="dropdown-item">Profile</a>
                    <a href="logout.php" class="dropdown-item logout">Logout</a>
                </div>
            </div>

        <?php else: ?>
            <a href="#" id="nav-signin-btn" class="text-blue-100 hover:text-white transition">Sign In</a>
            <button id="nav-signup-btn" class="bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition">Sign Up</button>
        <?php endif; ?>

      </div>

      <button id="menu-btn" class="block md:hidden text-white focus:outline-none">
        <i class="fas fa-bars"></i> </button>
    </div>

    <div id="mobile-menu" class="hidden md:hidden bg-blue-600 shadow-md">
      <div class="px-4 py-3 space-y-3">

        <?php if ($is_logged_in && $current_user): ?>
            <div class="px-2 py-2">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="profile-pic-container" style="width: 45px; height: 45px;">
                        <?php if (!empty($current_user['profile_picture_path'])): ?>
                            <img src="<?php echo htmlspecialchars($current_user['profile_picture_path']); ?>" alt="Profile">
                        <?php else: ?>
                            <span><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-white font-semibold"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                </div>
                <a href="dashboard.php" class="block text-white text-center p-2 rounded-lg hover:bg-blue-700">Dashboard</a>
                <a href="settings.php" class="block text-white text-center p-2 rounded-lg hover:bg-blue-700">Settings</a>
                <a href="user_profile.php?id=<?php echo $_SESSION['user_id']; ?>" class="block text-white text-center p-2 rounded-lg hover:bg-blue-700">Profile</a>
                <a href="logout.php" class="block bg-red-500 text-white text-center p-2 mt-2 rounded-lg hover:bg-red-600">Logout</a>
            </div>
        <?php else: ?>
            <a href="#" id="mobile-signin-btn" class="block text-blue-100 hover:text-white transition text-center">Sign In</a>
            <button id="mobile-signup-btn" class="w-full bg-white text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition">Sign Up</button>
        <?php endif; ?>

      </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const menuBtn = document.getElementById("menu-btn");
        const mobileMenu = document.getElementById("mobile-menu");
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener("click", () => {
                mobileMenu.classList.toggle("hidden");
            });
        }

        const profileToggleBtn = document.getElementById('profile-toggle-btn');
        const profileDropdownMenu = document.getElementById('profile-dropdown-menu');

        if (profileToggleBtn && profileDropdownMenu) {
            profileToggleBtn.addEventListener('click', function(event) {
                profileDropdownMenu.classList.toggle('show');
                profileToggleBtn.classList.toggle('active');
                event.stopPropagation();
            });

            profileDropdownMenu.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }

        document.addEventListener('click', function(event) {
            if (profileDropdownMenu && profileDropdownMenu.classList.contains('show')) {
                if (!profileToggleBtn.contains(event.target)) {
                    profileDropdownMenu.classList.remove('show');
                    profileToggleBtn.classList.remove('active');
                }
            }
        });

        // --- Notification Logic ---
        const notificationBellBtn = document.getElementById('notification-bell-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationList = document.getElementById('notification-list');
        const notificationCount = document.getElementById('notification-count');
        const clearAllNotificationsBtn = document.getElementById('clear-all-notifications');

        if (notificationBellBtn && notificationDropdown) {
            notificationBellBtn.addEventListener('click', function(event) {
                notificationDropdown.classList.toggle('show');
                event.stopPropagation();
            });

            document.addEventListener('click', function(event) {
                if (notificationDropdown.classList.contains('show') && !notificationBellBtn.contains(event.target) && !notificationDropdown.contains(event.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });
        }

        function fetchNotifications() {
            fetch('api/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    notificationList.innerHTML = '';
                    let unreadCount = 0;
                    if (data.length > 0) {
                        data.forEach(notification => {
                            const notificationItem = document.createElement('a');
                            notificationItem.href = notification.link;
                            notificationItem.classList.add('notification-item');
                            if (notification.is_read == 0) {
                                notificationItem.classList.add('unread');
                                unreadCount++;
                            }
                            notificationItem.dataset.id = notification.id;
                            notificationItem.innerHTML = `
                                <div class="notification-content">
                                    <p>${notification.message}</p>
                                    <span class="notification-time">${timeSince(new Date(notification.created_at))} ago</span>
                                </div>
                            `;
                            notificationItem.addEventListener('click', function(e) {
                                // Don't prevent default, just mark as read
                                markNotificationAsRead(notification.id);
                            });
                            notificationList.appendChild(notificationItem);
                        });
                    } else {
                        notificationList.innerHTML = '<div class="notification-item">No new notifications</div>';
                    }

                    if (unreadCount > 0) {
                        notificationCount.textContent = unreadCount;
                        notificationCount.classList.remove('hidden');
                    } else {
                        notificationCount.classList.add('hidden');
                    }
                });
        }

        function markNotificationAsRead(notificationId) {
            fetch('api/mark_notification_as_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: notificationId, action: 'mark_read' })
            }).then(() => {
                const item = notificationList.querySelector(`[data-id='${notificationId}']`);
                if (item) {
                    item.classList.remove('unread');
                }
                fetchNotifications();
            });
        }

        function clearAllNotifications() {
            if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
                return;
            }
            fetch('api/mark_notification_as_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: 'all', action: 'clear_all' })
            }).then(() => {
                fetchNotifications();
            });
        }

        if (clearAllNotificationsBtn) {
            clearAllNotificationsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearAllNotifications();
            });
        }
        
        function timeSince(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes";
            return Math.floor(seconds) + " seconds";
        }

        <?php if ($is_logged_in): ?>
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
        <?php endif; ?>

        // --- Focus Timer Logic ---
        <?php if ($is_logged_in): ?>
        const timerContainer = document.getElementById('focus-timer-container');
        const timerDisplay = document.getElementById('focus-timer-display');
        const startBtn = document.getElementById('focus-timer-start-btn');
        const stopBtn = document.getElementById('focus-timer-stop-btn');
        
        let timerInterval;
        const SESSION_KEY = 'study_session_start';
        const LAST_TICK_KEY = 'study_last_tick';
        const COMPLETED_KEY = 'study_session_completed'; // Tracks if 20m goal was hit for this session

        function updateTimerUI() {
            const startTime = localStorage.getItem(SESSION_KEY);
            if (startTime) {
                // Check if session is stale (from a different day or > 12 hours old)
                // This allows the timer to reset automatically for a new day's tasks
                const startDate = new Date(parseInt(startTime));
                const now = new Date();
                const isDifferentDay = startDate.getDate() !== now.getDate() || startDate.getMonth() !== now.getMonth();
                
                if (isDifferentDay) {
                    // Auto-restart for the new day
                    startTimer();
                    return;
                }

                const elapsed = Math.floor((now.getTime() - parseInt(startTime)) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (timerContainer) timerContainer.classList.remove('hidden');
                if (startBtn) startBtn.classList.add('hidden');
                
                // Logic checks
                checkMilestones(minutes, elapsed);
            } else {
                // Should not happen with auto-start, but fallback
                if (timerContainer) timerContainer.classList.add('hidden');
                if (startBtn) startBtn.classList.remove('hidden');
            }
        }

        function checkMilestones(minutes, elapsedSeconds) {
            // 1. Minute Tick (Weekly Task: Study Marathon)
            const lastTick = parseInt(localStorage.getItem(LAST_TICK_KEY) || '0');
            // Only tick if we crossed a new minute boundary since last save
            if (minutes > lastTick) {
                localStorage.setItem(LAST_TICK_KEY, minutes);
                sendProgressUpdate('minute_tick');
            }

            // 2. 20-Minute Goal (Daily Task: Focused Study)
            const alreadyCompleted = localStorage.getItem(COMPLETED_KEY);
            if (minutes >= 20 && !alreadyCompleted) {
                localStorage.setItem(COMPLETED_KEY, 'true');
                sendProgressUpdate('session_complete');
                // Optional: Visual flair
                if (timerDisplay) timerDisplay.classList.add('text-green-400');
                // Notify user gently
                // alert("🎉 Daily Goal Reached! 20 Minutes of Focus."); 
            }
        }

        function sendProgressUpdate(type) {
            fetch('api/track_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type })
            }).catch(err => console.error('Progress track error:', err));
        }

        function startTimer() {
            localStorage.setItem(SESSION_KEY, Date.now().toString());
            localStorage.setItem(LAST_TICK_KEY, '0');
            localStorage.removeItem(COMPLETED_KEY);
            
            if (timerDisplay) timerDisplay.classList.remove('text-green-400');
            
            // Clear existing interval if any to avoid duplicates
            if (timerInterval) clearInterval(timerInterval);
            
            updateTimerUI();
            timerInterval = setInterval(updateTimerUI, 1000);
        }

        function stopTimer() {
            if(confirm("Reset your active session timer?")) {
                startTimer(); // Simply restart it for "Always On" behavior
            }
        }

        if (startBtn) startBtn.addEventListener('click', startTimer);
        if (stopBtn) stopBtn.addEventListener('click', stopTimer);

        // Auto-Start Logic
        if (!localStorage.getItem(SESSION_KEY)) {
            startTimer();
        } else {
            timerInterval = setInterval(updateTimerUI, 1000);
            updateTimerUI(); // Immediate check
        }
        <?php endif; ?>
    });
</script>