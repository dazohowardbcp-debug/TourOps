<?php
/**
 * My Reviews Page
 * Shows only the logged-in user's reviews, with a button to see others' reviews for the same package
 */

require_once 'inc/config.php';
require_once 'inc/db.php';

// Require login
if (empty($_SESSION['user'])) {
    $_SESSION['info'] = 'Please login to view your reviews.';
    redirect('login.php');
}

$userId = $_SESSION['user']['id'];

// Fetch current user's reviews with package info
$stmt = $pdo->prepare(
    "SELECT r.*, p.title AS package_title, p.id AS package_id, p.image_url, p.image
     FROM ratings r
     JOIN packages p ON p.id = r.package_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC"
);
$stmt->execute([$userId]);
$my_reviews = $stmt->fetchAll();

// Helper function for package image
function my_review_pkg_image($row) {
    if (!empty($row['image_url'])) return htmlspecialchars($row['image_url']);
    if (!empty($row['image'])) {
        if (preg_match('/^https?:\/\//i', $row['image'])) return htmlspecialchars($row['image']);
        return upload($row['image']);
    }
    return 'https://via.placeholder.com/100x100/0d6efd/ffffff?text=' . urlencode(substr($row['package_title'] ?? 'P', 0, 1));
}

$page_title = 'My Reviews - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="mb-1"><i class="bi bi-star-fill text-warning me-2"></i>My Reviews</h1>
      <p class="text-muted mb-0">Only reviews that you posted</p>
    </div>
    <a href="<?= url('packages.php') ?>" class="btn btn-primary"><i class="bi bi-box me-2"></i>Browse Packages</a>
  </div>

  <?php if (empty($my_reviews)): ?>
    <div class="text-center py-5">
      <i class="bi bi-chat-left-dots text-muted" style="font-size: 3rem;"></i>
      <h4 class="mt-3">You haven\'t posted any reviews yet</h4>
      <p class="text-muted">Book and complete a tour to leave a review.</p>
      <a class="btn btn-outline-primary" href="<?= url('itinerary.php') ?>"><i class="bi bi-calendar-check me-2"></i>Go to My Bookings</a>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($my_reviews as $review): ?>
        <div class="col-md-6 mb-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <!-- Package info -->
              <div class="d-flex align-items-center mb-3">
                <img src="<?= my_review_pkg_image($review) ?>" class="rounded me-3" style="width:60px;height:60px;object-fit:cover" alt="<?= htmlspecialchars($review['package_title']) ?>">
                <div class="flex-grow-1">
                  <a href="<?= url('package.php?id=' . $review['package_id']) ?>" class="text-decoration-none">
                    <h6 class="mb-1"><?= htmlspecialchars($review['package_title']) ?></h6>
                  </a>
                  <div class="text-warning">
                    <?php for ($i=1; $i<=5; $i++): ?>
                      <i class="bi bi-star<?= $i <= (int)$review['rating'] ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                    <small class="text-muted ms-2"><?= (int)$review['rating'] ?>/5</small>
                  </div>
                </div>
              </div>

              <!-- Your review -->
              <?php if (!empty($review['review'])): ?>
                <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
              <?php else: ?>
                <p class="text-muted fst-italic mb-2">No written review</p>
              <?php endif; ?>

              <!-- Admin reply (if any) -->
              <?php if (!empty($review['admin_reply'])): ?>
                <div class="alert alert-info py-2 px-3 mb-2">
                  <strong><i class="bi bi-reply me-1"></i>Admin Response:</strong>
                  <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <small class="text-muted"><i class="bi bi-calendar me-1"></i><?= date('M j, Y', strtotime($review['created_at'])) ?></small>
                <div class="btn-group btn-group-sm">
                  <a href="<?= url('reviews.php?package=' . $review['package_id']) ?>" class="btn btn-outline-secondary" title="See all reviews for this package">
                    <i class="bi bi-people me-1"></i>See others
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'inc/footer.php'; ?>
