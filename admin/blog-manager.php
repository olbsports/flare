<?php
/**
 * FLARE CUSTOM - Gestionnaire de Blog
 */
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Blog.php';

$blogModel = new Blog();
$action = $_GET['action'] ?? 'list';
$articleId = $_GET['id'] ?? null;
$article = null;

if ($articleId && in_array($action, ['edit', 'view'])) {
    $article = $blogModel->getById($articleId);
}

$categories = $blogModel->getCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Blog - FLARE CUSTOM Admin</title>
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

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .article-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .article-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: var(--gray-100);
        }

        .article-content {
            padding: 20px;
        }

        .article-category {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,75,38,0.1);
            color: var(--primary);
            margin-bottom: 12px;
        }

        .article-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .article-excerpt {
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--gray-100);
        }

        .article-date {
            font-size: 12px;
            color: var(--gray-500);
        }

        .article-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-published { background: rgba(52,199,89,0.1); color: var(--success); }
        .status-draft { background: rgba(255,149,0,0.1); color: var(--warning); }
        .status-archived { background: var(--gray-100); color: var(--gray-500); }

        .article-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; font-family: inherit;
        }
        .form-group textarea { min-height: 200px; resize: vertical; }

        .filters { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: var(--gray-500); }
        .filter-group input, .filter-group select {
            padding: 8px 12px; border: 1px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; min-width: 160px;
        }

        .alert { padding: 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray-500); }
        .empty-state-icon { font-size: 48px; margin-bottom: 16px; }

        .tags-input { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; border: 1px solid var(--gray-200); border-radius: 8px; min-height: 44px; }
        .tag { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: var(--gray-100); border-radius: 20px; font-size: 13px; }
        .tag button { background: none; border: none; cursor: pointer; font-size: 14px; }
        .tags-input input { border: none; outline: none; flex: 1; min-width: 100px; }
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
        <a href="blog-manager.php" class="nav-item active">📝 Blog</a>
        <a href="media-manager.php" class="nav-item">🖼️ Médias</a>
        <a href="import-manager.php" class="nav-item">📥 Import</a>
        <a href="settings-manager.php" class="nav-item">⚙️ Paramètres</a>
        <a href="logout.php" class="nav-item">🚪 Déconnexion</a>
    </nav>

    <main class="main-content">
        <div class="header">
            <h1>📝 Gestion du Blog</h1>
            <div>
                <a href="?action=add" class="btn btn-primary">➕ Nouvel article</a>
            </div>
        </div>

        <div id="alerts"></div>

        <?php if ($action === 'list'): ?>
        <!-- Liste des articles -->
        <div class="card">
            <div class="filters">
                <div class="filter-group">
                    <label>Rechercher</label>
                    <input type="text" id="searchInput" placeholder="Titre, contenu...">
                </div>
                <div class="filter-group">
                    <label>Catégorie</label>
                    <select id="categoryFilter">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Statut</label>
                    <select id="statusFilter">
                        <option value="">Tous</option>
                        <option value="published">Publié</option>
                        <option value="draft">Brouillon</option>
                        <option value="archived">Archivé</option>
                    </select>
                </div>
                <div class="filter-group" style="justify-content: flex-end;">
                    <label>&nbsp;</label>
                    <button class="btn btn-secondary" onclick="loadArticles()">🔍 Filtrer</button>
                </div>
            </div>
        </div>

        <div id="articlesContainer" class="articles-grid">
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">📝</div>
                <p>Chargement des articles...</p>
            </div>
        </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire article -->
        <div class="card">
            <h2 style="margin-bottom: 24px;"><?php echo $action === 'add' ? 'Nouvel article' : 'Modifier l\'article'; ?></h2>

            <form id="articleForm">
                <?php if ($article): ?>
                <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group full">
                        <label>Titre *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" placeholder="Généré automatiquement">
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="category">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($article['category'] ?? '') === $cat['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Image mise en avant</label>
                        <input type="url" name="featured_image" value="<?php echo htmlspecialchars($article['featured_image'] ?? ''); ?>" placeholder="URL de l'image">
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status">
                            <option value="draft" <?php echo ($article['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                            <option value="published" <?php echo ($article['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publié</option>
                            <option value="archived" <?php echo ($article['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archivé</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Extrait</label>
                        <textarea name="excerpt" rows="3"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Contenu</label>
                        <textarea name="content" rows="15"><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                    </div>
                </div>

                <h3 style="margin: 32px 0 16px;">SEO</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" value="<?php echo htmlspecialchars($article['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($article['meta_keywords'] ?? ''); ?>">
                    </div>
                    <div class="form-group full">
                        <label>Meta Description</label>
                        <textarea name="meta_description" rows="2"><?php echo htmlspecialchars($article['meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <?php if ($action === 'edit'): ?>
                    <button type="button" class="btn btn-success" onclick="publishArticle()">📢 Publier</button>
                    <?php endif; ?>
                    <a href="blog-manager.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const API_URL = '../api/blog.php';

        // Charger les articles
        async function loadArticles() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;

            let url = `${API_URL}?limit=50`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (category) url += `&category=${encodeURIComponent(category)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderArticles(data.data);
                }
            } catch (error) {
                showAlert('Erreur lors du chargement', 'error');
            }
        }

        function renderArticles(articles) {
            const container = document.getElementById('articlesContainer');

            if (!articles || articles.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-state-icon">📝</div>
                        <p>Aucun article trouvé</p>
                        <a href="?action=add" class="btn btn-primary" style="margin-top: 16px;">Créer un article</a>
                    </div>
                `;
                return;
            }

            container.innerHTML = articles.map(a => `
                <div class="article-card">
                    ${a.featured_image ? `<img src="${a.featured_image}" class="article-image" alt="">` : '<div class="article-image"></div>'}
                    <div class="article-content">
                        ${a.category ? `<span class="article-category">${a.category}</span>` : ''}
                        <h3 class="article-title">${a.title}</h3>
                        <p class="article-excerpt">${a.excerpt ? a.excerpt.substring(0, 120) + '...' : 'Pas d\'extrait'}</p>
                        <div class="article-meta">
                            <span class="article-date">${formatDate(a.created_at)}</span>
                            <span class="article-status status-${a.status}">${getStatusLabel(a.status)}</span>
                        </div>
                        <div class="article-actions">
                            <a href="?action=edit&id=${a.id}" class="btn btn-secondary btn-sm">✏️ Modifier</a>
                            <button class="btn btn-danger btn-sm" onclick="deleteArticle(${a.id})">🗑️</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function getStatusLabel(status) {
            const labels = { published: 'Publié', draft: 'Brouillon', archived: 'Archivé' };
            return labels[status] || status;
        }

        // Formulaire
        document.getElementById('articleForm')?.addEventListener('submit', async function(e) {
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
                    showAlert(isEdit ? 'Article mis à jour' : 'Article créé', 'success');
                    setTimeout(() => window.location.href = 'blog-manager.php', 1000);
                } else {
                    showAlert(result.error || 'Erreur', 'error');
                }
            } catch (error) {
                showAlert('Erreur de communication', 'error');
            }
        });

        async function publishArticle() {
            const id = document.querySelector('input[name="id"]').value;
            if (!id) return;

            try {
                const response = await fetch(API_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status: 'published' })
                });

                const result = await response.json();
                if (result.success) {
                    showAlert('Article publié', 'success');
                    document.querySelector('select[name="status"]').value = 'published';
                }
            } catch (error) {
                showAlert('Erreur', 'error');
            }
        }

        async function deleteArticle(id) {
            if (!confirm('Supprimer cet article ?')) return;

            try {
                const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.success) {
                    showAlert('Article supprimé', 'success');
                    loadArticles();
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
        <?php if ($action === 'list'): ?>
        loadArticles();
        document.getElementById('searchInput').addEventListener('input', debounce(() => loadArticles(), 300));

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
