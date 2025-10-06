<?php
// footer.php – closes the container from header.php and renders footer
?>
    </div> <!-- end .container from header.php -->

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-airplane me-2"></i><?= SITE_NAME ?></h5>
                    <p class="mb-0">Your trusted partner for amazing travel experiences.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                    <small class="text-muted">Version <?= SITE_VERSION ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="<?= asset('js/main.js') ?>?v=<?= SITE_VERSION ?>"></script>
</body>
</html>
