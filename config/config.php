<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '88222006');
define('DB_NAME', 'campushub');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_NAME', 'CampusHub');
define('SITE_URL', 'http://localhost/campushub');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('XML_FILE',   __DIR__ . '/../data/announcements.xml');

// Create db connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;color:red;padding:20px;">
            <strong>Database Connection Failed:</strong> ' . htmlspecialchars($conn->connect_error) . '
         </div>');
}

// Set charset
$conn->set_charset(DB_CHARSET);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
