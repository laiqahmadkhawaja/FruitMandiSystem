<?php
/**
 * One Day Analysis Report (ایک روزہ موازنہ اور تجزیاتی رپورٹ)
 * Matching Video Frames 010 & 033
 */
require_once __DIR__ . '/includes/header.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));

// Fetch all customers who had sales or wasooli on this date
$customers = $pdo->query("SELECT id, urdu_name, city, mobile FROM `parties` WHERE `type` = 'customer' ORDER BY `urdu_name` ASC")->fetchAll();

// Sales on $date
$sales_stmt = $pdo->prepare("SELECT s.*, i.urdu_name as item_name 
    FROM `sales_bills` s 
    JOIN `items` i ON s.item_id = i.id 
    WHERE s.sale_date = ?");
$sales_stmt->execute([$date]);
$sales_raw = $sales_stmt->fetchAll();

$cust_sales = [];
$cust_sales_details = [];
foreach ($sales_raw as $s) {
    $cid = $s['customer_id'];
    if (!isset($cust_sales[$cid])) {
        $cust_sales[$cid] = 0;
        $cust_sales_details[$cid] = [];
    }
    $cust_sales[$cid] += $s['net_amount'];
    $cust_sales_details[$cid][] = $s['nag'] . ' نگ ' . $s['item_name'] . ' (' . $s['marke'] . ')';
}

// Wasooli on $date
$wasooli_stmt = $pdo->prepare("SELECT customer_id, (morning_amount + evening_amount) as total_wasooli 
    FROM `bazar_wasooli` 
    WHERE date = ?");
$wasooli_stmt->execute([$date]);
$cust_wasooli = $wasooli_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Cash receipts vouchers on $date
$vr_stmt = $pdo->prepare("SELECT party_id, SUM(amount) as v_amt FROM `vouchers` WHERE voucher_date = ? AND type = 'receipt' GROUP BY party_id");
$vr_stmt->execute([$date]);
$v_receipts = $vr_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Combine and filter only active customers today
$active_rows = [];
$tot_sales = 0;
$tot_wasooli = 0;

foreach ($customers as $c) {
    $cid = $c['id'];
    $s_amt = (float)($cust_sales[$cid] ?? 0);
    $w_amt = (float)($cust_wasooli[$cid] ?? 0) + (float)($v_receipts[$cid] ?? 0);

    if ($s_amt > 0 || $w_amt > 0) {
        $details = implode('، ', $cust_sales_details[$cid] ?? ['-']);
        $diff = $s_amt - $w_amt;

        $tot_sales += $s_amt;
        $tot_wasooli += $w_amt;

        $active_rows[] = [
            'customer' => $c,
            'details' => $details,
            'sale_amt' => $s_amt,
            'wasooli_amt' => $w_amt,
            'diff' => $diff
        ];
    }
}

// Commission Earned
$comm_stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM `sales_bills` WHERE sale_date = ?");
$comm_stmt->execute([$date]);
$total_comm = (float)$comm_stmt->fetchColumn();

// Daily Expenses
$exp_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE voucher_date = ? AND type = 'daily_expense'");
$exp_stmt->execute([$date]);
$total_expenses = (float)$exp_stmt->fetchColumn();

$net_profit = $total_comm - $total_expenses;
?>

<div class="card-custom">
    <!-- Header with Date Selection (Matching Video Frame 010) -->
    <div class="card-custom-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-chart-line text-primary fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">ایک روزہ موازنہ رپورٹ (One Day Analysis & Comparison Report)</h4>
        </div>

        <form method="GET" action="daily_summary.php" class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-bold">تاریخ:</label>
            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
            <a href="print_daily_summary.php?date=<?= urlencode($date) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
            </a>
        </form>
    </div>

    <!-- Top KPI Highlights -->
    <div class="p-3 bg-light border-bottom">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">آج کی کل فروخت (فروخت بل رقم):</span>
                    <h4 class="rubik-font fw-bold text-primary m-0 mt-1">Rs. <?= format_rs($tot_sales) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">آج کی کل وصولی (وصولی جمع):</span>
                    <h4 class="rubik-font fw-bold text-success m-0 mt-1">Rs. <?= format_rs($tot_wasooli) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">آج کا کمیشن منافع:</span>
                    <h4 class="rubik-font fw-bold text-warning text-dark m-0 mt-1">Rs. <?= format_rs($total_comm) ?></h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 bg-white rounded border border-<?= $net_profit >= 0 ? 'success' : 'danger' ?>">
                    <span class="text-muted small">خالص منافع (کمیشن منفی خرچ):</span>
                    <h4 class="rubik-font fw-bold text-<?= $net_profit >= 0 ? 'success' : 'danger' ?> m-0 mt-1">Rs. <?= format_rs($net_profit) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Matching Frame 010 -->
    <div class="table-responsive p-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>نام گاہک لسٹ (Customer Name)</th>
                    <th>تفصیل بل (Bill Particulars)</th>
                    <th style="width: 170px;" class="bg-primary-subtle text-primary">فروخت بل رقم (Sales)</th>
                    <th style="width: 170px;" class="bg-success-subtle text-success">وصولی جمع (Collection)</th>
                    <th style="width: 170px;">ٹوٹل فرق (Difference)</th>
                    <th style="width: 80px;" class="no-print">واٹس ایپ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($active_rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">اس تاریخ پر کوئی فروخت یا وصولی ریکارڈ نہیں ہے</td></tr>
                <?php else: 
                    $c = 1;
                    foreach ($active_rows as $row):
                        $cust = $row['customer'];
                ?>
                    <tr>
                        <td class="text-center rubik-font small"><?= $c++ ?></td>
                        <td class="urdu-font fw-bold fs-5 text-dark">
                            <a href="ledgers.php?party_id=<?= $cust['id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($cust['urdu_name']) ?>
                            </a>
                            <small class="text-muted d-block"><?= htmlspecialchars($cust['city'] ?: '-') ?></small>
                        </td>
                        <td class="urdu-font small"><?= htmlspecialchars($row['details']) ?></td>
                        <td class="text-end rubik-font fw-bold text-primary fs-6">
                            <?= $row['sale_amt'] > 0 ? 'Rs. ' . format_rs($row['sale_amt']) : '-' ?>
                        </td>
                        <td class="text-end rubik-font fw-bold text-success fs-6">
                            <?= $row['wasooli_amt'] > 0 ? 'Rs. ' . format_rs($row['wasooli_amt']) : '-' ?>
                        </td>
                        <td class="text-end rubik-font fw-bold fs-6 <?= $row['diff'] > 0 ? 'text-danger' : 'text-success' ?>">
                            Rs. <?= format_rs(abs($row['diff'])) ?> 
                            <small class="urdu-font">(<?= $row['diff'] > 0 ? 'نام' : ($row['diff'] < 0 ? 'جمع' : '0') ?>)</small>
                        </td>
                        <td class="text-center no-print">
                            <?php 
                            $msg = "محترم " . $cust['urdu_name'] . "، آج تاریخ " . format_dt($date) . " کا موازنہ بل: فروخت Rs. " . format_rs($row['sale_amt']) . "، وصولی جمع Rs. " . format_rs($row['wasooli_amt']) . "۔ شکریہ: " . $settings['shop_name'];
                            ?>
                            <a href="<?= whatsapp_url($cust['mobile'], $msg) ?>" target="_blank" class="btn btn-sm btn-outline-success p-1 px-2">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold fs-6">
                <tr>
                    <td colspan="3" class="text-end urdu-font">کل میزان (Total):</td>
                    <td class="text-end rubik-font text-primary fs-5">Rs. <?= format_rs($tot_sales) ?></td>
                    <td class="text-end rubik-font text-success fs-5">Rs. <?= format_rs($tot_wasooli) ?></td>
                    <td class="text-end rubik-font text-danger fs-5">Rs. <?= format_rs(abs($tot_sales - $tot_wasooli)) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
