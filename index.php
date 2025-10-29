<?php
/**
 * Portfolio Hub V2 - Main Router
 * This file routes all requests to the appropriate handlers
 */

// Error logging for debugging
error_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

// Check if setup is needed
$configFile = __DIR__ . '/config/config.php';
$needsSetup = !file_exists($configFile) || filesize($configFile) < 500;

if ($needsSetup && basename($_SERVER['PHP_SELF']) !== 'setup.php') {
    header('Location: /setup.php');
    exit;
}

// Get the request URI and remove query string
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?');
$requestUri = ltrim($requestUri, '/');

// Route setup page
if ($requestUri === 'setup.php' || strpos($requestUri, 'setup.php') === 0) {
    require __DIR__ . '/setup.php';
    exit;
}

// Route API requests (check BEFORE admin to avoid path conflicts)
if (strpos($requestUri, 'api/') === 0 || $requestUri === 'api') {
    error_log("Routing to API handler");
    require __DIR__ . '/api/index.php';
    exit;
}

// Route admin requests
if (strpos($requestUri, 'admin/') === 0 || strpos($requestUri, 'admin') === 0) {
    $adminFile = str_replace('admin/', '', $requestUri);
    $adminFile = str_replace('admin', '', $adminFile);
    
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
    
    // Add .php extension if not present
    if (!preg_match('/\.php$/', $adminFile)) {
        $adminFile .= '.php';
    }
    
    $adminPath = __DIR__ . '/admin/' . $adminFile;
    
    if (file_exists($adminPath)) {
        require $adminPath;
        exit;
    }
    
    // 404 if admin file not found
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 - Admin Page Not Found</h1></body></html>';
    exit;
}

// Serve static files from public directory if they exist
if (!empty($requestUri)) {
    // Sanitize the request URI to prevent path traversal
    $requestUri = basename($requestUri);
    $publicPath = __DIR__ . '/public/' . $requestUri;
    
    // Verify the real path is within public directory
    $realPath = realpath($publicPath);
    $publicDir = realpath(__DIR__ . '/public/');
    
    if ($realPath && $publicDir && strpos($realPath, $publicDir) === 0 && is_file($realPath)) {
        // Determine MIME type
        $mimeType = mime_content_type($realPath);
        
        // Set cache headers for static assets
        $extension = pathinfo($realPath, PATHINFO_EXTENSION);
        $cacheable = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'css', 'js', 'woff', 'woff2', 'ico']);
        
        header('Content-Type: ' . $mimeType);
        
        if ($cacheable) {
            header('Cache-Control: public, max-age=31536000, immutable');
        }
        
        readfile($realPath);
        exit;
    }
}

// Default to public index.php (home page)
require __DIR__ . '/public/index.php';