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

// load current profile
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$profile) {
        throw new Exception("User profile not found for user ID: $uid");
    }
} catch (Exception $e) {
    error_log("Profile loading error: " . $e->getMessage());
    $profile = []; 
    $errors[] = "Could not load your profile data. Please try again later.";
}

$current_gender = $profile['gender'] ?? 'prefer_not_to_say';
$current_bio = $profile['bio'] ?? '';
$current_dob = $profile['date_of_birth'] ?? '';
$current_country = $profile['country'] ?? '';
$current_major = $profile['major'] ?? '';
$current_show_online_status = $profile['show_online_status'] ?? '1';
$current_avatar = $profile['profile_picture_path'] ?? null;
$initials = '';
if (empty($current_avatar) && !empty($profile)) {
    $initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
}


// Handle POST (update profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($profile)) {
        $errors[] = 'Cannot update profile because it could not be loaded.';
    } else {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($csrf, $token)) {
            $errors[] = 'Invalid request.';
        } else {
            // Handle file upload first
            $new_avatar_path = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $upload_dir = __DIR__ . '/uploads/avatars/';
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $errors[] = 'Failed to create avatar directory.';
                        }
                    }
                    
                    if (empty($errors)) {
                        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $file_name = $uid . '_' . time() . '.' . $file_ext;
                        $target_path = $upload_dir . $file_name;

                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            $new_avatar_path = 'uploads/avatars/' . $file_name;

                            // Delete old avatar if it exists
                            $old_avatar = $profile['profile_picture_path'] ?? null;
                            if ($old_avatar && file_exists(__DIR__ . '/' . $old_avatar)) {
                                unlink(__DIR__ . '/' . $old_avatar);
                            }
                        } else {
                            $errors[] = 'Failed to save uploaded file.';
                        }
                    }
                } else {
                    $errors[] = 'Invalid file type or size (Max 2MB for JPG, PNG, GIF).';
                }
            }

            // Retrieve text form data
            // ** We use the 'name' attributes from the new HTML form **
            $skill = trim($_POST['skill_level'] ?? '');
            $tz = trim($_POST['timezone'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $dob = trim($_POST['date_of_birth'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $major = trim($_POST['major'] ?? '');
            $show_online_status = trim($_POST['show_online_status'] ?? '1');

            // Validation
            if ($skill === '') $errors[] = 'Skill level is required.';
            if ($tz === '') $errors[] = 'Timezone is required.';
            
            $allowed_genders = ['male', 'female', 'other', 'prefer_not_to_say']; // Match form values
            if (!in_array($gender, $allowed_genders)) {
                $errors[] = 'Invalid gender selected.';
            }
            
            $allowed_visibility = ['Visible', 'Hidden']; // Match form values
            if (!in_array($show_online_status, $allowed_visibility)) {
                $errors[] = 'Invalid online status visibility setting.';
            }
            // Convert visibility to '1' or '0' for DB
            $show_online_status_db = ($show_online_status === 'Visible') ? '1' : '0';


            if (!empty($dob) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors[] = 'Date of birth must be in YYYY-MM-DD format.';
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();
                    
                    $params = [$skill, $tz, $gender, $bio, empty($dob) ? null : $dob, $country, $major, $show_online_status_db];
                    $sql_parts = [
                        "skill_level = ?", 
                        "timezone = ?", 
                        "gender = ?", 
                        "bio = ?", 
                        "date_of_birth = ?", 
                        "country = ?", 
                        "major = ?",
                        "show_online_status = ?"
                    ];

                    if ($new_avatar_path) {
                        $sql_parts[] = "profile_picture_path = ?";
                        $params[] = $new_avatar_path;
                    } elseif (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                        // Handle removal
                        $sql_parts[] = "profile_picture_path = NULL";
                        
                        // Delete old avatar file if it exists
                        $old_avatar = $profile['profile_picture_path'] ?? null;
                        if ($old_avatar && file_exists(__DIR__ . '/' . $old_avatar)) {
                            unlink(__DIR__ . '/' . $old_avatar);
                        }
                    }

                    $params[] = $uid; // Add user ID for WHERE clause
                    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
                    
                    $u = $db->prepare($sql);
                    $u->execute($params);

                    $db->commit();
                    $success = true;
                    $_SESSION['success'] = 'Profile updated successfully.'; // Use session for redirect
                    
                    // Reload page to show new data and clear POST
                    header("Location: settings.php");
                    exit();

                } catch (Exception $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    error_log("Settings update error: " . $e->getMessage());
                    $errors[] = 'Failed to save profile.';
                }
            }
        }
    }
}

