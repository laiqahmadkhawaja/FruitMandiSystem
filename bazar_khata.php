<?php
/**
 * Bazar Khata (بازار کھاتہ - صبح و شام کی وصولی رجسٹر)
 * Matching Video Frames 022 & 030
 */
require_once __DIR__ . '/functions.php';

$date = clean($_GET['date'] ?? date('Y-m-d'));
$city_filter = clean($_GET['city'] ?? '');
$filter_mode = clean($_GET['filter_mode'] ?? 'all'); // all, non_blacklisted, only_blacklisted, with_sales

// Handle Bulk / Quick Wasooli Save BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_bazar_wasooli') {
        $p_date = clean($_POST['date'] ?? date('Y-m-d'));
        $morning_arr = $_POST['morning'] ?? [];
        $evening_arr = $_POST['evening'] ?? [];

        $saved_count = 0;
        foreach ($morning_arr as $cust_id => $m_amt) {
            $cust_id = (int)$cust_id;
            $m_val = (float)$m_amt;
            $e_val = (float)($evening_arr[$cust_id] ?? 0);

            if ($m_val > 0 || $e_val > 0) {
                // Upsert into bazar_wasooli
                $stmt = $pdo->prepare("INSERT INTO `bazar_wasooli` (`date`, `customer_id`, `morning_amount`, `evening_amount`) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE `morning_amount` = VALUES(`morning_amount`), `evening_amount` = VALUES(`evening_amount`)");
                $stmt->execute([$p_date, $cust_id, $m_val, $e_val]);
                $saved_count++;
            }
        }

        flash_set("تاریخ " . format_dt($p_date) . " کے بازار کھاتہ وصولی کا ریکارڈ کامیابی سے محفوظ ہو گیا۔", 'success');
        header("Location: bazar_khata.php?date=" . urlencode($p_date) . "&city=" . urlencode($city_filter) . "&filter_mode=" . urlencode($filter_mode));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

