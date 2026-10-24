<?php
/**
 * Main Dashboard - Mandi Accounting System
 */
require_once __DIR__ . '/includes/header.php';

$today = date('Y-m-d');

// 1. Today's Arrivals
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_nag), 0) as nag, COUNT(*) as challans FROM `maal_amad` WHERE amad_date = ?");
$stmt->execute([$today]);
$today_amad = $stmt->fetch();

// 2. Today's Sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(nag), 0) as nag, COALESCE(SUM(net_amount), 0) as amount, COALESCE(SUM(commission_amount), 0) as commission FROM `sales_bills` WHERE sale_date = ?");
$stmt->execute([$today]);
$today_sales = $stmt->fetch();

// 3. Today's Recovery (Bazar Wasooli + Cash Vouchers)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(morning_amount + evening_amount), 0) FROM `bazar_wasooli` WHERE date = ?");
$stmt->execute([$today]);
$today_wasooli = (float)$stmt->fetchColumn();

// 4. Unsold / Pending Stock in Mandi
$pending_stock = (int)$pdo->query("SELECT COALESCE(SUM(remaining_nag), 0) FROM `maal_amad` WHERE status != 'completed'")->fetchColumn();

// 5. Recent Arrivals
$recent_amad = $pdo->query("SELECT m.*, p.urdu_name as farmer_name, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    JOIN `items` i ON m.item_id = i.id 
    JOIN `packings` pk ON m.packing_id = pk.id 
    ORDER BY m.id DESC LIMIT 5")->fetchAll();

// 6. Recent Sales Bills
$recent_sales = $pdo->query("SELECT s.*, p.urdu_name as customer_name, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    JOIN `items` i ON s.item_id = i.id 
    JOIN `packings` pk ON s.packing_id = pk.id 
    ORDER BY s.id DESC LIMIT 5")->fetchAll();
?>

<!-- Quick KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Today Amad Nag -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 border-start border-4 border-success">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold">آج کی کل آمد (نَگ)</span>
                    <h3 class="fw-bold rubik-font m-0 text-success mt-1"><?= number_format($today_amad['nag']) ?></h3>
                    <small class="text-secondary"><?= $today_amad['challans'] ?> چالان رجسٹرڈ</small>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-truck-moving"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today Sales Amount -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 border-start border-4 border-primary">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold">آج کی کل فروخت</span>
                    <h3 class="fw-bold rubik-font m-0 text-primary mt-1">Rs. <?= format_rs($today_sales['amount']) ?></h3>
                    <small class="text-secondary"><?= number_format($today_sales['nag']) ?> نگ فروخت ہوئے</small>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-gavel"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today Recovery -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 border-start border-4 border-warning">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold">آج کی بازار وصولی</span>
                    <h3 class="fw-bold rubik-font m-0 text-warning mt-1">Rs. <?= format_rs($today_wasooli) ?></h3>
                    <small class="text-secondary">صبح و شام کی کل وصولی</small>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Remaining Mandi Stock -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom p-3 border-start border-4 border-danger">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold">منڈی میں موجود باقی سٹاک</span>
                    <h3 class="fw-bold rubik-font m-0 text-danger mt-1"><?= number_format($pending_stock) ?> نگ</h3>
                    <small class="text-secondary">غیر فروخت شدہ پھل</small>
                </div>
                <div class="bg-danger-subtle text-danger p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Touch/Click Grid (Matching Video frame 005) -->
<div class="card-custom mb-4">
    <div class="card-custom-header">
        <span><i class="fa-solid fa-bolt text-warning me-2"></i> فوری اندراج و شارٹ کٹس (Quick Mandi Actions)</span>
    </div>
    <div class="p-3">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3 col-lg-2">
                <a href="parties.php?type=customer" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-user-plus text-primary fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">نیا کھاتہ بنائیں</div>
                    <small class="text-muted">(کسٹمر / زمیندار)</small>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="items.php" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-apple-whole text-danger fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">پروڈکٹ بنائیں</div>
                    <small class="text-muted">(جنس و پیکنگ)</small>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="maal_amad.php" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-truck-ramp-box text-success fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">مال آمد کا اندراج</div>
                    <small class="text-muted">[F3] چالان انٹری</small>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="boli_sales.php" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-scale-balanced text-warning fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">خرید و فروخت بل</div>
                    <small class="text-muted">[F2] نیلامی بولی</small>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="bazar_khata.php" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-money-bill-wave text-info fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">بازار کھاتہ</div>
                    <small class="text-muted">[F5] وصولی صبح و شام</small>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="rokad.php" class="text-decoration-none d-block p-3 rounded bg-light border hover-shadow h-100">
                    <i class="fa-solid fa-book-bookmark text-secondary fs-1 mb-2"></i>
                    <div class="fw-bold text-dark urdu-font fs-5">روکڑ کھاتہ</div>
                    <small class="text-muted">[F6] کیش بک رجسٹر</small>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout: Recent Arrivals & Recent Sales -->
<div class="row g-4">
    <!-- Recent Arrivals (مال آمد) -->
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-truck-ramp-box text-success me-2"></i> تازہ ترین مال آمد (Inward Lots)</span>
                <a href="maal_amad.php" class="btn btn-sm btn-outline-success">سب دیکھیں</a>
            </div>
            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>چالان #</th>
                            <th>زمیندار</th>
                            <th>مارکہ</th>
                            <th>جنس</th>
                            <th>کل نَگ</th>
                            <th>باقی نَگ</th>
                            <th>حیثیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_amad)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">کوئی مال آمد ریکارڈ موجود نہیں</td></tr>
                        <?php else: foreach ($recent_amad as $a): ?>
                            <tr>
                                <td class="fw-bold rubik-font"><?= htmlspecialchars($a['challan_no']) ?></td>
                                <td class="urdu-font"><?= htmlspecialchars($a['farmer_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($a['marke']) ?></span></td>
                                <td><?= htmlspecialchars($a['item_name']) ?> (<?= htmlspecialchars($a['packing_name']) ?>)</td>
                                <td class="fw-bold rubik-font text-center"><?= $a['total_nag'] ?></td>
                                <td class="fw-bold rubik-font text-center <?= $a['remaining_nag'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $a['remaining_nag'] ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($a['status'] === 'completed'): ?>
                                        <span class="badge bg-success">مکمل فروخت</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">زیرِ فروخت</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Sales Bills (فروخت بل) -->
    <div class="col-12 col-lg-6">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-gavel text-primary me-2"></i> تازہ ترین فروخت بل (Recent Sales)</span>
                <a href="boli_sales.php" class="btn btn-sm btn-outline-primary">نئی بولی کاٹیں</a>
            </div>
            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>بل #</th>
                            <th>گاہک (خریدار)</th>
                            <th>جنس</th>
                            <th>نگ</th>
                            <th>ریٹ</th>
                            <th>صافی رقم</th>
                            <th>واٹس ایپ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_sales)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">آج کی کوئی فروخت درج نہیں</td></tr>
                        <?php else: foreach ($recent_sales as $s): ?>
                            <tr>
                                <td class="fw-bold rubik-font"><?= htmlspecialchars($s['bill_no']) ?></td>
                                <td class="urdu-font fw-bold"><?= htmlspecialchars($s['customer_name']) ?></td>
                                <td><?= htmlspecialchars($s['item_name']) ?></td>
                                <td class="text-center rubik-font"><?= $s['nag'] ?></td>
                                <td class="text-end rubik-font"><?= format_rs($s['rate']) ?></td>
                                <td class="text-end rubik-font fw-bold text-success">Rs. <?= format_rs($s['net_amount']) ?></td>
                                <td class="text-center">
                                    <?php 
                                    $msg = "محترم " . $s['customer_name'] . "، آپ کا فروخت بل #" . $s['bill_no'] . " بنام " . $settings['shop_name'] . " تیار ہے۔ تعداد نگ: " . $s['nag'] . "، کل رقم: Rs. " . format_rs($s['net_amount']);
                                    $wa = whatsapp_url('', $msg);
                                    ?>
                                    <a href="<?= $wa ?>" target="_blank" class="btn btn-sm btn-success p-1 px-2" title="واٹس ایپ پر بھیجیں">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
