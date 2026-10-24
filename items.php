<?php
/**
 * Items (جنس) and Packing Management
 */
require_once __DIR__ . '/functions.php';

// Handle Form Submissions BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    // Add or Edit Item (جنس)
    if ($post_action === 'save_item') {
        $id = (int)($_POST['id'] ?? 0);
        $urdu_name = clean($_POST['urdu_name'] ?? '');
        $name = clean($_POST['name'] ?? '');
        $code = clean($_POST['code'] ?? '');
        $category = clean($_POST['category'] ?? 'Fruit');

        if (!empty($urdu_name)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE `items` SET `urdu_name` = ?, `name` = ?, `code` = ?, `category` = ? WHERE `id` = ?");
                $stmt->execute([$urdu_name, $name, $code, $category, $id]);
                flash_set("جنس '{$urdu_name}' کامیابی سے اپ ڈیٹ ہو گئی۔", 'success');
            } else {
                $stmt = $pdo->prepare("INSERT INTO `items` (`urdu_name`, `name`, `code`, `category`) VALUES (?, ?, ?, ?)");
                $stmt->execute([$urdu_name, $name, $code, $category]);
                flash_set("نئی جنس '{$urdu_name}' شامل کر دی گئی۔", 'success');
            }
        }
        header("Location: items.php");
        exit;
    }

    // Add or Edit Packing
    if ($post_action === 'save_packing') {
        $id = (int)($_POST['id'] ?? 0);
        $urdu_name = clean($_POST['urdu_name'] ?? '');
        $name = clean($_POST['name'] ?? '');
        $weight = (float)($_POST['default_weight_kg'] ?? 0);

        if (!empty($urdu_name)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE `packings` SET `urdu_name` = ?, `name` = ?, `default_weight_kg` = ? WHERE `id` = ?");
                $stmt->execute([$urdu_name, $name, $weight, $id]);
                flash_set("پیکنگ '{$urdu_name}' کامیابی سے اپ ڈیٹ ہو گئی۔", 'success');
            } else {
                $stmt = $pdo->prepare("INSERT INTO `packings` (`urdu_name`, `name`, `default_weight_kg`) VALUES (?, ?, ?)");
                $stmt->execute([$urdu_name, $name, $weight]);
                flash_set("نئی پیکنگ '{$urdu_name}' شامل کر دی گئی۔", 'success');
            }
        }
        header("Location: items.php");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

$items = $pdo->query("SELECT * FROM `items` ORDER BY `urdu_name` ASC")->fetchAll();
$packings = $pdo->query("SELECT * FROM `packings` ORDER BY `id` ASC")->fetchAll();
?>

<div class="row g-4">
    <!-- Commodities / Items (جنس کی لسٹ) -->
    <div class="col-12 col-lg-7">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-apple-whole text-danger me-2"></i> جنس کی لسٹ (Items / Fruits & Vegetables)</span>
            </div>
            <div class="p-3 bg-light border-bottom">
                <form method="POST" action="items.php" class="row g-2 align-items-end">
                    <input type="hidden" name="post_action" value="save_item">
                    <div class="col-5">
                        <label class="form-label small fw-bold">جنس کا اردو نام <span class="text-danger">*</span></label>
                        <input type="text" name="urdu_name" class="form-control urdu-font" placeholder="مثلاً: سیب کالا کلو، امرود" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold">انگلش نام</label>
                        <input type="text" name="name" class="form-control" placeholder="Apple, Guava">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-mandi-primary w-100">
                            <i class="fa-solid fa-plus"></i> شامل کریں
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اردو نام</th>
                            <th>انگلش نام</th>
                            <th>کیٹگری</th>
                            <th>حیثیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td class="text-center rubik-font"><?= $idx + 1 ?></td>
                                <td class="urdu-font fw-bold fs-5 text-dark"><?= htmlspecialchars($item['urdu_name']) ?></td>
                                <td><?= htmlspecialchars($item['name'] ?: '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $item['category'] === 'Fruit' ? 'success' : 'warning' ?>">
                                        <?= $item['category'] === 'Fruit' ? 'پھل (Fruit)' : 'سبزی (Veg)' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">فعال</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Packing Types (پیکنگ کی اقسام) -->
    <div class="col-12 col-lg-5">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-box text-warning me-2"></i> پیکنگ کی اقسام (Packaging Types)</span>
            </div>
            <div class="p-3 bg-light border-bottom">
                <form method="POST" action="items.php" class="row g-2 align-items-end">
                    <input type="hidden" name="post_action" value="save_packing">
                    <div class="col-6">
                        <label class="form-label small fw-bold">پیکنگ اردو نام <span class="text-danger">*</span></label>
                        <input type="text" name="urdu_name" class="form-control urdu-font" placeholder="مثلاً: کریٹ، پیٹی، کاٹن" required>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100">
                            <i class="fa-solid fa-plus"></i> نئی پیکنگ شامل کریں
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اردو نام</th>
                            <th>انگلش نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packings as $pk): ?>
                            <tr>
                                <td class="text-center rubik-font"><?= $pk['id'] ?></td>
                                <td class="urdu-font fw-bold fs-5 text-dark"><?= htmlspecialchars($pk['urdu_name']) ?></td>
                                <td><?= htmlspecialchars($pk['name'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
