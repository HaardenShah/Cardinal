<?php
/**
 * Environment Configuration
 * Copy to .env.php and customize for your environment
 */

return [
    'APP_ENV' => 'production', // production, development
    'APP_URL' => 'https://yourname.com',
    'APP_NAME' => 'Your Name',
    
    // Database
    'DB_PATH' => __DIR__ . '/data/site.db',
    
    // Security
    'SESSION_SECURE' => true, // true for HTTPS
    'SESSION_NAME' => 'hub_session',
    'CSRF_TOKEN_NAME' => 'csrf_token',
    
    // Content Security Policy
    'CSP_REPORT_ONLY' => false,
    'CSP_REPORT_URI' => null,
    
    // Upload settings
    'UPLOAD_MAX_MB' => 10,
    'UPLOAD_PATH' => __DIR__ . '/uploads',
    'UPLOAD_ALLOWED_TYPES' => ['image/jpeg', 'image/png', 'image/webp'],
    
    // Image processing
    'IMAGE_QUALITY' => 75,
    'IMAGE_SIZES' => [480, 768, 1080, 1440, 1920],
    'IMAGE_ASPECT_RATIO' => 0.75, // 3:4 ratio
    
    // Rate limiting
    'RATE_LIMIT_LOGIN' => 5, // attempts per 15 minutes
    'RATE_LIMIT_MEDIA' => 20, // uploads per hour
    
    // Backup
    'BACKUP_PATH' => __DIR__ . '/backups',
    'BACKUP_KEEP_DAYS' => 14,
    
    // Admin seed (change this!)
    'ADMIN_EMAIL_SEED' => 'admin@yourname.com',
    'ADMIN_PASSWORD_SEED' => 'change-me-immediately',
    
    // Analytics
    'ANALYTICS_ID' => '', // Google Analytics or similar
    'RESPECT_DNT' => true,
];
