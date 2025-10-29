<?php
/**
 * API Router
 * Routes all /api/* requests
 */

require_once __DIR__ . '/../app/bootstrap.php';

setSecurityHeaders();
header('Content-Type: application/json');

// Parse request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix
$path = preg_replace('~^/api~', '', $path);

// Route handlers
$routes = [
    'GET' => [
        '/public/tiles' => 'handleGetPublicTiles',
        '/tiles' => 'handleGetTiles',
        '/tiles/(\d+)' => 'handleGetTile',
        '/media' => 'handleGetMedia',
        '/media/serve/(\d+)' => 'handleServeMedia',
        '/settings' => 'handleGetSettings',
        '/health' => 'handleHealth',
    ],
    'POST' => [
        '/auth/login' => 'handleLogin',
        '/auth/logout' => 'handleLogout',
        '/tiles' => 'handleCreateTile',
        '/media' => 'handleUploadMedia',
    ],
    'PUT' => [
        '/tiles/(\d+)' => 'handleUpdateTile',
        '/settings' => 'handleUpdateSettings',
    ],
    'PATCH' => [
        '/tiles/reorder' => 'handleReorderTiles',
    ],
    'DELETE' => [
        '/tiles/(\d+)' => 'handleDeleteTile',
        '/media/(\d+)' => 'handleDeleteMedia',
    ],
];

// Find matching route
$handler = null;
$matches = [];

if (isset($routes[$requestMethod])) {
    foreach ($routes[$requestMethod] as $pattern => $func) {
        if (preg_match('~^' . $pattern . '$~', $path, $matches)) {
            $handler = $func;
            array_shift($matches); // Remove full match
            break;
        }
    }
}

if (!$handler) {
    jsonResponse(['error' => 'Not found'], 404);
    exit;
}

// Execute handler
try {
    call_user_func($handler, ...$matches);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

// ============================================================================
// PUBLIC ENDPOINTS
// ============================================================================

function handleGetPublicTiles(): void {
    $db = getDatabase();
    
    $stmt = $db->query("
        SELECT t.*, m.path_original, m.path_webp, m.width, m.height, m.sizes_json
        FROM tiles t
        LEFT JOIN media m ON t.bg_media_id = m.id
        WHERE t.visible = 1 
        AND (t.publish_at IS NULL OR t.publish_at <= datetime('now'))
        ORDER BY t.order_index ASC
    ");
    
    $tiles = [];
    while ($row = $stmt->fetch()) {
        $tiles[] = [
            'id' => (int)$row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'blurb' => $row['blurb'],
            'cta_label' => $row['cta_label'],
            'target_url' => $row['target_url'],
            'accent_hex' => $row['accent_hex'],
            'media' => $row['path_webp'] ? [
                'path_original' => '/api/media/serve/' . $row['bg_media_id'],
                'path_webp' => '/api/media/serve/' . $row['bg_media_id'] . '?format=webp',
                'width' => (int)$row['width'],
                'height' => (int)$row['height'],
                'sizes' => json_decode($row['sizes_json'] ?? '[]', true),
            ] : null,
        ];
    }
    
    jsonResponse(['tiles' => $tiles]);
}

function handleHealth(): void {
    $db = getDatabase();
    
    try {
        $db->query('SELECT 1');
        $dbStatus = 'ok';
    } catch (Exception $e) {
        $dbStatus = 'error';
    }
    
    $config = getConfig();
    $uploadPath = $config['UPLOAD_PATH'];
    $diskFree = disk_free_space($uploadPath);
    $diskTotal = disk_total_space($uploadPath);
    
    jsonResponse([
        'status' => 'ok',
        'database' => $dbStatus,
        'disk' => [
            'free' => $diskFree,
            'total' => $diskTotal,
            'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
        ],
    ]);
}

// ============================================================================
// AUTH ENDPOINTS
// ============================================================================

function handleLogin(): void {
    startSecureSession();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit("login:$ip", 5, 900)) {
        jsonResponse(['error' => 'Too many login attempts. Try again later.'], 429);
        return;
    }
    
    if (empty($email) || empty($password)) {
        sleep(1);
        jsonResponse(['error' => 'Email and password required'], 400);
        return;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("SELECT id, email, password_hash, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sleep(1); // Prevent timing attacks
        jsonResponse(['error' => 'Invalid credentials'], 401);
        return;
    }
    
    // Consistent timing
    sleep(1);
    
    // Successful login - regenerate session ID BEFORE setting session variables
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login_at = datetime('now') WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    
    logActivity('login', 'user', $user['id']);
    
    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
        'csrf_token' => $_SESSION['csrf_token'],
    ]);
}

