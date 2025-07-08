</div>
</main>
</div>
</div>

<!-- Footer -->
<footer class="bg-primary text-white text-center py-3 mt-5">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> SMP Negeri 2 Ampek Angkek - Sistem Pendukung Keputusan Beasiswa
            Prestasi</p>
        <small class="text-light">Implementasi Metode Weighted Product</small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
// Konfirmasi hapus data
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
    return confirm(message);
}

// Auto hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});

// Format number input
function formatNumberInput(input) {
    let value = input.value.replace(/[^\d.,]/g, '');
    value = value.replace(',', '.');
    input.value = value;
}

// Validate form before submit
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}
</script>

<?php if (isset($additional_js)): ?>
<?php echo $additional_js; ?>
<?php endif; ?>
</body>

</html>