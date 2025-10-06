<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - TourOps</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/styles.css" rel="stylesheet">
  <style>
    .admin-sidebar {
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .admin-sidebar .nav-link {
      color: rgba(255,255,255,0.8);
      border-radius: 0.5rem;
      margin: 0.25rem 0;
      transition: all 0.3s ease;
    }
    .admin-sidebar .nav-link:hover,
    .admin-sidebar .nav-link.active {
      color: white;
      background-color: rgba(255,255,255,0.1);
      transform: translateX(5px);
    }
    .admin-content {
      background-color: #f8f9fa;
      min-height: 100vh;
    }
    .admin-header {
      background: white;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="admin-context">
<div class="container-fluid">
  <div class="row">
    <!-- Admin Sidebar -->
    <div class="col-md-3 col-lg-2 px-0 admin-sidebar">
      <div class="p-3">
        <h4 class="text-white mb-4">
          <i class="bi bi-gear-fill me-2"></i>
          TourOps Admin
        </h4>
        <nav class="nav flex-column">
          <a class="nav-link <?=basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''?>" href="index.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
          </a>
          <a class="nav-link <?=basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''?>" href="bookings.php">
            <i class="bi bi-calendar-check me-2"></i> Bookings
          </a>
          <a class="nav-link <?=basename($_SERVER['PHP_SELF']) === 'packages.php' ? 'active' : ''?>" href="packages.php">
            <i class="bi bi-box me-2"></i> Packages
          </a>
          <a class="nav-link <?=basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''?>" href="users.php">
            <i class="bi bi-people me-2"></i> Users
          </a>
          <a class="nav-link <?=basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : ''?>" href="feedback.php">
            <i class="bi bi-chat-quote me-2"></i> Feedback
          </a>
          <hr class="text-white-50">
          <a class="nav-link" href="../index.php">
            <i class="bi bi-house me-2"></i> View Site
          </a>
          <a class="nav-link" href="../logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
          </a>
        </nav>
      </div>
    </div>
    
    <!-- Admin Content -->
    <div class="col-md-9 col-lg-10 admin-content">
      <!-- Admin Header -->
      <div class="admin-header p-3">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <?php
            $page_titles = [
              'index.php' => 'Dashboard',
              'bookings.php' => 'Manage Bookings',
              'packages.php' => 'Manage Packages',
              'users.php' => 'Manage Users',
              'feedback.php' => 'Manage Feedback'
            ];
            $current_page = basename($_SERVER['PHP_SELF']);
            echo $page_titles[$current_page] ?? 'Admin Panel';
            ?>
          </h5>
          <div class="d-flex align-items-center">
            <span class="text-muted me-3">
              <i class="bi bi-person-circle me-1"></i>
              <?=htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? 'Admin')?>
            </span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
          </div>
        </div>
      </div>
      
      <!-- Page Content -->
      <div class="p-4">


