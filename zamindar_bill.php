<?php
/**
 * Zamindar Bill / Fard Hisab (زمیندار بل / فردِ حساب)
 */
require_once __DIR__ . '/functions.php';

$amad_id = (int)($_GET['amad_id'] ?? 0);
$bill_id = (int)($_GET['bill_id'] ?? 0);

// Handle Generating / Saving Fard Hisab BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_fard_hisab') {
        $p_amad_id = (int)$_POST['amad_id'];
        $p_farmer_id = (int)$_POST['farmer_id'];
        $bill_date = clean($_POST['bill_date'] ?? date('Y-m-d'));
        $total_nag = (int)$_POST['total_nag'];
        $gross_sale = (float)$_POST['gross_sale_amount'];
        $comm_rate = (float)$_POST['commission_rate'];
        $comm_amount = (float)$_POST['commission_amount'];
        $freight_ded = (float)$_POST['freight_deduction'];
        $local_cartage = (float)$_POST['local_cartage_deduction'];
        $mazdoori = (float)$_POST['mazdoori_amount'];
        $market_fee = (float)$_POST['market_fee_amount'];
        $munshiana = (float)$_POST['munshiana_amount'];
        $advance = (float)$_POST['advance_adjusted'];
        $net_payable = (float)$_POST['net_payable'];
        $notes = clean($_POST['notes'] ?? '');

        $next_zb = (int)$pdo->query("SELECT MAX(id) FROM `zamindar_bills`")->fetchColumn() + 1;
        $bill_no = 'ZB-' . str_pad($next_zb, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO `zamindar_bills` 
            (`bill_no`, `bill_date`, `farmer_id`, `amad_id`, `total_nag`, `gross_sale_amount`, `commission_rate`, `commission_amount`, `freight_deduction`, `local_cartage_deduction`, `mazdoori_amount`, `market_fee_amount`, `munshiana_amount`, `advance_adjusted`, `net_payable`, `notes`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $bill_no, $bill_date, $p_farmer_id, $p_amad_id, $total_nag,
            $gross_sale, $comm_rate, $comm_amount, $freight_ded, $local_cartage,
            $mazdoori, $market_fee, $munshiana, $advance, $net_payable, $notes
        ]);

        // Mark amad lot as bill posted
        $pdo->prepare("UPDATE `maal_amad` SET `bill_posted` = 1 WHERE `id` = ?")->execute([$p_amad_id]);

        flash_set("زمیندار فردِ حساب بل #{$bill_no} کامیابی سے تیار ہو گیا۔ صافی رقم: Rs. " . format_rs($net_payable), 'success');
        header("Location: zamindar_bill.php?bill_id=" . $pdo->lastInsertId());
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