function handleLogout(): void {
    startSecureSession();
    
    if (isAuthenticated()) {
        logActivity('logout', 'user', $_SESSION['user_id']);
    }
    
    session_destroy();
    jsonResponse(['success' => true]);
}

// ============================================================================
// TILE ENDPOINTS
// ============================================================================

function handleGetTiles(): void {
    startSecureSession();
    requireAuth();
    
    $db = getDatabase();
    $stmt = $db->query("
        SELECT t.*, m.path_original, m.path_webp, m.width, m.height
        FROM tiles t
        LEFT JOIN media m ON t.bg_media_id = m.id
        ORDER BY t.order_index ASC
    ");
    
    $tiles = [];
    while ($row = $stmt->fetch()) {
        $tiles[] = formatTileResponse($row);
    }
    
    jsonResponse(['tiles' => $tiles]);
}

function handleGetTile(string $id): void {
    startSecureSession();
    requireAuth();
    
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT t.*, m.path_original, m.path_webp, m.width, m.height
        FROM tiles t
        LEFT JOIN media m ON t.bg_media_id = m.id
        WHERE t.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $tile = $stmt->fetch();
    
    if (!$tile) {
        jsonResponse(['error' => 'Tile not found'], 404);
        return;
    }
    
    jsonResponse(['tile' => formatTileResponse($tile)]);
}

function handleCreateTile(): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate CSRF
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    // Validate required fields
    $required = ['slug', 'title', 'target_url'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonResponse(['error' => "Field '$field' is required"], 400);
            return;
        }
    }
    
    // Validate slug format
    if (!preg_match('/^[a-z0-9-]+$/', $input['slug'])) {
        jsonResponse(['error' => 'Invalid slug format. Use lowercase letters, numbers, and hyphens only.'], 400);
        return;
    }
    
    // Validate URL
    if (!validateUrl($input['target_url'])) {
        jsonResponse(['error' => 'Invalid target URL'], 400);
        return;
    }
    
    // Validate hex color if provided
    if (!empty($input['accent_hex']) && !validateHex($input['accent_hex'])) {
        jsonResponse(['error' => 'Invalid hex color'], 400);
        return;
    }
    
    $db = getDatabase();
    
    // Get next order index
    $stmt = $db->query("SELECT MAX(order_index) as max_order FROM tiles");
    $maxOrder = $stmt->fetch()['max_order'] ?? 0;
    
    $stmt = $db->prepare("
        INSERT INTO tiles (slug, title, blurb, cta_label, target_url, bg_media_id, accent_hex, order_index, visible, publish_at)
        VALUES (:slug, :title, :blurb, :cta_label, :target_url, :bg_media_id, :accent_hex, :order_index, :visible, :publish_at)
    ");
    
    $stmt->execute([
        ':slug' => sanitizeInput($input['slug'], 50),
        ':title' => sanitizeInput($input['title'], 100),
        ':blurb' => sanitizeInput($input['blurb'] ?? '', 500),
        ':cta_label' => sanitizeInput($input['cta_label'] ?? 'Visit', 50),
        ':target_url' => $input['target_url'],
        ':bg_media_id' => $input['bg_media_id'] ?? null,
        ':accent_hex' => $input['accent_hex'] ?? null,
        ':order_index' => $maxOrder + 1,
        ':visible' => $input['visible'] ?? 1,
        ':publish_at' => $input['publish_at'] ?? null,
    ]);
    
    $id = $db->lastInsertId();
    
    logActivity('create', 'tile', $id, json_encode(['title' => $input['title']]));
    
    jsonResponse(['success' => true, 'id' => $id], 201);
}

function handleUpdateTile(string $id): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    // Validate slug format if provided
    if (isset($input['slug']) && !preg_match('/^[a-z0-9-]+$/', $input['slug'])) {
        jsonResponse(['error' => 'Invalid slug format. Use lowercase letters, numbers, and hyphens only.'], 400);
        return;
    }
    
    // Validate hex color if provided
    if (isset($input['accent_hex']) && !empty($input['accent_hex']) && !validateHex($input['accent_hex'])) {
        jsonResponse(['error' => 'Invalid hex color'], 400);
        return;
    }
    
    // Validate URL if provided
    if (isset($input['target_url']) && !validateUrl($input['target_url'])) {
        jsonResponse(['error' => 'Invalid target URL'], 400);
        return;
    }
    
    $db = getDatabase();
    
    // Build dynamic update query
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['slug', 'title', 'blurb', 'cta_label', 'target_url', 'bg_media_id', 'accent_hex', 'order_index', 'visible', 'publish_at'];
    
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $fields[] = "$field = :$field";
            
            if (in_array($field, ['slug', 'title', 'blurb', 'cta_label'])) {
                $params[":$field"] = sanitizeInput($input[$field], $field === 'blurb' ? 500 : 100);
            } else {
                $params[":$field"] = $input[$field];
            }
        }
    }
    
    $fields[] = "updated_at = datetime('now')";
    
    if (count($fields) === 1) { // Only updated_at
        jsonResponse(['error' => 'No fields to update'], 400);
        return;
    }
    
    $sql = "UPDATE tiles SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    logActivity('update', 'tile', $id);
    
    jsonResponse(['success' => true]);
}

