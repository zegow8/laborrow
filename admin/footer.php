<footer class="footer-laborrow mt-auto">
    <div class="container-fluid px-lg-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <div class="small text-white-50">
                    &copy; <?= date('Y') ?> <strong>Laborrow Management System</strong> &bull; Panel Administrator Laboratorium
                </div>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="badge bg-light text-dark border">
                    <i class="fa-solid fa-shield-halved text-danger me-1"></i> Mode Admin Terverifikasi
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom JS -->
<script src="../assets/js/script.js"></script>
<?= render_flash() ?>
</body>
</html>
