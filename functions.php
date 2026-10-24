<?php
/**
 * Global Helper Functions for Mandi Accounting System
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize User Input
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Currency / Rupees with Pakistani style formatting
 */
function format_rs($amount, $show_decimals = false) {
    $num = (float)$amount;
    if ($show_decimals) {
        return number_format($num, 2);
    }
    return number_format($num, 0);
}

/**
 * Format Date to standard Pakistani display
 */
function format_dt($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') return '-';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Set Session Flash Message
 */
function flash_set($message, $type = 'success') {
    $_SESSION['flash_msg'] = [
        'text' => $message,
        'type' => $type // success, danger, warning, info
    ];
}

/**
 * Get and Clear Session Flash Message
 */
function flash_get() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return null;
}

/**
 * Get System Settings
 */
function get_settings() {
    global $pdo;
    static $settings = null;
    if ($settings === null) {
        $stmt = $pdo->query("SELECT * FROM `settings` LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            $settings = [
                'shop_name' => APP_NAME,
                'shop_subheading' => APP_SUBTITLE,
                'mandi_name' => 'نیو سبزی و پھل منڈی',
                'city' => 'سیالکوٹ',
                'phone' => '0300-824-9115',
                'default_commission_rate' => 6.00,
                'default_mazdoori_per_nag' => 10.00,
                'default_market_fee_percent' => 1.00,
                'default_munshiana_per_bill' => 20.00
            ];
        }
    }
    return $settings;
}

/**
 * Get Party by ID
 */
