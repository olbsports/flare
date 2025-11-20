<?php
/**
 * FLARE CUSTOM - Admin Dashboard
 * Interface d'administration
 */

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';

$productModel = new Product();

// Statistiques
try {
    $totalProducts = $productModel->count();
} catch (Exception $e) {
    $totalProducts = 0;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FLARE CUSTOM Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: #1d1d1f;
            color: #fff;
            padding: 20px 0;
            overflow-y: auto;
        }

        .logo {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            color: #FF4B26;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #FF4B26;
        }

        .nav-icon {
            width: 20px;
            margin-right: 12px;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 40px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h2 {
            font-size: 32px;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #FF4B26;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-label {
            color: #86868b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1d1d1f;
        }

        .stat-change {
            color: #34c759;
            font-size: 14px;
            margin-top: 8px;
        }

        /* Quick Actions */
        .quick-actions {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 40px;
        }

        .quick-actions h3 {
            font-size: 20px;
            margin-bottom: 24px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border: 2px solid #e5e5e7;
            border-radius: 12px;
            text-decoration: none;
            color: #1d1d1f;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: #FF4B26;
            background: #fff5f3;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .action-label {
            font-weight: 600;
            text-align: center;
        }

        /* Recent Activity */
        .activity-section {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .activity-section h3 {
            font-size: 20px;
            margin-bottom: 24px;
        }

        .activity-item {
            padding: 16px;
            border-bottom: 1px solid #e5e5e7;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            color: #86868b;
            font-size: 14px;
        }

        .btn-primary {
            background: #FF4B26;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #E63910;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">
            <h1>FLARE CUSTOM</h1>
            <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 4px;">Administration</p>
        </div>

        <a href="index.php" class="nav-item active">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>
        <a href="products.php" class="nav-item">
            <span class="nav-icon">📦</span>
            Produits
        </a>
        <a href="categories.php" class="nav-item">
            <span class="nav-icon">📁</span>
            Catégories
        </a>
        <a href="templates.php" class="nav-item">
            <span class="nav-icon">🎨</span>
            Templates
        </a>
        <a href="quotes.php" class="nav-item">
            <span class="nav-icon">💰</span>
            Devis
        </a>
        <a href="pages.php" class="nav-item">
            <span class="nav-icon">📄</span>
            Pages
        </a>
        <a href="media.php" class="nav-item">
            <span class="nav-icon">🖼️</span>
            Médias
        </a>
        <a href="configurator-admin-complete.html" class="nav-item">
            <span class="nav-icon">🔧</span>
            Configurateur
        </a>
        <a href="gestion-produits-complete.html" class="nav-item">
            <span class="nav-icon">✏️</span>
            Gestion Produits
        </a>
        <a href="settings.php" class="nav-item">
            <span class="nav-icon">⚙️</span>
            Paramètres
        </a>
        <a href="logout.php" class="nav-item" style="margin-top: 30px; opacity: 0.7;">
            <span class="nav-icon">🚪</span>
            Déconnexion
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h2>Dashboard</h2>
            <div class="user-menu">
                <span>Bonjour, <?php echo htmlspecialchars($_SESSION['admin_user']['username']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_user']['username'], 0, 1)); ?></div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Produits</div>
                <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                <div class="stat-change">+12% ce mois</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Devis en attente</div>
                <div class="stat-value">24</div>
                <div class="stat-change">3 nouveaux aujourd'hui</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Templates</div>
                <div class="stat-value">42</div>
                <div class="stat-change">+5 cette semaine</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Pages actives</div>
                <div class="stat-value">18</div>
                <div class="stat-change">Tout opérationnel</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Actions rapides</h3>
            <div class="actions-grid">
                <a href="products.php?action=add" class="action-btn">
                    <div class="action-icon">➕</div>
                    <div class="action-label">Ajouter un produit</div>
                </a>

                <a href="import.php" class="action-btn">
                    <div class="action-icon">📥</div>
                    <div class="action-label">Importer CSV</div>
                </a>

                <a href="templates.php?action=add" class="action-btn">
                    <div class="action-icon">🎨</div>
                    <div class="action-label">Ajouter un template</div>
                </a>

                <a href="pages.php?action=add" class="action-btn">
                    <div class="action-icon">📄</div>
                    <div class="action-label">Créer une page</div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
            <h3>Activité récente</h3>

            <div class="activity-item">
                <strong>Nouveau devis reçu</strong>
                <div class="activity-time">Il y a 15 minutes</div>
            </div>

            <div class="activity-item">
                <strong>Produit ajouté : Maillot Football Pro</strong>
                <div class="activity-time">Il y a 2 heures</div>
            </div>

            <div class="activity-item">
                <strong>Template mis à jour : Template 042</strong>
                <div class="activity-time">Hier à 14:30</div>
            </div>

            <div class="activity-item">
                <strong>Import CSV : 150 produits</strong>
                <div class="activity-time">Hier à 09:15</div>
            </div>
        </div>
    </main>
</body>
</html>
