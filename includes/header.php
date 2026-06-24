<?php
/**
 * CampusHub - Global Header & Navigation
 * Included at the top of every page via require_once.
 * Expects $conn and session to already be started (via config.php).
 */

// $pageTitle should be set by each page before including this file.
// Fallback to a generic title if not set.
$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME;

$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['role']      ?? null;
$userName    = $_SESSION['full_name'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/favicon.png">
</head>
<body>

<!-- ===== SITE HEADER ===== -->
<header class="site-header">
    <div class="container header-inner">
        <div class="logo">
            <a href="<?= SITE_URL ?>/index.php">
                <span class="logo-icon">🎓</span>
                <span class="logo-text"><?= SITE_NAME ?></span>
            </a>
        </div>

        <nav class="main-nav" aria-label="Main Navigation">
            <ul class="nav-list">
                <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= SITE_URL ?>/announcements.php">Announcements</a></li>

                <?php if ($isLoggedIn && $userRole === 'student'): ?>
                    <!-- Student Navigation -->
                    <li><a href="<?= SITE_URL ?>/student/events.php">Events</a></li>
                    <li><a href="<?= SITE_URL ?>/student/my_registrations.php">My Registrations</a></li>
                    <li><a href="<?= SITE_URL ?>/student/upload_media.php">Upload Media</a></li>
                    <li class="nav-divider">|</li>
                    <li class="nav-user">
                        <a href="<?= SITE_URL ?>/student/profile.php">👤 <?= htmlspecialchars($userName) ?></a>
                    </li>
                    <li><a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-outline-sm">Logout</a></li>

                <?php elseif ($isLoggedIn && $userRole === 'admin'): ?>
                    <!-- Admin Navigation -->
                    <li><a href="<?= SITE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="nav-dropdown">
                        <a href="#">Manage ▾</a>
                        <ul class="dropdown-menu">
                            <li><a href="<?= SITE_URL ?>/admin/students.php">Students</a></li>
                            <li><a href="<?= SITE_URL ?>/admin/events.php">Events</a></li>
                            <li><a href="<?= SITE_URL ?>/admin/registrations.php">Registrations</a></li>
                            <li><a href="<?= SITE_URL ?>/admin/announcements.php">Announcements</a></li>
                            <li><a href="<?= SITE_URL ?>/admin/media.php">Media Files</a></li>
                        </ul>
                    </li>
                    <li class="nav-divider">|</li>
                    <li class="nav-user">
                        <span>🛡️ <?= htmlspecialchars($userName) ?></span>
                    </li>
                    <li><a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-outline-sm">Logout</a></li>

                <?php else: ?>
                    <!-- Guest Navigation -->
                    <li><a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary-sm">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Mobile hamburger toggle (JS-driven) -->
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- ===== SESSION FLASH MESSAGES ===== -->
<?php if (!empty($_SESSION['flash_message'])): ?>
<div class="flash-message flash-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?>" role="alert">
    <div class="container">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
        <button class="flash-close" aria-label="Close">✕</button>
    </div>
</div>
<?php
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
endif;
?>

<!-- ===== MAIN CONTENT WRAPPER ===== -->
<main class="main-content">
    <div class="container">
