<?php
/**
 * PAGE DYNAMIQUE - FLARE CUSTOM
 *
 * Sert les pages catégories et info avec le design original
 * Injection dynamique des produits basée sur les filtres admin
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

    // Déterminer le chemin du fichier statique original
    $staticDir = $type === 'category' ? 'products' : 'info';
    $staticFile = __DIR__ . '/pages/' . $staticDir . '/' . $slug . '.html';

    // Pour les pages catégories, vérifier d'abord la BDD (pages dynamiques)
    if ($type === 'category') {
        // Vérifier si une page catégorie dynamique existe en BDD
        $stmt = $pdo->prepare("SELECT id FROM category_pages WHERE slug = ? AND active = 1");
        $stmt->execute([$slug]);
        $dynamicPage = $stmt->fetch();

        if ($dynamicPage) {
            // Utiliser le template dynamique categorie.php
            $_GET['slug'] = $slug;
            include __DIR__ . '/categorie.php';
            exit;
        }

        // Sinon, utiliser le fichier HTML statique si disponible
        if (file_exists($staticFile)) {
            $content = file_get_contents($staticFile);

            // Charger les filtres produits depuis la BDD si disponibles
            $filters = loadCategoryFilters($pdo, $slug);

            // Charger les produits selon les filtres
            $products = loadCategoryProducts($pdo, $slug, $filters);

            // Injecter les produits dans le HTML
            $content = injectProductsIntoHtml($content, $products);

            // Corriger les URLs relatives
            $content = fixUrls($content);

            echo $content;
            exit;
        }
    }

    // Pour les pages info, essayer d'abord le fichier statique
    if ($type === 'info' && file_exists($staticFile)) {
        $content = file_get_contents($staticFile);
        $content = fixUrls($content);
        echo $content;
        exit;
    }

    // Sinon, charger depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        if ($debug) {
            echo "<h1>Page non trouvée</h1>";
            echo "<p>Slug: " . htmlspecialchars($slug) . "</p>";
            echo "<p>Fichier cherché: " . htmlspecialchars($staticFile) . "</p>";
            exit;
        }
        include __DIR__ . '/pages/404.html';
        exit;
    }

    echo $page['content'] ?? '';

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        echo "Erreur: " . $e->getMessage();
    } else {
        echo "Erreur de chargement";
    }
}

/**
 * Charger les filtres de catégorie depuis la BDD
 */
