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

        .panel.expanded .panel-title {
            opacity: 0;
            transform: translateY(-20px);
        }

        .panel.expanded .panel-content {
            opacity: 1;
            pointer-events: all;
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

        .panel-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
            text-align: center;
        }

        .panel-content-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .panel-content-blurb {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 30px;
            max-width: 500px;
        }

        .panel-content-cta {
            display: inline-block;
            padding: 16px 32px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .panel-content-cta:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .panel-close {
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
            font-size: 24px;
            color: white;
            transition: all 0.2s;
        }

        .panel-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
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

            .panel-content {
                padding: 30px 20px;
            }

            .panel-content-title {
                font-size: 1.75rem;
            }

            .panel-content-blurb {
                font-size: 1rem;
                margin-bottom: 20px;
            }

            .panel-content-cta {
                padding: 12px 24px;
            }

            .panel-close {
                width: 36px;
                height: 36px;
                font-size: 20px;
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
    
    // Get average brightness of an image to determine text color
    async function getImageBrightness(imageUrl) {
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Sample a small portion at the top where text will be
                canvas.width = 100;
                canvas.height = 100;
                
                ctx.drawImage(img, 0, 0, img.width, Math.min(img.height / 3, img.width), 0, 0, 100, 100);
                
                try {
                    const imageData = ctx.getImageData(0, 0, 100, 100);
                    const data = imageData.data;
                    let colorSum = 0;
                    
                    // Calculate average brightness
                    for (let i = 0; i < data.length; i += 4) {
                        const r = data[i];
                        const g = data[i + 1];
                        const b = data[i + 2];
                        // Use perceived brightness formula
                        const brightness = (0.299 * r + 0.587 * g + 0.114 * b);
                        colorSum += brightness;
                    }
                    
                    const avgBrightness = colorSum / (data.length / 4);
                    resolve(avgBrightness);
                } catch (e) {
                    // If CORS fails, default to dark text
                    console.warn('Could not analyze image, using default', e);
                    resolve(128); // Middle value
                }
            };
            
            img.onerror = function() {
                resolve(128); // Default to middle brightness
            };
            
            img.src = imageUrl;
        });
    }
    
    // Fetch tiles
    async function loadTiles() {
        console.log('loadTiles() called');
        try {
            const apiUrl = '/api/index.php/public/tiles';
            console.log('Fetching from:', apiUrl);
            
            const response = await fetch(apiUrl);
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            state.tiles = data.tiles || [];
            console.log('Loaded tiles:', state.tiles);
            
            renderGallery();
            
            <?php if (($settings['autoplay_enabled'] ?? '0') === '1'): ?>
            startAutoplay();
            <?php endif; ?>
        } catch (error) {
            console.error('Failed to load tiles - Error:', error);
            document.getElementById('gallery').innerHTML = '<div class="loading">Failed to load content. Check console for errors.</div>';
        }
    }
    
    // Render gallery
    async function renderGallery() {
        console.log('renderGallery() called with', state.tiles.length, 'tiles');
        const gallery = document.getElementById('gallery');
        gallery.innerHTML = '';
        
        if (state.tiles.length === 0) {
            console.log('No tiles to display');
            gallery.innerHTML = '<div class="loading">No content available</div>';
            return;
        }
        
        console.log('Creating panels for tiles...');
        
        for (let index = 0; index < state.tiles.length; index++) {
            const tile = state.tiles[index];
            console.log('Creating panel for tile:', tile.title);
            
            const panel = document.createElement('div');
            panel.className = 'panel';
            panel.setAttribute('role', 'button');
            panel.setAttribute('tabindex', '0');
            panel.setAttribute('aria-label', tile.title);
            panel.dataset.tileId = tile.id;
            
            // Use background image if available, otherwise use gradient
            const bgUrl = tile.media?.path_webp || tile.media?.path_original || '';
            console.log('Tile', tile.title, 'background URL:', bgUrl);
            
            let textColor = 'white';
            let textShadowColor = 'rgba(0,0,0,0.8)';
            let overlayGradient = 'linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.7) 100%)';
            
            // Analyze image brightness if we have an image
            if (bgUrl) {
                try {
                    const brightness = await getImageBrightness(bgUrl);
                    console.log('Brightness for', tile.title, ':', brightness);
                    
                    // If image is bright (> 140), use dark text
                    if (brightness > 140) {
                        textColor = '#1a1a1a';
                        textShadowColor = 'rgba(255,255,255,0.8)';
                        overlayGradient = 'linear-gradient(180deg, rgba(255,255,255,0.3) 0%, rgba(0,0,0,0.3) 100%)';
                    }
                } catch (e) {
                    console.warn('Could not analyze image brightness', e);
                }
            }
            
            const bgStyle = bgUrl 
                ? `background-image: url('${bgUrl}')`
                : `background: linear-gradient(135deg, ${tile.accent_hex || '#667eea'} 0%, ${tile.accent_hex || '#764ba2'} 100%)`;
            
            const linkTarget = <?= json_encode(($settings['open_links_new_tab'] ?? '0') === '1' ? '_blank' : '_self') ?>;
            const relAttr = linkTarget === '_blank' ? 'rel="noopener noreferrer"' : '';

            panel.innerHTML = `
                <div class="panel-bg" style="${bgStyle}"></div>
                <div class="panel-overlay" style="background: ${overlayGradient}"></div>
                <div class="panel-title" style="color: ${textColor}; text-shadow: 0 2px 4px ${textShadowColor}, 0 4px 8px ${textShadowColor}, 0 8px 16px ${textShadowColor};">${escapeHtml(tile.title)}</div>
                <div class="panel-content">
                    <button class="panel-close" aria-label="Close">×</button>
                    <h2 class="panel-content-title">${escapeHtml(tile.title)}</h2>
                    <p class="panel-content-blurb">${escapeHtml(tile.blurb || '')}</p>
                    <a href="${escapeHtml(tile.target_url)}" class="panel-content-cta" target="${linkTarget}" ${relAttr}>${escapeHtml(tile.cta_label || 'Visit')}</a>
                </div>
            `;
            
            panel.addEventListener('click', (e) => {
                // Don't open panel if clicking on close button or CTA link
                if (e.target.closest('.panel-close')) {
                    e.stopPropagation();
                    closePanel();
                    return;
                }
                if (e.target.closest('.panel-content-cta')) {
                    e.stopPropagation();
                    return;
                }
                openPanel(panel, tile);
            });

            panel.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPanel(panel, tile);
                }
            });
            
            gallery.appendChild(panel);
            console.log('Panel added to gallery');
            
            // Stagger animation
            panel.style.opacity = '0';
            panel.style.transform = 'translateY(20px)';
            panel.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                panel.style.opacity = '1';
                panel.style.transform = 'translateY(0)';
            }, index * 80);
        }
        
        console.log('Gallery rendering complete. Total panels:', gallery.children.length);
    }
    
    // Open panel
    function openPanel(panelElement, tile) {
        state.userInteracted = true;
        stopAutoplay();

        state.activeTile = tile;

        // Update panel states
        document.querySelectorAll('.panel').forEach(panel => {
            if (panel === panelElement) {
                panel.classList.add('expanded');
                panel.classList.remove('contracted');
            } else {
                panel.classList.add('contracted');
                panel.classList.remove('expanded');
            }
        });

        // Track analytics
        trackEvent('panel_open', {tile_id: tile.id, tile_slug: tile.slug});
    }
    
    // Close panel
    function closePanel() {
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

            const panels = document.querySelectorAll('.panel');
            if (panels.length === 0) return;

            currentIndex = (currentIndex + 1) % state.tiles.length;
            openPanel(panels[currentIndex], state.tiles[currentIndex]);

            setTimeout(closePanel, interval * 0.7);
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
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Event listeners
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && state.activeTile) {
            closePanel();
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
