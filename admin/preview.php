<?php
require_once __DIR__ . '/../app/bootstrap.php';
startSecureSession();

if (!isAuthenticated()) {
    header('Location: /admin/login');
    exit;
}

// Show draft content if specified
$draft = isset($_GET['draft']) && $_GET['draft'] === '1';

// Bypass normal caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include public page
include __DIR__ . '/../public/index.php';
