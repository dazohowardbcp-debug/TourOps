<?php
require_once 'inc/config.php';
require_once 'inc/db.php';

// Set page variables
$page_title = 'Tour Packages - ' . SITE_NAME;

include 'inc/header.php';

// Fetch packages for React
$stmt = $pdo->query("SELECT * FROM packages ORDER BY created_at DESC");
$packages = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-box me-2"></i>Tour Packages</h1>
    <div class="d-flex gap-2">
        <input type="text" class="form-control" id="package-search" placeholder="Search packages..." style="width: 300px;">
        <button class="btn btn-outline-primary" id="search-btn">
            <i class="bi bi-search me-1"></i>Search
        </button>
    </div>
</div>

<!-- React Packages Grid -->
<div id="packages-root">
    <!-- Fallback content while React loads -->
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading packages...</span>
        </div>
        <p class="mt-3 text-muted">Loading packages...</p>
    </div>
</div>

<!-- React and ReactDOM -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>

<!-- Packages data for React -->
<script>
window.packagesData = <?= json_encode(array_map(function($pkg) {
    // Helper to handle both external and local images
    $imageUrl = '';
    if (!empty($pkg['image_url'])) {
        $imageUrl = $pkg['image_url'];
    } elseif (!empty($pkg['image'])) {
        // Check if it's already a full URL
        if (preg_match('/^https?:\/\//i', $pkg['image'])) {
            $imageUrl = $pkg['image'];
        } else {
            // It's a local file, use upload helper
            $imageUrl = UPLOADS_URL . '/' . ltrim($pkg['image'], '/');
        }
    }
    
    return [
        'id' => intval($pkg['id']),
        'title' => $pkg['title'],
        'description' => $pkg['description'],
        'price' => floatval($pkg['price']),
        'image_url' => $imageUrl,
        'location' => $pkg['location'],
        'duration' => $pkg['duration'],
        'group_size' => $pkg['group_size'],
        'highlights' => $pkg['highlights'],
        'created_at' => $pkg['created_at']
    ];
}, $packages)) ?>;
</script>

<!-- React App Component -->
<script src="<?= asset('js/react-app.js') ?>?v=<?= SITE_VERSION ?>"></script>

<script>
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('package-search');
    const searchBtn = document.getElementById('search-btn');
    
    const performSearch = () => {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            // Reload page with search parameter
            window.location.href = `${window.APP_CONFIG?.baseUrl || ''}/packages.php?search=${encodeURIComponent(searchTerm)}`;
        }
    };
    
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Set search term if provided in URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        searchInput.value = searchParam;
    }
});
</script>

<?php include 'inc/footer.php'; ?>
