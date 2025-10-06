<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/pagination.php';

// Check if logged in AND is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

$hasMaxPax = false;

// Handle package operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
        redirect('packages.php');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_package') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $days = intval($_POST['days'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $highlights = trim($_POST['highlights'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $image = '';
        $location = trim($_POST['location'] ?? '');
        $group_size = intval($_POST['group_size'] ?? 1);

        // Handle file upload if provided
        if (!empty($_FILES['image_file']['name'] ?? '')) {
            $file = $_FILES['image_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if ($file['size'] > MAX_FILE_SIZE) {
                    $_SESSION['error'] = 'Image exceeds max file size.';
                    redirect('packages.php');
                }
                if (!in_array(mime_content_type($file['tmp_name']), ALLOWED_IMAGE_TYPES, true)) {
                    $_SESSION['error'] = 'Invalid image type. Allowed: JPG, PNG.';
                    redirect('packages.php');
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = 'pkg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $safeName)) {
                    $_SESSION['error'] = 'Failed to save uploaded image.';
                    redirect('packages.php');
                }
                $image = $safeName; // store relative path; use upload($image) to render
                $image_url = ''; // prefer uploaded file when present
            }
        }

        if ($title && $days > 0 && $price > 0) {
            $stm = $pdo->prepare("INSERT INTO packages (title, description, days, duration, price, highlights, image, image_url, location, group_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stm->execute([$title, $description, $days, $duration, $price, $highlights, $image, $image_url, $location, $group_size]);
            $_SESSION['success'] = "Package added successfully!";
            redirect('packages.php');
        } else {
            $_SESSION['error'] = "Please fill all required fields correctly.";
        }
    }

    if ($action === 'update_package') {
        $id = intval($_POST['package_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $days = intval($_POST['days'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $highlights = trim($_POST['highlights'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $group_size = intval($_POST['group_size'] ?? 1);

        // Load current to preserve image if no changes
        $curr = null;
        if ($id > 0) {
            $q = $pdo->prepare('SELECT image, image_url FROM packages WHERE id = ?');
            $q->execute([$id]);
            $curr = $q->fetch() ?: ['image' => '', 'image_url' => ''];
        }
        $image = $curr['image'] ?? '';

        // Handle file upload if provided (overrides existing and URL)
        if (!empty($_FILES['image_file']['name'] ?? '')) {
            $file = $_FILES['image_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if ($file['size'] > MAX_FILE_SIZE) {
                    $_SESSION['error'] = 'Image exceeds max file size.';
                    redirect('packages.php');
                }
                if (!in_array(mime_content_type($file['tmp_name']), ALLOWED_IMAGE_TYPES, true)) {
                    $_SESSION['error'] = 'Invalid image type. Allowed: JPG, PNG.';
                    redirect('packages.php');
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = 'pkg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                if (!is_dir(UPLOAD_PATH)) { @mkdir(UPLOAD_PATH, 0777, true); }
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $safeName)) {
                    $_SESSION['error'] = 'Failed to save uploaded image.';
                    redirect('packages.php');
                }
                $image = $safeName;
                $image_url = '';
            }
        }

        if ($id > 0 && $title && $days > 0 && $price > 0) {
            $stm = $pdo->prepare("UPDATE packages SET title = ?, description = ?, days = ?, duration = ?, price = ?, highlights = ?, image = ?, image_url = ?, location = ?, group_size = ? WHERE id = ?");
            $stm->execute([$title, $description, $days, $duration, $price, $highlights, $image, $image_url, $location, $group_size, $id]);
            $_SESSION['success'] = "Package updated successfully!";
            redirect('packages.php');
        } else {
            $_SESSION['error'] = "Please fill all required fields correctly.";
        }
    }

    if ($action === 'delete_package') {
        $id = intval($_POST['package_id'] ?? 0);
        if ($id > 0) {
            $stm = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE package_id = ?");
            $stm->execute([$id]);
            $booking_count = (int)$stm->fetchColumn();

            if ($booking_count > 0) {
                $_SESSION['error'] = "Cannot delete package with existing bookings.";
            } else {
                $stm = $pdo->prepare("DELETE FROM packages WHERE id = ?");
                $stm->execute([$id]);
                $_SESSION['success'] = "Package deleted successfully!";
            }
        }
        redirect('packages.php');
    }
}

// Server-side pagination via helper
list($page, $perPage, $offset) = pagination_get_page_and_size(15);

