<?php
session_start();
require_once 'inc/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $fullname   = trim($_POST['fullname'] ?? '');
        $dob        = $_POST['dob'] ?? null;
        $gender     = $_POST['gender'] ?? 'Male';
        $nationality= trim($_POST['nationality'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $mobile     = trim($_POST['mobile'] ?? '');
        $address    = trim($_POST['address'] ?? '');
        $username   = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        // Validation
        if ($fullname === '' || $email === '' || $username === '' || $password === '') {
            $errors[] = "Please fill in all required fields.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
        }
        if ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO users 
                    (fullname, dob, gender, nationality, email, mobile, address, username, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $fullname,
                    $dob ?: null,
                    $gender,
                    $nationality,
                    $email,
                    $mobile,
                    $address,
                    $username,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                $success = true;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $errors[] = "Username or Email already exists.";
                } else {
                    $errors[] = "Registration failed: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

$page_title = 'Register - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4"><i class="bi bi-person-plus"></i> Create an Account</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">🎉 Registration successful! <a href="login.php">Login here</a>.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nationality</label>
                <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($_POST['nationality'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Register
        </button>
        <a href="login.php" class="btn btn-link">Already have an account? Login</a>
    </form>
</div>

<?php include 'inc/footer.php'; ?>
