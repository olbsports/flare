<?php
/**
 * FLARE CUSTOM - Gestionnaire de Médias
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médiathèque - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --dark: #1d1d1f;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-500: #86868b;
            --success: #34c759;
            --warning: #ff9500;
            --danger: #ff3b30;
            --sidebar-width: 280px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); }

        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-width); background: #1a1a1c; color: #fff;
            overflow-y: auto; padding: 24px 0;
        }
        .sidebar .logo { padding: 0 24px 24px; font-size: 24px; font-weight: bold; color: var(--primary); }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px; color: rgba(255,255,255,0.7);
            text-decoration: none; transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: #fff; }

        .main-content { margin-left: var(--sidebar-width); padding: 24px 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 28px; }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 8px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }

        .upload-zone {
            border: 3px dashed var(--gray-200);
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 24px;
        }

        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--primary);
            background: rgba(255,75,38,0.03);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .upload-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .upload-desc {
            color: var(--gray-500);
            font-size: 14px;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }

        .media-item {
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .media-item.selected {
            box-shadow: 0 0 0 3px var(--primary);
        }

        .media-thumb {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background: var(--gray-100);
        }

        .media-info {
            padding: 12px;
        }

        .media-name {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .media-meta {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        .media-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .media-item:hover .media-actions {
            opacity: 1;
        }

        .media-action-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(255,255,255,0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .media-action-btn:hover {
            background: #fff;
        }

        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filters input, .filters select {
            padding: 10px 14px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
        }

        .filters input {
            min-width: 250px;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #fff;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-500);
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .detail-item label {
            display: block;
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 4px;
        }

        .detail-item input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
        }

        .copy-url {
            display: flex;
            gap: 8px;
        }

        .copy-url input {
            flex: 1;
            font-family: monospace;
            font-size: 12px;
        }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }

        .stats-bar {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-500);
            font-size: 14px;
        }

        .stat-value {
            font-weight: 700;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">FLARE CUSTOM</div>
        <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
        <a href="products-manager.php" class="nav-item">📦 Produits</a>
        <a href="categories-manager.php" class="nav-item">📁 Catégories</a>
        <a href="configurator-manager.php" class="nav-item">🔧 Configurateur</a>
        <a href="quotes-manager.php" class="nav-item">💰 Devis</a>
        <a href="pages-manager.php" class="nav-item">📄 Pages</a>
        <a href="blog-manager.php" class="nav-item">📝 Blog</a>
        <a href="media-manager.php" class="nav-item active">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>🖼️ Médiathèque</h1>
            <div>
                <button class="btn btn-secondary" onclick="toggleView()">📐 Vue</button>
                <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                    ➕ Ajouter des fichiers
                </button>
            </div>
        </div>

        <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">

        <div id="alerts"></div>

        <!-- Zone d'upload -->
        <div class="upload-zone" id="uploadZone">
            <div class="upload-icon">📁</div>
            <div class="upload-title">Glissez vos fichiers ici</div>
            <div class="upload-desc">ou cliquez pour sélectionner des images (JPG, PNG, SVG, WebP)</div>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <span>📷</span>
                <span class="stat-value" id="totalImages">0</span> images
            </div>
            <div class="stat-item">
                <span>💾</span>
                <span class="stat-value" id="totalSize">0 MB</span> utilisés
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <input type="text" id="searchInput" placeholder="Rechercher un fichier...">
            <select id="typeFilter">
                <option value="">Tous les types</option>
                <option value="image">Images</option>
                <option value="svg">SVG</option>
                <option value="video">Vidéos</option>
                <option value="document">Documents</option>
            </select>
            <select id="sortFilter">
                <option value="recent">Plus récents</option>
                <option value="name">Nom A-Z</option>
                <option value="size">Taille</option>
            </select>
        </div>

        <!-- Grille des médias -->
        <div id="mediaGrid" class="media-grid">
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 16px;">🖼️</div>
                <p>Aucun média pour le moment</p>
                <p style="font-size: 14px; margin-top: 8px;">Uploadez des images pour commencer</p>
            </div>
        </div>
    </main>

    <!-- Modal détails -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Détails du média</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <img id="previewImage" class="preview-image" src="" alt="">
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Nom du fichier</label>
                        <input type="text" id="mediaName" readonly>
                    </div>
                    <div class="detail-item">
                        <label>Dimensions</label>
                        <input type="text" id="mediaDimensions" readonly>
                    </div>
                    <div class="detail-item">
                        <label>Taille</label>
                        <input type="text" id="mediaSize" readonly>
                    </div>
                    <div class="detail-item">
                        <label>Type</label>
                        <input type="text" id="mediaType" readonly>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>URL</label>
                        <div class="copy-url">
                            <input type="text" id="mediaUrl" readonly>
                            <button class="btn btn-secondary btn-sm" onclick="copyUrl()">📋 Copier</button>
                        </div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Texte alternatif</label>
                        <input type="text" id="mediaAlt" placeholder="Description de l'image">
                    </div>
                </div>
                <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button class="btn btn-danger" onclick="deleteMedia()">🗑️ Supprimer</button>
                    <button class="btn btn-primary" onclick="saveMedia()">💾 Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../api/media.php';
        let currentMedia = null;

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            loadMedia();
            setupUpload();
        });

        // Charger les médias
        async function loadMedia() {
            try {
                const response = await fetch(API_URL);
                const data = await response.json();

                if (data.success) {
                    renderMedia(data.data || []);
                    updateStats(data.data || []);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        function renderMedia(media) {
            const grid = document.getElementById('mediaGrid');

            if (!media || media.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div style="font-size: 48px; margin-bottom: 16px;">🖼️</div>
                        <p>Aucun média pour le moment</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = media.map(m => `
                <div class="media-item" onclick="openDetail(${JSON.stringify(m).replace(/"/g, '&quot;')})">
                    <img src="${m.url || m.path}" class="media-thumb" alt="${m.alt_text || ''}" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23f5f5f7%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%2386868b%22 font-size=%2220%22>📄</text></svg>'">
                    <div class="media-info">
                        <div class="media-name">${m.filename || m.original_filename}</div>
                        <div class="media-meta">${formatSize(m.size)} • ${m.mime_type || 'image'}</div>
                    </div>
                    <div class="media-actions">
                        <button class="media-action-btn" onclick="event.stopPropagation(); copyToClipboard('${m.url || m.path}')">📋</button>
                        <button class="media-action-btn" onclick="event.stopPropagation(); deleteMediaById(${m.id})">🗑️</button>
                    </div>
                </div>
            `).join('');
        }

        function updateStats(media) {
            document.getElementById('totalImages').textContent = media.length;
            const totalSize = media.reduce((sum, m) => sum + (m.size || 0), 0);
            document.getElementById('totalSize').textContent = formatSize(totalSize);
        }

        function formatSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        // Upload
        function setupUpload() {
            const zone = document.getElementById('uploadZone');
            const input = document.getElementById('fileInput');

            zone.addEventListener('click', () => input.click());

            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('dragover');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('dragover');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });

            input.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }

        async function handleFiles(files) {
            for (const file of files) {
                await uploadFile(file);
            }
            loadMedia();
        }

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Fichier uploadé: ' + file.name, 'success');
                } else {
                    showAlert('Erreur: ' + (result.error || 'Upload échoué'), 'error');
                }
            } catch (error) {
                showAlert('Erreur de connexion', 'error');
            }
        }

        // Modal détails
        function openDetail(media) {
            currentMedia = media;
            document.getElementById('previewImage').src = media.url || media.path;
            document.getElementById('mediaName').value = media.filename || media.original_filename;
            document.getElementById('mediaDimensions').value = (media.width && media.height) ? `${media.width} x ${media.height}` : 'N/A';
            document.getElementById('mediaSize').value = formatSize(media.size);
            document.getElementById('mediaType').value = media.mime_type || 'image';
            document.getElementById('mediaUrl').value = media.url || media.path;
            document.getElementById('mediaAlt').value = media.alt_text || '';
            document.getElementById('detailModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
            currentMedia = null;
        }

        function copyUrl() {
            const url = document.getElementById('mediaUrl').value;
            copyToClipboard(url);
            showAlert('URL copiée !', 'success');
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
        }

        async function deleteMedia() {
            if (!currentMedia || !confirm('Supprimer ce média ?')) return;

            await deleteMediaById(currentMedia.id);
            closeModal();
        }

        async function deleteMediaById(id) {
            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    showAlert('Média supprimé', 'success');
                    loadMedia();
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alerts');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }

        // Filtres
        document.getElementById('searchInput').addEventListener('input', () => {
            // Implémenter le filtrage côté client
        });
    </script>
</body>
</html>
