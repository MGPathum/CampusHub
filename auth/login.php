<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect already logged-in users
if (!empty($_SESSION['user_id'])) {
    $dest = ($_SESSION['role'] === 'admin')
        ? SITE_URL . '/admin/dashboard.php'
        : SITE_URL . '/student/dashboard.php';
    redirect($dest);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = post('email');
    $password = post('password');

    // Validate fields
    if (!isNotEmpty($email))       $errors['email']    = 'Email is required.';
    elseif (!isValidEmail($email)) $errors['email']    = 'Enter a valid email address.';
    if (!isNotEmpty($password))    $errors['password'] = 'Password is required.';

    if (empty($errors)) {
        // Check credentials
        $user = fetchOne($conn, 'SELECT * FROM users WHERE email = ?', 's', [$email]);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session on login
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];

            setFlash('Welcome back, ' . $user['full_name'] . '!', 'success');

            $dest = ($user['role'] === 'admin')
                ? SITE_URL . '/admin/dashboard.php'
                : SITE_URL . '/student/dashboard.php';
            redirect($dest);
        } else {
            $errors['general'] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Welcome back 👋</h1>
        <p class="auth-sub">Log in to your <?= SITE_NAME ?> account.</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash-message flash-error" role="alert" style="margin-bottom:20px;border-radius:8px;">
                <div style="padding:0 4px;"><?= e($errors['general']) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate>
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= e(post('email')) ?>"
                       class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       placeholder="you@school.edu" required autocomplete="email">
                <?php if (!empty($errors['email'])): ?>
                    <span class="form-error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password"
                       class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       placeholder="••••••••" required autocomplete="current-password">
                <?php if (!empty($errors['password'])): ?>
                    <span class="form-error"><?= e($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg mt-1">Log In</button>
        </form>

        <p class="auth-footer">
            Don't have an account? <a href="<?= SITE_URL ?>/auth/register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
