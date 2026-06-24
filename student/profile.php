<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Load current user data
$user = fetchOne($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = post('full_name');
    $course     = post('course');
    $year_level = post('year_level');
    $bio        = post('bio');
    $newPass    = post('new_password');
    $confirmPass = post('confirm_password');

    // Validate
    if (!isNotEmpty($full_name)) $errors['full_name'] = 'Full name is required.';
    if ($newPass !== '' && !isValidPassword($newPass))
        $errors['new_password'] = 'Password must be at least 8 chars with letters and numbers.';
    if ($newPass !== $confirmPass)
        $errors['confirm_password'] = 'Passwords do not match.';

    // Handle profile photo upload
    $photoPath = $user['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $result = handleUpload($_FILES['profile_photo'], 'profiles', ALLOWED_IMAGE_TYPES);
        if ($result['success']) {
            // Delete old photo if exists
            if ($photoPath && file_exists(UPLOAD_DIR . $photoPath)) {
                unlink(UPLOAD_DIR . $photoPath);
            }
            $photoPath = $result['path'];
        } else {
            $errors['profile_photo'] = $result['error'];
        }
    }

    if (empty($errors)) {
        $year = is_numeric($year_level) ? (int)$year_level : null;

        if ($newPass !== '') {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            execute($conn,
                'UPDATE users SET full_name=?, course=?, year_level=?, bio=?, profile_photo=?, password=? WHERE id=?',
                'ssisssi',
                [$full_name, $course, $year, $bio, $photoPath, $hashed, $userId]);
        } else {
            execute($conn,
                'UPDATE users SET full_name=?, course=?, year_level=?, bio=?, profile_photo=? WHERE id=?',
                'sisssi',
                [$full_name, $course, $year, $bio, $photoPath, $userId]);
        }

        // Refresh session name
        $_SESSION['full_name'] = $full_name;
        // Reload user data
        $user = fetchOne($conn, 'SELECT * FROM users WHERE id = ?', 'i', [$userId]);
        setFlash('Profile updated successfully!', 'success');
        $success = true;
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>👤 My Profile</h1><p>Manage your account information.</p></div>
</div>

<div class="profile-layout">

    <!-- Left: Avatar card -->
    <div class="card profile-avatar-card">
        <div class="card-body">
            <?php if ($user['profile_photo']): ?>
                <img src="<?= SITE_URL ?>/uploads/<?= e($user['profile_photo']) ?>"
                     alt="Profile Photo" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder">
                    <?= strtoupper(mb_substr($user['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <p class="profile-name"><?= e($user['full_name']) ?></p>
            <p class="profile-role">🎓 Student</p>
            <hr class="divider">
            <ul class="profile-detail-list">
                <li>🆔 <?= e($user['student_id'] ?? 'N/A') ?></li>
                <li>✉️ <?= e($user['email']) ?></li>
                <li>📚 <?= e($user['course'] ?? 'N/A') ?></li>
                <li>📅 Year <?= $user['year_level'] ?? 'N/A' ?></li>
                <li>🗓️ Joined <?= formatDate($user['created_at']) ?></li>
            </ul>
        </div>
    </div>

    <!-- Right: Edit form -->
    <div class="card">
        <div class="card-header"><h2>Edit Profile</h2></div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data" data-validate>

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?= e($user['full_name']) ?>"
                               class="<?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" required>
                        <?php if (!empty($errors['full_name'])): ?><span class="form-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="">-- Select --</option>
                            <?php for ($y = 1; $y <= 5; $y++): ?>
                                <option value="<?= $y ?>" <?= ($user['year_level'] == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="course">Course / Program</label>
                    <input type="text" id="course" name="course" value="<?= e($user['course'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us something about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="profile_photo">Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           data-preview="photo-preview"
                           class="<?= isset($errors['profile_photo']) ? 'is-invalid' : '' ?>">
                    <span class="form-hint">JPG, PNG, WebP — max 10 MB</span>
                    <?php if (!empty($errors['profile_photo'])): ?><span class="form-error"><?= e($errors['profile_photo']) ?></span><?php endif; ?>
                    <div id="photo-preview" class="mt-1"></div>
                </div>

                <hr class="divider">
                <h3 style="font-size:16px;margin-bottom:16px;">Change Password <span class="text-muted" style="font-size:13px;font-weight:400;">(leave blank to keep current)</span></h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                               placeholder="Min. 8 characters"
                               class="<?= isset($errors['new_password']) ? 'is-invalid' : '' ?>">
                        <?php if (!empty($errors['new_password'])): ?><span class="form-error"><?= e($errors['new_password']) ?></span><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Repeat new password"
                               class="<?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>">
                        <?php if (!empty($errors['confirm_password'])): ?><span class="form-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?= SITE_URL ?>/student/dashboard.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