// Case 1: Viewing already generated bill
$saved_bill = null;
if ($bill_id > 0) {
    $stmt = $pdo->prepare("SELECT zb.*, p.urdu_name as farmer_name, p.city as farmer_city, p.mobile as farmer_mobile, m.challan_no, m.marke, m.amad_date, i.urdu_name as item_name, pk.urdu_name as packing_name 
        FROM `zamindar_bills` zb 
        JOIN `parties` p ON zb.farmer_id = p.id 
        JOIN `maal_amad` m ON zb.amad_id = m.id 
        JOIN `items` i ON m.item_id = i.id 
        JOIN `packings` pk ON m.packing_id = pk.id 
        WHERE zb.id = ?");
    $stmt->execute([$bill_id]);
    $saved_bill = $stmt->fetch();
}

// Case 2: Preparing new bill for an Inward Lot
$amad = null;
$sales_items = [];
$gross_total = 0;
$total_sold = 0;

if ($amad_id > 0) {
    $stmt = $pdo->prepare("SELECT m.*, p.urdu_name as farmer_name, p.city as farmer_city, p.mobile as farmer_mobile, i.urdu_name as item_name, pk.urdu_name as packing_name 
        FROM `maal_amad` m 
        JOIN `parties` p ON m.farmer_id = p.id 
        JOIN `items` i ON m.item_id = i.id 
        JOIN `packings` pk ON m.packing_id = pk.id 
        WHERE m.id = ?");
    $stmt->execute([$amad_id]);
    $amad = $stmt->fetch();

    if ($amad) {
        // Fetch all sales for this amad lot
        $s_stmt = $pdo->prepare("SELECT s.*, p.urdu_name as customer_name 
            FROM `sales_bills` s 
            JOIN `parties` p ON s.customer_id = p.id 
            WHERE s.amad_id = ? 
            ORDER BY s.id ASC");
        $s_stmt->execute([$amad_id]);
        $sales_items = $s_stmt->fetchAll();

        foreach ($sales_items as $s) {
            $gross_total += $s['gross_amount'];
            $total_sold += $s['nag'];
        }
    }
}

// All Generated Bills List
$all_bills = $pdo->query("SELECT zb.*, p.urdu_name as farmer_name, m.challan_no, m.marke, i.urdu_name as item_name 
    FROM `zamindar_bills` zb 
    JOIN `parties` p ON zb.farmer_id = p.id 
    JOIN `maal_amad` m ON zb.amad_id = m.id 
    JOIN `items` i ON m.item_id = i.id 
    ORDER BY zb.id DESC LIMIT 20")->fetchAll();
?>

<!-- If Saved Bill is being viewed/printed -->
<?php if ($saved_bill): ?>
<div class="row justify-content-center mb-4">
    <div class="col-12 col-lg-8">
        <div class="card-custom p-4 bg-white shadow" id="printableInvoice">
            <!-- Header -->
            <div class="text-center border-bottom pb-3 mb-3">
                <h2 class="urdu-font fw-bold m-0 text-success"><?= htmlspecialchars($settings['shop_name']) ?></h2>
                <div class="text-muted small"><?= htmlspecialchars($settings['shop_subheading']) ?> - <?= htmlspecialchars($settings['mandi_name']) ?>, <?= htmlspecialchars($settings['city']) ?></div>
                <div class="rubik-font small"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($settings['phone']) ?></div>
                <h4 class="mt-2 text-dark border d-inline-block px-3 py-1 rounded bg-light urdu-font">زمیندار فردِ حساب بل (Farmer Inward Statement)</h4>
            </div>

            <!-- Meta details -->
            <div class="row g-2 mb-3 small">
                <div class="col-6">
                    <div><strong>بل نمبر:</strong> <span class="rubik-font fw-bold text-primary"><?= $saved_bill['bill_no'] ?></span></div>
                    <div><strong>زمیندار نام:</strong> <span class="urdu-font fw-bold fs-6"><?= $saved_bill['farmer_name'] ?> (<?= $saved_bill['farmer_city'] ?>)</span></div>
                    <div><strong>مارکہ:</strong> <span class="badge bg-secondary"><?= $saved_bill['marke'] ?></span></div>
                </div>
                <div class="col-6 text-end">
                    <div><strong>تاریخ بل:</strong> <span class="rubik-font"><?= format_dt($saved_bill['bill_date']) ?></span></div>
                    <div><strong>چالان آمد #:</strong> <span class="rubik-font"><?= $saved_bill['challan_no'] ?></span> (<?= format_dt($saved_bill['amad_date']) ?>)</div>
                    <div><strong>جنس و پیکنگ:</strong> <?= $saved_bill['item_name'] ?> (<?= $saved_bill['packing_name'] ?>)</div>
                </div>
            </div>

            <!-- Financial Table -->
            <table class="table table-bordered align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th>تفصیل</th>
                        <th>مقدار / شرح</th>
                        <th>رقم (روپے)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold">مجموعی فروخت مالیت (Gross Sales)</td>
                        <td class="text-center rubik-font"><?= $saved_bill['total_nag'] ?> نگ</td>
                        <td class="text-end rubik-font fw-bold fs-6">Rs. <?= format_rs($saved_bill['gross_sale_amount']) ?></td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-danger"><i class="fa-solid fa-minus-circle me-1"></i> منڈی کٹوتیاں (Deductions):</td>
                    </tr>
                    <tr>
                        <td>کمیشن آڑھت (Commission)</td>
                        <td class="text-center rubik-font"><?= $saved_bill['commission_rate'] ?>%</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['commission_amount']) ?></td>
                    </tr>
                    <tr>
                        <td>گاڑی کرایہ کٹوتی (Freight Paid)</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['freight_deduction']) ?></td>
                    </tr>
                    <tr>
                        <td>لوکل کرایہ / اڈا خرچہ</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['local_cartage_deduction']) ?></td>
                    </tr>
                    <tr>
                        <td>مزدوری (Labour)</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['mazdoori_amount']) ?></td>
                    </tr>
                    <tr>
                        <td>مارکیٹ فیس (Market Committee Tax)</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['market_fee_amount']) ?></td>
                    </tr>
                    <tr>
                        <td>منشیانہ</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['munshiana_amount']) ?></td>
                    </tr>
                    <?php if ($saved_bill['advance_adjusted'] > 0): ?>
                    <tr>
                        <td class="fw-bold text-danger">سابقہ پیشگی رقم ایڈجسٹمنٹ (Advance)</td>
                        <td class="text-center">-</td>
                        <td class="text-end rubik-font text-danger">- Rs. <?= format_rs($saved_bill['advance_adjusted']) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-success border-2 border-success">
                    <tr class="fs-5">
                        <td colspan="2" class="fw-bold text-success text-end urdu-font">صافی قابلِ ادائیگی رقم (Net Payable to Farmer):</td>
                        <td class="text-end rubik-font fw-bold text-success fs-4">Rs. <?= format_rs($saved_bill['net_payable']) ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="d-flex justify-content-between text-muted small mt-4 pt-4 border-top">
                <div>دستخط منشی / اکاؤنٹنٹ: ______________</div>
                <div>دستخط آڑھتی / مالک: ______________</div>
            </div>

            <!-- Action buttons -->
            <div class="mt-4 text-center no-print d-flex gap-2 justify-content-center">
                <button onclick="window.print()" class="btn btn-primary px-4">
                    <i class="fa-solid fa-print"></i> پرنٹ بل نکالیں
                </button>
                <?php 
                $wa_msg = "محترم زمیندار صاحب (" . $saved_bill['farmer_name'] . ")، آپ کی لاٹ مارکہ " . $saved_bill['marke'] . " (چالان #" . $saved_bill['challan_no'] . ") کا فردِ حساب تیار ہے۔ کل فروخت: Rs. " . format_rs($saved_bill['gross_sale_amount']) . "، تمام کٹوتیاں نکال کر صافی رقم: Rs. " . format_rs($saved_bill['net_payable']) . "۔ شکریہ: " . $settings['shop_name'];
                ?>
                <a href="<?= whatsapp_url($saved_bill['farmer_mobile'], $wa_msg) ?>" target="_blank" class="btn btn-success px-4">
                    <i class="fa-brands fa-whatsapp"></i> واٹس ایپ پر فرد بھیجیں
                </a>
                <a href="zamindar_bill.php" class="btn btn-outline-secondary px-3">واپس لسٹ</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Form for generating bill for an Amad Lot -->
