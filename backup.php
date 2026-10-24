<?php
/**
 * One-Click Database Backup & Export
 * Matching Video Frame 005 (یو ایس بی، گوگل ڈرائیو بیک اپ)
 */
require_once __DIR__ . '/functions.php';

$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Handle Export / Download Action BEFORE any HTML output
if (isset($_GET['action']) && $_GET['action'] === 'download_sql') {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql_dump = "-- Mandi Fruit Commission Agent Accounting System\n";
    $sql_dump .= "-- Database Backup: " . DB_NAME . "\n";
    $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_dump .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $t) {
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch();
        $sql_dump .= "DROP TABLE IF EXISTS `$t`;\n";
        $sql_dump .= $create['Create Table'] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll();
        if (!empty($rows)) {
            $cols = array_keys($rows[0]);
            $col_list = "`" . implode("`, `", $cols) . "`";

            foreach ($rows as $r) {
                $vals = [];
                foreach ($r as $val) {
                    if ($val === null) {
                        $vals[] = "NULL";
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $sql_dump .= "INSERT INTO `$t` ($col_list) VALUES (" . implode(", ", $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }
    $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    $filename = 'mandi_backup_' . date('Y_m_d_H_i_s') . '.sql';

    // Save a copy to backups directory
    file_put_contents($backup_dir . '/' . $filename, $sql_dump);

    // Trigger browser download
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql_dump));
    echo $sql_dump;
    exit;
}

require_once __DIR__ . '/includes/header.php';

// List existing backups
$backup_files = array_diff(scandir($backup_dir), ['.', '..']);
rsort($backup_files);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-xl-6">
        <div class="card-custom">
            <div class="card-custom-header">
                <span><i class="fa-solid fa-cloud-arrow-down text-success me-2"></i> ڈیٹا بیک اپ و سیکیورٹی (Database Backup)</span>
            </div>
            <div class="p-4 text-center">
                <i class="fa-solid fa-database fa-4x text-success mb-3"></i>
                <h4 class="urdu-font fw-bold">اپنے مکمل ڈیٹا بیس کا محفوظ بیک اپ بنائیں</h4>
                <p class="text-muted">
                    روزانہ شام دکان بند کرتے وقت ایک کلک سے بیک اپ فائل ڈاؤن لوڈ کریں اور اپنی USB یا گوگل ڈرائیو میں محفوظ رکھیں تاکہ آپ کا ڈیٹا ہمیشہ محفوظ رہے۔
                </p>

                <div class="d-flex justify-content-center gap-3 my-4">
                    <a href="backup.php?action=download_sql" class="btn btn-mandi-primary py-3 px-4 fs-5">
                        <i class="fa-solid fa-download me-2"></i> مکمل SQL بیک اپ ڈاؤن لوڈ کریں
                    </a>
                </div>

                <div class="border rounded p-3 bg-light text-start mt-4">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-1"></i> حالیہ محفوظ شدہ بیک اپ فائلیں:</h6>
                    <?php if (empty($backup_files)): ?>
                        <div class="text-muted small">ابھی تک کوئی لوکل بیک اپ محفوظ نہیں ہوا۔ اوپر دیے گئے بٹن پر کلک کریں۔</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($backup_files as $bf): 
                                $fsize = round(filesize($backup_dir . '/' . $bf) / 1024, 1);
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-2">
                                    <span class="rubik-font small"><i class="fa-solid fa-file-code text-primary me-2"></i> <?= htmlspecialchars($bf) ?></span>
                                    <span class="badge bg-secondary"><?= $fsize ?> KB</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
