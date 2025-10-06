<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Build user info safely
$user_fullname = $_SESSION['user']['fullname'] 
    ?? $_SESSION['user']['name'] 
    ?? 'Guest';
$user_is_admin = $_SESSION['user']['is_admin'] ?? false;

// Capture current package id if available
$current_package_id = $_GET['id'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : (SITE_NAME ?? 'TourOps') ?></title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Flatpickr CSS -->
  <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="assets/css/styles.css" rel="stylesheet">
  <script>
    // Global config for JavaScript
    window.APP_CONFIG = {
      baseUrl: '<?= BASE_URL ?>',
      csrfToken: '<?= csrf_token() ?>'
    };
    
    // Apply saved theme immediately (before page renders)
    (function() {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
  </script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<!-- Enhanced Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= url('index.php') ?>">
      <i class="bi bi-airplane-fill me-2 fs-4"></i>
      <span class="fs-4"><?= SITE_NAME ?? 'TourOps' ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">
            <i class="bi bi-house-door me-1"></i>Home
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (!empty($_SESSION['user'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-2 fs-5"></i>
              <span><?= htmlspecialchars($user_fullname) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= url('dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= url('itinerary.php') ?>"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
              <li><a class="dropdown-item" href="<?= url('profile.php') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
              <?php if ($user_is_admin): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-warning" href="<?= url('admin/') ?>"><i class="bi bi-shield-check me-2"></i>Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link btn btn-outline-light px-3 rounded-pill" href="<?= url('login.php') ?>">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container my-4 flex-grow-1">
