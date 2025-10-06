<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration (consistent across subdirectories like /admin)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
// Strip trailing /admin if present so BASE_URL always points to site root
$rootBase = preg_replace('#/admin$#i', '', rtrim($scriptDir, '/'));
$rootBase = ($rootBase === '/' ? '' : $rootBase);
define('BASE_URL', $protocol . '://' . $host . $rootBase);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourops');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('SITE_NAME', 'Tour Operations Management System');
define('SITE_VERSION', '1.0.0');
define('SITE_EMAIL', 'noreply@tourops.com'); // Email address for system emails

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');

// Session settings
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// Email settings (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// API settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per hour

// Error reporting (set to false in production)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Helper functions
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}
function asset($path = '') {
    return ASSETS_URL . '/' . ltrim($path, '/');
}
function upload($path = '') {
    return UPLOADS_URL . '/' . ltrim($path, '/');
}
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    return isset($_SESSION['csrf_token']) && $token && hash_equals($_SESSION['csrf_token'], $token);
}