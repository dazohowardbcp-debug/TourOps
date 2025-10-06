<?php
/**
 * Session Checker - Debug Tool
 * Shows your current session status
 * DELETE THIS FILE after debugging
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Checker - TourOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="bi bi-bug me-2"></i>Session Debug Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Debug Tool:</strong> Delete this file after debugging!
                        </div>

                        <h5><i class="bi bi-person-circle me-2"></i>Login Status</h5>
                        <?php if (empty($_SESSION['user'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle me-2"></i>
                                <strong>NOT LOGGED IN</strong>
                                <p class="mb-0 mt-2">You need to login first.</p>
                            </div>
                            <a href="login.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                            </a>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>LOGGED IN</strong>
                            </div>

                            <h5 class="mt-4"><i class="bi bi-info-circle me-2"></i>Your Session Data</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">User ID</th>
                                    <td><?= htmlspecialchars($_SESSION['user']['id'] ?? 'Not set') ?></td>
                                </tr>
                                <tr>
                                    <th>Full Name</th>
                                    <td><?= htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? 'Not set') ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($_SESSION['user']['email'] ?? 'Not set') ?></td>
                                </tr>
                                <tr>
                                    <th>Is Admin</th>
                                    <td>
                                        <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-shield-check me-1"></i>YES - You are an admin!
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-person me-1"></i>NO - Regular user
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>

                            <h5 class="mt-4"><i class="bi bi-shield-check me-2"></i>Admin Access</h5>
                            <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    You have admin privileges! You should be able to access the admin panel.
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="admin/" class="btn btn-success btn-lg">
                                        <i class="bi bi-shield-check me-2"></i>Go to Admin Panel
                                    </a>
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="bi bi-house me-2"></i>Go to Dashboard
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    You don't have admin privileges. Use <code>create_admin.php</code> to make your account an admin.
                                </div>
                                <a href="create_admin.php" class="btn btn-warning">
                                    <i class="bi bi-shield-plus me-2"></i>Make Me Admin
                                </a>
                            <?php endif; ?>

                            <hr>
                            <h5><i class="bi bi-code-square me-2"></i>Full Session Data (Debug)</h5>
                            <pre class="bg-dark text-light p-3 rounded"><code><?= htmlspecialchars(print_r($_SESSION, true)) ?></code></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
