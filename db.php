<?php
/**
 * Database Connection & Auto-Schema Initializer
 * UTF8mb4 for complete Urdu Nastaliq support
 */

require_once __DIR__ . '/config.php';

$pdo = null;

try {
    // Attempt connecting to the specified DB_PORT (e.g. 3306), fallback to 3307 if local
    $ports_to_try = [DB_PORT];
    if (ENV_MODE === 'local' && DB_PORT != '3307') {
        $ports_to_try[] = '3307';
    }

    $connected = false;
    $last_error = '';

    foreach ($ports_to_try as $port) {
        try {
            // First connect without db name to check or create database
            $dsn_server = "mysql:host=" . DB_HOST . ";port=" . $port . ";charset=utf8mb4";
            $temp_pdo = new PDO($dsn_server, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Create database if not exists
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now connect directly to the database
            $dsn_db = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn_db, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET NAMES utf8mb4");

            $connected = true;
            break;
        } catch (Exception $e) {
            $last_error = $e->getMessage();
        }
    }

    if (!$connected || !$pdo) {
        die("<div style='font-family:sans-serif; direction:rtl; text-align:right; padding:20px; background:#fff3f3; border:1px solid #ffcaca; border-radius:8px; margin:20px;'>
            <h2 style='color:#c00;'>ڈیٹا بیس کنکشن میں خرابی!</h2>
            <p>سافٹ ویئر ڈیٹا بیس سے منسلک نہیں ہو سکا۔ براہ کرم WAMP / MySQL چیک کریں۔</p>
            <p><strong>تفصیل:</strong> " . htmlspecialchars($last_error) . "</p>
        </div>");
    }

    // Auto-create tables if they don't exist
    init_database_schema($pdo);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

/**
 * Initialize all core tables required for Mandi operations
 */
function init_database_schema($pdo) {
    $schema = "
    -- Settings Table
    CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `shop_name` VARCHAR(255) NOT NULL DEFAULT 'سبحانی فروٹ کمپنی',
        `shop_subheading` VARCHAR(255) NOT NULL DEFAULT 'فروٹ کمیشن ایجنٹ',
        `mandi_name` VARCHAR(255) NOT NULL DEFAULT 'نیو سبزی و پھل منڈی',
        `city` VARCHAR(100) NOT NULL DEFAULT 'سیالکوٹ',
        `address` TEXT NULL,
        `phone` VARCHAR(100) NOT NULL DEFAULT '0300-824-9115',
        `phone2` VARCHAR(100) NULL,
        `default_commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 6.00,
        `default_mazdoori_per_nag` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
        `default_market_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
        `default_munshiana_per_bill` DECIMAL(10,2) NOT NULL DEFAULT 20.00,
        `whatsapp_country_code` VARCHAR(10) NOT NULL DEFAULT '92',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Packing Types (گٹو، پیٹی، کاٹن، ڈبی، ٹوکری، چھاپہ وغیرہ)
    CREATE TABLE IF NOT EXISTS `packings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `urdu_name` VARCHAR(100) NOT NULL,
        `default_weight_kg` DECIMAL(8,2) DEFAULT 0.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Items / Commodities (جنس: سیب، مالٹا، امرود، کیلا، انار وغیرہ)
    CREATE TABLE IF NOT EXISTS `items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NULL,
        `name` VARCHAR(150) NOT NULL,
        `urdu_name` VARCHAR(150) NOT NULL,
        `category` VARCHAR(50) DEFAULT 'Fruit',
        `default_packing_id` INT NULL,
        `status` ENUM('active','inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Parties (زمیندار / کاشتکار، گاہک / دکاندار، بینکس، اخراجات کھاتے)
    CREATE TABLE IF NOT EXISTS `parties` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NULL,
        `type` ENUM('farmer','customer','bank','expense') NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `urdu_name` VARCHAR(150) NOT NULL,
        `marfat` VARCHAR(150) NULL COMMENT 'معرفت / Reference',
        `cnic` VARCHAR(50) NULL,
        `mobile` VARCHAR(50) NULL,
        `city` VARCHAR(100) NULL,
        `address` TEXT NULL,
        `recovery_officer` VARCHAR(100) NULL,
        `credit_limit` DECIMAL(12,2) DEFAULT 0.00,
        `is_blacklisted` TINYINT(1) DEFAULT 0,
        `opening_balance_date` DATE NULL,
        `opening_dr` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'سابقہ نام (Debit)',
        `opening_cr` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'سابقہ جمع (Credit)',
        `status` ENUM('active','inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Maal Aamad / Inward Challan (مال آمد کا اندراج)
    CREATE TABLE IF NOT EXISTS `maal_amad` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `challan_no` VARCHAR(50) NOT NULL,
        `amad_date` DATE NOT NULL,
        `farmer_id` INT NOT NULL COMMENT 'زمیندار / بھیجنے والا',
        `marke` VARCHAR(100) NOT NULL COMMENT 'مارکہ / لاٹ نمبر جیسے NZ+JM',
        `item_id` INT NOT NULL COMMENT 'جنس',
        `packing_id` INT NOT NULL COMMENT 'پیکنگ',
        `total_nag` INT NOT NULL DEFAULT 0 COMMENT 'کل نگ',
        `sold_nag` INT NOT NULL DEFAULT 0 COMMENT 'فروخت شدہ نگ',
        `remaining_nag` INT NOT NULL DEFAULT 0 COMMENT 'باقی نگ',
        `vehicle_no` VARCHAR(50) NULL COMMENT 'گاڑی نمبر',
        `bilty_no` VARCHAR(50) NULL COMMENT 'بلٹی نمبر',
        `driver_phone` VARCHAR(50) NULL,
        `total_freight` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'گاڑی کا کرایہ',
        `freight_paid_by_agent` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'آڑھتی کا ادا کردہ کرایہ',
        `local_cartage` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'لوکل کرایہ / اڈا خرچہ',
        `status` ENUM('pending','partially_sold','completed') DEFAULT 'pending',
        `bill_posted` TINYINT(1) DEFAULT 0 COMMENT 'کیا فرد حساب بن چکا ہے؟',
        `notes` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`farmer_id`),
        INDEX (`amad_date`),
        INDEX (`marke`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Daily Sales / Boli Records (روزانہ نیلامی و فروخت بل)
    CREATE TABLE IF NOT EXISTS `sales_bills` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `bill_no` VARCHAR(50) NOT NULL,
        `sale_date` DATE NOT NULL,
        `customer_id` INT NOT NULL COMMENT 'گاہک / خریدار',
        `amad_id` INT NULL COMMENT 'مال آمد چالان لاٹ',
        `item_id` INT NOT NULL COMMENT 'جنس',
        `packing_id` INT NOT NULL COMMENT 'پیکنگ',
        `marke` VARCHAR(100) NULL COMMENT 'مارکہ',
        `nag` INT NOT NULL COMMENT 'تعداد نگ',
        `rate` DECIMAL(10,2) NOT NULL COMMENT 'ریٹ فی نگ',
        `gross_amount` DECIMAL(12,2) NOT NULL COMMENT 'کل رقم',
        `commission_percent` DECIMAL(5,2) DEFAULT 0.00,
        `commission_amount` DECIMAL(12,2) DEFAULT 0.00,
        `net_amount` DECIMAL(12,2) NOT NULL,
        `is_paid` TINYINT(1) DEFAULT 0 COMMENT 'نقد سیل یا ادھار',
        `notes` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`sale_date`),
        INDEX (`customer_id`),
        INDEX (`amad_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Zamindar Bill / Fard Hisab (زمیندار بل / فردِ حساب)
    CREATE TABLE IF NOT EXISTS `zamindar_bills` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `bill_no` VARCHAR(50) NOT NULL,
        `bill_date` DATE NOT NULL,
        `farmer_id` INT NOT NULL,
        `amad_id` INT NOT NULL,
        `total_nag` INT NOT NULL,
        `gross_sale_amount` DECIMAL(14,2) NOT NULL COMMENT 'کل فروخت',
        `commission_rate` DECIMAL(5,2) NOT NULL,
        `commission_amount` DECIMAL(14,2) NOT NULL,
        `freight_deduction` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'کرایہ کٹوتی',
        `local_cartage_deduction` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'لوکل کرایہ',
        `mazdoori_amount` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'مزدوری کٹوتی',
        `market_fee_amount` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'مارکیٹ فیس',
        `munshiana_amount` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'منشیانہ',
        `other_expenses` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'دیگر کٹوتیاں',
        `advance_adjusted` DECIMAL(14,2) DEFAULT 0.00 COMMENT 'پیشگی رقم کٹوتی',
        `net_payable` DECIMAL(14,2) NOT NULL COMMENT 'صافی قابلِ ادائیگی رقم',
        `is_cleared` TINYINT(1) DEFAULT 0,
        `notes` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`farmer_id`),
        INDEX (`amad_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Vouchers / Transactions (وصولی، ادائیگی، روکڑ اندراج)
    CREATE TABLE IF NOT EXISTS `vouchers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `voucher_no` VARCHAR(50) NOT NULL,
        `voucher_date` DATE NOT NULL,
        `type` ENUM('receipt','payment','journal','daily_expense') NOT NULL COMMENT 'وصولی، ادائیگی، جرنل، روزانہ خرچ',
        `party_id` INT NULL COMMENT 'پارٹی',
        `bill_id` INT NULL COMMENT 'اگر کسی بل سے متعلق ہو',
        `debit_party_id` INT NULL COMMENT 'بنام',
        `credit_party_id` INT NULL COMMENT 'بجمع',
        `amount` DECIMAL(14,2) NOT NULL,
        `payment_method` ENUM('cash','bank','cheque','transfer') DEFAULT 'cash',
        `bank_party_id` INT NULL,
        `narration` TEXT NOT NULL COMMENT 'تفصیل',
        `time_slot` ENUM('morning','evening','general') DEFAULT 'general' COMMENT 'صبح یا شام کی وصولی',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`voucher_date`),
        INDEX (`party_id`),
        INDEX (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Daily Bazar Wasooli (بازار کھاتہ - صبح و شام کی وصولی)
    CREATE TABLE IF NOT EXISTS `bazar_wasooli` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `date` DATE NOT NULL,
        `customer_id` INT NOT NULL,
        `morning_amount` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'وصولی صبح',
        `evening_amount` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'وصولی شام',
        `remarks` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_date_customer` (`date`, `customer_id`),
        INDEX (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($schema);

    // Seed default settings if empty
    $check_settings = $pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($check_settings == 0) {
        $pdo->exec("INSERT INTO `settings` (`shop_name`, `shop_subheading`, `mandi_name`, `city`, `phone`, `default_commission_rate`, `default_mazdoori_per_nag`, `default_market_fee_percent`, `default_munshiana_per_bill`)
            VALUES ('سبحانی فروٹ کمپنی', 'فروٹ کمیشن ایجنٹ', 'نیو سبزی و پھل منڈی، ایمن آباد روڈ', 'سیالکوٹ', '0300-824-9115', 6.00, 10.00, 1.00, 20.00)");
    }

    // Seed default packing types if empty
    $check_pack = $pdo->query("SELECT COUNT(*) FROM `packings`")->fetchColumn();
    if ($check_pack == 0) {
        $pdo->exec("INSERT INTO `packings` (`name`, `urdu_name`, `default_weight_kg`) VALUES
            ('Crate', 'کریٹ', 18.00),
            ('Carton', 'کاٹن', 12.00),
            ('Box / Peti', 'پیٹی', 10.00),
            ('Gatto / Bag', 'گٹو', 40.00),
            ('Dibbi', 'ڈبی', 5.00),
            ('Tokri', 'ٹوکری', 15.00),
            ('Chhapa', 'چھاپہ', 25.00)
        ");
    }

    // Seed default items / fruits if empty
    $check_items = $pdo->query("SELECT COUNT(*) FROM `items`")->fetchColumn();
    if ($check_items == 0) {
        $pdo->exec("INSERT INTO `items` (`code`, `name`, `urdu_name`, `category`) VALUES
            ('01', 'Apple', 'سیب', 'Fruit'),
            ('02', 'Orange / Malta', 'مالٹا / کینو', 'Fruit'),
            ('03', 'Guava', 'امرود', 'Fruit'),
            ('04', 'Banana', 'کیلا', 'Fruit'),
            ('05', 'Pomegranate', 'انار قندھاری', 'Fruit'),
            ('06', 'Grapes', 'انگور', 'Fruit'),
            ('07', 'Sweet Lime', 'میٹھا', 'Fruit'),
            ('08', 'Persimmon', 'جاپانی پھل', 'Fruit'),
            ('09', 'Mango', 'آم', 'Fruit'),
            ('10', 'Potato', 'آلو', 'Vegetable'),
            ('11', 'Onion', 'پیاز', 'Vegetable'),
            ('12', 'Tomato', 'ٹماٹر', 'Vegetable')
        ");
    }

    // Seed sample initial parties if empty
    $check_parties = $pdo->query("SELECT COUNT(*) FROM `parties`")->fetchColumn();
    if ($check_parties == 0) {
        $pdo->exec("INSERT INTO `parties` (`code`, `type`, `name`, `urdu_name`, `city`, `mobile`, `opening_dr`, `opening_cr`) VALUES
            ('Z01', 'farmer', 'Jummah Gul', 'جمعہ گل', 'زیارت / کوئٹہ', '03001234567', 0.00, 150000.00),
            ('Z02', 'farmer', 'Haji Abdul Karim', 'حاجی عبدالکریم', 'سوات', '03017654321', 0.00, 85000.00),
            ('G01', 'customer', 'Asif Pehlwan', 'آصف پہلوان', 'سیالکوٹ', '03211112233', 45000.00, 0.00),
            ('G02', 'customer', 'Anwar Shop 20', 'انور بلا نمبر 20', 'ڈسکہ', '03334445566', 112000.00, 0.00),
            ('G03', 'customer', 'Shakeel Kamboh', 'شکیل کمبوہ', 'سمبڑیال', '03459998877', 35000.00, 0.00),
            ('B01', 'bank', 'HBL Mandi Branch', 'حبیب بینک منڈی برانچ', 'سیالکوٹ', '', 250000.00, 0.00),
            ('E01', 'expense', 'Shop Expense', 'دکان خرچہ / چائے پانی', 'سیالکوٹ', '', 0.00, 0.00),
            ('E02', 'expense', 'Labour / Mazdoori', 'مزدوری خرچ', 'سیالکوٹ', '', 0.00, 0.00)
        ");
    }
}
