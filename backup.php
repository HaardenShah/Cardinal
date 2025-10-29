<?php
/**
 * Backup Script
 * Run manually or via cron: php backup.php
 */

require_once __DIR__ . '/app/bootstrap.php';

$config = getConfig();
$backupPath = $config['BACKUP_PATH'];
$dbPath = $config['DB_PATH'];

// Ensure backup directory exists
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

// Create timestamp
$timestamp = date('Y-m-d_His');

// Backup database
$dbBackupFile = "$backupPath/database_$timestamp.db";
copy($dbPath, $dbBackupFile);

echo "✓ Database backed up: $dbBackupFile\n";

// Optionally backup uploads (can be large)
$uploadsPath = $config['UPLOAD_PATH'];
if (is_dir($uploadsPath)) {
    $uploadsBackupFile = "$backupPath/uploads_$timestamp.tar.gz";
    
    // Use tar if available
    if (function_exists('exec')) {
        exec("tar -czf $uploadsBackupFile -C " . dirname($uploadsPath) . " " . basename($uploadsPath));
        echo "✓ Uploads backed up: $uploadsBackupFile\n";
    }
}

// Clean old backups (keep last 14 days)
$keepDays = $config['BACKUP_KEEP_DAYS'];
$cutoff = time() - ($keepDays * 86400);

$files = glob("$backupPath/*");
foreach ($files as $file) {
    if (is_file($file) && filemtime($file) < $cutoff) {
        unlink($file);
        echo "✓ Removed old backup: " . basename($file) . "\n";
    }
}

echo "\n✓ Backup complete!\n";
echo "Location: $backupPath\n";
echo "Files kept: $keepDays days\n";
