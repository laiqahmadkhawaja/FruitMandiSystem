<?php
/**
 * Dedicated Print Template: Rokad Khata / Daily Cash Book (روزنامچہ روکڑ کھاتہ)
 * Formatted for A4 portrait with traditional Mandi Cash Accounting layout
 */
require_once __DIR__ . '/functions.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));
$settings = get_settings();

// 1. Calculate Opening Cash Balance before $date
$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
$prev_stats = get_cash_in_hand($prev_date);
$opening_cash = (float)$prev_stats['cash_in_hand'];

// 2. Fetch all cash transactions for $date
// Cash Receipts
$r_stmt = $pdo->prepare("SELECT v.*, p.urdu_name as party_name 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE v.voucher_date = ? AND v.type = 'receipt' AND v.payment_method = 'cash'");
$r_stmt->execute([$date]);
$cash_receipts = $r_stmt->fetchAll();

// Cash Sales
$cs_stmt = $pdo->prepare("SELECT s.*, p.urdu_name as customer_name 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    WHERE s.sale_date = ? AND s.is_paid = 1");
$cs_stmt->execute([$date]);
$cash_sales = $cs_stmt->fetchAll();

// Bazar Wasooli
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

// Daily Expenses
$e_stmt = $pdo->prepare("SELECT v.*, p.urdu_name as party_name 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE v.voucher_date = ? AND v.type = 'daily_expense'");
$e_stmt->execute([$date]);
$daily_expenses = $e_stmt->fetchAll();

// Freight Paid in Cash on Arrival
$f_stmt = $pdo->prepare("SELECT m.*, p.urdu_name as farmer_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    WHERE m.amad_date = ? AND (m.freight_paid_by_agent > 0 OR m.local_cartage > 0)");
$f_stmt->execute([$date]);
$freight_entries = $f_stmt->fetchAll();

// Build Receipts (آمد / بنام)
$inflows = [];
foreach ($cash_receipts as $cr) {
    $inflows[] = [
        'ref' => $cr['voucher_no'],
        'details' => ($cr['party_name'] ? $cr['party_name'] . ': ' : '') . $cr['narration'],
        'amount' => (float)$cr['amount']
    ];
}
foreach ($cash_sales as $cs) {
    $inflows[] = [
        'ref' => $cs['bill_no'],
        'details' => 'نقد فروخت: ' . $cs['customer_name'] . ' (' . $cs['nag'] . ' نگ)',
        'amount' => (float)$cs['net_amount']
    ];
}
foreach ($bazar_wasooli as $bw) {
    $tot = (float)$bw['morning_amount'] + (float)$bw['evening_amount'];
    $inflows[] = [
        'ref' => 'بازار وصولی',
        'details' => $bw['customer_name'] . ' (صبح: ' . format_rs($bw['morning_amount']) . '، شام: ' . format_rs($bw['evening_amount']) . ')',
        'amount' => $tot
    ];
}

// Build Payments (خرچ / جمع)
$outflows = [];
foreach ($cash_payments as $cp) {
    $outflows[] = [
        'ref' => $cp['voucher_no'],
        'details' => ($cp['party_name'] ? $cp['party_name'] . ': ' : '') . $cp['narration'],
        'amount' => (float)$cp['amount']
    ];
}
foreach ($daily_expenses as $de) {
    $outflows[] = [
        'ref' => $de['voucher_no'],
        'details' => 'روزمرہ خرچہ: ' . $de['narration'],
        'amount' => (float)$de['amount']
    ];
}
foreach ($freight_entries as $fe) {
    $f_tot = (float)$fe['freight_paid_by_agent'] + (float)$fe['local_cartage'];
    $outflows[] = [
        'ref' => 'چالان ' . $fe['challan_no'],
        'details' => 'کرایہ گاڑی ادائیگی: ' . $fe['farmer_name'] . ' (مارکہ ' . $fe['marke'] . ')',
        'amount' => $f_tot
    ];
}

$tot_inflow = array_sum(array_column($inflows, 'amount'));
$tot_outflow = array_sum(array_column($outflows, 'amount'));
$closing_cash = $opening_cash + $tot_inflow - $tot_outflow;

