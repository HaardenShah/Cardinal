<?php
/**
 * Portfolio Hub V2 - Main Entry Point
 * Routes all requests and handles setup redirect
 */

// Check if setup is needed
$configFile = __DIR__ . '/config/config.php';
$needsSetup = !file_exists($configFile) || filesize($configFile) < 500;

if ($needsSetup && !strpos($_SERVER['REQUEST_URI'], '/setup.php')) {
    header('Location: /setup.php');
    exit;
}

// Get the request URI and remove query string
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?');
$requestUri = ltrim($requestUri, '/');

// Route setup page
if (strpos($requestUri, 'setup.php') === 0 || $requestUri === 'setup.php') {
    require __DIR__ . '/setup.php';
    exit;
}

// Route API requests
if (strpos($requestUri, 'api/') === 0) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Route admin requests
if (strpos($requestUri, 'admin/') === 0) {
    $adminFile = str_replace('admin/', '', $requestUri);
    
    // Handle admin assets
    if (strpos($adminFile, 'assets/') === 0) {
        $assetPath = __DIR__ . '/admin/' . $adminFile;
        if (file_exists($assetPath)) {
            $mimeType = mime_content_type($assetPath);
            header('Content-Type: ' . $mimeType);
            header('Cache-Control: public, max-age=31536000');
            readfile($assetPath);
            exit;
        }
    }
    
    // Handle admin PHP files
    if (empty($adminFile)) {
        $adminFile = 'tiles.php';
    }
    
    if (!preg_match('/\.php$/', $adminFile)) {
        $adminFile .= '.php';
    }
    
    $adminPath = __DIR__ . '/admin/' . $adminFile;
    
    if (file_exists($adminPath)) {
        require $adminPath;
        exit;
    }
}

// Serve static files from public directory
if (!empty($requestUri)) {
    $publicPath = __DIR__ . '/public/' . $requestUri;
    
    if (file_exists($publicPath) && is_file($publicPath)) {
        $mimeType = mime_content_type($publicPath);
        $extension = pathinfo($publicPath, PATHINFO_EXTENSION);
        $cacheable = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'css', 'js', 'woff', 'woff2', 'ico']);
        
        header('Content-Type: ' . $mimeType);
        
        if ($cacheable) {
            header('Cache-Control: public, max-age=31536000, immutable');
        }
        
        readfile($publicPath);
        exit;
    }
}

// Default to public index.php
require __DIR__ . '/public/index.php';