function loadCategoryFilters($pdo, $slug) {
    try {
        $stmt = $pdo->prepare("SELECT product_filters FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $data = $stmt->fetch();

        if ($data && !empty($data['product_filters'])) {
            return json_decode($data['product_filters'], true) ?: [];
        }
    } catch (Exception $e) {
        // Ignorer les erreurs de BDD pour les filtres
    }
    return [];
}

/**
 * Charger les produits pour une catégorie
 */
function loadCategoryProducts($pdo, $categorySlug, $filters = []) {
    // Si des IDs spécifiques sont inclus, les utiliser en priorité
    if (!empty($filters['included_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['included_ids']), '?'));
        $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND active = 1 ORDER BY nom";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($filters['included_ids']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Sinon, détecter le sport/famille depuis le slug
    $slug = strtolower($categorySlug);
    $where = ['active = 1'];
    $params = [];

    // Mapper les slugs vers les valeurs de sport
    $sportMappings = [
        'football' => 'Football',
        'rugby' => 'Rugby',
        'basketball' => 'Basketball',
        'basket' => 'Basketball',
        'handball' => 'Handball',
        'volleyball' => 'Volleyball',
        'volley' => 'Volleyball',
        'running' => 'Running',
        'course' => 'Running',
        'cyclisme' => 'Cyclisme',
        'velo' => 'Cyclisme',
        'triathlon' => 'Triathlon',
        'petanque' => 'Pétanque'
    ];

    // Mapper les familles de produits
    $familleMappings = [
        'maillot' => 'Maillot',
        'short' => 'Short',
        'polo' => 'Polo',
        'veste' => 'Veste',
        'pantalon' => 'Pantalon',
        'sweat' => 'Sweat',
        'debardeur' => 'Débardeur',
        'survetement' => 'Survêtement',
        'cuissard' => 'Cuissard',
        'combinaison' => 'Combinaison',
        'coupe-vent' => 'Coupe-vent',
        'gilet' => 'Gilet',
        'corsaire' => 'Corsaire',
        'tshirt' => 'T-Shirt',
        't-shirt' => 'T-Shirt'
    ];

    // Détecter le sport
    foreach ($sportMappings as $key => $value) {
        if (strpos($slug, $key) !== false) {
            $where[] = 'sport = ?';
            $params[] = $value;
            break;
        }
    }

    // Détecter la famille si le slug en contient une
    foreach ($familleMappings as $key => $value) {
        if (strpos($slug, $key) !== false) {
            $where[] = 'famille LIKE ?';
            $params[] = '%' . $value . '%';
            break;
        }
    }

    // Appliquer les exclusions
    if (!empty($filters['excluded_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['excluded_ids']), '?'));
        $where[] = "id NOT IN ($placeholders)";
        $params = array_merge($params, $filters['excluded_ids']);
    }

    $sql = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY nom LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Injecter les produits dans le HTML avec le design original
 */
function injectProductsIntoHtml($html, $products) {
    if (empty($products)) {
        return $html;
    }

    // Générer le HTML des produits avec le design original
    $productsHtml = '';
    foreach ($products as $product) {
        $productsHtml .= generateProductCard($product);
    }

    // Trouver et remplacer le contenu de la grille produits
    // Pattern pour trouver <div class="products-grid"...>CONTENU</div>
    $pattern = '/(<div[^>]*class="[^"]*products-grid[^"]*"[^>]*>)(.*?)(<\/div>\s*<\/div>\s*<\/section>)/is';

    if (preg_match($pattern, $html)) {
        $html = preg_replace(
            $pattern,
            '$1' . "\n" . $productsHtml . "\n" . '            </div>
        </div>
    </section>',
            $html,
            1
        );
    }

    // Mettre à jour le compteur de produits
    $count = count($products);
    $html = preg_replace(
        '/<span id="productsCount">[^<]*<\/span>/',
        '<span id="productsCount">' . $count . ' produit' . ($count > 1 ? 's' : '') . '</span>',
        $html
    );

    return $html;
}

/**
 * Générer une carte produit avec le design original
 */
function generateProductCard($product) {
    $ref = htmlspecialchars($product['reference'] ?? '');
    $nom = htmlspecialchars($product['nom'] ?? '');
    $famille = htmlspecialchars($product['famille'] ?? '');
    $tissu = htmlspecialchars($product['tissu'] ?? '');
    $grammage = htmlspecialchars($product['grammage'] ?? '');
    $genre = htmlspecialchars($product['genre'] ?? 'Mixte');
    $finition = htmlspecialchars($product['finition'] ?? '');

    // Photos
    $photos = [];
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($product["photo_$i"])) {
            $photos[] = $product["photo_$i"];
        }
    }
    if (empty($photos)) {
        $photos[] = '/assets/images/placeholder.jpg';
    }

    // Prix (toujours prix pour 500 pièces)
    $prixAdulte = number_format($product['prix_500'] ?? $product['prix_1'] ?? 0, 2, '.', '');
    $prixEnfant = number_format(($product['prix_500'] ?? $product['prix_1'] ?? 0) * 0.85, 2, '.', ''); // -15% pour enfant

    // Générer les slides
    $slidesHtml = '';
    $dotsHtml = '';
    foreach ($photos as $index => $photo) {
        $activeClass = $index === 0 ? 'active' : '';
        $photoUrl = htmlspecialchars($photo);
        $alt = $nom . ' - Photo ' . ($index + 1);

        $slidesHtml .= '<div class="product-slide ' . $activeClass . '">
                <img src="' . $photoUrl . '" alt="' . $alt . '" class="product-image" loading="lazy" width="420" height="560" decoding="async">
            </div>';

        $dotsHtml .= '<button class="slider-dot ' . $activeClass . '" data-slide="' . $index . '" aria-label="Voir photo ' . ($index + 1) . '"></button>';
    }

    // Badges
    $badgesHtml = '';
    if (stripos($tissu, 'eco') !== false || stripos($nom, 'eco') !== false) {
        $badgesHtml .= '<div class="product-badge eco">ÉCO</div>';
    }
    if (stripos($nom, 'pro') !== false || stripos($nom, 'premium') !== false) {
        $badgesHtml .= '<div class="product-badge premium">PREMIUM</div>';
    }

    // Specs
    $specs = [];
    if ($grammage) $specs[] = $grammage . ' gr/m²';
    if ($tissu) $specs[] = $tissu;
    if ($genre) $specs[] = $genre;
    $specsHtml = implode('</span><span class="product-spec">', $specs);

    // Finitions
    $finitionsHtml = '';
    if ($finition) {
        $finitionsHtml = '<div class="product-finitions"><span class="product-finition-badge">' . $finition . '</span></div>';
    }

    return '
                <div class="product-card" data-famille="' . $famille . '" data-genre="' . $genre . '" data-grammage="' . $grammage . '">
                    <a href="/produit/' . $ref . '" class="product-card-link">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            ' . $slidesHtml . '
                        </div>
                        <button class="slider-nav prev" aria-label="Photo précédente">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="slider-nav next" aria-label="Photo suivante">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
                        <div class="product-slider-dots">' . $dotsHtml . '</div>
                        <div class="product-badges">' . $badgesHtml . '</div>
                    </div>
                    <div class="product-info">
                        <div class="product-family">' . $famille . '</div>
                        <h3 class="product-name">' . $nom . '</h3>
                        <div class="product-specs">
                            <span class="product-spec">' . $specsHtml . '</span>
                        </div>
                        ' . $finitionsHtml . '
                        <div class="product-pricing">
                            <div class="product-price-label">À partir de</div>
                            <div class="product-price-adulte">
                                <span class="product-price-type">Adulte</span>
                                <span class="product-price">' . $prixAdulte . '€</span>
                            </div>
                            <div class="product-price-enfant">
                                <span class="product-price-type">Enfant</span>
                                <span class="product-price-small">' . $prixEnfant . '€</span>
                            </div>
                        </div>
                    </div>
                    </a>
                </div>';
}

/**
 * Corriger les URLs relatives dans le HTML
 */
function fixUrls($content) {
    // Corriger les liens produits
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/produits\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/produit/$1"', $content);

    // Corriger les liens catégories
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/products\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/categorie/$1"', $content);

    // Corriger les liens info
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/info\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/info/$1"', $content);

    // Corriger les liens blog
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/blog\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/blog/$1"', $content);

    // Corriger les chemins assets
    $content = preg_replace('/(?:\.\.\/)+assets\//i', '/assets/', $content);

    return $content;
}
