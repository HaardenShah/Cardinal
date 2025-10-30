# Cardinal

A modern, secure portfolio landing hub featuring an animated split-panel gallery with a full-featured admin panel for managing your project showcases.

![Version](https://img.shields.io/badge/version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## Overview

Cardinal provides a beautiful, animated landing page where visitors can explore your portfolio through an interactive split-panel gallery. Each panel can link to your projects, subdomains, or external sites. The admin panel gives you complete control over content, media, and site settings.

## Features

### Public Site
- **Split-Panel Gallery** - Smooth hover effects with expansion animations
- **Mobile Responsive** - Adaptive layout with bottom drawer on mobile devices
- **GPU-Accelerated** - Vanilla JavaScript animations with hardware acceleration
- **Accessibility** - Keyboard navigation, ARIA labels, respects `prefers-reduced-motion`
- **Performance** - Lazy loading, WebP images, responsive srcset, optimized delivery
- **SEO Optimized** - Semantic HTML, JSON-LD schema, meta tags

### Admin Panel
- **Tile Management** - Full CRUD operations: create, edit, delete, reorder
- **Drag-and-Drop** - Intuitive reordering with visual feedback
- **Media Library** - Upload, optimize, and manage images with automatic WebP conversion
- **Settings Editor** - Customize site title, colors, hero text, and behavior
- **Activity Logging** - Comprehensive audit trail of all admin actions
- **Live Preview** - See changes before publishing

### Security Features
- **Argon2id Password Hashing** - Industry-standard password protection
- **CSRF Protection** - Token validation on all state-changing requests
- **Rate Limiting** - Protection against brute force attacks
- **Session Security** - HttpOnly cookies, session regeneration, secure flags
- **Input Validation** - Strict validation on all user inputs
- **SQL Injection Protection** - Prepared statements with PDO
- **Image Validation** - Memory exhaustion prevention, dimension limits
- **Timing Attack Mitigation** - Consistent response times for auth failures
- **Security Headers** - CSP, X-Frame-Options, X-Content-Type-Options

## Tech Stack

**Frontend**
- HTML5, CSS3, Vanilla JavaScript (ES6+)
- No external dependencies
- GPU-accelerated animations

**Backend**
- PHP 8.2+
- SQLite 3 with WAL mode
- PDO for database operations

**Image Processing**
- PHP GD library
- WebP conversion
- Responsive image generation (480, 768, 1080, 1440, 1920px)
- Automatic cropping to 3:4 aspect ratio

## Requirements

- **PHP 8.2 or higher**
- **PHP Extensions**: GD, PDO, SQLite3
- **Web Server**: Apache (with mod_rewrite) or Nginx
- **HTTPS** (recommended for production)

## Quick Start

### 1. Upload Files

Upload all project files to your web server. Set your document root to the `/public` directory.

### 2. Run Setup Wizard

Navigate to your site in a web browser. You'll be automatically redirected to the setup wizard.

```
https://yoursite.com
```

The wizard will guide you through:

1. **Site Information** - Title, URL, hero text, subtitle
2. **Admin Account** - Email and password setup
3. **Theme Colors** - Primary and secondary brand colors
4. **Completion** - Automatic database and configuration creation

### 3. Set Permissions

After setup, ensure proper file permissions:

```bash
chmod 755 /path/to/cardinal
chmod 775 /path/to/cardinal/data
chmod 775 /path/to/cardinal/uploads
chmod 775 /path/to/cardinal/backups
chmod 775 /path/to/cardinal/config
```

### 4. Configure Web Server

#### Apache

Create or verify `/public/.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route API calls
RewriteRule ^api/(.*)$ ../api/index.php [L]

# Route admin calls
RewriteRule ^admin/(.*)$ ../admin/$1 [L]

# Default to index
RewriteRule ^ index.php [L]
```

#### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name yoursite.com;
    root /path/to/cardinal/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # API routing
    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # Admin routing
    location /admin/ {
        try_files $uri $uri/ /admin/$1;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|webp|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5. Access Admin Panel

```
https://yoursite.com/admin/login
```

Login with the credentials you created during setup.

## File Structure

```
cardinal/
├── public/                      # Web root (document root)
│   └── index.php               # Public landing page
│
├── admin/                       # Admin panel
│   ├── login.php              # Authentication
│   ├── tiles.php              # Tile management
│   ├── media.php              # Media library
│   ├── settings.php           # Site settings
│   ├── preview.php            # Live preview
│   └── assets/
│       └── admin.css          # Admin styling
│
├── api/                         # REST API
│   └── index.php              # API router
│
├── app/                         # Core application
│   ├── bootstrap.php          # Bootstrap & helpers
│   └── schema.sql             # Database schema
│
├── config/                      # Configuration (created by setup)
│   └── config.php             # Generated config file
│
├── data/                        # Data storage (created by setup)
│   ├── site.db                # SQLite database
│   ├── error.log              # Error logs
│   └── setup-error.log        # Setup errors
│
├── uploads/                     # Media files (created by setup)
├── backups/                     # DB backups (created by setup)
│
├── index.php                    # Main router
├── setup.php                    # Setup wizard
├── backup.php                   # Backup utility
├── check-tiles.php              # Debug helper
└── README.md                    # This file
```

## Database Schema

Cardinal uses SQLite with 6 optimized tables:

| Table | Purpose |
|-------|---------|
| `tiles` | Portfolio panels with metadata |
| `media` | Image files with responsive variants |
| `users` | Admin accounts |
| `settings` | Site configuration key-value pairs |
| `activity_log` | Audit trail for admin actions |
| `rate_limits` | Rate limiting tracking |

All tables include appropriate indexes for optimal query performance.

## Usage Guide

### Creating Your First Tile

1. Navigate to **Admin → Media**
2. Upload an image (JPG, PNG, or WebP)
   - Automatically cropped to 3:4 ratio
   - Converted to WebP format
   - Multiple responsive sizes generated
3. Navigate to **Admin → Tiles**
4. Click **+ New Tile**
5. Fill in the form:
   - **Slug**: URL-friendly identifier (e.g., `my-project`)
   - **Title**: Display name (e.g., `My Project`)
   - **Blurb**: Short description (1-2 sentences)
   - **CTA Label**: Button text (default: "Visit")
   - **Target URL**: Destination link
   - **Accent Color**: Hex color for text overlay (e.g., `#6366f1`)
   - **Background Image**: Select from media library
   - **Visible**: Toggle visibility
   - **Publish Date**: Optional scheduled publishing
6. Click **Save**

### Reordering Tiles

Simply drag and drop tiles in the tiles list. Changes are saved automatically.

### Managing Media

- **Upload**: Drag and drop or click to browse
- **Delete**: Click trash icon (warns if used by tiles)
- **Automatic Optimization**:
  - Cropped to 3:4 aspect ratio
  - Converted to WebP (75% quality)
  - Responsive variants: 480, 768, 1080, 1440, 1920px
  - EXIF data stripped for privacy

### Customizing Settings

Navigate to **Admin → Settings** to configure:

- **Site Identity**: Title, description, meta tags
- **Hero Section**: Main text and subtitle
- **Brand Colors**: Primary and secondary colors
- **Behavior**: Autoplay, animation speed, link target
- **Admin Account**: Change email or password

## API Reference

### Public Endpoints

```
GET  /api/public/tiles      Get all visible tiles
GET  /api/health           System health check
```

### Authentication

```
POST /api/auth/login       Login (rate limited: 5 attempts/15min)
POST /api/auth/logout      Logout
```

### Tiles (Authentication Required)

```
GET    /api/tiles          List all tiles
GET    /api/tiles/{id}     Get specific tile
POST   /api/tiles          Create new tile
PUT    /api/tiles/{id}     Update tile
DELETE /api/tiles/{id}     Delete tile
PATCH  /api/tiles/reorder  Batch reorder tiles
```

### Media (Authentication Required)

```
GET    /api/media          List all media
POST   /api/media          Upload image (rate limited: 20 attempts/15min)
GET    /api/media/serve/{id}  Serve image file
DELETE /api/media/{id}     Delete media
```

### Settings (Authentication Required)

```
GET  /api/settings         Get all settings
PUT  /api/settings         Update settings (batch)
```

All authenticated endpoints require a valid session and CSRF token.

## Backup & Restore

### Manual Backup

```bash
php backup.php
```

Creates a timestamped backup in `/backups` containing:
- Database snapshot (`site.db`)
- Uploads directory

### Automated Backup (Cron)

Add to your crontab for daily backups at 3 AM:

```bash
0 3 * * * /usr/bin/php /path/to/cardinal/backup.php
```

Backups are automatically retained for 14 days (configurable in settings).

### Restore from Backup

```bash
# Restore database
cp backups/backup-YYYYMMDD-HHMMSS/site.db data/site.db

# Restore uploads
cp -r backups/backup-YYYYMMDD-HHMMSS/uploads/* uploads/
```

## Customization

### Changing Panel Aspect Ratio

Edit `/config/config.php`:

```php
'IMAGE_ASPECT_RATIO' => 0.75,  // 3:4 (change to 0.5625 for 16:9)
```

### Adjusting Upload Limits

Edit `/config/config.php`:

```php
'UPLOAD_MAX_MB' => 10,  // Maximum file size in megabytes
```

Also update your `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Modifying Animation Speed

Edit the transition timing in `/public/index.php` CSS:

```css
transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
```

### Adding Custom CSS

Add custom styles to `/admin/assets/admin.css` or inline in `/public/index.php`.

## Performance Optimization

Cardinal is built for speed:

- **Critical CSS Inlined** - No render-blocking stylesheets
- **Lazy Loading** - Images load as they enter viewport
- **WebP Format** - Modern compression with fallbacks
- **Responsive Images** - Appropriate size served per device
- **GPU Acceleration** - Transform and opacity animations
- **WAL Mode** - SQLite write-ahead logging for concurrency
- **Cache Headers** - Long-term caching for static assets

### Lighthouse Targets

- Performance: 90+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 100

## Troubleshooting

### Setup Wizard Loops

If the setup wizard redirects infinitely:
- Check that `/config/config.php` was created
- Verify web server has write permissions to `/config`
- Check `/data/setup-error.log` for errors

### Database Locked Errors

- Ensure web server has write permissions to `/data`
- Verify no other process is using `site.db`
- Check that WAL mode is enabled (automatic in setup)

### Images Not Displaying

- Verify GD extension: `php -m | grep gd`
- Check file permissions on `/uploads`
- Ensure web server can serve files from `/uploads`
- Check browser console for CORS or 404 errors

### Upload Failures

- Verify PHP `upload_max_filesize` and `post_max_size` settings
- Check `/data/error.log` for detailed errors
- Ensure `/uploads` directory exists and is writable
- Verify image meets size and dimension limits

### Login Issues

- Clear browser cookies and cache
- Verify credentials in database
- Check for rate limiting (5 attempts per 15 minutes)
- Review `/data/error.log` for authentication errors

### Rate Limit Lockout

Rate limits automatically reset after the time window:
- **Login**: 5 attempts per 15 minutes
- **Media Upload**: 20 uploads per 15 minutes

Check `/data/site.db` → `rate_limits` table or wait 15 minutes.

## Security Best Practices

### Post-Installation Security Checklist

- [ ] Change admin password immediately after first login
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Set `SESSION_SECURE` to `true` in `/config/config.php`
- [ ] Verify file permissions (755 for directories, 644 for files)
- [ ] Enable firewall rules to restrict admin panel access
- [ ] Set up automated backups
- [ ] Monitor `/data/error.log` regularly
- [ ] Keep PHP version updated
- [ ] Review activity log in admin panel periodically

### Recommended `.gitignore`

```gitignore
/config/config.php
/data/
/uploads/
/backups/
.env
*.log
```

## Deployment

### Via rsync

```bash
rsync -avz --exclude 'data/' --exclude 'uploads/' --exclude 'config/' \
  /local/cardinal/ user@server:/path/to/cardinal/
```

### Via Git

```bash
git clone https://github.com/yourusername/cardinal.git
cd cardinal
# Run setup wizard via browser
```

### Post-Deployment Checklist

1. [ ] Upload all files to server
2. [ ] Set document root to `/public`
3. [ ] Run setup wizard
4. [ ] Set file permissions
5. [ ] Configure web server (Apache/Nginx)
6. [ ] Enable HTTPS
7. [ ] Test `/api/health` endpoint
8. [ ] Create first tile and verify display
9. [ ] Set up automated backups
10. [ ] Configure DNS and CDN (optional)

## Development

### Running Locally

```bash
# Using PHP built-in server
cd /path/to/cardinal/public
php -S localhost:8000

# Visit http://localhost:8000
```

### Debugging

Enable debug mode in `/config/config.php`:

```php
'APP_ENV' => 'development',
```

Check logs:
- **General Errors**: `/data/error.log`
- **Setup Errors**: `/data/setup-error.log`

Use the debug helper:

```bash
php check-tiles.php
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

### Version 2.0 (Current)

- Added interactive setup wizard
- Implemented comprehensive security hardening
- Added image dimension and resolution validation
- Improved database indexing for performance
- Added activity logging and audit trails
- Enhanced rate limiting with database transactions
- Fixed session fixation vulnerabilities
- Added timing attack mitigation
- Improved error handling and logging

### Version 1.0

- Initial release
- Split-panel gallery
- Admin panel with CRUD operations
- Media library
- SQLite database

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or feature requests:

1. Check `/data/error.log` for technical errors
2. Review this README and troubleshooting section
3. Test the `/api/health` endpoint
4. Open an issue on GitHub with:
   - PHP version (`php -v`)
   - Web server type and version
   - Error logs
   - Steps to reproduce

## Credits

Built with modern web standards emphasizing:
- **Performance** - Fast, optimized delivery
- **Security** - Industry-standard protection
- **Accessibility** - WCAG 2.1 AA compliance
- **Simplicity** - No external dependencies

---

**Cardinal** - Showcase your work beautifully.
