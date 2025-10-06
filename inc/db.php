<?php
// ================================
// Database Connection File
// ================================

// Always load config.php first so DB_HOST etc. are defined
require_once __DIR__ . '/config.php';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $options
    );
} catch (Exception $e) {
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
