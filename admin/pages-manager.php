<?php
/**
 * FLARE CUSTOM - Gestionnaire de Pages
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Page.php';

$pageModel = new Page();
$action = $_GET['action'] ?? 'list';
$pageId = $_GET['id'] ?? null;
$page = null;

if ($pageId && in_array($action, ['edit', 'view'])) {
    $page = $pageModel->getById($pageId);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Pages - FLARE CUSTOM Admin</title>
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
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { font-size: 12px; text-transform: uppercase; color: var(--gray-500); font-weight: 600; }
        tr:hover { background: var(--gray-100); }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-published { background: rgba(52,199,89,0.1); color: var(--success); }
        .status-draft { background: rgba(255,149,0,0.1); color: var(--warning); }
        .status-archived { background: var(--gray-100); color: var(--gray-500); }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; font-family: inherit;
        }
        .form-group textarea { min-height: 300px; resize: vertical; }

        .filters { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .filter-group input, .filter-group select {
            padding: 8px 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px;
        }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-500); }
        .actions { display: flex; gap: 8px; }

        .page-type { font-size: 11px; text-transform: uppercase; color: var(--gray-500); }
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
        <a href="pages-manager.php" class="nav-item active">📄 Pages</a>
        <a href="blog-manager.php" class="nav-item">📝 Blog</a>
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>📄 Gestion des Pages</h1>
            <div>
                <a href="?action=add" class="btn btn-primary">➕ Nouvelle page</a>
            </div>
        </div>

        <div id="alerts"></div>

        <?php if ($action === 'list'): ?>
        <!-- Liste des pages -->
        <div class="card">
            <div class="filters">
                <input type="text" id="searchInput" placeholder="Rechercher..." style="min-width: 250px;">
                <select id="typeFilter">
                    <option value="">Tous types</option>
                    <option value="page">Page</option>
                    <option value="category">Catégorie</option>
                    <option value="product">Produit</option>
                </select>
                <select id="statusFilter">
                    <option value="">Tous statuts</option>
                    <option value="published">Publié</option>
                    <option value="draft">Brouillon</option>
                    <option value="archived">Archivé</option>
                </select>
                <button class="btn btn-secondary" onclick="loadPages()">🔍 Filtrer</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Modifié le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pagesTable">
                    <tr>
                        <td colspan="6" class="empty-state">Chargement...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire page -->
        <div class="card">
            <h2 style="margin-bottom: 24px;"><?php echo $action === 'add' ? 'Nouvelle page' : 'Modifier la page'; ?></h2>

            <form id="pageForm">
                <?php if ($page): ?>
                <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Titre *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($page['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" placeholder="Généré automatiquement">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <option value="page" <?php echo ($page['type'] ?? 'page') === 'page' ? 'selected' : ''; ?>>Page</option>
                            <option value="category" <?php echo ($page['type'] ?? '') === 'category' ? 'selected' : ''; ?>>Catégorie</option>
                            <option value="product" <?php echo ($page['type'] ?? '') === 'product' ? 'selected' : ''; ?>>Produit</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Template</label>
                        <select name="template">
                            <option value="default" <?php echo ($page['template'] ?? 'default') === 'default' ? 'selected' : ''; ?>>Par défaut</option>
                            <option value="full-width" <?php echo ($page['template'] ?? '') === 'full-width' ? 'selected' : ''; ?>>Pleine largeur</option>
                            <option value="sidebar" <?php echo ($page['template'] ?? '') === 'sidebar' ? 'selected' : ''; ?>>Avec sidebar</option>
                            <option value="landing" <?php echo ($page['template'] ?? '') === 'landing' ? 'selected' : ''; ?>>Landing page</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status">
                            <option value="draft" <?php echo ($page['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                            <option value="published" <?php echo ($page['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publié</option>
                            <option value="archived" <?php echo ($page['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivé</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Contenu (HTML)</label>
                        <textarea name="content"><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                    </div>
                </div>

                <h3 style="margin: 32px 0 16px;">SEO</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($page['meta_keywords'] ?? ''); ?>">
                    </div>
                    <div class="form-group full">
                        <label>Meta Description</label>
                        <textarea name="meta_description" rows="2"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <?php if ($action === 'edit'): ?>
                    <button type="button" class="btn btn-success" onclick="publishPage()">📢 Publier</button>
                    <button type="button" class="btn btn-secondary" onclick="duplicatePage()">📋 Dupliquer</button>
                    <?php endif; ?>
                    <a href="pages-manager.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const API_URL = '../api/pages.php';

        async function loadPages() {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeFilter').value;
            const status = document.getElementById('statusFilter').value;

            let url = `${API_URL}?limit=50`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (type) url += `&type=${encodeURIComponent(type)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderPages(data.data);
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        function renderPages(pages) {
            const tbody = document.getElementById('pagesTable');

            if (!pages || pages.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Aucune page</td></tr>';
                return;
            }

            tbody.innerHTML = pages.map(p => `
                <tr>
                    <td><strong>${p.title}</strong></td>
                    <td><code>/${p.slug}</code></td>
                    <td><span class="page-type">${p.type}</span></td>
                    <td><span class="status-badge status-${p.status}">${getStatusLabel(p.status)}</span></td>
                    <td>${formatDate(p.updated_at)}</td>
                    <td>
                        <div class="actions">
                            <a href="?action=edit&id=${p.id}" class="btn btn-secondary btn-sm">✏️</a>
                            <button class="btn btn-danger btn-sm" onclick="deletePage(${p.id})">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('fr-FR');
        }

        function getStatusLabel(status) {
            const labels = { published: 'Publié', draft: 'Brouillon', archived: 'Archivé' };
            return labels[status] || status;
        }

        document.getElementById('pageForm')?.addEventListener('submit', async function(e) {
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
                    showAlert(isEdit ? 'Page mise à jour' : 'Page créée', 'success');
                    setTimeout(() => window.location.href = 'pages-manager.php', 1000);
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        });

        async function publishPage() {
            const id = document.querySelector('input[name="id"]').value;
            if (!id) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, action: 'publish' })
                });

                if ((await response.json()).success) {
                    showAlert('Page publiée', 'success');
                    document.querySelector('select[name="status"]').value = 'published';
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        async function duplicatePage() {
            const id = document.querySelector('input[name="id"]').value;
            if (!id) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'duplicate', id })
                });

                const result = await response.json();
                if (result.success) {
                    showAlert('Page dupliquée', 'success');
                    setTimeout(() => window.location.href = `?action=edit&id=${result.data.id}`, 1000);
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        async function deletePage(id) {
            if (!confirm('Supprimer cette page ?')) return;

            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                if ((await response.json()).success) {
                    showAlert('Page supprimée', 'success');
                    loadPages();
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

        <?php if ($action === 'list'): ?>
        loadPages();
        <?php endif; ?>
    </script>
</body>
</html>
