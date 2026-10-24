<?php
/**
 * Dedicated Print Template: Chittha Baqaya Jaat (چٹھہ بقایا جات - ریکوری لسٹ برائے ریکوری کار)
 * Formatted for A4 portrait with clear high-contrast lines and handwritten recovery column
 */
require_once __DIR__ . '/functions.php';

$show_nil = isset($_GET['show_nil']) && $_GET['show_nil'] === 'yes';
$city_filter = clean($_GET['city'] ?? '');
$officer_filter = clean($_GET['officer'] ?? '');
$settings = get_settings();

// Query
$sql = "SELECT * FROM `parties` WHERE `type` = 'customer'";
$params = [];
if (!empty($city_filter)) {
    $sql .= " AND `city` = ?";
    $params[] = $city_filter;
}
if (!empty($officer_filter)) {
    $sql .= " AND `recovery_officer` = ?";
    $params[] = $officer_filter;
}
$sql .= " ORDER BY `urdu_name` ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چٹھہ بقایا جات - <?= $city_filter ? htmlspecialchars($city_filter) . ' - ' : '' ?><?= date('d-m-Y') ?></title>
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
            tfoot { display: table-footer-group; }
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
            background: #fcfcfc;
            font-size: 12pt;
        }
        table.chittha-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.chittha-table th, table.chittha-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        table.chittha-table th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 12pt;
        }
        .write-box {
            height: 32px;
            border: 1px dashed #666;
            background: #fafafa;
        }
        .summary-strip {
            display: flex;
            justify-content: space-between;
            border: 2px solid #000;
            padding: 8px 15px;
            background: #f5f5f5;
            font-size: 13pt;
            margin-bottom: 25px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            padding: 10px 30px;
            text-align: center;
        }
        .sig-block {
            width: 28%;
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
        <strong>پرنٹ پریویو - چٹھہ بقایا جات</strong> &nbsp;|&nbsp; 
        <span>تاریخ: <span class="num"><?= date('d-m-Y') ?></span></span> &nbsp;|&nbsp;
        <span>شہر: <strong><?= $city_filter ? htmlspecialchars($city_filter) : 'تمام شہر' ?></strong></span>
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
        <span class="report-badge">چٹھہ بقایا جات (Daily Recovery & Arrears Sheet)</span>
    </div>
</div>

<div class="meta-strip">
    <div>
        <strong>تاریخ پرنٹ:</strong> <span class="num"><?= date('d-m-Y') ?> (<?= date('l') ?>)</span>
    </div>
    <div>
        <strong>روٹ / شہر:</strong> <?= $city_filter ? htmlspecialchars($city_filter) : 'تمام علاقہ جات' ?>
    </div>
    <div>
        <strong>ریکوری آفیسر / کارندہ:</strong> <?= $officer_filter ? htmlspecialchars($officer_filter) : 'تمام آفیسرز' ?>
    </div>
</div>

<table class="chittha-table">
    <thead>
        <tr>
            <th style="width: 35px;">#</th>
            <th style="text-align: right; width: 180px;">نام گاہک (خریدار)</th>
            <th style="width: 130px;">پتہ / معرفت</th>
            <th style="width: 85px;">شہر</th>
            <th style="width: 105px;">موبائل فون</th>
            <th style="width: 120px;">سابقہ واجب الادا بقایا</th>
            <th style="width: 110px;">آج کی وصولی (روپے)</th>
            <th style="width: 100px;">دستخط / ریمارکس</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $grand_baqaya = 0;
        $count = 0;

        foreach ($customers as $c):
            $bal = get_party_balance($c['id']);
            if (!$show_nil && $bal['balance'] <= 0) {
                continue;
            }
            $count++;
            $grand_baqaya += $bal['balance'];
        ?>
            <tr>
                <td style="text-align: center;"><span class="num"><?= $count ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($c['urdu_name']) ?></strong>
                    <?php if (!empty($c['code'])): ?>
                        <span style="font-size: 10pt; color: #555;">(<?= htmlspecialchars($c['code']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['address'] ?: '-') ?></td>
                <td style="text-align: center;"><?= htmlspecialchars($c['city'] ?: '-') ?></td>
                <td style="text-align: center;"><span class="num"><?= htmlspecialchars($c['mobile'] ?: '-') ?></span></td>
                <td style="text-align: left; padding-left: 8px;">
                    <strong class="num" style="font-size: 13pt;">Rs. <?= format_rs($bal['balance']) ?></strong>
                </td>
                <td style="padding: 2px;">
                    <div class="write-box"></div>
                </td>
                <td></td>
            </tr>
        <?php endforeach; ?>

        <?php if ($count === 0): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">کوئی ریکارڈ دستیاب نہیں ہے۔</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr style="background: #e0e0e0; font-weight: bold; font-size: 13pt;">
            <td colspan="5" style="text-align: left; padding-left: 15px;">
                کل میزان واجبات (<?= $count ?> گاہکان):
            </td>
            <td style="text-align: left; padding-left: 8px;">
                <strong class="num" style="font-size: 14pt;">Rs. <?= format_rs($grand_baqaya) ?></strong>
            </td>
            <td style="text-align: center;">میزان وصولی:</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="summary-strip">
    <div>
        <strong>کل گاہکان کی تعداد:</strong> <span class="num fw-bold"><?= $count ?></span>
    </div>
    <div>
        <strong>کل سابقہ واجب الادا بقایا جات:</strong> <span class="num fw-bold">Rs. <?= format_rs($grand_baqaya) ?></span>
    </div>
    <div>
        <strong>آج وصول شدہ کل کیش:</strong> ________________ روپے
    </div>
</div>

<div class="signatures">
    <div class="sig-block">
        دستخط ریکوری کارندہ
    </div>
    <div class="sig-block">
        دستخط کیشیئر / منشی
    </div>
    <div class="sig-block">
        دستخط پروپرائیٹر / مالک آڑھت
    </div>
</div>

</body>
</html>
