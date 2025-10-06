<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/pagination.php';

// check if user is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// handle user operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: users.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_admin') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $current_admin = intval($_POST['current_admin'] ?? 0);
        
        // Prevent admin from removing their own admin status
        if ($user_id == ($_SESSION['user']['id'] ?? -1)) {
            $_SESSION['error'] = "You cannot modify your own admin status.";
        } else if ($user_id > 0) {
            $new_admin_status = $current_admin ? 0 : 1;
            $stm = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            $stm->execute([$new_admin_status, $user_id]);
            
            $actionMsg = $new_admin_status ? 'granted' : 'removed';
            $_SESSION['success'] = "Admin privileges {$actionMsg} successfully!";
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Prevent admin from deleting themselves
        if ($user_id == ($_SESSION['user']['id'] ?? -1)) {
            $_SESSION['error'] = "You cannot delete your own account.";
        } else if ($user_id > 0) {
            // Check if user has bookings
            $stm = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
            $stm->execute([$user_id]);
            $booking_count = (int)$stm->fetchColumn();
            
            if ($booking_count > 0) {
                $_SESSION['error'] = "Cannot delete user with existing bookings.";
            } else {
                $stm = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stm->execute([$user_id]);
                $_SESSION['success'] = "User deleted successfully!";
            }
        }
        header('Location: users.php');
        exit;
    }
}

// Pagination via helper
list($page, $perPage, $offset) = pagination_get_page_and_size(15);

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$stm = $pdo->prepare("SELECT u.*, COUNT(b.id) as booking_count 
                       FROM users u 
                       LEFT JOIN bookings b ON u.id = b.user_id 
                       GROUP BY u.id 
                       ORDER BY u.created_at DESC 
                       LIMIT :limit OFFSET :offset");
$stm->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stm->bindValue(':offset', $offset, PDO::PARAM_INT);
$stm->execute();
$users = $stm->fetchAll();

// Helper to get user display name
function getUserName($user) {
    return $user['fullname'] ?? $user['name'] ?? 'User';
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>Manage Users</h2>
    <div class="d-flex gap-2">
        <input type="text" class="form-control table-search" placeholder="Search users..." style="width: 250px;">
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

<div class="card no-hover">
    <div class="card-body">
        <div class="d-flex justify-content-end mb-2">
            <form method="get" class="d-flex align-items-center gap-2">
                <input type="hidden" name="page" value="<?= max(1, (int)($_GET['page'] ?? 1)) ?>">
                <label class="text-muted small mb-0">Page size</label>
                <select name="pageSize" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach (pagination_allowed_sizes() as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage===$size? 'selected':'' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-responsive sticky-head">
            <table class="table table-striped table-hover table-compact">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Bookings</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><span class="badge bg-secondary">#<?=$u['id']?></span></td>
                            <td>
                                <strong><?=htmlspecialchars(getUserName($u))?></strong>
                                <?php if ($u['id'] == ($_SESSION['user']['id'] ?? -1)): ?>
                                    <span class="badge bg-primary ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="bi bi-envelope me-1"></i><?=htmlspecialchars($u['email'])?>
                            </td>
                            <td>
                                <?php if ($u['is_admin']): ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-shield-check me-1"></i>Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-person me-1"></i>User
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?=$u['booking_count']?> bookings</span>
                            </td>
                            <td>
                                <small>
                                    <i class="bi bi-calendar me-1"></i><?=date('M j, Y', strtotime($u['created_at']))?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($u['id'] != ($_SESSION['user']['id'] ?? -1)): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_admin">
                                            <input type="hidden" name="user_id" value="<?=$u['id']?>">
                                            <input type="hidden" name="current_admin" value="<?=$u['is_admin']?'1':'0'?>">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-<?=$u['is_admin'] ? 'warning' : 'success'?> rounded-pill px-3" 
                                                    onclick="return confirm('<?=$u['is_admin'] ? 'Remove' : 'Grant'?> admin privileges for <?=htmlspecialchars(getUserName($u))?>?')">
                                                <i class="bi bi-<?=$u['is_admin'] ? 'shield-x' : 'shield-check'?>"></i>
                                                <?=$u['is_admin'] ? 'Remove Admin' : 'Make Admin'?>
                                            </button>
                                        </form>
                                        
                                        <?php if ((int)$u['booking_count'] === 0): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?=$u['id']?>">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                                        onclick="return confirm('Are you sure you want to delete <?=htmlspecialchars(getUserName($u))?>? This action cannot be undone.')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Current user</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_render_controls($page, $totalPages, ['pageSize' => $perPage]) ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card no-hover">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>User Management Info</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Users with bookings cannot be deleted</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Admins can manage all aspects of the system</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Regular users can only book tours and view itineraries</li>
                    <li><i class="bi bi-exclamation-triangle text-warning me-2"></i>You cannot modify your own admin status</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card no-hover">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Quick Stats</h6>
            </div>
            <div class="card-body">
                <?php
                $total_users = count($users);
                $admin_users = count(array_filter($users, function($u) { return $u['is_admin']; }));
                $regular_users = $total_users - $admin_users;
                $users_with_bookings = count(array_filter($users, function($u) { return $u['booking_count'] > 0; }));
                ?>
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary"><?=$total_users?></h4>
                        <small class="text-muted">Total Users</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-danger"><?=$admin_users?></h4>
                        <small class="text-muted">Admins</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-success"><?=$regular_users?></h4>
                        <small class="text-muted">Regular Users</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info"><?=$users_with_bookings?></h4>
                        <small class="text-muted">With Bookings</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Table search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

// "See more" for Users table (client-side reveal)
// Generic see-more handled by uiEnhancer.initSeeMore() in main.js
</script>

<?php include 'footer.php'; ?>