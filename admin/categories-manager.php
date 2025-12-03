<?php
/**
 * FLARE CUSTOM - Gestionnaire de Catégories
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Category.php';

$categoryModel = new Category();
$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;
$category = null;

if ($categoryId && in_array($action, ['edit'])) {
    $category = $categoryModel->getById($categoryId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Catégories - FLARE CUSTOM Admin</title>
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

        .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .tab {
            padding: 10px 20px; border-radius: 8px; cursor: pointer;
            background: #fff; border: 2px solid var(--gray-200);
            font-weight: 600; transition: all 0.2s;
        }
        .tab.active { border-color: var(--primary); background: rgba(255,75,38,0.05); color: var(--primary); }
        .tab:hover { border-color: var(--primary); }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .category-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .category-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .category-content {
            padding: 16px;
        }

        .category-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .category-slug {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 8px;
        }

        .category-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--gray-100);
        }

        .category-type {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-sport { background: rgba(0,122,255,0.1); color: #007AFF; }
        .type-famille { background: rgba(52,199,89,0.1); color: var(--success); }

        .category-actions { display: flex; gap: 8px; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; font-family: inherit;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }

        .status-active { color: var(--success); }
        .status-inactive { color: var(--danger); }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 16px; padding: 32px; max-width: 500px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">FLARE CUSTOM</div>
        <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
        <a href="products-manager.php" class="nav-item">📦 Produits</a>
        <a href="categories-manager.php" class="nav-item active">📁 Catégories</a>
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
            <h1>📁 Gestion des Catégories</h1>
            <div>
                <button class="btn btn-primary" onclick="openAddModal()">➕ Nouvelle catégorie</button>
            </div>
        </div>

        <div id="alerts"></div>

        <?php if ($action === 'list'): ?>
        <!-- Onglets -->
        <div class="tabs">
            <div class="tab active" data-type="sport" onclick="filterByType('sport')">⚽ Sports</div>
            <div class="tab" data-type="famille" onclick="filterByType('famille')">👕 Familles de produits</div>
            <div class="tab" data-type="all" onclick="filterByType('all')">📋 Toutes</div>
        </div>

        <div id="categoriesContainer" class="categories-grid">
            <!-- Les catégories seront chargées ici -->
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal Ajouter/Modifier -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nouvelle catégorie</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="id" id="categoryId">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" id="categoryNom" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="categorySlug" placeholder="Généré automatiquement">
                </div>
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" id="categoryType" required>
                        <option value="sport">Sport</option>
                        <option value="famille">Famille de produits</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="categoryDescription"></textarea>
                </div>
                <div class="form-group">
                    <label>Image (URL)</label>
                    <input type="url" name="image" id="categoryImage" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>Ordre</label>
                    <input type="number" name="ordre" id="categoryOrdre" value="0">
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="active" id="categoryActive">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
                <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '../api/categories.php';
        let currentType = 'sport';
        let categories = [];

        // Charger les catégories
        async function loadCategories() {
            try {
                const response = await fetch(API_URL);
                const data = await response.json();

                if (data.success) {
                    categories = data.data;
                    renderCategories();
                }
            } catch (error) {
                showAlert('Erreur lors du chargement', 'error');
            }
        }

        function filterByType(type) {
            currentType = type;

            // Mettre à jour les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.type === type);
            });

            renderCategories();
        }

        function renderCategories() {
            const container = document.getElementById('categoriesContainer');
            let filtered = categories;

            if (currentType !== 'all') {
                filtered = categories.filter(c => c.type === currentType);
            }

            if (filtered.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: var(--gray-500);">
                        <div style="font-size: 48px; margin-bottom: 16px;">📁</div>
                        <p>Aucune catégorie</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = filtered.map(c => `
                <div class="category-card">
                    ${c.image ? `<img src="${c.image}" class="category-image" alt="">` : '<div class="category-image"></div>'}
                    <div class="category-content">
                        <div class="category-name">${c.nom}</div>
                        <div class="category-slug">/${c.slug}</div>
                        <div class="category-meta">
                            <span class="category-type type-${c.type}">${c.type}</span>
                            <div class="category-actions">
                                <button class="btn btn-secondary btn-sm" onclick="editCategory(${c.id})">✏️</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteCategory(${c.id})">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Nouvelle catégorie';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryType').value = currentType === 'all' ? 'sport' : currentType;
            document.getElementById('categoryModal').classList.add('active');
        }

        function editCategory(id) {
            const category = categories.find(c => c.id === id);
            if (!category) return;

            document.getElementById('modalTitle').textContent = 'Modifier la catégorie';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryNom').value = category.nom;
            document.getElementById('categorySlug').value = category.slug;
            document.getElementById('categoryType').value = category.type;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryImage').value = category.image || '';
            document.getElementById('categoryOrdre').value = category.ordre || 0;
            document.getElementById('categoryActive').value = category.active ? '1' : '0';
            document.getElementById('categoryModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }

        document.getElementById('categoryForm').addEventListener('submit', async function(e) {
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
                    showAlert(isEdit ? 'Catégorie mise à jour' : 'Catégorie créée', 'success');
                    closeModal();
                    loadCategories();
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        });

        async function deleteCategory(id) {
            if (!confirm('Supprimer cette catégorie ?')) return;

            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    showAlert('Catégorie supprimée', 'success');
                    loadCategories();
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

        // Charger au démarrage
        loadCategories();
    </script>
</body>
</html>
