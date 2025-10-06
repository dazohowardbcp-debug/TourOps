<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
require_once 'inc/db.php';

// Check if user is logged in
if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
    redirect('login.php');
}

// Ensure we have the user array for compatibility
if (empty($_SESSION['user']) && !empty($_SESSION['user_id'])) {
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['username'] ?? 'User',
        'fullname' => $_SESSION['username'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'is_admin' => $_SESSION['is_admin'] ?? false,
        'profile_image' => $_SESSION['profile_image'] ?? null,
        'created_at' => $_SESSION['created_at'] ?? date('Y-m-d H:i:s')
    ];
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? 'User';

// Get user's last 3 bookings
try {
    $stmt = $pdo->prepare("\n        SELECT b.*, p.title as package_title, p.image_url, p.image, p.location, p.duration, p.days\n        FROM bookings b\n        JOIN packages p ON b.package_id = p.id\n        WHERE b.user_id = ?\n        ORDER BY b.created_at DESC\n        LIMIT 3\n    ");
    $stmt->execute([$user_id]);
    $recent_bookings = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recent_bookings = [];
}

// Get total bookings count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_bookings = $stmt->fetch()['total'];
} catch (Exception $e) {
    $total_bookings = 0;
}

// Set page variables
$page_title = 'Dashboard - ' . SITE_NAME;
$GLOBALS['BODY_CLASS'] = '';

include 'inc/header.php';
?>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">
                                <i class="bi bi-house-door me-2"></i>
                                Welcome back, <?= htmlspecialchars($user_name) ?>!
                            </h1>
                            <p class="mb-0 opacity-75">
                                Ready for your next adventure? Explore our latest packages and manage your bookings.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <?php if (!empty($_SESSION['user']['profile_image'])): 
                                    // Handle profile image URL - check if it's already a full path
                                    $profileImg = $_SESSION['user']['profile_image'];
                                    if (strpos($profileImg, 'assets/uploads/') === 0) {
                                        // It's already a relative path, use url() helper
                                        $profileImgUrl = url($profileImg);
                                    } elseif (preg_match('/^https?:\/\//i', $profileImg)) {
                                        // It's already a full URL
                                        $profileImgUrl = $profileImg;
                                    } else {
                                        // It's just a filename, use upload() helper
                                        $profileImgUrl = upload($profileImg);
                                    }
                                ?>
                                    <img src="<?= htmlspecialchars($profileImgUrl) ?>" 
                                         class="rounded-circle me-3" 
                                         style="width: 60px; height: 60px; object-fit: cover;" 
                                         alt="Profile">
                                <?php else: ?>
                                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 60px; height: 60px;">
                                        <span class="text-white fw-bold fs-4">
                                            <?= strtoupper(substr($user_name, 0, 2)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($user_name) ?></div>
                                    <small class="opacity-75">Member since <?= date('M Y', strtotime($_SESSION['user']['created_at'] ?? 'now')) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h5 mb-3">
                <i class="bi bi-lightning me-2"></i>Quick Actions
            </h2>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= url('packages.php') ?>" class="card text-decoration-none border-0 shadow-sm h-100 no-hover">
                <div class="card-body text-center p-4">
                    <i class="bi bi-box text-primary" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3 mb-2">Browse Packages</h5>
                    <p class="text-muted small mb-0">Discover amazing travel destinations</p>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= url('itinerary.php') ?>" class="card text-decoration-none border-0 shadow-sm h-100 no-hover">
                <div class="card-body text-center p-4">
                    <i class="bi bi-calendar-check text-success" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3 mb-2">My Bookings</h5>
                    <p class="text-muted small mb-0">View and manage your trips</p>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= url('my_reviews.php') ?>" class="card text-decoration-none border-0 shadow-sm h-100 no-hover">
                <div class="card-body text-center p-4">
                    <i class="bi bi-star-fill text-warning" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3 mb-2">Your Reviews</h5>
                    <p class="text-muted small mb-0">See your reviews and ratings</p>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="<?= url('profile.php') ?>" class="card text-decoration-none border-0 shadow-sm h-100 no-hover">
                <div class="card-body text-center p-4">
                    <i class="bi bi-person text-info" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3 mb-2">Profile</h5>
                    <p class="text-muted small mb-0">Manage your account settings</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm no-hover">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">
                            <i class="bi bi-clock-history me-2"></i>Recent Bookings
                        </h2>
                        <a href="<?= url('itinerary.php') ?>" class="btn btn-outline-primary btn-sm">
                            View All (<?= $total_bookings ?>)
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_bookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No bookings yet</h5>
                            <p class="text-muted">Start your journey by exploring our packages!</p>
                            <a href="<?= url('packages.php') ?>" class="btn btn-primary">
                                <i class="bi bi-box me-2"></i>Browse Packages
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Package</th>
                                        <th>Travel Date</th>
                                        <th>Pax</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                        $pkgImg = '';
                                                        if (!empty($booking['image_url'])) {
                                                            $pkgImg = $booking['image_url'];
                                                        } elseif (!empty($booking['image'])) {
                                                            if (preg_match('/^https?:\/\//i', $booking['image'])) {
                                                                $pkgImg = $booking['image'];
                                                            } else {
                                                                $pkgImg = upload($booking['image']);
                                                            }
                                                        }
                                                    ?>
                                                    <div class="thumb-box-50 me-3">
                                                        <?php if (!empty($pkgImg)): ?>
                                                            <img src="<?= htmlspecialchars($pkgImg) ?>" class="img-thumb-50" alt="<?= htmlspecialchars($booking['package_title']) ?>">
                                                        <?php else: ?>
                                                            <i class="bi bi-image text-muted"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($booking['package_title']) ?></div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($booking['location'] ?? 'N/A') ?> • <?= htmlspecialchars($booking['duration'] ?? $booking['days'] . ' Days') ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= date('M d, Y', strtotime($booking['travel_date'])) ?></div>
                                                <small class="text-muted">
                                                    <?= date('D', strtotime($booking['travel_date'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= $booking['pax'] ?> <?= $booking['pax'] == 1 ? 'person' : 'people' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary">
                                                    ₱<?= number_format($booking['total'], 2) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = match($booking['status']) {
                                                    'Completed' => 'bg-success',
                                                    'Cancelled' => 'bg-danger',
                                                    'Confirmed' => 'bg-primary',
                                                    default => 'bg-warning'
                                                };
                                                ?>
                                                <span class="badge <?= $status_class ?>">
                                                    <?= htmlspecialchars($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= url('package.php?id=' . $booking['package_id']) ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="View Package">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($booking['status'] === 'Pending'): ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger" 
                                                                onclick="cancelBooking(<?= $booking['id'] ?>)" 
                                                                title="Cancel Booking">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        fetch('<?= url('cancel_booking.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ booking_id: bookingId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel booking');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the booking');
        });
    }
}
</script>

<?php include 'inc/footer.php'; ?>