<?php if ($amad && !$saved_bill): 
    $comm_percent = (float)$settings['default_commission_rate'];
    $comm_amount = ($gross_total * $comm_percent) / 100;
    $mazdoori = $amad['total_nag'] * (float)$settings['default_mazdoori_per_nag'];
    $market_fee = ($gross_total * (float)$settings['default_market_fee_percent']) / 100;
    $munshiana = (float)$settings['default_munshiana_per_bill'];
    $freight = (float)$amad['freight_paid_by_agent'];
    $local_cartage = (float)$amad['local_cartage'];
    $total_deductions = $comm_amount + $mazdoori + $market_fee + $munshiana + $freight + $local_cartage;
    $net_calc = $gross_total - $total_deductions;
?>
<div class="row justify-content-center mb-4">
    <div class="col-12 col-xl-9">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-calculator text-success me-2"></i> نیا فردِ حساب بنائیں (Generate Farmer Statement)</span>
                <span class="badge bg-primary">چالان # <?= htmlspecialchars($amad['challan_no']) ?></span>
            </div>
            <div class="p-4">
                <form method="POST" action="zamindar_bill.php">
                    <input type="hidden" name="post_action" value="save_fard_hisab">
                    <input type="hidden" name="amad_id" value="<?= $amad['id'] ?>">
                    <input type="hidden" name="farmer_id" value="<?= $amad['farmer_id'] ?>">
                    <input type="hidden" name="total_nag" value="<?= $amad['total_nag'] ?>">

                    <div class="row g-3 mb-3 bg-light p-3 rounded">
                        <div class="col-md-4">
                            <strong>زمیندار:</strong> <span class="urdu-font fs-5 text-dark"><?= $amad['farmer_name'] ?> (<?= $amad['farmer_city'] ?>)</span>
                        </div>
                        <div class="col-md-3">
                            <strong>مارکہ:</strong> <span class="badge bg-dark fs-6"><?= $amad['marke'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>جنس:</strong> <?= $amad['item_name'] ?> (<?= $amad['packing_name'] ?>)
                        </div>
                        <div class="col-md-2 text-end">
                            <strong>کل نگ:</strong> <span class="rubik-font fw-bold fs-5 text-primary"><?= $amad['total_nag'] ?></span>
                        </div>
                    </div>

                    <!-- Breakdown of sales -->
                    <h5 class="fw-bold text-muted mb-2">اس لاٹ کی فروخت تفصیل (Sales Breakdown):</h5>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>بل #</th>
                                    <th>گاہک</th>
                                    <th>نگ</th>
                                    <th>ریٹ</th>
                                    <th>کل رقم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_items as $si): ?>
                                    <tr>
                                        <td><?= $si['bill_no'] ?></td>
                                        <td><?= $si['customer_name'] ?></td>
                                        <td class="text-center"><?= $si['nag'] ?></td>
                                        <td class="text-end">Rs. <?= format_rs($si['rate']) ?></td>
                                        <td class="text-end">Rs. <?= format_rs($si['gross_amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2">کل فروخت:</td>
                                    <td class="text-center"><?= $total_sold ?> نگ</td>
                                    <td></td>
                                    <td class="text-end text-success fs-6">Rs. <?= format_rs($gross_total) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Deductions Input Grid -->
                    <h5 class="fw-bold text-danger mb-2">منڈی کٹوتیاں (Deductions):</h5>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">کل فروخت رقم</label>
                            <input type="number" step="0.01" name="gross_sale_amount" id="grossSale" class="form-control rubik-font fw-bold" value="<?= $gross_total ?>" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">کمیشن شرح (%)</label>
                            <input type="number" step="0.01" name="commission_rate" id="commRate" class="form-control rubik-font" value="<?= $comm_percent ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">کمیشن رقم</label>
                            <input type="number" step="0.01" name="commission_amount" id="commAmt" class="form-control rubik-font text-danger" value="<?= $comm_amount ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">گاڑی کرایہ کٹوتی</label>
                            <input type="number" step="0.01" name="freight_deduction" id="freightDed" class="form-control rubik-font text-danger" value="<?= $freight ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">لوکل کرایہ / اڈا</label>
                            <input type="number" step="0.01" name="local_cartage_deduction" id="localCart" class="form-control rubik-font text-danger" value="<?= $local_cartage ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">مزدوری رقم</label>
                            <input type="number" step="0.01" name="mazdoori_amount" id="mazAmt" class="form-control rubik-font text-danger" value="<?= $mazdoori ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">مارکیٹ فیس ٹیکس</label>
                            <input type="number" step="0.01" name="market_fee_amount" id="mktFee" class="form-control rubik-font text-danger" value="<?= $market_fee ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">منشیانہ</label>
                            <input type="number" step="0.01" name="munshiana_amount" id="munshiana" class="form-control rubik-font text-danger" value="<?= $munshiana ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">پیشگی رقم کٹوتی (Advance)</label>
                            <input type="number" step="0.01" name="advance_adjusted" id="advAdj" class="form-control rubik-font text-primary" value="0">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-success fs-6">صافی قابلِ ادائیگی رقم (Net Payable to Farmer)</label>
                            <input type="number" step="0.01" name="net_payable" id="netPayable" class="form-control rubik-font fs-4 fw-bold text-success border-success" value="<?= $net_calc ?>" readonly>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success py-2 fs-5">
                            <i class="fa-solid fa-file-invoice-dollar"></i> فردِ حساب بل جاری کریں (Save & Generate Bill)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto recalculate Net Payable
function recalcNet() {
    const gross = parseFloat(document.getElementById('grossSale').value) || 0;
    const commRate = parseFloat(document.getElementById('commRate').value) || 0;
    const commAmt = (gross * commRate) / 100;
    document.getElementById('commAmt').value = commAmt.toFixed(2);

    const freight = parseFloat(document.getElementById('freightDed').value) || 0;
    const localCart = parseFloat(document.getElementById('localCart').value) || 0;
    const maz = parseFloat(document.getElementById('mazAmt').value) || 0;
    const mkt = parseFloat(document.getElementById('mktFee').value) || 0;
    const munshiana = parseFloat(document.getElementById('munshiana').value) || 0;
    const adv = parseFloat(document.getElementById('advAdj').value) || 0;

    const net = gross - (commAmt + freight + localCart + maz + mkt + munshiana + adv);
    document.getElementById('netPayable').value = net.toFixed(2);
}

['commRate', 'commAmt', 'freightDed', 'localCart', 'mazAmt', 'mktFee', 'munshiana', 'advAdj'].forEach(id => {
    document.getElementById(id).addEventListener('input', recalcNet);
});
</script>
<?php endif; ?>

<!-- List of Generated Farmer Bills -->
<div class="card-custom">
    <div class="card-custom-header">
        <span><i class="fa-solid fa-list text-primary me-2"></i> جاری شدہ زمیندار فردِ حساب بلز (Generated Farmer Bills)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-mandi table-hover align-middle">
            <thead>
                <tr>
                    <th>بل #</th>
                    <th>تاریخ</th>
                    <th>زمیندار</th>
                    <th>مارکہ</th>
                    <th>چالان #</th>
                    <th>نگ</th>
                    <th>کل فروخت</th>
                    <th>کمیشن</th>
                    <th>صافی ادائیگی</th>
                    <th>پرنٹ / ایکشن</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_bills)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">کوئی زمیندار بل نہیں ہے</td></tr>
                <?php else: foreach ($all_bills as $b): ?>
                    <tr>
                        <td class="rubik-font fw-bold text-center"><?= $b['bill_no'] ?></td>
                        <td class="text-center small"><?= format_dt($b['bill_date']) ?></td>
                        <td class="urdu-font fw-bold fs-6"><?= $b['farmer_name'] ?></td>
                        <td><span class="badge bg-secondary"><?= $b['marke'] ?></span></td>
                        <td class="text-center rubik-font"><?= $b['challan_no'] ?></td>
                        <td class="text-center rubik-font"><?= $b['total_nag'] ?></td>
                        <td class="text-end rubik-font">Rs. <?= format_rs($b['gross_sale_amount']) ?></td>
                        <td class="text-end rubik-font text-danger">Rs. <?= format_rs($b['commission_amount']) ?></td>
                        <td class="text-end rubik-font fw-bold text-success fs-6">Rs. <?= format_rs($b['net_payable']) ?></td>
                        <td class="text-center">
                            <a href="zamindar_bill.php?bill_id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye"></i> بل دیکھیں
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
