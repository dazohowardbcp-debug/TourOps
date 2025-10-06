<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
require_once 'inc/db.php';

// Redirect if already logged in
if (!empty($_SESSION['user'])) {
    if ($_SESSION['user']['is_admin']) {
        header("Location: admin/");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$errors = [];

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // ✅ Set sessions
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['fullname']     = $user['fullname'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['is_admin']     = $user['is_admin'];
                $_SESSION['profile_image']= $user['profile_image'];
                $_SESSION['created_at']   = $user['created_at'];

                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'fullname' => $user['fullname'],
                    'email'    => $user['email'],
                    'is_admin' => $user['is_admin'],
                    'profile_image' => $user['profile_image'],
                    'created_at'    => $user['created_at']
                ];

                // Redirect
                if ($user['is_admin']) {
                    header("Location: admin/");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $errors[] = "Invalid email or password.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    }
}

$page_title = "Login - " . SITE_NAME;
include 'inc/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm border-0">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <i class="bi bi-airplane text-primary" style="font-size: 3rem;"></i>
          <h2 class="mt-3 mb-1">Welcome Back</h2>
          <p class="text-muted">Sign in to your account</p>
        </div>

        <?php if (!empty($_SESSION['info'])): ?>
          <div class="alert alert-info alert-dismissible fade show">
            <i class="bi bi-info-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
            <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
          </div>
        </form>

        <hr class="my-4">
        <div class="text-center">
          <p class="mb-0">Don't have an account? <a href="register.php">Sign up</a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'inc/footer.php'; ?>
