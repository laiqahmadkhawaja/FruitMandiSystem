<?php
/**
 * Dedicated Print Template: Sale Bill / Auction Slip (فروخت بل پرچہ / خریدار رسید)
 * Formatted for A5 or 2-up A4 Slip with zero clipping
 */
require_once __DIR__ . '/functions.php';

$bill_id = (int)($_GET['id'] ?? 0);
if ($bill_id <= 0) {
    die("بل آئی ڈی درست نہیں ہے۔");
}

$stmt = $pdo->prepare("SELECT s.*, 
    c.urdu_name as customer_name, c.city as customer_city, c.mobile as customer_mobile, c.code as customer_code,
    i.urdu_name as item_name, pk.urdu_name as packing_name,
    m.challan_no, m.marke as lot_marke, m.vehicle_no,
    f.urdu_name as farmer_name
    FROM `sales_bills` s
    JOIN `parties` c ON s.customer_id = c.id
    JOIN `items` i ON s.item_id = i.id
    JOIN `packings` pk ON s.packing_id = pk.id
    LEFT JOIN `maal_amad` m ON s.amad_id = m.id
    LEFT JOIN `parties` f ON m.farmer_id = f.id
    WHERE s.id = ?");
$stmt->execute([$bill_id]);
$b = $stmt->fetch();

if (!$b) {
    die("بل کا ریکارڈ نہیں ملا۔");
}

$settings = get_settings();

// Customer balance
$bal = get_party_balance($b['customer_id']);
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فروخت بل # <?= htmlspecialchars($b['bill_no']) ?> - <?= htmlspecialchars($b['customer_name']) ?></title>
    <style>
        @font-face {
            font-family: 'Jameel Noori Nastaleeq';
            src: local('Jameel Noori Nastaleeq'),
                 url('assets/fonts/JameelNooriNastaleeq.ttf') format('truetype');
        }
        @page {
            size: A4 portrait;
            margin: 8mm;
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
            margin-bottom: 12px;
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
            body { font-size: 12pt; line-height: 1.6; }
            .bill-wrapper { page-break-inside: avoid; }
        }
        .two-up-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .bill-card {
            border: 2px solid #000;
            padding: 12px 18px;
            border-radius: 4px;
            position: relative;
        }
        .divider-cut {
            border-top: 1px dashed #888;
            margin: 10px 0;
            position: relative;
            text-align: center;
        }
        .divider-cut span {
            background: #fff;
            padding: 0 10px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #666;
            position: relative;
            top: -10px;
        }
        .header-section {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .shop-title {
            font-size: 22pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.3;
        }
        .shop-meta {
            font-size: 12pt;
            margin: 2px 0;
        }
        .badge-copy {
            float: left;
            border: 1px solid #000;
            padding: 2px 12px;
            font-size: 11pt;
            background: #eee;
            border-radius: 3px;
        }
        .badge-type {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 1px 16px;
            font-size: 14pt;
            font-weight: bold;
            background: #f8f8f8;
            margin-top: 4px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            background: #fafafa;
            border: 1px solid #000;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        .meta-item {
            display: flex;
            justify-content: flex-start;
            gap: 6px;
        }
        .meta-label {
            font-weight: bold;
        }
        table.bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.bill-table th, table.bill-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: center;
        }
        table.bill-table th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 12pt;
        }
        .summary-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 8px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }
        .bal-table {
            width: 48%;
            border-collapse: collapse;
        }
        .bal-table td {
            border: 1px solid #000;
            padding: 3px 8px;
        }
        .signatures {
            width: 48%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 30px;
            text-align: center;
        }
        .sig-line {
            border-top: 1px dashed #000;
            width: 45%;
            font-size: 11pt;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<div id="action-bar">
    <div>
        <strong>پرنٹ پریویو - فروخت بل پرچہ</strong> &nbsp;|&nbsp; 
        <span>بل نمبر: <span class="num"><?= htmlspecialchars($b['bill_no']) ?></span></span> &nbsp;|&nbsp;
        <span>خریدار: <strong><?= htmlspecialchars($b['customer_name']) ?></strong></span>
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

<?php 
// Function to render one bill copy
function render_sale_bill_copy($b, $settings, $bal, $copy_title) {
?>
<div class="bill-card">
    <div class="header-section">
        <span class="badge-copy"><?= $copy_title ?></span>
        <h1 class="shop-title"><?= htmlspecialchars(($settings['shop_name'] ?? '') ?: 'سبحانی فروٹ کمپنی') ?></h1>
        <div class="shop-meta">
            <?= htmlspecialchars(($settings['address'] ?? '') ?: ((($settings['mandi_name'] ?? '') . '، ' . ($settings['city'] ?? '')) ?: 'غلہ و سبزی و فروٹ منڈی، سیالکوٹ')) ?>
            <?php if (!empty($settings['phone'])): ?>
                &nbsp;|&nbsp; فون: <span class="num"><?= htmlspecialchars($settings['phone']) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <span class="badge-type">فروخت بل پرچہ (Auction Sale Slip)</span>
        </div>
    </div>

    <!-- Meta Details -->
    <div class="meta-grid">
        <div class="meta-item">
            <span class="meta-label">بل نمبر:</span>
            <span class="num fw-bold"><?= htmlspecialchars($b['bill_no']) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">تاریخ فروخت:</span>
            <span class="num"><?= format_dt($b['sale_date']) ?></span>
        </div>
        <div class="meta-item" style="grid-column: span 2;">
            <span class="meta-label">خریدار / پارٹی:</span>
            <strong><?= htmlspecialchars($b['customer_name']) ?></strong>
            <?php if (!empty($b['customer_city'])): ?>
                (<?= htmlspecialchars($b['customer_city']) ?>)
            <?php endif; ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">فون نمبر:</span>
            <span class="num"><?= htmlspecialchars($b['customer_mobile'] ?: '-') ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">مارکہ لاٹ:</span>
            <strong><?= htmlspecialchars($b['marke'] ?: $b['lot_marke']) ?></strong>
        </div>
        <div class="meta-item">
            <span class="meta-label">چالان نمبر:</span>
            <span class="num"><?= htmlspecialchars($b['challan_no'] ?: '-') ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">سپلائر / زمیندار:</span>
            <span><?= htmlspecialchars($b['farmer_name'] ?: 'لوکل') ?></span>
        </div>
    </div>

    <!-- Bill Items Table -->
    <table class="bill-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th style="text-align: right;">جنس و تفصیل</th>
                <th style="width: 100px;">پیکنگ</th>
                <th style="width: 80px;">مارکہ</th>
                <th style="width: 90px;">تعداد نگ</th>
                <th style="width: 110px;">ریٹ فی نگ (روپے)</th>
                <th style="width: 130px;">کل رقم (روپے)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td style="text-align: right;">
                    <strong><?= htmlspecialchars($b['item_name']) ?></strong>
                    <?php if (!empty($b['notes'])): ?>
                        <div style="font-size: 11pt; color: #444;">نوٹ: <?= htmlspecialchars($b['notes']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($b['packing_name']) ?></td>
                <td><?= htmlspecialchars($b['marke'] ?: $b['lot_marke']) ?></td>
                <td><strong class="num" style="font-size: 14pt;"><?= number_format($b['nag']) ?></strong></td>
                <td><span class="num"><?= format_rs($b['rate']) ?></span></td>
                <td><strong class="num" style="font-size: 14pt;"><?= format_rs($b['net_amount']) ?></strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="4" style="text-align: left;">کل میزان رقم:</td>
                <td><span class="num" style="font-size: 13pt;"><?= number_format($b['nag']) ?> نگ</span></td>
                <td></td>
                <td><strong class="num" style="font-size: 15pt;">Rs. <?= format_rs($b['net_amount']) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Financial Balance & Signatures -->
    <div class="summary-box">
        <table class="bal-table">
            <tr>
                <td style="background: #f2f2f2; width: 55%;">حالت بل:</td>
                <td>
                    <?php if ($b['is_paid']): ?>
                        <strong style="color: green;">نقد ادا شدہ (CASH PAID)</strong>
                    <?php else: ?>
                        <strong style="color: red;">ادھار کھاتہ (ON CREDIT)</strong>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="background: #f2f2f2;">موجودہ کل بقایا کھاتہ:</td>
                <td>
                    <strong class="num">Rs. <?= format_rs($bal['balance']) ?></strong>
                    (<?= $bal['status'] ?>)
                </td>
            </tr>
        </table>

        <div class="signatures">
            <div class="sig-line">
                دستخط خریدار
            </div>
            <div class="sig-line">
                دستخط منشی / آڑھت
            </div>
        </div>
    </div>
</div>
<?php 
} // end function
?>

<div class="two-up-container">
    <!-- Copy 1: Customer Copy -->
    <?php render_sale_bill_copy($b, $settings, $bal, 'نقل خریدار (Customer Copy)'); ?>

    <div class="divider-cut">
        <span>✂️ یہاں سے کاٹیں (Cut Along Line)</span>
    </div>

    <!-- Copy 2: Office / Mandi Record Copy -->
    <?php render_sale_bill_copy($b, $settings, $bal, 'نقل آڑھت / دفتر (Office Copy)'); ?>
</div>

</body>
</html>
