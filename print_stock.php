<?php
/**
 * Dedicated Print Template: Stock Inventory Report (سٹاک رپورٹ - آمد، فروخت اور بقایا مال)
 * Formatted for A4 landscape or portrait with high-contrast borders
 */
require_once __DIR__ . '/functions.php';

$item_filter = (int)($_GET['item_id'] ?? 0);
$status_filter = clean($_GET['status'] ?? 'all');
$settings = get_settings();

$sql = "SELECT m.*, p.urdu_name as farmer_name, p.city as farmer_city, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    JOIN `items` i ON m.item_id = i.id 
    JOIN `packings` pk ON m.packing_id = pk.id 
    WHERE 1=1";
$params = [];

if ($item_filter > 0) {
    $sql .= " AND m.item_id = ?";
    $params[] = $item_filter;
}

if ($status_filter === 'available') {
    $sql .= " AND m.remaining_nag > 0";
} elseif ($status_filter === 'completed') {
    $sql .= " AND m.remaining_nag = 0";
}

$sql .= " ORDER BY m.remaining_nag DESC, m.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stock_rows = $stmt->fetchAll();

// Totals
$tot_amad = 0;
$tot_sold = 0;
$tot_rem = 0;
foreach ($stock_rows as $r) {
    $tot_amad += $r['total_nag'];
    $tot_sold += $r['sold_nag'];
    $tot_rem += $r['remaining_nag'];
}

$item_name_selected = 'تمام اجناس';
if ($item_filter > 0) {
    $it_stmt = $pdo->prepare("SELECT urdu_name FROM `items` WHERE id = ?");
    $it_stmt->execute([$item_filter]);
    $item_name_selected = $it_stmt->fetchColumn() ?: 'تمام اجناس';
}
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سٹاک رپورٹ - <?= htmlspecialchars($item_name_selected) ?> - <?= date('d-m-Y') ?></title>
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
        table.stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.stock-table th, table.stock-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
        }
        table.stock-table th {
            background: #e8e8e8;
            font-weight: bold;
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
        <strong>پرنٹ پریویو - سٹاک رپورٹ</strong> &nbsp;|&nbsp; 
        <span>جنس: <strong><?= htmlspecialchars($item_name_selected) ?></strong></span> &nbsp;|&nbsp;
        <span>بقایا سٹاک: <strong class="num"><?= number_format($tot_rem) ?></strong> نگ</span>
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
        <span class="report-badge">سٹاک انوینٹری رپورٹ (Stock Inventory Report)</span>
    </div>
</div>

<div class="meta-strip">
    <div>
        <strong>تاریخ پرنٹ:</strong> <span class="num"><?= date('d-m-Y') ?></span>
    </div>
    <div>
        <strong>منتخب جنس:</strong> <?= htmlspecialchars($item_name_selected) ?>
    </div>
    <div>
        <strong>فلٹر حالت:</strong> 
        <?= $status_filter === 'available' ? 'صرف دستیاب سٹاک' : ($status_filter === 'completed' ? 'صرف مکمل فروخت' : 'تمام لاٹس') ?>
    </div>
</div>

<table class="stock-table">
    <thead>
        <tr>
            <th style="width: 35px;">#</th>
            <th style="width: 80px;">تاریخ آمد</th>
            <th style="width: 75px;">چالان #</th>
            <th style="text-align: right; width: 150px;">زمیندار / سپلائر</th>
            <th style="width: 120px;">جنس و پیکنگ</th>
            <th style="width: 75px;">مارکہ</th>
            <th style="width: 85px;">گاڑی #</th>
            <th style="width: 75px;">کل آمد</th>
            <th style="width: 75px;">فروخت</th>
            <th style="width: 85px;">بقایا نگ</th>
            <th style="width: 80px;">کیفیت</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $sr = 0;
        foreach ($stock_rows as $r): 
            $sr++;
        ?>
            <tr>
                <td><span class="num"><?= $sr ?></span></td>
                <td><span class="num"><?= format_dt($r['amad_date']) ?></span></td>
                <td><span class="num fw-bold"><?= htmlspecialchars($r['challan_no']) ?></span></td>
                <td style="text-align: right;">
                    <strong><?= htmlspecialchars($r['farmer_name']) ?></strong>
                    <?php if (!empty($r['farmer_city'])): ?>
                        <span style="font-size: 10pt; color: #555;">(<?= htmlspecialchars($r['farmer_city']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['item_name']) ?> / <?= htmlspecialchars($r['packing_name']) ?></td>
                <td><strong><?= htmlspecialchars($r['marke']) ?></strong></td>
                <td><span class="num"><?= htmlspecialchars($r['vehicle_no'] ?: '-') ?></span></td>
                <td><span class="num"><?= number_format($r['total_nag']) ?></span></td>
                <td><span class="num"><?= number_format($r['sold_nag']) ?></span></td>
                <td style="background: <?= $r['remaining_nag'] > 0 ? '#eafaf1' : '#f8f8f8' ?>;">
                    <strong class="num" style="font-size: 13pt; color: <?= $r['remaining_nag'] > 0 ? '#1b5e20' : '#888' ?>;">
                        <?= number_format($r['remaining_nag']) ?>
                    </strong>
                </td>
                <td style="font-size: 11pt;">
                    <?php if ($r['remaining_nag'] > 0): ?>
                        <span style="color: green; font-weight: bold;">دستیاب</span>
                    <?php else: ?>
                        <span style="color: #777;">مکمل فروخت</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if ($sr === 0): ?>
            <tr>
                <td colspan="11" style="padding: 20px;">اس فلٹر کے مطابق کوئی سٹاک لاٹ نہیں ملی۔</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="background: #e0e0e0; font-weight: bold; font-size: 12pt;">
            <td colspan="7" style="text-align: left; padding-left: 15px;">میزان ٹوٹل نگ:</td>
            <td><strong class="num"><?= number_format($tot_amad) ?></strong></td>
            <td><strong class="num"><?= number_format($tot_sold) ?></strong></td>
            <td><strong class="num" style="font-size: 14pt; color: green;"><?= number_format($tot_rem) ?></strong></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<!-- Summary Box -->
<div class="summary-box">
    <div class="summary-grid">
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">کل لاٹس کی تعداد:</span>
            <strong class="num" style="font-size: 14pt;"><?= $sr ?></strong>
        </div>
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">کل آمد نگ:</span>
            <strong class="num" style="font-size: 14pt;"><?= number_format($tot_amad) ?></strong>
        </div>
        <div class="sum-card">
            <span style="font-size: 11pt; color: #555; display: block;">کل فروخت شدہ نگ:</span>
            <strong class="num" style="font-size: 14pt; color: #c0392b;"><?= number_format($tot_sold) ?></strong>
        </div>
        <div class="sum-card" style="border: 2px solid #16a34a; background: #f0fdf4;">
            <span style="font-size: 11pt; color: #166534; font-weight: bold; display: block;">موجودہ بقایا نگ (گودام):</span>
            <strong class="num" style="font-size: 16pt; color: #166534;"><?= number_format($tot_rem) ?></strong>
        </div>
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        دستخط منشی مال آمد
    </div>
    <div class="sig-block">
        دستخط گودام / پھڑ انچارج
    </div>
    <div class="sig-block">
        دستخط مالک آڑھت
    </div>
</div>

</body>
</html>
