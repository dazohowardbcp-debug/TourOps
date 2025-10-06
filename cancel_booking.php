<?php
// Start session before accessing $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
require_once 'inc/db.php';

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your bookings.']);
    exit;
}

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token()) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$bookingId = isset($input['booking_id']) ? intval($input['booking_id']) : 0;

if (!$bookingId) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
    exit;
}

// Ensure the booking belongs to the user and can be cancelled
try {
    $stmt = $pdo->prepare("SELECT id, status, travel_date FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }
    
    if ($booking['status'] === 'Cancelled' || $booking['status'] === 'Completed') {
        echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled.']);
        exit;
    }
    
    // Optional: prevent cancelling on/after travel date
    if (strtotime($booking['travel_date']) <= strtotime('today')) {
        echo json_encode(['success' => false, 'message' => 'Past or same-day bookings cannot be cancelled.']);
        exit;
    }
    
    // Update booking status to cancelled
    $update_stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
    $update_stmt->execute([$bookingId]);
    
    echo json_encode(['success' => true, 'message' => 'Booking has been cancelled successfully.']);
    
} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while cancelling the booking.']);
}