function get_party($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM `parties` WHERE `id` = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/**
 * Calculate Party Current Ledger Balance
 * Debit (نام) = Party owes us money (for Customers) / or we paid them
 * Credit (جمع) = We owe Party money (for Farmers) / or they deposited with us
 */
function get_party_balance($party_id, $up_to_date = null) {
    global $pdo;
    $party = get_party($party_id);
    if (!$party) return ['balance' => 0, 'status' => 'برابر', 'dr' => 0, 'cr' => 0];

    $total_dr = (float)$party['opening_dr'];
    $total_cr = (float)$party['opening_cr'];

    $date_sql = $up_to_date ? " AND voucher_date <= :up_to_date " : "";
    $params = ['party_id' => $party_id];
    if ($up_to_date) $params['up_to_date'] = $up_to_date;

    // 1. If Customer: Sales bills add to Debit (نام)
    if ($party['type'] === 'customer') {
        $sale_sql = "SELECT COALESCE(SUM(net_amount), 0) FROM `sales_bills` WHERE customer_id = :party_id " . 
                    ($up_to_date ? " AND sale_date <= :up_to_date " : "");
        $stmt = $pdo->prepare($sale_sql);
        $stmt->execute($params);
        $total_dr += (float)$stmt->fetchColumn();

        // Morning & evening collections from bazar_wasooli
        $wasooli_sql = "SELECT COALESCE(SUM(morning_amount + evening_amount), 0) FROM `bazar_wasooli` WHERE customer_id = :party_id " .
                       ($up_to_date ? " AND date <= :up_to_date " : "");
        $stmt = $pdo->prepare($wasooli_sql);
        $stmt->execute($params);
        $total_cr += (float)$stmt->fetchColumn();
    }

    // 2. If Farmer: Zamindar net payable bills add to Credit (جمع)
    if ($party['type'] === 'farmer') {
        $bill_sql = "SELECT COALESCE(SUM(net_payable), 0) FROM `zamindar_bills` WHERE farmer_id = :party_id " .
                    ($up_to_date ? " AND bill_date <= :up_to_date " : "");
        $stmt = $pdo->prepare($bill_sql);
        $stmt->execute($params);
        $total_cr += (float)$stmt->fetchColumn();

        // Freight paid by commission agent on arrival adds to Debit (نام)
        $freight_sql = "SELECT COALESCE(SUM(freight_paid_by_agent + local_cartage), 0) FROM `maal_amad` WHERE farmer_id = :party_id " .
                       ($up_to_date ? " AND amad_date <= :up_to_date " : "");
        $stmt = $pdo->prepare($freight_sql);
        $stmt->execute($params);
        $total_dr += (float)$stmt->fetchColumn();
    }

    // 3. Vouchers: Receipts (جمع) and Payments (نام)
    // Direct party vouchers
    $v_sql = "SELECT type, COALESCE(SUM(amount), 0) as total FROM `vouchers` 
              WHERE party_id = :party_id " . $date_sql . " GROUP BY type";
    $stmt = $pdo->prepare($v_sql);
    $stmt->execute($params);
    $vouchers = $stmt->fetchAll();
    foreach ($vouchers as $v) {
        if ($v['type'] === 'receipt') {
            $total_cr += (float)$v['total'];
        } elseif ($v['type'] === 'payment') {
            $total_dr += (float)$v['total'];
        }
    }

    // Journal vouchers (debit_party_id and credit_party_id)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE debit_party_id = :party_id " . $date_sql);
    $stmt->execute($params);
    $total_dr += (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE credit_party_id = :party_id " . $date_sql);
    $stmt->execute($params);
    $total_cr += (float)$stmt->fetchColumn();

    $diff = $total_dr - $total_cr;
    if ($diff > 0) {
        $status = 'نام'; // Dr (Party owes us)
        $balance = $diff;
    } elseif ($diff < 0) {
        $status = 'جمع'; // Cr (We owe party)
        $balance = abs($diff);
    } else {
        $status = 'برابر';
        $balance = 0;
    }

    return [
        'total_dr' => $total_dr,
        'total_cr' => $total_cr,
        'balance' => $balance,
        'status' => $status
    ];
}

/**
 * Calculate Cash-in-Hand (روکڑ کیش بیلنس) for a specific date or overall
 */
function get_cash_in_hand($date = null) {
    global $pdo;
    $v_date_sql = $date ? " AND voucher_date <= " . $pdo->quote($date) : "";
    $s_date_sql = $date ? " AND sale_date <= " . $pdo->quote($date) : "";
    $a_date_sql = $date ? " AND amad_date <= " . $pdo->quote($date) : "";
    $w_date_sql = $date ? " AND date <= " . $pdo->quote($date) : "";

    // Cash Inflows:
    // 1. Receipts in cash
    $r_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE type = 'receipt' AND payment_method = 'cash' $v_date_sql");
    $receipts = (float)$r_stmt->fetchColumn();

    // 2. Cash sales
    $cs_stmt = $pdo->query("SELECT COALESCE(SUM(net_amount), 0) FROM `sales_bills` WHERE is_paid = 1 $s_date_sql");
    $cash_sales = (float)$cs_stmt->fetchColumn();

    // 3. Bazar Wasooli (morning + evening)
    $bw_stmt = $pdo->query("SELECT COALESCE(SUM(morning_amount + evening_amount), 0) FROM `bazar_wasooli` WHERE 1=1 $w_date_sql");
    $bazar_wasooli = (float)$bw_stmt->fetchColumn();

    $total_in = $receipts + $cash_sales + $bazar_wasooli;

    // Cash Outflows:
    // 1. Payments in cash
    $p_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE type = 'payment' AND payment_method = 'cash' $v_date_sql");
    $payments = (float)$p_stmt->fetchColumn();

    // 2. Daily expenses in cash
    $e_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `vouchers` WHERE type = 'daily_expense' AND payment_method = 'cash' $v_date_sql");
    $expenses = (float)$e_stmt->fetchColumn();

    // 3. Freight paid in cash by agent on arrivals
    $f_stmt = $pdo->query("SELECT COALESCE(SUM(freight_paid_by_agent + local_cartage), 0) FROM `maal_amad` WHERE 1=1 $a_date_sql");
    $freight_paid = (float)$f_stmt->fetchColumn();

    $total_out = $payments + $expenses + $freight_paid;

    return [
        'total_in' => $total_in,
        'total_out' => $total_out,
        'cash_in_hand' => $total_in - $total_out
    ];
}

/**
 * Generate Direct WhatsApp Link for Bills & Ledgers
 */
function whatsapp_url($phone, $message) {
    // Sanitize phone number (e.g. 0300-1234567 -> 923001234567)
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strpos($phone, '0') === 0) {
        $phone = '92' . substr($phone, 1);
    }
    return "https://api.whatsapp.com/send?phone=" . urlencode($phone) . "&text=" . urlencode($message);
}
