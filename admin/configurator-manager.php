<?php
/**
 * FLARE CUSTOM - Gestionnaire du Configurateur
 * Configuration des options de personnalisation par produit
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';

$productModel = new Product();
$productId = $_GET['product_id'] ?? null;
$product = null;

if ($productId) {
    $product = $productModel->getById($productId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurateur - FLARE CUSTOM Admin</title>
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
        .btn-success { background: var(--success); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card h3 { margin-bottom: 20px; font-size: 18px; }

        .product-selector {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 24px;
        }

        .product-selector select {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            max-width: 400px;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
        }

        .config-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .config-section h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--gray-100);
            border-radius: 8px;
            margin-bottom: 12px;
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

        .color-palette {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .color-swatch {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 2px solid var(--gray-200);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .color-swatch:hover {
            transform: scale(1.1);
        }

        .color-swatch.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255,75,38,0.3);
        }

        .sizes-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 12px;
        }

        .size-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px;
            background: var(--gray-100);
            border-radius: 6px;
            cursor: pointer;
        }

        .size-checkbox input {
            width: auto;
        }

        .price-rules {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }
        .alert-info { background: rgba(0,122,255,0.1); color: #007AFF; }

        .products-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .product-card {
            display: flex;
            gap: 16px;
            padding: 16px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .product-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .product-card img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--gray-100);
        }

        .product-card-info {
            flex: 1;
        }

        .product-card-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .product-card-ref {
            font-size: 12px;
            color: var(--gray-500);
        }

        .configured-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(52,199,89,0.1);
            color: var(--success);
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            margin-top: 4px;
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
        <a href="configurator-manager.php" class="nav-item active">🔧 Configurateur</a>
        <a href="quotes-manager.php" class="nav-item">💰 Devis</a>
        <a href="pages-manager.php" class="nav-item">📄 Pages</a>
        <a href="blog-manager.php" class="nav-item">📝 Blog</a>
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>🔧 Configuration du Configurateur</h1>
            <?php if ($product): ?>
            <a href="configurator-manager.php" class="btn btn-secondary">← Retour à la liste</a>
            <?php endif; ?>
        </div>

        <div id="alerts"></div>

        <?php if (!$product): ?>
        <!-- Liste des produits à configurer -->
        <div class="card">
            <h3>Sélectionner un produit à configurer</h3>
            <div class="product-selector">
                <select id="productSearch" onchange="goToProduct(this.value)">
                    <option value="">-- Rechercher un produit --</option>
                </select>
                <button class="btn btn-secondary" onclick="loadProducts()">🔄 Actualiser</button>
            </div>
        </div>

        <div id="productsList" class="products-list">
            <!-- Produits chargés dynamiquement -->
        </div>

        <?php else: ?>
        <!-- Configuration d'un produit -->
        <div class="alert alert-info">
            Configuration pour: <strong><?php echo htmlspecialchars($product['nom']); ?></strong>
            (<?php echo htmlspecialchars($product['reference']); ?>)
        </div>

        <form id="configForm">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

            <div class="config-grid">
                <!-- Options de design -->
                <div class="config-section">
                    <h4>🎨 Options de design</h4>

                    <div class="toggle-group">
                        <span>Design FLARE (sur mesure)</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_flare_design" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <span>Design client (fichier fourni)</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_client_design" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <span>Templates prédéfinis</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_templates" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Personnalisation -->
                <div class="config-section">
                    <h4>✏️ Personnalisation</h4>

                    <div class="toggle-group">
                        <span>Couleurs personnalisables</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_colors" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <span>Logos (ajout de logos)</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_logos" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Nombre max de logos</label>
                        <input type="number" name="max_logos" value="3" min="1" max="10">
                    </div>

                    <div class="toggle-group">
                        <span>Texte personnalisé (noms)</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_text" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <span>Numéros</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_numbers" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Tailles disponibles -->
                <div class="config-section">
                    <h4>📏 Tailles disponibles</h4>

                    <div class="sizes-grid">
                        <?php
                        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];
                        foreach ($sizes as $size):
                        ?>
                        <label class="size-checkbox">
                            <input type="checkbox" name="sizes[]" value="<?php echo $size; ?>" checked>
                            <?php echo $size; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label>Quantité minimum par taille</label>
                        <input type="number" name="min_qty_per_size" value="1" min="1">
                    </div>
                </div>

                <!-- Quantités -->
                <div class="config-section">
                    <h4>📦 Quantités</h4>

                    <div class="form-group">
                        <label>Quantité minimum totale</label>
                        <input type="number" name="min_total_quantity" value="5" min="1">
                    </div>

                    <div class="form-group">
                        <label>Quantité maximum totale</label>
                        <input type="number" name="max_total_quantity" value="1000" min="1">
                    </div>
                </div>

                <!-- Règles de prix -->
                <div class="config-section">
                    <h4>💰 Règles de prix supplémentaires</h4>

                    <div class="price-rules">
                        <div class="form-group">
                            <label>Surcoût logo (€)</label>
                            <input type="number" step="0.01" name="logo_extra" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Surcoût texte (€)</label>
                            <input type="number" step="0.01" name="text_extra" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Frais design FLARE (€)</label>
                            <input type="number" step="0.01" name="flare_design_fee" value="50" min="0">
                        </div>
                        <div class="form-group">
                            <label>Surcoût express (%)</label>
                            <input type="number" name="express_surcharge" value="30" min="0">
                        </div>
                    </div>
                </div>

                <!-- Délais -->
                <div class="config-section">
                    <h4>⏰ Délais de livraison</h4>

                    <div class="form-group">
                        <label>Délai standard (jours)</label>
                        <input type="number" name="lead_time_standard" value="21" min="1">
                    </div>

                    <div class="form-group">
                        <label>Délai express (jours)</label>
                        <input type="number" name="lead_time_express" value="10" min="1">
                    </div>
                </div>
            </div>

            <div style="margin-top: 32px; display: flex; gap: 16px;">
                <button type="submit" class="btn btn-primary">💾 Enregistrer la configuration</button>
                <button type="button" class="btn btn-success" onclick="applyToSimilar()">📋 Appliquer aux produits similaires</button>
                <a href="configurator-manager.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
        <?php endif; ?>
    </main>

    <script>
        const API_URL = '../api/products.php';
        const CONFIG_API = '../api/product-config.php';

        <?php if (!$product): ?>
        // Charger les produits
        async function loadProducts() {
            try {
                const response = await fetch(`${API_URL}?limit=100`);
                const data = await response.json();

                if (data.success) {
                    renderProducts(data.data);
                    populateSelect(data.data);
                }
            } catch (error) {
                showAlert('Erreur de chargement', 'error');
            }
        }

        function renderProducts(products) {
            const container = document.getElementById('productsList');

            container.innerHTML = products.slice(0, 50).map(p => `
                <div class="product-card" onclick="goToProduct(${p.id})">
                    ${p.photo_1 ? `<img src="${p.photo_1}" alt="">` : '<div style="width:60px;height:60px;background:#f5f5f7;border-radius:8px;"></div>'}
                    <div class="product-card-info">
                        <div class="product-card-name">${p.nom}</div>
                        <div class="product-card-ref">${p.reference}</div>
                        <span class="configured-badge">Configurer</span>
                    </div>
                </div>
            `).join('');
        }

        function populateSelect(products) {
            const select = document.getElementById('productSearch');
            products.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `${p.reference} - ${p.nom}`;
                select.appendChild(option);
            });
        }

        function goToProduct(id) {
            if (id) {
                window.location.href = `?product_id=${id}`;
            }
        }

        loadProducts();

        <?php else: ?>
        // Formulaire de configuration
        document.getElementById('configForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {};

            // Convertir FormData en objet
            for (let [key, value] of formData.entries()) {
                if (key === 'sizes[]') {
                    if (!data.sizes) data.sizes = [];
                    data.sizes.push(value);
                } else {
                    data[key] = value;
                }
            }

            // Convertir les checkboxes
            const checkboxes = this.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => {
                if (!cb.name.includes('[]')) {
                    data[cb.name] = cb.checked;
                }
            });

            try {
                const response = await fetch(CONFIG_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Configuration enregistrée !', 'success');
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur de communication', 'error');
            }
        });

        async function applyToSimilar() {
            if (!confirm('Appliquer cette configuration à tous les produits de la même famille ?')) return;

            showAlert('Fonctionnalité en cours de développement', 'info');
        }
        <?php endif; ?>

        function showAlert(message, type) {
            const container = document.getElementById('alerts');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }
    </script>
</body>
</html>
