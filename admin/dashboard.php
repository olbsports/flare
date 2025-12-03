<?php
/**
 * FLARE CUSTOM - Dashboard Admin Unifié
 * Panneau d'administration complet
 */

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';
require_once __DIR__ . '/../includes/Quote.php';
require_once __DIR__ . '/../includes/Blog.php';
require_once __DIR__ . '/../includes/Page.php';
require_once __DIR__ . '/../includes/Category.php';

// Initialiser les modèles
$productModel = new Product();
$quoteModel = new Quote();
$blogModel = new Blog();
$pageModel = new Page();
$categoryModel = new Category();

// Récupérer les statistiques
try {
    $stats = [
        'products' => $productModel->count(),
        'quotes_pending' => $quoteModel->count('pending'),
        'quotes_total' => $quoteModel->count(),
        'blog_posts' => $blogModel->count(),
        'pages' => $pageModel->count(),
        'categories' => $categoryModel->count()
    ];
} catch (Exception $e) {
    $stats = [
        'products' => 0,
        'quotes_pending' => 0,
        'quotes_total' => 0,
        'blog_posts' => 0,
        'pages' => 0,
        'categories' => 0
    ];
}

// Récupérer les derniers devis
try {
    $recentQuotes = $quoteModel->getAll([], 1, 5)['data'];
} catch (Exception $e) {
    $recentQuotes = [];
}

