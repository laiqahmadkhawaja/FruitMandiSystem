<?php
/**
 * Party / Account Management (کھاتہ جات: کسٹمر، زمیندار، بینک، خرچ)
 */
require_once __DIR__ . '/functions.php';

$action = $_GET['action'] ?? 'list';
$type_filter = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');
$edit_id = (int)($_GET['id'] ?? 0);

// Handle Form Submissions (Create / Update / Delete) BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_party') {
        $id = (int)($_POST['id'] ?? 0);
        $type = clean($_POST['type'] ?? 'customer');
        $code = clean($_POST['code'] ?? '');
        $name = clean($_POST['name'] ?? '');
        $urdu_name = clean($_POST['urdu_name'] ?? '');
        $marfat = clean($_POST['marfat'] ?? '');
        $cnic = clean($_POST['cnic'] ?? '');
        $mobile = clean($_POST['mobile'] ?? '');
        $city = clean($_POST['city'] ?? '');
        $address = clean($_POST['address'] ?? '');
        $recovery_officer = clean($_POST['recovery_officer'] ?? '');
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $is_blacklisted = isset($_POST['is_blacklisted']) ? 1 : 0;
        $opening_dr = (float)($_POST['opening_dr'] ?? 0);
        $opening_cr = (float)($_POST['opening_cr'] ?? 0);
        $opening_date = !empty($_POST['opening_balance_date']) ? $_POST['opening_balance_date'] : date('Y-m-d');

        if (empty($urdu_name)) {
            flash_set('براہ کرم کھاتہ کا اردو نام درج کریں۔', 'danger');
        } else {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE `parties` SET 
                    `type` = ?, `code` = ?, `name` = ?, `urdu_name` = ?, `marfat` = ?, 
                    `cnic` = ?, `mobile` = ?, `city` = ?, `address` = ?, `recovery_officer` = ?, 
                    `credit_limit` = ?, `is_blacklisted` = ?, `opening_dr` = ?, `opening_cr` = ?, 
                    `opening_balance_date` = ? WHERE `id` = ?");
                $stmt->execute([
                    $type, $code, $name, $urdu_name, $marfat,
                    $cnic, $mobile, $city, $address, $recovery_officer,
                    $credit_limit, $is_blacklisted, $opening_dr, $opening_cr,
                    $opening_date, $id
                ]);
                flash_set("کھاتہ '{$urdu_name}' کامیابی سے اپ ڈیٹ ہو گیا۔", 'success');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO `parties` 
                    (`type`, `code`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_dr`, `opening_cr`, `opening_balance_date`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $type, $code, $name, $urdu_name, $marfat,
                    $cnic, $mobile, $city, $address, $recovery_officer,
                    $credit_limit, $is_blacklisted, $opening_dr, $opening_cr,
                    $opening_date
                ]);
                flash_set("نیا کھاتہ '{$urdu_name}' کامیابی سے محفوظ ہو گیا۔", 'success');
            }
            header("Location: parties.php?type=" . urlencode($type));
            exit;
        }
    } elseif ($post_action === 'delete_party') {
        $del_id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM `parties` WHERE `id` = ?");
        $stmt->execute([$del_id]);
        flash_set("کھاتہ کامیابی سے حذف کر دیا گیا۔", 'warning');
        header("Location: parties.php");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch party for editing if requested
$edit_party = null;
if ($action === 'edit' && $edit_id > 0) {
    $edit_party = get_party($edit_id);
}

// Build Query for listing
$query = "SELECT * FROM `parties` WHERE 1=1";
$params = [];
if (!empty($type_filter)) {
    $query .= " AND `type` = ?";
    $params[] = $type_filter;
}
if (!empty($search)) {
    $query .= " AND (`urdu_name` LIKE ? OR `name` LIKE ? OR `mobile` LIKE ? OR `city` LIKE ? OR `code` LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
$query .= " ORDER BY `urdu_name` ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$parties = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Left Column: Add / Edit Form -->
    <div class="col-12 col-xl-4">
        <div class="card-custom sticky-top" style="top: 80px;">
            <div class="card-custom-header">
                <span>
                    <i class="fa-solid fa-<?= $edit_party ? 'pen-to-square text-warning' : 'user-plus text-success' ?> me-2"></i>
                    <?= $edit_party ? 'کھاتہ کی تبدیلی (ترمیم)' : 'نیا کھاتہ بنائیں (رجسٹریشن)' ?>
                </span>
                <?php if ($edit_party): ?>
                    <a href="parties.php" class="btn btn-sm btn-outline-secondary">نیا بنائیں</a>
                <?php endif; ?>
            </div>
            <div class="p-3">
                <form method="POST" action="parties.php">
                    <input type="hidden" name="post_action" value="save_party">
                    <input type="hidden" name="id" value="<?= $edit_party['id'] ?? 0 ?>">

                    <!-- Account Type -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">کھاتے کی قسم (Account Type) <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-lg fw-bold" required>
                            <option value="customer" <?= ($edit_party['type'] ?? $type_filter) === 'customer' ? 'selected' : '' ?>>گاہک / دکاندار (Customers / Shops)</option>
                            <option value="farmer" <?= ($edit_party['type'] ?? $type_filter) === 'farmer' ? 'selected' : '' ?>>زمیندار / کاشتکار (Farmers / Growers)</option>
                            <option value="bank" <?= ($edit_party['type'] ?? $type_filter) === 'bank' ? 'selected' : '' ?>>بینک کھاتہ (Bank Account)</option>
                            <option value="expense" <?= ($edit_party['type'] ?? $type_filter) === 'expense' ? 'selected' : '' ?>>دکان خرچ کھاتہ (Expense Account)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label fw-bold">اردو نام <span class="text-danger">*</span></label>
                            <input type="text" name="urdu_name" class="form-control urdu-font fs-5" 
                                value="<?= htmlspecialchars($edit_party['urdu_name'] ?? '') ?>" required placeholder="مثلاً: آصف پہلوان / جمعہ گل">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold">کوڈ #</label>
                            <input type="text" name="code" class="form-control rubik-font" 
                                value="<?= htmlspecialchars($edit_party['code'] ?? '') ?>" placeholder="01">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">انگلش نام</label>
                            <input type="text" name="name" class="form-control" 
                                value="<?= htmlspecialchars($edit_party['name'] ?? '') ?>" placeholder="Asif Pehlwan">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">معرفت (Care of)</label>
                            <input type="text" name="marfat" class="form-control" 
                                value="<?= htmlspecialchars($edit_party['marfat'] ?? '') ?>" placeholder="حوالہ / دکان">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">موبائل فون (واٹس ایپ)</label>
                            <input type="text" name="mobile" class="form-control rubik-font" 
                                value="<?= htmlspecialchars($edit_party['mobile'] ?? '') ?>" placeholder="03001234567">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">شہر / علاقہ</label>
                            <input type="text" name="city" class="form-control" 
                                value="<?= htmlspecialchars($edit_party['city'] ?? '') ?>" placeholder="سیالکوٹ / ڈسکہ">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">شناختی کارڈ #</label>
                            <input type="text" name="cnic" class="form-control rubik-font" 
                                value="<?= htmlspecialchars($edit_party['cnic'] ?? '') ?>" placeholder="34601-...">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">ریکوری آفیسر</label>
                            <input type="text" name="recovery_officer" class="form-control" 
                                value="<?= htmlspecialchars($edit_party['recovery_officer'] ?? '') ?>" placeholder="وصولی کار">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold">پتہ (Address)</label>
                        <input type="text" name="address" class="form-control" 
                            value="<?= htmlspecialchars($edit_party['address'] ?? '') ?>" placeholder="دکان نمبر، مارکیٹ...">
                    </div>

                    <!-- Opening Balance Group -->
                    <div class="border rounded p-2 bg-light mb-3">
                        <div class="small fw-bold text-muted mb-2">سابقہ بقایا جات (Opening Balance)</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-danger">سابقہ نام (Debit / ہم نے لینا ہے)</label>
                                <input type="number" step="0.01" name="opening_dr" class="form-control rubik-font text-danger fw-bold" 
                                    value="<?= $edit_party['opening_dr'] ?? 0 ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-success">سابقہ جمع (Credit / ہم نے دینا ہے)</label>
                                <input type="number" step="0.01" name="opening_cr" class="form-control rubik-font text-success fw-bold" 
                                    value="<?= $edit_party['opening_cr'] ?? 0 ?>">
                            </div>
                            <div class="col-12 mt-1">
                                <label class="form-label small">بقایا کی تاریخ</label>
                                <input type="date" name="opening_balance_date" class="form-control form-control-sm" 
                                    value="<?= $edit_party['opening_balance_date'] ?? date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Blacklist & Credit Limit -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ادھار کی حد (Credit Limit)</label>
                            <input type="number" step="0.01" name="credit_limit" class="form-control rubik-font" 
                                value="<?= $edit_party['credit_limit'] ?? 0 ?>">
                        </div>
                        <div class="col-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_blacklisted" id="blacklistedCheck" 
                                    <?= !empty($edit_party['is_blacklisted']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-danger" for="blacklistedCheck">بلیک لسٹ کسٹمر</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-mandi-primary justify-content-center py-2">
                            <i class="fa-solid fa-floppy-disk"></i> <?= $edit_party ? 'تبدیلی محفوظ کریں' : 'کھاتہ محفوظ کریں (Save)' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Party List Table & Filter -->
    <div class="col-12 col-xl-8">
        <div class="card-custom">
            <div class="card-custom-header flex-wrap gap-2">
                <span><i class="fa-solid fa-users text-primary me-2"></i> رجسٹرڈ کھاتے کی فہرست (Accounts List)</span>
                
                <!-- Quick Filter Tabs -->
                <div class="btn-group btn-group-sm">
                    <a href="parties.php" class="btn btn-<?= empty($type_filter) ? 'primary' : 'outline-primary' ?>">تمام</a>
                    <a href="parties.php?type=customer" class="btn btn-<?= $type_filter === 'customer' ? 'primary' : 'outline-primary' ?>">گاہک</a>
                    <a href="parties.php?type=farmer" class="btn btn-<?= $type_filter === 'farmer' ? 'primary' : 'outline-primary' ?>">زمیندار</a>
                    <a href="parties.php?type=bank" class="btn btn-<?= $type_filter === 'bank' ? 'primary' : 'outline-primary' ?>">بینک</a>
                    <a href="parties.php?type=expense" class="btn btn-<?= $type_filter === 'expense' ? 'primary' : 'outline-primary' ?>">اخراجات</a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="p-3 bg-light border-bottom">
                <form method="GET" action="parties.php" class="row g-2">
                    <?php if ($type_filter): ?>
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type_filter) ?>">
                    <?php endif; ?>
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control fs-6" 
                                placeholder="نام، موبائل، شہر یا کوڈ سے تلاش کریں..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">تلاش کریں</button>
                        <?php if ($search || $type_filter): ?>
                            <a href="parties.php" class="btn btn-outline-secondary">ری سیٹ</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>کوڈ</th>
                            <th>اردو نام</th>
                            <th>قسم</th>
                            <th>شہر</th>
                            <th>موبائل</th>
                            <th>موجودہ بیلنس</th>
                            <th>کیفیت</th>
                            <th>ایکشن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parties)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">کوئی کھاتہ نہیں ملا</td></tr>
                        <?php else: foreach ($parties as $p): 
                            $bal = get_party_balance($p['id']);
                            $type_urdu = [
                                'customer' => 'گاہک',
                                'farmer' => 'زمیندار',
                                'bank' => 'بینک',
                                'expense' => 'خرچ'
                            ][$p['type']] ?? $p['type'];
                        ?>
                            <tr class="<?= !empty($p['is_blacklisted']) ? 'table-danger' : '' ?>">
                                <td class="text-center rubik-font"><?= htmlspecialchars($p['code'] ?: $p['id']) ?></td>
                                <td class="urdu-font fw-bold fs-5">
                                    <a href="ledgers.php?party_id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($p['urdu_name']) ?>
                                    </a>
                                    <?php if (!empty($p['marfat'])): ?>
                                        <small class="text-muted d-block fs-6">معرفت: <?= htmlspecialchars($p['marfat']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= $type_urdu ?></span></td>
                                <td><?= htmlspecialchars($p['city'] ?: '-') ?></td>
                                <td class="rubik-font"><?= htmlspecialchars($p['mobile'] ?: '-') ?></td>
                                <td class="text-end rubik-font fw-bold fs-6">
                                    Rs. <?= format_rs($bal['balance']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($bal['status'] === 'نام'): ?>
                                        <span class="badge-dr">نام (وصول طلب)</span>
                                    <?php elseif ($bal['status'] === 'جمع'): ?>
                                        <span class="badge-cr">جمع (ادائیگی طلب)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">صاف / 0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="ledgers.php?party_id=<?= $p['id'] ?>" class="btn btn-outline-info" title="کھاتہ دیکھیں">
                                            <i class="fa-solid fa-book-open"></i>
                                        </a>
                                        <a href="parties.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline-warning" title="ترمیم">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php if (!empty($p['mobile'])): 
                                            $wa_msg = "محترم " . $p['urdu_name'] . "، آپ کا کل بیلنس: Rs. " . format_rs($bal['balance']) . " (" . $bal['status'] . ") ہے۔ برائے مہربانی اپنا کھاتہ چیک کریں۔ رابطہ: " . $settings['shop_name'];
                                        ?>
                                            <a href="<?= whatsapp_url($p['mobile'], $wa_msg) ?>" target="_blank" class="btn btn-outline-success" title="واٹس ایپ بیلنس">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 bg-light border-top text-muted small text-end px-3">
                کل کھاتے: <?= count($parties) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
