<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Update registration status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_id'], $_POST['new_status'])) {
    $regId     = (int)$_POST['reg_id'];
    $newStatus = $_POST['new_status'];
    $allowed   = ['pending', 'approved', 'rejected', 'cancelled'];

    if (in_array($newStatus, $allowed)) {
        execute($conn,
            'UPDATE registrations SET status = ? WHERE id = ?',
            'si', [$newStatus, $regId]);
        setFlash('Registration status updated to "' . ucfirst($newStatus) . '".', 'success');
    } else {
        setFlash('Invalid registration status.', 'error');
    }
    redirect(SITE_URL . '/admin/registrations.php?' . http_build_query($_GET));
}

// Update attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['att_reg_id'], $_POST['new_attendance'])) {
    $regId         = (int)$_POST['att_reg_id'];
    $newAttendance = $_POST['new_attendance'];
    $allowedAtt    = ['not_marked', 'attended', 'absent'];

    if (in_array($newAttendance, $allowedAtt)) {
        // Only approved registrations can have attendance marked
        $reg = fetchOne($conn,
            "SELECT id FROM registrations WHERE id = ? AND status = 'approved'",
            'i', [$regId]);

        if ($reg) {
            execute($conn,
                'UPDATE registrations SET attendance = ? WHERE id = ?',
                'si', [$newAttendance, $regId]);
            setFlash('Attendance marked as "' . ucfirst(str_replace('_', ' ', $newAttendance)) . '".', 'success');
        } else {
            setFlash('Attendance can only be marked for approved registrations.', 'error');
        }
    } else {
        setFlash('Invalid attendance value.', 'error');
    }
    redirect(SITE_URL . '/admin/registrations.php?' . http_build_query($_GET));
}

// Get registrations with filters
$search       = get('search');
$statusFilter = get('status');
$eventFilter  = get('event_id');

$sql    = "SELECT r.*, u.full_name, u.student_id AS sid, u.email,
           e.title AS event_title, e.event_date
           FROM registrations r
           JOIN users u  ON r.student_id = u.id
           JOIN events e ON r.event_id   = e.id
           WHERE 1=1";
$types  = '';
$params = [];

if ($statusFilter) {
    $sql .= ' AND r.status = ?'; $types .= 's'; $params[] = $statusFilter;
}
if ($eventFilter) {
    $sql .= ' AND r.event_id = ?'; $types .= 'i'; $params[] = (int)$eventFilter;
}
if ($search) {
    $sql .= ' AND (u.full_name LIKE ? OR u.student_id LIKE ? OR e.title LIKE ?)';
    $types .= 'sss';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$sql .= ' ORDER BY r.registered_at DESC';

$registrations = fetchAll($conn, $sql, $types, $params);

// Events list for filter dropdown
$eventsList = fetchAll($conn, "SELECT id, title FROM events ORDER BY event_date DESC");

$pageTitle = 'Manage Registrations';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* compact table style for registrations page */
.reg-table {
    font-size: 0.82rem;
}
.reg-table thead th,
.reg-table tbody td {
    padding: 8px 10px;
}
</style>

<div class="page-header">
    <div>
        <h1>✅ Manage Registrations</h1>
        <p><?= count($registrations) ?> registration(s) found.</p>
    </div>
</div>

<!-- Filter form -->
<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Student name, ID, or event…"
           value="<?= e($search) ?>" style="flex:2;">
    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="event_id">
        <option value="">All Events</option>
        <?php foreach ($eventsList as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $eventFilter == $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= SITE_URL ?>/admin/registrations.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="reg-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Event Date</th>
                    <th>Reg. Status</th>
                    <th>Attendance</th>
                    <th>Registered On</th>
                    <th>Change Reg. Status</th>
                    <th>Mark Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registrations)): ?>
                <?php foreach ($registrations as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <strong><?= e($r['full_name']) ?></strong><br>
                        <span class="text-muted" style="font-size:12px;"><?= e($r['sid']) ?></span>
                    </td>
                    <td><?= e($r['event_title']) ?></td>
                    <td style="font-size:13px;white-space:nowrap;"><?= formatDate($r['event_date']) ?></td>

                    <!-- Registration status -->
                    <td>
                        <span class="badge <?= registrationStatusBadge($r['status']) ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>

                    <!-- Attendance status -->
                    <td>
                        <?php $att = isset($r['attendance']) ? $r['attendance'] : 'not_marked'; ?>
                        <span class="badge <?= attendanceStatusBadge($att) ?>">
                            <?php
                                if ($att === 'attended')   echo '✅ Attended';
                                elseif ($att === 'absent') echo '❌ Absent';
                                else                       echo '— Not Marked';
                            ?>
                        </span>
                    </td>

                    <td style="font-size:13px;white-space:nowrap;"><?= formatDate($r['registered_at']) ?></td>

                    <!-- Change registration status -->
                    <td style="min-width:155px;white-space:nowrap;">
                        <form method="POST">
                            <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                            <div style="display:flex;gap:4px;align-items:center;white-space:nowrap;">
                                <select name="new_status"
                                        style="padding:3px 6px;font-size:0.8rem;border-radius:5px;border:1px solid var(--border);min-width:95px;width:auto;">
                                    <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm"
                                        style="padding:3px 8px;font-size:0.8rem;">Save</button>
                            </div>
                        </form>
                    </td>

                    <!-- Mark attendance (approved only) -->
                    <td style="min-width:175px;white-space:nowrap;">
                        <?php if ($r['status'] === 'approved'): ?>
                        <form method="POST">
                            <input type="hidden" name="att_reg_id" value="<?= $r['id'] ?>">
                            <div style="display:flex;gap:4px;align-items:center;white-space:nowrap;">
                                <?php $curAtt = isset($r['attendance']) ? $r['attendance'] : 'not_marked'; ?>
                                <select name="new_attendance"
                                        style="padding:3px 6px;font-size:0.8rem;border-radius:5px;border:1px solid var(--border);min-width:112px;width:auto;">
                                    <option value="not_marked" <?= $curAtt === 'not_marked' ? 'selected' : '' ?>>— Not Marked</option>
                                    <option value="attended"   <?= $curAtt === 'attended'   ? 'selected' : '' ?>>✅ Attended</option>
                                    <option value="absent"     <?= $curAtt === 'absent'     ? 'selected' : '' ?>>❌ Absent</option>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm"
                                        style="padding:3px 8px;font-size:0.8rem;">Mark</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:0.78rem;">Approve first</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding:32px;">
                        No registrations found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
