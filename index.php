<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch quick stats for the hero section
$totalStudents = fetchOne($conn, "SELECT COUNT(*) AS cnt FROM users WHERE role='student'")['cnt'] ?? 0;
$totalEvents   = fetchOne($conn, "SELECT COUNT(*) AS cnt FROM events WHERE status='upcoming'")['cnt'] ?? 0;
$totalRegs     = fetchOne($conn, "SELECT COUNT(*) AS cnt FROM registrations")['cnt'] ?? 0;

// Upcoming events (next 3)
$upcomingEvents = fetchAll(
    $conn,
    "SELECT * FROM events WHERE status = 'upcoming' ORDER BY event_date ASC LIMIT 3"
);

// Latest 3 announcements from XML
$announcements = array_slice(getAnnouncements(), 0, 3);

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<section class="hero">
    <h1>Welcome to <?= SITE_NAME ?></h1>
    <p>Your one-stop campus hub for events, announcements, and community connections.</p>
    <div class="hero-actions">
        <?php if (empty($_SESSION['user_id'])): ?>
            <a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-white btn-lg">Get Started</a>
            <a href="<?= SITE_URL ?>/auth/login.php"    class="btn btn-ghost btn-lg">Log In</a>
        <?php elseif ($_SESSION['role'] === 'student'): ?>
            <a href="<?= SITE_URL ?>/student/events.php"    class="btn btn-white btn-lg">Browse Events</a>
            <a href="<?= SITE_URL ?>/student/dashboard.php" class="btn btn-ghost btn-lg">My Dashboard</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-white btn-lg">Admin Dashboard</a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/announcements.php" class="btn btn-ghost btn-lg">Announcements</a>
    </div>
</section>

<!-- ===== STATS ===== -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-number"><?= number_format($totalStudents) ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= number_format($totalEvents) ?></div>
        <div class="stat-label">Upcoming Events</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= number_format($totalRegs) ?></div>
        <div class="stat-label">Event Registrations</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count(getAnnouncements()) ?></div>
        <div class="stat-label">Active Announcements</div>
    </div>
</div>

<!-- ===== UPCOMING EVENTS ===== -->
<h2 class="section-title">📅 Upcoming Events</h2>

<?php if (!empty($upcomingEvents)): ?>
<div class="event-grid mb-4">
    <?php foreach ($upcomingEvents as $event): ?>
    <div class="card event-card">
        <?php if ($event['banner_image']): ?>
            <img src="<?= SITE_URL ?>/uploads/<?= e($event['banner_image']) ?>"
                 alt="<?= e($event['title']) ?>" class="event-banner">
        <?php else: ?>
            <div class="event-banner-placeholder">🎓</div>
        <?php endif; ?>
        <div class="card-body">
            <span class="badge badge-primary mb-1"><?= e(ucfirst($event['status'])) ?></span>
            <h3><?= e($event['title']) ?></h3>
            <div class="event-meta">
                <span>📅 <?= formatDate($event['event_date']) ?></span>
                <?php if ($event['event_time']): ?>
                    <span>🕐 <?= formatTime($event['event_time']) ?></span>
                <?php endif; ?>
                <?php if ($event['location']): ?>
                    <span>📍 <?= e($event['location']) ?></span>
                <?php endif; ?>
                <?php if ($event['max_slots']): ?>
                    <span>👥 <?= $event['max_slots'] ?> slots</span>
                <?php endif; ?>
            </div>
            <p class="text-muted" style="font-size:14px;">
                <?= e(mb_substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '…' : '' ?>
            </p>
        </div>
        <div class="card-footer">
            <?php if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <a href="<?= SITE_URL ?>/student/events.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">View & Register</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline btn-sm">Login to Register</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center mb-4">
    <a href="<?= SITE_URL ?>/student/events.php" class="btn btn-outline">View All Events →</a>
</div>
<?php else: ?>
<div class="empty-state mb-4">
    <div class="empty-icon">📭</div>
    <h3>No upcoming events yet</h3>
    <p>Check back soon for new activities.</p>
</div>
<?php endif; ?>

<!-- ===== LATEST ANNOUNCEMENTS ===== -->
<h2 class="section-title">📢 Latest Announcements</h2>

<?php if (!empty($announcements)): ?>
<div class="announcement-list mb-4">
    <?php foreach ($announcements as $ann): ?>
    <div class="announcement-card type-<?= e($ann['type']) ?>">
        <h3><?= e($ann['title']) ?></h3>
        <p><?= e(mb_substr($ann['content'], 0, 160)) ?><?= strlen($ann['content']) > 160 ? '…' : '' ?></p>
        <div class="announcement-meta">
            <span>👤 <?= e($ann['author']) ?></span>
            <span>📅 <?= formatDate($ann['date']) ?></span>
            <a href="<?= SITE_URL ?>/announcements.php">Read more →</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center">
    <a href="<?= SITE_URL ?>/announcements.php" class="btn btn-outline">All Announcements →</a>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>No announcements right now</h3>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
