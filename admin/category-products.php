<?php
/**
 * FLARE CUSTOM - Gestion des produits par catégorie
 * Permet de sélectionner/désélectionner les produits à afficher sur chaque page catégorie
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Vérifier la connexion admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Récupérer la page catégorie sélectionnée
$pageId = $_GET['page_id'] ?? $_POST['page_id'] ?? null;
$currentPage = null;
$currentFilters = [];

if ($pageId) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ? AND type = 'category'");
    $stmt->execute([$pageId]);
    $currentPage = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($currentPage && !empty($currentPage['product_filters'])) {
        $currentFilters = json_decode($currentPage['product_filters'], true) ?: [];
    }
}

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_filters' && $pageId) {
        $filters = [
            'sport' => $_POST['filter_sport'] ?? '',
            'famille' => $_POST['filter_famille'] ?? '',
            'included_ids' => [],
            'excluded_ids' => []
        ];

        // Récupérer les produits sélectionnés
        $selectedProducts = $_POST['selected_products'] ?? [];
        if (!empty($selectedProducts)) {
            $filters['included_ids'] = array_map('intval', $selectedProducts);
        }

        // Récupérer les produits exclus
        $excludedProducts = $_POST['excluded_products'] ?? [];
        if (!empty($excludedProducts)) {
            $filters['excluded_ids'] = array_map('intval', $excludedProducts);
        }

        // Sauvegarder
        $stmt = $pdo->prepare("UPDATE pages SET product_filters = ? WHERE id = ?");
        $stmt->execute([json_encode($filters), $pageId]);

        $message = "Filtres sauvegardés avec succès !";
        $currentFilters = $filters;
    }
}

// Récupérer toutes les pages catégories
$categoryPages = $pdo->query("SELECT id, title, slug FROM pages WHERE type = 'category' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les sports et familles uniques
$sports = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
$familles = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les produits (filtrés si des filtres sont définis)
$products = [];
if ($currentPage) {
    $where = ['active = 1'];
    $params = [];

    if (!empty($currentFilters['sport'])) {
        $where[] = 'sport LIKE ?';
        $params[] = '%' . $currentFilters['sport'] . '%';
    }
    if (!empty($currentFilters['famille'])) {
        $where[] = 'famille LIKE ?';
        $params[] = '%' . $currentFilters['famille'] . '%';
    }

    $sql = "SELECT id, reference, nom, sport, famille, photo_1, prix_1 FROM products WHERE " . implode(' AND ', $where) . " ORDER BY sport, famille, nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Produits Catégories - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1a1a2e;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-50); min-height: 100vh; }

        .container { max-width: 1400px; margin: 0 auto; padding: 24px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .header h1 { font-size: 28px; color: var(--gray-900); }
        .header a { color: var(--gray-500); text-decoration: none; }
        .header a:hover { color: var(--primary); }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card h2 {
            font-size: 18px;
            color: var(--gray-900);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        /* Grille de produits */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            max-height: 600px;
            overflow-y: auto;
            padding: 8px;
        }

        .product-card {
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .product-card:hover { border-color: var(--primary); }
        .product-card.selected {
            border-color: var(--success);
            background: #ecfdf5;
        }
        .product-card.excluded {
            border-color: var(--danger);
            background: #fef2f2;
            opacity: 0.6;
        }

        .product-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .product-card .ref {
            font-size: 10px;
            color: var(--gray-500);
            margin-bottom: 4px;
        }
        .product-card .name {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-900);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .meta {
            font-size: 10px;
            color: var(--gray-500);
            margin-top: 6px;
        }
        .product-card .checkbox {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid var(--gray-300);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-card.selected .checkbox {
            background: var(--success);
            border-color: var(--success);
        }
        .product-card.selected .checkbox::after {
            content: '✓';
            color: #fff;
            font-size: 12px;
            font-weight: bold;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar .count {
            font-size: 14px;
            color: var(--gray-500);
            margin-left: auto;
        }

        .search-box {
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .legend span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legend .dot {
            width: 12px;
            height: 12px;
            border-radius: 4px;
        }
        .legend .dot.included { background: var(--success); }
        .legend .dot.excluded { background: var(--danger); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des Produits par Catégorie</h1>
            <a href="admin.php">← Retour à l'admin</a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Sélection de la page catégorie -->
        <div class="card">
            <h2>1. Sélectionner une page catégorie</h2>
            <form method="GET" class="form-row">
                <div class="form-group">
                    <label>Page catégorie</label>
                    <select name="page_id" onchange="this.form.submit()">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($categoryPages as $page): ?>
                        <option value="<?php echo $page['id']; ?>" <?php echo $pageId == $page['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($page['title']); ?> (<?php echo htmlspecialchars($page['slug']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($currentPage): ?>
        <form method="POST">
            <input type="hidden" name="page_id" value="<?php echo $pageId; ?>">
            <input type="hidden" name="action" value="save_filters">

            <!-- Filtres -->
            <div class="card">
                <h2>2. Définir les filtres</h2>
                <p style="color: var(--gray-500); margin-bottom: 16px; font-size: 14px;">
                    Filtrez les produits par sport et/ou famille. Laissez vide pour afficher tous les produits.
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Filtrer par Sport</label>
                        <select name="filter_sport" onchange="this.form.submit()">
                            <option value="">-- Tous les sports --</option>
                            <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo htmlspecialchars($sport); ?>" <?php echo ($currentFilters['sport'] ?? '') === $sport ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sport); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Filtrer par Famille</label>
                        <select name="filter_famille" onchange="this.form.submit()">
                            <option value="">-- Toutes les familles --</option>
                            <?php foreach ($familles as $famille): ?>
                            <option value="<?php echo htmlspecialchars($famille); ?>" <?php echo ($currentFilters['famille'] ?? '') === $famille ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($famille); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sélection des produits -->
            <div class="card">
                <h2>3. Sélectionner les produits à afficher</h2>

                <div class="legend">
                    <span><span class="dot included"></span> Produit inclus (sélectionné)</span>
                    <span><span class="dot excluded"></span> Produit exclu</span>
                </div>

                <div class="toolbar">
                    <input type="text" class="search-box" placeholder="Rechercher un produit..." id="searchProducts">
                    <button type="button" class="btn btn-secondary" onclick="selectAll()">Tout sélectionner</button>
                    <button type="button" class="btn btn-secondary" onclick="deselectAll()">Tout désélectionner</button>
                    <span class="count"><span id="selectedCount">0</span> produit(s) sélectionné(s) sur <?php echo count($products); ?></span>
                </div>

                <div class="products-grid" id="productsGrid">
                    <?php foreach ($products as $product):
                        $isIncluded = in_array($product['id'], $currentFilters['included_ids'] ?? []);
                        $isExcluded = in_array($product['id'], $currentFilters['excluded_ids'] ?? []);
                        $cardClass = $isIncluded ? 'selected' : ($isExcluded ? 'excluded' : '');
                    ?>
                    <div class="product-card <?php echo $cardClass; ?>"
                         data-id="<?php echo $product['id']; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($product['nom'])); ?>"
                         onclick="toggleProduct(this)">
                        <div class="checkbox"></div>
                        <img src="<?php echo htmlspecialchars($product['photo_1'] ?: '/assets/images/placeholder.jpg'); ?>"
                             alt="<?php echo htmlspecialchars($product['nom']); ?>"
                             loading="lazy">
                        <div class="ref"><?php echo htmlspecialchars($product['reference']); ?></div>
                        <div class="name"><?php echo htmlspecialchars($product['nom']); ?></div>
                        <div class="meta"><?php echo htmlspecialchars($product['sport'] . ' • ' . $product['famille']); ?></div>
                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>"
                               style="display: none;" <?php echo $isIncluded ? 'checked' : ''; ?>>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="text-align: center; padding: 20px;">
                <button type="submit" class="btn btn-primary" style="padding: 14px 40px; font-size: 16px;">
                    Sauvegarder les filtres
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        function toggleProduct(card) {
            card.classList.toggle('selected');
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.checked = card.classList.contains('selected');
            updateCount();
        }

        function selectAll() {
            document.querySelectorAll('.product-card:not(.excluded)').forEach(card => {
                card.classList.add('selected');
                card.querySelector('input[type="checkbox"]').checked = true;
            });
            updateCount();
        }

        function deselectAll() {
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('input[type="checkbox"]').checked = false;
            });
            updateCount();
        }

        function updateCount() {
            const count = document.querySelectorAll('.product-card.selected').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // Recherche
        document.getElementById('searchProducts')?.addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                const name = card.dataset.name;
                card.style.display = name.includes(search) ? '' : 'none';
            });
        });

        // Initialiser le compteur
        updateCount();
    </script>
</body>
</html>
