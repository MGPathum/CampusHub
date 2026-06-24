<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $file = fetchOne($conn, 'SELECT * FROM media WHERE id = ?', 'i', [$deleteId]);
    if ($file) {
        $fullPath = UPLOAD_DIR . $file['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        execute($conn, 'DELETE FROM media WHERE id = ?', 'i', [$deleteId]);
        setFlash('File deleted.', 'success');
    }
    redirect(SITE_URL . '/admin/media.php');
}

$typeFilter = get('type');
$sql    = "SELECT m.*, u.full_name AS uploader_name, e.title AS event_title
           FROM media m
           JOIN users u  ON m.uploader_id = u.id
           LEFT JOIN events e ON m.event_id = e.id
           WHERE 1=1";
$types  = '';
$params = [];

if ($typeFilter) {
    $sql   .= ' AND m.file_type LIKE ?';
    $types .= 's';
    $params[] = $typeFilter . '%';
}
$sql .= ' ORDER BY m.uploaded_at DESC';

$files = fetchAll($conn, $sql, $types, $params);

$pageTitle = 'Media Files';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>🖼️ Media Files</h1>
        <p><?= count($files) ?> file(s) uploaded.</p>
    </div>
</div>

<!-- Type filter -->
<form method="GET" class="filter-bar">
    <select name="type">
        <option value="">All Types</option>
        <option value="image" <?= $typeFilter === 'image' ? 'selected' : '' ?>>🖼️ Images</option>
        <option value="video" <?= $typeFilter === 'video' ? 'selected' : '' ?>>🎬 Videos</option>
        <option value="audio" <?= $typeFilter === 'audio' ? 'selected' : '' ?>>🎵 Audio</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= SITE_URL ?>/admin/media.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<?php if (!empty($files)): ?>
<div class="media-grid">
    <?php foreach ($files as $file): ?>
    <div class="media-item" style="position:relative;">

        <?php if (str_starts_with($file['file_type'], 'image/')): ?>
            <img src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                 alt="<?= e($file['caption'] ?? $file['file_name']) ?>"
                 style="height:150px;object-fit:cover;width:100%;">

        <?php elseif (str_starts_with($file['file_type'], 'video/')): ?>
            <video controls preload="metadata" style="width:100%;max-height:150px;">
                <source src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                        type="<?= e($file['file_type']) ?>">
            </video>

        <?php elseif (str_starts_with($file['file_type'], 'audio/')): ?>
            <div style="padding:16px 12px;text-align:center;">
                <div style="font-size:36px;margin-bottom:8px;">🎵</div>
                <audio controls style="width:100%;">
                    <source src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                            type="<?= e($file['file_type']) ?>">
                </audio>
            </div>
        <?php endif; ?>

        <div class="media-caption">
            <?php if ($file['caption']): ?>
                <strong><?= e($file['caption']) ?></strong><br>
            <?php endif; ?>
            <span style="font-size:11px;">👤 <?= e($file['uploader_name']) ?></span><br>
            <?php if ($file['event_title']): ?>
                <span style="font-size:11px;color:var(--primary);">📅 <?= e($file['event_title']) ?></span><br>
            <?php endif; ?>
            <span style="font-size:11px;color:var(--text-muted);"><?= formatDate($file['uploaded_at']) ?></span>
        </div>

        <!-- Delete button -->
        <form method="POST" style="padding:0 10px 10px;">
            <input type="hidden" name="delete_id" value="<?= $file['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm w-100"
                    data-confirm="Permanently delete this file?">🗑 Delete</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">🖼️</div>
    <h3>No media files yet</h3>
    <p>Students can upload files from their dashboard.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
