<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$errors = [];

// Handle publish new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish'])) {
    $data = [
        'title'    => post('title'),
        'content'  => post('content'),
        'type'     => post('type'),
        'priority' => post('priority'),
        'author'   => $_SESSION['full_name'],
    ];

    if (!isNotEmpty($data['title']))   $errors['title']   = 'Title is required.';
    if (!isNotEmpty($data['content'])) $errors['content'] = 'Content is required.';

    if (empty($errors)) {
        if (saveAnnouncement($data)) {
            setFlash('Announcement published successfully!', 'success');
        } else {
            setFlash('Failed to save announcement. Check file permissions on data/announcements.xml.', 'error');
        }
        redirect(SITE_URL . '/admin/announcements.php');
    }
}

// Handle deactivate (set active = false)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_id'])) {
    $targetId = (int)$_POST['deactivate_id'];
    $xmlFile  = XML_FILE;

    if (file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile);
        foreach ($xml->announcement as $ann) {
            if ((int)$ann['id'] === $targetId) {
                $ann->active = 'false';
                break;
            }
        }
        $dom = new DOMDocument('1.0','UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($xmlFile);
        setFlash('Announcement deactivated.', 'info');
    }
    redirect(SITE_URL . '/admin/announcements.php');
}

// Load all announcements (including inactive for admin view)
$allAnns = [];
if (file_exists(XML_FILE)) {
    $xml = simplexml_load_file(XML_FILE);
    foreach ($xml->announcement as $item) {
        $allAnns[] = [
            'id'       => (int)    $item['id'],
            'title'    => (string) $item->title,
            'content'  => (string) $item->content,
            'type'     => (string) $item->type,
            'priority' => (int)    $item->priority,
            'author'   => (string) $item->author,
            'date'     => (string) $item->date,
            'active'   => (string) $item->active === 'true',
        ];
    }
}

$pageTitle = 'Manage Announcements';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div><h1>📢 Manage Announcements</h1><p>Publish and manage campus announcements (stored in XML).</p></div>
</div>

<div style="display:grid;grid-template-columns:400px 1fr;gap:28px;align-items:start;" class="ann-layout">

    <!-- Publish Form -->
    <div class="card">
        <div class="card-header"><h2>Publish Announcement</h2></div>
        <div class="card-body">
            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title"
                           value="<?= e(post('title')) ?>"
                           class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                           placeholder="Announcement title" required>
                    <?php if (!empty($errors['title'])): ?><span class="form-error"><?= e($errors['title']) ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="content">Content <span class="required">*</span></label>
                    <textarea id="content" name="content"
                              class="<?= isset($errors['content']) ? 'is-invalid' : '' ?>"
                              placeholder="Full announcement text…" style="min-height:120px;" required><?= e(post('content')) ?></textarea>
                    <?php if (!empty($errors['content'])): ?><span class="form-error"><?= e($errors['content']) ?></span><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="info">ℹ️ Info</option>
                            <option value="success">✅ Event/Success</option>
                            <option value="warning">⚠️ Warning/Reminder</option>
                            <option value="danger">🔴 Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <?php for ($p = 1; $p <= 5; $p++): ?>
                            <option value="<?= $p ?>"><?= $p ?> <?= $p === 1 ? '(Highest)' : ($p === 5 ? '(Lowest)' : '') ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" name="publish" class="btn btn-primary w-100">Publish Announcement</button>
            </form>
        </div>
    </div>

    <!-- Announcements List -->
    <div>
        <h2 class="section-title">All Announcements (XML)</h2>

        <?php if (!empty($allAnns)): ?>
        <div class="announcement-list">
            <?php foreach (array_reverse($allAnns) as $ann): ?>
            <div class="announcement-card type-<?= e($ann['type']) ?>"
                 style="<?= !$ann['active'] ? 'opacity:.5;' : '' ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div style="flex:1;">
                        <div style="display:flex;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                            <span class="badge badge-<?= $ann['type'] === 'danger' ? 'danger' : ($ann['type'] === 'success' ? 'success' : ($ann['type'] === 'warning' ? 'warning' : 'info')) ?>">
                                <?= ucfirst($ann['type']) ?>
                            </span>
                            <span class="badge badge-secondary">Priority <?= $ann['priority'] ?></span>
                            <?php if (!$ann['active']): ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size:15px;"><?= e($ann['title']) ?></h3>
                        <p style="font-size:13px;margin:6px 0;"><?= e(mb_substr($ann['content'], 0, 120)) ?>…</p>
                        <div class="announcement-meta">
                            <span>👤 <?= e($ann['author']) ?></span>
                            <span>📅 <?= formatDate($ann['date']) ?></span>
                        </div>
                    </div>
                    <?php if ($ann['active']): ?>
                    <form method="POST" style="flex-shrink:0;">
                        <input type="hidden" name="deactivate_id" value="<?= $ann['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                data-confirm="Deactivate this announcement?">Deactivate</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No announcements yet</h3>
            <p>Use the form to publish your first announcement.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.ann-layout { }
@media(max-width:768px){ .ann-layout{ grid-template-columns:1fr!important; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
