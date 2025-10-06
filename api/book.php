<?php
require_once '../inc/config.php';
require_once '../inc/db.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['package_id', 'guest_name', 'guest_email', 'travel_date', 'pax'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Extract and validate data
    $package_id = intval($_POST['package_id']);
    $guest_name = trim($_POST['guest_name']);
    $guest_email = trim($_POST['guest_email']);
    $travel_date = $_POST['travel_date'];
    $pax = intval($_POST['pax']);
    $special_requests = trim($_POST['special_requests'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate email
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Validate travel date (must be future)
    $selected_date = new DateTime($travel_date);
    $tomorrow = new DateTime('tomorrow');
    if ($selected_date < $tomorrow) {
        echo json_encode(['success' => false, 'message' => 'Travel date must be at least tomorrow']);
        exit;
    }
    
    // Validate pax (1-50)
    if ($pax < 1 || $pax > 50) {
        echo json_encode(['success' => false, 'message' => 'Number of passengers must be between 1 and 50']);
        exit;
    }
    
    // Get package details
    $package_stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $package_stmt->execute([$package_id]);
    $package = $package_stmt->fetch();
    
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }
    
    // Calculate total
    $total = $package['price'] * $pax;
    
    // Get user ID if logged in
    $user_id = null;
    if (!empty($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    }
    
    // Check for duplicate booking (same user/email, package, travel date)
    $is_duplicate = false;
    if (!empty($_SESSION['user']['is_admin']) && ($_POST['override_duplicate'] ?? 0) == 1) {
        // Admin override: skip duplicate check
        $is_duplicate = false;
    } else {
        $check_duplicate_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = ? AND travel_date = ? AND (user_id = ? OR guest_email = ?)";
        $check_stmt = $pdo->prepare($check_duplicate_sql);
        $check_stmt->execute([$package_id, $travel_date, $user_id, $guest_email]);
        if ($check_stmt->fetchColumn() > 0) {
            $is_duplicate = true;
        }
    }
    
    if ($is_duplicate) {
        echo json_encode(['success' => false, 'message' => 'You already have a booking for this package on this date']);
        exit;
    }
    
    // Insert booking
    $insert_sql = "INSERT INTO bookings (package_id, user_id, guest_name, guest_email, travel_date, pax, total, special_requests, notes, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', NOW())";
    $insert_stmt = $pdo->prepare($insert_sql);
    $insert_stmt->execute([
        $package_id,
        $user_id,
        $guest_name,
        $guest_email,
        $travel_date,
        $pax,
        $total,
        $special_requests,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Log booking activity (only if user is logged in)
    if ($user_id) {
        try {
            $log_sql = "INSERT INTO user_logins (user_id, ip, user_agent, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $user_id,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
            error_log("Booking activity logging failed: " . $e->getMessage());
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking submitted successfully!',
        'booking_id' => $booking_id,
        'total' => $total,
        'redirect_url' => url('itinerary.php')
    ]);
    
} catch (Exception $e) {
    error_log("Booking API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => DEBUG_MODE ? $e->getMessage() : 'An error occurred while processing your booking'
    ]);
}
?>

require_once '../inc/db.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['package_id', 'guest_name', 'guest_email', 'travel_date', 'pax'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Extract and validate data
    $package_id = intval($_POST['package_id']);
    $guest_name = trim($_POST['guest_name']);
    $guest_email = trim($_POST['guest_email']);
    $travel_date = $_POST['travel_date'];
    $pax = intval($_POST['pax']);
    $special_requests = trim($_POST['special_requests'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate email
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Validate travel date (must be future)
    $selected_date = new DateTime($travel_date);
    $tomorrow = new DateTime('tomorrow');
    if ($selected_date < $tomorrow) {
        echo json_encode(['success' => false, 'message' => 'Travel date must be at least tomorrow']);
        exit;
    }
    
    // Validate pax (1-50)
    if ($pax < 1 || $pax > 50) {
        echo json_encode(['success' => false, 'message' => 'Number of passengers must be between 1 and 50']);
        exit;
    }
    
    // Get package details
    $package_stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $package_stmt->execute([$package_id]);
    $package = $package_stmt->fetch();
    
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }
    
    // Calculate total
    $total = $package['price'] * $pax;
    
    // Get user ID if logged in
    $user_id = null;
    if (!empty($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    }
    
    // Check for duplicate booking (same user/email, package, travel date)
    $is_duplicate = false;
    if (!empty($_SESSION['user']['is_admin']) && ($_POST['override_duplicate'] ?? 0) == 1) {
        // Admin override: skip duplicate check
        $is_duplicate = false;
    } else {
        $check_duplicate_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = ? AND travel_date = ? AND (user_id = ? OR guest_email = ?)";
        $check_stmt = $pdo->prepare($check_duplicate_sql);
        $check_stmt->execute([$package_id, $travel_date, $user_id, $guest_email]);
        if ($check_stmt->fetchColumn() > 0) {
            $is_duplicate = true;
        }
    }
    
    if ($is_duplicate) {
        echo json_encode(['success' => false, 'message' => 'You already have a booking for this package on this date']);
        exit;
    }
    
    // Insert booking
    $insert_sql = "INSERT INTO bookings (package_id, user_id, guest_name, guest_email, travel_date, pax, total, special_requests, notes, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', NOW())";
    $insert_stmt = $pdo->prepare($insert_sql);
    $insert_stmt->execute([
        $package_id,
        $user_id,
        $guest_name,
        $guest_email,
        $travel_date,
        $pax,
        $total,
        $special_requests,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Log booking activity (only if user is logged in)
    if ($user_id) {
        try {
            $log_sql = "INSERT INTO user_logins (user_id, ip, user_agent, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $user_id,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
            error_log("Booking activity logging failed: " . $e->getMessage());
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking submitted successfully!',
        'booking_id' => $booking_id,
        'total' => $total,
        'redirect_url' => url('itinerary.php')
    ]);
    
} catch (Exception $e) {
    error_log("Booking API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => DEBUG_MODE ? $e->getMessage() : 'An error occurred while processing your booking'
    ]);
}
?>

