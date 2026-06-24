<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId  = $_SESSION['user_id'];
$message = '';
$msgType = 'info';

// Handle cancel registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'], $_POST['cancel_id'])) {
    $regId = (int)$_POST['cancel_id'];

    // Check the registration belongs to this student
    $reg = fetchOne($conn,
        'SELECT id FROM registrations WHERE id = ? AND student_id = ?',
        'ii', [$regId, $userId]);

    if (!$reg) {
        setFlash('Registration not found or access denied.', 'error');
    } else {
        $affected = execute($conn,
            "UPDATE registrations SET status = 'cancelled' WHERE id = ? AND student_id = ?",
            'ii', [$regId, $userId]);

        if ($affected > 0) {
            setFlash('Your registration has been cancelled.', 'success');
        } else {
            setFlash('Could not cancel registration. Please try again.', 'error');
        }
    }

    // Redirect to avoid form resubmission
    redirect(SITE_URL . '/student/events.php');
}

// Handle register for event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $eventId = (int)$_POST['event_id'];

    // Check event exists and is upcoming
    $event = fetchOne($conn, "SELECT * FROM events WHERE id = ? AND status = 'upcoming'", 'i', [$eventId]);

    if (!$event) {
        setFlash('Event not found or registration is closed.', 'error');
    } else {
        // Check for an active registration (pending or approved, not absent)
        $activeReg = fetchOne($conn,
            "SELECT id FROM registrations
             WHERE student_id = ? AND event_id = ?
               AND status IN ('pending','approved')
               AND attendance != 'absent'",
            'ii', [$userId, $eventId]);

        if ($activeReg) {
            setFlash('You are already registered for this event.', 'warning');
        } else {
            // Check if a cancelled or absent row exists (can't INSERT due to unique key, must UPDATE)
            $oldRow = fetchOne($conn,
                "SELECT id FROM registrations
                 WHERE student_id = ? AND event_id = ?
                   AND (status = 'cancelled' OR attendance = 'absent')",
                'ii', [$userId, $eventId]);

            // Check slots
            if ($event['max_slots']) {
                $taken = fetchOne($conn,
                    "SELECT COUNT(*) AS cnt FROM registrations
                     WHERE event_id = ? AND status != 'cancelled'",
                    'i', [$eventId])['cnt'];
                $taken = $taken ? $taken : 0;
                if ($taken >= $event['max_slots']) {
                    setFlash('Sorry, this event is already full.', 'error');
                    redirect(SITE_URL . '/student/events.php');
                }
            }

            if ($oldRow) {
                // Re-activate old row
                $affected = execute($conn,
                    "UPDATE registrations SET status = 'pending', attendance = 'not_marked' WHERE id = ?",
                    'i', [$oldRow['id']]);
            } else {
                // Fresh registration
                $affected = execute($conn,
                    'INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, "pending")',
                    'ii', [$userId, $eventId]);
            }

            if ($affected > 0) {
                setFlash('Successfully registered for "' . $event['title'] . '"! Pending admin approval.', 'success');
            } else {
                setFlash('Registration failed. Please try again.', 'error');
            }
        }
    }

    // Redirect to avoid form resubmission
    redirect(SITE_URL . '/student/events.php');
}

// Get events with registration info for this student
$search = get('search');
$status = get('status', 'upcoming');

$sql = "SELECT e.*,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status != 'cancelled') AS reg_count,
        (SELECT id FROM registrations WHERE student_id = ? AND event_id = e.id AND status != 'cancelled' LIMIT 1) AS my_reg_id,
        (SELECT status FROM registrations WHERE student_id = ? AND event_id = e.id AND status != 'cancelled' LIMIT 1) AS my_reg_status,
        (SELECT attendance FROM registrations WHERE student_id = ? AND event_id = e.id AND status != 'cancelled' LIMIT 1) AS my_attendance
        FROM events e
        WHERE 1=1";
$types  = 'iii';
$params = [$userId, $userId, $userId];

