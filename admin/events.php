<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    execute($conn, 'DELETE FROM events WHERE id = ?', 'i', [$deleteId]);
    setFlash('Event deleted.', 'success');
    redirect(SITE_URL . '/admin/events.php');
}

$search = get('search');
$status = get('status');

$sql    = "SELECT e.*,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) AS reg_count,
           u.full_name AS created_by_name
           FROM events e
           LEFT JOIN users u ON e.created_by = u.id
           WHERE 1=1";
$types  = '';
$params = [];

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
$sql .= ' ORDER BY e.event_date DESC';

$events = fetchAll($conn, $sql, $types, $params);

$pageTitle = 'Manage Events';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>📅 Manage Events</h1>
        <p><?= count($events) ?> event(s) found.</p>
    </div>
    <a href="<?= SITE_URL ?>/admin/event_form.php" class="btn btn-primary">+ Add Event</a>
</div>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search title or location…" value="<?= e($search) ?>" style="flex:1;">
    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['upcoming','ongoing','completed','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= SITE_URL ?>/admin/events.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Date & Time</th>
                    <th>Location</th>
                    <th>Slots</th>
                    <th>Regs</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($events)): ?>
                <?php foreach ($events as $i => $ev): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($ev['title']) ?></strong></td>
                    <td style="white-space:nowrap;font-size:13px;">
                        <?= formatDate($ev['event_date']) ?>
                        <?php if ($ev['event_time']): ?><br><?= formatTime($ev['event_time']) ?><?php endif; ?>
                    </td>
                    <td><?= e($ev['location'] ?? '—') ?></td>
                    <td class="text-center"><?= $ev['max_slots'] ?? '∞' ?></td>
                    <td class="text-center"><?= $ev['reg_count'] ?></td>
                    <td><span class="badge <?= eventStatusBadge($ev['status']) ?>"><?= ucfirst($ev['status']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="<?= SITE_URL ?>/admin/event_form.php?id=<?= $ev['id'] ?>"
                               class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete event '<?= e(addslashes($ev['title'])) ?>'? All registrations will also be deleted.">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:32px;">No events found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
