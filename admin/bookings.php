<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/pagination.php';

// check if user is admin
if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    if ($postAction === 'update_status') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? 'Pending';
        $new_payment_status = $_POST['new_payment_status'] ?? 'Pending';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        if ($booking_id > 0) {
            $stm = $pdo->prepare("UPDATE bookings SET status = ?, payment_status = ?, notes = CONCAT(IFNULL(notes, ''), '\n\nAdmin Update (', NOW(), '): ', ?) WHERE id = ?");
            $stm->execute([$new_status, $new_payment_status, $admin_notes, $booking_id]);
            $_SESSION['success'] = "Booking status updated successfully!";
        }
        header('Location: bookings.php');
        exit;
    }
}

// Pagination via helper
list($page, $perPage, $offset) = pagination_get_page_and_size(15);

$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalPages = max(1, (int)ceil($totalBookings / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// fetch bookings for current page with package details
$stm = $pdo->prepare("SELECT b.*, p.title 
                      FROM bookings b 
                      JOIN packages p ON p.id = b.package_id 
                      ORDER BY b.created_at DESC 
                      LIMIT :limit OFFSET :offset");
$stm->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stm->bindValue(':offset', $offset, PDO::PARAM_INT);
$stm->execute();
$bookings = $stm->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-check me-2"></i>Manage Bookings</h2>
    <div class="d-flex gap-2">
        <input type="text" class="form-control table-search" placeholder="Search bookings..." style="width: 250px;">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card no-hover">
    <div class="card-body">
        <div class="d-flex justify-content-end mb-2">
            <form method="get" class="d-flex align-items-center gap-2">
                <input type="hidden" name="page" value="<?= max(1, (int)($_GET['page'] ?? 1)) ?>">
                <label class="text-muted small mb-0">Page size</label>
                <select name="pageSize" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach (pagination_allowed_sizes() as $size): ?>
                        <option value="<?= $size ?>" <?= $perPage===$size? 'selected':'' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-responsive sticky-head">
            <table class="table table-striped table-hover table-compact">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Package</th>
                        <th>Guest</th>
                        <th>Travel Date</th>
                        <th>Pax</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Booked On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><span class="badge bg-secondary">#<?=$b['id']?></span></td>
                            <td>
                                <strong><?=htmlspecialchars($b['title'])?></strong>
                                <?php if (!empty($b['special_requests'])): ?>
                                    <br><small class="text-muted">
                                        <i class="bi bi-star me-1"></i>Special: <?=htmlspecialchars(substr($b['special_requests'], 0, 50))?>...
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="bi bi-person me-1"></i><?=htmlspecialchars($b['guest_name'])?><br>
                                <small class="text-muted">
                                    <i class="bi bi-envelope me-1"></i><?=htmlspecialchars($b['guest_email'])?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <i class="bi bi-calendar me-1"></i><?=date('M j, Y', strtotime($b['travel_date']))?>
                                </span>
                            </td>
                            <td><span class="badge bg-secondary"><?=$b['pax']?></span></td>
                            <td><strong class="text-success">₱<?=number_format($b['total'],0)?></strong></td>
                            <td>
                                <span class="badge bg-<?=$b['status'] === 'Confirmed' ? 'success' : ($b['status'] === 'Pending' ? 'warning' : 'secondary')?>">
                                    <?=htmlspecialchars($b['status'])?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?=$b['payment_status'] === 'Paid' ? 'success' : ($b['payment_status'] === 'Partial' ? 'info' : ($b['payment_status'] === 'Cancelled' ? 'danger' : 'warning'))?>">
                                    <?=htmlspecialchars($b['payment_status'])?>
                                </span>
                            </td>
                            <td><small><i class="bi bi-clock me-1"></i><?=date('M j, Y', strtotime($b['created_at']))?></small></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-booking-btn" data-bs-toggle="modal" data-bs-target="#editBookingModal"
                                        data-id="<?=$b['id']?>"
                                        data-title="<?=htmlspecialchars($b['title'], ENT_QUOTES)?>"
                                        data-guest="<?=htmlspecialchars($b['guest_name'], ENT_QUOTES)?>"
                                        data-email="<?=htmlspecialchars($b['guest_email'], ENT_QUOTES)?>"
                                        data-traveldate="<?=date('F j, Y', strtotime($b['travel_date']))?>"
                                        data-total="<?=number_format($b['total'],0)?>"
                                        data-status="<?=htmlspecialchars($b['status'], ENT_QUOTES)?>"
                                        data-payment="<?=htmlspecialchars($b['payment_status'], ENT_QUOTES)?>"
                                        data-requests="<?=htmlspecialchars($b['special_requests'] ?? '', ENT_QUOTES)?>"
                                        data-notes="<?=htmlspecialchars($b['notes'] ?? '', ENT_QUOTES)?>">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_render_controls($page, $totalPages, ['pageSize' => $perPage]) ?>
    </div>
</div>

<!-- Reusable Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil me-2"></i>Edit Booking
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="booking_id" id="edit_booking_id">

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-box me-1"></i>Package</label>
                <input type="text" class="form-control" id="edit_title" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-1"></i>Guest</label>
                <input type="text" class="form-control" id="edit_guest" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-calendar me-1"></i>Travel Date</label>
                <input type="text" class="form-control" id="edit_traveldate" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-currency-dollar me-1"></i>Total Amount</label>
                <input type="text" class="form-control" id="edit_total" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-calendar-check me-1"></i>Booking Status</label>
                <select name="new_status" id="edit_status" class="form-select" required>
                  <option value="Pending">Pending</option>
                  <option value="Confirmed">Confirmed</option>
                  <option value="Cancelled">Cancelled</option>
                  <option value="Completed">Completed</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-credit-card me-1"></i>Payment Status</label>
                <select name="new_payment_status" id="edit_payment" class="form-select" required>
                  <option value="Pending">Pending</option>
                  <option value="Partial">Partial</option>
                  <option value="Paid">Paid</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3" id="edit_requests_wrap" style="display:none;">
            <label class="form-label"><i class="bi bi-star me-1"></i>Special Requests</label>
            <textarea class="form-control" id="edit_requests" rows="2" readonly></textarea>
          </div>

          <div class="mb-3" id="edit_notes_wrap" style="display:none;">
            <label class="form-label"><i class="bi bi-journal-text me-1"></i>Current Notes</label>
            <textarea class="form-control" id="edit_notes" rows="3" readonly></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><i class="bi bi-plus-circle me-1"></i>Add Admin Notes</label>
            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any admin notes or updates..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Booking</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Populate reusable edit modal (event delegation)
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.edit-booking-btn');
  if (!btn) return;
  document.getElementById('edit_booking_id').value = btn.dataset.id;
  document.getElementById('edit_title').value = btn.dataset.title || '';
  document.getElementById('edit_guest').value = (btn.dataset.guest || '') + ' (' + (btn.dataset.email || '') + ')';
  document.getElementById('edit_traveldate').value = btn.dataset.traveldate || '';
  document.getElementById('edit_total').value = '₱' + (btn.dataset.total || '0');

  const statusSel = document.getElementById('edit_status');
  const paySel = document.getElementById('edit_payment');
  if (statusSel) statusSel.value = btn.dataset.status || 'Pending';
  if (paySel) paySel.value = btn.dataset.payment || 'Pending';

  const reqWrap = document.getElementById('edit_requests_wrap');
  const reqTa = document.getElementById('edit_requests');
  const notesWrap = document.getElementById('edit_notes_wrap');
  const notesTa = document.getElementById('edit_notes');
  const reqVal = btn.dataset.requests || '';
  const notesVal = btn.dataset.notes || '';
  reqTa.value = reqVal; notesTa.value = notesVal;
  reqWrap.style.display = reqVal ? 'block' : 'none';
  notesWrap.style.display = notesVal ? 'block' : 'none';
});

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
</script>

<?php include 'footer.php'; ?>