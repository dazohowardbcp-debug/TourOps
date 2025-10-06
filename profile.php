<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'inc/config.php';
require_once 'inc/db.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    redirect('login.php');
}

$userId = $_SESSION['user']['id'];

// Load user data
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $current = $stmt->fetch();
    if (!$current) {
        redirect('login.php');
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    redirect('login.php');
}

// Helper functions
$formVal = function($key, $fallback = '') use ($current) {
    // Map 'name' input to 'fullname' column for consistency
    $dbKey = ($key === 'name') ? 'fullname' : $key;
    if (isset($_POST[$key]) && $_POST[$key] !== '') return $_POST[$key];
    if (isset($current[$dbKey]) && $current[$dbKey] !== null && $current[$dbKey] !== '') return $current[$dbKey];
    return $fallback;
};

$formChk = function($key, $currentKey = null) use ($current) {
    $k = $currentKey ?: $key;
    if (isset($_POST[$key])) return true;
    return !empty($current[$k] ?? null);
};

// Handle profile picture upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    header('Content-Type: application/json');
    
    try {
        // Check if file was uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        $file = $_FILES['avatar'];
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('File size must be less than 2MB');
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Only JPG, PNG, and GIF files are allowed');
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file extension');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = UPLOAD_PATH;
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Generate unique filename
        $filename = 'avatar_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Delete old profile image if exists
        if (!empty($current['profile_image'])) {
            $oldFile = UPLOAD_PATH . basename($current['profile_image']);
            if (file_exists($oldFile) && is_file($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Update database
        $relativePath = 'assets/uploads/' . $filename;
        $stmt = $pdo->prepare('UPDATE users SET profile_image = ? WHERE id = ?');
        $stmt->execute([$relativePath, $userId]);
        
        // Update session
        $_SESSION['user']['profile_image'] = $relativePath;
        $_SESSION['user']['profile_image_version'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'image_url' => upload($filename) . '?v=' . time()
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    // CSRF protection
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        redirect('profile.php');
    }
    // Delete account
    if (($_POST['confirm_delete'] ?? '') === 'DELETE') {
        try {
            // Delete profile image if exists
            if (!empty($current['profile_image'])) {
                $oldFile = UPLOAD_PATH . basename($current['profile_image']);
                if (file_exists($oldFile) && is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            session_destroy();
            redirect('index.php');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete account. Please try again.';
        }
    } elseif (!empty($_POST['confirm_delete'])) {
        $_SESSION['error'] = 'Type DELETE in uppercase to confirm account deletion.';
    } else {
        // Update profile
        $fullname = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $nationality = trim($_POST['nationality'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $newPass = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $twofa = isset($_POST['two_factor_enabled']) ? 1 : 0;
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $notifySms = isset($_POST['notify_sms']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;

        $errors = [];
        if ($fullname === '') $errors[] = 'Name is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if ($newPass !== '' && $newPass !== $confirm) $errors[] = 'Passwords do not match';
        if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'Invalid date of birth';

        if (empty($errors)) {
            try {
                if ($newPass !== '') {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET fullname=?, email=?, password=?, mobile=?, nationality=?, dob=?, two_factor_enabled=?, notify_email=?, notify_sms=?, newsletter=? WHERE id=?');
                    $stmt->execute([$fullname, $email, $hash, $mobile, $nationality, $dob ?: null, $twofa, $notifyEmail, $notifySms, $newsletter, $userId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET fullname=?, email=?, mobile=?, nationality=?, dob=?, two_factor_enabled=?, notify_email=?, notify_sms=?, newsletter=? WHERE id=?');
                    $stmt->execute([$fullname, $email, $mobile, $nationality, $dob ?: null, $twofa, $notifyEmail, $notifySms, $newsletter, $userId]);
                }
                
                // Update session
                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email'] = $email;
                
                $_SESSION['success'] = 'Profile updated successfully';
                redirect('profile.php');
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $_SESSION['error'] = 'Failed to update profile. Please try again.';
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
    
    if (isset($_SESSION['error'])) {
        redirect('profile.php');
    }
}

// Set page variables
$page_title = 'Profile - ' . SITE_NAME;
$GLOBALS['PAGE_FULL_WIDTH'] = true;

include 'inc/header.php';

// Get profile image URL with improved logic
$displayName = trim(($current['fullname'] ?? '') !== '' ? $current['fullname'] : ($_SESSION['user']['fullname'] ?? 'User'));
$sessionAvatar = $_SESSION['user']['profile_image'] ?? '';
$version = $_SESSION['user']['profile_image_version'] ?? 0;

// Determine the best profile image source
$profileImage = null;

// First priority: Session avatar (most recent)
if (!empty($sessionAvatar)) {
    $profileImage = $sessionAvatar;
}
// Second priority: Database profile image
elseif (!empty($current['profile_image'])) {
    $profileImage = $current['profile_image'];
}
// Third priority: Generate initials avatar
else {
    // Use a more professional avatar service
    $profileImage = "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=0D6EFD&color=fff&size=200&bold=true&format=svg";
}

// Process the profile image URL
if ($profileImage && !empty($profileImage)) {
    // Check if it's a full URL (like UI Avatars)
    if (filter_var($profileImage, FILTER_VALIDATE_URL)) {
        $profileImgTag = $profileImage;
    } else {
        // It's a relative path, extract just the filename for upload() function
        $filename = basename($profileImage);
        $profileImgTag = upload($filename);
    }
} else {
    // Fallback to initials avatar
    $profileImgTag = "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=0D6EFD&color=fff&size=200&bold=true&format=svg";
}

// Add version parameter for cache busting
$profileImgTag = htmlspecialchars($profileImgTag . ($version ? (strpos($profileImgTag,'?')!==false ? '&v='.$version : '?v='.$version) : ''));
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h1 class="mb-1 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>My Profile</h1>
        <p class="text-muted mb-0">Manage your account information and preferences</p>
    </div>
    <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= nl2br(htmlspecialchars($_SESSION['error'])); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card no-hover border-0 shadow-sm">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block mb-3">
                    <div class="profile-picture-container">
                        <img class="img-fluid rounded-circle profile-avatar" 
                             src="<?= $profileImgTag ?>" 
                             alt="Profile Picture" 
                             id="profileAvatar"
                             style="width: 160px; height: 160px; object-fit: cover; border: 4px solid #f8f9fa; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <button type="button" 
                                data-avatar-trigger 
                                class="btn btn-primary position-absolute d-flex align-items-center justify-content-center p-0" 
                                title="Change profile picture"
                                style="bottom: 8px; right: 8px; width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                        <div id="uploadProgress" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                            <div class="spinner-border text-light" role="status">
                                <span class="visually-hidden">Uploading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="mb-2"><?= htmlspecialchars($displayName) ?></h5>
                <p class="text-muted small mb-3">Click to upload a new profile picture</p>
                <div class="text-muted small mb-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Supported formats: JPG, PNG, GIF (max 2MB)
                </div>
                <div id="uploadMessage" class="mt-2"></div>
            </div>
        </div>
        <div class="card mt-3 no-hover border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="text-muted mb-3 fw-bold">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
                <div class="d-grid gap-3">
                    <a class="btn btn-outline-primary d-flex align-items-center justify-content-start" href="dashboard.php">
                        <i class="bi bi-house-door me-3 fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold">Dashboard</div>
                            <small class="text-muted">View your overview</small>
                        </div>
                    </a>
                    <a class="btn btn-outline-success d-flex align-items-center justify-content-start" href="itinerary.php">
                        <i class="bi bi-calendar-check me-3 fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold">My Bookings</div>
                            <small class="text-muted">Manage your trips</small>
                        </div>
                    </a>
                    <a class="btn btn-outline-info d-flex align-items-center justify-content-start" href="packages.php">
                        <i class="bi bi-box me-3 fs-5"></i>
                        <div class="text-start">
                            <div class="fw-bold">Browse Packages</div>
                            <small class="text-muted">Discover destinations</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card no-hover border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold">Account Settings</h4>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="themeToggle">
                        <label class="form-check-label ms-2" for="themeToggle">
                            <i class="bi bi-moon-stars me-1"></i>Dark Mode
                        </label>
                    </div>
                </div>
                
                <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center justify-content-center" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-personal" 
                                type="button"
                                style="min-width: 120px;">
                            <i class="bi bi-person me-2"></i>Personal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center justify-content-center" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-security" 
                                type="button"
                                style="min-width: 120px;">
                            <i class="bi bi-shield-lock me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center justify-content-center" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-notifications" 
                                type="button"
                                style="min-width: 120px;">
                            <i class="bi bi-bell me-2"></i>Notifications
                        </button>
                    </li>
                </ul>
                
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input id="avatarInput" type="file" name="avatar" class="d-none" accept=".jpg,.jpeg,.png,.gif">
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-personal">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" 
                                               name="name" 
                                               class="form-control" 
                                               id="nameInput"
                                               value="<?= htmlspecialchars($formVal('name')) ?>" 
                                               required 
                                               placeholder="Enter your full name">
                                        <label for="nameInput">
                                            <i class="bi bi-person me-2"></i>Full Name
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" 
                                               name="email" 
                                               class="form-control" 
                                               id="emailInput"
                                               value="<?= htmlspecialchars($formVal('email')) ?>" 
                                               required 
                                               placeholder="Enter your email address">
                                        <label for="emailInput">
                                            <i class="bi bi-envelope me-2"></i>Email Address
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" 
                                               name="mobile" 
                                               class="form-control" 
                                               id="mobileInput"
                                               value="<?= htmlspecialchars($formVal('mobile')) ?>" 
                                               placeholder="Enter your mobile number">
                                        <label for="mobileInput">
                                            <i class="bi bi-phone me-2"></i>Mobile Number
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" 
                                               name="nationality" 
                                               class="form-control" 
                                               id="nationalityInput"
                                               value="<?= htmlspecialchars($formVal('nationality')) ?>" 
                                               placeholder="Enter your nationality">
                                        <label for="nationalityInput">
                                            <i class="bi bi-flag me-2"></i>Nationality
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" 
                                               name="dob" 
                                               class="form-control" 
                                               id="dobInput"
                                               value="<?= htmlspecialchars($formVal('dob')) ?>" 
                                               max="<?= date('Y-m-d') ?>" 
                                               placeholder="Select your date of birth">
                                        <label for="dobInput">
                                            <i class="bi bi-calendar me-2"></i>Date of Birth
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-security">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" 
                                               name="password" 
                                               class="form-control" 
                                               id="passwordInput"
                                               minlength="8" 
                                               placeholder="">
                                        <label for="passwordInput">
                                            <i class="bi bi-lock me-2"></i>New Password
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>Minimum 8 characters
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               id="confirmPasswordInput"
                                               minlength="8" 
                                               placeholder="">
                                        <label for="confirmPasswordInput">
                                            <i class="bi bi-lock-fill me-2"></i>Confirm New Password
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="twofa" name="two_factor_enabled" <?= $formChk('two_factor_enabled', 'two_factor_enabled') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="twofa">
                                            <i class="bi bi-shield-check me-2"></i>Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                    <div class="form-text text-muted">
                                        Add an extra layer of security to your account
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-notifications">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="fw-bold mb-2"><i class="bi bi-bell me-2"></i>Notification Preferences</h6>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notify_email" name="notify_email" <?= $formChk('notify_email', 'notify_email') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="notify_email">
                                            <i class="bi bi-envelope me-2"></i>Email Alerts
                                        </label>
                                    </div>
                                    <div class="form-text text-muted">
                                        Receive booking confirmations, promotional offers, and travel reminders
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_sms" name="notify_sms" <?= $formChk('notify_sms', 'notify_sms') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="notify_sms">
                                            <i class="bi bi-phone me-2"></i>SMS Reminders
                                        </label>
                                    </div>
                                    <div class="form-text text-muted">
                                        Get important travel updates and booking reminders via text message
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" <?= $formChk('newsletter', 'newsletter') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="newsletter">
                                            <i class="bi bi-newspaper me-2"></i>Newsletter Subscription
                                        </label>
                                    </div>
                                    <div class="form-text text-muted">
                                        Stay updated with travel tips, destination guides, and exclusive offers
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-4 pt-3 border-top">
                        <button class="btn btn-primary px-4 py-2" type="submit">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </button>
                        <a class="btn btn-outline-secondary px-4 py-2" href="dashboard.php">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Dark Mode Toggle
(function() {
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-bs-theme', savedTheme);
    if (themeToggle) {
        themeToggle.checked = savedTheme === 'dark';
    }
    
    // Toggle theme
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            const theme = this.checked ? 'dark' : 'light';
            htmlElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Update icon
            const icon = this.nextElementSibling.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill me-1' : 'bi bi-moon-stars me-1';
            }
        });
    }
})();

// Avatar Upload
(function(){
    const triggers = document.querySelectorAll('[data-avatar-trigger]');
    const input = document.getElementById('avatarInput');
    const img = document.getElementById('profileAvatar');
    const progress = document.getElementById('uploadProgress');
    const message = document.getElementById('uploadMessage');
    
    const openPicker = () => input && input.click();
    triggers.forEach(btn => btn.addEventListener('click', openPicker));
    if (img) img.addEventListener('click', openPicker);
    
    if (input && img) {
        input.addEventListener('change', function(){
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type
                if (!/image\/(jpeg|jpg|png|gif)/.test(file.type)) {
                    showMessage('Only JPG, PNG, and GIF files are allowed', 'danger');
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showMessage('File size must be less than 2MB', 'danger');
                    return;
                }
                
                // Show preview
                const url = URL.createObjectURL(file);
                img.src = url;
                
                // Upload file
                uploadFile(file);
            }
        });
    }
    
    function uploadFile(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('action', 'upload_avatar');
        
        // Show progress
        progress.style.display = 'block';
        showMessage('Uploading...', 'info');
        
        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progress.style.display = 'none';
            
            if (data.success) {
                showMessage(data.message, 'success');
                // Update image with server URL
                img.src = data.image_url;
            } else {
                showMessage(data.message, 'danger');
                // Revert to original image
                img.src = '<?= $profileImgTag ?>';
            }
        })
        .catch(error => {
            progress.style.display = 'none';
            showMessage('Upload failed. Please try again.', 'danger');
            // Revert to original image
            img.src = '<?= $profileImgTag ?>';
            console.error('Upload error:', error);
        });
    }
    
    function showMessage(text, type) {
        message.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show py-2">
            <small>${text}</small>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>`;
        
        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                message.innerHTML = '';
            }, 3000);
        }
    }
    
    // Dark mode toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        // Load saved preference
        const savedTheme = localStorage.getItem('darkMode');
        if (savedTheme === 'true') {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            themeToggle.checked = true;
        }
        
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.documentElement.setAttribute('data-bs-theme', 'light');
                localStorage.setItem('darkMode', 'false');
            }
        });
    }
})();
</script>

<?php include 'inc/footer.php'; ?>
