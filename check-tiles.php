<?php
require_once __DIR__ . '/app/bootstrap.php';

$db = getDatabase();

echo "<h2>All Tiles:</h2>";
$stmt = $db->query("SELECT id, slug, title, visible, publish_at, created_at FROM tiles ORDER BY order_index");
$tiles = $stmt->fetchAll();

echo "<pre>";
print_r($tiles);
echo "</pre>";

echo "<h2>Visible Tiles (what public sees):</h2>";
$stmt = $db->query("
    SELECT id, slug, title, visible, publish_at, created_at 
    FROM tiles 
    WHERE visible = 1 
    AND (publish_at IS NULL OR publish_at <= datetime('now'))
    ORDER BY order_index
");
$visibleTiles = $stmt->fetchAll();

echo "<pre>";
print_r($visibleTiles);
echo "</pre>";

echo "<h2>Current datetime:</h2>";
$stmt = $db->query("SELECT datetime('now') as now");
echo "<pre>";
print_r($stmt->fetch());
echo "</pre>";