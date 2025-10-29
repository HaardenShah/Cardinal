# Quick Start Guide

Get your Portfolio Hub running in 5 minutes!

## Prerequisites

- PHP 8.2+ with GD extension
- SQLite 3
- Web server (Apache or Nginx)
- HTTPS certificate (for production)

## Installation Steps

### 1. Upload Files

Upload all project files to your server. Set your web server's document root to the `/public` directory.

### 2. Configure Environment

Copy and edit `.env.php`:

```bash
cp .env.php .env.local.php
```

Update these critical values:

```php
'APP_URL' => 'https://yourname.com',
'ADMIN_EMAIL_SEED' => 'admin@yourname.com',
'ADMIN_PASSWORD_SEED' => 'ChangeThisPassword123!',
'SESSION_SECURE' => true, // Only if using HTTPS
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

You should see:
```
✓ Admin user created: admin@yourname.com
✓ Database initialized successfully
```

### 5. (Optional) Seed Demo Data

```bash
php seed-demo.php
```

This creates 4 example tiles you can customize.

### 6. Configure Web Server

#### For Apache

The `.htaccess` file in `/public` should work automatically if mod_rewrite is enabled.

#### For Nginx

Add this to your site config:

```nginx
server {
    listen 443 ssl;
    server_name yourname.com;
    root /path/to/project/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location /api/ {
        rewrite ^/api/(.*)$ /api/index.php last;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Reload Nginx:
```bash
sudo systemctl reload nginx
```

### 7. First Login

1. Navigate to: `https://yourname.com/admin/login`
2. Login with your seed credentials
3. **IMMEDIATELY** go to Settings and change your password

### 8. Create Your First Tile

1. Go to **Media** and upload background images
2. Go to **Tiles** and click **+ New Tile**
3. Fill in:
   - Slug: `my-project`
   - Title: `My Project`
   - Description: `Check out my amazing project`
   - Target URL: `https://project.yourname.com`
   - Choose a background image
4. Click **Save Tile**

### 9. Customize Settings

Go to **Settings** and update:
- Site title and description
- Hero text (your name)
- Brand colors
- Enable/disable autoplay
- Add analytics ID

### 10. View Your Site

Visit: `https://yourname.com`

You should see your animated panel gallery! 🎉

## Common Issues

### "Database is locked"

**Solution**: Check permissions on `/data` directory:
```bash
chmod 775 /path/to/project/data
chown www-data:www-data /path/to/project/data
```

### Images not uploading

**Solution**: Check PHP upload limits in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### 404 errors on API routes

**Solution**: 
- Apache: Enable mod_rewrite: `sudo a2enmod rewrite && sudo systemctl restart apache2`
- Nginx: Verify location blocks in config

### Session/login issues

**Solution**: 
- Ensure cookies work (disable same-site restrictions in dev)
- Check `SESSION_SECURE` matches your HTTPS usage
- Clear browser cookies

## Next Steps

### Production Checklist

- [ ] Change admin password
- [ ] Update `.env.php` with production values
- [ ] Enable HTTPS and set `SESSION_SECURE = true`
- [ ] Configure firewall
- [ ] Set up database backups (cron job)
- [ ] Add analytics
- [ ] Test on mobile devices
- [ ] Run Lighthouse audit
- [ ] Monitor error logs

### Customization Ideas

- Upload your logo/favicon in Settings
- Adjust brand colors to match your brand
- Fine-tune animation speeds
- Add more tiles for your projects
- Set up scheduled publishing for upcoming launches

### Maintenance

**Weekly**:
- Check error logs: `/data/error.log`
- Review activity log in database
- Test login and tile creation

**Monthly**:
- Verify backups are running
- Clean up old media files
- Update PHP/dependencies
- Review analytics

**Quarterly**:
- Audit security settings
- Test disaster recovery
- Review and archive old logs

## Support

Need help? Check:

1. **README.md** - Full documentation
2. **TEST_PLAN.md** - Comprehensive testing guide  
3. **Logs** - `/data/error.log` for errors
4. **Health Check** - Visit `/api/health`

## File Locations

- Public site: `/public/index.php`
- Admin panel: `/admin/*`
- API: `/api/index.php`
- Database: `/data/site.db`
- Uploads: `/uploads/*`
- Backups: `/backups/*`
- Config: `.env.php`
- Logs: `/data/error.log`

## Security Reminders

⚠️ **Critical**: Change the default admin password immediately

🔒 **Always**:
- Use HTTPS in production
- Keep PHP updated
- Use strong passwords
- Monitor access logs
- Regular backups

🚫 **Never**:
- Expose `/data` directory to web
- Commit `.env.php` to version control
- Use default credentials in production
- Disable security headers

---

That's it! You're ready to go. Happy showcasing! 🚀
