<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId = $_SESSION['user_id'];

// Student's registration count
$myRegs = fetchOne($conn,
    "SELECT COUNT(*) AS cnt FROM registrations WHERE student_id = ?", 'i', [$userId])['cnt'] ?? 0;

// Upcoming registrations
$upcoming = fetchAll($conn,
    "SELECT r.*, e.title, e.event_date, e.event_time, e.location
     FROM registrations r
     JOIN events e ON r.event_id = e.id
     WHERE r.student_id = ? AND e.status = 'upcoming'
     ORDER BY e.event_date ASC LIMIT 5",
    'i', [$userId]);

// Latest announcements
$announcements = array_slice(getAnnouncements(), 0, 3);

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Hello, <?= e($_SESSION['full_name']) ?> 👋</h1>
        <p>Here's what's happening on campus.</p>
    </div>
    <a href="<?= SITE_URL ?>/student/events.php" class="btn btn-primary">Browse Events</a>
</div>

<!-- Quick Links -->
<div class="dashboard-grid mb-4">
    <a href="<?= SITE_URL ?>/student/events.php" class="dash-card">
        <div class="dash-icon blue">📅</div>
        <div class="dash-info"><h3>Browse Events</h3><strong>Register</strong></div>
    </a>
    <a href="<?= SITE_URL ?>/student/my_registrations.php" class="dash-card">
        <div class="dash-icon green">✅</div>
        <div class="dash-info"><h3>My Registrations</h3><strong><?= $myRegs ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/student/profile.php" class="dash-card">
        <div class="dash-icon purple">👤</div>
        <div class="dash-info"><h3>My Profile</h3><strong>Edit</strong></div>
    </a>
    <a href="<?= SITE_URL ?>/student/upload_media.php" class="dash-card">
        <div class="dash-icon orange">📸</div>
        <div class="dash-info"><h3>Upload Media</h3><strong>Share</strong></div>
    </a>
    <a href="<?= SITE_URL ?>/announcements.php" class="dash-card">
        <div class="dash-icon red">📢</div>
        <div class="dash-info"><h3>Announcements</h3><strong>Read</strong></div>
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;" class="responsive-cols">

    <!-- Upcoming Events I'm Registered For -->
    <div class="card">
        <div class="card-header">
            <h3>📅 My Upcoming Events</h3>
            <a href="<?= SITE_URL ?>/student/my_registrations.php" style="font-size:13px;">View all →</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (!empty($upcoming)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $r): ?>
                    <tr>
                        <td style="font-weight:600;"><?= e($r['title']) ?></td>
                        <td style="white-space:nowrap;font-size:13px;"><?= formatDate($r['event_date']) ?></td>
                        <td><span class="badge <?= registrationStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state" style="padding:32px;">
                <div class="empty-icon">📭</div>
                <h3>No upcoming events</h3>
                <p><a href="<?= SITE_URL ?>/student/events.php">Browse and register for events!</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Latest Announcements -->
    <div class="card">
        <div class="card-header">
            <h3>📢 Latest Announcements</h3>
            <a href="<?= SITE_URL ?>/announcements.php" style="font-size:13px;">View all →</a>
        </div>
        <div class="card-body">
            <?php if (!empty($announcements)): ?>
            <div class="announcement-list">
                <?php foreach ($announcements as $ann): ?>
                <div class="announcement-card type-<?= e($ann['type']) ?>" style="padding:12px 14px;">
                    <h3 style="font-size:14px;"><?= e($ann['title']) ?></h3>
                    <p style="font-size:13px;margin-bottom:4px;">
                        <?= e(mb_substr($ann['content'], 0, 80)) ?>…
                    </p>
                    <span style="font-size:12px;color:var(--text-muted);"><?= formatDate($ann['date']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No announcements right now.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>.responsive-cols { } @media(max-width:700px){.responsive-cols{grid-template-columns:1fr!important;}}</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
