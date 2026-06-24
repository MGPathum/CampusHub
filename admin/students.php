<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    if ($deleteId !== (int)$_SESSION['user_id']) { // Prevent self-delete
        execute($conn, 'DELETE FROM users WHERE id = ? AND role = "student"', 'i', [$deleteId]);
        setFlash('Student record deleted.', 'success');
    } else {
        setFlash('You cannot delete your own account.', 'error');
    }
    redirect(SITE_URL . '/admin/students.php');
}

$search = get('search');
$sql    = "SELECT u.*,
           (SELECT COUNT(*) FROM registrations WHERE student_id = u.id) AS reg_count
           FROM users u WHERE u.role = 'student'";
$types  = '';
$params = [];

if ($search) {
    $sql    .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ? OR u.course LIKE ?)";
    $types   = 'ssss';
    $like    = '%' . $search . '%';
    $params  = [$like, $like, $like, $like];
}
$sql .= ' ORDER BY u.created_at DESC';

$students = fetchAll($conn, $sql, $types, $params);

$pageTitle = 'Student Records';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>👨‍🎓 Student Records</h1>
        <p><?= count($students) ?> student(s) found.</p>
    </div>
</div>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search by name, email, student ID, course…"
           value="<?= e($search) ?>" style="flex:1;">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <a href="<?= SITE_URL ?>/admin/students.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Registrations</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                <?php foreach ($students as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($s['profile_photo']): ?>
                                <img src="<?= SITE_URL ?>/uploads/<?= e($s['profile_photo']) ?>"
                                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;
                                            display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">
                                    <?= strtoupper(mb_substr($s['full_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <strong><?= e($s['full_name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($s['student_id'] ?? '—') ?></td>
                    <td style="font-size:13px;"><?= e($s['email']) ?></td>
                    <td><?= e($s['course'] ?? '—') ?></td>
                    <td class="text-center"><?= $s['year_level'] ?? '—' ?></td>
                    <td class="text-center"><?= $s['reg_count'] ?></td>
                    <td style="font-size:13px;white-space:nowrap;"><?= formatDate($s['created_at']) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="<?= SITE_URL ?>/admin/student_edit.php?id=<?= $s['id'] ?>"
                               class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Delete student '<?= e(addslashes($s['full_name'])) ?>'? This cannot be undone.">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted" style="padding:32px;">
                    No students found<?= $search ? ' matching your search.' : '.' ?>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
