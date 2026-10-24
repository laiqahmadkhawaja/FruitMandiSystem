<?php
/**
 * Mandi Accounting & Inventory System (سبزی و پھل منڈی کمیشن ایجنٹ سافٹ ویئر)
 * Core PHP Configuration File - Local & Cloud Dual Compatibility
 */

// Enable Output Buffering to prevent "headers already sent" warnings
if (!ob_get_level()) {
    ob_start();
}

// Timezone
date_default_timezone_set('Asia/Karachi');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect Environment (Local vs Cloud)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = (
    in_array($http_host, ['localhost', '127.0.0.1', '::1']) ||
    strpos($http_host, '192.168.') === 0 ||
    strpos($http_host, '10.') === 0
);

if ($is_local) {
    // Localhost / WAMP / XAMPP Configuration
    define('ENV_MODE', 'local');
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');       // Will fallback to 3307 if 3306 fails
    define('DB_NAME', 'mandi_fruit_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', '/Fruit/');
} else {
    // Cloud / Production Server Configuration (cPanel, VPS, Live Domain)
    define('ENV_MODE', 'cloud');
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'mandi_fruit_db'); // Change to your cloud database name
    define('DB_USER', 'root');           // Change to your cloud database user
    define('DB_PASS', '');               // Change to your cloud database password
    define('BASE_URL', '/');
}

// Default Company Settings (Used until loaded from settings table)
define('APP_NAME', 'سبحانی فروٹ کمپنی');
define('APP_SUBTITLE', 'فروٹ کمیشن ایجنٹ - نیو سبزی و پھل منڈی');
define('APP_VERSION', '2.0.0');
define('CURRENCY', 'روپے');
