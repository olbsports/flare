<?php
/**
 * FLARE CUSTOM - Gestionnaire des Paramètres
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Settings.php';

$settingsModel = new Settings();
$settings = $settingsModel->getAll();

// Grouper par catégorie
$grouped = [];
foreach ($settings as $setting) {
    $cat = $setting['category'] ?? 'general';
    if (!isset($grouped[$cat])) {
        $grouped[$cat] = [];
    }
    $grouped[$cat][] = $setting;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - FLARE CUSTOM Admin</title>
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

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card h3 {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-grid {
            display: grid;
            gap: 20px;
        }

        .setting-item {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 16px;
            align-items: start;
            padding: 16px;
            background: var(--gray-100);
            border-radius: 12px;
        }

        .setting-label {
            font-weight: 600;
        }

        .setting-key {
            font-size: 12px;
            color: var(--gray-500);
            font-family: monospace;
            margin-top: 4px;
        }

        .setting-desc {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 4px;
        }

        .setting-input input, .setting-input select, .setting-input textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
        }

        .setting-input textarea {
            min-height: 100px;
            resize: vertical;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--gray-300);
            border-radius: 26px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: 0.3s;
        }

        .toggle-switch input:checked + .toggle-slider {
            background: var(--success);
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            background: #fff;
            border: 2px solid var(--gray-200);
            font-weight: 600;
            transition: all 0.2s;
        }

        .tab.active {
            border-color: var(--primary);
            background: rgba(255,75,38,0.05);
            color: var(--primary);
        }

        .tab:hover {
            border-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .category-icons {
            general: '⚙️',
            pricing: '💰',
            catalog: '📦',
            configurator: '🔧',
            email: '✉️',
            seo: '🔍'
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
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item active">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>⚙️ Paramètres du site</h1>
            <button class="btn btn-primary" onclick="saveAllSettings()">💾 Enregistrer tout</button>
        </div>

        <div id="alerts"></div>

        <!-- Onglets par catégorie -->
        <div class="tabs">
            <?php
            $icons = [
                'general' => '⚙️',
                'pricing' => '💰',
                'catalog' => '📦',
                'configurator' => '🔧',
                'email' => '✉️',
                'seo' => '🔍',
                'page_builder' => '🏗️'
            ];
            $first = true;
            foreach (array_keys($grouped) as $category):
            ?>
            <div class="tab <?php echo $first ? 'active' : ''; ?>" onclick="showTab('<?php echo $category; ?>')">
                <?php echo $icons[$category] ?? '📋'; ?> <?php echo ucfirst($category); ?>
            </div>
            <?php $first = false; endforeach; ?>
        </div>

        <!-- Contenu des onglets -->
        <form id="settingsForm">
            <?php $first = true; foreach ($grouped as $category => $categorySettings): ?>
            <div class="tab-content <?php echo $first ? 'active' : ''; ?>" id="tab-<?php echo $category; ?>">
                <div class="card">
                    <h3><?php echo $icons[$category] ?? '📋'; ?> Paramètres <?php echo ucfirst($category); ?></h3>

                    <div class="settings-grid">
                        <?php foreach ($categorySettings as $setting): ?>
                        <div class="setting-item">
                            <div>
                                <div class="setting-label"><?php echo htmlspecialchars($setting['description'] ?? $setting['setting_key']); ?></div>
                                <div class="setting-key"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                            </div>
                            <div class="setting-input">
                                <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="<?php echo $setting['setting_key']; ?>" <?php echo $setting['setting_value'] === 'true' ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <?php elseif ($setting['setting_type'] === 'text' || $setting['setting_type'] === 'json'): ?>
                                <textarea name="<?php echo $setting['setting_key']; ?>"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <?php elseif ($setting['setting_type'] === 'number'): ?>
                                <input type="number" name="<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php else: ?>
                                <input type="text" name="<?php echo $setting['setting_key']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php $first = false; endforeach; ?>
        </form>

        <!-- Ajouter un nouveau paramètre -->
        <div class="card">
            <h3>➕ Ajouter un paramètre</h3>
            <form id="addSettingForm" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                <input type="text" name="key" placeholder="Clé (ex: site_name)" required style="padding: 10px; border: 1px solid var(--gray-200); border-radius: 8px;">
                <input type="text" name="value" placeholder="Valeur" required style="padding: 10px; border: 1px solid var(--gray-200); border-radius: 8px;">
                <select name="type" style="padding: 10px; border: 1px solid var(--gray-200); border-radius: 8px;">
                    <option value="string">Texte</option>
                    <option value="number">Nombre</option>
                    <option value="boolean">Booléen</option>
                    <option value="json">JSON</option>
                </select>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </main>

    <script>
        const API_URL = '../api/settings.php';

        function showTab(category) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            document.querySelector(`.tab[onclick="showTab('${category}')"]`).classList.add('active');
            document.getElementById(`tab-${category}`).classList.add('active');
        }

        async function saveAllSettings() {
            const form = document.getElementById('settingsForm');
            const formData = new FormData(form);
            const settings = {};

            // Convertir les checkboxes
            form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                settings[cb.name] = cb.checked ? 'true' : 'false';
            });

            // Autres champs
            for (let [key, value] of formData.entries()) {
                if (!settings[key]) {
                    settings[key] = value;
                }
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'bulk_update', settings })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Paramètres enregistrés !', 'success');
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur de communication', 'error');
            }
        }

        document.getElementById('addSettingForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                setting_key: formData.get('key'),
                setting_value: formData.get('value'),
                setting_type: formData.get('type'),
                category: 'general'
            };

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Paramètre ajouté', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        });

        function showAlert(message, type) {
            const container = document.getElementById('alerts');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }
    </script>
</body>
</html>
