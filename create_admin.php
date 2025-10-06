<?php
/**
 * Create Admin User Script
 * Run this once to create an admin account, then delete this file
 */

require_once 'inc/config.php';
require_once 'inc/db.php';

// Only allow from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('This script can only be run from localhost');
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? 'Admin User');
    
    if ($email && $password) {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing user to admin
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = 1, fullname = ? WHERE email = ?");
                $stmt->execute([$hash, $fullname, $email]);
                $message = "User updated to admin successfully!";
                $success = true;
            } else {
                // Create new admin user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, is_admin) VALUES (?, ?, ?, 1)");
                $stmt->execute([$fullname, $email, $hash]);
                $message = "Admin user created successfully!";
                $success = true;
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Please fill in all fields";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - TourOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="bi bi-shield-exclamation me-2"></i>Create Admin User</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Security Warning:</strong> Delete this file after creating your admin account!
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $success ? 'check-circle' : 'x-circle' ?> me-2"></i>
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                                </a>
                                <a href="admin/" class="btn btn-success">
                                    <i class="bi bi-shield-check me-2"></i>Go to Admin Panel
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="fullname" class="form-control" value="Admin User" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="admin@example.com" required>
                                    <small class="text-muted">This will be your login email</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-shield-plus me-2"></i>Create Admin Account
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <hr>
                        <div class="text-muted small">
                            <p class="mb-1"><strong>Existing Admin Accounts:</strong></p>
                            <?php
                            $stmt = $pdo->query("SELECT id, fullname, email FROM users WHERE is_admin = 1");
                            $admins = $stmt->fetchAll();
                            if ($admins):
                                foreach ($admins as $admin):
                            ?>
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($admin['fullname'] ?? 'No name') ?></span>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($admin['email']) ?></span>
                                </div>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <p class="text-muted">No admin accounts found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
