<?php
/**
 * Maal Aamad / Inward Challan Entry (مال آمد کا اندراج)
 */
require_once __DIR__ . '/functions.php';

// Generate Next Challan No
$next_challan = (int)$pdo->query("SELECT MAX(id) FROM `maal_amad`")->fetchColumn() + 1;
$next_challan_no = 'CH-' . str_pad($next_challan, 4, '0', STR_PAD_LEFT);

// Handle New Challan Submission BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_amad') {
        $challan_no = clean($_POST['challan_no'] ?? $next_challan_no);
        $amad_date = clean($_POST['amad_date'] ?? date('Y-m-d'));
        $farmer_id = (int)($_POST['farmer_id'] ?? 0);
        $marke = clean($_POST['marke'] ?? '');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $packing_id = (int)($_POST['packing_id'] ?? 0);
        $total_nag = (int)($_POST['total_nag'] ?? 0);
        $vehicle_no = clean($_POST['vehicle_no'] ?? '');
        $bilty_no = clean($_POST['bilty_no'] ?? '');
        $total_freight = (float)($_POST['total_freight'] ?? 0);
        $freight_paid = (float)($_POST['freight_paid_by_agent'] ?? 0);
        $local_cartage = (float)($_POST['local_cartage'] ?? 0);
        $notes = clean($_POST['notes'] ?? '');

        if ($farmer_id <= 0 || $item_id <= 0 || $total_nag <= 0) {
            flash_set('براہ کرم زمیندار، جنس اور نگ کی تعداد درست درج کریں۔', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO `maal_amad` 
                (`challan_no`, `amad_date`, `farmer_id`, `marke`, `item_id`, `packing_id`, `total_nag`, `sold_nag`, `remaining_nag`, `vehicle_no`, `bilty_no`, `total_freight`, `freight_paid_by_agent`, `local_cartage`, `notes`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $challan_no, $amad_date, $farmer_id, $marke, $item_id, $packing_id,
                $total_nag, $total_nag, $vehicle_no, $bilty_no,
                $total_freight, $freight_paid, $local_cartage, $notes
            ]);

            flash_set("مال آمد چالان #{$challan_no} برائے مارکہ '{$marke}' کامیابی سے درج ہو گیا۔", 'success');
            header("Location: maal_amad.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch Farmers, Items, Packings
$farmers = $pdo->query("SELECT id, urdu_name, city, mobile FROM `parties` WHERE `type` = 'farmer' ORDER BY `urdu_name` ASC")->fetchAll();
$items = $pdo->query("SELECT id, urdu_name FROM `items` ORDER BY `urdu_name` ASC")->fetchAll();
$packings = $pdo->query("SELECT id, urdu_name FROM `packings` ORDER BY `id` ASC")->fetchAll();

// List of Inward Lots
$search = trim($_GET['search'] ?? '');
$query = "SELECT m.*, p.urdu_name as farmer_name, p.city as farmer_city, i.urdu_name as item_name, pk.urdu_name as packing_name 
    FROM `maal_amad` m 
    JOIN `parties` p ON m.farmer_id = p.id 
    JOIN `items` i ON m.item_id = i.id 
    JOIN `packings` pk ON m.packing_id = pk.id 
    WHERE 1=1";
$params = [];
if (!empty($search)) {
    $query .= " AND (m.challan_no LIKE ? OR m.marke LIKE ? OR p.urdu_name LIKE ? OR m.vehicle_no LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}
$query .= " ORDER BY m.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$amad_records = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Challan Inward Form (Matching Video Frame 007) -->
    <div class="col-12 col-xl-4">
        <div class="card-custom sticky-top" style="top: 80px;">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-truck-ramp-box text-success me-2"></i> نیا مال آمد چالان (New Inward Lot)</span>
            </div>
            <div class="p-3">
                <form method="POST" action="maal_amad.php">
                    <input type="hidden" name="post_action" value="save_amad">

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">چالان نمبر <span class="text-danger">*</span></label>
                            <input type="text" name="challan_no" class="form-control rubik-font fw-bold text-primary" 
                                value="<?= htmlspecialchars($next_challan_no) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">تاریخ آمد <span class="text-danger">*</span></label>
                            <input type="date" name="amad_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Farmer -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">زمیندار / کاشتکار (Farmer) <span class="text-danger">*</span></label>
                        <select name="farmer_id" class="form-select urdu-font fs-5" required>
                            <option value="">-- زمیندار منتخب کریں --</option>
                            <?php foreach ($farmers as $f): ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['urdu_name']) ?> (<?= htmlspecialchars($f['city'] ?: '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">مارکہ (Marke / Lot) <span class="text-danger">*</span></label>
                            <input type="text" name="marke" class="form-control fw-bold" placeholder="مثلاً: NZ+JM, 786" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">جنس (Fruit / Item) <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select urdu-font" required>
                                <?php foreach ($items as $it): ?>
                                    <option value="<?= $it['id'] ?>"><?= htmlspecialchars($it['urdu_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">پیکنگ کی قسم</label>
                            <select name="packing_id" class="form-select urdu-font" required>
                                <?php foreach ($packings as $pk): ?>
                                    <option value="<?= $pk['id'] ?>"><?= htmlspecialchars($pk['urdu_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-success">تعداد نَگ (Total Nag) <span class="text-danger">*</span></label>
                            <input type="number" name="total_nag" class="form-control rubik-font fs-5 fw-bold text-success" placeholder="50" required min="1">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">گاڑی / ٹرک نمبر</label>
                            <input type="text" name="vehicle_no" class="form-control" placeholder="LES-1234">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">بلٹی نمبر</label>
                            <input type="text" name="bilty_no" class="form-control" placeholder="B-998">
                        </div>
                    </div>

                    <!-- Freight Section (کرایہ) -->
                    <div class="border rounded p-2 bg-light mb-3">
                        <div class="small fw-bold text-muted mb-2"><i class="fa-solid fa-receipt me-1"></i> کرایہ کی تفصیلات (Freight)</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">کل گاڑی کرایہ</label>
                                <input type="number" step="0.01" name="total_freight" class="form-control rubik-font" placeholder="15000">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-danger">آڑھتی کا ادا کردہ کرایہ</label>
                                <input type="number" step="0.01" name="freight_paid_by_agent" class="form-control rubik-font text-danger fw-bold" placeholder="15000">
                            </div>
                            <div class="col-12 mt-1">
                                <label class="form-label small">لوکل کرایہ / اڈا خرچہ</label>
                                <input type="number" step="0.01" name="local_cartage" class="form-control rubik-font" placeholder="500">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">نوٹس / اضافی تفصیل</label>
                        <input type="text" name="notes" class="form-control" placeholder="مثلاً کوالٹی اے ون، سائز میڈیم">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-mandi-primary justify-content-center py-2">
                            <i class="fa-solid fa-floppy-disk"></i> چالان محفوظ کریں (Save Inward)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Inward Challans Table -->
    <div class="col-12 col-xl-8">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-list-check text-primary me-2"></i> مال آمد لاٹس کی فہرست (Inward Stock Lots)</span>
                <span class="badge bg-secondary"><?= count($amad_records) ?> لاٹس</span>
            </div>

            <!-- Search -->
            <div class="p-3 bg-light border-bottom">
                <form method="GET" action="maal_amad.php" class="row g-2">
                    <div class="col-md-9">
                        <input type="text" name="search" class="form-control" 
                            placeholder="چالان، مارکہ، گاڑی یا زمیندار کے نام سے تلاش کریں..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">تلاش</button>
                        <?php if ($search): ?>
                            <a href="maal_amad.php" class="btn btn-outline-secondary">سب</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>چالان #</th>
                            <th>تاریخ</th>
                            <th>زمیندار</th>
                            <th>مارکہ</th>
                            <th>جنس</th>
                            <th>کل نگ</th>
                            <th>فروخت نگ</th>
                            <th>باقی نگ</th>
                            <th>ادا شدہ کرایہ</th>
                            <th>فرد حساب</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($amad_records)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-5">کوئی مال آمد ریکارڈ نہیں ملا</td></tr>
                        <?php else: foreach ($amad_records as $rec): ?>
                            <tr>
                                <td class="rubik-font fw-bold text-center"><?= htmlspecialchars($rec['challan_no']) ?></td>
                                <td class="text-center small"><?= format_dt($rec['amad_date']) ?></td>
                                <td class="urdu-font fw-bold fs-6">
                                    <?= htmlspecialchars($rec['farmer_name']) ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($rec['farmer_city'] ?: '-') ?></small>
                                </td>
                                <td><span class="badge bg-dark fs-6"><?= htmlspecialchars($rec['marke']) ?></span></td>
                                <td><?= htmlspecialchars($rec['item_name']) ?> (<?= htmlspecialchars($rec['packing_name']) ?>)</td>
                                <td class="text-center rubik-font fw-bold"><?= $rec['total_nag'] ?></td>
                                <td class="text-center rubik-font text-primary"><?= $rec['sold_nag'] ?></td>
                                <td class="text-center rubik-font fw-bold <?= $rec['remaining_nag'] > 0 ? 'text-danger fs-6' : 'text-success' ?>">
                                    <?= $rec['remaining_nag'] ?>
                                </td>
                                <td class="text-end rubik-font">Rs. <?= format_rs($rec['freight_paid_by_agent']) ?></td>
                                <td class="text-center">
                                    <a href="zamindar_bill.php?amad_id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-success" title="زمیندار کا بل / فرد حساب بنائیں">
                                        <i class="fa-solid fa-file-invoice"></i> فرد حساب
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
