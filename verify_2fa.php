<?php
/**
 * Two-Factor Authentication Verification Page
 * Users with 2FA enabled must enter a code sent to their email
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
require_once 'inc/db.php';

// Check if user needs 2FA verification
if (empty($_SESSION['pending_2fa_user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['pending_2fa_user_id'];

// Get user data
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['two_factor_enabled']) {
        unset($_SESSION['pending_2fa_user_id']);
        redirect('login.php');
    }
} catch (Exception $e) {
    error_log("2FA error: " . $e->getMessage());
    redirect('login.php');
}

// Handle code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    if ($code === $_SESSION['2fa_code'] && time() < $_SESSION['2fa_expires']) {
        // Code is valid, complete login
        unset($_SESSION['pending_2fa_user_id']);
        unset($_SESSION['2fa_code']);
        unset($_SESSION['2fa_expires']);
        
        // Set user session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'fullname' => $user['fullname'] ?? $user['name'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin'],
            'profile_image' => $user['profile_image']
        ];
        
        $_SESSION['success'] = 'Login successful!';
        redirect($user['is_admin'] ? 'admin/' : 'dashboard.php');
    } else {
        $_SESSION['error'] = 'Invalid or expired verification code.';
    }
}

// Generate and send code if not already sent
if (empty($_SESSION['2fa_code']) || time() >= $_SESSION['2fa_expires']) {
    $code = sprintf('%06d', mt_rand(0, 999999));
    $_SESSION['2fa_code'] = $code;
    $_SESSION['2fa_expires'] = time() + 600; // 10 minutes
    
    // Send email with code
    $to = $user['email'];
    $subject = 'Your ' . SITE_NAME . ' Verification Code';
    $message = "Your verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.";
    $headers = 'From: ' . SITE_EMAIL;
    
    @mail($to, $subject, $message, $headers);
    
    // DEBUG MODE: Show code on screen for localhost testing
    if (DEBUG_MODE) {
        $_SESSION['debug_2fa_code'] = $code;
    }
}

$page_title = 'Two-Factor Authentication - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Two-Factor Authentication</h3>
                        <p class="text-muted">Enter the verification code sent to your email</p>
                    </div>

                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (DEBUG_MODE && !empty($_SESSION['debug_2fa_code'])): ?>
                        <div class="alert alert-warning">
                            <strong><i class="bi bi-bug me-2"></i>DEBUG MODE:</strong> Your code is <strong><?= $_SESSION['debug_2fa_code'] ?></strong>
                            <br><small>This message only appears in development mode</small>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Verification Code</label>
                            <input type="text" 
                                   name="code" 
                                   class="form-control form-control-lg text-center" 
                                   placeholder="000000"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   required
                                   autofocus
                                   style="letter-spacing: 0.5em; font-size: 1.5rem;">
                            <div class="form-text">
                                <i class="bi bi-envelope me-1"></i>
                                Code sent to <?= htmlspecialchars(substr($user['email'], 0, 3) . '***@' . substr($user['email'], strpos($user['email'], '@') + 1)) ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Verify Code
                            </button>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Login
                            </a>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            Code expires in 10 minutes
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