$totalStmt = $pdo->query("SELECT COUNT(*) AS cnt FROM packages");
$totalRows = (int)$totalStmt->fetch()['cnt'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$stm = $pdo->prepare("SELECT * FROM packages ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stm->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stm->bindValue(':offset', $offset, PDO::PARAM_INT);
$stm->execute();
$packages = $stm->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box me-2"></i>Manage Packages</h2>
    <div class="d-flex gap-2">
        <input type="text" class="form-control table-search" placeholder="Search packages..." style="width: 250px;">
        <form method="get" class="d-flex align-items-center gap-2">
            <input type="hidden" name="page" value="<?= max(1, (int)($_GET['page'] ?? 1)) ?>">
            <label class="text-muted small">Page size</label>
            <select name="pageSize" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach (pagination_allowed_sizes() as $size): ?>
                    <option value="<?= $size ?>" <?= $perPage===$size? 'selected':'' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
            <i class="bi bi-plus-circle me-1"></i>Add Package
        </button>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card no-hover">
    <div class="card-body">
        <div class="table-responsive sticky-head">
            <table class="table table-striped table-hover table-compact">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Group Size</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="packages-tbody">
                    <?php foreach ($packages as $p): ?>
                    <tr>
                        <td><?=$p['id']?></td>
                        <td>
                            <?php
                                $thumb = '';
                                if (!empty($p['image_url'])) {
                                    $thumb = $p['image_url'];
                                } elseif (!empty($p['image'])) {
                                    if (preg_match('/^https?:\/\//i', $p['image'])) {
                                        // Full external URL stored in image
                                        $thumb = $p['image'];
                                    } elseif (preg_match('/^\/?assets\/uploads\//i', $p['image'])) {
                                        // Legacy: full relative path already stored (e.g., assets/uploads/xyz.jpg)
                                        $thumb = url(ltrim($p['image'], '/'));
                                    } else {
                                        // Filename only stored, use upload() helper
                                        $thumb = upload($p['image']);
                                    }
                                }
                            ?>
                            <div class="thumb-box-100">
                                <?php if ($thumb): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?=htmlspecialchars($p['title'])?>" class="img-thumb-100">
                                <?php else: ?>
                                    <i class="bi bi-image text-muted"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><strong><?=htmlspecialchars($p['title'])?></strong></td>
                        <td><?=htmlspecialchars($p['location'] ?? 'N/A')?></td>
                        <td><?=$p['days']?> Days</td>
                        <td>₱<?=number_format($p['price'])?></td>
                        <td><?=$p['group_size'] ?? 1?></td>
                        <td><?=date('M d, Y', strtotime($p['created_at']))?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editPackage(<?=$p['id']?>)">Edit</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePackage(<?=$p['id']?>, '<?=htmlspecialchars($p['title'], ENT_QUOTES)?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= pagination_render_controls($page, $totalPages, ['pageSize' => $perPage]) ?>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="add_package">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Package Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Days *</label>
                            <input type="number" name="days" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (e.g., "3 Days 2 Nights")</label>
                            <input type="text" name="duration" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Group Size</label>
                            <input type="number" name="group_size" class="form-control" min="1" value="10">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Or Upload Image</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                        <div class="form-text">You can either upload an image or paste a URL. If both are provided, the upload will be used.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Highlights (comma-separated)</label>
                        <textarea name="highlights" class="form-control" rows="2" placeholder="Beach resort, Island hopping, Snorkeling"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Add Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="editPackageForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_package">
                    <input type="hidden" name="package_id" id="edit_package_id">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Package Title *</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Days *</label>
                            <input type="number" name="days" id="edit_days" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration</label>
                            <input type="text" name="duration" id="edit_duration" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Group Size</label>
                            <input type="number" name="group_size" id="edit_group_size" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" id="edit_image" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Or Upload New Image</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                        <div class="form-text">Leave empty to keep the current image. Uploading a new file will replace it.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Highlights (comma-separated)</label>
                        <textarea name="highlights" id="edit_highlights" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Package Form (hidden) -->
<form method="post" id="deletePackageForm" style="display: none;">
    <input type="hidden" name="action" value="delete_package">
    <input type="hidden" name="package_id" id="delete_package_id">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
</form>

<script>
// Edit package function
async function editPackage(id) {
    try {
        const response = await fetch(`../api/packages.php?id=${id}`);
        const pkg = await response.json();
        
        document.getElementById('edit_package_id').value = pkg.id;
        document.getElementById('edit_title').value = pkg.title || '';
        document.getElementById('edit_description').value = pkg.description || '';
        document.getElementById('edit_days').value = pkg.days || 1;
        document.getElementById('edit_duration').value = pkg.duration || '';
        document.getElementById('edit_location').value = pkg.location || '';
        document.getElementById('edit_price').value = pkg.price || 0;
        document.getElementById('edit_group_size').value = pkg.group_size || 10;
        document.getElementById('edit_image').value = pkg.image_url || pkg.image || '';
        document.getElementById('edit_highlights').value = pkg.highlights || '';
        
        const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
        modal.show();
    } catch (error) {
        console.error('Error loading package:', error);
        alert('Failed to load package details');
    }
}

// Delete package function
function deletePackage(id, title) {
    if (confirm(`Are you sure you want to delete "${title}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('delete_package_id').value = id;
        document.getElementById('deletePackageForm').submit();
    }
}

// Table search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

<?php include 'footer.php'; ?>
