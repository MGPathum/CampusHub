<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Stats
$stats = [
    'students'      => fetchOne($conn, "SELECT COUNT(*) AS c FROM users WHERE role='student'")['c'] ?? 0,
    'events'        => fetchOne($conn, "SELECT COUNT(*) AS c FROM events")['c'] ?? 0,
    'upcoming'      => fetchOne($conn, "SELECT COUNT(*) AS c FROM events WHERE status='upcoming'")['c'] ?? 0,
    'registrations' => fetchOne($conn, "SELECT COUNT(*) AS c FROM registrations")['c'] ?? 0,
    'pending_regs'  => fetchOne($conn, "SELECT COUNT(*) AS c FROM registrations WHERE status='pending'")['c'] ?? 0,
    'media'         => fetchOne($conn, "SELECT COUNT(*) AS c FROM media")['c'] ?? 0,
];

// Recent registrations
$recentRegs = fetchAll($conn,
    "SELECT r.*, u.full_name, u.student_id AS sid, e.title AS event_title
     FROM registrations r
     JOIN users u ON r.student_id = u.id
     JOIN events e ON r.event_id  = e.id
     ORDER BY r.registered_at DESC LIMIT 8");

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>🛡️ Admin Dashboard</h1>
        <p>Overview of CampusHub activity.</p>
    </div>
    <a href="<?= SITE_URL ?>/admin/event_form.php" class="btn btn-primary">+ New Event</a>
</div>

<!-- Stats -->
<div class="dashboard-grid mb-4">
    <a href="<?= SITE_URL ?>/admin/students.php" class="dash-card">
        <div class="dash-icon blue">👨‍🎓</div>
        <div class="dash-info"><h3>Total Students</h3><strong><?= $stats['students'] ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/events.php" class="dash-card">
        <div class="dash-icon green">📅</div>
        <div class="dash-info"><h3>Upcoming Events</h3><strong><?= $stats['upcoming'] ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/registrations.php?status=pending" class="dash-card">
        <div class="dash-icon orange">⏳</div>
        <div class="dash-info"><h3>Pending Approvals</h3><strong><?= $stats['pending_regs'] ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/registrations.php" class="dash-card">
        <div class="dash-icon purple">✅</div>
        <div class="dash-info"><h3>Total Registrations</h3><strong><?= $stats['registrations'] ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/media.php" class="dash-card">
        <div class="dash-icon red">🖼️</div>
        <div class="dash-info"><h3>Media Files</h3><strong><?= $stats['media'] ?></strong></div>
    </a>
    <a href="<?= SITE_URL ?>/admin/announcements.php" class="dash-card">
        <div class="dash-icon blue">📢</div>
        <div class="dash-info"><h3>Announcements</h3><strong><?= count(getAnnouncements()) ?></strong></div>
    </a>
</div>

<!-- Recent Registrations -->
<div class="card">
    <div class="card-header">
        <h2>Recent Registrations</h2>
        <a href="<?= SITE_URL ?>/admin/registrations.php" style="font-size:13px;">View all →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th>Registered On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentRegs)): ?>
                <?php foreach ($recentRegs as $r): ?>
                <tr>
                    <td>
                        <strong><?= e($r['full_name']) ?></strong><br>
                        <span class="text-muted" style="font-size:12px;"><?= e($r['sid']) ?></span>
                    </td>
                    <td><?= e($r['event_title']) ?></td>
                    <td><span class="badge <?= registrationStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td style="font-size:13px;white-space:nowrap;"><?= formatDate($r['registered_at']) ?></td>
                    <td>
                        <a href="<?= SITE_URL ?>/admin/registrations.php?id=<?= $r['id'] ?>"
                           class="btn btn-outline btn-sm">Manage</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">No registrations yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
