<?php
/**
 * Admin redirect file
 * Redirects to admin/index.php for proper admin panel access
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';

// Check if user is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: login.php');
    exit;
}

// Redirect to admin directory
header('Location: admin/');
exit;
