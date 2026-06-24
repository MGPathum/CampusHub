<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    redirect(SITE_URL . '/student/dashboard.php');
}

$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'full_name'  => post('full_name'),
        'email'      => post('email'),
        'student_id' => post('student_id'),
        'course'     => post('course'),
        'year_level' => post('year_level'),
        'password'   => post('password'),
        'password2'  => post('password_confirm'),
    ];

    // --- Server-side validation ---
    if (!isNotEmpty($input['full_name']))   $errors['full_name']  = 'Full name is required.';
    if (!isNotEmpty($input['email']))       $errors['email']      = 'Email is required.';
    elseif (!isValidEmail($input['email'])) $errors['email']      = 'Enter a valid email address.';
    if (!isNotEmpty($input['student_id']))  $errors['student_id'] = 'Student ID is required.';
    if (!isNotEmpty($input['course']))      $errors['course']     = 'Course is required.';
    if (!isNotEmpty($input['password']))    $errors['password']   = 'Password is required.';
    elseif (!isValidPassword($input['password'])) {
        $errors['password'] = 'Password must be at least 8 characters with letters and numbers.';
    }
    if ($input['password'] !== $input['password2']) {
        $errors['password2'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check email is not already registered
        $existing = fetchOne($conn, 'SELECT id FROM users WHERE email = ?', 's', [$input['email']]);
        if ($existing) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($input['password'], PASSWORD_BCRYPT);
        $year   = is_numeric($input['year_level']) ? (int)$input['year_level'] : null;

        $affected = execute(
            $conn,
            'INSERT INTO users (full_name, email, password, role, student_id, course, year_level)
             VALUES (?, ?, ?, "student", ?, ?, ?)',
            'sssssi',
            [$input['full_name'], $input['email'], $hashed, $input['student_id'], $input['course'], $year]
        );

        if ($affected > 0) {
            setFlash('Account created successfully! Please log in.', 'success');
            redirect(SITE_URL . '/auth/login.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Student Registration';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper" style="align-items:flex-start; padding-top:40px;">
    <div class="auth-card" style="max-width:540px;">
        <h1>Create your account</h1>
        <p class="auth-sub">Join <?= SITE_NAME ?> to register for events and stay updated.</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash-message flash-error" role="alert" style="margin-bottom:20px;border-radius:8px;">
                <div><?= e($errors['general']) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate>

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?= e($input['full_name'] ?? '') ?>"
                           class="<?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           placeholder="Juan dela Cruz" required>
                    <?php if (!empty($errors['full_name'])): ?><span class="form-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="student_id">Student ID <span class="required">*</span></label>
                    <input type="text" id="student_id" name="student_id"
                           value="<?= e($input['student_id'] ?? '') ?>"
                           class="<?= isset($errors['student_id']) ? 'is-invalid' : '' ?>"
                           placeholder="2024-00001" required>
                    <?php if (!empty($errors['student_id'])): ?><span class="form-error"><?= e($errors['student_id']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= e($input['email'] ?? '') ?>"
                       class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       placeholder="you@student.edu" required>
                <?php if (!empty($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course / Program <span class="required">*</span></label>
                    <input type="text" id="course" name="course"
                           value="<?= e($input['course'] ?? '') ?>"
                           class="<?= isset($errors['course']) ? 'is-invalid' : '' ?>"
                           placeholder="BS Computer Science" required>
                    <?php if (!empty($errors['course'])): ?><span class="form-error"><?= e($errors['course']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="">-- Select Year --</option>
                        <?php for ($y = 1; $y <= 5; $y++): ?>
                            <option value="<?= $y ?>" <?= (($input['year_level'] ?? '') == $y) ? 'selected' : '' ?>>
                                Year <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password"
                           class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Min. 8 chars" required autocomplete="new-password">
                    <?php if (!empty($errors['password'])): ?><span class="form-error"><?= e($errors['password']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           class="<?= isset($errors['password2']) ? 'is-invalid' : '' ?>"
                           placeholder="Repeat password" required autocomplete="new-password">
                    <?php if (!empty($errors['password2'])): ?><span class="form-error"><?= e($errors['password2']) ?></span><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">Create Account</button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="<?= SITE_URL ?>/auth/login.php">Log in here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
