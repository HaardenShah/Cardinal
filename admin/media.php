<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/bootstrap.php';
startSecureSession();

if (!isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$user = getCurrentUser();
if (!$user) {
    header('Location: /admin/login.php');
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - Admin</title>
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="logo">Portfolio Admin</div>
            <ul class="nav-menu">
                <li><a href="/admin/tiles.php">Tiles</a></li>
                <li class="active"><a href="/admin/media.php">Media</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/admin/preview.php" target="_blank">Preview</a></li>
            </ul>
            <div class="user-info">
                <div><?= htmlspecialchars($user['email']) ?></div>
                <button onclick="logout()">Logout</button>
            </div>
        </nav>
        
        <main class="content">
            <div class="header">
                <h1>Media Library</h1>
            </div>
            
            <div class="upload-zone" id="uploadZone">
                <input type="file" id="fileInput" accept="image/*" multiple style="display: none;">
                <div style="font-size: 48px;">📁</div>
                <p>Click or drag images here to upload</p>
                <p style="font-size: 12px; margin-top: 8px;">Max 10MB per file • JPG, PNG, WebP</p>
            </div>
            
            <div id="uploadProgress" style="display: none; margin-top: 20px;">
                <div style="background: var(--bg); border-radius: 8px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span id="progressText">Uploading...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div id="progressBar" style="background: var(--primary); height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>
            
            <div class="media-grid" id="mediaGrid" style="margin-top: 32px;">
                <div class="loading">Loading media...</div>
            </div>
        </main>
    </div>
    
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const MAX_FILE_SIZE = 10 * 1024 * 1024;
        let media = [];
        
        function apiUrl(path) {
            return '/api/index.php' + path;
        }
        
        async function loadMedia() {
            try {
                const response = await fetch(apiUrl('/media'));
                const data = await response.json();
                media = data.media || [];
                renderMedia();
            } catch (error) {
                console.error('Failed to load media:', error);
                document.getElementById('mediaGrid').innerHTML = '<div class="empty-state">Failed to load media</div>';
            }
        }
        
        function renderMedia() {
            const grid = document.getElementById('mediaGrid');
            
            if (media.length === 0) {
                grid.innerHTML = '<div class="empty-state">No images yet. Upload some!</div>';
                return;
            }
            
            grid.innerHTML = media.map(m => `
                <div class="media-item-card">
                    <img src="${escapeHtml(m.url)}" alt="${escapeHtml(m.original_name)}">
                    <div class="media-info">
                        <div class="media-name">${escapeHtml(m.original_name)}</div>
                        <div class="media-meta">${m.width} × ${m.height}</div>
                        <button onclick="deleteMedia(${m.id})" class="btn-icon" title="Delete">🗑️</button>
                    </div>
                </div>
            `).join('');
        }
        
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        async function handleFiles(files) {
            const filesArray = Array.from(files);
            const total = filesArray.length;
            let completed = 0;
            
            for (const file of filesArray) {
                if (file.size > MAX_FILE_SIZE) {
                    alert(`${file.name} is too large (max 10MB)`);
                    return;
                }
                
                if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                    alert(`${file.name} is not a supported format`);
                    return;
                }
            }
            
            document.getElementById('uploadProgress').style.display = 'block';
            
            for (const file of filesArray) {
                await uploadFile(file);
                completed++;
                
                const percent = Math.round((completed / total) * 100);
                document.getElementById('progressPercent').textContent = percent + '%';
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressText').textContent = `Uploading ${completed} of ${total}...`;
            }
            
            document.getElementById('progressText').textContent = 'Upload complete!';
            setTimeout(() => {
                document.getElementById('uploadProgress').style.display = 'none';
                document.getElementById('progressBar').style.width = '0%';
            }, 2000);
            
            await loadMedia();
            fileInput.value = '';
        }
        
        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', CSRF_TOKEN);
            
            try {
                const response = await fetch(apiUrl('/media'), {
                    method: 'POST',
                    body: formData,
                });
                
                if (!response.ok) {
                    const error = await response.json();
                    alert(`Failed to upload ${file.name}: ${error.error || 'Unknown error'}`);
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert(`Failed to upload ${file.name}`);
            }
        }
        
        async function deleteMedia(id) {
            if (!confirm('Delete this image? This cannot be undone.')) return;
            
            try {
                const response = await fetch(apiUrl(`/media/${id}`), {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({csrf_token: CSRF_TOKEN}),
                });
                
                if (response.ok) {
                    await loadMedia();
                } else {
                    const data = await response.json();
                    alert(data.error || 'Failed to delete');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Failed to delete');
            }
        }
        
        async function logout() {
            await fetch(apiUrl('/auth/logout'), {method: 'POST'});
            window.location.href = '/admin/login.php';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        loadMedia();
    </script>
    
    <style>
        .media-item-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .media-item-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .media-item-card img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
        }
        
        .media-info {
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        
        .media-name {
            font-size: 13px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }
        
        .media-meta {
            font-size: 11px;
            color: var(--text-muted);
        }
    </style>
</body>
</html>