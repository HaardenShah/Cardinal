<?php
/**
 * Application Bootstrap
 * Loaded by all entry points
 */

// Error reporting
if (getenv('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Load configuration
function getConfig(): array {
    static $config = null;
    if ($config === null) {
        // Check if config exists (post-setup)
        $configPath = __DIR__ . '/../config/config.php';
        
        if (file_exists($configPath)) {
            // Load actual config
            $config = require $configPath;
        } else {
            // Return defaults for setup wizard
            $config = [
                'APP_ENV' => 'production',
                'APP_URL' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
                'APP_NAME' => 'Portfolio Hub',
                'DB_PATH' => __DIR__ . '/../data/site.db',
                'SESSION_SECURE' => false,
                'SESSION_NAME' => 'hub_session',
                'CSRF_TOKEN_NAME' => 'csrf_token',
                'CSP_REPORT_ONLY' => false,
                'CSP_REPORT_URI' => null,
                'UPLOAD_MAX_MB' => 10,
                'UPLOAD_PATH' => __DIR__ . '/../uploads',
                'UPLOAD_ALLOWED_TYPES' => ['image/jpeg', 'image/png', 'image/webp'],
                'IMAGE_QUALITY' => 75,
                'IMAGE_SIZES' => [480, 768, 1080, 1440, 1920],
                'IMAGE_ASPECT_RATIO' => 0.75,
                'RATE_LIMIT_LOGIN' => 5,
                'RATE_LIMIT_MEDIA' => 20,
                'BACKUP_PATH' => __DIR__ . '/../backups',
                'BACKUP_KEEP_DAYS' => 14,
                'ANALYTICS_ID' => '',
                'RESPECT_DNT' => true,
            ];
        }
    }
    return $config;
}

// Database connection
function getDatabase(): PDO {
    static $db = null;
    if ($db === null) {
        $config = getConfig();
        $dbPath = $config['DB_PATH'];
        
        // Ensure directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Enable foreign keys
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('PRAGMA wal_autocheckpoint = 1000');
        $db->exec('PRAGMA journal_size_limit = 67110000'); // 64MB
    }
    return $db;
}

// Session management
function startSecureSession(): void {
    $config = getConfig();
    
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        if ($config['SESSION_SECURE']) {
            ini_set('session.cookie_secure', 1);
        }
        
        session_name($config['SESSION_NAME']);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// CSRF Protection
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Authentication
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        http_response_code(401);
        jsonResponse(['error' => 'Authentication required'], 401);
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("SELECT id, email, role, last_login_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// JSON Response helper
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_THROW_ON_ERROR);
}

// Input sanitization
function sanitizeInput(string $input, int $maxLength = 1000): string {
    $input = trim($input);
    $input = substr($input, 0, $maxLength);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function validateUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function validateHex(string $hex): bool {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) === 1;
}

// Rate limiting with transaction support
function checkRateLimit(string $key, int $limit, int $window = 900): bool {
    $db = getDatabase();
    
    try {
        $db->beginTransaction();
        
        $cutoff = date('Y-m-d H:i:s', time() - $window);
        
        // Clean old entries
        $db->exec("DELETE FROM rate_limits WHERE created_at < '$cutoff'");
        
        // Check current count
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM rate_limits WHERE key = :key AND created_at > :cutoff");
        $stmt->execute([':key' => $key, ':cutoff' => $cutoff]);
        $count = $stmt->fetch()['cnt'];
        
        if ($count >= $limit) {
            $db->rollBack();
            return false;
        }
        
        // Record attempt
        $stmt = $db->prepare("INSERT INTO rate_limits (key, created_at) VALUES (:key, datetime('now'))");
        $stmt->execute([':key' => $key]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Rate limit error: " . $e->getMessage());
        return false;
    }
}

// Create rate limits table if needed
function ensureRateLimitTable(): void {
    $db = getDatabase();
    $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_rate_limits_key ON rate_limits(key, created_at)");
}

ensureRateLimitTable();

// Activity logging
function logActivity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    $db = getDatabase();
    $userId = $_SESSION['user_id'] ?? null;
    
    $stmt = $db->prepare("
        INSERT INTO activity_log (user_id, action, entity_type, entity_id, details) 
        VALUES (:user_id, :action, :entity_type, :entity_id, :details)
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':details' => $details,
    ]);
}

// Error handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $logMessage = sprintf(
        "[%s] %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $message,
        $file,
        $line
    );
    
    error_log($logMessage, 3, __DIR__ . '/../data/error.log');
    
    if (getConfig()['APP_ENV'] === 'production') {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
    
    return false;
});

// Set security headers
function setSecurityHeaders(): void {
    $config = getConfig();
    
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    $csp = "default-src 'self'; " .
           "script-src 'self' https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline'; " .
           "img-src 'self' data: https:; " .
           "font-src 'self'; " .
           "object-src 'none'; " .
           "base-uri 'none'; " .
           "form-action 'self'";
    
    if ($config['CSP_REPORT_URI']) {
        $csp .= "; report-uri " . $config['CSP_REPORT_URI'];
    }
    
    $headerName = $config['CSP_REPORT_ONLY'] ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
    header($headerName . ': ' . $csp);
}