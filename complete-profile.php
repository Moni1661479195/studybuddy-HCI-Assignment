<?php
require_once 'session.php';
require_once 'lib/db.php';

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db();
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Form Data Retrieval ---
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $skill_level = trim($_POST['skill_level'] ?? 'beginner');
    $country = trim($_POST['country'] ?? '');
    $date_of_birth = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $gender = trim($_POST['gender'] ?? 'prefer_not_to_say');
    $timezone = trim($_POST['timezone'] ?? 'UTC');
    $show_online_status = trim($_POST['show_online_status'] ?? '1');

    // --- Basic Validation ---
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and last name are required.";
    }
    
    // --- File Upload Handling ---
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
                $file_name = $user_id . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $new_avatar_path = 'uploads/avatars/' . $file_name;
                } else {
                    $errors[] = 'Failed to save uploaded file.';
                }
            }
        } else {
            $errors[] = 'Invalid file type or size (Max 2MB for JPG, PNG, GIF).';
        }
    }

    // --- Database Update ---
    if (empty($errors)) {
        $sql_parts = [
            "first_name = :first_name",
            "last_name = :last_name",
            "bio = :bio",
            "major = :major",
            "skill_level = :skill_level",
            "country = :country",
            "date_of_birth = :date_of_birth",
            "gender = :gender",
            "timezone = :timezone",
            "show_online_status = :show_online_status",
            "profile_setup_complete = 1"
        ];
        
        if ($new_avatar_path) {
            $sql_parts[] = "profile_picture_path = :profile_picture_path";
        }

        $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = :user_id";
        $stmt = $db->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->bindParam(':bio', $bio, PDO::PARAM_STR);
        $stmt->bindParam(':major', $major, PDO::PARAM_STR);
        $stmt->bindParam(':skill_level', $skill_level, PDO::PARAM_STR);
        $stmt->bindParam(':country', $country, PDO::PARAM_STR);
        $stmt->bindParam(':date_of_birth', $date_of_birth, PDO::PARAM_STR);
        $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
        $stmt->bindParam(':timezone', $timezone, PDO::PARAM_STR);
        $stmt->bindParam(':show_online_status', $show_online_status, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($new_avatar_path) {
            $stmt->bindParam(':profile_picture_path', $new_avatar_path, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            $_SESSION['first_name'] = $first_name;
            if ($new_avatar_path) {
                $_SESSION['user_avatar'] = $new_avatar_path;
            }
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}

// --- Fetch existing user data to pre-fill the form ---
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Timezone List ---
$timezones = ['UTC','America/New_York','America/Chicago','America/Denver','America/Los_Angeles','Europe/London','Europe/Berlin','Asia/Kolkata','Asia/Shanghai','Asia/Tokyo', 'Asia/Kuala_Lumpur'];
if (!empty($user['timezone']) && !in_array($user['timezone'], $timezones)) {
    $timezones[] = $user['timezone'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - Study Buddy</title>
    <link rel="stylesheet" href="assets/css/modern_auth.css?v=1.1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #eee;
            background-color: #f8f9fa;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-preview .initials {
            font-size: 2.5rem;
            font-weight: bold;
            color: #495057;
        }
        #profile_picture_input {
            display: none;
        }
    </style>
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Welcome, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>!</h1>
            <p class="auth-subtitle">Just one more step. Please complete your profile to continue.</p>

            <?php if (!empty($errors)): ?>
                <div class="error-message" style="display: block; text-align: left;">
                    <strong>Please fix the following errors:</strong><br>
                    <?php foreach ($errors as $error) echo '- ' . htmlspecialchars($error) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                
                <label for="profile_picture_input" class="avatar-preview" title="Click to change profile picture">
                    <?php if (!empty($user['profile_picture_path'])): ?>
                        <img id="avatar_img" src="<?php echo htmlspecialchars($user['profile_picture_path']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <img id="avatar_img" src="" alt="Profile Picture" style="display: none;">
                        <span id="avatar_initials"><?php echo strtoupper(substr($user['first_name'] ?? 'A', 0, 1) . substr($user['last_name'] ?? 'B', 0, 1)); ?></span>
                    <?php endif; ?>
                </label>
                <input type="file" id="profile_picture_input" name="profile_picture" accept="image/jpeg, image/png, image/gif">

                <div class="input-group">
                    <label class="input-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="input-field" placeholder="e.g., John" required value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label class="input-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="input-field" placeholder="e.g., Doe" required value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label class="input-label" for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="input-field" placeholder="Tell us a little about yourself..." rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="input-group">
                    <label class="input-label" for="major">Your Major</label>
                    <input type="text" id="major" name="major" class="input-field" placeholder="e.g., Computer Science" value="<?php echo htmlspecialchars($user['major'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label class="input-label" for="country">Country</label>
                    <input type="text" id="country" name="country" class="input-field" placeholder="e.g., United States" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label class="input-label" for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="input-field" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="input-group">
                    <label class="input-label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="input-field">
                        <option value="prefer_not_to_say" <?php echo (($user['gender'] ?? '') === 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                        <option value="male" <?php echo (($user['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (($user['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (($user['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label" for="skill_level">Skill Level</label>
                    <select id="skill_level" name="skill_level" class="input-field">
                        <option value="beginner" <?php echo (($user['skill_level'] ?? '') === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo (($user['skill_level'] ?? '') === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo (($user['skill_level'] ?? '') === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label" for="timezone">Timezone</label>
                    <select id="timezone" name="timezone" class="input-field">
                        <?php foreach ($timezones as $tz): ?>
                            <option value="<?php echo htmlspecialchars($tz); ?>" <?php echo (($user['timezone'] ?? '') === $tz) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tz); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label" for="show_online_status">Online Status Visibility</label>
                    <select id="show_online_status" name="show_online_status" class="input-field">
                        <option value="1" <?php echo (($user['show_online_status'] ?? '1') == '1') ? 'selected' : ''; ?>>Show my online status</option>
                        <option value="0" <?php echo (($user['show_online_status'] ?? '1') == '0') ? 'selected' : ''; ?>>Hide my online status</option>
                    </select>
                </div>

                <button type="submit" class="auth-button">Save and Continue</button>
            </form>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('profile_picture_input');
    const avatarImg = document.getElementById('avatar_img');
    const avatarInitials = document.getElementById('avatar_initials');

    avatarInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarImg.src = e.target.result;
                avatarImg.style.display = 'block';
                if (avatarInitials) {
                    avatarInitials.style.display = 'none';
                }
            }
            reader.readAsDataURL(file);
        }
    });
});
</script>
</body>
</html>