// Get Distinct Cities for Dropdown Filter
$cities = $pdo->query("SELECT DISTINCT `city` FROM `parties` WHERE `type` = 'customer' AND `city` IS NOT NULL AND `city` != '' ORDER BY `city` ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch Customers matching filters
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

// Fetch today's sales per customer
$sales_stmt = $pdo->prepare("SELECT customer_id, COALESCE(SUM(net_amount), 0) as today_sales FROM `sales_bills` WHERE sale_date = ? GROUP BY customer_id");
$sales_stmt->execute([$date]);
$today_sales_map = $sales_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch today's bazar wasooli
$wasooli_stmt = $pdo->prepare("SELECT customer_id, morning_amount, evening_amount FROM `bazar_wasooli` WHERE date = ?");
$wasooli_stmt->execute([$date]);
$wasooli_rows = $wasooli_stmt->fetchAll();
$wasooli_map = [];
foreach ($wasooli_rows as $w) {
    $wasooli_map[$w['customer_id']] = $w;
}

// Filter if 'with_sales' is selected
if ($filter_mode === 'with_sales') {
    $customers = array_filter($customers, function($c) use ($today_sales_map) {
        return isset($today_sales_map[$c['id']]) && $today_sales_map[$c['id']] > 0;
    });
}
?>

<div class="card-custom">
    <!-- Header with Filters (Matching Video Frame 022) -->
    <div class="card-custom-header flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-cash-register text-success fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">بازار کھاتہ - صبح و شام وصولی (Bazar Recovery Register)</h4>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="bazar_khata.php" class="d-flex align-items-center flex-wrap gap-2">
            <!-- Date -->
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">تاریخ:</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
            </div>

            <!-- City Filter -->
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">شہر:</label>
                <select name="city" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">تمام شہر</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $city_filter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Mode Radios -->
            <div class="btn-group btn-group-sm">
                <input type="radio" class="btn-check" name="filter_mode" id="btnradio1" value="all" <?= $filter_mode === 'all' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="btn btn-outline-secondary" for="btnradio1">تمام کسٹمر</label>

                <input type="radio" class="btn-check" name="filter_mode" id="btnradio2" value="non_blacklisted" <?= $filter_mode === 'non_blacklisted' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="btn btn-outline-secondary" for="btnradio2">بلیک لسٹ نہ دکھائیں</label>

                <input type="radio" class="btn-check" name="filter_mode" id="btnradio3" value="only_blacklisted" <?= $filter_mode === 'only_blacklisted' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="btn btn-outline-secondary" for="btnradio3">صرف بلیک لسٹ</label>

                <input type="radio" class="btn-check" name="filter_mode" id="btnradio4" value="with_sales" <?= $filter_mode === 'with_sales' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="btn btn-outline-secondary" for="btnradio4">صرف آج کی فروخت</label>
            </div>

            <a href="print_bazar_khata.php?date=<?= urlencode($date) ?>&city=<?= urlencode($city_filter) ?>&filter_mode=<?= urlencode($filter_mode) ?>" target="_blank" class="btn btn-sm btn-outline-success ms-auto">
                <i class="fa-solid fa-print"></i> پرنٹ پیج کھولیں
            </a>
        </form>
    </div>

    <!-- Bazar Khata Interactive Grid -->
    <form method="POST" action="bazar_khata.php">
        <input type="hidden" name="post_action" value="save_bazar_wasooli">
        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">

        <div class="table-responsive">
            <table class="table table-mandi table-bordered align-middle">
                <thead>
                    <tr class="table-light">
                        <th style="width: 50px;">#</th>
                        <th style="width: 200px;">نام گاہک (خریدار)</th>
                        <th>شہر</th>
                        <th>سابقہ بیلنس</th>
                        <th>آج کی فروخت بل</th>
                        <th style="width: 150px;" class="bg-warning-subtle text-dark">وصولی صبح (Morning)</th>
                        <th style="width: 150px;" class="bg-warning-subtle text-dark">وصولی شام (Evening)</th>
                        <th>کل واجب الادا بقایا</th>
                        <th style="width: 80px;">واٹس ایپ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_prev = 0;
                    $total_sales = 0;
                    $total_morning = 0;
                    $total_evening = 0;
                    $total_closing = 0;

                    if (empty($customers)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">کوئی ریکارڈ نہیں ملا</td></tr>
                    <?php else: 
                        $counter = 1;
                        foreach ($customers as $cust):
                            $bal_info = get_party_balance($cust['id']);
                            $prev_balance = $bal_info['balance'];
                            $sale_amt = (float)($today_sales_map[$cust['id']] ?? 0);
                            $m_val = (float)($wasooli_map[$cust['id']]['morning_amount'] ?? 0);
                            $e_val = (float)($wasooli_map[$cust['id']]['evening_amount'] ?? 0);

                            $closing_bal = $prev_balance + $sale_amt - ($m_val + $e_val);

                            $total_prev += $prev_balance;
                            $total_sales += $sale_amt;
                            $total_morning += $m_val;
                            $total_evening += $e_val;
                            $total_closing += $closing_bal;
                    ?>
                        <tr class="<?= $cust['is_blacklisted'] ? 'table-danger' : '' ?>">
                            <td class="text-center rubik-font small"><?= $counter++ ?></td>
                            <td class="urdu-font fw-bold fs-6">
                                <a href="ledgers.php?party_id=<?= $cust['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($cust['urdu_name']) ?>
                                </a>
                                <?php if (!empty($cust['marfat'])): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($cust['marfat']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($cust['city'] ?: '-') ?></td>
                            <td class="text-end rubik-font fw-bold text-muted">Rs. <?= format_rs($prev_balance) ?></td>
                            <td class="text-end rubik-font fw-bold text-primary">Rs. <?= format_rs($sale_amt) ?></td>
                            
                            <!-- Editable Morning Collection -->
                            <td class="p-1 bg-warning-subtle">
                                <input type="number" step="0.01" name="morning[<?= $cust['id'] ?>]" 
                                    class="form-control form-control-sm text-end rubik-font fw-bold morning-input" 
                                    value="<?= $m_val > 0 ? $m_val : '' ?>" placeholder="0">
                            </td>

                            <!-- Editable Evening Collection -->
                            <td class="p-1 bg-warning-subtle">
                                <input type="number" step="0.01" name="evening[<?= $cust['id'] ?>]" 
                                    class="form-control form-control-sm text-end rubik-font fw-bold evening-input" 
                                    value="<?= $e_val > 0 ? $e_val : '' ?>" placeholder="0">
                            </td>

                            <!-- Net Closing Balance -->
                            <td class="text-end rubik-font fw-bold fs-6 text-danger">
                                Rs. <?= format_rs($closing_bal) ?>
                            </td>

                            <!-- WhatsApp -->
                            <td class="text-center">
                                <?php 
                                $wa_msg = "محترم " . $cust['urdu_name'] . "، آپ کا آج تاریخ " . format_dt($date) . " کو بقایا کھاتہ حساب: سابقہ Rs. " . format_rs($prev_balance) . "، آج کی سیل Rs. " . format_rs($sale_amt) . "، موصول شدہ وصولی Rs. " . format_rs($m_val + $e_val) . "، باقی بیلنس واجب الادا: Rs. " . format_rs($closing_bal) . "۔ رابطہ: " . $settings['shop_name'];
                                ?>
                                <a href="<?= whatsapp_url($cust['mobile'], $wa_msg) ?>" target="_blank" class="btn btn-sm btn-outline-success p-1 px-2" title="واٹس ایپ کریں">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($customers)): ?>
                <tfoot class="table-light fw-bold fs-6">
                    <tr class="align-middle">
                        <td colspan="3" class="text-end urdu-font">کل میزان (Grand Total):</td>
                        <td class="text-end rubik-font text-muted">Rs. <?= format_rs($total_prev) ?></td>
                        <td class="text-end rubik-font text-primary">Rs. <?= format_rs($total_sales) ?></td>
                        <td class="text-end rubik-font text-dark">Rs. <?= format_rs($total_morning) ?></td>
                        <td class="text-end rubik-font text-dark">Rs. <?= format_rs($total_evening) ?></td>
                        <td class="text-end rubik-font text-danger fs-5">Rs. <?= format_rs($total_closing) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Sticky Save Button Bar -->
        <div class="p-3 bg-white border-top sticky-bottom shadow-lg d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">کی بورڈ شارٹ کٹ: اینٹر دبا کر اگلے کسٹمر کی وصولی پر جائیں۔</span>
            </div>
            <div class="d-flex gap-2">
                <a href="print_bazar_khata.php?date=<?= urlencode($date) ?>&city=<?= urlencode($city_filter) ?>&filter_mode=<?= urlencode($filter_mode) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-print"></i> پرنٹ بازار کھاتہ
                </a>
                <button type="submit" class="btn btn-mandi-primary px-4 py-2 fs-5">
                    <i class="fa-solid fa-floppy-disk"></i> وصولی محفوظ کریں (Save All Recovery)
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
