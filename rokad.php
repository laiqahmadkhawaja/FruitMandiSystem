<?php
/**
 * Rokad Khata / Daily Cash Book (روکڑ کھاتہ - روزنامچہ کیش رجسٹر)
 * Matching Video Frames 033 & 034
 */
require_once __DIR__ . '/includes/header.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));

// 1. Calculate Opening Cash Balance before $date
$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
$prev_stats = get_cash_in_hand($prev_date);
$opening_cash = $prev_stats['cash_in_hand'];

// 2. Fetch all cash transactions for $date
// Cash Receipts
$r_stmt = $pdo->prepare("SELECT v.*, p.urdu_name as party_name 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE v.voucher_date = ? AND v.type = 'receipt' AND v.payment_method = 'cash'");
$r_stmt->execute([$date]);
$cash_receipts = $r_stmt->fetchAll();

// Cash Sales without duplicate voucher
$cs_stmt = $pdo->prepare("SELECT s.*, p.urdu_name as customer_name 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    LEFT JOIN `vouchers` v ON v.voucher_no = CONCAT('RCP-', s.bill_no)
    WHERE s.sale_date = ? AND s.is_paid = 1 AND v.id IS NULL");
$cs_stmt->execute([$date]);
$cash_sales = $cs_stmt->fetchAll();

// Bazar Wasooli for today
$bw_stmt = $pdo->prepare("SELECT bw.*, p.urdu_name as customer_name 
    FROM `bazar_wasooli` bw 
    JOIN `parties` p ON bw.customer_id = p.id 
    WHERE bw.date = ? AND (bw.morning_amount > 0 OR bw.evening_amount > 0)");
$bw_stmt->execute([$date]);
$bazar_wasooli = $bw_stmt->fetchAll();

// Cash Payments
$p_stmt = $pdo->prepare("SELECT v.*, p.urdu_name as party_name 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE v.voucher_date = ? AND v.type = 'payment' AND v.payment_method = 'cash'");
$p_stmt->execute([$date]);
$cash_payments = $p_stmt->fetchAll();

// Daily Expenses (Cash only)
$e_stmt = $pdo->prepare("SELECT v.*, p.urdu_name as party_name 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE v.voucher_date = ? AND v.type = 'daily_expense' AND v.payment_method = 'cash'");
$e_stmt->execute([$date]);
$daily_expenses = $e_stmt->fetchAll();

// Freight Paid in Cash on Arrival
$f_stmt = $pdo->prepare("SELECT m.*, p.urdu_name as farmer_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    WHERE m.amad_date = ? AND (m.freight_paid_by_agent > 0 OR m.local_cartage > 0)");
$f_stmt->execute([$date]);
$freight_entries = $f_stmt->fetchAll();

// Compile Combined Ledger Lines
$entries = [];

// Receipts
foreach ($cash_receipts as $cr) {
    $entries[] = [
        'type' => 'in',
        'ref' => $cr['voucher_no'],
        'details' => 'روپے وصول بذریعہ واؤچر: ' . ($cr['party_name'] ?: 'عام وصولی') . ' - ' . $cr['narration'],
        'amount' => (float)$cr['amount']
    ];
}

// Cash Sales
foreach ($cash_sales as $cs) {
    $entries[] = [
        'type' => 'in',
        'ref' => $cs['bill_no'],
        'details' => 'نقد سیل بل: ' . $cs['customer_name'] . ' (' . $cs['nag'] . ' نگ)',
        'amount' => (float)$cs['net_amount']
    ];
}

// Bazar Wasooli
foreach ($bazar_wasooli as $bw) {
    $amt = (float)$bw['morning_amount'] + (float)$bw['evening_amount'];
    $entries[] = [
        'type' => 'in',
        'ref' => 'بازار وصولی',
        'details' => 'بازار کھاتہ وصولی: ' . $bw['customer_name'] . ' (صبح: ' . format_rs($bw['morning_amount']) . ' + شام: ' . format_rs($bw['evening_amount']) . ')',
        'amount' => $amt
    ];
}

// Payments
foreach ($cash_payments as $cp) {
    $entries[] = [
        'type' => 'out',
        'ref' => $cp['voucher_no'],
        'details' => 'ادائیگی بذریعہ واؤچر: ' . ($cp['party_name'] ?: 'پارٹی ادائیگی') . ' - ' . $cp['narration'],
        'amount' => (float)$cp['amount']
    ];
}

// Expenses
foreach ($daily_expenses as $de) {
    $entries[] = [
        'type' => 'out',
        'ref' => $de['voucher_no'],
        'details' => 'دکان روزانہ خرچہ: ' . ($de['party_name'] ?: '') . ' ' . $de['narration'],
        'amount' => (float)$de['amount']
    ];
}

// Freight
foreach ($freight_entries as $fe) {
    $fr_amt = (float)$fe['freight_paid_by_agent'] + (float)$fe['local_cartage'];
    $entries[] = [
        'type' => 'out',
        'ref' => $fe['challan_no'],
        'details' => 'گاڑی کرایہ ادائیگی لاٹ ' . $fe['marke'] . ' (' . $fe['farmer_name'] . ')',
        'amount' => $fr_amt
    ];
}

// Compute Today Totals
$today_total_in = 0;
$today_total_out = 0;
foreach ($entries as $e) {
    if ($e['type'] === 'in') $today_total_in += $e['amount'];
    if ($e['type'] === 'out') $today_total_out += $e['amount'];
}
$closing_cash = $opening_cash + $today_total_in - $today_total_out;

// Commission Earned Today
$comm_stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM `sales_bills` WHERE sale_date = ?");
$comm_stmt->execute([$date]);
$today_comm = (float)$comm_stmt->fetchColumn();

// Total Expenses Today
$exp_today = 0;
foreach ($daily_expenses as $de) $exp_today += $de['amount'];
$today_profit = $today_comm - $exp_today;
?>

<div class="card-custom">
    <!-- Header with Date Selection -->
    <div class="card-custom-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-book-bookmark text-success fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">روکڑ کھاتہ (Daily Cash Register / Rokad)</h4>
        </div>

        <form method="GET" action="rokad.php" class="d-flex align-items-center gap-2">
            <label class="small text-muted fw-bold">تاریخ منتخب کریں:</label>
            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
            <a href="print_rokad.php?date=<?= urlencode($date) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
            </a>
        </form>
    </div>

    <!-- Summary Box (Matching Video Frame 033) -->
    <div class="p-3 bg-light border-bottom">
        <div class="row g-3 text-center align-items-center">
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-white">
                    <span class="text-muted small">سابقہ اوپننگ روکڑ:</span>
                    <h5 class="rubik-font fw-bold m-0 text-secondary mt-1">Rs. <?= format_rs($opening_cash) ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-white">
                    <span class="text-muted small">آج کی کل آمد (آمدن):</span>
                    <h5 class="rubik-font fw-bold m-0 text-success mt-1">+ Rs. <?= format_rs($today_total_in) ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-white">
                    <span class="text-muted small">آج کا کل خرچ (ادائیگی):</span>
                    <h5 class="rubik-font fw-bold m-0 text-danger mt-1">- Rs. <?= format_rs($today_total_out) ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded p-2 bg-success-subtle border-success">
                    <span class="text-success small fw-bold">موجودہ روکڑ کیش (Closing):</span>
                    <h4 class="rubik-font fw-bold m-0 text-success mt-1">Rs. <?= format_rs($closing_cash) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Rokad Detailed Table (Matching Video Frame 034) -->
    <div class="table-responsive p-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 140px;">بل / حوالہ #</th>
                    <th>تفصیل اندراج (Narration)</th>
                    <th style="width: 160px;" class="bg-success-subtle text-success">رقم آمد (Cash In)</th>
                    <th style="width: 160px;" class="bg-danger-subtle text-danger">رقم خرچ (Cash Out)</th>
                    <th style="width: 180px;">بقایا روکڑ (Balance)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="table-light fw-bold">
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="urdu-font">سابقہ کیش بیلنس بشمول پرانے بقایا جات (Opening Cash in Hand)</td>
                    <td class="text-end rubik-font text-success">Rs. <?= format_rs($opening_cash) ?></td>
                    <td class="text-end rubik-font text-muted">-</td>
                    <td class="text-end rubik-font fw-bold fs-6">Rs. <?= format_rs($opening_cash) ?></td>
                </tr>

                <?php 
                $running_balance = $opening_cash;
                $idx = 1;
                if (empty($entries)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">آج کی تاریخ میں کوئی کیش ٹرانزیکشن نہیں ہوئی</td></tr>
                <?php else: foreach ($entries as $item): 
                    if ($item['type'] === 'in') {
                        $running_balance += $item['amount'];
                    } else {
                        $running_balance -= $item['amount'];
                    }
                ?>
                    <tr>
                        <td class="text-center rubik-font small"><?= $idx++ ?></td>
                        <td class="text-center rubik-font small"><span class="badge bg-secondary"><?= htmlspecialchars($item['ref']) ?></span></td>
                        <td class="urdu-font fs-6"><?= htmlspecialchars($item['details']) ?></td>
                        <td class="text-end rubik-font fw-bold text-success">
                            <?= $item['type'] === 'in' ? 'Rs. ' . format_rs($item['amount']) : '-' ?>
                        </td>
                        <td class="text-end rubik-font fw-bold text-danger">
                            <?= $item['type'] === 'out' ? 'Rs. ' . format_rs($item['amount']) : '-' ?>
                        </td>
                        <td class="text-end rubik-font fw-bold fs-6">Rs. <?= format_rs($running_balance) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold fs-6">
                <tr>
                    <td colspan="3" class="text-end urdu-font">کل آمد و خرچ میزان:</td>
                    <td class="text-end rubik-font text-success">Rs. <?= format_rs($today_total_in) ?></td>
                    <td class="text-end rubik-font text-danger">Rs. <?= format_rs($today_total_out) ?></td>
                    <td class="text-end rubik-font text-primary fs-5">Rs. <?= format_rs($closing_cash) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Profit & Loss Summary Card (Matching Frame 033 Bottom) -->
    <div class="m-3 p-3 border rounded bg-light">
        <h5 class="urdu-font fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="fa-solid fa-chart-pie text-primary me-2"></i> آج کا نفع نقصان خلاصہ (Today's Profit & Loss Summary)
        </h5>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">آج کی کمیشن آمدن (Commission Income):</span>
                    <h5 class="rubik-font fw-bold text-success m-0 mt-1">Rs. <?= format_rs($today_comm) ?></h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">آج کے کل دکان اخراجات (Shop Expenses):</span>
                    <h5 class="rubik-font fw-bold text-danger m-0 mt-1">Rs. <?= format_rs($exp_today) ?></h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 bg-white rounded border border-<?= $today_profit >= 0 ? 'success' : 'danger' ?>">
                    <span class="text-muted small">آج کا خالص منافع (Net Today P&L):</span>
                    <h4 class="rubik-font fw-bold text-<?= $today_profit >= 0 ? 'success' : 'danger' ?> m-0 mt-1">Rs. <?= format_rs($today_profit) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