function handleDeleteTile(string $id): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("DELETE FROM tiles WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    logActivity('delete', 'tile', $id);
    
    jsonResponse(['success' => true]);
}

function handleReorderTiles(): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    $ids = $input['ids'] ?? [];
    
    if (!is_array($ids)) {
        jsonResponse(['error' => 'Invalid ids array'], 400);
        return;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("UPDATE tiles SET order_index = :order WHERE id = :id");
    
    foreach ($ids as $order => $id) {
        // Validate that ID is numeric
        if (!is_numeric($id)) {
            continue;
        }
        $stmt->execute([':order' => (int)$order, ':id' => (int)$id]);
    }
    
    logActivity('reorder', 'tiles', null, json_encode($ids));
    
    jsonResponse(['success' => true]);
}

// ============================================================================
// MEDIA ENDPOINTS
// ============================================================================

function handleGetMedia(): void {
    startSecureSession();
    requireAuth();
    
    $db = getDatabase();
    $stmt = $db->query("
        SELECT id, original_name, path_original, path_webp, width, height, created_at
        FROM media
        ORDER BY created_at DESC
    ");
    
    $media = [];
    while ($row = $stmt->fetch()) {
        $media[] = [
            'id' => (int)$row['id'],
            'original_name' => $row['original_name'],
            'url' => '/api/media/serve/' . $row['id'],
            'width' => (int)$row['width'],
            'height' => (int)$row['height'],
            'created_at' => $row['created_at'],
        ];
    }
    
    jsonResponse(['media' => $media]);
}

function handleServeMedia(string $id): void {
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM media WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $media = $stmt->fetch();
    
    if (!$media) {
        http_response_code(404);
        echo 'Not found';
        return;
    }
    
    $format = $_GET['format'] ?? 'original';
    $path = $format === 'webp' ? $media['path_webp'] : $media['path_original'];
    
    if (!file_exists($path)) {
        http_response_code(404);
        echo 'File not found';
        return;
    }
    
    $mimeType = mime_content_type($path);
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=31536000, immutable');
    
    readfile($path);
}

function handleUploadMedia(): void {
    startSecureSession();
    requireAuth();
    
    // CSRF validation from POST data
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    // Rate limiting
    $userId = $_SESSION['user_id'];
    if (!checkRateLimit("upload:$userId", 20, 3600)) {
        jsonResponse(['error' => 'Upload limit exceeded'], 429);
        return;
    }
    
    if (!isset($_FILES['file'])) {
        jsonResponse(['error' => 'No file uploaded'], 400);
        return;
    }
    
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Upload failed'], 400);
        return;
    }
    
    $config = getConfig();
    $maxSize = $config['UPLOAD_MAX_MB'] * 1024 * 1024;
    
    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'File too large'], 400);
        return;
    }
    
    $allowedTypes = $config['UPLOAD_ALLOWED_TYPES'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        jsonResponse(['error' => 'Invalid file type'], 400);
        return;
    }
    
    // Process image
    try {
        $result = processImage($file['tmp_name'], $file['name']);
        
        $db = getDatabase();
        $stmt = $db->prepare("
            INSERT INTO media (original_name, path_original, path_webp, width, height, sizes_json)
            VALUES (:original_name, :path_original, :path_webp, :width, :height, :sizes_json)
        ");
        
        $stmt->execute([
            ':original_name' => $file['name'],
            ':path_original' => $result['path_original'],
            ':path_webp' => $result['path_webp'],
            ':width' => $result['width'],
            ':height' => $result['height'],
            ':sizes_json' => json_encode($result['sizes']),
        ]);
        
        $id = $db->lastInsertId();
        
        logActivity('upload', 'media', $id, $file['name']);
        
        jsonResponse([
            'success' => true,
            'id' => $id,
            'url' => '/api/media/serve/' . $id,
        ], 201);
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(['error' => 'Failed to process image'], 500);
    }
}

