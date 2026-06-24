<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId = $_SESSION['user_id'];

// Cancel registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $regId = (int)$_POST['cancel_id'];
    // Check the registration belongs to this student
    $reg = fetchOne($conn,
        'SELECT * FROM registrations WHERE id = ? AND student_id = ?',
        'ii', [$regId, $userId]);

    if ($reg && in_array($reg['status'], ['pending', 'approved'])
             && !in_array($reg['attendance'] ? $reg['attendance'] : 'not_marked', ['attended', 'absent'])) {
        execute($conn,
            "UPDATE registrations SET status='cancelled' WHERE id=?",
            'i', [$regId]);
        setFlash('Registration cancelled.', 'info');
    } else {
        setFlash('Could not cancel this registration.', 'error');
    }
    redirect(SITE_URL . '/student/my_registrations.php');
}

// Fetch all registrations
$statusFilter = get('status');
$sql    = "SELECT r.*, e.title, e.event_date, e.event_time, e.location, e.status AS event_status
           FROM registrations r
           JOIN events e ON r.event_id = e.id
           WHERE r.student_id = ?";
$types  = 'i';
$params = [$userId];

if ($statusFilter) {
    $sql    .= ' AND r.status = ?';
    $types  .= 's';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY e.event_date DESC';

$registrations = fetchAll($conn, $sql, $types, $params);

$pageTitle = 'My Registrations';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>✅ My Registrations</h1><p>Track your event registrations and their status.</p></div>
    <a href="<?= SITE_URL ?>/student/events.php" class="btn btn-primary">Browse More Events</a>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar">
    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= SITE_URL ?>/student/my_registrations.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<?php if (!empty($registrations)): ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Reg. Status</th>
                    <th>Attendance</th>
                    <th>Registered On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <strong><?= e($r['title']) ?></strong><br>
                        <span class="badge <?= eventStatusBadge($r['event_status']) ?>" style="margin-top:4px;">
                            <?= ucfirst($r['event_status']) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;">
                        <?= formatDate($r['event_date']) ?><br>
                        <?php if ($r['event_time']): ?>
                            <span class="text-muted" style="font-size:12px;"><?= formatTime($r['event_time']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['location'] ?? '—') ?></td>
                    <td><span class="badge <?= registrationStatusBadge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php
                            $att = $r['attendance'] ?? 'not_marked';
                            if ($att === 'attended'):
                        ?>
                            <span class="badge badge-success">✅ Attended</span>
                        <?php elseif ($att === 'absent'): ?>
                            <span class="badge badge-danger">❌ Absent</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">— Not Marked</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;white-space:nowrap;"><?= formatDate($r['registered_at']) ?></td>
                    <td>
                        <?php
                            $att = $r['attendance'] ?? 'not_marked';
                            $attendanceMarked = in_array($att, ['attended', 'absent']);
                            $canCancel = in_array($r['status'], ['pending', 'approved'])
                                      && $r['event_status'] === 'upcoming'
                                      && !$attendanceMarked;
                        ?>
                        <?php if ($canCancel): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Cancel your registration for this event?">Cancel</button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>No registrations yet</h3>
    <p><a href="<?= SITE_URL ?>/student/events.php">Browse events and register!</a></p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
