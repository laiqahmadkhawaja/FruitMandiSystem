-- Mandi Fruit Commission Agent Accounting System
-- Database: mandi_fruit_db
-- Date: 2026-10-24 20:53:31

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `bazar_wasooli`;
CREATE TABLE `bazar_wasooli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `morning_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'وصولی صبح',
  `evening_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'وصولی شام',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_customer` (`date`,`customer_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `urdu_name` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT 'Fruit',
  `default_packing_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('1', '01', 'Apple', 'سیب', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('2', '02', 'Orange / Malta', 'مالٹا / کینو', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('3', '03', 'Guava', 'امرود', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('4', '04', 'Banana', 'کیلا', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('5', '05', 'Pomegranate', 'انار قندھاری', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('6', '06', 'Grapes', 'انگور', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('7', '07', 'Sweet Lime', 'میٹھا', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('8', '08', 'Persimmon', 'جاپانی پھل', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('9', '09', 'Mango', 'آم', 'Fruit', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('10', '10', 'Potato', 'آلو', 'Vegetable', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('11', '11', 'Onion', 'پیاز', 'Vegetable', NULL, 'active', '2026-10-24 20:39:13');
INSERT INTO `items` (`id`, `code`, `name`, `urdu_name`, `category`, `default_packing_id`, `status`, `created_at`) VALUES ('12', '12', 'Tomato', 'ٹماٹر', 'Vegetable', NULL, 'active', '2026-10-24 20:39:13');

DROP TABLE IF EXISTS `maal_amad`;
CREATE TABLE `maal_amad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `challan_no` varchar(50) NOT NULL,
  `amad_date` date NOT NULL,
  `farmer_id` int(11) NOT NULL COMMENT 'زمیندار / بھیجنے والا',
  `marke` varchar(100) NOT NULL COMMENT 'مارکہ / لاٹ نمبر جیسے NZ+JM',
  `item_id` int(11) NOT NULL COMMENT 'جنس',
  `packing_id` int(11) NOT NULL COMMENT 'پیکنگ',
  `total_nag` int(11) NOT NULL DEFAULT 0 COMMENT 'کل نگ',
  `sold_nag` int(11) NOT NULL DEFAULT 0 COMMENT 'فروخت شدہ نگ',
  `remaining_nag` int(11) NOT NULL DEFAULT 0 COMMENT 'باقی نگ',
  `vehicle_no` varchar(50) DEFAULT NULL COMMENT 'گاڑی نمبر',
  `bilty_no` varchar(50) DEFAULT NULL COMMENT 'بلٹی نمبر',
  `driver_phone` varchar(50) DEFAULT NULL,
  `total_freight` decimal(12,2) DEFAULT 0.00 COMMENT 'گاڑی کا کرایہ',
  `freight_paid_by_agent` decimal(12,2) DEFAULT 0.00 COMMENT 'آڑھتی کا ادا کردہ کرایہ',
  `local_cartage` decimal(12,2) DEFAULT 0.00 COMMENT 'لوکل کرایہ / اڈا خرچہ',
  `status` enum('pending','partially_sold','completed') DEFAULT 'pending',
  `bill_posted` tinyint(1) DEFAULT 0 COMMENT 'کیا فرد حساب بن چکا ہے؟',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `farmer_id` (`farmer_id`),
  KEY `amad_date` (`amad_date`),
  KEY `marke` (`marke`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `packings`;
CREATE TABLE `packings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `urdu_name` varchar(100) NOT NULL,
  `default_weight_kg` decimal(8,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('1', 'Crate', 'کریٹ', '18.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('2', 'Carton', 'کاٹن', '12.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('3', 'Box / Peti', 'پیٹی', '10.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('4', 'Gatto / Bag', 'گٹو', '40.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('5', 'Dibbi', 'ڈبی', '5.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('6', 'Tokri', 'ٹوکری', '15.00', '2026-10-24 20:39:13');
INSERT INTO `packings` (`id`, `name`, `urdu_name`, `default_weight_kg`, `created_at`) VALUES ('7', 'Chhapa', 'چھاپہ', '25.00', '2026-10-24 20:39:13');

DROP TABLE IF EXISTS `parties`;
CREATE TABLE `parties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `type` enum('farmer','customer','bank','expense') NOT NULL,
  `name` varchar(150) NOT NULL,
  `urdu_name` varchar(150) NOT NULL,
  `marfat` varchar(150) DEFAULT NULL COMMENT 'معرفت / Reference',
  `cnic` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `recovery_officer` varchar(100) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `is_blacklisted` tinyint(1) DEFAULT 0,
  `opening_balance_date` date DEFAULT NULL,
  `opening_dr` decimal(14,2) DEFAULT 0.00 COMMENT 'سابقہ نام (Debit)',
  `opening_cr` decimal(14,2) DEFAULT 0.00 COMMENT 'سابقہ جمع (Credit)',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('1', 'Z01', 'farmer', 'Jummah Gul', 'جمعہ گل', NULL, NULL, '03001234567', 'زیارت / کوئٹہ', NULL, NULL, '0.00', '0', NULL, '0.00', '150000.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('2', 'Z02', 'farmer', 'Haji Abdul Karim', 'حاجی عبدالکریم', NULL, NULL, '03017654321', 'سوات', NULL, NULL, '0.00', '0', NULL, '0.00', '85000.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('3', 'G01', 'customer', 'Asif Pehlwan', 'آصف پہلوان', NULL, NULL, '03211112233', 'سیالکوٹ', NULL, NULL, '0.00', '0', NULL, '45000.00', '0.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('4', 'G02', 'customer', 'Anwar Shop 20', 'انور بلا نمبر 20', NULL, NULL, '03334445566', 'ڈسکہ', NULL, NULL, '0.00', '0', NULL, '112000.00', '0.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('5', 'G03', 'customer', 'Shakeel Kamboh', 'شکیل کمبوہ', NULL, NULL, '03459998877', 'سمبڑیال', NULL, NULL, '0.00', '0', NULL, '35000.00', '0.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('6', 'B01', 'bank', 'HBL Mandi Branch', 'حبیب بینک منڈی برانچ', NULL, NULL, '', 'سیالکوٹ', NULL, NULL, '0.00', '0', NULL, '250000.00', '0.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('7', 'E01', 'expense', 'Shop Expense', 'دکان خرچہ / چائے پانی', NULL, NULL, '', 'سیالکوٹ', NULL, NULL, '0.00', '0', NULL, '0.00', '0.00', 'active', '2026-10-24 20:39:13');
INSERT INTO `parties` (`id`, `code`, `type`, `name`, `urdu_name`, `marfat`, `cnic`, `mobile`, `city`, `address`, `recovery_officer`, `credit_limit`, `is_blacklisted`, `opening_balance_date`, `opening_dr`, `opening_cr`, `status`, `created_at`) VALUES ('8', 'E02', 'expense', 'Labour / Mazdoori', 'مزدوری خرچ', NULL, NULL, '', 'سیالکوٹ', NULL, NULL, '0.00', '0', NULL, '0.00', '0.00', 'active', '2026-10-24 20:39:13');

DROP TABLE IF EXISTS `sales_bills`;
CREATE TABLE `sales_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(50) NOT NULL,
  `sale_date` date NOT NULL,
  `customer_id` int(11) NOT NULL COMMENT 'گاہک / خریدار',
  `amad_id` int(11) DEFAULT NULL COMMENT 'مال آمد چالان لاٹ',
  `item_id` int(11) NOT NULL COMMENT 'جنس',
  `packing_id` int(11) NOT NULL COMMENT 'پیکنگ',
  `marke` varchar(100) DEFAULT NULL COMMENT 'مارکہ',
  `nag` int(11) NOT NULL COMMENT 'تعداد نگ',
  `rate` decimal(10,2) NOT NULL COMMENT 'ریٹ فی نگ',
  `gross_amount` decimal(12,2) NOT NULL COMMENT 'کل رقم',
  `commission_percent` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 0 COMMENT 'نقد سیل یا ادھار',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_date` (`sale_date`),
  KEY `customer_id` (`customer_id`),
  KEY `amad_id` (`amad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_name` varchar(255) NOT NULL DEFAULT 'سبحانی فروٹ کمپنی',
  `shop_subheading` varchar(255) NOT NULL DEFAULT 'فروٹ کمیشن ایجنٹ',
  `mandi_name` varchar(255) NOT NULL DEFAULT 'نیو سبزی و پھل منڈی',
  `city` varchar(100) NOT NULL DEFAULT 'سیالکوٹ',
  `address` text DEFAULT NULL,
  `phone` varchar(100) NOT NULL DEFAULT '0300-824-9115',
  `phone2` varchar(100) DEFAULT NULL,
  `default_commission_rate` decimal(5,2) NOT NULL DEFAULT 6.00,
  `default_mazdoori_per_nag` decimal(10,2) NOT NULL DEFAULT 10.00,
  `default_market_fee_percent` decimal(5,2) NOT NULL DEFAULT 1.00,
  `default_munshiana_per_bill` decimal(10,2) NOT NULL DEFAULT 20.00,
  `whatsapp_country_code` varchar(10) NOT NULL DEFAULT '92',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `shop_name`, `shop_subheading`, `mandi_name`, `city`, `address`, `phone`, `phone2`, `default_commission_rate`, `default_mazdoori_per_nag`, `default_market_fee_percent`, `default_munshiana_per_bill`, `whatsapp_country_code`, `created_at`, `updated_at`) VALUES ('1', 'سبحانی فروٹ کمپنی', 'فروٹ کمیشن ایجنٹ', 'نیو سبزی و پھل منڈی، ایمن آباد روڈ', 'سیالکوٹ', NULL, '0300-824-9115', NULL, '6.00', '10.00', '1.00', '20.00', '92', '2026-10-24 20:39:13', '2026-10-24 20:39:13');

DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(50) NOT NULL,
  `voucher_date` date NOT NULL,
  `type` enum('receipt','payment','journal','daily_expense') NOT NULL COMMENT 'وصولی، ادائیگی، جرنل، روزانہ خرچ',
  `party_id` int(11) DEFAULT NULL COMMENT 'پارٹی',
  `bill_id` int(11) DEFAULT NULL COMMENT 'اگر کسی بل سے متعلق ہو',
  `debit_party_id` int(11) DEFAULT NULL COMMENT 'بنام',
  `credit_party_id` int(11) DEFAULT NULL COMMENT 'بجمع',
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','transfer') DEFAULT 'cash',
  `bank_party_id` int(11) DEFAULT NULL,
  `narration` text NOT NULL COMMENT 'تفصیل',
  `time_slot` enum('morning','evening','general') DEFAULT 'general' COMMENT 'صبح یا شام کی وصولی',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `voucher_date` (`voucher_date`),
  KEY `party_id` (`party_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `zamindar_bills`;
CREATE TABLE `zamindar_bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(50) NOT NULL,
  `bill_date` date NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `amad_id` int(11) NOT NULL,
  `total_nag` int(11) NOT NULL,
  `gross_sale_amount` decimal(14,2) NOT NULL COMMENT 'کل فروخت',
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(14,2) NOT NULL,
  `freight_deduction` decimal(14,2) DEFAULT 0.00 COMMENT 'کرایہ کٹوتی',
  `local_cartage_deduction` decimal(14,2) DEFAULT 0.00 COMMENT 'لوکل کرایہ',
  `mazdoori_amount` decimal(14,2) DEFAULT 0.00 COMMENT 'مزدوری کٹوتی',
  `market_fee_amount` decimal(14,2) DEFAULT 0.00 COMMENT 'مارکیٹ فیس',
  `munshiana_amount` decimal(14,2) DEFAULT 0.00 COMMENT 'منشیانہ',
  `other_expenses` decimal(14,2) DEFAULT 0.00 COMMENT 'دیگر کٹوتیاں',
  `advance_adjusted` decimal(14,2) DEFAULT 0.00 COMMENT 'پیشگی رقم کٹوتی',
  `net_payable` decimal(14,2) NOT NULL COMMENT 'صافی قابلِ ادائیگی رقم',
  `is_cleared` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `farmer_id` (`farmer_id`),
  KEY `amad_id` (`amad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
