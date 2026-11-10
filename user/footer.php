<footer class="footer-laborrow">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <h5 class="text-white fw-bold mb-1"><i class="fa-solid fa-flask-vial text-danger me-2"></i>LABORROW</h5>
                <p class="text-white-50 mb-0 small">Sistem Peminjaman Alat Laboratorium Praktis, Terintegrasi, dan Transparan.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="small text-white-50">
                    &copy; <?= date('Y') ?> <strong>Laborrow</strong>. Didesain dengan penuh dedikasi untuk efisiensi laboratorium sekolah/kampus.
                </div>
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
