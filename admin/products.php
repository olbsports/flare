<?php
/**
 * FLARE CUSTOM - Products Management
 * Gestion des produits
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
$action = $_GET['action'] ?? 'list';
$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add') {
            $productModel->create($_POST);
            $success = "Produit ajouté avec succès";
            $action = 'list';
        } elseif ($action === 'edit') {
            $id = $_POST['id'] ?? 0;
            unset($_POST['id']);
            $productModel->update($id, $_POST);
            $success = "Produit mis à jour avec succès";
            $action = 'list';
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $productModel->delete($id);
            $success = "Produit supprimé avec succès";
            $action = 'list';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des données selon l'action
if ($action === 'list') {
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';
    $sport = $_GET['sport'] ?? '';
    $famille = $_GET['famille'] ?? '';

    $filters = [
        'page' => $page,
        'limit' => 20,
        'search' => $search,
        'sport' => $sport,
        'famille' => $famille
    ];

    $products = $productModel->getAll($filters);
    $totalProducts = $productModel->count($filters);
    $totalPages = ceil($totalProducts / $filters['limit']);
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    $product = $productModel->getById($id);
    if (!$product) {
        $error = "Produit non trouvé";
        $action = 'list';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - FLARE CUSTOM Admin</title>
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
            z-index: 1000;
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

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 75, 38, 0.4);
        }

        .btn-secondary {
            background: #e5e5e7;
            color: #1d1d1f;
        }

        .btn-secondary:hover {
            background: #d1d1d6;
        }

        .btn-danger {
            background: #ff3b30;
            color: #fff;
        }

        /* Search and Filters */
        .filters {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #86868b;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            padding: 12px 16px;
            border: 2px solid #e5e5e7;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #FF4B26;
        }

        /* Table */
        .products-table {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f5f5f7;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #1d1d1f;
            font-size: 14px;
        }

        td {
            padding: 16px;
            border-top: 1px solid #e5e5e7;
        }

        tr:hover {
            background: #fafafa;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-ref {
            font-family: 'Courier New', monospace;
            color: #86868b;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1f4e0;
            color: #1e8e3e;
        }

        .badge-warning {
            background: #fff4e0;
            color: #c77700;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
        }

        .page-link {
            padding: 8px 12px;
            border: 2px solid #e5e5e7;
            border-radius: 8px;
            text-decoration: none;
            color: #1d1d1f;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            border-color: #FF4B26;
            color: #FF4B26;
        }

        .page-link.active {
            background: #FF4B26;
            color: #fff;
            border-color: #FF4B26;
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
        }

        /* Form */
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            max-width: 800px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-row-full {
            margin-bottom: 24px;
        }

        textarea.form-input {
            min-height: 120px;
            resize: vertical;
        }

        .price-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
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

        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>
        <a href="products.php" class="nav-item active">
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
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="header">
                <h2>Produits</h2>
                <a href="?action=add" class="btn btn-primary">➕ Ajouter un produit</a>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filters-grid">
                    <div class="form-group">
                        <label class="form-label">Recherche</label>
                        <input type="text" name="search" class="form-input" placeholder="Référence, nom, description..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sport</label>
                        <select name="sport" class="form-select">
                            <option value="">Tous</option>
                            <option value="Football" <?php echo $sport === 'Football' ? 'selected' : ''; ?>>Football</option>
                            <option value="Basketball" <?php echo $sport === 'Basketball' ? 'selected' : ''; ?>>Basketball</option>
                            <option value="Rugby" <?php echo $sport === 'Rugby' ? 'selected' : ''; ?>>Rugby</option>
                            <option value="Handball" <?php echo $sport === 'Handball' ? 'selected' : ''; ?>>Handball</option>
                            <option value="Volleyball" <?php echo $sport === 'Volleyball' ? 'selected' : ''; ?>>Volleyball</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Famille</label>
                        <select name="famille" class="form-select">
                            <option value="">Toutes</option>
                            <option value="Maillot" <?php echo $famille === 'Maillot' ? 'selected' : ''; ?>>Maillot</option>
                            <option value="Short" <?php echo $famille === 'Short' ? 'selected' : ''; ?>>Short</option>
                            <option value="Polo" <?php echo $famille === 'Polo' ? 'selected' : ''; ?>>Polo</option>
                            <option value="Veste" <?php echo $famille === 'Veste' ? 'selected' : ''; ?>>Veste</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </form>
            </div>

            <!-- Table -->
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Référence</th>
                            <th>Nom</th>
                            <th>Sport</th>
                            <th>Famille</th>
                            <th>Prix (à partir de)</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #86868b;">
                                    Aucun produit trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['photo_1']): ?>
                                            <img src="<?php echo htmlspecialchars($product['photo_1']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="product-image" style="background: #e5e5e7;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="product-ref"><?php echo htmlspecialchars($product['reference']); ?></span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($product['nom']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['sport'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($product['famille'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($product['prix_1']): ?>
                                            <strong><?php echo number_format($product['prix_1'], 2); ?> €</strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['active']): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-small">✏️ Éditer</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-danger btn-small">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sport=<?php echo urlencode($sport); ?>&famille=<?php echo urlencode($famille); ?>" class="page-link">← Précédent</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sport=<?php echo urlencode($sport); ?>&famille=<?php echo urlencode($famille); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sport=<?php echo urlencode($sport); ?>&famille=<?php echo urlencode($famille); ?>" class="page-link">Suivant →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <div class="header">
                <h2><?php echo $action === 'add' ? 'Ajouter un produit' : 'Éditer le produit'; ?></h2>
                <a href="?" class="btn btn-secondary">← Retour à la liste</a>
            </div>

            <div class="form-card">
                <form method="POST">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Référence *</label>
                            <input type="text" name="reference" class="form-input" required value="<?php echo htmlspecialchars($product['reference'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-input" required value="<?php echo htmlspecialchars($product['nom'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Sport</label>
                            <select name="sport" class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="Football" <?php echo ($product['sport'] ?? '') === 'Football' ? 'selected' : ''; ?>>Football</option>
                                <option value="Basketball" <?php echo ($product['sport'] ?? '') === 'Basketball' ? 'selected' : ''; ?>>Basketball</option>
                                <option value="Rugby" <?php echo ($product['sport'] ?? '') === 'Rugby' ? 'selected' : ''; ?>>Rugby</option>
                                <option value="Handball" <?php echo ($product['sport'] ?? '') === 'Handball' ? 'selected' : ''; ?>>Handball</option>
                                <option value="Volleyball" <?php echo ($product['sport'] ?? '') === 'Volleyball' ? 'selected' : ''; ?>>Volleyball</option>
                                <option value="Running" <?php echo ($product['sport'] ?? '') === 'Running' ? 'selected' : ''; ?>>Running</option>
                                <option value="Cyclisme" <?php echo ($product['sport'] ?? '') === 'Cyclisme' ? 'selected' : ''; ?>>Cyclisme</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Famille</label>
                            <select name="famille" class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="Maillot" <?php echo ($product['famille'] ?? '') === 'Maillot' ? 'selected' : ''; ?>>Maillot</option>
                                <option value="Short" <?php echo ($product['famille'] ?? '') === 'Short' ? 'selected' : ''; ?>>Short</option>
                                <option value="Polo" <?php echo ($product['famille'] ?? '') === 'Polo' ? 'selected' : ''; ?>>Polo</option>
                                <option value="Veste" <?php echo ($product['famille'] ?? '') === 'Veste' ? 'selected' : ''; ?>>Veste</option>
                                <option value="Pantalon" <?php echo ($product['famille'] ?? '') === 'Pantalon' ? 'selected' : ''; ?>>Pantalon</option>
                                <option value="Débardeur" <?php echo ($product['famille'] ?? '') === 'Débardeur' ? 'selected' : ''; ?>>Débardeur</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row-full">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row-full">
                        <div class="form-group">
                            <label class="form-label">Description SEO</label>
                            <textarea name="description_seo" class="form-input"><?php echo htmlspecialchars($product['description_seo'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tissu</label>
                            <input type="text" name="tissu" class="form-input" value="<?php echo htmlspecialchars($product['tissu'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grammage</label>
                            <input type="text" name="grammage" class="form-input" value="<?php echo htmlspecialchars($product['grammage'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Genre</label>
                            <select name="genre" class="form-select">
                                <option value="">Sélectionner...</option>
                                <option value="Homme" <?php echo ($product['genre'] ?? '') === 'Homme' ? 'selected' : ''; ?>>Homme</option>
                                <option value="Femme" <?php echo ($product['genre'] ?? '') === 'Femme' ? 'selected' : ''; ?>>Femme</option>
                                <option value="Mixte" <?php echo ($product['genre'] ?? '') === 'Mixte' ? 'selected' : ''; ?>>Mixte</option>
                                <option value="Enfant" <?php echo ($product['genre'] ?? '') === 'Enfant' ? 'selected' : ''; ?>>Enfant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Étiquettes</label>
                            <input type="text" name="etiquettes" class="form-input" value="<?php echo htmlspecialchars($product['etiquettes'] ?? ''); ?>" placeholder="Séparées par des virgules">
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px; color: #FF4B26;">Prix dégressifs</h3>
                    <div class="price-grid">
                        <div class="form-group">
                            <label class="form-label">1 pièce</label>
                            <input type="number" step="0.01" name="prix_1" class="form-input" value="<?php echo $product['prix_1'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">5 pièces</label>
                            <input type="number" step="0.01" name="prix_5" class="form-input" value="<?php echo $product['prix_5'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">10 pièces</label>
                            <input type="number" step="0.01" name="prix_10" class="form-input" value="<?php echo $product['prix_10'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">20 pièces</label>
                            <input type="number" step="0.01" name="prix_20" class="form-input" value="<?php echo $product['prix_20'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">50 pièces</label>
                            <input type="number" step="0.01" name="prix_50" class="form-input" value="<?php echo $product['prix_50'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">100 pièces</label>
                            <input type="number" step="0.01" name="prix_100" class="form-input" value="<?php echo $product['prix_100'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">250 pièces</label>
                            <input type="number" step="0.01" name="prix_250" class="form-input" value="<?php echo $product['prix_250'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">500 pièces</label>
                            <input type="number" step="0.01" name="prix_500" class="form-input" value="<?php echo $product['prix_500'] ?? ''; ?>">
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px; color: #FF4B26;">Photos</h3>
                    <div class="form-row-full">
                        <div class="form-group">
                            <label class="form-label">Photo 1 (URL)</label>
                            <input type="url" name="photo_1" class="form-input" value="<?php echo htmlspecialchars($product['photo_1'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Photo 2 (URL)</label>
                            <input type="url" name="photo_2" class="form-input" value="<?php echo htmlspecialchars($product['photo_2'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Photo 3 (URL)</label>
                            <input type="url" name="photo_3" class="form-input" value="<?php echo htmlspecialchars($product['photo_3'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 style="margin: 32px 0 16px; color: #FF4B26;">SEO</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-input" value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-input" value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" placeholder="Généré automatiquement si vide">
                        </div>
                    </div>
                    <div class="form-row-full">
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-input" style="min-height: 80px;"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="active" class="form-select">
                                <option value="1" <?php echo ($product['active'] ?? 1) == 1 ? 'selected' : ''; ?>>Actif</option>
                                <option value="0" <?php echo ($product['active'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'add' ? '➕ Ajouter le produit' : '💾 Mettre à jour'; ?>
                        </button>
                        <a href="?" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
