<?php
/**
 * Admin Login Redirect
 * Admins should use the main login page
 * This file redirects to the main login with a message
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in as admin, go to admin panel
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['is_admin'])) {
    header('Location: index.php');
    exit;
}

// Redirect to main login page
$_SESSION['info'] = 'Please login with your admin account to access the admin panel.';
header('Location: ../login.php');
exit;