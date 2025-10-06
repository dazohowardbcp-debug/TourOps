<?php
require_once 'inc/config.php';
require_once 'inc/db.php';

// Get package ID
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($package_id <= 0) {
    redirect('packages.php');
}

// Fetch package
$stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->execute([$package_id]);
$pkg = $stmt->fetch();

if (!$pkg) {
    echo "<div class='alert alert-danger'>Package not found.</div>";
    include 'inc/footer.php';
    exit;
}

// Fetch ratings and reviews for this package
$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
    FROM ratings 
    WHERE package_id = ?
");
$stmt->execute([$package_id]);
$rating_data = $stmt->fetch();

// Fetch recent reviews with user names and admin replies
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname, u.name 
    FROM ratings r 
    JOIN users u ON u.id = r.user_id 
    WHERE r.package_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute([$package_id]);
$reviews = $stmt->fetchAll();

$page_title = $pkg['title'] . " - " . SITE_NAME;

// Helper to handle both external and local images
function package_image($pkg) {
    if (!empty($pkg['image_url'])) {
        return htmlspecialchars($pkg['image_url']);
    }
    if (!empty($pkg['image'])) {
        if (preg_match('/^https?:\/\//i', $pkg['image'])) {
            return htmlspecialchars($pkg['image']);
        }
        return upload($pkg['image']);
    }
    // Fallback placeholder
    return 'https://via.placeholder.com/800x400?text=' . urlencode($pkg['title']);
}

include 'inc/header.php';
?>

<div class="container my-5">
  <div class="row align-items-stretch">
    <!-- Package Info -->
    <div class="col-md-7 d-flex">
      <div class="card h-100 w-100 shadow-sm">
        <div class="card-body">
          <!-- Package Image with fallback -->
          <img src="<?= package_image($pkg) ?>" 
               class="img-fluid rounded mb-3 package-hero" 
               alt="<?= htmlspecialchars($pkg['title']) ?>"
               style="max-height: 400px; width: 100%; object-fit: cover;">

          <h2><?= htmlspecialchars($pkg['title']) ?></h2>
          
          <?php if ($rating_data['review_count'] > 0): ?>
            <div class="mb-2">
              <span class="text-warning">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star<?= $i <= round($rating_data['avg_rating']) ? '-fill' : '' ?>"></i>
                <?php endfor; ?>
              </span>
              <span class="text-muted ms-2">
                <?= number_format($rating_data['avg_rating'], 1) ?> (<?= $rating_data['review_count'] ?> <?= $rating_data['review_count'] == 1 ? 'review' : 'reviews' ?>)
              </span>
            </div>
          <?php endif; ?>
          
          <p class="lead"><?= htmlspecialchars($pkg['description'] ?? '') ?></p>
          <p class="text-muted"><?= intval($pkg['days']) ?> days • ₱<?= number_format($pkg['price'], 0) ?></p>

          <?php if (!empty($pkg['highlights'])): ?>
            <h5>Highlights</h5>
            <ul>
              <?php foreach (explode(',', $pkg['highlights']) as $h): ?>
                <li><?= htmlspecialchars(trim($h)) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <!-- View Itinerary Button -->
          <a href="itinerary.php?id=<?= $pkg['id'] ?>" class="btn btn-primary mt-3">
            View Itinerary
          </a>
        </div>
      </div>
    </div>

    <!-- Booking Form -->
    <div class="col-md-5 d-flex">
      <div class="card h-100 w-100 shadow-sm">
        <div class="card-body">
          <h5>Book this package</h5>
          <form action="book_process.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">

            <div class="mb-2">
              <label class="form-label">Your name</label>
              <input required name="guest_name" class="form-control">
            </div>

            <div class="mb-2">
              <label class="form-label">Email</label>
              <input required name="guest_email" type="email" class="form-control">
            </div>

            <div class="mb-2">
              <label class="form-label">Travel Date</label>
              <input required name="travel_date" type="date" 
                     class="form-control" 
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>

            <div class="mb-2">
              <label class="form-label">Pax (number of people)</label>
              <input required name="pax" type="number" min="1" max="50" step="1" 
                     value="1" class="form-control" inputmode="numeric">
            </div>

            <div class="mb-2">
              <label class="form-label">Special Requests (optional)</label>
              <textarea name="special_requests" class="form-control" rows="2"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Additional Notes (optional)</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="d-grid">
              <button class="btn btn-success" type="submit">
                Confirm booking — ₱<?= number_format($pkg['price'], 0) ?>/pax
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Ratings and Reviews Section -->
  <div class="row mt-5">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Ratings & Reviews</h4>
            <?php if ($rating_data['review_count'] > 0): ?>
              <div class="text-end">
                <div class="h3 mb-0">
                  <?= number_format($rating_data['avg_rating'], 1) ?> 
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
                <small class="text-muted"><?= $rating_data['review_count'] ?> <?= $rating_data['review_count'] == 1 ? 'review' : 'reviews' ?></small>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($reviews)): ?>
            <div class="text-center py-5">
              <i class="bi bi-chat-quote text-muted" style="font-size: 3rem;"></i>
              <p class="text-muted mt-3">No reviews yet. Be the first to review this package!</p>
            </div>
          <?php else: ?>
            <div class="row">
              <?php foreach ($reviews as $review): ?>
                <div class="col-md-6 mb-3">
                  <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <div class="fw-bold"><?= htmlspecialchars($review['fullname'] ?? $review['name'] ?? 'Anonymous') ?></div>
                        <div class="text-warning">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                          <?php endfor; ?>
                        </div>
                      </div>
                      <small class="text-muted"><?= date('M j, Y', strtotime($review['created_at'])) ?></small>
                    </div>
                    
                    <?php if (!empty($review['review'])): ?>
                      <p class="mb-2 text-muted"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['admin_reply'])): ?>
                      <div class="alert alert-info mt-2 mb-0 py-2 px-3">
                        <strong><i class="bi bi-reply me-1"></i>Admin Response:</strong>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'inc/footer.php'; ?>
