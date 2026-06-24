<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$id      = (int)get('id');
$isEdit  = $id > 0;
$event   = [];
$errors  = [];

if ($isEdit) {
    $event = fetchOne($conn, 'SELECT * FROM events WHERE id = ?', 'i', [$id]);
    if (!$event) {
        setFlash('Event not found.', 'error');
        redirect(SITE_URL . '/admin/events.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = post('title');
    $description = post('description');
    $location    = post('location');
    $event_date  = post('event_date');
    $event_time  = post('event_time');
    $max_slots   = post('max_slots');
    $status      = post('status');

    // Validation
    if (!isNotEmpty($title))      $errors['title']      = 'Event title is required.';
    if (!isNotEmpty($event_date)) $errors['event_date'] = 'Event date is required.';
    $validStatuses = ['upcoming','ongoing','completed','cancelled'];
    if (!in_array($status, $validStatuses)) $errors['status'] = 'Invalid status selected.';

    // Handle banner image upload
    $bannerPath = $event['banner_image'] ?? null;
    if (!empty($_FILES['banner_image']['name'])) {
        $result = handleUpload($_FILES['banner_image'], 'media', ALLOWED_IMAGE_TYPES);
        if ($result['success']) {
            if ($bannerPath && file_exists(UPLOAD_DIR . $bannerPath)) unlink(UPLOAD_DIR . $bannerPath);
            $bannerPath = $result['path'];
        } else {
            $errors['banner_image'] = $result['error'];
        }
    }

    // Handle teaser video upload
    $teaserPath = $event['teaser_video'] ?? null;
    $teaserUrl  = post('teaser_url');
    if (!empty($_FILES['teaser_video']['name'])) {
        $result = handleUpload($_FILES['teaser_video'], 'media', ['video/mp4' => 'mp4', 'video/webm' => 'webm']);
        if ($result['success']) {
            if ($teaserPath && !filter_var($teaserPath, FILTER_VALIDATE_URL) && file_exists(UPLOAD_DIR . $teaserPath)) {
                unlink(UPLOAD_DIR . $teaserPath);
            }
            $teaserPath = $result['path'];
        } else {
            $errors['teaser_video'] = $result['error'];
        }
    } elseif ($teaserUrl) {
        $teaserPath = $teaserUrl; // Store external URL directly
    }

    if (empty($errors)) {
        $slots    = is_numeric($max_slots) && $max_slots > 0 ? (int)$max_slots : null;
        $adminId  = $_SESSION['user_id'];

        if ($isEdit) {
            execute($conn,
                'UPDATE events SET title=?, description=?, location=?, event_date=?, event_time=?,
                 max_slots=?, status=?, banner_image=?, teaser_video=? WHERE id=?',
                'sssssisssi',
                [$title, $description, $location, $event_date,
                 $event_time ? $event_time : null, $slots, $status, $bannerPath, $teaserPath, $id]);
            setFlash('Event updated successfully.', 'success');
        } else {
            execute($conn,
                'INSERT INTO events (title, description, location, event_date, event_time, max_slots,
                 status, banner_image, teaser_video, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'sssssisssi',
                [$title, $description, $location, $event_date,
                 $event_time ? $event_time : null, $slots, $status, $bannerPath, $teaserPath, $adminId]);
            setFlash('Event created successfully.', 'success');
        }
        redirect(SITE_URL . '/admin/events.php');
    }

    // Repopulate on error
    $event = array_merge($event, compact('title','description','location','event_date','event_time','max_slots','status'));
}

$pageTitle = $isEdit ? 'Edit Event' : 'Add Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= $isEdit ? '✏️ Edit Event' : '➕ Add New Event' ?></h1>
        <p><?= $isEdit ? 'Update event details.' : 'Create a new campus event.' ?></p>
    </div>
    <a href="<?= SITE_URL ?>/admin/events.php" class="btn btn-outline">← Back to Events</a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" data-validate>

            <div class="form-group">
                <label for="title">Event Title <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= e($event['title'] ?? '') ?>"
                       class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                       placeholder="e.g. Freshmen Orientation 2024" required>
                <?php if (!empty($errors['title'])): ?><span class="form-error"><?= e($errors['title']) ?></span><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Describe the event…"><?= e($event['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="event_date">Event Date <span class="required">*</span></label>
                    <input type="date" id="event_date" name="event_date"
                           value="<?= e($event['event_date'] ?? '') ?>"
                           class="<?= isset($errors['event_date']) ? 'is-invalid' : '' ?>" required>
                    <?php if (!empty($errors['event_date'])): ?><span class="form-error"><?= e($errors['event_date']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="event_time">Start Time</label>
                    <input type="time" id="event_time" name="event_time"
                           value="<?= e($event['event_time'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location / Venue</label>
                    <input type="text" id="location" name="location"
                           value="<?= e($event['location'] ?? '') ?>"
                           placeholder="e.g. Main Auditorium">
                </div>
                <div class="form-group">
                    <label for="max_slots">Max Slots <span class="form-hint" style="display:inline;">(leave blank for unlimited)</span></label>
                    <input type="number" id="max_slots" name="max_slots"
                           value="<?= e($event['max_slots'] ?? '') ?>"
                           min="1" placeholder="e.g. 100">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status <span class="required">*</span></label>
                <select id="status" name="status" required>
                    <?php foreach (['upcoming','ongoing','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($event['status'] ?? 'upcoming') === $s) ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['status'])): ?><span class="form-error"><?= e($errors['status']) ?></span><?php endif; ?>
            </div>

            <hr class="divider">
            <h3 style="font-size:15px;margin-bottom:16px;">Media</h3>

            <div class="form-group">
                <label for="banner_image">Banner Image</label>
                <?php if (!empty($event['banner_image'])): ?>
                    <div class="mb-1">
                        <img src="<?= SITE_URL ?>/uploads/<?= e($event['banner_image']) ?>"
                             style="max-height:100px;border-radius:6px;">
                        <span class="form-hint">Upload a new image to replace.</span>
                    </div>
                <?php endif; ?>
                <input type="file" id="banner_image" name="banner_image"
                       accept="image/jpeg,image/png,image/webp"
                       data-preview="banner-preview"
                       class="<?= isset($errors['banner_image']) ? 'is-invalid' : '' ?>">
                <?php if (!empty($errors['banner_image'])): ?><span class="form-error"><?= e($errors['banner_image']) ?></span><?php endif; ?>
                <div id="banner-preview" class="mt-1"></div>
            </div>

            <div class="form-group">
                <label for="teaser_video">Teaser Video (upload MP4/WebM)</label>
                <?php if (!empty($event['teaser_video']) && !filter_var($event['teaser_video'], FILTER_VALIDATE_URL)): ?>
                    <div class="mb-1">
                        <video src="<?= SITE_URL ?>/uploads/<?= e($event['teaser_video']) ?>"
                               controls style="max-height:80px;border-radius:6px;"></video>
                        <span class="form-hint">Upload a new video to replace.</span>
                    </div>
                <?php endif; ?>
                <input type="file" id="teaser_video" name="teaser_video"
                       accept="video/mp4,video/webm"
                       data-preview="video-preview"
                       class="<?= isset($errors['teaser_video']) ? 'is-invalid' : '' ?>">
                <?php if (!empty($errors['teaser_video'])): ?><span class="form-error"><?= e($errors['teaser_video']) ?></span><?php endif; ?>
                <div id="video-preview" class="mt-1"></div>
            </div>

            <div class="form-group">
                <label for="teaser_url">— OR — Teaser Video URL (YouTube/external)</label>
                <input type="text" id="teaser_url" name="teaser_url"
                       value="<?= !empty($event['teaser_video']) && filter_var($event['teaser_video'], FILTER_VALIDATE_URL) ? e($event['teaser_video']) : '' ?>"
                       placeholder="https://youtube.com/watch?v=...">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Event' : 'Create Event' ?></button>
                <a href="<?= SITE_URL ?>/admin/events.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
