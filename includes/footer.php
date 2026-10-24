</main>

<!-- Footer -->
<footer class="bg-white border-top py-3 mt-auto no-print">
    <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between text-muted small px-4">
        <div>
            <strong><?= htmlspecialchars($settings['shop_name'] ?? APP_NAME) ?></strong> &copy; <?= date('Y') ?> | 
            پھل و سبزی منڈی کمیشن اینڈ انوینٹری اکاؤنٹنگ سسٹم
        </div>
        <div class="d-flex align-items-center gap-3">
            <span><i class="fa-solid fa-headset text-success"></i> ہیلپ لائن: <?= htmlspecialchars($settings['phone']) ?></span>
            <span class="badge bg-secondary">ورژن <?= APP_VERSION ?></span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Keyboard Shortcuts & Speed Navigation for Mandi Accounting -->
<script>
document.addEventListener('keydown', function(e) {
    // Only trigger if not typing inside a modal textarea
    const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';

    switch(e.key) {
        case 'F1':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>index.php';
            break;
        case 'F2':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>boli_sales.php';
            break;
        case 'F3':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>maal_amad.php';
            break;
        case 'F4':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>vouchers.php';
            break;
        case 'F5':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>bazar_khata.php';
            break;
        case 'F6':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>rokad.php';
            break;
        case 'F7':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>parties.php';
            break;
        case 'F8':
            e.preventDefault();
            window.location.href = '<?= BASE_URL ?>chittha.php';
            break;
    }

    // Enter key navigation inside forms: move focus to next input field instead of submitting prematurely
    if (e.key === 'Enter' && activeTag === 'input') {
        const form = document.activeElement.form;
        if (form && !document.activeElement.classList.contains('allow-enter-submit')) {
            const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([disabled]), select:not([disabled]), button[type="submit"]'));
            const index = inputs.indexOf(document.activeElement);
            if (index > -1 && index < inputs.length - 1) {
                e.preventDefault();
                inputs[index + 1].focus();
                if (inputs[index + 1].select) {
                    inputs[index + 1].select();
                }
            }
        }
    }
});
</script>

</body>
</html>
