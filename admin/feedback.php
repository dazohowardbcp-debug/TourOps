<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/pagination.php';

// Check if logged in AND is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF protection
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: feedback.php');
        exit;
    }
    if ($_POST['action'] === 'delete_review') {
        $review_id = intval($_POST['review_id'] ?? 0);
        if ($review_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM ratings WHERE id = ?");
                $stmt->execute([$review_id]);
                $_SESSION['success'] = "Review deleted successfully!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to delete review: " . $e->getMessage();
            }
        }
        header('Location: feedback.php');
        exit;
    }
    
    if ($_POST['action'] === 'add_reply') {
        $review_id = intval($_POST['review_id'] ?? 0);
        $admin_reply = trim($_POST['admin_reply'] ?? '');
        
        if ($review_id > 0 && !empty($admin_reply)) {
            try {
                $stmt = $pdo->prepare("UPDATE ratings SET admin_reply = ?, admin_reply_at = NOW() WHERE id = ?");
                $stmt->execute([$admin_reply, $review_id]);
                $_SESSION['success'] = "Reply added successfully!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to add reply: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Reply cannot be empty.";
        }
        header('Location: feedback.php');
        exit;
    }
    
    // Note: Admin replies are permanent by design; no deletion endpoint provided.
}

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

// Server-side pagination (10 per page) via helper
list($page, $perPage, $offset) = pagination_get_page_and_size(10);