if ($status) {
    $sql    .= ' AND e.status = ?';
    $types  .= 's';
    $params[] = $status;
}
if ($search) {
    $sql    .= ' AND (e.title LIKE ? OR e.location LIKE ?)';
    $types  .= 'ss';
    $like    = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY e.event_date ASC';

$events = fetchAll($conn, $sql, $types, $params);

$pageTitle = 'Browse Events';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>📅 Campus Events</h1><p>Browse and register for upcoming activities.</p></div>
</div>

<!-- Search & Filter -->
<form method="GET" action="" class="filter-bar">
    <input type="text" name="search" placeholder="Search events or location…"
           value="<?= e($search) ?>" style="flex:2;">
    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['upcoming','ongoing','completed','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <a href="<?= SITE_URL ?>/student/events.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<?php if (!empty($events)): ?>
<div class="event-grid">
    <?php foreach ($events as $event): ?>
    <?php
        $slotsLeft    = $event['max_slots'] ? max(0, $event['max_slots'] - $event['reg_count']) : null;
        $isRegistered = !empty($event['my_reg_id']);
        $isFull       = ($event['max_slots'] && $slotsLeft === 0);
        $myAtt        = $event['my_attendance'] ? $event['my_attendance'] : 'not_marked';
        $attMarked    = ($myAtt === 'attended' || $myAtt === 'absent');
    ?>
    <div class="card event-card">
        <?php if ($event['banner_image']): ?>
            <img src="<?= SITE_URL ?>/uploads/<?= e($event['banner_image']) ?>"
                 alt="<?= e($event['title']) ?>" class="event-banner">
        <?php else: ?>
            <div class="event-banner-placeholder">🎓</div>
        <?php endif; ?>

        <div class="card-body">
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                <span class="badge <?= eventStatusBadge($event['status']) ?>"><?= ucfirst($event['status']) ?></span>
                <?php if ($isRegistered): ?>
                    <span class="badge <?= registrationStatusBadge($event['my_reg_status']) ?>">
                        ✓ <?= ucfirst($event['my_reg_status']) ?>
                    </span>
                    <?php if ($myAtt === 'attended'): ?>
                        <span class="badge badge-success">✅ Attended</span>
                    <?php elseif ($myAtt === 'absent'): ?>
                        <span class="badge badge-danger">❌ Absent</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <h3><?= e($event['title']) ?></h3>

            <div class="event-meta">
                <span>📅 <?= formatDate($event['event_date']) ?></span>
                <?php if ($event['event_time']): ?>
                    <span>🕐 <?= formatTime($event['event_time']) ?></span>
                <?php endif; ?>
                <?php if ($event['location']): ?>
                    <span>📍 <?= e($event['location']) ?></span>
                <?php endif; ?>
                <span>👥 <?= $event['reg_count'] ?> registered
                    <?= $event['max_slots'] ? '/ ' . $event['max_slots'] . ' slots' : '' ?>
                </span>
            </div>

            <p style="font-size:14px;color:var(--text-muted);">
                <?= e(mb_substr($event['description'], 0, 120)) ?><?= strlen($event['description']) > 120 ? '…' : '' ?>
            </p>

            <?php if ($event['teaser_video']): ?>
            <div class="mt-2">
                <?php if (filter_var($event['teaser_video'], FILTER_VALIDATE_URL)): ?>
                    <a href="<?= e($event['teaser_video']) ?>" target="_blank" rel="noopener"
                       class="btn btn-outline btn-sm">▶ Watch Teaser</a>
                <?php else: ?>
                    <video controls style="width:100%;border-radius:6px;margin-top:8px;">
                        <source src="<?= SITE_URL ?>/uploads/<?= e($event['teaser_video']) ?>">
                        Your browser does not support video.
                    </video>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-footer">
            <?php if ($event['status'] !== 'upcoming'): ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <?php if ($isRegistered): ?>
                        <span class="badge <?= registrationStatusBadge($event['my_reg_status']) ?>">
                            Registration: <?= ucfirst($event['my_reg_status']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($myAtt === 'attended'): ?>
                        <span class="badge badge-success">✅ Attended</span>
                    <?php elseif ($myAtt === 'absent'): ?>
                        <span class="badge badge-danger">❌ Absent</span>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:13px;">Registration closed</span>
                    <?php endif; ?>
                </div>
            <?php elseif ($isRegistered): ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <span class="badge <?= registrationStatusBadge($event['my_reg_status']) ?>">
                        Registration: <?= ucfirst($event['my_reg_status']) ?>
                    </span>
                    <?php if ($myAtt === 'attended'): ?>
                        <span class="badge badge-success">✅ Attended</span>
                    <?php elseif ($myAtt === 'absent'): ?>
                        <span class="badge badge-danger">❌ Absent</span>
                    <?php endif; ?>
                </div>
                <?php if (!$attMarked): ?>
                    <form method="POST" style="display:inline;margin-top:6px;">
                        <input type="hidden" name="cancel_id" value="<?= $event['my_reg_id'] ?>">
                        <button type="submit" name="cancel" class="btn btn-danger btn-sm"
                                data-confirm="Cancel your registration for this event?">Cancel</button>
                    </form>
                <?php endif; ?>
            <?php elseif ($isFull): ?>
                <span class="badge badge-danger">Event Full</span>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <button type="submit" class="btn btn-primary btn-sm"
                            data-confirm="Register for '<?= e(addslashes($event['title'])) ?>'?">
                        Register Now
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">🗓️</div>
    <h3>No events found</h3>
    <p><?= $search ? 'Try a different search term.' : 'Check back soon for new events.' ?></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
