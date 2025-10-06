<?php
/**
 * Database Migration Tool
 * Safely updates existing database schema with new columns
 */

require_once 'inc/config.php';
require_once 'inc/db.php';

// Only allow execution from localhost for security
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Migration can only be run from localhost');
}

$migrations = [];
$errors = [];

// Handle migration execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        // Migration 1: Add missing columns to users table
        $userColumns = [
            'fullname' => "ALTER TABLE users ADD COLUMN fullname VARCHAR(255) DEFAULT NULL AFTER id",
            'dob' => "ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL",
            'gender' => "ALTER TABLE users ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male'",
            'nationality' => "ALTER TABLE users ADD COLUMN nationality VARCHAR(100) DEFAULT NULL",
            'mobile' => "ALTER TABLE users ADD COLUMN mobile VARCHAR(20) DEFAULT NULL",
            'address' => "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL",
            'username' => "ALTER TABLE users ADD COLUMN username VARCHAR(100) DEFAULT NULL",
            'emergency_name' => "ALTER TABLE users ADD COLUMN emergency_name VARCHAR(255) DEFAULT NULL",
            'emergency_relation' => "ALTER TABLE users ADD COLUMN emergency_relation VARCHAR(100) DEFAULT NULL",
            'emergency_phone' => "ALTER TABLE users ADD COLUMN emergency_phone VARCHAR(20) DEFAULT NULL",
            'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL",
            'two_factor_enabled' => "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0",
            'notify_email' => "ALTER TABLE users ADD COLUMN notify_email TINYINT(1) DEFAULT 1",
            'notify_sms' => "ALTER TABLE users ADD COLUMN notify_sms TINYINT(1) DEFAULT 0",
            'newsletter' => "ALTER TABLE users ADD COLUMN newsletter TINYINT(1) DEFAULT 0"
        ];

        foreach ($userColumns as $column => $sql) {
            try {
                // Check if column exists
                $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
                if ($check->rowCount() == 0) {
                    $pdo->exec($sql);
                    $migrations[] = "✓ Added column 'users.$column'";
                } else {
                    $migrations[] = "- Column 'users.$column' already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Error adding users.$column: " . $e->getMessage();
            }
        }

        // Migration 2: Add missing columns to packages table
        $packageColumns = [
            'image_url' => "ALTER TABLE packages ADD COLUMN image_url VARCHAR(500) DEFAULT NULL",
            'location' => "ALTER TABLE packages ADD COLUMN location VARCHAR(255) DEFAULT NULL",
            'group_size' => "ALTER TABLE packages ADD COLUMN group_size INT DEFAULT 10",
            'duration' => "ALTER TABLE packages ADD COLUMN duration VARCHAR(100) DEFAULT NULL"
        ];

        foreach ($packageColumns as $column => $sql) {
            try {
                $check = $pdo->query("SHOW COLUMNS FROM packages LIKE '$column'");
                if ($check->rowCount() == 0) {
                    $pdo->exec($sql);
                    $migrations[] = "✓ Added column 'packages.$column'";
                } else {
                    $migrations[] = "- Column 'packages.$column' already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Error adding packages.$column: " . $e->getMessage();
            }
        }

        // Migration 3: Add missing columns to bookings table
        $bookingColumns = [
            'travel_date' => "ALTER TABLE bookings ADD COLUMN travel_date DATE DEFAULT NULL",
            'special_requests' => "ALTER TABLE bookings ADD COLUMN special_requests TEXT DEFAULT NULL",
            'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status ENUM('Pending', 'Partial', 'Paid', 'Cancelled') DEFAULT 'Pending'",
            'notes' => "ALTER TABLE bookings ADD COLUMN notes TEXT DEFAULT NULL"
        ];

        foreach ($bookingColumns as $column => $sql) {
            try {
                $check = $pdo->query("SHOW COLUMNS FROM bookings LIKE '$column'");
                if ($check->rowCount() == 0) {
                    $pdo->exec($sql);
                    $migrations[] = "✓ Added column 'bookings.$column'";
                } else {
                    $migrations[] = "- Column 'bookings.$column' already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Error adding bookings.$column: " . $e->getMessage();
            }
        }

        // Migration 4: Create user_logins table if not exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_logins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                ip VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
            $migrations[] = "✓ Created/verified 'user_logins' table";
        } catch (PDOException $e) {
            $errors[] = "Error creating user_logins table: " . $e->getMessage();
        }

        // Migration 5: Create itinerary table if not exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS itinerary (
                id INT AUTO_INCREMENT PRIMARY KEY,
                package_id INT NOT NULL,
                day INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
            )");
            $migrations[] = "✓ Created/verified 'itinerary' table";
        } catch (PDOException $e) {
            $errors[] = "Error creating itinerary table: " . $e->getMessage();
        }

        // Migration 6: Create ratings table if not exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                package_id INT NOT NULL,
                booking_id INT DEFAULT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                review TEXT,
                admin_reply TEXT DEFAULT NULL,
                admin_reply_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
                UNIQUE KEY unique_user_package (user_id, package_id)
            )");
            $migrations[] = "✓ Created/verified 'ratings' table";
        } catch (PDOException $e) {
            $errors[] = "Error creating ratings table: " . $e->getMessage();
        }

        // Migration 7: Add admin_reply columns to existing ratings table
        try {
            $check = $pdo->query("SHOW COLUMNS FROM ratings LIKE 'admin_reply'");
            if ($check->rowCount() === 0) {
                $pdo->exec("ALTER TABLE ratings ADD COLUMN admin_reply TEXT DEFAULT NULL");
                $migrations[] = "✓ Added 'admin_reply' column to ratings table";
            }
            
            $check = $pdo->query("SHOW COLUMNS FROM ratings LIKE 'admin_reply_at'");
            if ($check->rowCount() === 0) {
                $pdo->exec("ALTER TABLE ratings ADD COLUMN admin_reply_at TIMESTAMP NULL DEFAULT NULL");
                $migrations[] = "✓ Added 'admin_reply_at' column to ratings table";
            }
        } catch (PDOException $e) {
            $errors[] = "Error adding admin reply columns: " . $e->getMessage();
        }

        $success = empty($errors);
    } catch (Exception $e) {
        $errors[] = "Migration failed: " . $e->getMessage();
        $success = false;
    }
}

$page_title = 'Database Migration - ' . SITE_NAME;
include 'inc/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-database-gear me-2"></i>Database Migration Tool</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Important:</strong> This tool will safely update your database schema by adding missing columns and tables. 
                        Existing data will not be affected.
                    </div>

                    <?php if (isset($success)): ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle me-2"></i>Migration Completed Successfully!</h5>
                                <ul class="mb-0">
                                    <?php foreach ($migrations as $msg): ?>
                                        <li><?= htmlspecialchars($msg) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Errors Occurred:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>Return to Home
                        </a>
                    <?php else: ?>
                        <h5 class="mb-3">Migration will add the following:</h5>
                        <ul>
                            <li>Missing columns to <code>users</code> table (fullname, profile_image, etc.)</li>
                            <li>Missing columns to <code>packages</code> table (image_url, location, etc.)</li>
                            <li>Missing columns to <code>bookings</code> table (travel_date, payment_status, etc.)</li>
                            <li>Create <code>user_logins</code> table if not exists</li>
                            <li>Create <code>itinerary</code> table if not exists</li>
                            <li>Create <code>ratings</code> table for feedback/reviews if not exists</li>
                        </ul>

                        <form method="post" class="mt-4">
                            <div class="d-grid gap-2">
                                <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                    <i class="bi bi-play-circle me-2"></i>Run Database Migration
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
