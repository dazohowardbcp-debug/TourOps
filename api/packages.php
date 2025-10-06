<?php
require_once '../inc/config.php';
require_once '../inc/db.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if requesting single package by ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
        $stmt->execute([$id]);
        $package = $stmt->fetch();
        
        if ($package) {
            echo json_encode([
                'id' => intval($package['id']),
                'title' => $package['title'],
                'description' => $package['description'] ?? '',
                'price' => floatval($package['price']),
                'image_url' => $package['image_url'] ?? '',
                'image' => $package['image'] ?? '',
                'location' => $package['location'] ?? '',
                'duration' => $package['duration'] ?? $package['days'] . ' Days',
                'group_size' => intval($package['group_size'] ?? 1),
                'highlights' => $package['highlights'] ?? '',
                'days' => intval($package['days']),
                'created_at' => $package['created_at']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Package not found']);
        }
        exit;
    }
    
    // Get search parameters
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ? OR highlights LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM packages {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // Get packages
    $sql = "SELECT * FROM packages {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();
    
    // Process packages data
    $processed_packages = [];
    foreach ($packages as $package) {
        $processed_packages[] = [
            'id' => intval($package['id']),
            'title' => $package['title'],
            'description' => $package['description'] ?? '',
            'price' => floatval($package['price']),
            'image_url' => $package['image_url'] ?? $package['image'] ?? '',
            'location' => $package['location'] ?? '',
            'duration' => $package['duration'] ?? $package['days'] . ' Days',
            'group_size' => intval($package['group_size'] ?? 1),
            'highlights' => $package['highlights'] ?? '',
            'days' => intval($package['days']),
            'created_at' => $package['created_at']
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'packages' => $processed_packages,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'has_next' => ($page * $limit) < $total,
            'has_prev' => $page > 1
        ],
        'meta' => [
            'search' => $search,
            'count' => count($processed_packages)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Packages API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => DEBUG_MODE ? $e->getMessage() : 'An error occurred while loading packages'
    ]);
}
?>
