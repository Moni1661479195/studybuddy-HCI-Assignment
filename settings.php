<?php
// DEV: show errors (remove in production)
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/session.php';

// Ensure required libs exist
foreach (['/lib/db.php','/lib/matching.php'] as $f) {
    $path = __DIR__ . $f;
    if (!file_exists($path)) {
        http_response_code(500);
        echo "Missing required file: " . htmlspecialchars(basename($path));
        exit;
    }
}
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/matching.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $db = get_db();
} catch (Exception $e) {
    http_response_code(500);
    echo "Database connection failed. Please try again later.";
    exit;
}

$uid = (int) $_SESSION['user_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];
$errors = [];
$success = false;

// Handle POST (update profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $errors[] = 'Invalid request.';
    } else {
        $skill = trim($_POST['skill_level'] ?? '');
        $tz = trim($_POST['timezone'] ?? '');
        // $raw_interests = trim($_POST['interests'] ?? '');

        if ($skill === '') $errors[] = 'Skill level is required.';
        if ($tz === '') $errors[] = 'Timezone is required.';

        if (empty($errors)) {
            try {
                $db->beginTransaction();
                // update users table (assumes columns skill_level and timezone exist)
                $u = $db->prepare("UPDATE users SET skill_level = ?, timezone = ? WHERE id = ?");
                $u->execute([$skill, $tz, $uid]);

                /*
                // sync interests: comma separated list
                $names = [];
                if ($raw_interests !== '') {
                    // split on commas, semicolons or pipes
                    $parts = preg_split('/[,\;\|]+/', $raw_interests);
                    foreach ($parts as $p) {
                        $n = trim($p);
                        if ($n === '') continue;
                        $names[] = mb_strtolower($n, 'UTF-8');
                    }
                    $names = array_values(array_unique($names));
                }

                if (empty($names)) {
                    $del = $db->prepare("DELETE FROM user_interests WHERE user_id = ?");
                    $del->execute([$uid]);
                } else {
                    $insertInterest = $db->prepare("INSERT IGNORE INTO interests (name) VALUES (?)");
                    $selectInterest = $db->prepare("SELECT id FROM interests WHERE name = ?");
                    $replaceUI = $db->prepare("REPLACE INTO user_interests (user_id, interest_id, weight) VALUES (?, ?, ?)");
                    $keepIds = [];

                    foreach ($names as $n) {
                        $insertInterest->execute([$n]);
                        $selectInterest->execute([$n]);
                        $iid = (int)$selectInterest->fetchColumn();
                        if ($iid) {
                            $keepIds[] = $iid;
                            $replaceUI->execute([$uid, $iid, 1.0]);
                        }
                    }

                    if (!empty($keepIds)) {
                        $in = implode(',', array_fill(0, count($keepIds), '?'));
                        $params = array_merge([$uid], $keepIds);
                        $stmt = $db->prepare("DELETE FROM user_interests WHERE user_id = ? AND interest_id NOT IN ($in)");
                        $stmt->execute($params);
                    } else {
                        $del = $db->prepare("DELETE FROM user_interests WHERE user_id = ?");
                        $del->execute([$uid]);
                    }
                }
                */

                $db->commit();
                $success = true;
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Settings update error: " . $e->getMessage());
                $errors[] = 'Failed to save profile.';
            }
        }
    }
}


// load current profile via get_user_profile for consistent format
try {
    $profile = get_user_profile($db, $uid);
    $current_skill = $profile['skill'] ?? '';
    $current_tz = $profile['tz'] ?? '';
    // $current_interests = implode(', ', array_keys($profile['interests'] ?? []));
    $current_interests = ''; // Set to empty string
} catch (Exception $e) {
    error_log("Profile loading error: " . $e->getMessage());
    $current_skill = '';
    $current_tz = '';
    $current_interests = '';
}

