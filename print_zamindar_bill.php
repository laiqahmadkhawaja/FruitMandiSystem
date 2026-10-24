<?php
/**
 * Dedicated Print Template: Zamindar Bill / Fard Hisab (زمیندار فردِ حساب)
 * Formatted for A4 / A5 Paper with zero clipping
 */
require_once __DIR__ . '/functions.php';

$bill_id = (int)($_GET['id'] ?? 0);
if ($bill_id <= 0) {
    die("بل آئی ڈی فراہم نہیں کی گئی۔");
}

$stmt = $pdo->prepare("SELECT zb.*, p.urdu_name as farmer_name, p.city as farmer_city, p.mobile as farmer_mobile, 
    m.challan_no, m.marke, m.amad_date, m.vehicle_no, m.bilty_no,
    i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `zamindar_bills` zb 
    JOIN `parties` p ON zb.farmer_id = p.id 
    JOIN `maal_amad` m ON zb.amad_id = m.id 
    JOIN `items` i ON m.item_id = i.id 
    JOIN `packings` pk ON m.packing_id = pk.id 
    WHERE zb.id = ?");
$stmt->execute([$bill_id]);
$b = $stmt->fetch();
if (!$b) die("بل ریکارڈ نہیں ملا۔");

$settings = get_settings();

// Fetch sales breakdown for this amad lot
$s_stmt = $pdo->prepare("SELECT s.*, p.urdu_name as customer_name 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    WHERE s.amad_id = ? 
    ORDER BY s.id ASC");
$s_stmt->execute([$b['amad_id']]);
$sales = $s_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>زمیندار بل # <?= htmlspecialchars($b['bill_no']) ?> - <?= htmlspecialchars($b['farmer_name']) ?></title>
    <style>
        @font-face {
            font-family: 'Jameel Noori Nastaleeq';
            src: local('Jameel Noori Nastaleeq'),
                 url('assets/fonts/JameelNooriNastaleeq.ttf') format('truetype');
        }
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Jameel Noori Nastaleeq', Tahoma, serif;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-size: 14pt;
            line-height: 1.8;
            direction: rtl;
        }
        .num {
            font-family: 'Segoe UI', Tahoma, sans-serif !important;
            direction: ltr;
            display: inline-block;
        }
        .print-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 15px 20px;
        }
        .header-box {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .shop-name {
            font-size: 26pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.4;
        }
        .shop-sub {
            font-size: 13pt;
            margin: 2px 0;
        }
        .bill-badge {
            display: inline-block;
            border: 2px solid #000;
            padding: 2px 20px;
            font-size: 16pt;
            font-weight: bold;
            background: #f0f0f0;
            margin-top: 5px;
        }
        .meta-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 13pt;
        }
        .meta-row {
            display: table-row;
        }
        .meta-cell {
            display: table-cell;
            padding: 3px 6px;
            width: 50%;
        }
        .tbl-report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .tbl-report th, .tbl-report td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: center;
        }
        .tbl-report th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .bold { font-weight: bold; }
        .total-row {
            font-size: 16pt;
            background-color: #f9f9f9;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 35px;
            padding-top: 10px;
        }
        .sig-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 13pt;
        }
        .sig-line {
            display: inline-block;
            width: 180px;
            border-bottom: 1px dashed #000;
            margin-bottom: 5px;
        }
        .action-bar {
            background: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-act {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14pt;
            cursor: pointer;
            border-radius: 4px;
            font-family: inherit;
        }
        .btn-close-win {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14pt;
            cursor: pointer;
            border-radius: 4px;
            font-family: inherit;
            margin-left: 10px;
        }
        @media print {
            .action-bar { display: none !important; }
            .print-container { border: 2px solid #000 !important; }
            body { font-size: 13pt; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <button class="btn-act" onclick="window.print()">🖨️ ابھی پرنٹ کریں (Print)</button>
    <button class="btn-close-win" onclick="window.close()">بند کریں (Close)</button>
</div>

<div class="print-container">
    <!-- Header -->
    <div class="header-box">
        <h1 class="shop-name"><?= htmlspecialchars($settings['shop_name']) ?></h1>
        <div class="shop-sub"><?= htmlspecialchars($settings['shop_subheading']) ?> - <?= htmlspecialchars($settings['mandi_name']) ?>، <?= htmlspecialchars($settings['city']) ?></div>
        <div class="shop-sub">فون / واٹس ایپ: <span class="num"><?= htmlspecialchars($settings['phone']) ?></span></div>
        <div class="bill-badge">زمیندار فردِ حساب بل</div>
    </div>

    <!-- Meta Details -->
    <div class="meta-grid">
        <div class="meta-row">
            <div class="meta-cell"><strong>بل نمبر:</strong> <span class="num bold"><?= $b['bill_no'] ?></span></div>
            <div class="meta-cell text-left"><strong>تاریخ بل:</strong> <span class="num"><?= format_dt($b['bill_date']) ?></span></div>
        </div>
        <div class="meta-row">
            <div class="meta-cell"><strong>زمیندار کا نام:</strong> <span class="bold"><?= htmlspecialchars($b['farmer_name']) ?></span> (<?= htmlspecialchars($b['farmer_city'] ?: '-') ?>)</div>
            <div class="meta-cell text-left"><strong>چالان آمد #:</strong> <span class="num bold"><?= $b['challan_no'] ?></span> (تاریخ: <span class="num"><?= format_dt($b['amad_date']) ?></span>)</div>
        </div>
        <div class="meta-row">
            <div class="meta-cell"><strong>مارکہ:</strong> <span class="num bold">[ <?= htmlspecialchars($b['marke']) ?> ]</span> &nbsp;|&nbsp; <strong>جنس:</strong> <?= $b['item_name'] ?> (<?= $b['packing_name'] ?>)</div>
            <div class="meta-cell text-left"><strong>کل آمد نَگ:</strong> <span class="num bold"><?= $b['total_nag'] ?></span> &nbsp;|&nbsp; گاڑی: <span class="num"><?= htmlspecialchars($b['vehicle_no'] ?: '-') ?></span></div>
        </div>
    </div>

    <!-- Sales Details -->
    <?php if (!empty($sales)): ?>
    <div style="font-weight:bold; margin-bottom:4px;">فروخت کی تفصیل (Sales Breakdown):</div>
    <table class="tbl-report" style="margin-bottom:10px;">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th class="text-right">خریدار (گاہک)</th>
                <th style="width:70px;">تعداد نَگ</th>
                <th style="width:90px;">ریٹ فی نَگ</th>
                <th style="width:110px;">رقم فروخت</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($sales as $sl): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-right"><?= htmlspecialchars($sl['customer_name']) ?></td>
                <td><span class="num"><?= $sl['nag'] ?></span></td>
                <td><span class="num">Rs. <?= format_rs($sl['rate']) ?></span></td>
                <td><span class="num bold">Rs. <?= format_rs($sl['gross_amount']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Financial Breakdown Table -->
    <table class="tbl-report">
        <thead>
            <tr>
                <th class="text-right">تفصیل حساب و کٹوتیاں</th>
                <th style="width:120px;">مقدار / شرح</th>
                <th style="width:150px;">رقم (روپے)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bold">
                <td class="text-right">مجموعی فروخت مالیت (Gross Total Sales)</td>
                <td><span class="num"><?= $b['total_nag'] ?> نَگ</span></td>
                <td><span class="num bold">Rs. <?= format_rs($b['gross_sale_amount']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: کمیشن آڑھت (Commission)</td>
                <td><span class="num"><?= $b['commission_rate'] ?>%</span></td>
                <td><span class="num">- Rs. <?= format_rs($b['commission_amount']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: گاڑی کرایہ کٹوتی (Freight Deduction)</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['freight_deduction']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: لوکل کرایہ / اڈا خرچہ</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['local_cartage_deduction']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: مزدوری کٹوتی (Labour)</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['mazdoori_amount']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: مارکیٹ کمیٹی فیس</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['market_fee_amount']) ?></span></td>
            </tr>
            <tr>
                <td class="text-right">منفی: منشیانہ</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['munshiana_amount']) ?></span></td>
            </tr>
            <?php if ($b['advance_adjusted'] > 0): ?>
            <tr>
                <td class="text-right">منفی: سابقہ پیشگی رقم ایڈجسٹمنٹ</td>
                <td>-</td>
                <td><span class="num">- Rs. <?= format_rs($b['advance_adjusted']) ?></span></td>
            </tr>
            <?php endif; ?>
            <tr class="total-row bold">
                <td colspan="2" class="text-right" style="font-size:16pt;">صافی قابلِ ادائیگی رقم (Net Payable to Farmer):</td>
                <td style="font-size:18pt;"><span class="num bold">Rs. <?= format_rs($b['net_payable']) ?></span></td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="signatures">
        <div class="sig-cell">
            <div class="sig-line"></div><br>
            دستخط منشی / کمپیوٹر آپریٹر
        </div>
        <div class="sig-cell">
            <div class="sig-line"></div><br>
            دستخط آڑھتی / مالک
        </div>
    </div>
</div>

</body>
</html>
