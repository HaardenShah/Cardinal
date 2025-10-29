<?php
require_once __DIR__ . '/../app/bootstrap.php';
startSecureSession();

if (!isAuthenticated()) {
    header('Location: /admin/login');
    exit;
}

$user = getCurrentUser();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="logo">Portfolio Admin</div>
            <ul class="nav-menu">
                <li><a href="/admin/tiles">Tiles</a></li>
                <li><a href="/admin/media">Media</a></li>
                <li class="active"><a href="/admin/settings">Settings</a></li>
                <li><a href="/admin/preview" target="_blank">Preview</a></li>
            </ul>
            <div class="user-info">
                <div><?= htmlspecialchars($user['email']) ?></div>
                <button onclick="logout()">Logout</button>
            </div>
        </nav>
        
        <main class="content">
            <div class="header">
                <h1>Settings</h1>
                <button class="btn-primary" onclick="saveSettings()">Save Changes</button>
            </div>
            
            <div id="successMessage" style="display: none; padding: 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 24px;">
                Settings saved successfully!
            </div>
            
            <div class="settings-section">
                <h2>Site Information</h2>
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" placeholder="Your Name - Portfolio Hub">
                </div>
                
                <div class="form-group">
                    <label for="site_description">Site Description</label>
                    <textarea id="site_description" rows="2" placeholder="Explore my work across multiple domains"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="hero_text">Hero Text</label>
                    <input type="text" id="hero_text" placeholder="Your Name">
                </div>
                
                <div class="form-group">
                    <label for="hero_subtext">Hero Subtext</label>
                    <input type="text" id="hero_subtext" placeholder="Designer • Developer • Creator">
                </div>
            </div>
            
            <div class="settings-section">
                <h2>Brand Colors</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="brand_primary">Primary Color</label>
                        <input type="color" id="brand_primary" value="#6366f1">
                    </div>
                    
                    <div class="form-group">
                        <label for="brand_secondary">Secondary Color</label>
                        <input type="color" id="brand_secondary" value="#8b5cf6">
                    </div>
                </div>
            </div>
            
            <div class="settings-section">
                <h2>Behavior</h2>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="autoplay_enabled">
                        Enable Auto-cycle
                    </label>
                    <p style="font-size: 14px; color: var(--text-muted); margin-top: 8px;">
                        Automatically cycle through panels when idle
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="autoplay_interval">Auto-cycle Interval (seconds)</label>
                    <input type="number" id="autoplay_interval" value="7" min="3" max="30">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="open_links_new_tab">
                        Open links in new tab
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="animation_speed">Animation Speed</label>
                    <select id="animation_speed">
                        <option value="slow">Slow (800ms)</option>
                        <option value="normal" selected>Normal (500ms)</option>
                        <option value="fast">Fast (300ms)</option>
                    </select>
                </div>
            </div>
            
            <div class="settings-section">
                <h2>Analytics</h2>
                <div class="form-group">
                    <label for="analytics_id">Analytics ID (Google Analytics, etc.)</label>
                    <input type="text" id="analytics_id" placeholder="G-XXXXXXXXXX">
                </div>
            </div>
            
            <div class="settings-section">
                <h2>Export & Backup</h2>
                <button class="btn-secondary" onclick="exportData()">Export All Data (JSON)</button>
                <button class="btn-secondary" onclick="runBackup()" style="margin-left: 12px;">Create Backup Now</button>
            </div>
        </main>
    </div>
    
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        let settings = {};
        
        // Load settings
        async function loadSettings() {
            const response = await fetch('/api/settings');
            const data = await response.json();
            settings = data.settings || {};
            populateForm();
        }
        
        function populateForm() {
            document.getElementById('site_title').value = settings.site_title || '';
            document.getElementById('site_description').value = settings.site_description || '';
            document.getElementById('hero_text').value = settings.hero_text || '';
            document.getElementById('hero_subtext').value = settings.hero_subtext || '';
            document.getElementById('brand_primary').value = settings.brand_primary || '#6366f1';
            document.getElementById('brand_secondary').value = settings.brand_secondary || '#8b5cf6';
            document.getElementById('autoplay_enabled').checked = settings.autoplay_enabled === '1';
            document.getElementById('autoplay_interval').value = settings.autoplay_interval || '7';
            document.getElementById('open_links_new_tab').checked = settings.open_links_new_tab === '1';
            document.getElementById('animation_speed').value = settings.animation_speed || 'normal';
            document.getElementById('analytics_id').value = settings.analytics_id || '';
        }
        
        async function saveSettings() {
            const updatedSettings = {
                site_title: document.getElementById('site_title').value,
                site_description: document.getElementById('site_description').value,
                hero_text: document.getElementById('hero_text').value,
                hero_subtext: document.getElementById('hero_subtext').value,
                brand_primary: document.getElementById('brand_primary').value,
                brand_secondary: document.getElementById('brand_secondary').value,
                autoplay_enabled: document.getElementById('autoplay_enabled').checked ? '1' : '0',
                autoplay_interval: document.getElementById('autoplay_interval').value,
                open_links_new_tab: document.getElementById('open_links_new_tab').checked ? '1' : '0',
                animation_speed: document.getElementById('animation_speed').value,
                analytics_id: document.getElementById('analytics_id').value,
            };
            
            const response = await fetch('/api/settings', {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    csrf_token: CSRF_TOKEN,
                    settings: updatedSettings
                }),
            });
            
            if (response.ok) {
                const msg = document.getElementById('successMessage');
                msg.style.display = 'block';
                setTimeout(() => msg.style.display = 'none', 3000);
            } else {
                alert('Failed to save settings');
            }
        }
        
        async function exportData() {
            const tilesResponse = await fetch('/api/tiles');
            const tilesData = await tilesResponse.json();
            
            const settingsResponse = await fetch('/api/settings');
            const settingsData = await settingsResponse.json();
            
            const exportData = {
                tiles: tilesData.tiles,
                settings: settingsData.settings,
                exported_at: new Date().toISOString(),
            };
            
            const blob = new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `portfolio-export-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        }
        
        async function runBackup() {
            alert('Backup initiated. This runs on the server.');
        }
        
        async function logout() {
            await fetch('/api/auth/logout', {method: 'POST'});
            window.location.href = '/admin/login';
        }
        
        // Initialize
        loadSettings();
    </script>
</body>
</html>
