<?php
/**
 * Detailed Party Ledger (تفصیلی کھاتہ)
 * Matching Video Frame 036
 */
require_once __DIR__ . '/includes/header.php';

$party_id = (int)($_GET['party_id'] ?? 0);
$from_date = clean($_GET['from_date'] ?? date('Y-01-01'));
$to_date = clean($_GET['to_date'] ?? date('Y-m-d'));

$parties = $pdo->query("SELECT id, urdu_name, code, type, city, mobile FROM `parties` ORDER BY `type`, `urdu_name` ASC")->fetchAll();

$selected_party = null;
$ledger_lines = [];
$opening_balance = 0;
$opening_status = 'برابر';

if ($party_id > 0) {
    $selected_party = get_party($party_id);

    if ($selected_party) {
        // Calculate Opening Balance before $from_date
        $pre_date = date('Y-m-d', strtotime($from_date . ' -1 day'));
        $pre_calc = get_party_balance($party_id, $pre_date);
        $opening_dr = $pre_calc['total_dr'];
        $opening_cr = $pre_calc['total_cr'];
        $diff = $opening_dr - $opening_cr;
        $running_balance = $diff;

        // Fetch transactions between $from_date and $to_date
        // 1. Sales Bills (If customer)
        if ($selected_party['type'] === 'customer') {
            $s_stmt = $pdo->prepare("SELECT sale_date as date, bill_no as ref, CONCAT('فروخت بل: ', nag, ' نگ ', i.urdu_name, ' مارکہ ', marke) as details, net_amount as dr, 0 as cr 
                FROM `sales_bills` s 
                JOIN `items` i ON s.item_id = i.id 
                WHERE s.customer_id = ? AND s.sale_date BETWEEN ? AND ?");
            $s_stmt->execute([$party_id, $from_date, $to_date]);
            $ledger_lines = array_merge($ledger_lines, $s_stmt->fetchAll());

            // Bazar wasooli
            $bw_stmt = $pdo->prepare("SELECT date, 'بازار وصولی' as ref, 'بازار کھاتہ وصولی (صبح و شام)' as details, 0 as dr, (morning_amount + evening_amount) as cr 
                FROM `bazar_wasooli` 
                WHERE customer_id = ? AND (morning_amount > 0 OR evening_amount > 0) AND date BETWEEN ? AND ?");
            $bw_stmt->execute([$party_id, $from_date, $to_date]);
            $ledger_lines = array_merge($ledger_lines, $bw_stmt->fetchAll());
        }

        // 2. Farmer Bills & Freight (If farmer)
        if ($selected_party['type'] === 'farmer') {
            $zb_stmt = $pdo->prepare("SELECT bill_date as date, bill_no as ref, CONCAT('فرد حساب بل (چالان ', m.challan_no, ' مارکہ ', m.marke, ')') as details, 0 as dr, net_payable as cr 
                FROM `zamindar_bills` zb 
                JOIN `maal_amad` m ON zb.amad_id = m.id 
                WHERE zb.farmer_id = ? AND zb.bill_date BETWEEN ? AND ?");
            $zb_stmt->execute([$party_id, $from_date, $to_date]);
            $ledger_lines = array_merge($ledger_lines, $zb_stmt->fetchAll());

            // Freight paid on arrival
            $f_stmt = $pdo->prepare("SELECT amad_date as date, challan_no as ref, CONCAT('گاڑی کرایہ ادائیگی لاٹ ', marke) as details, (freight_paid_by_agent + local_cartage) as dr, 0 as cr 
                FROM `maal_amad` 
                WHERE farmer_id = ? AND (freight_paid_by_agent > 0 OR local_cartage > 0) AND amad_date BETWEEN ? AND ?");
            $f_stmt->execute([$party_id, $from_date, $to_date]);
            $ledger_lines = array_merge($ledger_lines, $f_stmt->fetchAll());
        }

        // 3. Vouchers
        $v_stmt = $pdo->prepare("SELECT voucher_date as date, voucher_no as ref, narration as details, 
            CASE WHEN type = 'payment' THEN amount ELSE 0 END as dr,
            CASE WHEN type = 'receipt' THEN amount ELSE 0 END as cr 
            FROM `vouchers` 
            WHERE party_id = ? AND voucher_date BETWEEN ? AND ?");
        $v_stmt->execute([$party_id, $from_date, $to_date]);
        $ledger_lines = array_merge($ledger_lines, $v_stmt->fetchAll());

        // Sort all lines by date ascending
        usort($ledger_lines, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
    }
}
?>

<div class="card-custom">
    <!-- Filter Bar -->
    <div class="card-custom-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-book-open text-primary fs-4"></i>
            <h4 class="urdu-font fw-bold m-0">تفصیلی کھاتہ (Party Ledger Statement)</h4>
        </div>

        <form method="GET" action="ledgers.php" class="d-flex align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">کھاتہ دار:</label>
                <select name="party_id" class="form-select form-select-sm urdu-font" style="min-width: 200px;" onchange="this.form.submit()">
                    <option value="">-- کھاتہ منتخب کریں --</option>
                    <?php foreach ($parties as $p): 
                        $type_label = [
                            'customer' => 'گاہک',
                            'farmer' => 'زمیندار',
                            'bank' => 'بینک',
                            'expense' => 'خرچ'
                        ][$p['type']] ?? '';
                    ?>
                        <option value="<?= $p['id'] ?>" <?= $party_id === $p['id'] ? 'selected' : '' ?>>
                            [<?= $type_label ?>] <?= htmlspecialchars($p['urdu_name']) ?> (<?= htmlspecialchars($p['city'] ?: '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">از تاریخ:</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date) ?>">
            </div>

            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted fw-bold">تا تاریخ:</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date) ?>">
            </div>

            <button type="submit" class="btn btn-sm btn-primary">دیکھیں</button>

            <?php if ($selected_party): ?>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-print"></i> پرنٹ کھاتہ
                </button>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selected_party): 
        $current_bal = get_party_balance($party_id);
    ?>
        <!-- Party Meta Card -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="urdu-font fw-bold m-0 text-dark"><?= htmlspecialchars($selected_party['urdu_name']) ?></h4>
                <div class="small text-muted">
                    شہر: <?= htmlspecialchars($selected_party['city'] ?: '-') ?> | 
                    فون: <?= htmlspecialchars($selected_party['mobile'] ?: '-') ?> | 
                    معرفت: <?= htmlspecialchars($selected_party['marfat'] ?: '-') ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <span class="small text-muted">کل واجب الادا بیلنس:</span>
                    <h3 class="rubik-font fw-bold m-0 <?= $current_bal['status'] === 'نام' ? 'text-danger' : 'text-success' ?>">
                        Rs. <?= format_rs($current_bal['balance']) ?> 
                        <span class="fs-6 urdu-font">(<?= $current_bal['status'] ?>)</span>
                    </h3>
                </div>

                <?php if (!empty($selected_party['mobile'])): 
                    $wa_ledger = "محترم " . $selected_party['urdu_name'] . "، آپ کا تفصیلی کھاتہ بنام " . $settings['shop_name'] . "۔ کل بیلنس: Rs. " . format_rs($current_bal['balance']) . " (" . $current_bal['status'] . ")۔";
                ?>
                    <a href="<?= whatsapp_url($selected_party['mobile'], $wa_ledger) ?>" target="_blank" class="btn btn-success no-print">
                        <i class="fa-brands fa-whatsapp me-1"></i> واٹس ایپ پر کھاتہ بھیجیں
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ledger Table (Matching Video Frame 036) -->
        <div class="table-responsive p-3">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 110px;">تاریخ</th>
                        <th style="width: 140px;">بل / واؤچر #</th>
                        <th>تفصیل اندراج (Narration)</th>
                        <th style="width: 150px;" class="bg-danger-subtle text-danger">نام (Debit)</th>
                        <th style="width: 150px;" class="bg-success-subtle text-success">جمع (Credit)</th>
                        <th style="width: 160px;">بقایا بیلنس</th>
                        <th style="width: 100px;">کیفیت</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="table-light fw-bold">
                        <td class="text-center">-</td>
                        <td class="text-center"><?= format_dt($from_date) ?></td>
                        <td class="text-center">-</td>
                        <td class="urdu-font">سابقہ اوپننگ بیلنس (Opening Balance)</td>
                        <td class="text-end rubik-font text-danger"><?= $running_balance > 0 ? 'Rs. ' . format_rs($running_balance) : '-' ?></td>
                        <td class="text-end rubik-font text-success"><?= $running_balance < 0 ? 'Rs. ' . format_rs(abs($running_balance)) : '-' ?></td>
                        <td class="text-end rubik-font fw-bold">Rs. <?= format_rs(abs($running_balance)) ?></td>
                        <td class="text-center">
                            <?php if ($running_balance > 0): ?>
                                <span class="badge-dr">نام</span>
                            <?php elseif ($running_balance < 0): ?>
                                <span class="badge-cr">جمع</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php 
                    $tot_dr = $running_balance > 0 ? $running_balance : 0;
                    $tot_cr = $running_balance < 0 ? abs($running_balance) : 0;
                    $c = 1;

                    foreach ($ledger_lines as $row): 
                        $dr = (float)$row['dr'];
                        $cr = (float)$row['cr'];
                        $tot_dr += $dr;
                        $tot_cr += $cr;
                        $running_balance += ($dr - $cr);

                        $line_status = $running_balance > 0 ? 'نام' : ($running_balance < 0 ? 'جمع' : 'برابر');
                    ?>
                        <tr>
                            <td class="text-center rubik-font small"><?= $c++ ?></td>
                            <td class="text-center rubik-font small"><?= format_dt($row['date']) ?></td>
                            <td class="text-center rubik-font small"><span class="badge bg-secondary"><?= htmlspecialchars($row['ref']) ?></span></td>
                            <td class="urdu-font fs-6"><?= htmlspecialchars($row['details']) ?></td>
                            <td class="text-end rubik-font fw-bold text-danger"><?= $dr > 0 ? 'Rs. ' . format_rs($dr) : '-' ?></td>
                            <td class="text-end rubik-font fw-bold text-success"><?= $cr > 0 ? 'Rs. ' . format_rs($cr) : '-' ?></td>
                            <td class="text-end rubik-font fw-bold fs-6">Rs. <?= format_rs(abs($running_balance)) ?></td>
                            <td class="text-center">
                                <?php if ($line_status === 'نام'): ?>
                                    <span class="badge-dr">نام</span>
                                <?php elseif ($line_status === 'جمع'): ?>
                                    <span class="badge-cr">جمع</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">0</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold fs-6">
                    <tr>
                        <td colspan="4" class="text-end urdu-font">کل میزان (Total):</td>
                        <td class="text-end rubik-font text-danger">Rs. <?= format_rs($tot_dr) ?></td>
                        <td class="text-end rubik-font text-success">Rs. <?= format_rs($tot_cr) ?></td>
                        <td class="text-end rubik-font fs-5 text-primary">Rs. <?= format_rs(abs($running_balance)) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?= $running_balance > 0 ? 'نام' : ($running_balance < 0 ? 'جمع' : '0') ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-book-open fa-3x mb-3 text-secondary"></i>
            <h5 class="urdu-font">براہ کرم اوپر سے کسی بھی پارٹی یا کھاتہ دار کو منتخب کریں</h5>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
