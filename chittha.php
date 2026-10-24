<?php
/**
 * Chittha Baqaya Jaat (چٹھہ بقایا جات - ریکوری لسٹ برائے ریکوری کار)
 * Matching Video Frame 026
 */
require_once __DIR__ . '/includes/header.php';

$show_nil = isset($_GET['show_nil']) && $_GET['show_nil'] === 'yes';
$city_filter = clean($_GET['city'] ?? '');
$officer_filter = clean($_GET['officer'] ?? '');

// Fetch recovery officers and cities
$cities = $pdo->query("SELECT DISTINCT `city` FROM `parties` WHERE `type` = 'customer' AND `city` IS NOT NULL AND `city` != '' ORDER BY `city` ASC")->fetchAll(PDO::FETCH_COLUMN);
$officers = $pdo->query("SELECT DISTINCT `recovery_officer` FROM `parties` WHERE `type` = 'customer' AND `recovery_officer` IS NOT NULL AND `recovery_officer` != '' ORDER BY `recovery_officer` ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch customers
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

<div class="card-custom">
    <!-- Header with Filters -->
    <div class="card-custom-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-receipt text-danger fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">چٹھہ بقایا جات (Chittha Arrears & Recovery Sheet)</h4>
        </div>

        <form method="GET" action="chittha.php" class="d-flex align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">شہر:</label>
                <select name="city" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">تمام شہر</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $city_filter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">ریکوری آفیسر:</label>
                <select name="officer" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">تمام آفیسرز</option>
                    <?php foreach ($officers as $off): ?>
                        <option value="<?= htmlspecialchars($off) ?>" <?= $officer_filter === $off ? 'selected' : '' ?>><?= htmlspecialchars($off) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-check form-switch ms-2">
                <input class="form-check-input" type="checkbox" name="show_nil" id="showNil" value="yes" <?= $show_nil ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label small fw-bold" for="showNil">صفر (0) بیلنس بھی دکھائیں</label>
            </div>

            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary ms-auto">
                <i class="fa-solid fa-print"></i> چٹھہ پرنٹ کریں
            </button>
        </form>
    </div>

    <!-- Chittha Print Format Table -->
    <div class="table-responsive p-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>نام گاہک (پارٹی)</th>
                    <th>معرفت / پتہ</th>
                    <th>شہر</th>
                    <th>موبائل فون #</th>
                    <th>موجودہ بقایا رقم (روپے)</th>
                    <th>کیفیت</th>
                    <th class="no-print">واٹس ایپ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_dr = 0;
                $grand_cr = 0;
                $count = 0;

                foreach ($customers as $c):
                    $bal = get_party_balance($c['id']);
                    if (!$show_nil && $bal['balance'] == 0) {
                        continue;
                    }
                    $count++;

                    if ($bal['status'] === 'نام') {
                        $grand_dr += $bal['balance'];
                    } elseif ($bal['status'] === 'جمع') {
                        $grand_cr += $bal['balance'];
                    }
                ?>
                    <tr class="<?= $c['is_blacklisted'] ? 'table-danger' : '' ?>">
                        <td class="text-center rubik-font small"><?= $count ?></td>
                        <td class="urdu-font fw-bold fs-5">
                            <a href="ledgers.php?party_id=<?= $c['id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($c['urdu_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($c['marfat'] ?: $c['address'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($c['city'] ?: '-') ?></td>
                        <td class="rubik-font"><?= htmlspecialchars($c['mobile'] ?: '-') ?></td>
                        <td class="text-end rubik-font fw-bold fs-5 <?= $bal['status'] === 'نام' ? 'text-danger' : 'text-success' ?>">
                            Rs. <?= format_rs($bal['balance']) ?>
                        </td>
                        <td class="text-center">
                            <?php if ($bal['status'] === 'نام'): ?>
                                <span class="badge-dr">نام (وصول طلب)</span>
                            <?php elseif ($bal['status'] === 'جمع'): ?>
                                <span class="badge-cr">جمع (ادائیگی طلب)</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">0 (برابر)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <?php 
                            $msg = "محترم " . $c['urdu_name'] . "، آپ کا چٹھہ بقایا کھاتہ بنام " . $settings['shop_name'] . " میں واجب الادا رقم: Rs. " . format_rs($bal['balance']) . " (" . $bal['status'] . ") ہے۔ برائے مہربانی آج کی وصولی وقت پر ادا فرمائیں۔ شکریہ۔";
                            ?>
                            <a href="<?= whatsapp_url($c['mobile'], $msg) ?>" target="_blank" class="btn btn-sm btn-success p-1 px-2" title="واٹس ایپ ریمائنڈر">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold fs-6">
                <tr>
                    <td colspan="5" class="text-end urdu-font">کل وصول طلب بقایا جات (Total Recoverable Debit):</td>
                    <td class="text-end rubik-font text-danger fs-4">Rs. <?= format_rs($grand_dr) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
