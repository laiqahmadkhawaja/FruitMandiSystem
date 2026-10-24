<?php
/**
 * Reports Center (مرکزی رپورٹ سنٹر - تمام رپورٹس اور پرنٹنگ کی سہولت)
 */
require_once __DIR__ . '/includes/header.php';

$today = date('Y-m-d');
$parties = $pdo->query("SELECT id, urdu_name, code, type, city FROM `parties` ORDER BY `type`, `urdu_name` ASC")->fetchAll();
$cities = $pdo->query("SELECT DISTINCT `city` FROM `parties` WHERE `city` IS NOT NULL AND `city` != '' ORDER BY `city` ASC")->fetchAll(PDO::FETCH_COLUMN);
$items = $pdo->query("SELECT id, urdu_name FROM `items` ORDER BY `urdu_name` ASC")->fetchAll();
$zamindar_bills = $pdo->query("SELECT zb.id, zb.bill_no, zb.bill_date, p.urdu_name as farmer_name, m.challan_no 
    FROM `zamindar_bills` zb 
    JOIN `parties` p ON zb.farmer_id = p.id 
    JOIN `maal_amad` m ON zb.amad_id = m.id 
    ORDER BY zb.id DESC LIMIT 15")->fetchAll();
$sales_bills = $pdo->query("SELECT s.id, s.bill_no, s.sale_date, p.urdu_name as customer_name, s.net_amount 
    FROM `sales_bills` s 
    JOIN `parties` p ON s.customer_id = p.id 
    ORDER BY s.id DESC LIMIT 15")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="urdu-font fw-bold text-primary m-0">
            <i class="fa-solid fa-print text-success"></i> مرکزی رپورٹ و پرنٹنگ سنٹر (All Mandi Reports)
        </h3>
        <p class="text-muted small m-0 urdu-font">
            تمام کھاتہ جات، بلز، سٹاک اور روزنامچہ روکڑ کے صاف و معیاری پرنٹ صفحات بغیر کسی رکاوٹ کے حاصل کریں۔
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="print_rokad.php?date=<?= $today ?>" target="_blank" class="btn btn-outline-success btn-sm urdu-font">
            <i class="fa-solid fa-receipt"></i> آج کی روکڑ پرنٹ کریں
        </a>
        <a href="print_bazar_khata.php?date=<?= $today ?>" target="_blank" class="btn btn-outline-primary btn-sm urdu-font">
            <i class="fa-solid fa-cash-register"></i> آج کا بازار کھاتہ
        </a>
        <a href="print_chittha.php" target="_blank" class="btn btn-outline-danger btn-sm urdu-font">
            <i class="fa-solid fa-motorcycle"></i> چٹھہ ریکوری شیٹ
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- 1. زمیندار فرد حساب (Zamindar Bill) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">زمیندار فردِ حساب (فارمر بل)</h5>
                        <small class="text-muted">Farmer Statement & Expense Deductions</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    مال کی بولی تفصیل، جملہ اخراجات (کمیشن، کرایہ گاڑی، مزدوری، منشیانہ، مارکیٹ فیس) اور خالص واجب الادا رقم۔
                </p>
                <form action="print_zamindar_bill.php" method="GET" target="_blank" class="mt-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold urdu-font">بل منتخب کریں:</label>
                        <select name="id" class="form-select form-select-sm urdu-font" required>
                            <?php foreach ($zamindar_bills as $zb): ?>
                                <option value="<?= $zb['id'] ?>">
                                    بل # <?= htmlspecialchars($zb['bill_no']) ?> - <?= htmlspecialchars($zb['farmer_name']) ?> (<?= format_dt($zb['bill_date']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="zamindar_bill.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. فروخت پرچہ خریدار (Sale Bill) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-info">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-info-subtle text-info p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-receipt"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">فروخت بل پرچہ (خریدار پرچہ)</h5>
                        <small class="text-muted">Buyer Auction Slip (Double Copy A4/A5)</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    بولی کے بعد خریدار کو دیا جانے والا تصدیقی پرچہ، ایک نقل خریدار اور ایک نقل آڑھت کے لیے۔
                </p>
                <form action="print_sale_bill.php" method="GET" target="_blank" class="mt-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold urdu-font">فروخت بل منتخب کریں:</label>
                        <select name="id" class="form-select form-select-sm urdu-font" required>
                            <?php foreach ($sales_bills as $sb): ?>
                                <option value="<?= $sb['id'] ?>">
                                    بل # <?= htmlspecialchars($sb['bill_no']) ?> - <?= htmlspecialchars($sb['customer_name']) ?> (Rs. <?= format_rs($sb['net_amount']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-info text-white btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="boli_sales.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. بازار کھاتہ (Bazar Khata) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-book"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">بازار کھاتہ وصولی رجسٹر</h5>
                        <small class="text-muted">Daily Market Collection Register</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    تمام خریداروں کی صبح و شام کی نقد وصولیاں، سابقہ بقایا جات اور کل بقایا کے کالم۔
                </p>
                <form action="print_bazar_khata.php" method="GET" target="_blank" class="mt-3">
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small fw-bold urdu-font">تاریخ:</label>
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= $today ?>">
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold urdu-font">شہر:</label>
                            <select name="city" class="form-select form-select-sm">
                                <option value="">تمام شہر</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="bazar_khata.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 4. چٹھہ بقایا جات (Chittha) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-danger">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger-subtle text-danger p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-motorcycle"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">چٹھہ بقایا جات (ریکوری لسٹ)</h5>
                        <small class="text-muted">Field Arrears Sheet for Recovery Staff</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    مارکیٹ ریکوری کے لیے کسٹمر وائز بقایا لسٹ مع خالی دستی وصولی باکس اور دستخط۔
                </p>
                <form action="print_chittha.php" method="GET" target="_blank" class="mt-3">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold urdu-font">شہر / روٹ:</label>
                            <select name="city" class="form-select form-select-sm">
                                <option value="">تمام شہر</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <div class="form-check mt-4 pt-1">
                                <input class="form-check-input" type="checkbox" name="show_nil" value="yes" id="chitthaNil">
                                <label class="form-check-label small urdu-font" for="chitthaNil">صفر بیلنس بھی</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-danger btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="chittha.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. روکڑ کھاتہ (Rokad) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-dark">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary-subtle text-dark p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">روزنامچہ روکڑ کھاتہ</h5>
                        <small class="text-muted">Daily Cash Book & Reconciliation</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    افتتاحی کیش، تمام آمد/وصولیاں، تمام خرچ/ادائیگیاں، اختتامی کیش ان ہینڈ اور روزانہ حساب کتاب۔
                </p>
                <form action="print_rokad.php" method="GET" target="_blank" class="mt-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold urdu-font">تاریخ:</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= $today ?>" required>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-dark btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="rokad.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. تفصیلی کھاتہ (Party Ledger) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning-subtle text-warning p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-address-book"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">تفصیلی پارٹی کھاتہ (لیجر)</h5>
                        <small class="text-muted">Account Statement with Running Balance</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    کسی بھی زمیندار یا گاہک کا از تاریخ تا تاریخ مکمل کھاتہ، رننگ بیلنس اور واجب الوصول رقم۔
                </p>
                <form action="print_ledger.php" method="GET" target="_blank" class="mt-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold urdu-font">کھاتہ دار / پارٹی:</label>
                        <select name="party_id" class="form-select form-select-sm urdu-font" required>
                            <option value="">-- پارٹی منتخب کریں --</option>
                            <?php foreach ($parties as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['urdu_name']) ?> (<?= $p['type'] === 'farmer' ? 'زمیندار' : 'گاہک' ?> - <?= htmlspecialchars($p['city'] ?: 'سیالکوٹ') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold urdu-font">از تاریخ:</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= date('Y-01-01') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold urdu-font">تا تاریخ:</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $today ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-warning btn-sm w-100 urdu-font text-dark">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="ledgers.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 7. سٹاک رپورٹ (Stock Inventory) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-secondary">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary-subtle text-secondary p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">سٹاک انوینٹری رپورٹ</h5>
                        <small class="text-muted">Arrivals, Sold & Remaining Bags Stock</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    آمد، فروخت شدہ نگ، گودام میں بقایا نگ اور ہر لاٹ کی مکمل صورتحال۔
                </p>
                <form action="print_stock.php" method="GET" target="_blank" class="mt-3">
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small fw-bold urdu-font">جنس:</label>
                            <select name="item_id" class="form-select form-select-sm urdu-font">
                                <option value="0">تمام اجناس</option>
                                <?php foreach ($items as $it): ?>
                                    <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['urdu_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold urdu-font">حالت:</label>
                            <select name="status" class="form-select form-select-sm urdu-font">
                                <option value="all">تمام لاٹس</option>
                                <option value="available" selected>صرف دستیاب</option>
                                <option value="completed">مکمل فروخت</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-secondary btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="stock_report.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 8. ایک روزہ موازنہ رپورٹ (Daily Summary) -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success p-2 fs-5 rounded-circle">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    <div>
                        <h5 class="urdu-font fw-bold m-0">ایک روزہ موازنہ و تجزیاتی رپورٹ</h5>
                        <small class="text-muted">Daily Sales vs Collection Analysis</small>
                    </div>
                </div>
                <p class="small text-muted urdu-font">
                    ایک ہی دن کی کل فروخت، کل وصولیاں، ریکوری کا تناسب اور روزانہ خالص منافع کا تفصیلی جائزہ۔
                </p>
                <form action="print_daily_summary.php" method="GET" target="_blank" class="mt-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold urdu-font">تاریخ:</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= $today ?>" required>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 urdu-font">
                            <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
                        </button>
                        <a href="daily_summary.php" class="btn btn-outline-secondary btn-sm urdu-font">
                            صفحہ
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