function handleDeleteMedia(string $id): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    $db = getDatabase();
    
    // Check if media is in use
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM tiles WHERE bg_media_id = :id");
    $stmt->execute([':id' => $id]);
    
    if ($stmt->fetch()['cnt'] > 0) {
        jsonResponse(['error' => 'Media is in use'], 400);
        return;
    }
    
    // Get paths
    $stmt = $db->prepare("SELECT path_original, path_webp FROM media WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $media = $stmt->fetch();
    
    if ($media) {
        @unlink($media['path_original']);
        @unlink($media['path_webp']);
        
        // Delete size variants
        $dir = dirname($media['path_original']);
        $base = pathinfo($media['path_original'], PATHINFO_FILENAME);
        foreach (glob("$dir/{$base}_*.*") as $file) {
            @unlink($file);
        }
    }
    
    $stmt = $db->prepare("DELETE FROM media WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    logActivity('delete', 'media', $id);
    
    jsonResponse(['success' => true]);
}

// ============================================================================
// SETTINGS ENDPOINTS
// ============================================================================

function handleGetSettings(): void {
    startSecureSession();
    requireAuth();
    
    $db = getDatabase();
    $stmt = $db->query("SELECT key, value FROM settings");
    
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
    
    jsonResponse(['settings' => $settings]);
}

function handleUpdateSettings(): void {
    startSecureSession();
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
        return;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)");
    
    foreach ($input['settings'] ?? [] as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
    
    logActivity('update', 'settings');
    
    jsonResponse(['success' => true]);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function formatTileResponse(array $row): array {
    return [
        'id' => (int)$row['id'],
        'slug' => $row['slug'],
        'title' => $row['title'],
        'blurb' => $row['blurb'],
        'cta_label' => $row['cta_label'],
        'target_url' => $row['target_url'],
        'accent_hex' => $row['accent_hex'],
        'order_index' => (int)$row['order_index'],
        'visible' => (bool)$row['visible'],
        'publish_at' => $row['publish_at'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'media' => isset($row['path_webp']) ? [
            'path_original' => '/api/media/serve/' . $row['bg_media_id'],
            'path_webp' => '/api/media/serve/' . $row['bg_media_id'] . '?format=webp',
            'width' => (int)$row['width'],
            'height' => (int)$row['height'],
        ] : null,
    ];
}

function processImage(string $tmpPath, string $originalName): array {
    $config = getConfig();
    $uploadPath = $config['UPLOAD_PATH'];
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename
    $hash = md5_file($tmpPath);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = $hash;
    
    // Load and validate image
    $img = @imagecreatefromstring(file_get_contents($tmpPath));
    
    if (!$img) {
        throw new Exception('Invalid image file');
    }
    
    // Get dimensions
    $width = imagesx($img);
    $height = imagesy($img);
    
    // Validate dimensions to prevent DoS
    if ($width > 10000 || $height > 10000) {
        imagedestroy($img);
        throw new Exception('Image dimensions too large (max 10000x10000)');
    }
    
    if ($width * $height > 25000000) { // 25 megapixels
        imagedestroy($img);
        throw new Exception('Image resolution too high (max 25 megapixels)');
    }
    
    // Calculate target dimensions (3:4 ratio)
    $targetRatio = $config['IMAGE_ASPECT_RATIO'];
    $currentRatio = $width / $height;
    
    if ($currentRatio > $targetRatio) {
        $targetWidth = $height * $targetRatio;
        $targetHeight = $height;
        $srcX = ($width - $targetWidth) / 2;
        $srcY = 0;
    } else {
        $targetWidth = $width;
        $targetHeight = $width / $targetRatio;
        $srcX = 0;
        $srcY = ($height - $targetHeight) / 2;
    }
    
    // Create cropped image
    $cropped = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($cropped, $img, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $targetWidth, $targetHeight);
    imagedestroy($img);
    
    // Save original
    $pathOriginal = "$uploadPath/{$basename}.$ext";
    imagejpeg($cropped, $pathOriginal, $config['IMAGE_QUALITY']);
    
    // Save WebP
    $pathWebp = "$uploadPath/{$basename}.webp";
    imagewebp($cropped, $pathWebp, $config['IMAGE_QUALITY']);
    
    // Generate responsive sizes
    $sizes = [];
    foreach ($config['IMAGE_SIZES'] as $size) {
        if ($size >= $targetWidth) continue;
        
        $ratio = $size / $targetWidth;
        $newHeight = $targetHeight * $ratio;
        
        $resized = imagecreatetruecolor($size, $newHeight);
        imagecopyresampled($resized, $cropped, 0, 0, 0, 0, $size, $newHeight, $targetWidth, $targetHeight);
        
        $sizePath = "$uploadPath/{$basename}_{$size}w.webp";
        imagewebp($resized, $sizePath, $config['IMAGE_QUALITY']);
        imagedestroy($resized);
        
        $sizes[] = ['width' => $size, 'path' => $sizePath];
    }
    
    imagedestroy($cropped);
    
    return [
        'path_original' => $pathOriginal,
        'path_webp' => $pathWebp,
        'width' => (int)$targetWidth,
        'height' => (int)$targetHeight,
        'sizes' => $sizes,
    ];
}