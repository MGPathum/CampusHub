<?php
require_once __DIR__ . '/../config/config.php';

// Destroy the session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect to login with a confirmation message
session_start();
$_SESSION['flash_message'] = 'You have been logged out successfully.';
$_SESSION['flash_type']    = 'info';

header('Location: ' . SITE_URL . '/auth/login.php');
exit;
