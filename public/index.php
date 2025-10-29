<?php
require_once __DIR__ . '/../app/bootstrap.php';
setSecurityHeaders();

$config = getConfig();
$db = getDatabase();

// Fetch settings
$stmt = $db->query("SELECT key, value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($settings['site_description'] ?? '') ?>">
    <title><?= htmlspecialchars($settings['site_title'] ?? 'Portfolio Hub') ?></title>
    
    <!-- Preload critical assets -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Favicon -->
    <?php if (!empty($settings['favicon_media_id'])): ?>
    <link rel="icon" type="image/png" href="/api/media/serve/<?= $settings['favicon_media_id'] ?>">
    <?php endif; ?>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($settings['site_description'] ?? '') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $config['APP_URL'] ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
    
    <!-- Critical CSS -->
    <style>
        :root {
            --primary: <?= $settings['brand_primary'] ?? '#6366f1' ?>;
            --secondary: <?= $settings['brand_secondary'] ?? '#8b5cf6' ?>;
            --bg: #0a0a0f;
            --text: #ffffff;
            --text-muted: #a0a0b0;
            --shadow: rgba(0, 0, 0, 0.5);
            --radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 100%);
            color: var(--text);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .hero {
            text-align: center;
            padding: 60px 20px 40px;
        }
        
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        
        .hero p {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: var(--text-muted);
            font-weight: 300;
        }
        
        .gallery {
            display: flex;
            gap: 16px;
            padding: 20px;
            min-height: 500px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .panel {
            position: relative;
            flex: 1;
            min-width: 80px;
            height: 600px;
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px var(--shadow);
        }
        
        .panel:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 60px var(--shadow);
        }
        
        .panel.expanded {
            flex: 3;
            transform: scale(1);
        }
        
        .panel.contracted {
            flex: 0.5;
            opacity: 0.7;
        }
        
        .panel-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }
        
        .panel:hover .panel-bg {
            transform: scale(1.05);
        }
        
        .panel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);
        }
        
        .panel-title {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            font-size: 2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
            transition: all 0.3s ease;
        }
        
        .panel.expanded .panel-title {
            opacity: 0;
        }
        
        .info-drawer {
            position: fixed;
            top: 0;
            right: -500px;
            width: 450px;
            height: 100vh;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(20px);
            padding: 60px 40px;
            box-shadow: -10px 0 40px var(--shadow);
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .info-drawer.active {
            right: 0;
        }
        
        .drawer-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .drawer-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        .drawer-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .drawer-blurb {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .drawer-cta {
            display: inline-block;
            padding: 16px 32px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .drawer-cta:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        @media (max-width: 768px) {
            .gallery {
                flex-direction: column;
                padding: 12px;
            }
            
            .panel {
                height: 200px;
                min-height: 200px;
            }
            
            .panel.expanded {
                height: 400px;
            }
            
            .info-drawer {
                top: auto;
                bottom: -100%;
                right: 0;
                left: 0;
                width: 100%;
                height: 70vh;
                border-radius: 24px 24px 0 0;
            }
            
            .info-drawer.active {
                bottom: 0;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        .loading {
            text-align: center;
            padding: 100px 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1><?= htmlspecialchars($settings['hero_text'] ?? 'Your Name') ?></h1>
        <p><?= htmlspecialchars($settings['hero_subtext'] ?? 'Explore my work') ?></p>
    </div>
    
    <div class="gallery" id="gallery">
        <div class="loading">Loading...</div>
    </div>
    
    <div class="info-drawer" id="drawer">
        <button class="drawer-close" id="drawerClose" aria-label="Close">×</button>
        <h2 class="drawer-title" id="drawerTitle"></h2>
        <p class="drawer-blurb" id="drawerBlurb"></p>
        <a href="#" class="drawer-cta" id="drawerCta" target="_self"></a>
    </div>
    
    <!-- JSON-LD Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Person",
        "name": "<?= htmlspecialchars($settings['hero_text'] ?? '') ?>",
        "description": "<?= htmlspecialchars($settings['hero_subtext'] ?? '') ?>",
        "url": "<?= $config['APP_URL'] ?>"
    }
    </script>
    
    <script>
        // App State
        const state = {
            tiles: [],
            activeTile: null,
            autoplayInterval: null,
            userInteracted: false
        };
        
        // Fetch tiles
        async function loadTiles() {
            try {
                const response = await fetch('/api/public/tiles');
                const data = await response.json();
                state.tiles = data.tiles || [];
                renderGallery();
                
                <?php if (($settings['autoplay_enabled'] ?? '0') === '1'): ?>
                startAutoplay();
                <?php endif; ?>
            } catch (error) {
                console.error('Failed to load tiles:', error);
                document.getElementById('gallery').innerHTML = '<div class="loading">Failed to load content</div>';
            }
        }
        
        // Render gallery
        function renderGallery() {
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = '';
            
            if (state.tiles.length === 0) {
                gallery.innerHTML = '<div class="loading">No content available</div>';
                return;
            }
            
            state.tiles.forEach((tile, index) => {
                const panel = document.createElement('div');
                panel.className = 'panel';
                panel.setAttribute('role', 'button');
                panel.setAttribute('tabindex', '0');
                panel.setAttribute('aria-label', tile.title);
                panel.dataset.tileId = tile.id;
                
                const bgUrl = tile.media?.path_webp || tile.media?.path_original || '';
                const accentColor = tile.accent_hex || 'var(--primary)';
                
                panel.innerHTML = `
                    <div class="panel-bg" style="background-image: url('${bgUrl}')"></div>
                    <div class="panel-overlay"></div>
                    <div class="panel-title" style="color: ${accentColor}">${escapeHtml(tile.title)}</div>
                `;
                
                panel.addEventListener('click', () => openPanel(tile));
                panel.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openPanel(tile);
                    }
                });
                
                gallery.appendChild(panel);
                
                // Stagger animation
                setTimeout(() => {
                    panel.style.opacity = '1';
                    panel.style.transform = 'translateY(0)';
                }, index * 80);
            });
        }
        
        // Open panel
        function openPanel(tile) {
            state.userInteracted = true;
            stopAutoplay();
            
            state.activeTile = tile;
            
            // Update panel states
            document.querySelectorAll('.panel').forEach(panel => {
                if (panel.dataset.tileId === String(tile.id)) {
                    panel.classList.add('expanded');
                    panel.classList.remove('contracted');
                } else {
                    panel.classList.add('contracted');
                    panel.classList.remove('expanded');
                }
            });
            
            // Update drawer
            document.getElementById('drawerTitle').textContent = tile.title;
            document.getElementById('drawerBlurb').textContent = tile.blurb || '';
            
            const cta = document.getElementById('drawerCta');
            cta.textContent = tile.cta_label || 'Visit';
            cta.href = tile.target_url;
            cta.target = <?= json_encode(($settings['open_links_new_tab'] ?? '0') === '1' ? '_blank' : '_self') ?>;
            if (cta.target === '_blank') {
                cta.rel = 'noopener noreferrer';
            }
            
            document.getElementById('drawer').classList.add('active');
            
            // Track analytics
            trackEvent('panel_open', {tile_id: tile.id, tile_slug: tile.slug});
        }
        
        // Close drawer
        function closeDrawer() {
            document.getElementById('drawer').classList.remove('active');
            document.querySelectorAll('.panel').forEach(panel => {
                panel.classList.remove('expanded', 'contracted');
            });
            state.activeTile = null;
        }
        
        // Autoplay
        function startAutoplay() {
            const interval = <?= intval($settings['autoplay_interval'] ?? 7) ?> * 1000;
            let currentIndex = 0;
            
            state.autoplayInterval = setInterval(() => {
                if (state.userInteracted) {
                    stopAutoplay();
                    return;
                }
                
                if (state.tiles.length === 0) return;
                
                currentIndex = (currentIndex + 1) % state.tiles.length;
                openPanel(state.tiles[currentIndex]);
                
                setTimeout(closeDrawer, interval * 0.7);
            }, interval);
        }
        
        function stopAutoplay() {
            if (state.autoplayInterval) {
                clearInterval(state.autoplayInterval);
                state.autoplayInterval = null;
            }
        }
        
        // Analytics
        function trackEvent(event, data) {
            if (navigator.doNotTrack === '1' && <?= json_encode($config['RESPECT_DNT']) ?>) {
                return;
            }
            
            const payload = {
                event,
                timestamp: new Date().toISOString(),
                ...data
            };
            
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/analytics', JSON.stringify(payload));
            }
        }
        
        // Utility
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Event listeners
        document.getElementById('drawerClose').addEventListener('click', closeDrawer);
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && state.activeTile) {
                closeDrawer();
            }
        });
        
        // Initialize
        loadTiles();
    </script>
    
    <?php if (!empty($settings['analytics_id'])): ?>
    <!-- Analytics placeholder -->
    <script>
        // Initialize analytics with ID: <?= htmlspecialchars($settings['analytics_id']) ?>
    </script>
    <?php endif; ?>
</body>
</html>
