# Portfolio Hub - Split-Panel Gallery

A modern, animated portfolio landing hub with an admin panel for managing tiles/panels that link to your sub-domains.

## Features

✨ **Public Site**
- Smooth split-panel gallery with hover effects and expansion animations
- Mobile-responsive with bottom drawer on mobile
- GSAP-free vanilla JS animations with GPU acceleration
- Respects `prefers-reduced-motion`
- Optional auto-cycle through panels
- WebP images with responsive srcset
- Keyboard accessible
- SEO optimized with JSON-LD schema

🔐 **Admin Panel**
- Full CRUD for tiles (create, edit, delete, reorder)
- Drag-and-drop reordering
- Media library with upload, optimization, and WebP generation
- Settings management
- Activity logging
- CSRF protection
- Rate limiting on auth and uploads
- Argon2id password hashing

## Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6)
- **Backend**: PHP 8.2+
- **Database**: SQLite 3
- **Image Processing**: GD library
- **Security**: Argon2id, CSRF tokens, CSP headers, rate limiting

## Requirements

- PHP 8.2 or higher
- PHP GD extension
- SQLite 3
- Apache or Nginx with mod_rewrite
- HTTPS (recommended for production)

## Installation

### 1. Clone/Upload Files

Upload all files to your web server. The document root should point to the `/public` directory.

### 2. Configure Environment

Copy `.env.php` and update the values:

```php
'APP_ENV' => 'production',
'APP_URL' => 'https://yourname.com',
'ADMIN_EMAIL_SEED' => 'admin@yourname.com',
'ADMIN_PASSWORD_SEED' => 'your-secure-password',
'SESSION_SECURE' => true, // Set true if using HTTPS
```

### 3. Set Permissions

```bash
chmod 755 /path/to/project
chmod 775 /path/to/project/data
chmod 775 /path/to/project/uploads
chmod 775 /path/to/project/backups
```

### 4. Initialize Database

```bash
php init-db.php
```

This will:
- Create all database tables
- Insert default settings
- Create the admin user

**⚠️ IMPORTANT**: Change the admin password immediately after first login!

### 5. Configure Web Server

#### Apache (.htaccess)

