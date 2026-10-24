<?php
/**
 * Daily Auction & Sale Bills (خرید و فروخت بل / بولی بل)
 */
require_once __DIR__ . '/functions.php';

// Generate Next Bill No
$next_bill = (int)$pdo->query("SELECT MAX(id) FROM `sales_bills`")->fetchColumn() + 1;
$next_bill_no = 'SB-' . str_pad($next_bill, 4, '0', STR_PAD_LEFT);

// Handle Form Submission BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_sale') {
        $bill_no = clean($_POST['bill_no'] ?? $next_bill_no);
        $sale_date = clean($_POST['sale_date'] ?? date('Y-m-d'));
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $amad_id = (int)($_POST['amad_id'] ?? 0);
        $nag = (int)($_POST['nag'] ?? 0);
        $rate = (float)($_POST['rate'] ?? 0);
        $settings = get_settings();
        $commission_percent = (float)($_POST['commission_percent'] ?? $settings['default_commission_rate']);
        $is_paid = isset($_POST['is_paid']) ? 1 : 0;
        $notes = clean($_POST['notes'] ?? '');

        if ($customer_id <= 0 || $amad_id <= 0 || $nag <= 0 || $rate <= 0) {
            flash_set('براہ کرم گاہک، مال آمد لاٹ، نگ اور ریٹ درست درج کریں۔', 'danger');
        } else {
            // Get amad details
            $stmt = $pdo->prepare("SELECT * FROM `maal_amad` WHERE id = ?");
            $stmt->execute([$amad_id]);
            $amad = $stmt->fetch();

            if (!$amad || $amad['remaining_nag'] < $nag) {
                flash_set("خرابی: اس لاٹ میں صرف {$amad['remaining_nag']} نگ باقی ہیں۔", 'danger');
            } else {
                $gross_amount = $nag * $rate;
                $commission_amount = ($gross_amount * $commission_percent) / 100;
                $net_amount = $gross_amount; // Bill to customer is gross amount

                // Insert into sales_bills
                $stmt = $pdo->prepare("INSERT INTO `sales_bills` 
                    (`bill_no`, `sale_date`, `customer_id`, `amad_id`, `item_id`, `packing_id`, `marke`, `nag`, `rate`, `gross_amount`, `commission_percent`, `commission_amount`, `net_amount`, `is_paid`, `notes`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $bill_no, $sale_date, $customer_id, $amad_id, $amad['item_id'], $amad['packing_id'],
                    $amad['marke'], $nag, $rate, $gross_amount, $commission_percent, $commission_amount,
                    $net_amount, $is_paid, $notes
                ]);

                // Update remaining and sold nag in maal_amad
                $stmt = $pdo->prepare("UPDATE `maal_amad` SET 
                    `sold_nag` = `sold_nag` + ?, 
                    `remaining_nag` = `remaining_nag` - ?, 
                    `status` = IF(`remaining_nag` - ? <= 0, 'completed', 'partially_sold') 
                    WHERE `id` = ?");
                $stmt->execute([$nag, $nag, $nag, $amad_id]);

                // If paid cash on spot, add entry to cash vouchers
                if ($is_paid) {
                    $stmt = $pdo->prepare("INSERT INTO `vouchers` 
                        (`voucher_no`, `voucher_date`, `type`, `party_id`, `amount`, `payment_method`, `narration`) 
                        VALUES (?, ?, 'receipt', ?, ?, 'cash', ?)");
                    $stmt->execute([
                        'RCP-' . $bill_no, $sale_date, $customer_id, $net_amount, "نقد فروخت بل #{$bill_no}"
                    ]);
                }

                flash_set("فروخت بل #{$bill_no} کامیابی سے محفوظ ہو گیا۔ نگ: {$nag}، رقم: Rs. " . format_rs($net_amount), 'success');
                header("Location: boli_sales.php");
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch Active Inward Lots with Remaining Stock > 0
$available_lots = $pdo->query("SELECT m.*, p.urdu_name as farmer_name, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    JOIN `items` i ON m.item_id = i.id 
    JOIN `packings` pk ON m.packing_id = pk.id 
    WHERE m.remaining_nag > 0 
    ORDER BY m.id DESC")->fetchAll();

// Fetch Customers
$customers = $pdo->query("SELECT id, urdu_name, city, mobile, is_blacklisted FROM `parties` WHERE `type` = 'customer' ORDER BY `urdu_name` ASC")->fetchAll();

// Fetch Today's Bills
$filter_date = clean($_GET['date'] ?? date('Y-m-d'));
$bills = $pdo->prepare("SELECT s.*, p.urdu_name as customer_name, p.mobile as customer_phone, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    JOIN `items` i ON s.item_id = i.id 
    JOIN `packings` pk ON s.packing_id = pk.id 
    WHERE s.sale_date = ? 
    ORDER BY s.id DESC");
$bills->execute([$filter_date]);
$sales_list = $bills->fetchAll();

// Totals for today
$total_sold_nag = 0;
$total_sold_amount = 0;
foreach ($sales_list as $row) {
    $total_sold_nag += $row['nag'];
    $total_sold_amount += $row['net_amount'];
}
?>

<div class="row g-4">
    <!-- Fast Entry Form (بولی بل کاٹیں) -->
    <div class="col-12 col-xl-4">
        <div class="card-custom sticky-top" style="top: 80px;">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-gavel text-primary me-2"></i> نیا بولی بل کاٹیں (Sale Bill Entry)</span>
            </div>
            <div class="p-3">
                <form method="POST" action="boli_sales.php" id="saleBillForm">
                    <input type="hidden" name="post_action" value="save_sale">

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">بل نمبر</label>
                            <input type="text" name="bill_no" class="form-control rubik-font fw-bold text-primary" 
                                value="<?= htmlspecialchars($next_bill_no) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">تاریخ فروخت</label>
                            <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Customer Selection -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">گاہک / خریدار (Customer) <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select urdu-font fs-5" required id="customerSelect">
                            <option value="">-- گاہک منتخب کریں --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['is_blacklisted'] ? 'style="color:red;"' : '' ?>>
                                    <?= htmlspecialchars($c['urdu_name']) ?> (<?= htmlspecialchars($c['city'] ?: '-') ?>)
                                    <?= $c['is_blacklisted'] ? ' [بلیک لسٹ]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Available Inward Lot Selection -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">مال آمد لاٹ / مارکہ (Stock Lot) <span class="text-danger">*</span></label>
                        <select name="amad_id" class="form-select urdu-font" required id="lotSelect">
                            <option value="">-- دستیاب مال آمد لاٹ منتخب کریں --</option>
                            <?php foreach ($available_lots as $lot): ?>
                                <option value="<?= $lot['id'] ?>" data-rem="<?= $lot['remaining_nag'] ?>">
                                    مارکہ: <?= htmlspecialchars($lot['marke']) ?> | <?= htmlspecialchars($lot['item_name']) ?> (<?= htmlspecialchars($lot['packing_name']) ?>) - باقی: <?= $lot['remaining_nag'] ?> نگ [<?= htmlspecialchars($lot['farmer_name']) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-danger fw-bold" id="remNagNotice"></small>
                    </div>

                    <!-- Nag & Rate -->
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold text-primary">تعداد نگ (Nag) <span class="text-danger">*</span></label>
                            <input type="number" name="nag" id="saleNag" class="form-control rubik-font fs-5 fw-bold text-primary" 
                                placeholder="10" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-success">ریٹ فی نگ (Rate) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rate" id="saleRate" class="form-control rubik-font fs-5 fw-bold text-success" 
                                placeholder="1200" required>
                        </div>
                    </div>

                    <!-- Calculation Preview Box -->
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">کل رقم (Gross):</span>
                            <span class="fw-bold rubik-font" id="grossPreview">Rs. 0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">کمیشن شرح (%):</span>
                            <span class="rubik-font"><?= $settings['default_commission_rate'] ?>%</span>
                            <input type="hidden" name="commission_percent" value="<?= $settings['default_commission_rate'] ?>">
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-dark fs-5">صافی رقم (Net Bill):</span>
                            <span class="fw-bold text-success fs-4 rubik-font" id="netPreview">Rs. 0</span>
                        </div>
                    </div>

                    <!-- Cash Sale Toggle -->
                    <div class="form-check form-switch mb-3 p-2 bg-warning-subtle rounded border border-warning">
                        <input class="form-check-input ms-2" type="checkbox" name="is_paid" id="cashSaleCheck">
                        <label class="form-check-label fw-bold text-dark" for="cashSaleCheck">
                            <i class="fa-solid fa-coins text-warning me-1"></i> نقد ادائیگی (Cash Received On Spot)
                        </label>
                    </div>

                    <div class="mb-3">
                        <input type="text" name="notes" class="form-control" placeholder="اضافی ریمارکس / تفصیل">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-mandi-primary justify-content-center py-2 fs-5">
                            <i class="fa-solid fa-gavel"></i> بل کاٹیں (Save & Print)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Today's Boli Sales Register -->
    <div class="col-12 col-xl-8">
        <div class="card-custom">
            <div class="card-custom-header flex-wrap gap-2">
                <span><i class="fa-solid fa-file-invoice text-success me-2"></i> روزانہ بولی و فروخت رجسٹر (Boli Sales Sheet)</span>
                
                <!-- Date Filter -->
                <form method="GET" action="boli_sales.php" class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-bold">تاریخ:</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">
                </form>
            </div>

            <!-- Summary KPI -->
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted">تاریخ <?= format_dt($filter_date) ?> کی فروخت: </span>
                    <strong class="text-primary rubik-font fs-5"><?= number_format($total_sold_nag) ?> نگ</strong>
                </div>
                <div>
                    <span class="text-muted">کل مالیت: </span>
                    <strong class="text-success rubik-font fs-4">Rs. <?= format_rs($total_sold_amount) ?></strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>بل #</th>
                            <th>گاہک (خریدار)</th>
                            <th>مارکہ</th>
                            <th>جنس</th>
                            <th>نگ</th>
                            <th>ریٹ</th>
                            <th>کل رقم</th>
                            <th>نوعیت</th>
                            <th>واٹس ایپ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales_list)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-5">اس تاریخ پر کوئی فروخت بل نہیں ہے</td></tr>
                        <?php else: foreach ($sales_list as $row): ?>
                            <tr>
                                <td class="text-center rubik-font fw-bold"><?= htmlspecialchars($row['bill_no']) ?></td>
                                <td class="urdu-font fw-bold fs-6"><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['marke']) ?></span></td>
                                <td><?= htmlspecialchars($row['item_name']) ?> (<?= htmlspecialchars($row['packing_name']) ?>)</td>
                                <td class="text-center rubik-font fw-bold text-primary"><?= $row['nag'] ?></td>
                                <td class="text-end rubik-font">Rs. <?= format_rs($row['rate']) ?></td>
                                <td class="text-end rubik-font fw-bold text-success fs-6">Rs. <?= format_rs($row['net_amount']) ?></td>
                                <td class="text-center">
                                    <?php if ($row['is_paid']): ?>
                                        <span class="badge bg-success">نقد</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">ادھار کھاتہ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="print_sale_bill.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="پرنٹ پرچہ خریدار">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <?php 
                                    $wa_msg = "محترم " . $row['customer_name'] . "، آپ کا فروخت پرچہ بل #" . $row['bill_no'] . " تاریخ " . format_dt($row['sale_date']) . "، جنس: " . $row['item_name'] . "، مارکہ: " . $row['marke'] . "، تعداد: " . $row['nag'] . " نگ، ریٹ: Rs. " . format_rs($row['rate']) . "، کل رقم: Rs. " . format_rs($row['net_amount']) . "۔ شکریہ: " . $settings['shop_name'];
                                    ?>
                                    <a href="<?= whatsapp_url($row['customer_phone'], $wa_msg) ?>" target="_blank" class="btn btn-sm btn-success" title="واٹس ایپ پر بل بھیجیں">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($sales_list)): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">میزان ٹوٹل:</td>
                            <td class="text-center rubik-font text-primary"><?= number_format($total_sold_nag) ?></td>
                            <td></td>
                            <td class="text-end rubik-font text-success">Rs. <?= format_rs($total_sold_amount) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time calculation of Gross and Net Amount
const nagInput = document.getElementById('saleNag');
const rateInput = document.getElementById('saleRate');
const grossPreview = document.getElementById('grossPreview');
const netPreview = document.getElementById('netPreview');
const lotSelect = document.getElementById('lotSelect');
const remNotice = document.getElementById('remNagNotice');

function updateTotals() {
    const nag = parseFloat(nagInput.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;
    const gross = nag * rate;
    grossPreview.innerText = 'Rs. ' + gross.toLocaleString('en-US');
    netPreview.innerText = 'Rs. ' + gross.toLocaleString('en-US');
}

nagInput.addEventListener('input', updateTotals);
rateInput.addEventListener('input', updateTotals);

lotSelect.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const rem = opt.getAttribute('data-rem');
    if (rem) {
        remNotice.innerText = 'اس لاٹ میں دستیاب بقایا نگ: ' + rem;
        nagInput.max = rem;
    } else {
        remNotice.innerText = '';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
