<?php
/**
 * Global Header with Jameel Noori Nastaleeq Typography & Balanced Non-Clipping Layout
 */
require_once __DIR__ . '/../functions.php';
$settings = get_settings();
$cash_stats = get_cash_in_hand();
$flash = flash_get();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['shop_name'] ?? APP_NAME) ?> - منڈی کمیشن اکاؤنٹنگ سسٹم</title>
    
    <!-- Google Fonts for Clean Tabular Digits -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&family=Tajawal:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL & FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Jameel Noori Nastaleeq Webfont */
        @font-face {
            font-family: 'Jameel Noori Nastaleeq';
            src: local('Jameel Noori Nastaleeq'),
                 url('<?= BASE_URL ?>assets/fonts/JameelNooriNastaleeq.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --primary-green: #1b5e20;
            --primary-dark: #103b14;
            --secondary-gold: #f59e0b;
            --accent-green: #2e7d32;
            --bg-light: #f1f5f9;
            --text-main: #0f172a;
        }

        /* Jameel Noori Nastaleeq applied as Primary Font with Calibrated Line-Height */
        body {
            font-family: 'Jameel Noori Nastaleeq', 'Noto Nastaliq Urdu', 'Tajawal', Tahoma, serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.95;
            font-size: 1.12rem;
            letter-spacing: 0;
        }

        /* Latin / English Digits (Numbers, Currency, Dates, Codes) */
        .rubik-digits, .rubik-font, input[type="number"], input[type="date"], input[type="tel"] {
            font-family: 'Rubik', sans-serif !important;
            letter-spacing: 0.3px;
        }

        /* Top Header Bar */
        .top-navbar {
            background: linear-gradient(135deg, #0d3810 0%, #1b5e20 50%, #25792a 100%);
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            padding: 8px 24px;
            position: sticky;
            top: 0;
            z-index: 1040;
        }

        .shop-title {
            font-family: 'Jameel Noori Nastaleeq', serif;
            font-size: 1.9rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.7;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.25);
            display: inline-block;
        }

        .shop-subtitle {
            font-size: 0.92rem;
            color: #d1fae5;
            line-height: 1.4;
            margin-top: -3px;
        }

        .cash-badge {
            background-color: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.32);
            border-radius: 8px;
            padding: 4px 16px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }

        .cash-badge .amount {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fef08a;
            font-family: 'Rubik', sans-serif !important;
        }

        /* Main Menu Navigation Bar */
        .main-menu-nav {
            background: #ffffff;
            border-bottom: 2px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            padding: 0 16px;
            position: relative;
            z-index: 1030;
            overflow: visible !important;
        }

        .main-menu-nav .container-fluid {
            overflow: visible !important;
        }

        .nav-scroll-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            overflow: visible !important;
        }

        .nav-item-btn {
            font-family: 'Jameel Noori Nastaleeq', serif;
            color: #1e293b;
            font-weight: 600;
            font-size: 1.15rem;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
            line-height: 1.6;
        }

        .nav-item-btn:hover {
            color: var(--primary-green);
            background-color: #f0fdf4;
            border-bottom-color: var(--primary-green);
        }

        .nav-item-btn.active {
            color: var(--primary-green);
            background-color: #f0fdf4;
            border-bottom-color: var(--primary-green);
            font-weight: 700;
        }

        .nav-item-btn i {
            font-size: 1.05rem;
            color: #16a34a;
        }

        /* Dropdowns - High Z-Index, Absolute Positioning, Non-Clipping */
        .dropdown {
            position: relative;
        }

        .dropdown:hover .dropdown-menu-mandi {
            display: block;
        }

        .dropdown-menu-mandi {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            left: auto !important;
            z-index: 9999 !important;
            min-width: 240px;
            background: #ffffff !important;
            border-radius: 8px;
            border: 1px solid #94a3b8 !important;
            box-shadow: 0 12px 28px rgba(0,0,0,0.18) !important;
            padding: 6px 0;
            margin-top: 2px;
        }

        .dropdown-menu-mandi .dropdown-item {
            padding: 9px 16px;
            font-family: 'Jameel Noori Nastaleeq', serif;
            font-size: 1.18rem;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.7;
            color: #1e293b;
        }

        .dropdown-menu-mandi .dropdown-item:hover {
            background-color: #e8f5e9;
            color: var(--primary-green);
            font-weight: 700;
        }

        /* Hotkey bar */
        .hotkeys-bar {
            background: #0f172a;
            color: #cbd5e1;
            font-size: 0.95rem;
            padding: 5px 15px;
            display: flex;
            gap: 14px;
            flex-wrap: nowrap;
            overflow-x: auto;
            justify-content: center;
            align-items: center;
            white-space: nowrap;
            line-height: 1.5;
            font-family: 'Jameel Noori Nastaleeq', serif;
        }

        .hotkey-item kbd {
            background: #1e293b;
            color: #f8fafc;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #475569;
            font-weight: 700;
            font-family: 'Rubik', sans-serif;
            font-size: 0.8rem;
            margin-left: 4px;
        }

        /* High-speed Grid / Table with Jameel Noori Typography */
        .table-mandi {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            margin-bottom: 0;
        }

        .table-mandi th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e1;
            padding: 10px 8px;
            font-size: 1.1rem;
            vertical-align: middle;
            text-align: center;
            font-family: 'Jameel Noori Nastaleeq', serif;
            line-height: 1.6;
        }

        .table-mandi td {
            vertical-align: middle;
            padding: 8px 8px;
            border-color: #e2e8f0;
            font-size: 1.12rem;
            line-height: 1.8;
        }

        .table-mandi tr:hover {
            background-color: #f8fafc;
        }

        /* Cards */
        .card-custom {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }

        .card-custom-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 18px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Jameel Noori Nastaleeq', serif;
            line-height: 1.7;
        }

        .btn-mandi-primary {
            background-color: #1b5e20;
            color: #ffffff;
            border: none;
            font-family: 'Jameel Noori Nastaleeq', serif;
            font-size: 1.18rem;
            font-weight: 600;
            padding: 6px 18px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            line-height: 1.6;
            transition: all 0.15s;
        }

        .btn-mandi-primary:hover {
            background-color: #144618;
            color: #ffffff;
        }

        .badge-dr {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #f87171;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.95rem;
            font-family: 'Jameel Noori Nastaleeq', serif;
        }

        .badge-cr {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.95rem;
            font-family: 'Jameel Noori Nastaleeq', serif;
        }

        .form-label {
            margin-bottom: 3px;
            font-weight: 700;
            font-size: 1.05rem;
            font-family: 'Jameel Noori Nastaleeq', serif;
            line-height: 1.6;
        }

        /* Inputs & Select dropdowns use clean modern Urdu font so Windows native comboboxes don't clip */
        .form-control, .form-select {
            border-radius: 6px;
            border-color: #cbd5e1;
            padding: 8px 12px;
            font-size: 1.02rem;
            font-family: 'Tajawal', 'Almarai', Tahoma, sans-serif !important;
            line-height: 1.5;
        }

        .form-select option {
            font-family: 'Tajawal', 'Almarai', Tahoma, sans-serif !important;
            padding: 6px 10px;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
        }

        @media print {
            .no-print, .top-navbar, .main-menu-nav, .hotkeys-bar {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .card-custom {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Top Header Bar -->
<header class="top-navbar no-print">
    <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
        <!-- Logo & Shop Info in Jameel Noori Nastaleeq -->
        <div class="d-flex align-items-center gap-2">
            <span class="fs-2 text-warning"><i class="fa-solid fa-lemon"></i></span>
            <div>
                <h1 class="shop-title"><?= htmlspecialchars($settings['shop_name']) ?></h1>
                <div class="shop-subtitle">
                    <i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($settings['mandi_name']) ?>، <?= htmlspecialchars($settings['city']) ?> &nbsp;|&nbsp; 
                    <i class="fa-solid fa-phone me-1"></i> <span class="rubik-digits"><?= htmlspecialchars($settings['phone']) ?></span>
                </div>
            </div>
        </div>

        <!-- Middle: Today Date -->
        <div class="text-white text-center d-none d-md-block px-3 py-1 rounded" style="background: rgba(255,255,255,0.08);">
            <div class="small opacity-75" style="font-size: 0.85rem; line-height: 1.2;">آج کی تاریخ</div>
            <div class="fw-bold rubik-digits" style="font-size: 0.95rem;"><?= date('d F Y') ?> (<?= date('l') ?>)</div>
        </div>

        <!-- Left: Cash Balance & Mode -->
        <div class="d-flex align-items-center gap-2">
            <div class="cash-badge">
                <i class="fa-solid fa-vault fs-5 text-warning"></i>
                <div class="text-end">
                    <div style="font-size: 0.82rem; line-height: 1.1;">روکڑ کیش (Cash in Hand)</div>
                    <span class="amount">Rs. <?= format_rs($cash_stats['cash_in_hand']) ?></span>
                </div>
            </div>

            <span class="badge bg-<?= ENV_MODE === 'local' ? 'success' : 'info' ?> p-1 px-2 rounded-pill d-none d-lg-inline-block" style="font-size: 0.85rem;">
                <i class="fa-solid fa-<?= ENV_MODE === 'local' ? 'desktop' : 'cloud' ?> me-1"></i> 
                <?= ENV_MODE === 'local' ? 'لوکل' : 'کلاؤڈ' ?>
            </span>
        </div>
    </div>
</header>

<!-- Main Navigation Bar - Strictly Single Clean Row with Jameel Noori Font -->
<nav class="main-menu-nav no-print">
    <div class="container-fluid d-flex align-items-center justify-content-between p-0">
        <div class="nav-scroll-container">
            <!-- 1. Dashboard -->
            <a href="<?= BASE_URL ?>index.php" class="nav-item-btn <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> ڈیش بورڈ
            </a>

            <!-- 2. Parties -->
            <a href="<?= BASE_URL ?>parties.php" class="nav-item-btn <?= $current_page == 'parties.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> کھاتے
            </a>

            <!-- 3. Inward / Amad -->
            <a href="<?= BASE_URL ?>maal_amad.php" class="nav-item-btn <?= $current_page == 'maal_amad.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-ramp-box"></i> مال آمد
            </a>

            <!-- 4. Sales / Boli -->
            <a href="<?= BASE_URL ?>boli_sales.php" class="nav-item-btn <?= $current_page == 'boli_sales.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gavel"></i> بولی و فروخت
            </a>

            <!-- 5. Bazar Khata -->
            <a href="<?= BASE_URL ?>bazar_khata.php" class="nav-item-btn <?= $current_page == 'bazar_khata.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-cash-register"></i> بازار کھاتہ
            </a>

            <!-- 6. Chittha -->
            <a href="<?= BASE_URL ?>chittha.php" class="nav-item-btn <?= $current_page == 'chittha.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> چٹھہ بقایا
            </a>

            <!-- 7. Vouchers -->
            <a href="<?= BASE_URL ?>vouchers.php" class="nav-item-btn <?= $current_page == 'vouchers.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-money-bill-transfer"></i> واؤچرز
            </a>

            <!-- 8. Rokad -->
            <a href="<?= BASE_URL ?>rokad.php" class="nav-item-btn <?= $current_page == 'rokad.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-book-bookmark"></i> روکڑ کیش بک
            </a>

            <!-- 9. Reports Dropdown -->
            <div class="dropdown">
                <a class="nav-item-btn dropdown-toggle <?= in_array($current_page, ['zamindar_bill.php', 'ledgers.php', 'stock_report.php', 'daily_summary.php']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="fa-solid fa-chart-pie"></i> رپورٹس
                </a>
                <ul class="dropdown-menu dropdown-menu-mandi text-end shadow" data-bs-popper="static">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>zamindar_bill.php">
                            <i class="fa-solid fa-file-invoice-dollar text-success"></i> زمیندار بل (فردِ حساب)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>ledgers.php">
                            <i class="fa-solid fa-book-open text-primary"></i> تفصیلی کھاتہ (لیجر)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>stock_report.php">
                            <i class="fa-solid fa-boxes-stacked text-warning"></i> سٹاک رپورٹ (آمد و فروخت)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>daily_summary.php">
                            <i class="fa-solid fa-chart-line text-info"></i> ایک روزہ موازنہ رپورٹ
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 10. Items & Packings -->
            <a href="<?= BASE_URL ?>items.php" class="nav-item-btn <?= $current_page == 'items.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-apple-whole"></i> جنس و پیکنگ
            </a>
        </div>

        <!-- Right Side: Settings & Backup -->
        <div class="d-flex align-items-center">
            <a href="<?= BASE_URL ?>settings.php" class="nav-item-btn text-secondary <?= $current_page == 'settings.php' ? 'active' : '' ?>" title="سیٹنگز">
                <i class="fa-solid fa-gear text-secondary"></i>
            </a>
            <a href="<?= BASE_URL ?>backup.php" class="nav-item-btn text-secondary <?= $current_page == 'backup.php' ? 'active' : '' ?>" title="بیک اپ">
                <i class="fa-solid fa-download text-secondary"></i>
            </a>
        </div>
    </div>
</nav>

<!-- Clean Keyboard Hotkeys Bar (Single Line) -->
<div class="hotkeys-bar no-print">
    <div class="hotkey-item"><kbd>F1</kbd> ڈیش بورڈ</div>
    <div class="hotkey-item"><kbd>F2</kbd> بولی بل</div>
    <div class="hotkey-item"><kbd>F3</kbd> مال آمد</div>
    <div class="hotkey-item"><kbd>F4</kbd> واؤچر</div>
    <div class="hotkey-item"><kbd>F5</kbd> بازار کھاتہ</div>
    <div class="hotkey-item"><kbd>F6</kbd> روکڑ کیش بک</div>
    <div class="hotkey-item"><kbd>F7</kbd> کھاتے</div>
    <div class="hotkey-item"><kbd>F8</kbd> چٹھہ بقایا</div>
</div>

<main class="container-fluid py-3 flex-grow-1">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm py-2" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> <?= htmlspecialchars($flash['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