// basic timezone list
$timezones = [
    'UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles',
    'Europe/London','Europe/Berlin','Asia/Kolkata','Asia/Shanghai','Asia/Tokyo'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Settings — Study Buddy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        /* Styles copied from login.php for exact visual parity */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #1f2937;
        }

        #navbar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo.active {
            background: linear-gradient(45deg, #ef4444, #dc2626) !important;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
        }

        /* CTA/button visuals from login.php */
        .nav-links .cta-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .nav-links .cta-button.primary {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
        }
        .nav-links .cta-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        .nav-links .active {
            background: linear-gradient(45deg, #ef4444, #dc2626) !important;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        /* Form / card styling from login.php */
        .form-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .form-header h1 { font-size: 2rem; font-weight: 700; color: #1f2937; text-align: center; margin-bottom: 0.5rem; }
        .form-header p { color: #6b7280; text-align: center; margin-top: 0.5rem; margin-bottom: 2.5rem; font-size: 0.95rem; line-height: 1.4; }

        .input-group { margin-bottom: 1.5rem; }
        .form-label, .input-label { display: block; color: #374151; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .input-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            background: white;
            transition: all 0.3s ease;
            padding: 0 1rem;
        }
        .input-wrapper:focus-within { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .input-icon { color: #9ca3af; font-size: 1rem; margin-right: 0.75rem; }
        .form-input, .input-field, .form-select { width: 100%; border: none; outline: none; padding: 1rem 0; font-size: 1rem; background: transparent; color: #1f2937; }
        .form-help { color: #6b7280; font-size: 0.8rem; margin-top: 0.25rem; }

        .form-button, #login-button {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .form-button:hover, #login-button:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }

        .message { padding: 0.75rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
        .error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }

        .footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .footer p { margin-bottom: 0.5rem; opacity: 0.8; }
        .footer-links a { color: white; text-decoration: none; margin: 0 0.75rem; opacity: 1; transition: opacity 0.3s ease; }
        .footer-links a:hover { opacity: 1; }

        @media (max-width: 480px) {
            #navbar { padding: 1rem; }
            .logo { font-size: 1.5rem; }
            .form-card { padding: 2rem; margin: 1rem; border-radius: 1rem; }
            .form-header h1 { font-size: 1.75rem; }
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

        <button id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links" id="nav-links" role="navigation" aria-hidden="true">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="cta-button primary <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="study-groups.php" class="cta-button primary <?php echo ($currentPage == 'study-groups.php') ? 'active' : ''; ?>">Study Groups</a>
                <a href="settings.php" class="cta-button primary <?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">Settings</a>
                <a href="logout.php" class="cta-button primary">Logout</a>
            <?php else: ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
                <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h1>Profile Settings</h1>
                <p>Adjust your preferences to find better study partners and get personalized recommendations.</p>
            </div>

            <?php if ($success): ?>
                <div class="message success">Profile updated successfully.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <?php foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="settings.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="form-field">
                    <label class="form-label" for="skill_level">Skill Level</label>
                    <div class="input-group select-group">
                        <span class="input-icon"><i class="fas fa-signal"></i></span>
                        <select id="skill_level" name="skill_level" required class="form-select">
                            <option value="">Select your skill level</option>
                            <option value="beginner" <?php if ($current_skill === 'beginner') echo 'selected'; ?>>Beginner</option>
                            <option value="intermediate" <?php if ($current_skill === 'intermediate') echo 'selected'; ?>>Intermediate</option>
                            <option value="advanced" <?php if ($current_skill === 'advanced') echo 'selected'; ?>>Advanced</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label" for="timezone">Timezone</label>
                    <div class="input-group select-group">
                        <span class="input-icon"><i class="fas fa-clock"></i></span>
                        <select id="timezone" name="timezone" required class="form-select">
                            <option value="">Select your timezone</option>
                            <?php foreach ($timezones as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php if ($current_tz === $t) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button class="form-button" type="submit">Save Settings</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Study Buddy. All rights reserved.</p>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="terms.php">Terms</a>
            <a href="privacy.php">Privacy</a>
        </div>
    </footer>

    <script src="assets/js/responsive.js" defer></script>
</body>
</html>