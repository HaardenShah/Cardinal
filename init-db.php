<?php
/**
 * Database Schema and Migration
 * Run once: php init-db.php
 */

require_once __DIR__ . '/app/bootstrap.php';

$db = getDatabase();

// Create tables
$db->exec("
CREATE TABLE IF NOT EXISTS tiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    blurb TEXT,
    cta_label TEXT DEFAULT 'Visit',
    target_url TEXT NOT NULL,
    bg_media_id INTEGER,
    accent_hex TEXT,
    order_index INTEGER DEFAULT 0,
    visible INTEGER DEFAULT 1,
    publish_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bg_media_id) REFERENCES media(id) ON DELETE SET NULL
)");

$db->exec("
CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_name TEXT NOT NULL,
    path_original TEXT NOT NULL,
    path_webp TEXT NOT NULL,
    width INTEGER,
    height INTEGER,
    sizes_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT CHECK(role IN ('admin', 'editor')) DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME
)");

$db->exec("
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
)");

$db->exec("
CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id INTEGER,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");

// Create indexes
$db->exec("CREATE INDEX IF NOT EXISTS idx_tiles_visible ON tiles(visible, publish_at)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tiles_order ON tiles(order_index)");

// Insert default settings
$defaultSettings = [
    'site_title' => 'Your Name - Portfolio Hub',
    'site_description' => 'Explore my work across multiple domains',
    'hero_text' => 'Your Name',
    'hero_subtext' => 'Designer • Developer • Creator',
    'brand_primary' => '#6366f1',
    'brand_secondary' => '#8b5cf6',
    'autoplay_enabled' => '1',
    'autoplay_interval' => '7',
    'animation_speed' => 'normal',
    'favicon_media_id' => '',
    'logo_media_id' => '',
    'open_links_new_tab' => '0',
];

$stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)");
foreach ($defaultSettings as $key => $value) {
    $stmt->execute([':key' => $key, ':value' => $value]);
}

// Create seed admin user if doesn't exist
$config = getConfig();
$email = $config['ADMIN_EMAIL_SEED'];
$password = $config['ADMIN_PASSWORD_SEED'];

$stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

if (!$stmt->fetch()) {
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, 'admin')");
    $stmt->execute([':email' => $email, ':hash' => $hash]);
    echo "✓ Admin user created: {$email}\n";
    echo "⚠ CHANGE THE PASSWORD IMMEDIATELY!\n\n";
} else {
    echo "✓ Admin user already exists\n\n";
}

echo "✓ Database initialized successfully\n";
echo "✓ Schema version: 1.0\n";
echo "✓ Location: " . $config['DB_PATH'] . "\n";
