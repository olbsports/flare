<?php
/**
 * PAGE DYNAMIQUE - FLARE CUSTOM
 *
 * Sert le HTML complet stocké en BDD (conserve le design original de chaque page)
 *
 * URLs:
 * - /info/contact → page.php?slug=contact&type=info
 * - /categorie/maillots-football → page.php?slug=maillots-football&type=category
 */

require_once __DIR__ . '/config/database.php';

// Récupération des paramètres
$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? 'info';
$debug = isset($_GET['debug']);

if (empty($slug)) {
    http_response_code(404);
    include __DIR__ . '/pages/404.html';
    exit;
}

try {
    $pdo = getConnection();

    // Charger la page depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        // Fallback: essayer différentes variations du slug
        $variations = [$slug, str_replace('-', '_', $slug)];
        foreach ($variations as $variation) {
            $stmt->execute([$variation]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($page) break;
        }
    }

    // Si toujours pas trouvé
    if (!$page) {
        http_response_code(404);
        if ($debug) {
            echo "<h1>Page non trouvée</h1>";
            echo "<p>Slug: " . htmlspecialchars($slug) . "</p>";
            echo "<h3>Pages disponibles:</h3><pre>";
            $all = $pdo->query("SELECT slug, title FROM pages ORDER BY slug")->fetchAll();
            print_r($all);
            echo "</pre>";
            exit;
        }
        // Essayer de servir le fichier HTML statique original
        $staticFile = __DIR__ . '/pages/' . ($type === 'category' ? 'products' : 'info') . '/' . $slug . '.html';
        if (file_exists($staticFile)) {
            readfile($staticFile);
            exit;
        }
        include __DIR__ . '/pages/404.html';
        exit;
    }

    $content = $page['content'] ?? '';

    // Pour les pages catégories, injecter les produits filtrés
    if ($type === 'category' || $page['type'] === 'category') {
        $content = injectCategoryProducts($pdo, $slug, $content);
    }

    // Servir le contenu HTML complet
    echo $content;

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        echo "Erreur: " . $e->getMessage();
    } else {
        echo "Erreur de chargement";
    }
}

/**
 * Injecter les produits dans une page catégorie
 */
function injectCategoryProducts($pdo, $categorySlug, $content) {
    // Chercher la configuration des produits pour cette catégorie
    $stmt = $pdo->prepare("SELECT product_filters FROM pages WHERE slug = ?");
    $stmt->execute([$categorySlug]);
    $pageData = $stmt->fetch();

    $filters = [];
    if ($pageData && !empty($pageData['product_filters'])) {
        $filters = json_decode($pageData['product_filters'], true) ?: [];
    }

    // Si pas de filtres définis, utiliser le slug pour deviner le sport/famille
    if (empty($filters)) {
        // Détecter automatiquement basé sur le slug
        $slug = strtolower($categorySlug);

        // Sports
        $sports = ['football', 'rugby', 'basketball', 'handball', 'volleyball', 'running', 'cyclisme', 'triathlon', 'petanque'];
        foreach ($sports as $sport) {
            if (strpos($slug, $sport) !== false) {
                $filters['sport'] = ucfirst($sport);
                break;
            }
        }

        // Familles/Types de produits
        $familles = [
            'maillot' => 'Maillot',
            'short' => 'Short',
            'polo' => 'Polo',
            'veste' => 'Veste',
            'pantalon' => 'Pantalon',
            'sweat' => 'Sweat',
            'debardeur' => 'Débardeur',
            'survetement' => 'Survêtement',
            'cuissard' => 'Cuissard',
            'combinaison' => 'Combinaison'
        ];
        foreach ($familles as $key => $value) {
            if (strpos($slug, $key) !== false) {
                $filters['famille'] = $value;
                break;
            }
        }
    }

    // Construire la requête pour récupérer les produits
    $where = ['active = 1'];
    $params = [];

    if (!empty($filters['sport'])) {
        $where[] = 'sport LIKE ?';
        $params[] = '%' . $filters['sport'] . '%';
    }
    if (!empty($filters['famille'])) {
        $where[] = 'famille LIKE ?';
        $params[] = '%' . $filters['famille'] . '%';
    }
    if (!empty($filters['excluded_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['excluded_ids']), '?'));
        $where[] = "id NOT IN ($placeholders)";
        $params = array_merge($params, $filters['excluded_ids']);
    }
    if (!empty($filters['included_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['included_ids']), '?'));
        $where[] = "id IN ($placeholders)";
        $params = array_merge($params, $filters['included_ids']);
    }

    $sql = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY nom LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Générer le HTML des produits (format grille)
    $productsHtml = generateProductsGrid($products);

    // Remplacer le placeholder ou la grille existante dans le contenu
    // Chercher la section products-grid ou similar
    if (preg_match('/<div[^>]*class="[^"]*products?-grid[^"]*"[^>]*>.*?<\/div>/is', $content)) {
        $content = preg_replace(
            '/<div[^>]*class="[^"]*products?-grid[^"]*"[^>]*>.*?<\/div>/is',
            '<div class="products-grid">' . $productsHtml . '</div>',
            $content
        );
    }

    return $content;
}

/**
 * Générer la grille HTML des produits
 */
function generateProductsGrid($products) {
    if (empty($products)) {
        return '<p class="no-products">Aucun produit trouvé dans cette catégorie.</p>';
    }

    $html = '';
    foreach ($products as $product) {
        $ref = htmlspecialchars($product['reference']);
        $nom = htmlspecialchars($product['nom']);
        $photo = htmlspecialchars($product['photo_1'] ?? '/assets/images/placeholder.jpg');
        $prix = number_format($product['prix_1'] ?? 0, 2, ',', ' ');
        $sport = htmlspecialchars($product['sport'] ?? '');

        $html .= '
        <a href="/produit/' . $ref . '" class="product-card">
            <div class="product-image">
                <img src="' . $photo . '" alt="' . $nom . '" loading="lazy">
            </div>
            <div class="product-info">
                <span class="product-sport">' . $sport . '</span>
                <h3 class="product-name">' . $nom . '</h3>
                <p class="product-price">À partir de <strong>' . $prix . ' €</strong></p>
            </div>
        </a>';
    }

    return $html;
}
