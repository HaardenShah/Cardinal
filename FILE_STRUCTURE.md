# Portfolio Hub - Complete File Structure

## 📂 Directory Tree

```
portfolio-hub/
│
├── 📄 .env.php                    # Configuration (EDIT THIS FIRST!)
├── 📄 README.md                   # Complete documentation
├── 📄 QUICKSTART.md               # 5-minute setup guide
├── 📄 TEST_PLAN.md                # 200+ test cases
├── 📄 init-db.php                 # Database initialization script
├── 📄 seed-demo.php               # Demo data seeder
├── 📄 backup.php                  # Backup script (cron-ready)
├── 🔧 deploy.sh                   # Deployment script
│
├── 📁 public/                     # ← WEB SERVER DOCUMENT ROOT
│   ├── 📄 index.php              # Main landing page (animated gallery)
│   └── 📄 .htaccess              # Apache rewrite rules
│
├── 📁 admin/                      # Admin panel
│   ├── 📄 login.php              # Authentication page
│   ├── 📄 tiles.php              # Tile management (CRUD + reorder)
│   ├── 📄 media.php              # Media library (upload/delete)
│   ├── 📄 settings.php           # Site configuration
│   ├── 📄 preview.php            # Draft preview
│   └── 📁 assets/
│       └── 📄 admin.css          # Admin interface styles
│
├── 📁 api/                        # Backend API
│   └── 📄 index.php              # API router + all endpoints
│
├── 📁 app/                        # Core application
│   └── 📄 bootstrap.php          # Bootstrap, helpers, security
│
├── 📁 data/                       # Database & logs (created on init)
│   ├── 📄 site.db                # SQLite database (created)
│   └── 📄 error.log              # PHP error log (created)
│
├── 📁 uploads/                    # Media files (created on first upload)
│   └── 📷 [images]               # Original + WebP + responsive sizes
│
└── 📁 backups/                    # Database backups (created by backup.php)
    └── 📦 database_*.db          # Timestamped backups
```

## 📋 File Purposes

### Configuration & Setup
- **`.env.php`** - Main configuration (URLs, DB path, security, upload limits)
- **`init-db.php`** - Run once to create database and admin user
- **`seed-demo.php`** - Optional demo data (4 example tiles)

### Public Site
- **`public/index.php`** - The animated panel gallery your visitors see
- **`public/.htaccess`** - URL rewrites, security headers, caching

### Admin Panel (Secure Area)
- **`admin/login.php`** - Login form with rate limiting
- **`admin/tiles.php`** - Manage tiles: create, edit, delete, reorder
- **`admin/media.php`** - Upload & manage images
- **`admin/settings.php`** - Configure site, colors, behavior
- **`admin/preview.php`** - Preview changes before publishing
- **`admin/assets/admin.css`** - Admin UI styles

### Backend
- **`api/index.php`** - All API endpoints (auth, tiles, media, settings)
- **`app/bootstrap.php`** - Security, database, sessions, helpers

### Maintenance
- **`backup.php`** - Manual or cron backup script
- **`deploy.sh`** - Deploy to server via rsync

### Documentation
- **`README.md`** - Complete technical documentation
- **`QUICKSTART.md`** - Get running in 5 minutes
- **`TEST_PLAN.md`** - Comprehensive testing checklist

## 🗄️ Database Tables

Created by `init-db.php`:

```
site.db
├── tiles              # Panel content
│   ├── id, slug, title, blurb
│   ├── cta_label, target_url
│   ├── bg_media_id → media.id
│   ├── accent_hex, order_index
│   └── visible, publish_at
│
├── media              # Image library
│   ├── id, original_name
│   ├── path_original, path_webp
│   ├── width, height, sizes_json
│   └── created_at
│
├── users              # Admin accounts
│   ├── id, email, password_hash
│   ├── role (admin/editor)
│   └── created_at, last_login_at
│
├── settings           # Site configuration
│   ├── key (primary key)
│   └── value
│
└── activity_log       # Audit trail
    ├── id, user_id, action
    ├── entity_type, entity_id
    └── details, created_at
```

## 📡 API Endpoints

### Public (No Authentication)
```
GET  /api/public/tiles    # Get visible tiles for gallery
GET  /api/health          # Health check
```

### Authentication
```
POST /api/auth/login      # Login (returns session)
POST /api/auth/logout     # Logout
```

### Tiles (Requires Auth)
```
GET    /api/tiles         # List all tiles
GET    /api/tiles/{id}    # Get single tile
POST   /api/tiles         # Create tile
PUT    /api/tiles/{id}    # Update tile
DELETE /api/tiles/{id}    # Delete tile
PATCH  /api/tiles/reorder # Reorder tiles
```