// After POST logic, refresh current values for the form
$current_skill = $profile['skill_level'] ?? '';
$current_tz = $profile['timezone'] ?? '';
$current_gender = $profile['gender'] ?? 'prefer_not_to_say';
$current_bio = $profile['bio'] ?? '';
$current_dob = $profile['date_of_birth'] ?? '';
$current_country = $profile['country'] ?? '';
$current_major = $profile['major'] ?? '';
$current_show_online_status = $profile['show_online_status'] ?? '1';
$current_avatar = $profile['profile_picture_path'] ?? null;
if (empty($current_avatar) && !empty($profile)) {
    $initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
}

// Use session messages
if (isset($_SESSION['success'])) {
    $success = true;
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}


// basic timezone list
$timezones = [
    'UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles',
    'Europe/London','Europe/Berlin','Asia/Kolkata','Asia/Shanghai','Asia/Tokyo', 'Asia/Kuala_Lumpur'
];
// Ensure current timezone is in the list
if (!empty($current_tz) && !in_array($current_tz, $timezones)) {
    $timezones[] = $current_tz;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tailwindcss.com"></script> <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Settings — Study Buddy</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    


    <style>
        #navbar {
            background-color: #1e40af !important; /* A color similar to Tailwind's bg-blue-800 */
            background-image: none !important;
            backdrop-filter: none !important;
        }

        /* ===== page / background ===== */
        html,
        body {
            height: 100%;
        }

        body {
    margin: 0;
    font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    min-height: 100vh; 
    display: flex; 
    flex-direction: column; 
    background: linear-gradient(145deg, #cbd5e1, #e2e8f0);
    position: relative;
    color: #0f172a;
}

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 12% 18%, rgba(99, 102, 241, 0.06), transparent 8%),
                radial-gradient(circle at 88% 82%, rgba(34, 211, 238, 0.04), transparent 8%);
            z-index: 0;
        }
        
        /* Container to center the card */
        .card-container {
    width: 100%;
    display: flex;
    justify-content: center;
    padding: 2rem; 
    z-index: 1;
    position: relative;
    flex-grow: 1; 
    align-items: center; 
}

        /* ===== main card ===== */
        .card {
            width: min(980px, 95%);
            max-width: 980px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.06);
            padding: 28px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
        }

        /* ===== left: avatar summary ===== */
        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .avatar-wrap {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.8);
            background: linear-gradient(145deg, #dbeafe, #e0e7ff);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.08);
            transition: transform .22s ease, box-shadow .22s ease;
            font-size: 3.5rem; /* For initials */
            font-weight: 700;
            color: #4f46e5;
        }
        .avatar-wrap:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(99, 102, 241, 0.12); }
        .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .avatar-actions { display: flex; flex-direction: column; gap: 8px; width: 100%; align-items: center; }

        .btn {
            appearance: none;
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            background: linear-gradient(90deg, #6366f1, #4f46e5);
            color: #fff;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.12);
            width: 100%; /* Make buttons full width */
            text-align: center;
            font-size: 0.95rem;
            transition: all .2s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2); }
        .btn:active { transform: translateY(0); }

        .btn.secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #0f172a;
            border: 1px solid rgba(2, 6, 23, 0.03);
            box-shadow: 0 6px 16px rgba(2, 6, 23, 0.03);
        }
        .btn.secondary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(2, 6, 23, 0.07); }
        
        #avatarInput { display: none; } /* hide real file input */

        /* ===== right: form area ===== */
        .form-area { display: flex; flex-direction: column; gap: 16px; }
        .card-title { font-size: 20px; font-weight: 700; color: #0b1220; }
        form.profile-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-weight: 600; color: #24303a; font-size: 0.95rem; }
        
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
            color: #0b1220;
            transition: box-shadow .18s, border-color .18s, transform .12s;
            font-family: 'Inter', sans-serif; /* Ensure font is inherited */
        }
        textarea { min-height: 84px; resize: vertical; }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.7);
            box-shadow: 0 8px 18px rgba(99, 102, 241, 0.08);
            transform: translateY(-1px);
        }
        
        /* Styles for success/error messages */
        .message { padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
        .error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }

        .group-divider { grid-column: 1 / -1; height: 1px; background: linear-gradient(90deg, rgba(2, 6, 23, 0.04), rgba(2, 6, 23, 0.02)); margin: 8px 0; border-radius: 1px; }
        .full { grid-column: 1 / -1; }
        .save-row { grid-column: 1 / -1; display: flex; justify-content: flex-end; }
        .save-row .btn { min-width: 160px; width: auto; /* override full width */ }

        /* small screens */
        @media (max-width:900px) {
            .card { grid-template-columns: 1fr; padding: 20px; }
            .avatar-wrap { width: 120px; height: 120px; font-size: 2.5rem; }
            .avatar-actions { flex-direction: row; }
            form.profile-form { grid-template-columns: 1fr; }
            .save-row { justify-content: center; }
            .save-row .btn { min-width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

<main class="card-container mt-24 md:mt-28" role="main" aria-label="User profile card">
           <div class="card">
            <section class="avatar-section" aria-label="Avatar module">
                <div class="avatar-wrap" id="avatarWrap" aria-hidden="false">
                    <?php if ($current_avatar): ?>
                        <img id="avatarImg" src="<?php echo htmlspecialchars($current_avatar); ?>" alt="Profile Avatar">
                    <?php else: ?>
                        <img id="avatarImg" src="https://via.placeholder.com/300x300.png?text=Avatar" alt="no image" style="display:none;">
                        <span><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                </div>

                <div class="avatar-actions">
                    <label for="avatarInput" class="btn" aria-label="Upload avatar">Choose File</label>
                    <button type="button" class="btn secondary" id="removeAvatar">Remove</button>
                </div>
                
                <div style="width: 100%; margin-top: 1rem;">
                    <?php if ($success): ?>
                        <div class="message success" style="text-align: center;"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="message error">
                            <strong>Oops!</strong><br>
                            <?php foreach ($errors as $err) echo htmlspecialchars($err) . "<br>"; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </section>

            <section class="form-area" aria-label="Profile settings">
                <div>
                    <div class="card-title" style="font-size: 24px; font-weight: 700; color: #0b1220;">Profile Settings</div>
                    <div style="color:#55606a;font-size:0.95rem;margin-top:6px">Manage your public information</div>
                </div>

                <form id="profileForm" class="profile-form" autocomplete="off" novalidate 
                      method="POST" action="settings.php" enctype="multipart/form-data">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">
                    <input id="avatarInput" name="profile_picture" type="file" accept="image/png, image/jpeg, image/gif" style="display:none;" />


                    <div class="form-group">
                        <label for="major">Major / Field of Study</label>
                        <input id="major" name="major" type="text" placeholder="e.g. Computer Science" value="<?php echo htmlspecialchars($current_major); ?>">
                    </div>

                    <div class="form-group">
                        <label for="skill">Skill Level</label>
                        <select id="skill" name="skill_level" aria-label="skill-level" required>
                            <option value="">Select level</option>
                            <option value="beginner" <?php if ($current_skill === 'beginner') echo 'selected'; ?>>Beginner</option>
                            <option value="intermediate" <?php if ($current_skill === 'intermediate') echo 'selected'; ?>>Intermediate</option>
                            <option value="advanced" <?php if ($current_skill === 'advanced') echo 'selected'; ?>>Advanced</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label for="bio">Short Bio</label>
                        <textarea id="bio" name="bio" placeholder="Write a short bio..."><?php echo htmlspecialchars($current_bio); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="prefer_not_to_say" <?php if (strtolower((string)$current_gender) === 'prefer_not_to_say') echo 'selected'; ?>>Prefer not to say</option>
                            <option value="male" <?php if (strtolower((string)$current_gender) === 'male') echo 'selected'; ?>>Male</option>
                            <option value="female" <?php if (strtolower((string)$current_gender) === 'female') echo 'selected'; ?>>Female</option>
                            <option value="other" <?php if (strtolower((string)$current_gender) === 'other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input id="dob" name="date_of_birth" type="date" value="<?php echo htmlspecialchars($current_dob); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="country">Country</label>
                        <input id="country" name="country" type="text" placeholder="e.g. United States" value="<?php echo htmlspecialchars($current_country); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone" required>
                            <option value="">Select timezone</option>
                            <?php foreach ($timezones as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php if ($current_tz === $t) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="group-divider full" role="separator" aria-hidden="true"></div>

                    <div class="form-group full">
                        <label for="visibility">Online Status Visibility</label>
                        <select id="visibility" name="show_online_status" required>
                            <option value="Visible" <?php if ($current_show_online_status == '1') echo 'selected'; ?>>Show my online status to others</option>
                            <option value="Hidden" <?php if ($current_show_online_status == '0') echo 'selected'; ?>>Hide my online status</option>
                        </select>
                    </div>

                    <div class="save-row">
                        <button class="btn" id="saveBtn" type="submit">Save Settings</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Avatar preview handling
        const avatarInput = document.getElementById('avatarInput');
        const avatarImg = document.getElementById('avatarImg');
        const avatarWrap = document.getElementById('avatarWrap');
        const avatarInitialsSpan = avatarWrap.querySelector('span'); // Get the span for initials
        const removeAvatarFlag = document.getElementById('removeAvatarFlag');

        avatarInput.addEventListener('change', function() {
            const f = this.files && this.files[0];
            if (!f) return;
            
            // Reset remove flag if user picks a new file
            if (removeAvatarFlag) removeAvatarFlag.value = '0';

            const reader = new FileReader();
            reader.onload = function(ev) {
                avatarImg.src = ev.target.result;
                avatarImg.style.display = 'block'; // Show image
                if (avatarInitialsSpan) {
                    avatarInitialsSpan.style.display = 'none'; // Hide initials
                }
            };
            reader.readAsDataURL(f);
        });

        // Remove avatar fallback
        document.getElementById('removeAvatar').addEventListener('click', function() {
            avatarImg.src = 'https://via.placeholder.com/300x300.png?text=Avatar'; // Set to placeholder
            avatarImg.style.display = 'none'; // Hide image
            if (avatarInitialsSpan) {
                avatarInitialsSpan.style.display = 'flex'; // Show initials again
            }
            avatarInput.value = ''; // Clear the file input
            
            // Set remove flag
            if (removeAvatarFlag) removeAvatarFlag.value = '1';
        });

        // Form submission feedback (optional, since PHP reloads)
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('saveBtn');
            btn.textContent = 'Saving...';
            btn.disabled = true;
            // The form will now submit to PHP
        });

        // Clear autofill on load (optional, but good for UX)
        window.addEventListener('load', function() {
            // We don't clear fields anymore because PHP is populating them.
            // But we can ensure selects are correctly set.
            const selects = document.querySelectorAll('#profileForm select');
            selects.forEach(s => {
                if (s.value === "") {
                    s.selectedIndex = 0;
                }
            });
        });
    </script>
</body>
</html>
