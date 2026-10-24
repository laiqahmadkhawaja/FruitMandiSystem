<?php
/**
 * Dedicated Print Template: Bazar Khata (بازار کھاتہ - صبح و شام وصولی رجسٹر)
 * Formatted for A4 with clean high-contrast borders
 */
require_once __DIR__ . '/functions.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));
$city_filter = clean($_GET['city'] ?? '');
$filter_mode = clean($_GET['filter_mode'] ?? 'all');
$settings = get_settings();

// Query
$sql = "SELECT p.* FROM `parties` p WHERE p.type = 'customer'";
$params = [];
if (!empty($city_filter)) {
    $sql .= " AND p.city = ?";
    $params[] = $city_filter;
}
if ($filter_mode === 'non_blacklisted') {
    $sql .= " AND p.is_blacklisted = 0";
} elseif ($filter_mode === 'only_blacklisted') {
    $sql .= " AND p.is_blacklisted = 1";
}
$sql .= " ORDER BY p.urdu_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Sales on $date
$sales_stmt = $pdo->prepare("SELECT customer_id, COALESCE(SUM(net_amount), 0) FROM `sales_bills` WHERE sale_date = ? GROUP BY customer_id");
$sales_stmt->execute([$date]);
$sales_map = $sales_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Wasooli on $date
$wasooli_stmt = $pdo->prepare("SELECT customer_id, morning_amount, evening_amount FROM `bazar_wasooli` WHERE date = ?");
$wasooli_stmt->execute([$date]);
$wasooli_rows = $wasooli_stmt->fetchAll();
$wasooli_map = [];
foreach ($wasooli_rows as $w) {
    $wasooli_map[$w['customer_id']] = $w;
}
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بازار کھاتہ وصولی - تاریخ: <?= format_dt($date) ?></title>
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
        .report-title {
            display: inline-block;
            font-size: 16pt;
            font-weight: bold;
            border: 1px solid #000;
            padding: 2px 25px;
            background: #f0f0f0;
            margin-top: 4px;
        }
        .tbl-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5pt;
        }
        .tbl-data th, .tbl-data td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
        }
        .tbl-data th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .bold { font-weight: bold; }
        .total-row {
            font-weight: bold;
            font-size: 13.5pt;
            background: #f5f5f5;
        }
        .action-bar {
            background: #222;
            padding: 10px;
            text-align: center;
            margin-bottom: 12px;
        }
        .btn-act {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-size: 14pt;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-close-win {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14pt;
            cursor: pointer;
            border-radius: 4px;
            margin-left: 10px;
        }
        @media print {
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <button class="btn-act" onclick="window.print()">🖨️ پرنٹ نکالیں (Print Report)</button>
    <button class="btn-close-win" onclick="window.close()">بند کریں (Close)</button>
</div>

<div class="header-box">
    <h1 class="shop-name"><?= htmlspecialchars($settings['shop_name']) ?></h1>
    <div><?= htmlspecialchars($settings['shop_subheading']) ?> - <?= htmlspecialchars($settings['mandi_name']) ?>، <?= htmlspecialchars($settings['city']) ?></div>
    <div class="report-title">بازار کھاتہ - صبح و شام وصولی رجسٹر</div>
    <div style="margin-top:4px;">
        <strong>تاریخ:</strong> <span class="num"><?= format_dt($date) ?></span> &nbsp;|&nbsp; 
        <strong>شہر فلٹر:</strong> <?= htmlspecialchars($city_filter ?: 'تمام شہر') ?>
    </div>
</div>

<table class="tbl-data">
    <thead>
        <tr>
            <th style="width:35px;">#</th>
            <th class="text-right">گاہک کا نام (خریدار)</th>
            <th style="width:90px;">شہر</th>
            <th style="width:110px;">سابقہ بیلنس</th>
            <th style="width:110px;">آج کی فروخت بل</th>
            <th style="width:105px;">وصولی صبح</th>
            <th style="width:105px;">وصولی شام</th>
            <th style="width:115px;">کل واجب الادا بقایا</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $t_prev = 0; $t_sales = 0; $t_m = 0; $t_e = 0; $t_close = 0;
        $i = 1;
        $prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
        foreach ($customers as $c):
            $bal = get_party_balance($c['id'], $prev_date);
            $prev = $bal['balance'];
            $sale = (float)($sales_map[$c['id']] ?? 0);
            $m = (float)($wasooli_map[$c['id']]['morning_amount'] ?? 0);
            $e = (float)($wasooli_map[$c['id']]['evening_amount'] ?? 0);
            $close = $prev + $sale - ($m + $e);

            $t_prev += $prev; $t_sales += $sale; $t_m += $m; $t_e += $e; $t_close += $close;
        ?>
        <tr>
            <td><span class="num"><?= $i++ ?></span></td>
            <td class="text-right bold"><?= htmlspecialchars($c['urdu_name']) ?></td>
            <td><?= htmlspecialchars($c['city'] ?: '-') ?></td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($prev) ?></span></td>
            <td class="text-left"><span class="num"><?= $sale > 0 ? 'Rs. ' . format_rs($sale) : '-' ?></span></td>
            <td class="text-left"><span class="num"><?= $m > 0 ? 'Rs. ' . format_rs($m) : '-' ?></span></td>
            <td class="text-left"><span class="num"><?= $e > 0 ? 'Rs. ' . format_rs($e) : '-' ?></span></td>
            <td class="text-left bold"><span class="num">Rs. <?= format_rs($close) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-right">کل میزان (Grand Total):</td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($t_prev) ?></span></td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($t_sales) ?></span></td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($t_m) ?></span></td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($t_e) ?></span></td>
            <td class="text-left"><span class="num">Rs. <?= format_rs($t_close) ?></span></td>
        </tr>
    </tfoot>
</table>

<div style="display:table; width:100%; margin-top:30px;">
    <div style="display:table-cell; width:50%; text-align:center;">
        دستخط ریکوری آفیسر: __________________
    </div>
    <div style="display:table-cell; width:50%; text-align:center;">
        دستخط منشی / مالک: __________________
    </div>
</div>

</body>
</html>
