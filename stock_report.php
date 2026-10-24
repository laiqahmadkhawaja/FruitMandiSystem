<?php
/**
 * Stock Report (سٹاک رپورٹ - آمد، فروخت اور بقایا سٹاک)
 * Matching Video Frame 028
 */
require_once __DIR__ . '/includes/header.php';

$item_filter = (int)($_GET['item_id'] ?? 0);
$status_filter = clean($_GET['status'] ?? 'all'); // all, available, completed

$items = $pdo->query("SELECT id, urdu_name FROM `items` ORDER BY `urdu_name` ASC")->fetchAll();

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

// Calculate Totals
$tot_amad = 0;
$tot_sold = 0;
$tot_rem = 0;

foreach ($stock_rows as $r) {
    $tot_amad += $r['total_nag'];
    $tot_sold += $r['sold_nag'];
    $tot_rem += $r['remaining_nag'];
}
?>

<div class="card-custom">
    <!-- Header with Filters -->
    <div class="card-custom-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-success fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">سٹاک رپورٹ - آمد، فروخت اور بقایا سٹاک (Stock Report)</h4>
        </div>

        <form method="GET" action="stock_report.php" class="d-flex align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">جنس:</label>
                <select name="item_id" class="form-select form-select-sm urdu-font" onchange="this.form.submit()">
                    <option value="0">تمام پھل و سبزی</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id'] ?>" <?= $item_filter === $it['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($it['urdu_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">سٹاک کی صورتحال:</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>تمام لاٹس</option>
                    <option value="available" <?= $status_filter === 'available' ? 'selected' : '' ?>>صرف موجودہ سٹاک (باقی > 0)</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>مکمل فروخت شدہ</option>
                </select>
            </div>

            <a href="print_stock.php?item_id=<?= $item_filter ?>&status=<?= urlencode($status_filter) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="fa-solid fa-print"></i> پرنٹ سٹاک
            </a>
        </form>
    </div>

    <!-- Stock KPI Cards -->
    <div class="p-3 bg-light border-bottom">
        <div class="row g-3 text-center">
            <div class="col-4">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">کل آمد نَگ (Inward):</span>
                    <h4 class="rubik-font fw-bold text-dark m-0 mt-1"><?= number_format($tot_amad) ?></h4>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 bg-white rounded border">
                    <span class="text-muted small">کل فروخت شدہ نَگ (Sold):</span>
                    <h4 class="rubik-font fw-bold text-primary m-0 mt-1"><?= number_format($tot_sold) ?></h4>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 bg-white rounded border border-danger">
                    <span class="text-danger small fw-bold">منڈی میں موجود باقی سٹاک (Remaining):</span>
                    <h4 class="rubik-font fw-bold text-danger m-0 mt-1"><?= number_format($tot_rem) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Table (Matching Video Frame 028) -->
    <div class="table-responsive p-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>جنس (Commodity)</th>
                    <th>مارکہ (Marke)</th>
                    <th>پیکنگ (Packing)</th>
                    <th>زمیندار (Farmer)</th>
                    <th style="width: 140px;" class="bg-light">آمد نَگ (Inward)</th>
                    <th style="width: 140px;" class="bg-primary-subtle text-primary">فروخت نَگ (Sold)</th>
                    <th style="width: 140px;" class="bg-danger-subtle text-danger">باقی سٹاک (Remaining)</th>
                    <th style="width: 100px;">فروخت کریں</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stock_rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">کوئی سٹاک ریکارڈ نہیں ملا</td></tr>
                <?php else: 
                    $c = 1;
                    foreach ($stock_rows as $stk):
                ?>
                    <tr class="<?= $stk['remaining_nag'] > 0 ? '' : 'table-light text-muted' ?>">
                        <td class="text-center rubik-font small"><?= $c++ ?></td>
                        <td class="urdu-font fw-bold fs-5 text-dark"><?= htmlspecialchars($stk['item_name']) ?></td>
                        <td><span class="badge bg-secondary fs-6"><?= htmlspecialchars($stk['marke']) ?></span></td>
                        <td><?= htmlspecialchars($stk['packing_name']) ?></td>
                        <td class="urdu-font">
                            <?= htmlspecialchars($stk['farmer_name']) ?> 
                            <small class="text-muted">(<?= htmlspecialchars($stk['farmer_city'] ?: '-') ?>)</small>
                        </td>
                        <td class="text-center rubik-font fw-bold fs-6"><?= number_format($stk['total_nag']) ?></td>
                        <td class="text-center rubik-font fw-bold text-primary fs-6"><?= number_format($stk['sold_nag']) ?></td>
                        <td class="text-center rubik-font fw-bold text-danger fs-5 <?= $stk['remaining_nag'] > 0 ? 'bg-danger-subtle' : '' ?>">
                            <?= number_format($stk['remaining_nag']) ?>
                        </td>
                        <td class="text-center no-print">
                            <?php if ($stk['remaining_nag'] > 0): ?>
                                <a href="boli_sales.php" class="btn btn-sm btn-success p-1 px-2">
                                    <i class="fa-solid fa-gavel"></i> بولی
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success">مکمل</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold fs-6">
                <tr>
                    <td colspan="5" class="text-end urdu-font">کل میزان (Total Stock):</td>
                    <td class="text-center rubik-font text-dark fs-5"><?= number_format($tot_amad) ?></td>
                    <td class="text-center rubik-font text-primary fs-5"><?= number_format($tot_sold) ?></td>
                    <td class="text-center rubik-font text-danger fs-4"><?= number_format($tot_rem) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
