<?php
/**
 * Dedicated Print Template: One Day Analysis / Daily Summary Report (ایک روزہ موازنہ رپورٹ)
 * Formatted for A4 portrait with comparison analytics and signatures
 */
require_once __DIR__ . '/functions.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));
$settings = get_settings();

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
$tot_nag_sold = 0;
foreach ($sales_raw as $s) {
    $cid = $s['customer_id'];
    if (!isset($cust_sales[$cid])) {
        $cust_sales[$cid] = 0;
        $cust_sales_details[$cid] = [];
    }
    $cust_sales[$cid] += $s['net_amount'];
    $cust_sales_details[$cid][] = $s['nag'] . ' نگ ' . $s['item_name'];
    $tot_nag_sold += (int)$s['nag'];
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

// Combine and filter active rows
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
            'customer' => $c['urdu_name'],
            'city' => $c['city'],
            'mobile' => $c['mobile'],
            'details' => $details,
            'sales' => $s_amt,
            'wasooli' => $w_amt,
            'diff' => $diff
        ];
    }
}

// Commission on $date
$comm_stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM `sales_bills` WHERE sale_date = ?");
$comm_stmt->execute([$date]);
$tot_comm = (float)$comm_stmt->fetchColumn();

// Recovery percentage
$recovery_percent = $tot_sales > 0 ? round(($tot_wasooli / $tot_sales) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ایک روزہ موازنہ رپورٹ - <?= format_dt($date) ?></title>
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
        .meta-strip {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            padding: 4px 12px;
            margin-bottom: 10px;
            background: #fdfdfd;
            font-size: 12pt;
        }
        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.summary-table th, table.summary-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        table.summary-table th {
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
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            text-align: center;
        }
        .sum-card {
            background: #fff;
            border: 1px solid #000;
            padding: 6px 4px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            padding: 15px 40px;
            text-align: center;
        }
        .sig-block {
            width: 30%;
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
        <strong>پرنٹ پریویو - ایک روزہ موازنہ رپورٹ</strong> &nbsp;|&nbsp; 
        <span>بتاریخ: <span class="num"><?= format_dt($date) ?></span></span> &nbsp;|&nbsp;
        <span>کل فروخت: <strong>Rs. <?= format_rs($tot_sales) ?></strong></span> &nbsp;|&nbsp;
        <span>کل وصولی: <strong>Rs. <?= format_rs($tot_wasooli) ?></strong></span>
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
        <span class="report-badge">ایک روزہ موازنہ و تجزیاتی رپورٹ (Daily Sales vs Recovery Analysis)</span>
    </div>
</div>

<div class="meta-strip">
    <div>
        <strong>بتاریخ:</strong> <span class="num"><?= format_dt($date) ?> (<?= date('l', strtotime($date)) ?>)</span>
    </div>
    <div>
        <strong>کل فعال گاہکان:</strong> <span class="num fw-bold"><?= count($active_rows) ?></span>
    </div>
    <div>
        <strong>فروخت شدہ مال:</strong> <span class="num fw-bold"><?= number_format($tot_nag_sold) ?></span> نگ
    </div>
</div>

<table class="summary-table">
    <thead>
        <tr>
            <th style="width: 35px;">#</th>
            <th style="text-align: right; width: 170px;">نام گاہک (پارٹی)</th>
            <th style="width: 85px; text-align: center;">شہر</th>
            <th style="text-align: right;">آج کی خریداری / تفصیل جنس</th>
            <th style="width: 110px; text-align: left;">آج کی فروخت (بل)</th>
            <th style="width: 110px; text-align: left;">آج کی وصولی</th>
            <th style="width: 120px; text-align: left;">روزانہ خالص فرق</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $sr = 0;
        foreach ($active_rows as $row): 
            $sr++;
        ?>
            <tr>
                <td style="text-align: center;"><span class="num"><?= $sr ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($row['customer']) ?></strong>
                </td>
                <td style="text-align: center;"><?= htmlspecialchars($row['city'] ?: '-') ?></td>
                <td><small><?= htmlspecialchars($row['details']) ?></small></td>
                <td style="text-align: left;">
                    <span class="num"><?= $row['sales'] > 0 ? format_rs($row['sales']) : '-' ?></span>
                </td>
                <td style="text-align: left;">
                    <span class="num"><?= $row['wasooli'] > 0 ? format_rs($row['wasooli']) : '-' ?></span>
                </td>
                <td style="text-align: left;">
                    <?php if ($row['diff'] > 0): ?>
                        <strong class="num" style="color: #c0392b;">+<?= format_rs($row['diff']) ?></strong>
                        <small style="color: #c0392b;">(بنام)</small>
                    <?php elseif ($row['diff'] < 0): ?>
                        <strong class="num" style="color: #27ae60;"><?= format_rs(abs($row['diff'])) ?></strong>
                        <small style="color: #27ae60;">(جمع)</small>
                    <?php else: ?>
                        <span class="num">0</span> <small>(برابر)</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if ($sr === 0): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">اس تاریخ پر کوئی فروخت یا وصولی ریکارڈ نہیں ہوئی۔</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="background: #e0e0e0; font-weight: bold; font-size: 12.5pt;">
            <td colspan="4" style="text-align: left; padding-left: 15px;">میزان ٹوٹل:</td>
            <td style="text-align: left;"><strong class="num">Rs. <?= format_rs($tot_sales) ?></strong></td>
            <td style="text-align: left;"><strong class="num">Rs. <?= format_rs($tot_wasooli) ?></strong></td>
            <td style="text-align: left;">
                <strong class="num" style="font-size: 13pt; color: <?= ($tot_sales - $tot_wasooli) >= 0 ? '#c0392b' : '#27ae60' ?>;">
                    Rs. <?= format_rs(abs($tot_sales - $tot_wasooli)) ?>
                </strong>
            </td>
        </tr>
    </tfoot>
</table>

<!-- Summary Box -->
<div class="summary-box">
    <div class="summary-grid">
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">آج کی کل فروخت (بل):</span>
            <strong class="num" style="font-size: 14pt; color: #2980b9;">Rs. <?= format_rs($tot_sales) ?></strong>
        </div>
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">آج کی کل وصولی (کیش):</span>
            <strong class="num" style="font-size: 14pt; color: #27ae60;">Rs. <?= format_rs($tot_wasooli) ?></strong>
        </div>
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">آج کا کمیشن منافع:</span>
            <strong class="num" style="font-size: 14pt; color: #d35400;">Rs. <?= format_rs($tot_comm) ?></strong>
        </div>
        <div class="sum-card" style="border: 2px solid #2563eb; background: #eff6ff;">
            <span style="font-size: 11pt; color: #1e40af; font-weight: bold; display: block;">شرح ریکوری (Recovery %):</span>
            <strong class="num" style="font-size: 16pt; color: #1e40af;"><?= $recovery_percent ?>%</strong>
        </div>
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        دستخط منشی بولی / فروخت
    </div>
    <div class="sig-block">
        دستخط کیشیئر / ریکوری انچارج
    </div>
    <div class="sig-block">
        دستخط مالک آڑھت
    </div>
</div>

</body>
</html>
