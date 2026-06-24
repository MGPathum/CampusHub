<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Load and parse all active announcements from XML
$announcements = getAnnouncements();

// Optional: filter by type via GET param
$filterType = get('type');
if ($filterType) {
    $announcements = array_filter($announcements, fn($a) => $a['type'] === $filterType);
}

$pageTitle = 'Announcements';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>📢 Campus Announcements</h1>
        <p>Stay informed with the latest news and updates from the administration.</p>
    </div>
</div>

<!-- Filter bar -->
<div class="filter-bar mb-3">
    <span style="font-size:14px;font-weight:600;color:var(--text-muted);">Filter by type:</span>
    <a href="<?= SITE_URL ?>/announcements.php"
       class="btn btn-sm <?= !$filterType ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <?php
    $types = ['info' => '🔵 Info', 'success' => '🟢 Events', 'warning' => '🟡 Reminders', 'danger' => '🔴 Urgent'];
    foreach ($types as $key => $label):
    ?>
    <a href="<?= SITE_URL ?>/announcements.php?type=<?= $key ?>"
       class="btn btn-sm <?= $filterType === $key ? 'btn-primary' : 'btn-outline' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($announcements)): ?>
<div class="announcement-list">
    <?php foreach ($announcements as $ann): ?>
    <div class="announcement-card type-<?= e($ann['type']) ?>">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <span class="badge badge-<?= $ann['type'] === 'danger' ? 'danger' : ($ann['type'] === 'success' ? 'success' : ($ann['type'] === 'warning' ? 'warning' : 'info')) ?>">
                        <?= ucfirst($ann['type']) ?>
                    </span>
                    <?php if ($ann['priority'] <= 2): ?>
                        <span class="badge badge-danger">High Priority</span>
                    <?php endif; ?>
                </div>
                <h3 style="margin-bottom:10px;"><?= e($ann['title']) ?></h3>
                <p style="font-size:15px;line-height:1.7;color:var(--text);"><?= nl2br(e($ann['content'])) ?></p>
            </div>
        </div>
        <hr class="divider" style="margin:12px 0;">
        <div class="announcement-meta">
            <span>👤 Posted by: <strong><?= e($ann['author']) ?></strong></span>
            <span>📅 <?= formatDate($ann['date']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>No announcements found</h3>
    <p>There are currently no announcements<?= $filterType ? " in this category" : "" ?>.</p>
    <?php if ($filterType): ?>
        <a href="<?= SITE_URL ?>/announcements.php" class="btn btn-outline mt-2">View All</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
