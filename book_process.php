<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';

// Validate session
if (!isset($_SESSION['user']) && empty($_POST)) {
    $_SESSION['error'] = "Please log in to make a booking.";
    header('Location: login.php');
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Invalid security token. Please try again.";
    header('Location: packages.php');
    exit;
}

// Sanitize inputs
$package_id = intval($_POST['package_id'] ?? 0);
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');
$pax = intval($_POST['pax'] ?? 1);
$travel_date = trim($_POST['travel_date'] ?? '');
$special_requests = trim($_POST['special_requests'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$override_dup = isset($_POST['override_duplicate']) && !empty($_SESSION['user']['is_admin']);

// Validate required fields
if (!$package_id || !$guest_name || !$guest_email || $pax < 1 || $pax > 50 || !$travel_date) {
    $_SESSION['error'] = $pax > 50 ? "Maximum pax is 50." : "Invalid booking data. Please fill all required fields.";
    header('Location: packages.php');
    exit;
}

// Validate travel date (must be in the future)
$travel_timestamp = strtotime($travel_date);
$today = strtotime('today');
if ($travel_timestamp <= $today) {
    $_SESSION['error'] = "Travel date must be in the future (not today).";
    header('Location: packages.php');
    exit;
}

// Fetch package price
$pkgStm = $pdo->prepare("SELECT price FROM packages WHERE id = ?");
$pkgStm->execute([$package_id]);
$pkg = $pkgStm->fetch();
if (!$pkg) {
    $_SESSION['error'] = "Package not found.";
    header('Location: packages.php');
    exit;
}
$price = $pkg['price'];

// Prevent duplicate booking unless override is enabled
$user_id = $_SESSION['user']['id'] ?? null;
if (!$override_dup) {
    $dupStm = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE package_id = ? AND travel_date = ? AND (user_id = ? OR guest_email = ?)
    ");
    $dupStm->execute([$package_id, $travel_date, $user_id, $guest_email]);
    if ((int)$dupStm->fetchColumn() > 0) {
        $_SESSION['error'] = "You already have a booking for this package on that date.";
        header('Location: packages.php');
        exit;
    }
}

// Calculate total
$total = $price * $pax;

// Insert booking
$ins = $pdo->prepare("
    INSERT INTO bookings 
    (user_id, guest_name, guest_email, package_id, pax, total, travel_date, special_requests, notes) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$ins->execute([
    $user_id,
    $guest_name,
    $guest_email,
    $package_id,
    $pax,
    $total,
    $travel_date,
    $special_requests,
    $notes
]);

$_SESSION['success'] = "Booking created successfully! Total: ₱" . number_format($total, 0) . " for " . date('F j, Y', strtotime($travel_date));
header('Location: itinerary.php?id=' . $package_id);
exit;