// Total count with same filters
$countSql = "SELECT COUNT(*) FROM ratings r JOIN users u ON u.id = r.user_id JOIN packages p ON p.id = r.package_id {$where_clause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Fetch page of filtered reviews (all positional placeholders)
$stmt = $pdo->prepare("\n    SELECT r.*, \n           u.fullname, u.name, u.email,\n           p.title as package_title, p.id as package_id\n    FROM ratings r\n    JOIN users u ON u.id = r.user_id\n    JOIN packages p ON p.id = r.package_id\n    {$where_clause}\n    ORDER BY r.created_at DESC\n    LIMIT ? OFFSET ?\n");
// Bind filter params first, then limit and offset
foreach ($params as $idx => $val) { $stmt->bindValue($idx+1, $val); }
$stmt->bindValue(count($params)+1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Get all packages for filter dropdown
$packages_stmt = $pdo->query("SELECT id, title FROM packages ORDER BY title ASC");
$all_packages = $packages_stmt->fetchAll();

// Calculate statistics
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

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-chat-quote me-2"></i>Manage Feedback & Reviews</h2>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white no-hover">
            <div class="card-body">
                <h6 class="card-title">Total Reviews</h6>
                <h2 class="mb-0"><?=$stats['total_reviews']?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white no-hover">
            <div class="card-body">
                <h6 class="card-title">Average Rating</h6>
                <h2 class="mb-0"><?=number_format($stats['avg_rating'], 1)?> ⭐</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white no-hover">
            <div class="card-body">
                <h6 class="card-title">5-Star Reviews</h6>
                <h2 class="mb-0"><?=$stats['five_star']?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white no-hover">
            <div class="card-body">
                <h6 class="card-title">Low Ratings (1-2★)</h6>
                <h2 class="mb-0"><?=$stats['one_star'] + $stats['two_star']?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card no-hover mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-5">
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
            <div class="col-md-5">
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
            <div class="col-md-2 d-flex align-items-end">
                <?php if ($filter_rating > 0 || $filter_package > 0): ?>
                    <a href="feedback.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Reviews Table -->
<div class="card no-hover">
    <div class="card-body">
        <?php if (empty($reviews)): ?>
            <div class="text-center py-5">
                <i class="bi bi-chat-quote text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">No reviews found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive sticky-head">
                <table class="table table-hover table-compact">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Package</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Review & Reply</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?=$review['id']?></span></td>
                                <td>
                                    <strong><?=htmlspecialchars($review['package_title'])?></strong><br>
                                    <small class="text-muted">ID: <?=$review['package_id']?></small>
                                </td>
                                <td>
                                    <strong><?=htmlspecialchars($review['fullname'] ?? $review['name'] ?? 'Anonymous')?></strong><br>
                                    <small class="text-muted"><?=htmlspecialchars($review['email'])?></small>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?=$review['rating']?>/5</small>
                                </td>
                                <td style="max-width: 400px;">
                                    <!-- Customer Review -->
                                    <div class="mb-2">
                                        <strong class="text-primary">Customer:</strong>
                                        <?php if (!empty($review['review'])): ?>
                                            <div class="text-truncate" title="<?=htmlspecialchars($review['review'])?>">
                                                <?=htmlspecialchars(substr($review['review'], 0, 80))?>
                                                <?= strlen($review['review']) > 80 ? '...' : '' ?>
                                            </div>
                                            <button class="btn btn-sm btn-link p-0" onclick="viewFullReview('<?=htmlspecialchars($review['review'], ENT_QUOTES)?>')">
                                                View Full
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No written review</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Admin Reply -->
                                    <?php if (!empty($review['admin_reply'])): ?>
                                        <div class="alert alert-success py-2 px-2 mb-1">
                                            <strong class="text-success">Admin Reply:</strong>
                                            <div><?=nl2br(htmlspecialchars($review['admin_reply']))?></div>
                                            <small class="text-muted">Replied: <?=date('M j, Y g:i A', strtotime($review['admin_reply_at']))?></small>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openReplyModal(<?=$review['id']?>, '<?=htmlspecialchars($review['package_title'], ENT_QUOTES)?>')">
                                            <i class="bi bi-reply me-1"></i>Add Reply
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?=date('M j, Y', strtotime($review['created_at']))?></small><br>
                                    <small class="text-muted"><?=date('g:i A', strtotime($review['created_at']))?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteReview(<?=$review['id']?>, '<?=htmlspecialchars($review['package_title'], ENT_QUOTES)?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?= pagination_render_inline_row(7, $page, $totalPages, $_GET) ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form method="post" id="deleteReviewForm" style="display: none;">
    <input type="hidden" name="action" value="delete_review">
    <input type="hidden" name="review_id" id="delete_review_id">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<!-- Delete Reply Form (hidden) -->
<form method="post" id="deleteReplyForm" style="display: none;">
    <input type="hidden" name="action" value="delete_reply">
    <input type="hidden" name="review_id" id="delete_reply_id">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<!-- View Full Review Modal -->
<div class="modal fade" id="viewReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-chat-quote me-2"></i>Full Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="fullReviewText" style="white-space: pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-reply me-2"></i>Reply to Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="add_reply">
                    <input type="hidden" name="review_id" id="reply_review_id">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Replying to review for: <strong id="reply_package_title"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Reply *</label>
                        <textarea name="admin_reply" class="form-control" rows="4" placeholder="Thank you for your feedback..." required></textarea>
                        <small class="text-muted">This reply will be visible to all users viewing this package.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Post Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// View full review
function viewFullReview(reviewText) {
    document.getElementById('fullReviewText').textContent = reviewText;
    const modal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
    modal.show();
}

// Open reply modal
function openReplyModal(reviewId, packageTitle) {
    document.getElementById('reply_review_id').value = reviewId;
    document.getElementById('reply_package_title').textContent = packageTitle;
    const modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
}

// Delete review
function deleteReview(reviewId, packageTitle) {
    if (confirm(`Are you sure you want to delete this review for "${packageTitle}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('delete_review_id').value = reviewId;
        document.getElementById('deleteReviewForm').submit();
    }
}

// Delete reply
function deleteReply(reviewId) {
    if (confirm('Are you sure you want to remove your reply?\n\nThis action cannot be undone.')) {
        document.getElementById('delete_reply_id').value = reviewId;
        document.getElementById('deleteReplyForm').submit();
    }
}
</script>

<?php include 'footer.php'; ?>
