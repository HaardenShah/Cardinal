# Installation Commands Reference

Quick copy-paste commands for setting up your Portfolio Hub.

## 📥 Download & Extract

If you received the compressed file:

```bash
# Extract the archive
tar -xzf portfolio-hub.tar.gz

# Navigate to project
cd portfolio-hub
```

## 🔧 Initial Setup

### 1. Set File Permissions

```bash
# Make directories writable
chmod 755 .
chmod 775 data uploads backups

# Make deploy script executable
chmod +x deploy.sh
```

### 2. Configure Environment

```bash
# Open configuration file
nano .env.php

# Or use your preferred editor
vim .env.php
```

**Critical settings to change:**
- `APP_URL` → Your domain
- `ADMIN_EMAIL_SEED` → Your email
- `ADMIN_PASSWORD_SEED` → Strong password
- `SESSION_SECURE` → `true` if using HTTPS

### 3. Initialize Database

```bash
# Create database and admin user
php init-db.php
```

Expected output:
```
✓ Admin user created: your-email@example.com
✓ Database initialized successfully
✓ Schema version: 1.0
✓ Location: /path/to/data/site.db
```

### 4. (Optional) Add Demo Data

```bash
# Create 4 example tiles
php seed-demo.php
```

## 🌐 Web Server Configuration

### Apache (Most Common)

The `.htaccess` file in `/public` should work automatically.

Verify mod_rewrite is enabled:
```bash
# Check if mod_rewrite is loaded
apache2ctl -M | grep rewrite

# If not enabled:
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Virtual host configuration:
```bash
# Edit Apache config
sudo nano /etc/apache2/sites-available/yourname.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName yourname.com
    DocumentRoot /var/www/portfolio-hub/public
    
    <Directory /var/www/portfolio-hub/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable and restart:
```bash
sudo a2ensite yourname.conf
sudo systemctl restart apache2
```

### Nginx

```bash
# Edit Nginx config
sudo nano /etc/nginx/sites-available/yourname.com
```

Add:
```nginx
server {
    listen 80;
    server_name yourname.com;
    root /var/www/portfolio-hub/public;
    index index.php;
    
    # API routing
    location /api/ {
        rewrite ^/api/(.*)$ /api/index.php last;
    }
    
    # Admin routing
    location /admin/ {
        try_files $uri $uri/ /admin/$uri.php;
    }
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files
    location ~* \.(jpg|jpeg|png|gif|webp|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/yourname.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🔒 HTTPS Setup (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx  # For Nginx
# OR
sudo apt install certbot python3-certbot-apache # For Apache

# Get certificate
sudo certbot --nginx -d yourname.com
# OR
sudo certbot --apache -d yourname.com

# Auto-renewal test
sudo certbot renew --dry-run
```

After HTTPS is enabled, update `.env.php`:
```php
'SESSION_SECURE' => true,
```

## 🗄️ Database Backup Setup

### Manual Backup
```bash
php backup.php
```

### Automated Backups (Cron)

```bash
# Edit crontab
crontab -e

# Add this line (backup daily at 3 AM)
0 3 * * * cd /var/www/portfolio-hub && /usr/bin/php backup.php >> /var/log/portfolio-backup.log 2>&1
```

## 🔍 Verification Commands

### Check PHP Version
```bash
php -v
# Should be 8.2 or higher
```

### Check PHP Extensions
```bash
php -m | grep -E "gd|sqlite3"
# Should show: gd, sqlite3
```

### Check File Permissions
```bash
ls -la data/ uploads/ backups/
# Should show: drwxrwxr-x (775)
```

### Test Database
```bash
sqlite3 data/site.db "SELECT COUNT(*) FROM tiles;"
# Should return a number
```

### Health Check
```bash
curl http://yourname.com/api/health
# Should return: {"status":"ok",...}
```

### Test Public Page
```bash
curl -I http://yourname.com/
# Should return: HTTP/1.1 200 OK
```

## 🧹 Maintenance Commands

### View Error Log
```bash
tail -f data/error.log
```

### View Activity Log
```bash
sqlite3 data/site.db "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10;"
```

### Check Database Size
```bash
du -h data/site.db
```

### Check Upload Directory Size
```bash
du -sh uploads/
```

### Clean Old Backups (Manual)
```bash
find backups/ -name "*.db" -mtime +14 -delete
```

## 🚀 Deployment Commands

### Deploy via rsync
```bash
# From local machine to server
./deploy.sh user@server.com:/var/www/portfolio-hub
```

