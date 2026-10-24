<?php
/**
 * Vouchers (آمد خرچ واؤچر / وصولی و ادائیگی واؤچرز)
 */
require_once __DIR__ . '/functions.php';

// Generate Next Voucher No
$next_v = (int)$pdo->query("SELECT MAX(id) FROM `vouchers`")->fetchColumn() + 1;
$next_v_no = 'VC-' . str_pad($next_v, 4, '0', STR_PAD_LEFT);

// Handle Form Submission BEFORE sending HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_voucher') {
        $v_no = clean($_POST['voucher_no'] ?? $next_v_no);
        $v_date = clean($_POST['voucher_date'] ?? date('Y-m-d'));
        $v_type = clean($_POST['type'] ?? 'receipt'); // receipt, payment, daily_expense
        $party_id = (int)($_POST['party_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method = clean($_POST['payment_method'] ?? 'cash');
        $narration = clean($_POST['narration'] ?? '');

        if ($amount <= 0 || ($v_type !== 'daily_expense' && $party_id <= 0)) {
            flash_set('براہ کرم پارٹی اور رقم درست درج کریں۔', 'danger');
        } else {
            $stmt = $pdo->prepare("INSERT INTO `vouchers` 
                (`voucher_no`, `voucher_date`, `type`, `party_id`, `amount`, `payment_method`, `narration`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$v_no, $v_date, $v_type, $party_id ?: null, $amount, $payment_method, $narration]);

            flash_set("واؤچر #{$v_no} برائے رقم Rs. " . format_rs($amount) . " کامیابی سے محفوظ ہو گیا۔", 'success');
            header("Location: vouchers.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';

// Fetch Parties for Dropdowns
$all_parties = $pdo->query("SELECT id, urdu_name, type, city FROM `parties` ORDER BY `type`, `urdu_name` ASC")->fetchAll();

// List of Recent Vouchers
$v_filter_type = clean($_GET['type'] ?? '');
$v_filter_date = clean($_GET['date'] ?? date('Y-m-d'));

$sql = "SELECT v.*, p.urdu_name as party_name, p.type as party_type 
    FROM `vouchers` v 
    LEFT JOIN `parties` p ON v.party_id = p.id 
    WHERE 1=1";
$params = [];
if (!empty($v_filter_type)) {
    $sql .= " AND v.type = ?";
    $params[] = $v_filter_type;
}
if (!empty($v_filter_date)) {
    $sql .= " AND v.voucher_date = ?";
    $params[] = $v_filter_date;
}
$sql .= " ORDER BY v.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vouchers = $stmt->fetchAll();
?>

<div class="row g-4">
    <!-- Voucher Entry Form -->
    <div class="col-12 col-xl-4">
        <div class="card-custom sticky-top" style="top: 80px;">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-money-bill-transfer text-success me-2"></i> نیا واؤچر درج کریں (New Voucher)</span>
            </div>
            <div class="p-3">
                <form method="POST" action="vouchers.php">
                    <input type="hidden" name="post_action" value="save_voucher">

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">واؤچر نمبر</label>
                            <input type="text" name="voucher_no" class="form-control rubik-font fw-bold" value="<?= $next_v_no ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">تاریخ</label>
                            <input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Voucher Type -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">واؤچر کی قسم (Transaction Type) <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-lg fw-bold" required id="voucherType">
                            <option value="receipt" class="text-success">وصولی واؤچر (Cash Received - جمع)</option>
                            <option value="payment" class="text-danger">ادائیگی واؤچر (Payment Paid - نام)</option>
                            <option value="daily_expense" class="text-warning">دکان روزانہ خرچ (Shop Expense)</option>
                        </select>
                    </div>

                    <!-- Party Selection -->
                    <div class="mb-3" id="partySelectDiv">
                        <label class="form-label fw-bold">کھاتہ دار / پارٹی منتخب کریں <span class="text-danger">*</span></label>
                        <select name="party_id" class="form-select urdu-font fs-5" id="partySelect">
                            <option value="">-- پارٹی منتخب کریں --</option>
                            <?php foreach ($all_parties as $pr): 
                                $t_badge = [
                                    'customer' => 'گاہک',
                                    'farmer' => 'زمیندار',
                                    'bank' => 'بینک',
                                    'expense' => 'خرچ'
                                ][$pr['type']] ?? '';
                            ?>
                                <option value="<?= $pr['id'] ?>">
                                    [<?= $t_badge ?>] <?= htmlspecialchars($pr['urdu_name']) ?> (<?= htmlspecialchars($pr['city'] ?: '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-success fs-5">رقم (روپے) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control rubik-font fs-3 fw-bold text-success" placeholder="5000" required>
                    </div>

                    <!-- Payment Mode -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">طریقہ ادائیگی</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">کیش (نقد روکڑ)</option>
                            <option value="bank">بینک ٹرانسفر / آن لائن</option>
                            <option value="cheque">چیک</option>
                        </select>
                    </div>

                    <!-- Narration -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">تفصیل / ریمارکس (Narration)</label>
                        <textarea name="narration" class="form-control urdu-font" rows="2" placeholder="وصولی بابت پرانا بقایا، یا خرچ تفصیل..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-mandi-primary justify-content-center py-2 fs-5">
                            <i class="fa-solid fa-floppy-disk"></i> واؤچر محفوظ کریں (Save)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vouchers List Table -->
    <div class="col-12 col-xl-8">
        <div class="card-custom">
            <div class="card-custom-header flex-wrap gap-2">
                <span><i class="fa-solid fa-list-check text-primary me-2"></i> واؤچرز کی فہرست (Recent Vouchers)</span>
                
                <form method="GET" action="vouchers.php" class="d-flex align-items-center gap-2">
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($v_filter_date) ?>" onchange="this.form.submit()">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">تمام اقسام</option>
                        <option value="receipt" <?= $v_filter_type === 'receipt' ? 'selected' : '' ?>>وصولیاں</option>
                        <option value="payment" <?= $v_filter_type === 'payment' ? 'selected' : '' ?>>ادائیگیاں</option>
                        <option value="daily_expense" <?= $v_filter_type === 'daily_expense' ? 'selected' : '' ?>>اخراجات</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-mandi table-hover align-middle">
                    <thead>
                        <tr>
                            <th>واؤچر #</th>
                            <th>تاریخ</th>
                            <th>قسم</th>
                            <th>پارٹی / کھاتہ</th>
                            <th>تفصیل</th>
                            <th>طریقہ</th>
                            <th>رقم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vouchers)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">کوئی واؤچر ریکارڈ نہیں ملا</td></tr>
                        <?php else: foreach ($vouchers as $v): ?>
                            <tr>
                                <td class="rubik-font fw-bold text-center"><?= $v['voucher_no'] ?></td>
                                <td class="text-center small"><?= format_dt($v['voucher_date']) ?></td>
                                <td class="text-center">
                                    <?php if ($v['type'] === 'receipt'): ?>
                                        <span class="badge bg-success">وصولی (Cash In)</span>
                                    <?php elseif ($v['type'] === 'payment'): ?>
                                        <span class="badge bg-danger">ادائیگی (Cash Out)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">دکان خرچ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="urdu-font fw-bold fs-6">
                                    <?= htmlspecialchars($v['party_name'] ?: 'دکان جنرل خرچ') ?>
                                </td>
                                <td><?= htmlspecialchars($v['narration']) ?></td>
                                <td class="text-center small"><span class="badge bg-light text-dark"><?= $v['payment_method'] ?></span></td>
                                <td class="text-end rubik-font fw-bold fs-6 <?= $v['type'] === 'receipt' ? 'text-success' : 'text-danger' ?>">
                                    Rs. <?= format_rs($v['amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const vType = document.getElementById('voucherType');
const pDiv = document.getElementById('partySelectDiv');
vType.addEventListener('change', function() {
    if (this.value === 'daily_expense') {
        pDiv.style.opacity = '0.7';
    } else {
        pDiv.style.opacity = '1';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
