<?php
/**
 * FLARE CUSTOM - Import Manager
 * Import de données depuis CSV, JSON, et fichiers HTML
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
    <title>Import / Export - FLARE CUSTOM Admin</title>
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
            --info: #007aff;
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
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card h3 { margin-bottom: 16px; font-size: 18px; display: flex; align-items: center; gap: 8px; }

        .import-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
        }

        .import-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            text-align: center;
            border: 2px dashed var(--gray-200);
            transition: all 0.3s;
        }

        .import-card:hover {
            border-color: var(--primary);
            background: rgba(255,75,38,0.02);
        }

        .import-card.dragover {
            border-color: var(--primary);
            background: rgba(255,75,38,0.05);
        }

        .import-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .import-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .import-desc {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .file-input {
            display: none;
        }

        .progress-container {
            margin-top: 20px;
            display: none;
        }

        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.3s;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 13px;
            color: var(--gray-500);
        }

        .results {
            margin-top: 20px;
            padding: 16px;
            background: var(--gray-100);
            border-radius: 8px;
            text-align: left;
            font-size: 13px;
            display: none;
        }

        .results.success { background: rgba(52,199,89,0.1); }
        .results.error { background: rgba(255,59,48,0.1); }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }
        .alert-info { background: rgba(0,122,255,0.1); color: var(--info); }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
            background: #fff;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .quick-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .quick-action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .quick-action-title {
            font-weight: 600;
        }

        .quick-action-desc {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 4px;
            text-align: center;
        }

        .log-container {
            background: #1a1a1c;
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }

        .log-line { margin: 4px 0; }
        .log-success { color: var(--success); }
        .log-error { color: var(--danger); }
        .log-info { color: var(--info); }
        .log-warning { color: var(--warning); }
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
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item active">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>📥 Import / Export de données</h1>
        </div>

        <div id="alerts"></div>

        <!-- Actions rapides -->
        <div class="quick-actions">
            <a href="#" class="quick-action" onclick="runImport('products'); return false;">
                <span class="quick-action-icon">📦</span>
                <span class="quick-action-title">Importer produits CSV</span>
                <span class="quick-action-desc">Depuis PRICING-FLARE-2025.csv</span>
            </a>
            <a href="#" class="quick-action" onclick="runImport('blog'); return false;">
                <span class="quick-action-icon">📝</span>
                <span class="quick-action-title">Importer articles blog</span>
                <span class="quick-action-desc">Depuis les fichiers HTML</span>
            </a>
            <a href="#" class="quick-action" onclick="runImport('pages'); return false;">
                <span class="quick-action-icon">📄</span>
                <span class="quick-action-title">Importer pages</span>
                <span class="quick-action-desc">Depuis les fichiers info/</span>
            </a>
            <a href="#" class="quick-action" onclick="runImport('all'); return false;">
                <span class="quick-action-icon">🚀</span>
                <span class="quick-action-title">Import complet</span>
                <span class="quick-action-desc">Tout importer d'un coup</span>
            </a>
        </div>

        <!-- Zones d'import par fichier -->
        <div class="import-grid">
            <!-- Import CSV -->
            <div class="import-card" id="csvDropzone">
                <div class="import-icon">📊</div>
                <div class="import-title">Import CSV Produits</div>
                <div class="import-desc">Glissez un fichier CSV ou cliquez pour sélectionner</div>
                <input type="file" class="file-input" id="csvFile" accept=".csv">
                <button class="btn btn-primary" onclick="document.getElementById('csvFile').click()">
                    📁 Choisir un fichier
                </button>
                <div class="progress-container" id="csvProgress">
                    <div class="progress-bar"><div class="progress-fill"></div></div>
                    <div class="progress-text">Import en cours...</div>
                </div>
                <div class="results" id="csvResults"></div>
            </div>

            <!-- Import JSON -->
            <div class="import-card" id="jsonDropzone">
                <div class="import-icon">📋</div>
                <div class="import-title">Import JSON</div>
                <div class="import-desc">Importer des données au format JSON</div>
                <input type="file" class="file-input" id="jsonFile" accept=".json">
                <button class="btn btn-primary" onclick="document.getElementById('jsonFile').click()">
                    📁 Choisir un fichier
                </button>
                <div class="progress-container" id="jsonProgress">
                    <div class="progress-bar"><div class="progress-fill"></div></div>
                    <div class="progress-text">Import en cours...</div>
                </div>
                <div class="results" id="jsonResults"></div>
            </div>
        </div>

        <!-- Export -->
        <div class="card">
            <h3>📤 Exporter les données</h3>
            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                <button class="btn btn-secondary" onclick="exportData('products', 'csv')">
                    📦 Exporter produits (CSV)
                </button>
                <button class="btn btn-secondary" onclick="exportData('products', 'json')">
                    📦 Exporter produits (JSON)
                </button>
                <button class="btn btn-secondary" onclick="exportData('quotes', 'csv')">
                    💰 Exporter devis (CSV)
                </button>
                <button class="btn btn-secondary" onclick="exportData('blog', 'json')">
                    📝 Exporter blog (JSON)
                </button>
                <button class="btn btn-secondary" onclick="exportData('pages', 'json')">
                    📄 Exporter pages (JSON)
                </button>
            </div>
        </div>

        <!-- Log d'import -->
        <div class="card">
            <h3>📋 Journal d'import</h3>
            <div class="log-container" id="importLog">
                <div class="log-line log-info">En attente d'une action...</div>
            </div>
        </div>
    </main>

    <script>
        const log = document.getElementById('importLog');

        function addLog(message, type = 'info') {
            const line = document.createElement('div');
            line.className = `log-line log-${type}`;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            log.appendChild(line);
            log.scrollTop = log.scrollHeight;
        }

        function clearLog() {
            log.innerHTML = '';
        }

        // Import automatique depuis les fichiers existants
        async function runImport(type) {
            clearLog();
            addLog(`Démarrage de l'import: ${type}`, 'info');

            try {
                const response = await fetch(`../import-all.php?type=${type}`);
                const result = await response.json();

                if (result.success) {
                    addLog(`Import terminé avec succès!`, 'success');
                    if (result.stats) {
                        Object.entries(result.stats).forEach(([key, value]) => {
                            addLog(`  - ${key}: ${value}`, 'success');
                        });
                    }
                } else {
                    addLog(`Erreur: ${result.error}`, 'error');
                }
            } catch (error) {
                addLog(`Erreur de connexion: ${error.message}`, 'error');
            }
        }

        // Gestion des fichiers CSV
        document.getElementById('csvFile').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            clearLog();
            addLog(`Fichier sélectionné: ${file.name}`, 'info');
            addLog(`Taille: ${(file.size / 1024).toFixed(2)} KB`, 'info');

            const progressContainer = document.getElementById('csvProgress');
            const progressFill = progressContainer.querySelector('.progress-fill');
            const progressText = progressContainer.querySelector('.progress-text');
            const results = document.getElementById('csvResults');

            progressContainer.style.display = 'block';
            results.style.display = 'none';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'products');

            try {
                addLog('Envoi du fichier...', 'info');
                progressFill.style.width = '30%';

                const response = await fetch('../api/import.php', {
                    method: 'POST',
                    body: formData
                });

                progressFill.style.width = '70%';
                addLog('Traitement en cours...', 'info');

                const result = await response.json();

                progressFill.style.width = '100%';

                if (result.success) {
                    addLog(`Import réussi: ${result.imported} produits importés`, 'success');
                    results.className = 'results success';
                    results.innerHTML = `✅ ${result.imported} produits importés avec succès`;
                } else {
                    addLog(`Erreur: ${result.error}`, 'error');
                    results.className = 'results error';
                    results.innerHTML = `❌ Erreur: ${result.error}`;
                }

                results.style.display = 'block';
            } catch (error) {
                addLog(`Erreur: ${error.message}`, 'error');
                results.className = 'results error';
                results.innerHTML = `❌ Erreur de connexion`;
                results.style.display = 'block';
            }
        });

        // Export de données
        function exportData(type, format) {
            addLog(`Export ${type} en ${format.toUpperCase()}...`, 'info');
            window.open(`../api/${type}.php?action=export&format=${format}`, '_blank');
            addLog(`Téléchargement démarré`, 'success');
        }

        // Drag & Drop
        ['csvDropzone', 'jsonDropzone'].forEach(id => {
            const zone = document.getElementById(id);

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

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const input = zone.querySelector('input[type="file"]');
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    input.files = dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
    </script>
</body>
</html>