$currentUser = $_SESSION['admin_user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --dark: #1d1d1f;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-300: #d1d1d6;
            --gray-500: #86868b;
            --gray-700: #424245;
            --success: #34c759;
            --warning: #ff9500;
            --danger: #ff3b30;
            --info: #007aff;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-100);
            color: var(--dark);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a1c 0%, #2d2d30 100%);
            color: #fff;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 32px;
            letter-spacing: 3px;
            color: var(--primary);
        }

        .logo-subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-top: 4px;
        }

        .nav-section {
            padding: 16px 0;
        }

        .nav-section-title {
            padding: 8px 24px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            font-weight: 600;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            gap: 12px;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        .nav-item.active {
            background: rgba(255,75,38,0.15);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--primary);
            color: #fff;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            background: #fff;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .user-menu:hover {
            background: var(--gray-100);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: var(--gray-500);
        }

        .content {
            padding: 40px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-icon.products { background: rgba(0,122,255,0.1); }
        .stat-icon.quotes { background: rgba(255,149,0,0.1); }
        .stat-icon.blog { background: rgba(52,199,89,0.1); }
        .stat-icon.pages { background: rgba(175,82,222,0.1); }
        .stat-icon.categories { background: rgba(255,59,48,0.1); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--gray-500);
            font-size: 14px;
        }

        /* Quick Actions */
        .section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s ease;
            text-align: center;
        }

        .action-card:hover {
            border-color: var(--primary);
            background: rgba(255,75,38,0.03);
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .action-desc {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            font-weight: 600;
            color: var(--gray-500);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: var(--gray-100);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background: rgba(255,149,0,0.1); color: var(--warning); }
        .status-sent { background: rgba(0,122,255,0.1); color: var(--info); }
        .status-accepted { background: rgba(52,199,89,0.1); color: var(--success); }
        .status-rejected { background: rgba(255,59,48,0.1); color: var(--danger); }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
        }

        /* Two column layout */
        .two-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1200px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }

        /* Activity list */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            gap: 12px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .activity-time {
            font-size: 12px;
            color: var(--gray-500);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">FLARE CUSTOM</div>
            <div class="logo-subtitle">Panneau d'Administration</div>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Catalogue</div>
            <a href="products-manager.php" class="nav-item">
                <span class="nav-icon">📦</span>
                Produits
                <span class="nav-badge"><?php echo number_format($stats['products']); ?></span>
            </a>
            <a href="categories-manager.php" class="nav-item">
                <span class="nav-icon">📁</span>
                Catégories
            </a>
            <a href="configurator-manager.php" class="nav-item">
                <span class="nav-icon">🔧</span>
                Configurateur
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Commercial</div>
            <a href="quotes-manager.php" class="nav-item">
                <span class="nav-icon">💰</span>
                Devis
                <?php if ($stats['quotes_pending'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['quotes_pending']; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Contenu</div>
            <a href="pages-manager.php" class="nav-item">
                <span class="nav-icon">📄</span>
                Pages
            </a>
            <a href="blog-manager.php" class="nav-item">
                <span class="nav-icon">📝</span>
                Blog
            </a>
            <a href="media-manager.php" class="nav-item">
                <span class="nav-icon">🖼️</span>
                Médias
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Système</div>
            <a href="import-manager.php" class="nav-item">
                <span class="nav-icon">📥</span>
                Import / Export
            </a>
            <a href="settings-manager.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                Paramètres
            </a>
            <a href="logout.php" class="nav-item">
                <span class="nav-icon">🚪</span>
                Déconnexion
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <h1 class="header-title">Dashboard</h1>
            <div class="header-actions">
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        <div class="user-role"><?php echo ucfirst($currentUser['role'] ?? 'Admin'); ?></div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon products">📦</div>
                    <div class="stat-value"><?php echo number_format($stats['products']); ?></div>
                    <div class="stat-label">Produits</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon quotes">💰</div>
                    <div class="stat-value"><?php echo number_format($stats['quotes_pending']); ?></div>
                    <div class="stat-label">Devis en attente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blog">📝</div>
                    <div class="stat-value"><?php echo number_format($stats['blog_posts']); ?></div>
                    <div class="stat-label">Articles blog</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pages">📄</div>
                    <div class="stat-value"><?php echo number_format($stats['pages']); ?></div>
                    <div class="stat-label">Pages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon categories">📁</div>
                    <div class="stat-value"><?php echo number_format($stats['categories']); ?></div>
                    <div class="stat-label">Catégories</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Actions rapides</h2>
                </div>
                <div class="actions-grid">
                    <a href="products-manager.php?action=add" class="action-card">
                        <div class="action-icon">➕</div>
                        <div class="action-title">Nouveau produit</div>
                        <div class="action-desc">Ajouter un produit au catalogue</div>
                    </a>
                    <a href="import-manager.php" class="action-card">
                        <div class="action-icon">📥</div>
                        <div class="action-title">Importer CSV</div>
                        <div class="action-desc">Importer des données en masse</div>
                    </a>
                    <a href="blog-manager.php?action=add" class="action-card">
                        <div class="action-icon">📝</div>
                        <div class="action-title">Nouvel article</div>
                        <div class="action-desc">Créer un article de blog</div>
                    </a>
                    <a href="pages-manager.php?action=add" class="action-card">
                        <div class="action-icon">📄</div>
                        <div class="action-title">Nouvelle page</div>
                        <div class="action-desc">Créer une page de contenu</div>
                    </a>
                    <a href="quotes-manager.php" class="action-card">
                        <div class="action-icon">💰</div>
                        <div class="action-title">Voir les devis</div>
                        <div class="action-desc">Gérer les demandes de devis</div>
                    </a>
                    <a href="configurator-manager.php" class="action-card">
                        <div class="action-icon">🔧</div>
                        <div class="action-title">Configurateur</div>
                        <div class="action-desc">Configurer les options produits</div>
                    </a>
                </div>
            </div>

            <!-- Two columns: Recent quotes & Activity -->
            <div class="two-columns">
                <!-- Recent Quotes -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Derniers devis</h2>
                        <a href="quotes-manager.php" class="btn btn-secondary btn-sm">Voir tout</a>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Produit</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentQuotes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray-500);">
                                        Aucun devis pour le moment
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentQuotes as $quote): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($quote['reference']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($quote['client_prenom'] . ' ' . $quote['client_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['product_nom'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $quote['status']; ?>">
                                            <?php echo ucfirst($quote['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($quote['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Activity -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Activité récente</h2>
                    </div>
                    <ul class="activity-list">
                        <li class="activity-item">
                            <div class="activity-icon">📦</div>
                            <div class="activity-content">
                                <div class="activity-text">Catalogue produits disponible</div>
                                <div class="activity-time"><?php echo number_format($stats['products']); ?> produits en base</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">✅</div>
                            <div class="activity-content">
                                <div class="activity-text">Système admin opérationnel</div>
                                <div class="activity-time">Prêt à gérer votre contenu</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">🔧</div>
                            <div class="activity-content">
                                <div class="activity-text">Configurateur actif</div>
                                <div class="activity-time">Personnalisation produits disponible</div>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon">📝</div>
                            <div class="activity-content">
                                <div class="activity-text">Blog et pages</div>
                                <div class="activity-time">Gestion de contenu complète</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>
