<?php
/**
 * System Settings (سیٹنگز - دکان و منڈی معلومات)
 */
require_once __DIR__ . '/functions.php';

// Handle Save Settings BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = clean($_POST['shop_name'] ?? APP_NAME);
    $shop_subheading = clean($_POST['shop_subheading'] ?? '');
    $mandi_name = clean($_POST['mandi_name'] ?? '');
    $city = clean($_POST['city'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $phone2 = clean($_POST['phone2'] ?? '');
    $commission = (float)($_POST['default_commission_rate'] ?? 6.00);
    $mazdoori = (float)($_POST['default_mazdoori_per_nag'] ?? 10.00);
    $market_fee = (float)($_POST['default_market_fee_percent'] ?? 1.00);
    $munshiana = (float)($_POST['default_munshiana_per_bill'] ?? 20.00);

    $stmt = $pdo->prepare("UPDATE `settings` SET 
        `shop_name` = ?, `shop_subheading` = ?, `mandi_name` = ?, `city` = ?, 
        `phone` = ?, `phone2` = ?, `default_commission_rate` = ?, 
        `default_mazdoori_per_nag` = ?, `default_market_fee_percent` = ?, 
        `default_munshiana_per_bill` = ? WHERE `id` = 1");
    $stmt->execute([
        $shop_name, $shop_subheading, $mandi_name, $city,
        $phone, $phone2, $commission,
        $mazdoori, $market_fee, $munshiana
    ]);

    flash_set("سسٹم سیٹنگز کامیابی سے اپ ڈیٹ ہو گئیں۔", 'success');
    header("Location: settings.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

$cur_settings = get_settings();
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-xl-6">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-gear text-primary me-2"></i> سسٹم و دکان سیٹنگز (Shop Settings)</span>
            </div>
            <div class="p-4">
                <form method="POST" action="settings.php">
                    <div class="mb-3">
                        <label class="form-label fw-bold">دکان / آڑھت کا نام (Shop Name) <span class="text-danger">*</span></label>
                        <input type="text" name="shop_name" class="form-control urdu-font fs-5" 
                            value="<?= htmlspecialchars($cur_settings['shop_name']) ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">سب ہیڈنگ (مثلاً: فروٹ کمیشن ایجنٹ)</label>
                            <input type="text" name="shop_subheading" class="form-control" 
                                value="<?= htmlspecialchars($cur_settings['shop_subheading']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">شہر</label>
                            <input type="text" name="city" class="form-control" 
                                value="<?= htmlspecialchars($cur_settings['city']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">منڈی کا نام و پتہ</label>
                        <input type="text" name="mandi_name" class="form-control" 
                            value="<?= htmlspecialchars($cur_settings['mandi_name']) ?>" placeholder="نیو سبزی و پھل منڈی، ایمن آباد روڈ">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">موبائل فون # 1 (واٹس ایپ)</label>
                            <input type="text" name="phone" class="form-control rubik-font" 
                                value="<?= htmlspecialchars($cur_settings['phone']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">موبائل فون # 2</label>
                            <input type="text" name="phone2" class="form-control rubik-font" 
                                value="<?= htmlspecialchars($cur_settings['phone2'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Default Mandi Deductions -->
                    <div class="border rounded p-3 bg-light mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-percent text-success me-1"></i> ڈیفالٹ منڈی کٹوتیاں (Default Deductions)
                        </h6>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">ڈیفالٹ کمیشن شرح (%)</label>
                                <input type="number" step="0.01" name="default_commission_rate" class="form-control rubik-font" 
                                    value="<?= $cur_settings['default_commission_rate'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">مزدوری فی نَگ (روپے)</label>
                                <input type="number" step="0.01" name="default_mazdoori_per_nag" class="form-control rubik-font" 
                                    value="<?= $cur_settings['default_mazdoori_per_nag'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">مارکیٹ فیس شرح (%)</label>
                                <input type="number" step="0.01" name="default_market_fee_percent" class="form-control rubik-font" 
                                    value="<?= $cur_settings['default_market_fee_percent'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">منشیانہ فی بل (روپے)</label>
                                <input type="number" step="0.01" name="default_munshiana_per_bill" class="form-control rubik-font" 
                                    value="<?= $cur_settings['default_munshiana_per_bill'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-mandi-primary justify-content-center py-2 fs-5">
                            <i class="fa-solid fa-floppy-disk"></i> سیٹنگز محفوظ کریں (Save Settings)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
