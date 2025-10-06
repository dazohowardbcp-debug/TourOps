<?php
/**
 * Submit Rating/Review Handler
 * Processes user ratings and reviews for completed bookings
 */

require_once 'inc/config.php';
require_once 'inc/db.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    $_SESSION['error'] = 'Please login to submit a review.';
    redirect('login.php');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('itinerary.php');
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    redirect('itinerary.php');
}

$userId = $_SESSION['user']['id'];
$bookingId = intval($_POST['booking_id'] ?? 0);
$packageId = intval($_POST['package_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

// Validate inputs
if ($bookingId <= 0 || $packageId <= 0) {
    $_SESSION['error'] = 'Invalid booking or package.';
    redirect('itinerary.php');
}

if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = 'Rating must be between 1 and 5 stars.';
    redirect('itinerary.php');
}

try {
    // Verify booking belongs to user and is completed
    $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ? AND package_id = ?");
    $stmt->execute([$bookingId, $userId, $packageId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $_SESSION['error'] = 'Booking not found or does not belong to you.';
        redirect('itinerary.php');
    }
    
    if ($booking['status'] !== 'Completed') {
        $_SESSION['error'] = 'You can only review completed bookings.';
        redirect('itinerary.php');
    }
    
    // Check if user already rated this package
    $stmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND package_id = ?");
    $stmt->execute([$userId, $packageId]);
    $existingRating = $stmt->fetch();
    
    if ($existingRating) {
        // Update existing rating
        $stmt = $pdo->prepare("UPDATE ratings SET rating = ?, review = ?, booking_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$rating, $review, $bookingId, $existingRating['id']]);
        $_SESSION['success'] = 'Your review has been updated successfully!';
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, package_id, booking_id, rating, review) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $packageId, $bookingId, $rating, $review]);
        $_SESSION['success'] = 'Thank you for your review!';
    }
    
} catch (Exception $e) {
    error_log("Rating submission error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to submit review. Please try again.';
}

redirect('itinerary.php');
