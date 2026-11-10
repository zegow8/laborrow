/**
 * Laborrow Main Scripts
 * Global utilities, SweetAlert confirmation, and UI enhancements
 */

document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap Tooltips jika ada
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Validasi Ukuran File Upload Gambar Realtime (Maks. 2MB)
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const maxBytes = 2 * 1024 * 1024; // 2MB
                if (file.size > maxBytes) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ukuran Terlalu Besar',
                        text: 'Ukuran file foto maksimal adalah 2MB. Silakan pilih foto dengan ukuran lebih kecil.',
                        confirmButtonColor: '#800020'
                    });
                    this.value = '';
                }
            }
        });
    });
});
