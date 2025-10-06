<?php
require_once '../inc/config.php';
require_once '../inc/db.php';

// Check if logged in AND is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// Get some basic stats
$stm = $pdo->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stm->fetch()['total'];

$stm = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stm->fetch()['total'];

$stm = $pdo->query("SELECT COUNT(*) as total FROM packages");
$total_packages = $stm->fetch()['total'];

$stm = $pdo->query("SELECT COUNT(*) as pending FROM bookings WHERE status = 'Pending'");
$pending_bookings = $stm->fetch()['pending'];

$stm = $pdo->query("SELECT COUNT(*) as paid FROM bookings WHERE payment_status = 'Paid'");
$paid_bookings = $stm->fetch()['paid'];

include 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white no-hover">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Bookings</h6>
                        <h2 class="mb-0"><?=$total_bookings?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white no-hover">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Bookings</h6>
                        <h2 class="mb-0"><?=$pending_bookings?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white no-hover">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Paid Bookings</h6>
                        <h2 class="mb-0"><?=$paid_bookings?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white no-hover">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0"><?=$total_users?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card no-hover">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="bookings.php" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-2"></i>Manage Bookings
                    </a>
                    <a href="packages.php" class="btn btn-success">
                        <i class="bi bi-box me-2"></i>Manage Packages
                    </a>
                    <a href="users.php" class="btn btn-info">
                        <i class="bi bi-people me-2"></i>Manage Users
                    </a>
                    <a href="feedback.php" class="btn btn-warning">
                        <i class="bi bi-chat-quote me-2"></i>Manage Feedback
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card no-hover">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php
                $stm = $pdo->query("SELECT b.*, p.title FROM bookings b JOIN packages p ON p.id = b.package_id ORDER BY b.created_at DESC LIMIT 5");
                $recent_bookings = $stm->fetchAll();
                ?>
                <?php if (empty($recent_bookings)): ?>
                    <p class="text-muted">No recent bookings</p>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $b): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong><?=htmlspecialchars($b['title'])?></strong><br>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i><?=htmlspecialchars($b['guest_name'])?> • 
                                    <i class="bi bi-calendar me-1"></i><?=date('M j', strtotime($b['created_at']))?>
                                </small>
                            </div>
                            <span class="badge bg-<?=$b['status'] === 'Confirmed' ? 'success' : 'warning'?>">
                                <?=$b['status']?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
