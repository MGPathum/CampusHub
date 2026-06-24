<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId = $_SESSION['user_id'];
$errors = [];

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = post('caption');
    $eventId = post('event_id') !== '' ? (int)post('event_id') : null;

    if (empty($_FILES['media_file']['name'])) {
        $errors['media_file'] = 'Please select a file to upload.';
    } else {
        $result = handleUpload($_FILES['media_file'], 'media', ALLOWED_MEDIA_TYPES);
        if (!$result['success']) {
            $errors['media_file'] = $result['error'];
        } else {
            // Store in DB
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['media_file']['tmp_name']);
            // tmp_name is already moved; get mime from stored file
            $storedPath = UPLOAD_DIR . $result['path'];
            $mimeType   = mime_content_type($storedPath);
            $fileSize   = filesize($storedPath);

            execute($conn,
                'INSERT INTO media (uploader_id, event_id, file_name, file_path, file_type, file_size, caption)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'iisssis',
                [$userId, $eventId, $_FILES['media_file']['name'], $result['path'],
                 $mimeType, $fileSize, $caption]);

            setFlash('File uploaded successfully!', 'success');
            redirect(SITE_URL . '/student/upload_media.php');
        }
    }
}

// My uploaded files
$myFiles = fetchAll($conn,
    'SELECT m.*, e.title AS event_title
     FROM media m
     LEFT JOIN events e ON m.event_id = e.id
     WHERE m.uploader_id = ?
     ORDER BY m.uploaded_at DESC',
    'i', [$userId]);

// Events list for association dropdown
$events = fetchAll($conn, "SELECT id, title FROM events WHERE status != 'cancelled' ORDER BY event_date DESC");

$pageTitle = 'Upload Media';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>📸 Upload Media</h1><p>Share photos, videos, and audio with the campus community.</p></div>
</div>

<div style="display:grid;grid-template-columns:400px 1fr;gap:28px;align-items:start;" class="upload-layout">

    <!-- Upload Form -->
    <div class="card">
        <div class="card-header"><h2>Upload a File</h2></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" data-validate>
                <div class="form-group">
                    <label for="media_file">Choose File <span class="required">*</span></label>
                    <input type="file" id="media_file" name="media_file"
                           accept="image/*,video/mp4,video/webm,audio/mpeg,audio/ogg,audio/wav"
                           data-preview="upload-preview"
                           class="<?= isset($errors['media_file']) ? 'is-invalid' : '' ?>"
                           required>
                    <span class="form-hint">Images, MP4, WebM, MP3, OGG, WAV — max 10 MB</span>
                    <?php if (!empty($errors['media_file'])): ?>
                        <span class="form-error"><?= e($errors['media_file']) ?></span>
                    <?php endif; ?>
                    <div id="upload-preview" class="mt-1"></div>
                </div>

                <div class="form-group">
                    <label for="caption">Caption</label>
                    <input type="text" id="caption" name="caption"
                           value="<?= e(post('caption')) ?>"
                           placeholder="Describe this file…">
                </div>

                <div class="form-group">
                    <label for="event_id">Link to Event (optional)</label>
                    <select id="event_id" name="event_id">
                        <option value="">-- No event --</option>
                        <?php foreach ($events as $ev): ?>
                        <option value="<?= $ev['id'] ?>"><?= e($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Upload File</button>
            </form>
        </div>
    </div>

    <!-- My Files -->
    <div>
        <h2 class="section-title">My Uploaded Files</h2>

        <?php if (!empty($myFiles)): ?>
        <div class="media-grid">
            <?php foreach ($myFiles as $file): ?>
            <div class="media-item">
                <?php if (str_starts_with($file['file_type'], 'image/')): ?>
                    <img src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                         alt="<?= e($file['caption'] ?? $file['file_name']) ?>">

                <?php elseif (str_starts_with($file['file_type'], 'video/')): ?>
                    <video controls preload="metadata">
                        <source src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                                type="<?= e($file['file_type']) ?>">
                        Your browser does not support video.
                    </video>

                <?php elseif (str_starts_with($file['file_type'], 'audio/')): ?>
                    <div style="padding:12px;">
                        <div style="font-size:32px;text-align:center;margin-bottom:8px;">🎵</div>
                        <audio controls style="width:100%;">
                            <source src="<?= SITE_URL ?>/uploads/<?= e($file['file_path']) ?>"
                                    type="<?= e($file['file_type']) ?>">
                            Your browser does not support audio.
                        </audio>
                    </div>
                <?php endif; ?>

                <div class="media-caption">
                    <?= e($file['caption'] ?? $file['file_name']) ?>
                    <?php if ($file['event_title']): ?>
                        <br><span style="font-size:11px;color:var(--primary);">📅 <?= e($file['event_title']) ?></span>
                    <?php endif; ?>
                    <br><span style="font-size:11px;color:var(--text-muted);"><?= formatDate($file['uploaded_at']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🖼️</div>
            <h3>No files uploaded yet</h3>
            <p>Use the form to upload your first file.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.upload-layout { }
@media(max-width:768px){ .upload-layout{ grid-template-columns:1fr!important; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