// Max rows to align tables
$max_rows = max(count($inflows), count($outflows), 1);
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>روکڑ کھاتہ - <?= format_dt($date) ?></title>
    <style>
        @font-face {
            font-family: 'Jameel Noori Nastaleeq';
            src: local('Jameel Noori Nastaleeq'),
                 url('assets/fonts/JameelNooriNastaleeq.ttf') format('truetype');
        }
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Jameel Noori Nastaleeq', Tahoma, serif;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-size: 13pt;
            line-height: 1.7;
            direction: rtl;
        }
        .num {
            font-family: 'Segoe UI', Tahoma, sans-serif !important;
            direction: ltr;
            display: inline-block;
        }
        #action-bar {
            background: #2c3e50;
            color: #fff;
            padding: 10px 20px;
            text-align: center;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-size: 14px;
        }
        .btn-print {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 8px 24px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-close-window {
            background: #7f8c8d;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            #action-bar { display: none !important; }
            body { font-size: 11.5pt; line-height: 1.5; }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
        }
        .header-box {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .shop-name {
            font-size: 24pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.3;
        }
        .shop-meta {
            font-size: 12pt;
            margin: 2px 0;
        }
        .report-badge {
            display: inline-block;
            border: 2px solid #000;
            padding: 2px 20px;
            font-size: 16pt;
            font-weight: bold;
            background: #f0f0f0;
            margin-top: 5px;
        }
        .date-strip {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            padding: 4px 12px;
            margin-bottom: 12px;
            background: #fdfdfd;
            font-size: 13pt;
        }
        .cash-columns {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }
        .cash-col {
            flex: 1;
            border: 1.5px solid #000;
        }
        .col-header {
            background: #e8e8e8;
            border-bottom: 1.5px solid #000;
            padding: 6px 10px;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
        }
        table.side-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.side-table th, table.side-table td {
            border-bottom: 1px solid #ccc;
            padding: 4px 6px;
            font-size: 11.5pt;
        }
        table.side-table th {
            background: #f4f4f4;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }
        .khatam-box {
            border: 2px solid #000;
            padding: 10px 15px;
            background: #f7f7f7;
            margin-bottom: 20px;
        }
        .khatam-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            text-align: center;
            gap: 10px;
        }
        .k-item {
            border: 1px solid #000;
            background: #fff;
            padding: 6px 4px;
        }
        .k-label {
            font-size: 12pt;
            font-weight: bold;
            display: block;
        }
        .k-val {
            font-size: 15pt;
            font-weight: bold;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            padding: 15px 40px;
            text-align: center;
        }
        .sig-block {
            width: 35%;
            border-top: 1.5px dashed #000;
            padding-top: 4px;
            font-size: 13pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div id="action-bar">
    <div>
        <strong>پرنٹ پریویو - روکڑ کھاتہ روزنامچہ</strong> &nbsp;|&nbsp; 
        <span>تاریخ: <span class="num"><?= format_dt($date) ?></span></span> &nbsp;|&nbsp;
        <span>بقایا کیش ان ہینڈ: <strong>Rs. <?= format_rs($closing_cash) ?></strong></span>
    </div>
    <div style="display:flex; gap: 10px;">
        <button class="btn-print" onclick="window.print()">
            🖨️ پرنٹ نکالیں (Print)
        </button>
        <button class="btn-close-window" onclick="window.close()">
            بند کریں (Close)
        </button>
    </div>
</div>

<div class="header-box">
    <h1 class="shop-name"><?= htmlspecialchars(($settings['shop_name'] ?? '') ?: 'سبحانی فروٹ کمپنی') ?></h1>
    <div class="shop-meta">
        <?= htmlspecialchars(($settings['address'] ?? '') ?: ((($settings['mandi_name'] ?? '') . '، ' . ($settings['city'] ?? '')) ?: 'غلہ و سبزی و فروٹ منڈی، سیالکوٹ')) ?>
        <?php if (!empty($settings['phone'])): ?>
            &nbsp;|&nbsp; فون: <span class="num"><?= htmlspecialchars($settings['phone']) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <span class="report-badge">روزنامچہ روکڑ کھاتہ (Daily Cash Register / Rokad)</span>
    </div>
</div>

<div class="date-strip">
    <div>
        <strong>بتاریخ:</strong> <span class="num"><?= format_dt($date) ?> (<?= date('l', strtotime($date)) ?>)</span>
    </div>
    <div>
        <strong>افتتاحی روکڑ (Opening Cash):</strong> <strong class="num">Rs. <?= format_rs($opening_cash) ?></strong>
    </div>
    <div>
        <strong>اختتامی روکڑ (Closing Cash):</strong> <strong class="num" style="font-size: 14pt; color: #000;">Rs. <?= format_rs($closing_cash) ?></strong>
    </div>
</div>

<!-- Classic Two Column Inflow & Outflow Structure -->
<div class="cash-columns">
    <!-- Inflows (آمد / بنام) -->
    <div class="cash-col">
        <div class="col-header" style="background: #eafaf1;">
            آمد / وصولیاں (Cash Receipts / Inflows)
        </div>
        <table class="side-table">
            <thead>
                <tr>
                    <th style="width: 70px;">حوالہ #</th>
                    <th>تفصیل و منقولہ</th>
                    <th style="width: 100px; text-align: left;">رقم (روپے)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Cash Entry -->
                <tr style="background: #fdfae5;">
                    <td style="text-align: center;"><span class="num">-</span></td>
                    <td><strong>سابقہ افتتاحی کیش ان ہینڈ (Opening Cash)</strong></td>
                    <td style="text-align: left;"><strong class="num"><?= format_rs($opening_cash) ?></strong></td>
                </tr>

                <?php foreach ($inflows as $in): ?>
                    <tr>
                        <td style="text-align: center;"><span class="num" style="font-size: 10pt;"><?= htmlspecialchars($in['ref']) ?></span></td>
                        <td><?= htmlspecialchars($in['details']) ?></td>
                        <td style="text-align: left;"><span class="num"><?= format_rs($in['amount']) ?></span></td>
                    </tr>
                <?php endforeach; ?>

                <?php 
                $extra_rows = $max_rows - count($inflows);
                for ($i = 0; $i < $extra_rows; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr style="background: #e8e8e8; font-weight: bold; border-top: 2px solid #000;">
                    <td colspan="2" style="text-align: right;">کل وصولیاں + افتتاحی کیش:</td>
                    <td style="text-align: left;">
                        <strong class="num" style="font-size: 13pt;">Rs. <?= format_rs($opening_cash + $tot_inflow) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Outflows (خرچ / جمع) -->
    <div class="cash-col">
        <div class="col-header" style="background: #fdf2e9;">
            خرچ / ادائیگیاں (Cash Payments / Outflows)
        </div>
        <table class="side-table">
            <thead>
                <tr>
                    <th style="width: 70px;">حوالہ #</th>
                    <th>تفصیل و اخراجات</th>
                    <th style="width: 100px; text-align: left;">رقم (روپے)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($outflows as $out): ?>
                    <tr>
                        <td style="text-align: center;"><span class="num" style="font-size: 10pt;"><?= htmlspecialchars($out['ref']) ?></span></td>
                        <td><?= htmlspecialchars($out['details']) ?></td>
                        <td style="text-align: left;"><span class="num"><?= format_rs($out['amount']) ?></span></td>
                    </tr>
                <?php endforeach; ?>

                <?php 
                $extra_rows_out = ($max_rows + 1) - count($outflows);
                for ($i = 0; $i < $extra_rows_out; $i++): 
                ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr style="background: #e8e8e8; font-weight: bold; border-top: 2px solid #000;">
                    <td colspan="2" style="text-align: right;">کل ادائیگیاں و اخراجات:</td>
                    <td style="text-align: left;">
                        <strong class="num" style="font-size: 13pt;">Rs. <?= format_rs($tot_outflow) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Daily Summary Strip -->
<div class="khatam-box">
    <div class="khatam-grid">
        <div class="k-item">
            <span class="k-label">سابقہ افتتاحی کیش:</span>
            <span class="k-val num">Rs. <?= format_rs($opening_cash) ?></span>
        </div>
        <div class="k-item">
            <span class="k-label">آج کی کل وصولیاں:</span>
            <span class="k-val num" style="color: green;">+ Rs. <?= format_rs($tot_inflow) ?></span>
        </div>
        <div class="k-item">
            <span class="k-label">آج کی کل ادائیگیاں:</span>
            <span class="k-val num" style="color: red;">- Rs. <?= format_rs($tot_outflow) ?></span>
        </div>
        <div class="k-item" style="background: #f0fdf4; border: 2px solid #16a34a;">
            <span class="k-label">موجودہ خالص کیش ان ہینڈ:</span>
            <span class="k-val num" style="color: #166534; font-size: 17pt;">Rs. <?= format_rs($closing_cash) ?></span>
        </div>
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        دستخط منشی / اکاؤنٹنٹ
    </div>
    <div class="sig-block">
        دستخط کیشیئر / خزانچی
    </div>
    <div class="sig-block">
        دستخط مالک آڑھت
    </div>
</div>

</body>
</html>
