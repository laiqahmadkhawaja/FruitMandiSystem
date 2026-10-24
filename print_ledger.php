<?php
/**
 * Dedicated Print Template: Party Ledger Statement (تفصیلی کھاتہ سٹیٹمنٹ)
 * Formatted for A4 portrait with clear debit/credit columns and running balance
 */
require_once __DIR__ . '/functions.php';

$party_id = (int)($_GET['party_id'] ?? 0);
if ($party_id <= 0) {
    die("براہ کرم پارٹی منتخب کریں۔");
}

$from_date = clean($_GET['from_date'] ?? date('Y-01-01'));
$to_date = clean($_GET['to_date'] ?? date('Y-m-d'));
$settings = get_settings();

$party = get_party($party_id);
if (!$party) {
    die("پارٹی ریکارڈ نہیں ملا۔");
}

// Calculate Opening Balance before $from_date
$pre_date = date('Y-m-d', strtotime($from_date . ' -1 day'));
$pre_calc = get_party_balance($party_id, $pre_date);
$opening_dr = (float)$pre_calc['total_dr'];
$opening_cr = (float)$pre_calc['total_cr'];
$opening_diff = $opening_dr - $opening_cr;
$running_balance = $opening_diff;

$ledger_lines = [];

// 1. Sales Bills (If customer)
if ($party['type'] === 'customer') {
    $s_stmt = $pdo->prepare("SELECT sale_date as date, bill_no as ref, CONCAT('فروخت بل: ', nag, ' نگ ', i.urdu_name, ' (مارکہ ', marke, ')') as details, net_amount as dr, 0 as cr 
        FROM `sales_bills` s 
        JOIN `items` i ON s.item_id = i.id 
        WHERE s.customer_id = ? AND s.sale_date BETWEEN ? AND ?");
    $s_stmt->execute([$party_id, $from_date, $to_date]);
    $ledger_lines = array_merge($ledger_lines, $s_stmt->fetchAll());

    // Bazar wasooli
    $bw_stmt = $pdo->prepare("SELECT date, 'بازار وصولی' as ref, 'بازار کھاتہ روزانہ وصولی (صبح/شام)' as details, 0 as dr, (morning_amount + evening_amount) as cr 
        FROM `bazar_wasooli` 
        WHERE customer_id = ? AND (morning_amount > 0 OR evening_amount > 0) AND date BETWEEN ? AND ?");
    $bw_stmt->execute([$party_id, $from_date, $to_date]);
    $ledger_lines = array_merge($ledger_lines, $bw_stmt->fetchAll());
}

// 2. Farmer Bills & Freight (If farmer)
if ($party['type'] === 'farmer') {
    $zb_stmt = $pdo->prepare("SELECT bill_date as date, bill_no as ref, CONCAT('فرد حساب بل (چالان ', m.challan_no, ' مارکہ ', m.marke, ')') as details, 0 as dr, net_payable as cr 
        FROM `zamindar_bills` zb 
        JOIN `maal_amad` m ON zb.amad_id = m.id 
        WHERE zb.farmer_id = ? AND zb.bill_date BETWEEN ? AND ?");
    $zb_stmt->execute([$party_id, $from_date, $to_date]);
    $ledger_lines = array_merge($ledger_lines, $zb_stmt->fetchAll());

    // Freight paid on arrival (Only for unbilled lots, since billed lots have freight deducted on the bill)
    $f_stmt = $pdo->prepare("SELECT amad_date as date, challan_no as ref, CONCAT('گاڑی کرایہ ادائیگی لاٹ ', marke) as details, (freight_paid_by_agent + local_cartage) as dr, 0 as cr 
        FROM `maal_amad` 
        WHERE farmer_id = ? AND bill_posted = 0 AND (freight_paid_by_agent > 0 OR local_cartage > 0) AND amad_date BETWEEN ? AND ?");
    $f_stmt->execute([$party_id, $from_date, $to_date]);
    $ledger_lines = array_merge($ledger_lines, $f_stmt->fetchAll());
}

// 3. Vouchers
$v_stmt = $pdo->prepare("SELECT voucher_date as date, voucher_no as ref, narration as details, 
    CASE WHEN type = 'payment' THEN amount ELSE 0 END as dr,
    CASE WHEN type = 'receipt' THEN amount ELSE 0 END as cr 
    FROM `vouchers` 
    WHERE party_id = ? AND voucher_date BETWEEN ? AND ?");
$v_stmt->execute([$party_id, $from_date, $to_date]);
$ledger_lines = array_merge($ledger_lines, $v_stmt->fetchAll());

// Sort all lines by date ascending
usort($ledger_lines, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

$period_dr = 0;
$period_cr = 0;
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>کھاتہ سٹیٹمنٹ - <?= htmlspecialchars($party['urdu_name']) ?> (<?= format_dt($from_date) ?> تا <?= format_dt($to_date) ?>)</title>
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
        .party-card {
            border: 1.5px solid #000;
            background: #fcfcfc;
            padding: 8px 14px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            font-size: 12pt;
        }
        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.ledger-table th, table.ledger-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        table.ledger-table th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 12pt;
        }
        .summary-box {
            border: 2px solid #000;
            background: #f7f7f7;
            padding: 10px 15px;
            margin-bottom: 25px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            text-align: center;
        }
        .sum-card {
            background: #fff;
            border: 1px solid #000;
            padding: 8px 5px;
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
            font-size: 12pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div id="action-bar">
    <div>
        <strong>پرنٹ پریویو - تفصیلی کھاتہ</strong> &nbsp;|&nbsp; 
        <span>کھاتہ دار: <strong><?= htmlspecialchars($party['urdu_name']) ?></strong></span> &nbsp;|&nbsp;
        <span>مدت: <span class="num"><?= format_dt($from_date) ?></span> تا <span class="num"><?= format_dt($to_date) ?></span></span>
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
        <span class="report-badge">تفصیلی کھاتہ سٹیٹمنٹ (Party Account Ledger)</span>
    </div>
</div>

<!-- Party Information -->
<div class="party-card">
    <div>
        <strong>نام کھاتہ دار:</strong>
        <span style="font-size: 14pt; font-weight: bold;"><?= htmlspecialchars($party['urdu_name']) ?></span>
        <?php if (!empty($party['code'])): ?>
            <span style="font-size: 11pt; color: #555;">(<?= htmlspecialchars($party['code']) ?>)</span>
        <?php endif; ?>
    </div>
    <div>
        <strong>نوعیت کھاتہ:</strong>
        <span>
            <?php 
            if ($party['type'] === 'farmer') echo 'زمیندار / مال سپلائر';
            elseif ($party['type'] === 'customer') echo 'خریدار / دکاندار';
            else echo 'عام کھاتہ';
            ?>
        </span>
    </div>
    <div>
        <strong>شہر / پتہ:</strong>
        <span><?= htmlspecialchars($party['city'] ?: '-') ?></span>
    </div>
    <div>
        <strong>موبائل فون #:</strong>
        <span class="num"><?= htmlspecialchars($party['mobile'] ?: '-') ?></span>
    </div>
    <div style="grid-column: span 2;">
        <strong>مدت رپورٹ:</strong>
        <span>از تاریخ <span class="num"><?= format_dt($from_date) ?></span> تا تاریخ <span class="num"><?= format_dt($to_date) ?></span></span>
    </div>
</div>

<!-- Ledger Transactions Table -->
<table class="ledger-table">
    <thead>
        <tr>
            <th style="width: 35px;">#</th>
            <th style="width: 85px;">تاریخ</th>
            <th style="width: 90px;">بل / واؤچر #</th>
            <th style="text-align: right;">تفصیل و نوعیتِ لین دین</th>
            <th style="width: 105px; text-align: left;">بنام / ڈیبٹ (روپے)</th>
            <th style="width: 105px; text-align: left;">جمع / کریڈٹ (روپے)</th>
            <th style="width: 120px; text-align: left;">بقایا میزان (روپے)</th>
            <th style="width: 75px;">کیفیت</th>
        </tr>
    </thead>
    <tbody>
        <!-- Opening Balance Row -->
        <tr style="background: #fef9e7; font-weight: bold;">
            <td style="text-align: center;"><span class="num">-</span></td>
            <td style="text-align: center;"><span class="num"><?= format_dt($from_date) ?></span></td>
            <td style="text-align: center;">سابقہ</td>
            <td style="text-align: right;">سابقہ افتتاحی بقایا قبل از <span class="num"><?= format_dt($from_date) ?></span></td>
            <td style="text-align: left;"><span class="num"><?= $opening_diff > 0 ? format_rs($opening_diff) : '-' ?></span></td>
            <td style="text-align: left;"><span class="num"><?= $opening_diff < 0 ? format_rs(abs($opening_diff)) : '-' ?></span></td>
            <td style="text-align: left;">
                <strong class="num"><?= format_rs(abs($opening_diff)) ?></strong>
            </td>
            <td style="text-align: center; font-size: 11pt;">
                <?= $opening_diff > 0 ? 'بنام (لینے)' : ($opening_diff < 0 ? 'جمع (دینے)' : 'برابر') ?>
            </td>
        </tr>

        <!-- Transaction Rows -->
        <?php 
        $sr = 0;
        foreach ($ledger_lines as $row):
            $sr++;
            $dr = (float)$row['dr'];
            $cr = (float)$row['cr'];
            $period_dr += $dr;
            $period_cr += $cr;
            $running_balance += ($dr - $cr);

            $status_urdu = 'برابر';
            if ($running_balance > 0) $status_urdu = 'بنام (لینے)';
            elseif ($running_balance < 0) $status_urdu = 'جمع (دینے)';
        ?>
            <tr>
                <td style="text-align: center;"><span class="num"><?= $sr ?></span></td>
                <td style="text-align: center;"><span class="num"><?= format_dt($row['date']) ?></span></td>
                <td style="text-align: center;"><span class="num"><?= htmlspecialchars($row['ref']) ?></span></td>
                <td style="text-align: right;"><?= htmlspecialchars($row['details']) ?></td>
                <td style="text-align: left;"><span class="num"><?= $dr > 0 ? format_rs($dr) : '-' ?></span></td>
                <td style="text-align: left;"><span class="num"><?= $cr > 0 ? format_rs($cr) : '-' ?></span></td>
                <td style="text-align: left;">
                    <strong class="num"><?= format_rs(abs($running_balance)) ?></strong>
                </td>
                <td style="text-align: center; font-size: 11pt;"><?= $status_urdu ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if ($sr === 0): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 15px;">اس تاریخ کے دوران کوئی نیا لین دین درج نہیں ہے۔</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="background: #eaeaea; font-weight: bold; font-size: 12pt;">
            <td colspan="4" style="text-align: right;">مجموعہ اس مدت کے دوران:</td>
            <td style="text-align: left;"><strong class="num">Rs. <?= format_rs($period_dr) ?></strong></td>
            <td style="text-align: left;"><strong class="num">Rs. <?= format_rs($period_cr) ?></strong></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<!-- Final Reconciliation Box -->
<div class="summary-box">
    <div class="summary-grid">
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">کل ڈیبٹ (بنام):</span>
            <strong class="num" style="font-size: 14pt; color: #c0392b;">Rs. <?= format_rs($opening_diff > 0 ? ($opening_diff + $period_dr) : $period_dr) ?></strong>
        </div>
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">کل کریڈٹ (جمع):</span>
            <strong class="num" style="font-size: 14pt; color: #27ae60;">Rs. <?= format_rs($opening_diff < 0 ? (abs($opening_diff) + $period_cr) : $period_cr) ?></strong>
        </div>
        <div class="sum-card" style="border: 2px solid #000; background: #fffde7;">
            <span style="font-size: 11pt; color: #000; font-weight: bold; display: block;">موجودہ خالص بقایا (Net Balance):</span>
            <strong class="num" style="font-size: 16pt;">Rs. <?= format_rs(abs($running_balance)) ?></strong>
            <div style="font-size: 12pt; font-weight: bold; margin-top: 2px; color: <?= $running_balance > 0 ? '#b91c1c' : ($running_balance < 0 ? '#15803d' : '#333') ?>;">
                <?php 
                if ($running_balance > 0) {
                    echo "بقایا واجب الوصول (آپ کے ذمہ واجب الادا ہیں)";
                } elseif ($running_balance < 0) {
                    echo "بقایا واجب الادا (آپ کو ادا کرنے ہیں)";
                } else {
                    echo "حساب بالکل برابر اور کلیئر ہے";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        دستخط منشی / اکاؤنٹنٹ
    </div>
    <div class="sig-block">
        دستخط کھاتہ دار / پارٹی
    </div>
</div>

</body>
</html>
