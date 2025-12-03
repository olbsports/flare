<?php
/**
 * FLARE CUSTOM - Gestionnaire de Produits
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';
require_once __DIR__ . '/../includes/Category.php';

$error = null;
$sports = [];
$familles = [];
$product = null;

try {
    $productModel = new Product();
    $categoryModel = new Category();

    $action = $_GET['action'] ?? 'list';
    $productId = $_GET['id'] ?? null;

    if ($productId && in_array($action, ['edit', 'view'])) {
        $product = $productModel->getById($productId);
    }

    // Récupérer les catégories
    $sports = $categoryModel->getByType('sport');
    $familles = $categoryModel->getByType('famille');
} catch (Exception $e) {
    $error = "Erreur BDD: " . $e->getMessage() . " - Avez-vous lancé l'import depuis /admin/import-content.php ?";
    $action = 'list';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Produits - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --dark: #1d1d1f;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-300: #d1d1d6;
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

        .filters { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: var(--gray-500); }
        .filter-group input, .filter-group select {
            padding: 8px 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; min-width: 180px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { font-size: 12px; text-transform: uppercase; color: var(--gray-500); font-weight: 600; }
        tr:hover { background: var(--gray-100); }

        .product-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: var(--gray-100); }
        .status-active { color: var(--success); }
        .status-inactive { color: var(--danger); }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }

        .price-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .price-item { text-align: center; }
        .price-item label { display: block; font-size: 11px; color: var(--gray-500); margin-bottom: 4px; }
        .price-item input { width: 100%; padding: 8px; text-align: center; }

        .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 24px; }
        .pagination a, .pagination span {
            padding: 8px 14px; border-radius: 6px; text-decoration: none;
            background: #fff; color: var(--dark); border: 1px solid var(--gray-200);
        }
        .pagination a:hover { background: var(--gray-100); }
        .pagination .active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 16px; padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-500); }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; }

        .actions { display: flex; gap: 8px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">FLARE CUSTOM</div>
        <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
        <a href="products-manager.php" class="nav-item active">📦 Produits</a>
        <a href="categories-manager.php" class="nav-item">📁 Catégories</a>
        <a href="configurator-manager.php" class="nav-item">🔧 Configurateur</a>
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
            <h1>📦 Gestion des Produits</h1>
            <div>
                <a href="?action=add" class="btn btn-primary">➕ Nouveau produit</a>
            </div>
        </div>

        <?php if ($error): ?>
        <div style="background:#ff3b30;color:#fff;padding:16px;border-radius:8px;margin-bottom:20px;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
            <br><a href="import-content.php" style="color:#fff;text-decoration:underline;">Lancer l'import</a>
        </div>
        <?php endif; ?>

        <div id="alerts"></div>

        <?php if ($action === 'list'): ?>
        <!-- Liste des produits -->
        <div class="card">
            <div class="filters">
                <div class="filter-group">
                    <label>Rechercher</label>
                    <input type="text" id="searchInput" placeholder="Référence, nom...">
                </div>
                <div class="filter-group">
                    <label>Sport</label>
                    <select id="sportFilter">
                        <option value="">Tous les sports</option>
                        <?php foreach ($sports as $sport): ?>
                        <option value="<?php echo htmlspecialchars($sport['nom']); ?>"><?php echo htmlspecialchars($sport['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Famille</label>
                    <select id="familleFilter">
                        <option value="">Toutes les familles</option>
                        <?php foreach ($familles as $famille): ?>
                        <option value="<?php echo htmlspecialchars($famille['nom']); ?>"><?php echo htmlspecialchars($famille['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Statut</label>
                    <select id="statusFilter">
                        <option value="">Tous</option>
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
                <div class="filter-group" style="justify-content: flex-end;">
                    <label>&nbsp;</label>
                    <button class="btn btn-secondary" onclick="loadProducts()">🔍 Filtrer</button>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Référence</th>
                            <th>Nom</th>
                            <th>Sport</th>
                            <th>Famille</th>
                            <th>Prix (1 pièce)</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTable">
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-state-icon">📦</div>
                                <p>Chargement des produits...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination" class="pagination"></div>
        </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire produit -->
        <div class="card">
            <h2 style="margin-bottom: 24px;"><?php echo $action === 'add' ? 'Nouveau produit' : 'Modifier le produit'; ?></h2>

            <form id="productForm">
                <?php if ($product): ?>
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Référence *</label>
                        <input type="text" name="reference" value="<?php echo htmlspecialchars($product['reference'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($product['nom'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Sport</label>
                        <select name="sport">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo htmlspecialchars($sport['nom']); ?>" <?php echo ($product['sport'] ?? '') === $sport['nom'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sport['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Famille</label>
                        <select name="famille">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($familles as $famille): ?>
                            <option value="<?php echo htmlspecialchars($famille['nom']); ?>" <?php echo ($product['famille'] ?? '') === $famille['nom'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($famille['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tissu</label>
                        <input type="text" name="tissu" value="<?php echo htmlspecialchars($product['tissu'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Grammage</label>
                        <input type="text" name="grammage" value="<?php echo htmlspecialchars($product['grammage'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Genre</label>
                        <select name="genre">
                            <option value="">-- Sélectionner --</option>
                            <option value="Homme" <?php echo ($product['genre'] ?? '') === 'Homme' ? 'selected' : ''; ?>>Homme</option>
                            <option value="Femme" <?php echo ($product['genre'] ?? '') === 'Femme' ? 'selected' : ''; ?>>Femme</option>
                            <option value="Mixte" <?php echo ($product['genre'] ?? '') === 'Mixte' ? 'selected' : ''; ?>>Mixte</option>
                            <option value="Enfant" <?php echo ($product['genre'] ?? '') === 'Enfant' ? 'selected' : ''; ?>>Enfant</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="active">
                            <option value="1" <?php echo ($product['active'] ?? 1) == 1 ? 'selected' : ''; ?>>Actif</option>
                            <option value="0" <?php echo ($product['active'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Description SEO</label>
                        <textarea name="description_seo"><?php echo htmlspecialchars($product['description_seo'] ?? ''); ?></textarea>
                    </div>
                </div>

                <h3 style="margin: 32px 0 16px;">Tarifs (€ HT)</h3>
                <div class="price-grid">
                    <?php
                    $priceTiers = [1, 5, 10, 20, 50, 100, 250, 500];
                    foreach ($priceTiers as $tier):
                    ?>
                    <div class="price-item">
                        <label><?php echo $tier; ?> pièce<?php echo $tier > 1 ? 's' : ''; ?></label>
                        <input type="number" step="0.01" name="prix_<?php echo $tier; ?>" value="<?php echo $product['prix_' . $tier] ?? ''; ?>" placeholder="0.00">
                    </div>
                    <?php endforeach; ?>
                </div>

                <h3 style="margin: 32px 0 16px;">Photos</h3>
                <div class="form-grid">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="form-group">
                        <label>Photo <?php echo $i; ?></label>
                        <input type="url" name="photo_<?php echo $i; ?>" value="<?php echo htmlspecialchars($product['photo_' . $i] ?? ''); ?>" placeholder="URL de l'image">
                    </div>
                    <?php endfor; ?>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <a href="products-manager.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal de confirmation -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <p>Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.</p>
            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../api/products.php';
        let currentPage = 1;
        let deleteProductId = null;

        // Charger les produits
        async function loadProducts(page = 1) {
            currentPage = page;
            const search = document.getElementById('searchInput').value;
            const sport = document.getElementById('sportFilter').value;
            const famille = document.getElementById('familleFilter').value;
            const status = document.getElementById('statusFilter').value;

            let url = `${API_URL}?page=${page}&limit=20`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (sport) url += `&sport=${encodeURIComponent(sport)}`;
            if (famille) url += `&famille=${encodeURIComponent(famille)}`;
            if (status !== '') url += `&active=${status}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderProducts(data.data);
                    renderPagination(data.pagination);
                }
            } catch (error) {
                showAlert('Erreur lors du chargement des produits', 'error');
            }
        }

        function renderProducts(products) {
            const tbody = document.getElementById('productsTable');

            if (!products || products.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <p>Aucun produit trouvé</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = products.map(p => `
                <tr>
                    <td>
                        ${p.photo_1 ? `<img src="${p.photo_1}" class="product-thumb" alt="">` : '<div class="product-thumb"></div>'}
                    </td>
                    <td><strong>${p.reference}</strong></td>
                    <td>${p.nom}</td>
                    <td>${p.sport || '-'}</td>
                    <td>${p.famille || '-'}</td>
                    <td>${p.prix_1 ? p.prix_1 + ' €' : '-'}</td>
                    <td>
                        <span class="${p.active == 1 ? 'status-active' : 'status-inactive'}">
                            ${p.active == 1 ? '● Actif' : '○ Inactif'}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=${p.id}" class="btn btn-secondary btn-sm">✏️</a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(${p.id})">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!pagination || pagination.pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === pagination.page) {
                    html += `<span class="active">${i}</span>`;
                } else {
                    html += `<a href="#" onclick="loadProducts(${i}); return false;">${i}</a>`;
                }
            }
            container.innerHTML = html;
        }

        // Formulaire produit
        document.getElementById('productForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const isEdit = !!data.id;

            try {
                const response = await fetch(API_URL, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(isEdit ? 'Produit mis à jour' : 'Produit créé', 'success');
                    setTimeout(() => window.location.href = 'products-manager.php', 1000);
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur de communication avec le serveur', 'error');
            }
        });

        // Suppression
        function confirmDelete(id) {
            deleteProductId = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteProductId = null;
        }

        document.getElementById('confirmDelete').addEventListener('click', async function() {
            if (!deleteProductId) return;

            try {
                const response = await fetch(`${API_URL}?id=${deleteProductId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    showAlert('Produit supprimé', 'success');
                    closeModal();
                    loadProducts(currentPage);
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur de communication', 'error');
            }
        });

        function showAlert(message, type) {
            const container = document.getElementById('alerts');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 3000);
        }

        // Charger les produits au démarrage
        <?php if ($action === 'list'): ?>
        loadProducts();

        // Recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', debounce(() => loadProducts(), 300));

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        <?php endif; ?>
    </script>
</body>
</html>
