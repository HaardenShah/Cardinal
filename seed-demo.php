<?php
/**
 * Seed Example Data
 * Run after init-db.php to populate with sample tiles
 * Usage: php seed-demo.php
 */

require_once __DIR__ . '/app/bootstrap.php';

$db = getDatabase();

echo "🌱 Seeding demo data...\n\n";

// Example tiles
$tiles = [
    [
        'slug' => 'paris',
        'title' => 'PARIS',
        'blurb' => 'Explore the City of Light through stunning photography and travel guides.',
        'cta_label' => 'Visit',
        'target_url' => 'https://paris.example.com',
        'accent_hex' => '#e76f51',
        'order_index' => 1,
    ],
    [
        'slug' => 'dubai',
        'title' => 'DUBAI',
        'blurb' => 'Discover luxury and innovation in the heart of the UAE.',
        'cta_label' => 'Explore',
        'target_url' => 'https://dubai.example.com',
        'accent_hex' => '#2a9d8f',
        'order_index' => 2,
    ],
    [
        'slug' => 'brazil',
        'title' => 'BRAZIL',
        'blurb' => 'Experience the vibrant culture and breathtaking landscapes of Brazil.',
        'cta_label' => 'Discover',
        'target_url' => 'https://brazil.example.com',
        'accent_hex' => '#f4a261',
        'order_index' => 3,
    ],
    [
        'slug' => 'india',
        'title' => 'INDIA',
        'blurb' => 'Journey through ancient temples and modern marvels across India.',
        'cta_label' => 'Explore',
        'target_url' => 'https://india.example.com',
        'accent_hex' => '#e9c46a',
        'order_index' => 4,
    ],
];

$stmt = $db->prepare("
    INSERT INTO tiles (slug, title, blurb, cta_label, target_url, accent_hex, order_index, visible)
    VALUES (:slug, :title, :blurb, :cta_label, :target_url, :accent_hex, :order_index, 1)
");

foreach ($tiles as $tile) {
    try {
        $stmt->execute($tile);
        echo "✓ Created tile: {$tile['title']}\n";
    } catch (Exception $e) {
        echo "✗ Tile {$tile['title']} already exists or error: {$e->getMessage()}\n";
    }
}

echo "\n✓ Demo data seeded!\n";
echo "\nNext steps:\n";
echo "1. Login to admin: /admin/login\n";
echo "2. Upload images for tiles\n";
echo "3. Customize settings\n";
echo "4. Update tile URLs to your actual sub-domains\n";
