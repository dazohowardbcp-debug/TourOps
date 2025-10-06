<?php
/**
 * My Bookings Page (Itinerary)
 * Shows all user bookings with ability to cancel and leave reviews
 */

require_once 'inc/config.php';
require_once 'inc/db.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    redirect('login.php');
}

$userId = $_SESSION['user']['id'];

// Fetch all bookings for this user
$stmt = $pdo->prepare("
    SELECT b.*, p.title, p.image, p.image_url, p.days, p.location,
           r.id as has_rating, r.rating, r.review
    FROM bookings b
    JOIN packages p ON p.id = b.package_id
    LEFT JOIN ratings r ON r.booking_id = b.id AND r.user_id = ?
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$userId, $userId]);
$bookings = $stmt->fetchAll();

// Helper to get package image
function booking_image($booking) {
    if (!empty($booking['image_url'])) {
        return htmlspecialchars($booking['image_url']);
    }
    if (!empty($booking['image'])) {
        if (preg_match('/^https?:\/\//i', $booking['image'])) {
            return htmlspecialchars($booking['image']);
        }
        return upload($booking['image']);
    }
    return 'https://via.placeholder.com/300x200/0d6efd/ffffff?text=' . urlencode($booking['title']);
}

$page_title = 'My Bookings - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="bi bi-calendar-check me-2"></i>My Bookings</h1>
            <p class="text-muted">View and manage your tour bookings</p>
        </div>
        <a href="<?= url('packages.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Book New Tour
        </a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-4 text-muted">No Bookings Yet</h3>
            <p class="text-muted">Start exploring our amazing tour packages!</p>
            <a href="<?= url('packages.php') ?>" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-box me-2"></i>Browse Packages
            </a>
        </div>
    <?php else: ?>
        <div class="row" data-see-more="15">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?= booking_image($booking) ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: cover;"
                             alt="<?= htmlspecialchars($booking['title']) ?>"
                             onerror="this.src='https://via.placeholder.com/300x200/0d6efd/ffffff?text=<?= urlencode($booking['title']) ?>'">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($booking['title']) ?></h5>
                            
                            <div class="mb-2">
                                <span class="badge bg-<?= $booking['status'] === 'Confirmed' ? 'success' : ($booking['status'] === 'Pending' ? 'warning' : ($booking['status'] === 'Cancelled' ? 'danger' : 'secondary')) ?>">
                                    <?= htmlspecialchars($booking['status']) ?>
                                </span>
                                <span class="badge bg-<?= $booking['payment_status'] === 'Paid' ? 'success' : ($booking['payment_status'] === 'Partial' ? 'info' : 'warning') ?>">
                                    <?= htmlspecialchars($booking['payment_status']) ?>
                                </span>
                            </div>

                            <ul class="list-unstyled small">
                                <li><i class="bi bi-calendar me-2"></i><strong>Travel Date:</strong> <?= date('M j, Y', strtotime($booking['travel_date'])) ?></li>
                                <li><i class="bi bi-people me-2"></i><strong>Passengers:</strong> <?= intval($booking['pax']) ?></li>
                                <li><i class="bi bi-currency-dollar me-2"></i><strong>Total:</strong> ₱<?= number_format($booking['total'], 0) ?></li>
                                <li><i class="bi bi-clock me-2"></i><strong>Booked:</strong> <?= date('M j, Y', strtotime($booking['created_at'])) ?></li>
                            </ul>

                            <?php if (!empty($booking['special_requests'])): ?>
                                <div class="alert alert-info py-2 px-3 small mb-2">
                                    <i class="bi bi-star me-1"></i><strong>Special Requests:</strong><br>
                                    <?= htmlspecialchars($booking['special_requests']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2 mt-3">
                                <a href="<?= url('package.php?id=' . $booking['package_id']) ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>View Package
                                </a>
                                
                                <?php if ($booking['status'] === 'Completed' && empty($booking['has_rating'])): ?>
                                    <button class="btn btn-warning btn-sm" onclick="openRatingModal(<?= $booking['id'] ?>, <?= $booking['package_id'] ?>, '<?= htmlspecialchars($booking['title'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-star me-1"></i>Leave Review
                                    </button>
                                <?php elseif (!empty($booking['has_rating'])): ?>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="bi bi-check-circle me-1"></i>Reviewed (<?= $booking['rating'] ?>★)
                                    </button>
                                <?php endif; ?>

                                <?php if ($booking['status'] === 'Pending' || $booking['status'] === 'Confirmed'): ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="cancelBooking(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['title'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-x-circle me-1"></i>Cancel Booking
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-star me-2"></i>Leave a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="submit_rating.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="rating_booking_id">
                    <input type="hidden" name="package_id" id="rating_package_id">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <h6 id="rating_package_title" class="mb-3"></h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Rating *</label>
                        <div class="rating-stars" id="ratingStars">
                            <i class="bi bi-star fs-2" data-rating="1"></i>
                            <i class="bi bi-star fs-2" data-rating="2"></i>
                            <i class="bi bi-star fs-2" data-rating="3"></i>
                            <i class="bi bi-star fs-2" data-rating="4"></i>
                            <i class="bi bi-star fs-2" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="rating_value" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Review (Optional)</label>
                        <textarea name="review" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-stars i {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}
.rating-stars i:hover,
.rating-stars i.active {
    color: #ffc107;
}
</style>

<script>
// Rating stars functionality
const stars = document.querySelectorAll('#ratingStars i');
const ratingInput = document.getElementById('rating_value');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        ratingInput.value = rating;
        
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill', 'active');
            } else {
                s.classList.remove('bi-star-fill', 'active');
                s.classList.add('bi-star');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = this.getAttribute('data-rating');
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
});

document.getElementById('ratingStars').addEventListener('mouseleave', function() {
    const currentRating = ratingInput.value;
    stars.forEach((s, index) => {
        if (index < currentRating) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
});

// Open rating modal
function openRatingModal(bookingId, packageId, packageTitle) {
    document.getElementById('rating_booking_id').value = bookingId;
    document.getElementById('rating_package_id').value = packageId;
    document.getElementById('rating_package_title').textContent = packageTitle;
    
    // Reset stars
    stars.forEach(s => {
        s.classList.remove('bi-star-fill', 'active');
        s.classList.add('bi-star');
    });
    ratingInput.value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
    modal.show();
}

// Cancel booking
async function cancelBooking(bookingId, packageTitle) {
    if (!confirm(`Are you sure you want to cancel your booking for "${packageTitle}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch('<?= url('cancel_booking.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({ booking_id: bookingId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to cancel booking. Please try again.');
    }
}
</script>

<?php include 'inc/footer.php'; ?>