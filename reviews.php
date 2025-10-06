<?php
/**
 * All Reviews Page
 * Shows all ratings and reviews across all packages
 */

require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/pagination.php';

// Get filter parameters
$filter_rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$filter_package = isset($_GET['package']) ? intval($_GET['package']) : 0;

// Build query
$where_conditions = [];
$params = [];

if ($filter_rating > 0 && $filter_rating <= 5) {
    $where_conditions[] = "r.rating = ?";
    $params[] = $filter_rating;
}

if ($filter_package > 0) {
    $where_conditions[] = "r.package_id = ?";
    $params[] = $filter_package;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Server-side pagination (15 per page) via helper
list($page, $perPage, $offset) = pagination_get_page_and_size(15);

// Count total reviews for current filters
$countSql = "SELECT COUNT(*) FROM ratings r JOIN users u ON u.id = r.user_id JOIN packages p ON p.id = r.package_id {$where_clause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Fetch page of reviews with package and user info (positional placeholders)
$stmt = $pdo->prepare("\n    SELECT r.*, \n           u.fullname, u.name,\n           p.title as package_title, p.id as package_id, p.image_url, p.image\n    FROM ratings r\n    JOIN users u ON u.id = r.user_id\n    JOIN packages p ON p.id = r.package_id\n    {$where_clause}\n    ORDER BY r.created_at DESC\n    LIMIT ? OFFSET ?\n");
foreach ($params as $idx => $val) { $stmt->bindValue($idx+1, $val); }
$stmt->bindValue(count($params)+1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Get all packages for filter dropdown
$packages_stmt = $pdo->query("SELECT id, title FROM packages ORDER BY title ASC");
$all_packages = $packages_stmt->fetchAll();

// Calculate overall statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM ratings
");
$stats = $stats_stmt->fetch();

// Helper function for package image
function review_package_image($review) {
    if (!empty($review['image_url'])) {
        return htmlspecialchars($review['image_url']);
    }
    if (!empty($review['image'])) {
        if (preg_match('/^https?:\/\//i', $review['image'])) {
            return htmlspecialchars($review['image']);
        }
        return upload($review['image']);
    }
    return 'https://via.placeholder.com/100x100/0d6efd/ffffff?text=' . urlencode(substr($review['package_title'], 0, 1));
}

$page_title = 'Customer Reviews - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="bi bi-chat-quote-fill me-2"></i>Customer Reviews</h1>
            <p class="text-muted">See what our travelers are saying about their experiences</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('packages.php') ?>" class="btn btn-primary">
                <i class="bi bi-box me-2"></i>Browse Packages
            </a>
        </div>
    </div>

    <!-- Statistics Card -->
    <?php if ($stats['total_reviews'] > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center border-end">
                    <div class="display-3 fw-bold text-warning"><?= number_format($stats['avg_rating'], 1) ?></div>
                    <div class="text-warning mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= round($stats['avg_rating']) ? '-fill' : '' ?> fs-5"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="text-muted">Based on <?= $stats['total_reviews'] ?> reviews</div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <?php 
                        $star_labels = [5 => 'five_star', 4 => 'four_star', 3 => 'three_star', 2 => 'two_star', 1 => 'one_star'];
                        foreach ($star_labels as $stars => $key):
                            $count = $stats[$key];
                            $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                        ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <span class="me-2" style="width: 60px;"><?= $stars ?> stars</span>
                                <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                    <div class="progress-bar bg-warning" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <span class="text-muted" style="width: 40px;"><?= $count ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-star me-1"></i>Filter by Rating</label>
                    <select name="rating" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Ratings</option>
                        <option value="5" <?= $filter_rating == 5 ? 'selected' : '' ?>>5 Stars Only</option>
                        <option value="4" <?= $filter_rating == 4 ? 'selected' : '' ?>>4 Stars Only</option>
                        <option value="3" <?= $filter_rating == 3 ? 'selected' : '' ?>>3 Stars Only</option>
                        <option value="2" <?= $filter_rating == 2 ? 'selected' : '' ?>>2 Stars Only</option>
                        <option value="1" <?= $filter_rating == 1 ? 'selected' : '' ?>>1 Star Only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-box me-1"></i>Filter by Package</label>
                    <select name="package" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Packages</option>
                        <?php foreach ($all_packages as $pkg): ?>
                            <option value="<?= $pkg['id'] ?>" <?= $filter_package == $pkg['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pkg['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <?php if ($filter_rating > 0 || $filter_package > 0): ?>
                        <a href="reviews.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Reviews List -->
    <?php if (empty($reviews)): ?>
        <div class="text-center py-5">
            <i class="bi bi-chat-quote text-muted" style="font-size: 4rem;"></i>
            <h3 class="mt-4 text-muted">No Reviews Found</h3>
            <p class="text-muted">Try adjusting your filters or check back later!</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($reviews as $review): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <!-- Package Info -->
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= review_package_image($review) ?>" 
                                     class="rounded me-3" 
                                     style="width: 60px; height: 60px; object-fit: cover;"
                                     alt="<?= htmlspecialchars($review['package_title']) ?>"
                                     onerror="this.src='https://via.placeholder.com/60x60/0d6efd/ffffff?text=<?= urlencode(substr($review['package_title'], 0, 1)) ?>'">
                                <div class="flex-grow-1">
                                    <a href="<?= url('package.php?id=' . $review['package_id']) ?>" class="text-decoration-none">
                                        <h6 class="mb-0"><?= htmlspecialchars($review['package_title']) ?></h6>
                                    </a>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Content -->
                            <?php if (!empty($review['review'])): ?>
                                <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted fst-italic mb-2">No written review provided.</p>
                            <?php endif; ?>
                            
                            <!-- Admin Reply -->
                            <?php if (!empty($review['admin_reply'])): ?>
                                <div class="alert alert-info mt-2 mb-3 py-2 px-3">
                                    <strong><i class="bi bi-reply me-1"></i>Admin Response:</strong>
                                    <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Reviewer Info -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <div>
                                    <i class="bi bi-person-circle me-1"></i>
                                    <strong><?= htmlspecialchars($review['fullname'] ?? $review['name'] ?? 'Anonymous') ?></strong>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <?= pagination_render_controls($page, $totalPages, $_GET) ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'inc/footer.php'; ?>
