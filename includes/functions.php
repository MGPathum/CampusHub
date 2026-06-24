<?php
// CampusHub - Helper Functions

// --- Auth helpers ---

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        setFlash('Please log in to access that page.', 'warning');
        redirect(SITE_URL . '/auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        setFlash('You do not have permission to view that page.', 'error');
        $home = ($_SESSION['role'] === 'admin')
            ? SITE_URL . '/admin/dashboard.php'
            : SITE_URL . '/student/dashboard.php';
        redirect($home);
    }
}

// --- Input / output helpers ---

// Escape output
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Get trimmed POST value
function post($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Get trimmed GET value
function get($key, $default = '') {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function isNotEmpty($value) {
    return $value !== '';
}

function isValidEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Password: min 8 chars, at least one letter and one number
function isValidPassword($password) {
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

// --- Database helpers ---

// Fetch one row
function fetchOne($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    return $row ? $row : null;
}

// Fetch all rows
function fetchAll($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

// Run INSERT, UPDATE or DELETE - returns affected rows
function execute($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return -1;

    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

// --- File upload helpers ---

// Allowed image types
const ALLOWED_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

// Allowed media types
const ALLOWED_MEDIA_TYPES = [
    'image/jpeg'  => 'jpg',
    'image/png'   => 'png',
    'video/mp4'   => 'mp4',
    'video/webm'  => 'webm',
    'audio/mpeg'  => 'mp3',
    'audio/ogg'   => 'ogg',
    'audio/wav'   => 'wav',
];

const MAX_UPLOAD_SIZE = 10 * 1024 * 1024; // 10 MB

// Handle file upload and return result array
function handleUpload($file, $subDir, $allowedTypes = ALLOWED_MEDIA_TYPES) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        ];
        $msg = isset($errors[$file['error']]) ? $errors[$file['error']] : 'Unknown upload error.';
        return ['success' => false, 'path' => '', 'error' => $msg];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'path' => '', 'error' => 'File size exceeds the 10 MB limit.'];
    }

    // Check MIME type
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedTypes)) {
        return ['success' => false, 'path' => '', 'error' => 'File type not allowed.'];
    }

    // Generate unique filename
    $ext      = $allowedTypes[$mimeType];
    $newName  = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir  = UPLOAD_DIR . $subDir . '/';
    $destPath = $destDir . $newName;

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        return ['success' => false, 'path' => '', 'error' => 'Could not create upload directory.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'path' => '', 'error' => 'Failed to move uploaded file.'];
    }

    return ['success' => true, 'path' => $subDir . '/' . $newName, 'error' => ''];
}

// --- XML helpers ---

// Read active announcements from XML file
function getAnnouncements() {
    $xmlFile = XML_FILE;

    if (!file_exists($xmlFile)) {
        return [];
    }

    $xml = simplexml_load_file($xmlFile, 'SimpleXMLElement', LIBXML_NOERROR);

    if ($xml === false) {
        return [];
    }

    $announcements = [];
    foreach ($xml->announcement as $item) {
        if ((string) $item->active !== 'true') {
            continue;
        }
        $announcements[] = [
            'id'       => (int)    $item['id'],
            'title'    => (string) $item->title,
            'content'  => (string) $item->content,
            'type'     => (string) $item->type,
            'priority' => (int)    $item->priority,
            'author'   => (string) $item->author,
            'date'     => (string) $item->date,
        ];
    }

    // Sort by priority
    usort($announcements, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });

    return $announcements;
}

// Save a new announcement to the XML file
function saveAnnouncement($data) {
    $xmlFile = XML_FILE;

    if (file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile, 'SimpleXMLElement', LIBXML_NOERROR);
    }

    if (!isset($xml) || $xml === false) {
        $xml = new SimpleXMLElement('<announcements></announcements>');
    }

    // Get next ID
    $maxId = 0;
    foreach ($xml->announcement as $item) {
        $id = (int) $item['id'];
        if ($id > $maxId) $maxId = $id;
    }
    $newId = $maxId + 1;

    $node = $xml->addChild('announcement');
    $node->addAttribute('id', (string) $newId);
    $node->addChild('title',    htmlspecialchars($data['title'],   ENT_XML1, 'UTF-8'));
    $node->addChild('content',  htmlspecialchars($data['content'], ENT_XML1, 'UTF-8'));
    $node->addChild('type',     isset($data['type'])     ? $data['type']     : 'info');
    $node->addChild('priority', (string)(isset($data['priority']) ? $data['priority'] : 3));
    $node->addChild('author',   htmlspecialchars(isset($data['author']) ? $data['author'] : 'Admin', ENT_XML1, 'UTF-8'));
    $node->addChild('date',     date('Y-m-d'));
    $node->addChild('active',   'true');

    // Format and save
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    return (bool) $dom->save($xmlFile);
}

// --- Date / formatting helpers ---

// Format date for display e.g. August 15, 2024
function formatDate($date) {
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : $date;
}

// Format time to 12-hour format e.g. 1:00 PM
function formatTime($time) {
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : $time;
}

// Return badge CSS class for event status
function eventStatusBadge($status) {
    $map = [
        'upcoming'  => 'badge-primary',
        'ongoing'   => 'badge-success',
        'completed' => 'badge-secondary',
        'cancelled' => 'badge-danger',
    ];
    return isset($map[$status]) ? $map[$status] : 'badge-secondary';
}

// Return badge CSS class for registration status
function registrationStatusBadge($status) {
    $map = [
        'pending'   => 'badge-warning',
        'approved'  => 'badge-success',
        'rejected'  => 'badge-danger',
        'cancelled' => 'badge-secondary',
    ];
    return isset($map[$status]) ? $map[$status] : 'badge-secondary';
}

// Return badge CSS class for attendance status
function attendanceStatusBadge($attendance) {
    $map = [
        'attended'   => 'badge-success',
        'absent'     => 'badge-danger',
        'not_marked' => 'badge-secondary',
    ];
    return isset($map[$attendance]) ? $map[$attendance] : 'badge-secondary';
}
