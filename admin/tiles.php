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
    <title>Manage Tiles - Admin</title>
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="logo">Portfolio Admin</div>
            <ul class="nav-menu">
                <li class="active"><a href="/admin/tiles">Tiles</a></li>
                <li><a href="/admin/media">Media</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/admin/preview" target="_blank">Preview</a></li>
            </ul>
            <div class="user-info">
                <div><?= htmlspecialchars($user['email']) ?></div>
                <button onclick="logout()">Logout</button>
            </div>
        </nav>
        
        <main class="content">
            <div class="header">
                <h1>Tiles</h1>
                <button class="btn-primary" onclick="showCreateModal()">+ New Tile</button>
            </div>
            
            <div class="tiles-grid" id="tilesGrid">
                <div class="loading">Loading tiles...</div>
            </div>
        </main>
    </div>
    
    <!-- Create/Edit Modal -->
    <div class="modal" id="tileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">New Tile</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="tileForm">
                <input type="hidden" id="tileId" name="id">
                
                <div class="form-group">
                    <label for="slug">Slug *</label>
                    <input type="text" id="slug" name="slug" required placeholder="my-project">
                </div>
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required placeholder="My Project">
                </div>
                
                <div class="form-group">
                    <label for="blurb">Description</label>
                    <textarea id="blurb" name="blurb" rows="3" placeholder="A brief description..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cta_label">Button Label</label>
                        <input type="text" id="cta_label" name="cta_label" placeholder="Visit">
                    </div>
                    
                    <div class="form-group">
                        <label for="accent_hex">Accent Color</label>
                        <input type="color" id="accent_hex" name="accent_hex">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="target_url">Target URL *</label>
                    <input type="url" id="target_url" name="target_url" required placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <label>Background Image</label>
                    <div class="media-picker">
                        <div class="media-preview" id="mediaPreview">
                            <span>No image selected</span>
                        </div>
                        <button type="button" class="btn-secondary" onclick="showMediaPicker()">Choose Image</button>
                        <input type="hidden" id="bg_media_id" name="bg_media_id">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="visible" name="visible" checked>
                            Visible
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="publish_at">Publish Date</label>
                        <input type="datetime-local" id="publish_at" name="publish_at">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Tile</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Media Picker Modal -->
    <div class="modal" id="mediaModal">
        <div class="modal-content modal-wide">
            <div class="modal-header">
                <h2>Choose Image</h2>
                <button class="modal-close" onclick="closeMediaModal()">&times;</button>
            </div>
            
            <div class="media-grid" id="mediaGrid">
                <div class="loading">Loading media...</div>
            </div>
        </div>
    </div>
    
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        let tiles = [];
        let media = [];
        let currentTile = null;
        
        // Load initial data
        async function loadTiles() {
            const response = await fetch('/api/tiles');
            const data = await response.json();
            tiles = data.tiles || [];
            renderTiles();
        }
        
        async function loadMedia() {
            const response = await fetch('/api/media');
            const data = await response.json();
            media = data.media || [];
        }
        
        // Render tiles
        function renderTiles() {
            const grid = document.getElementById('tilesGrid');
            
            if (tiles.length === 0) {
                grid.innerHTML = '<div class="empty-state">No tiles yet. Create your first one!</div>';
                return;
            }
            
            grid.innerHTML = tiles.map((tile, index) => `
                <div class="tile-card" data-tile-id="${tile.id}" draggable="true">
                    <div class="drag-handle">⋮⋮</div>
                    <div class="tile-preview" style="background-image: url('${escapeHtml(tile.media?.path_webp || '')}')">
                        ${!tile.visible ? '<span class="badge">Hidden</span>' : ''}
                    </div>
                    <div class="tile-info">
                        <h3>${escapeHtml(tile.title)}</h3>
                        <p>${escapeHtml(tile.blurb || 'No description')}</p>
                        <div class="tile-meta">
                            <span>${escapeHtml(tile.cta_label)} → ${escapeHtml(new URL(tile.target_url).hostname)}</span>
                        </div>
                    </div>
                    <div class="tile-actions">
                        <button onclick="editTile(${tile.id})" class="btn-icon" title="Edit">✏️</button>
                        <button onclick="toggleVisible(${tile.id}, ${!tile.visible})" class="btn-icon" title="${tile.visible ? 'Hide' : 'Show'}">
                            ${tile.visible ? '👁️' : '🚫'}
                        </button>
                        <button onclick="deleteTile(${tile.id})" class="btn-icon" title="Delete">🗑️</button>
                    </div>
                </div>
            `).join('');
            
            setupDragDrop();
        }
        
        // Drag and drop
        function setupDragDrop() {
            const cards = document.querySelectorAll('.tile-card');
            let draggedElement = null;
            
            cards.forEach(card => {
                card.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.style.opacity = '0.5';
                });
                
                card.addEventListener('dragend', function(e) {
                    this.style.opacity = '1';
                    draggedElement = null;
                });
                
                card.addEventListener('dragover', function(e) {
                    e.preventDefault();
                });
                
                card.addEventListener('drop', async function(e) {
                    e.preventDefault();
                    if (draggedElement === this) return;
                    
                    const grid = document.getElementById('tilesGrid');
                    const cards = [...grid.querySelectorAll('.tile-card')];
                    const draggedIndex = cards.indexOf(draggedElement);
                    const targetIndex = cards.indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        this.after(draggedElement);
                    } else {
                        this.before(draggedElement);
                    }
                    
                    await saveOrder();
                });
            });
        }
        
        async function saveOrder() {
            const cards = document.querySelectorAll('.tile-card');
            const ids = [...cards].map(card => parseInt(card.dataset.tileId));
            
            await fetch('/api/tiles/reorder', {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({csrf_token: CSRF_TOKEN, ids}),
            });
            
            await loadTiles();
        }
        
        // CRUD operations
        async function showCreateModal() {
            currentTile = null;
            document.getElementById('modalTitle').textContent = 'New Tile';
            document.getElementById('tileForm').reset();
            document.getElementById('tileId').value = '';
            document.getElementById('mediaPreview').innerHTML = '<span>No image selected</span>';
            document.getElementById('tileModal').classList.add('active');
        }
        
        async function editTile(id) {
            currentTile = tiles.find(t => t.id === id);
            if (!currentTile) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Tile';
            document.getElementById('tileId').value = currentTile.id;
            document.getElementById('slug').value = currentTile.slug;
            document.getElementById('title').value = currentTile.title;
            document.getElementById('blurb').value = currentTile.blurb || '';
            document.getElementById('cta_label').value = currentTile.cta_label || 'Visit';
            document.getElementById('target_url').value = currentTile.target_url;
            document.getElementById('accent_hex').value = currentTile.accent_hex || '#6366f1';
            document.getElementById('visible').checked = currentTile.visible;
            document.getElementById('publish_at').value = currentTile.publish_at || '';
            document.getElementById('bg_media_id').value = currentTile.media?.id || '';
            
            if (currentTile.media) {
                document.getElementById('mediaPreview').innerHTML = 
                    `<img src="${escapeHtml(currentTile.media.path_webp)}" alt="Preview">`;
            }
            
            document.getElementById('tileModal').classList.add('active');
        }
        
        document.getElementById('tileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {csrf_token: CSRF_TOKEN};
            
            formData.forEach((value, key) => {
                if (key === 'visible') {
                    data[key] = document.getElementById('visible').checked ? 1 : 0;
                } else if (value) {
                    data[key] = value;
                }
            });
            
            const id = document.getElementById('tileId').value;
            const url = id ? `/api/tiles/${id}` : '/api/tiles';
            const method = id ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data),
            });
            
            if (response.ok) {
                closeModal();
                await loadTiles();
            } else {
                const errorData = await response.json();
                alert(errorData.error || 'Failed to save tile');
            }
        });
        
        async function toggleVisible(id, visible) {
            await fetch(`/api/tiles/${id}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({csrf_token: CSRF_TOKEN, visible: visible ? 1 : 0}),
            });
            
            await loadTiles();
        }
        
        async function deleteTile(id) {
            if (!confirm('Delete this tile?')) return;
            
            await fetch(`/api/tiles/${id}`, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({csrf_token: CSRF_TOKEN}),
            });
            
            await loadTiles();
        }
        
        // Media picker
        async function showMediaPicker() {
            await loadMedia();
            
            const grid = document.getElementById('mediaGrid');
            grid.innerHTML = media.map(m => `
                <div class="media-item" onclick="selectMedia(${m.id})">
                    <img src="${escapeHtml(m.url)}" alt="${escapeHtml(m.original_name)}">
                </div>
            `).join('');
            
            document.getElementById('mediaModal').classList.add('active');
        }
        
        function selectMedia(id) {
            const selectedMedia = media.find(m => m.id === id);
            if (!selectedMedia) return;
            
            document.getElementById('bg_media_id').value = id;
            document.getElementById('mediaPreview').innerHTML = 
                `<img src="${escapeHtml(selectedMedia.url)}" alt="Preview">`;
            
            closeMediaModal();
        }
        
        function closeModal() {
            document.getElementById('tileModal').classList.remove('active');
        }
        
        function closeMediaModal() {
            document.getElementById('mediaModal').classList.remove('active');
        }
        
        async function logout() {
            await fetch('/api/auth/logout', {method: 'POST'});
            window.location.href = '/admin/login';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initialize
        loadTiles();
    </script>
</body>
</html>