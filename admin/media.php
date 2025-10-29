<script>
    const CSRF_TOKEN = '<?= $csrfToken ?>';
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    let media = [];
    
    // API URL helper
    function apiUrl(path) {
        return '/api/index.php' + path;
    }
    
    // Load media
    async function loadMedia() {
        const response = await fetch(apiUrl('/media'));
        const data = await response.json();
        media = data.media || [];
        renderMedia();
    }
    
    // Render media
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
    
    // Upload handling
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
        
        // Validate files before uploading
        for (const file of filesArray) {
            if (file.size > MAX_FILE_SIZE) {
                alert(`${file.name} is too large (max 10MB)`);
                return;
            }
            
            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                alert(`${file.name} is not a supported image format`);
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
        
        const response = await fetch(apiUrl('/media'), {
            method: 'POST',
            body: formData,
        });
        
        if (!response.ok) {
            const error = await response.json();
            alert(`Failed to upload ${file.name}: ${error.error || 'Unknown error'}`);
        }
    }
    
    async function deleteMedia(id) {
        if (!confirm('Delete this image? This cannot be undone.')) return;
        
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
    }
    
    async function logout() {
        await fetch(apiUrl('/auth/logout'), {method: 'POST'});
        window.location.href = '/admin/login.php';
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initialize
    loadMedia();
</script>