Create `/public/.htaccess`:

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
    listen 80;
    server_name yourname.com;
    root /path/to/project/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # API routing
    location /api/ {
        rewrite ^/api/(.*)$ /api/index.php last;
    }

    # Admin routing
    location /admin/ {
        try_files $uri $uri/ /admin/$uri.php;
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

## Usage

### Admin Access

1. Navigate to `https://yourname.com/admin/login`
2. Login with your admin credentials
3. Change password in Settings

### Creating Tiles

1. Go to **Tiles** in admin
2. Click **+ New Tile**
3. Fill in:
   - **Slug**: URL-friendly identifier
   - **Title**: Display name
   - **Description**: Short blurb (1-2 sentences)
   - **Button Label**: CTA text (default: "Visit")
   - **Target URL**: Your sub-domain or external link
   - **Accent Color**: Brand color for text overlay
   - **Background Image**: Choose from media library
   - **Visible**: Toggle visibility
   - **Publish Date**: Optional scheduled publishing

4. Click **Save**

### Uploading Media

1. Go to **Media** in admin
2. Click or drag images into the upload zone
3. Images are automatically:
   - Cropped to 3:4 ratio
   - Converted to WebP
   - Resized to responsive variants (480, 768, 1080, 1440, 1920)
   - Optimized to 75% quality

### Reordering Tiles

Simply drag and drop tiles in the admin tiles page. Order is saved automatically.

### Settings

Configure:
- Site title and description
- Hero text and subtitle
- Brand colors
- Autoplay settings
- Animation speed
- Open links in new tab

## File Structure

```
project/
├── public/             # Web root
│   └── index.php       # Public landing page
├── api/                # API endpoints
│   └── index.php       # API router
├── admin/              # Admin panel
│   ├── login.php
│   ├── tiles.php
│   ├── media.php
│   ├── settings.php
│   └── assets/
│       └── admin.css
├── app/                # Core application
│   └── bootstrap.php   # Bootstrap & helpers
├── data/               # SQLite database & logs
│   └── site.db
├── uploads/            # Media files
├── backups/            # Database backups
├── .env.php            # Configuration
└── init-db.php         # Database initialization
```

## API Endpoints

### Public
- `GET /api/public/tiles` - Get visible tiles
- `GET /api/health` - Health check

### Auth (requires authentication)
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout

### Tiles (requires authentication)
- `GET /api/tiles` - List all tiles
- `GET /api/tiles/{id}` - Get tile
- `POST /api/tiles` - Create tile
- `PUT /api/tiles/{id}` - Update tile
- `DELETE /api/tiles/{id}` - Delete tile
- `PATCH /api/tiles/reorder` - Reorder tiles

### Media (requires authentication)
- `GET /api/media` - List media
- `GET /api/media/serve/{id}` - Serve media file
- `POST /api/media` - Upload media
- `DELETE /api/media/{id}` - Delete media

### Settings (requires authentication)
- `GET /api/settings` - Get all settings
- `PUT /api/settings` - Update settings

## Security

### Authentication
- Passwords hashed with Argon2id
- Secure session management with HttpOnly cookies
- Session ID regeneration
- Rate limiting on login (5 attempts per 15 minutes)

### CSRF Protection
- All state-changing requests require CSRF token
- Token validated server-side

### File Uploads
- MIME type validation
- File size limits (10MB default)
- Images re-encoded to strip EXIF
- Stored with content-hash filenames

### Headers
- Content Security Policy
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy

## Performance

### Optimizations
- Critical CSS inlined
- Lazy loading for images
- WebP format with fallbacks
- Responsive srcset
- GPU-accelerated animations
- WAL mode for SQLite
- Cache headers for static assets

### Lighthouse Scores Target
- Performance: 90+
- Accessibility: 90+
- Best Practices: 90+
- SEO: 100

## Backup

### Manual Backup

```bash
php backup.php
```

### Automated Backup (Cron)

```bash
# Add to crontab: daily backup at 3 AM
0 3 * * * /usr/bin/php /path/to/project/backup.php
```

Backups are stored in `/backups` and kept for 14 days.

## Troubleshooting

### Database Locked
- Ensure web server has write permissions to `/data`
- Check if another process is using the database
- Verify WAL mode is enabled

### Images Not Uploading
- Check PHP upload_max_filesize and post_max_size
- Verify GD extension is installed: `php -m | grep gd`
- Ensure `/uploads` has write permissions

### Session Issues
- Verify session.cookie_secure matches HTTPS usage
- Check session storage permissions
- Clear browser cookies

### Rate Limit Errors
- Rate limits reset after time window
- Check logs in `/data/error.log`

## Customization

### Animation Speed

Edit in Settings or modify `/public/index.php`:

```javascript
transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
```

### Panel Ratios

Edit in `bootstrap.php`:

```php
'IMAGE_ASPECT_RATIO' => 0.75, // 3:4 ratio (change to 0.5625 for 16:9)
```

### Color Scheme

Update CSS variables in `/public/index.php`:

```css
:root {
    --primary: #6366f1;
    --secondary: #8b5cf6;
}
```

## Deployment

### Via rsync

```bash
rsync -avz --exclude 'data/' --exclude 'uploads/' \
  /local/path/ user@server:/path/to/project/
```

### Via FTP

Upload all files except:
- `/data` (create fresh on server)
- `/uploads` (backed up separately)
- `/backups`

### Post-Deployment

1. Run `php init-db.php` on server
2. Set correct permissions
3. Configure web server
4. Test `/api/health` endpoint
5. Warm cache by visiting `/`

## Support

For issues or questions:
1. Check logs in `/data/error.log`
2. Verify configuration in `.env.php`
3. Test API health endpoint
4. Review browser console for JS errors

## License

This project is provided as-is for personal and commercial use.

## Credits

Built with modern web standards and best practices for performance, security, and accessibility.