### Media (Requires Auth)
```
GET    /api/media             # List media
GET    /api/media/serve/{id}  # Serve image file
POST   /api/media             # Upload image
DELETE /api/media/{id}        # Delete image
```

### Settings (Requires Auth)
```
GET /api/settings         # Get all settings
PUT /api/settings         # Update settings
```

## 🎨 CSS Architecture

### Public Site Styles
Inline in `public/index.php`:
- CSS variables for theming
- Gallery grid and panels
- Hover effects and animations
- Info drawer
- Responsive breakpoints
- Reduced motion support

### Admin Styles
In `admin/assets/admin.css`:
- Layout (sidebar + content)
- Form components
- Tiles grid
- Media picker
- Modals
- Buttons and icons
- Responsive admin layout

## 🔧 Configuration Keys

In `.env.php`:

```php
// Application
'APP_ENV' => 'production'          # production | development
'APP_URL' => 'https://yourname.com'
'APP_NAME' => 'Your Name'

// Database
'DB_PATH' => __DIR__ . '/data/site.db'

// Security
'SESSION_SECURE' => true           # true for HTTPS
'SESSION_NAME' => 'hub_session'
'CSRF_TOKEN_NAME' => 'csrf_token'
'CSP_REPORT_ONLY' => false

// Uploads
'UPLOAD_MAX_MB' => 10              # Max file size
'UPLOAD_PATH' => __DIR__ . '/uploads'
'IMAGE_QUALITY' => 75              # WebP quality (1-100)
'IMAGE_SIZES' => [480, 768, 1080, 1440, 1920]
'IMAGE_ASPECT_RATIO' => 0.75       # 3:4 ratio

// Rate Limiting
'RATE_LIMIT_LOGIN' => 5            # Attempts per 15 min
'RATE_LIMIT_MEDIA' => 20           # Uploads per hour

// Backup
'BACKUP_PATH' => __DIR__ . '/backups'
'BACKUP_KEEP_DAYS' => 14

// Admin Seed
'ADMIN_EMAIL_SEED' => 'admin@yourname.com'
'ADMIN_PASSWORD_SEED' => 'change-me'  # CHANGE THIS!

// Analytics
'ANALYTICS_ID' => ''               # Optional
'RESPECT_DNT' => true
```

## 📏 Code Metrics

```
Total Files:       20+
Lines of Code:     ~3,500+
PHP Files:         11
JavaScript:        ~800 lines
CSS:               ~1,200 lines
Documentation:     ~2,000 lines

Functions:         50+
API Endpoints:     15
Database Tables:   5
Admin Pages:       4
Test Cases:        200+
```

## 🔐 Security Layers

```
Input Layer:
├── Sanitization (htmlspecialchars, trim, length limits)
├── Validation (URL, email, hex, MIME type)
└── Type checking

Auth Layer:
├── Argon2id password hashing
├── Session security (HttpOnly, Secure, SameSite)
├── CSRF token validation
└── Rate limiting

Database Layer:
├── Prepared statements (no SQL injection)
├── Foreign key constraints
└── Transaction atomicity

Output Layer:
├── Content Security Policy
├── Security headers
├── Output encoding
└── Cache control

File Layer:
├── MIME validation
├── Size limits
├── EXIF stripping
└── Safe filename generation
```

## 🚀 Deployment Checklist

```
Pre-Deploy:
☐ Update .env.php with production values
☐ Test locally
☐ Run test suite
☐ Backup existing site

Deploy:
☐ Upload files
☐ Set permissions (755/775)
☐ Run init-db.php
☐ Configure web server
☐ Enable HTTPS
☐ Test /api/health

Post-Deploy:
☐ Change admin password
☐ Upload first tile
☐ Test public page
☐ Test mobile
☐ Run Lighthouse audit
☐ Set up backups
☐ Monitor logs
```

## 💡 Key Features Map

```
Public Features:
├── Animated panel gallery
├── Hover effects (3D tilt)
├── Click-to-expand
├── Info drawer (desktop: right, mobile: bottom)
├── Autoplay carousel (optional)
├── Keyboard navigation
├── Lazy loading
├── WebP images
└── Responsive layout

Admin Features:
├── CRUD tiles
├── Drag-and-drop reordering
├── Media library
├── Image upload & optimization
├── Settings configuration
├── Activity logging
├── Data export
├── Live preview
└── Scheduled publishing

Technical Features:
├── SQLite database
├── RESTful API
├── Argon2id passwords
├── CSRF protection
├── Rate limiting
├── Session management
├── Error logging
├── Automated backups
└── Health monitoring
```

---

**Installation**: Start with `QUICKSTART.md`  
**Deep Dive**: Read `README.md`  
**Testing**: Follow `TEST_PLAN.md`

Ready to launch! 🎉
