<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$id = (int)get('id');
if (!$id) { redirect(SITE_URL . '/admin/students.php'); }

$student = fetchOne($conn, 'SELECT * FROM users WHERE id = ? AND role = "student"', 'i', [$id]);
if (!$student) {
    setFlash('Student not found.', 'error');
    redirect(SITE_URL . '/admin/students.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = post('full_name');
    $email      = post('email');
    $student_id = post('student_id');
    $course     = post('course');
    $year_level = post('year_level');
    $newPass    = post('new_password');

    if (!isNotEmpty($full_name))    $errors['full_name']  = 'Full name is required.';
    if (!isNotEmpty($email))        $errors['email']      = 'Email is required.';
    elseif (!isValidEmail($email))  $errors['email']      = 'Enter a valid email.';
    if ($newPass !== '' && !isValidPassword($newPass))
        $errors['new_password'] = 'Password must be at least 8 chars with letters and numbers.';

    // Check email uniqueness (excluding self)
    if (empty($errors['email'])) {
        $dup = fetchOne($conn, 'SELECT id FROM users WHERE email = ? AND id != ?', 'si', [$email, $id]);
        if ($dup) $errors['email'] = 'This email is already used by another account.';
    }

    if (empty($errors)) {
        $year = is_numeric($year_level) ? (int)$year_level : null;

        if ($newPass !== '') {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            execute($conn,
                'UPDATE users SET full_name=?, email=?, student_id=?, course=?, year_level=?, password=? WHERE id=?',
                'sssssii',
                [$full_name, $email, $student_id, $course, $year, $hashed, $id]);
        } else {
            execute($conn,
                'UPDATE users SET full_name=?, email=?, student_id=?, course=?, year_level=? WHERE id=?',
                'ssssi',
                [$full_name, $email, $student_id, $course, $year, $id]);
        }

        setFlash('Student record updated successfully.', 'success');
        redirect(SITE_URL . '/admin/students.php');
    }

    // Repopulate
    $student = array_merge($student, [
        'full_name' => $full_name, 'email' => $email, 'student_id' => $student_id,
        'course' => $course, 'year_level' => $year_level,
    ]);
}

$pageTitle = 'Edit Student';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>✏️ Edit Student</h1><p>Update information for <?= e($student['full_name']) ?>.</p></div>
    <a href="<?= SITE_URL ?>/admin/students.php" class="btn btn-outline">← Back to Students</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?= e($student['full_name']) ?>"
                           class="<?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" required>
                    <?php if (!empty($errors['full_name'])): ?><span class="form-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id"
                           value="<?= e($student['student_id'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= e($student['email']) ?>"
                       class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" required>
                <?php if (!empty($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course" value="<?= e($student['course'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="">-- Select --</option>
                        <?php for ($y = 1; $y <= 5; $y++): ?>
                            <option value="<?= $y ?>" <?= ($student['year_level'] == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <hr class="divider">
            <h3 style="font-size:15px;margin-bottom:14px;">Reset Password <span class="text-muted" style="font-weight:400;font-size:13px;">(leave blank to keep current)</span></h3>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password"
                       placeholder="Min. 8 characters"
                       class="<?= isset($errors['new_password']) ? 'is-invalid' : '' ?>">
                <?php if (!empty($errors['new_password'])): ?><span class="form-error"><?= e($errors['new_password']) ?></span><?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= SITE_URL ?>/admin/students.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