### Manual Deploy
```bash
# On local machine
rsync -avz --exclude 'data/' --exclude 'uploads/' \
    ./ user@server.com:/var/www/portfolio-hub/

# On server
cd /var/www/portfolio-hub
chmod 775 data uploads backups
php init-db.php  # Only if database doesn't exist
```

## 🔧 Troubleshooting Commands

### Apache Errors
```bash
sudo tail -f /var/log/apache2/error.log
```

### Nginx Errors
```bash
sudo tail -f /var/log/nginx/error.log
```

### PHP Errors
```bash
tail -f data/error.log
```

### Test Rewrite Rules
```bash
curl -I http://yourname.com/api/health
# Should return 200, not 404
```

### Check Session Directory
```bash
php -i | grep "session.save_path"
ls -la /var/lib/php/sessions/  # or wherever it points
```

### Test Database Connection
```bash
php -r "require 'app/bootstrap.php'; \$db = getDatabase(); echo 'Connected\n';"
```

### Check Disk Space
```bash
df -h
```

## 🔄 Update Commands

### Update PHP
```bash
sudo apt update
sudo apt upgrade php8.2
sudo systemctl restart apache2  # or nginx
```

### Update Project Files
```bash
# Backup first
cp -r /var/www/portfolio-hub /var/www/portfolio-hub.backup

# Deploy new version
./deploy.sh user@server.com:/var/www/portfolio-hub

# Verify
curl http://yourname.com/api/health
```

## 📊 Monitoring Commands

### Watch Logs in Real-Time
```bash
tail -f data/error.log
```

### Check Active Sessions
```bash
php -r "session_start(); var_dump(\$_SESSION);"
```

### Test Rate Limiting
```bash
# Try 6 rapid login attempts
for i in {1..6}; do
    curl -X POST http://yourname.com/api/auth/login \
         -H "Content-Type: application/json" \
         -d '{"email":"test","password":"test"}'
    echo ""
done
```

### Monitor Database Performance
```bash
sqlite3 data/site.db ".timer on" "SELECT COUNT(*) FROM tiles;"
```

## 🎨 Customization Commands

### Change Colors
```bash
# Edit settings via database
sqlite3 data/site.db "UPDATE settings SET value='#ff0000' WHERE key='brand_primary';"
```

### Add New Admin User
```bash
php -r "
require 'app/bootstrap.php';
\$db = getDatabase();
\$hash = password_hash('new-password', PASSWORD_ARGON2ID);
\$stmt = \$db->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
\$stmt->execute(['newadmin@example.com', \$hash, 'admin']);
echo 'User created\n';
"
```

## 📱 Testing Commands

### Test Mobile Responsive
```bash
# Using curl with mobile user agent
curl -A "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)" http://yourname.com/
```

### Load Test (Basic)
```bash
# Install apache bench
sudo apt install apache2-utils

# Test with 100 requests, 10 concurrent
ab -n 100 -c 10 http://yourname.com/
```

### Security Headers Check
```bash
curl -I https://yourname.com/ | grep -E "X-Frame-Options|X-Content-Type-Options|Content-Security-Policy"
```

## 🆘 Emergency Commands

### Restore from Backup
```bash
# Stop web server
sudo systemctl stop apache2  # or nginx

# Restore database
cp backups/database_TIMESTAMP.db data/site.db

# Restart
sudo systemctl start apache2  # or nginx
```

### Reset Admin Password
```bash
php -r "
require 'app/bootstrap.php';
\$db = getDatabase();
\$hash = password_hash('new-password', PASSWORD_ARGON2ID);
\$stmt = \$db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
\$stmt->execute([\$hash, 'admin@yourname.com']);
echo 'Password reset\n';
"
```

### Clear All Sessions
```bash
sudo rm /var/lib/php/sessions/sess_*
```

### Rebuild Database
```bash
# DANGER: This deletes all data
rm data/site.db
php init-db.php
```

---

**Pro Tip**: Save these commands in a `commands.txt` file on your server for quick reference!

## 📞 Quick Diagnostics

One-liner to check everything:
```bash
echo "PHP: $(php -v | head -1)"; \
echo "GD: $(php -m | grep gd)"; \
echo "SQLite: $(php -m | grep sqlite3)"; \
echo "DB exists: $(test -f data/site.db && echo 'Yes' || echo 'No')"; \
echo "Permissions: $(ls -ld data/ | awk '{print $1}')"; \
echo "Health: $(curl -s http://localhost/api/health | grep -o '"status":"[^"]*"')"
```

Expected output:
```
PHP: PHP 8.2.x
GD: gd
SQLite: sqlite3
DB exists: Yes
Permissions: drwxrwxr-x
Health: "status":"ok"
```
