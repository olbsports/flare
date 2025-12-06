<?php
/**
 * PAGE FAMILLE DYNAMIQUE - FLARE CUSTOM
 * Template SEO-optimized pour pages famille de produits
 */

require_once __DIR__ . '/config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Famille non trouvée");
}

try {
    if (!isset($pdo) || !$pdo) {
        $pdo = getConnection();
    }

    $stmt = $pdo->prepare("SELECT * FROM famille_pages WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        die("Famille non trouvée: " . htmlspecialchars($slug));
    }

    // Charger les produits selon le mode
    $productsSource = $page['products_source'] ?? 'famille';

    if ($productsSource === 'famille' && !empty($page['products_famille_filter'])) {
        $orderStmt = $pdo->prepare("SELECT product_id, position FROM page_products WHERE page_type = 'famille_page' AND page_slug = ?");
        $orderStmt->execute([$slug]);
        $positions = [];
        while ($row = $orderStmt->fetch()) {
            $positions[$row['product_id']] = $row['position'];
        }

        $stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.famille = ? AND p.active = 1 ORDER BY p.nom");
        $stmt->execute([$page['products_famille_filter']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($positions)) {
            usort($products, function($a, $b) use ($positions) {
                $posA = $positions[$a['id']] ?? 9999;
                $posB = $positions[$b['id']] ?? 9999;
                return $posA - $posB;
            });
        }

    } elseif ($productsSource === 'sport' && !empty($page['products_sport_filter'])) {
        $orderStmt = $pdo->prepare("SELECT product_id, position FROM page_products WHERE page_type = 'famille_page' AND page_slug = ?");
        $orderStmt->execute([$slug]);
        $positions = [];
        while ($row = $orderStmt->fetch()) {
            $positions[$row['product_id']] = $row['position'];
        }

        $stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.sport = ? AND p.active = 1 ORDER BY p.nom");
        $stmt->execute([$page['products_sport_filter']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($positions)) {
            usort($products, function($a, $b) use ($positions) {
                $posA = $positions[$a['id']] ?? 9999;
                $posB = $positions[$b['id']] ?? 9999;
                return $posA - $posB;
            });
        }

    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, pp.position
            FROM products p
            INNER JOIN page_products pp ON p.id = pp.product_id
            WHERE pp.page_type = 'famille_page' AND pp.page_slug = ? AND p.active = 1
            ORDER BY pp.position, p.nom
        ");
        $stmt->execute([$slug]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Décoder les champs JSON
    $trustBar = json_decode($page['trust_bar'] ?? '[]', true) ?: [];
    $ctaFeatures = json_decode($page['cta_features'] ?? '[]', true) ?: [];
    $seoCards = json_decode($page['seo_cards'] ?? '[]', true) ?: [];
    $seoStats = json_decode($page['seo_stats'] ?? '[]', true) ?: [];
    $seoBlocks = json_decode($page['seo_content_blocks'] ?? '[]', true) ?: [];

    // Extraire sports/genres uniques
    $uniqueSports = [];
    $uniqueGenres = [];
    $uniqueTissus = [];
    foreach ($products as $prod) {
        if (!empty($prod['sport']) && !in_array($prod['sport'], $uniqueSports)) {
            $uniqueSports[] = $prod['sport'];
        }
        if (!empty($prod['genre']) && !in_array($prod['genre'], $uniqueGenres)) {
            $uniqueGenres[] = $prod['genre'];
        }
        if (!empty($prod['tissu']) && !in_array($prod['tissu'], $uniqueTissus)) {
            $uniqueTissus[] = $prod['tissu'];
        }
    }
    sort($uniqueSports);
    sort($uniqueGenres);
    sort($uniqueTissus);

    $siteName = 'FLARE CUSTOM';
    $siteUrl = 'https://flare-custom.com';

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur de chargement");
}

$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?: '';
$productCount = count($products);
$familleName = $page['famille_name'] ?: $page['title'];
$familleNameLower = strtolower($familleName);
$familleIcon = $page['famille_icon'] ?? '👕';

// Générer les mots-clés SEO
$seoKeywords = "$familleName personnalisé, $familleName sublimation, $familleName sport, $familleName club, " . implode(', ', array_map(fn($s) => "$familleName $s", array_slice($uniqueSports, 0, 5)));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?: "$familleName personnalisés sublimation. $productCount modèles tous sports. Design gratuit, fabrication européenne, livraison 3-4 semaines. Devis gratuit sous 24h.") ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywords) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="FLARE CUSTOM">
    <link rel="canonical" href="<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription ?: "$familleName personnalisés sublimation pour tous les sports. Fabrication européenne, design gratuit.") ?>">
    <meta property="og:url" content="<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>">
    <meta property="og:site_name" content="<?= $siteName ?>">
    <meta property="og:locale" content="fr_FR">
    <?php if (!empty($products[0]['photo_1'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($products[0]['photo_1']) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "<?= htmlspecialchars($metaTitle) ?>",
        "description": "<?= htmlspecialchars($metaDescription) ?>",
        "url": "<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>",
        "numberOfItems": <?= $productCount ?>,
        "provider": {
            "@type": "Organization",
            "name": "FLARE CUSTOM",
            "url": "<?= $siteUrl ?>"
        },
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {"@type": "ListItem", "position": 1, "name": "Accueil", "item": "<?= $siteUrl ?>"},
                {"@type": "ListItem", "position": 2, "name": "<?= htmlspecialchars($familleName) ?>", "item": "<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>"}
            ]
        }
    }
    </script>

    <!-- Product List Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "<?= htmlspecialchars($familleName) ?> personnalisés",
        "numberOfItems": <?= $productCount ?>,
        "itemListElement": [
            <?php
            $schemaItems = [];
            foreach (array_slice($products, 0, 10) as $idx => $prod) {
                $prodName = !empty($prod['meta_title']) ? $prod['meta_title'] : $prod['nom'];
                $schemaItems[] = '{
                    "@type": "ListItem",
                    "position": ' . ($idx + 1) . ',
                    "item": {
                        "@type": "Product",
                        "name": "' . htmlspecialchars($prodName, ENT_QUOTES) . '",
                        "image": "' . htmlspecialchars($prod['photo_1'] ?? '') . '",
                        "offers": {
                            "@type": "Offer",
                            "price": "' . floatval($prod['prix_500'] ?? 0) . '",
                            "priceCurrency": "EUR",
                            "availability": "https://schema.org/InStock"
                        }
                    }
                }';
            }
            echo implode(",\n            ", $schemaItems);
            ?>
        ]
    }
    </script>

    <link rel="preload" href="/assets/css/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/style.css"></noscript>
    <link rel="stylesheet" href="/assets/css/style-sport.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Bebas+Neue&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Bebas+Neue&display=swap"></noscript>
    <link rel="preload" href="/assets/css/components.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/components.css"></noscript>

    <style>
    /* SEO Mega Styles */
    .seo-mega { padding: 80px 5%; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
    .seo-hero { text-align: center; margin-bottom: 60px; }
    .seo-hero-badge { display: inline-block; background: linear-gradient(135deg, #FF4B26, #E63910); color: #fff; padding: 8px 24px; font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 24px; margin-bottom: 24px; }
    .seo-hero-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(32px, 5vw, 56px); letter-spacing: 2px; margin-bottom: 24px; color: #1a1a1a; line-height: 1.2; }
    .seo-hero-intro { font-size: 18px; line-height: 1.8; color: #495057; max-width: 900px; margin: 0 auto; }
    .seo-cards-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; margin-bottom: 60px; }
    .seo-card { background: #fff; padding: 40px 32px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; }
    .seo-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
    .seo-card-icon { font-size: 48px; margin-bottom: 20px; }
    .seo-card h3 { font-size: 22px; font-weight: 700; margin-bottom: 16px; color: #1a1a1a; }
    .seo-card p { font-size: 15px; line-height: 1.7; color: #495057; }
    .seo-stats { background: linear-gradient(135deg, #1a1a1a, #2d2d2d); padding: 60px 40px; border-radius: 24px; margin: 60px 0; }
    .seo-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 40px; text-align: center; }
    .seo-stat-number { font-family: 'Bebas Neue', sans-serif; font-size: clamp(36px, 5vw, 56px); color: #FF4B26; letter-spacing: 2px; }
    .seo-stat-label { font-size: 14px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px; margin-top: 8px; }
    .seo-full-image { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; margin: 60px 0; padding: 60px; background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .seo-full-content h2 { font-family: 'Bebas Neue', sans-serif; font-size: 36px; margin-bottom: 24px; color: #1a1a1a; }
    .seo-full-content p { font-size: 16px; line-height: 1.8; color: #495057; margin-bottom: 16px; }
    .seo-full-image-wrapper { text-align: center; }
    .seo-alternating { margin-top: 60px; }
    .seo-alt-block { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; padding: 40px 0; border-bottom: 1px solid #e2e8f0; }
    .seo-alt-block:nth-child(even) { direction: rtl; }
    .seo-alt-block:nth-child(even) > * { direction: ltr; }
    .seo-alt-content h3 { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #1a1a1a; }
    .seo-alt-content p { font-size: 16px; line-height: 1.8; color: #495057; margin-bottom: 12px; }
    .seo-alt-visual { background: linear-gradient(135deg, #fff5f3, #fff); padding: 32px; border-radius: 16px; border: 1px solid #ffe4de; }
    .seo-alt-visual ul { list-style: none; padding: 0; margin: 0; }
    .seo-alt-visual li { padding: 12px 0; padding-left: 28px; position: relative; font-size: 15px; color: #495057; border-bottom: 1px solid #f1f3f5; }
    .seo-alt-visual li:last-child { border-bottom: none; }
    .seo-alt-visual li::before { content: '✓'; position: absolute; left: 0; color: #FF4B26; font-weight: bold; }
    .seo-longtail { padding: 80px 5%; background: #fff; }
    .seo-longtail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; }
    .seo-longtail-item { padding: 32px; background: #f8f9fa; border-radius: 16px; }
    .seo-longtail-item h3 { font-size: 20px; font-weight: 700; margin-bottom: 16px; color: #1a1a1a; }
    .seo-longtail-item p { font-size: 15px; line-height: 1.7; color: #495057; }
    .seo-faq { padding: 80px 5%; background: linear-gradient(135deg, #1a1a1a, #2d2d2d); }
    .seo-faq .section-header { text-align: center; margin-bottom: 50px; }
    .seo-faq .section-eyebrow { color: #FF4B26; }
    .seo-faq .section-title { color: #fff; }
    .seo-faq-grid { max-width: 900px; margin: 0 auto; }
    .seo-faq-item { background: rgba(255,255,255,0.05); border-radius: 12px; margin-bottom: 16px; overflow: hidden; }
    .seo-faq-question { padding: 24px 32px; font-size: 18px; font-weight: 600; color: #fff; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .seo-faq-question::after { content: '+'; font-size: 24px; color: #FF4B26; transition: transform 0.3s; }
    .seo-faq-item.active .seo-faq-question::after { transform: rotate(45deg); }
    .seo-faq-answer { padding: 0 32px 24px; color: rgba(255,255,255,0.8); line-height: 1.8; display: none; }
    .seo-faq-item.active .seo-faq-answer { display: block; }
    .cta-redesign { padding: 100px 5%; background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%); text-align: center; }
    .cta-redesign-container { max-width: 800px; margin: 0 auto; }
    .cta-redesign-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(32px, 5vw, 56px); color: #fff; letter-spacing: 2px; margin-bottom: 20px; }
    .cta-redesign-subtitle { font-size: 18px; color: rgba(255,255,255,0.9); margin-bottom: 40px; }
    .cta-redesign-features { display: flex; flex-wrap: wrap; justify-content: center; gap: 24px; margin-bottom: 40px; }
    .cta-feature { display: flex; align-items: center; gap: 8px; color: #fff; font-size: 15px; }
    .cta-feature svg { width: 20px; height: 20px; }
    .cta-redesign-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; }
    .btn-cta-main { display: inline-flex; align-items: center; gap: 12px; padding: 18px 36px; background: #fff; color: #FF4B26; font-weight: 700; font-size: 16px; border-radius: 8px; text-decoration: none; transition: transform 0.3s, box-shadow 0.3s; }
    .btn-cta-main:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
    .btn-cta-secondary { display: inline-flex; align-items: center; gap: 12px; padding: 18px 36px; background: transparent; border: 2px solid #fff; color: #fff; font-weight: 700; font-size: 16px; border-radius: 8px; text-decoration: none; transition: background 0.3s; }
    .btn-cta-secondary:hover { background: rgba(255,255,255,0.1); }
    .breadcrumb { padding: 15px 5%; background: #f8f9fa; font-size: 14px; }
    .breadcrumb a { color: #666; text-decoration: none; }
    .breadcrumb a:hover { color: #FF4B26; }
    .breadcrumb span { color: #999; margin: 0 8px; }
    @media (max-width: 768px) {
        .seo-full-image, .seo-alt-block { grid-template-columns: 1fr; }
        .seo-alt-block:nth-child(even) { direction: ltr; }
    }
    </style>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- Breadcrumb SEO -->
    <nav class="breadcrumb" aria-label="Fil d'Ariane">
        <a href="/">Accueil</a>
        <span>›</span>
        <a href="/categorie/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($familleName) ?></a>
    </nav>

    <!-- Hero Section -->
    <section class="hero-sport">
        <div class="hero-sport-content">
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_eyebrow'] ?: "$familleIcon $familleName personnalisés") ?></span>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: "$familleName Sport Sublimation") ?></h1>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['hero_subtitle'] ?: "$productCount modèles personnalisables · Tous sports · Fabrication européenne") ?></p>
        </div>
    </section>

    <!-- Trust Bar -->
    <?php if (!empty($trustBar)): ?>
    <section class="trust-bar">
        <div class="container">
            <div class="trust-items">
                <?php foreach ($trustBar as $item): ?>
                <div class="trust-item">
                    <strong><?= htmlspecialchars($item['value'] ?? '') ?></strong>
                    <span><?= htmlspecialchars($item['label'] ?? '') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Intro SEO Text -->
    <section style="padding: 60px 5%; background: #fff;">
        <div class="container" style="max-width: 900px; margin: 0 auto; text-align: center;">
            <p style="font-size: 18px; line-height: 1.9; color: #495057;">
                Découvrez notre collection de <strong><?= htmlspecialchars($familleNameLower) ?> personnalisés</strong> en sublimation intégrale.
                Avec <strong><?= $productCount ?> modèles</strong> disponibles pour <?= count($uniqueSports) ?> sports différents,
                trouvez le <?= htmlspecialchars($familleNameLower) ?> parfait pour votre équipe.
                Personnalisation illimitée, couleurs au choix, logos et sponsors inclus.
                <strong>Fabrication 100% européenne</strong> avec livraison en 3-4 semaines.
            </p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_eyebrow'] ?: "Catalogue $familleName") ?></div>
                <h2 class="section-title"><?= htmlspecialchars($page['products_title'] ?: "Nos $familleNameLower personnalisés") ?></h2>
                <p class="section-description">
                    <?= htmlspecialchars($page['products_description'] ?: "$productCount modèles tous sports. Tissus techniques haute performance, personnalisation illimitée en sublimation, fabrication européenne certifiée.") ?>
                </p>
            </div>

            <?php if ($page['show_filters'] ?? true): ?>
            <div class="filters-bar">
                <?php if (($page['filter_sport'] ?? 1) && !empty($uniqueSports)): ?>
                <div class="filter-group">
                    <label for="filterSport">Sport</label>
                    <select id="filterSport" class="filter-select">
                        <option value="">Tous les sports</option>
                        <?php foreach ($uniqueSports as $sport): ?>
                        <option value="<?= htmlspecialchars($sport) ?>"><?= htmlspecialchars($sport) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (($page['filter_genre'] ?? 1) && !empty($uniqueGenres)): ?>
                <div class="filter-group">
                    <label for="filterGenre">Genre</label>
                    <select id="filterGenre" class="filter-select">
                        <option value="">Tous</option>
                        <?php foreach ($uniqueGenres as $genre): ?>
                        <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($page['filter_sort'] ?? 1): ?>
                <div class="filter-group">
                    <label for="sortProducts">Trier par</label>
                    <select id="sortProducts" class="filter-select">
                        <option value="default">Par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="name">Nom A-Z</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="products-count">
                <span id="productsCount"><?= $productCount ?> <?= htmlspecialchars($familleNameLower) ?><?= $productCount > 1 ? 's' : '' ?> disponible<?= $productCount > 1 ? 's' : '' ?></span>
            </div>

            <div class="products-grid" id="productsGrid">
                <?php foreach ($products as $prod):
                    $prodName = !empty($prod['meta_title']) ? $prod['meta_title'] : $prod['nom'];
                    $prodPrice = $prod['prix_500'] ? number_format($prod['prix_500'], 2, '.', '') : '';
                    $photos = [];
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($prod["photo_$i"])) {
                            $photos[] = $prod["photo_$i"];
                        }
                    }
                    if (empty($photos)) {
                        $photos[] = '/photos/placeholder.webp';
                    }
                    $isEco = stripos($prodName, 'eco') !== false || stripos($prod['tissu'] ?? '', 'eco') !== false;
                ?>
                <article class="product-card" data-sport="<?= htmlspecialchars($prod['sport'] ?? '') ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>" itemscope itemtype="https://schema.org/Product">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            <?php foreach ($photos as $idx => $photo): ?>
                            <div class="product-slide <?= $idx === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($prodName) ?> - <?= htmlspecialchars($familleName) ?> personnalisé <?= htmlspecialchars($prod['sport'] ?? '') ?>" class="product-image" loading="lazy" width="420" height="560" decoding="async" itemprop="image">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($photos) > 1): ?>
                        <button class="slider-nav prev" aria-label="Photo précédente">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                        <button class="slider-nav next" aria-label="Photo suivante">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                        <div class="product-slider-dots">
                            <?php foreach ($photos as $idx => $photo): ?>
                            <button class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" data-slide="<?= $idx ?>" aria-label="Voir photo <?= $idx + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($isEco): ?>
                        <div class="product-badges"><div class="product-badge eco">ÉCO</div></div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if (!empty($prod['sport'])): ?>
                        <div class="product-family"><?= htmlspecialchars($prod['sport']) ?></div>
                        <?php endif; ?>
                        <h3 class="product-name" itemprop="name"><?= htmlspecialchars($prodName) ?></h3>
                        <div class="product-specs">
                            <?php if (!empty($prod['grammage'])):
                                $grammageVal = $prod['grammage'];
                                $grammageDisplay = (stripos($grammageVal, 'gr') === false) ? $grammageVal . ' gr/m²' : $grammageVal;
                            ?>
                            <span class="product-spec"><?= htmlspecialchars($grammageDisplay) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($prod['tissu'])): ?>
                            <span class="product-spec"><?= htmlspecialchars($prod['tissu']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($prod['genre'])): ?>
                            <span class="product-spec"><?= htmlspecialchars($prod['genre']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($prod['finition'])): ?>
                        <div class="product-finitions">
                            <span class="product-finition-badge"><?= htmlspecialchars($prod['finition']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($prodPrice):
                            $prixEnfant = number_format(floatval($prod['prix_500']) * 0.80, 2, '.', '');
                        ?>
                        <div class="product-pricing" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                            <meta itemprop="priceCurrency" content="EUR">
                            <meta itemprop="availability" content="https://schema.org/InStock">
                            <div class="product-price-label">À partir de</div>
                            <div class="product-price-adulte">
                                <span class="product-price-type">Adulte</span>
                                <span class="product-price" itemprop="price" content="<?= $prodPrice ?>"><?= $prodPrice ?>€</span>
                            </div>
                            <div class="product-price-enfant">
                                <span class="product-price-type">Enfant</span>
                                <span class="product-price-small"><?= $prixEnfant ?>€</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun <?= htmlspecialchars($familleNameLower) ?> disponible pour le moment. Contactez-nous pour un devis personnalisé.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sports Links Section (SEO Internal Linking) -->
    <?php if (!empty($uniqueSports)): ?>
    <section style="padding: 60px 5%; background: #f8f9fa;">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 40px;">
                <div class="section-eyebrow">Par sport</div>
                <h2 class="section-title"><?= htmlspecialchars($familleName) ?> par discipline</h2>
            </div>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 16px;">
                <?php foreach ($uniqueSports as $sport): ?>
                <a href="/sport/<?= strtolower(preg_replace('/[^a-z0-9]+/i', '-', $sport)) ?>" style="display: inline-block; padding: 12px 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #1a1a1a; font-weight: 500; transition: all 0.3s;">
                    <?= htmlspecialchars($familleName) ?> <?= htmlspecialchars($sport) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-redesign">
        <div class="cta-redesign-container">
            <h2 class="cta-redesign-title"><?= htmlspecialchars($page['cta_title'] ?: "Créons ensemble vos $familleNameLower personnalisés") ?></h2>
            <p class="cta-redesign-subtitle"><?= htmlspecialchars($page['cta_subtitle'] ?: "Équipez votre club avec des $familleNameLower uniques qui reflètent votre identité") ?></p>

            <?php if (!empty($ctaFeatures)): ?>
            <div class="cta-redesign-features">
                <?php foreach ($ctaFeatures as $feature): ?>
                <div class="cta-feature">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/>
                    </svg>
                    <span><?= htmlspecialchars($feature) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="cta-redesign-features">
                <div class="cta-feature"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/></svg><span>Devis gratuit sous 24h</span></div>
                <div class="cta-feature"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/></svg><span>Design professionnel inclus</span></div>
                <div class="cta-feature"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/></svg><span>Prix dégressifs garantis</span></div>
            </div>
            <?php endif; ?>

            <div class="cta-redesign-buttons">
                <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/contact.html') ?>" class="btn-cta-main">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 8L10.89 13.26C11.24 13.48 11.62 13.59 12 13.59C12.38 13.59 12.76 13.48 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z"/>
                    </svg>
                    <span><?= htmlspecialchars($page['cta_button_text'] ?: 'Demander un Devis Gratuit') ?></span>
                </a>
                <?php if (!empty($page['cta_whatsapp'])): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $page['cta_whatsapp']) ?>" class="btn-cta-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    <span>WhatsApp Direct</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SEO Mega Section -->
    <?php if (!empty($page['seo_hero_title']) || !empty($seoCards)): ?>
    <section class="seo-mega">
        <div class="container">
            <?php if (!empty($page['seo_hero_badge']) || !empty($page['seo_hero_title'])): ?>
            <div class="seo-hero">
                <?php if (!empty($page['seo_hero_badge'])): ?>
                <div class="seo-hero-badge"><?= htmlspecialchars($page['seo_hero_badge']) ?></div>
                <?php endif; ?>
                <?php if (!empty($page['seo_hero_title'])): ?>
                <h2 class="seo-hero-title"><?= htmlspecialchars($page['seo_hero_title']) ?></h2>
                <?php endif; ?>
                <?php if (!empty($page['seo_hero_intro'])): ?>
                <p class="seo-hero-intro"><?= htmlspecialchars($page['seo_hero_intro']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($seoCards)): ?>
            <div class="seo-cards-3">
                <?php foreach ($seoCards as $card): ?>
                <div class="seo-card">
                    <div class="seo-card-icon"><?= htmlspecialchars($card['icon'] ?? '✨') ?></div>
                    <h3><?= htmlspecialchars($card['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($card['content'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($page['seo_full_content'])): ?>
            <div class="seo-full-image">
                <div class="seo-full-content">
                    <?= $page['seo_full_content'] ?>
                </div>
                <div class="seo-full-image-wrapper">
                    <p style="font-size: 80px; margin: 0;"><?= htmlspecialchars($familleIcon) ?></p>
                    <p style="color: #666; margin-top: 20px;"><strong><?= htmlspecialchars($familleName) ?> Sublimation</strong><br>Personnalisation illimitée<br>Fabrication européenne</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($seoStats)): ?>
            <div class="seo-stats">
                <div class="seo-stats-grid">
                    <?php foreach ($seoStats as $stat): ?>
                    <div class="seo-stat">
                        <div class="seo-stat-number"><?= htmlspecialchars($stat['number'] ?? '') ?></div>
                        <div class="seo-stat-label"><?= htmlspecialchars($stat['label'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($seoBlocks)): ?>
            <div class="seo-alternating">
                <?php foreach ($seoBlocks as $block): ?>
                <div class="seo-alt-block">
                    <div class="seo-alt-content">
                        <?php if (!empty($block['title'])): ?>
                        <h3><?= htmlspecialchars($block['title']) ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($block['content'])): ?>
                        <p><?= nl2br(htmlspecialchars($block['content'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($block['list'])): ?>
                    <div class="seo-alt-visual">
                        <ul>
                            <?php foreach ($block['list'] as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- SEO Longtail Content -->
    <section class="seo-longtail">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 50px;">
                <div class="section-eyebrow">Guide complet</div>
                <h2 class="section-title">Tout savoir sur les <?= htmlspecialchars($familleNameLower) ?> personnalisés</h2>
            </div>
            <div class="seo-longtail-grid">
                <div class="seo-longtail-item">
                    <h3>Qu'est-ce que la sublimation ?</h3>
                    <p>La sublimation est un procédé d'impression révolutionnaire où l'encre pénètre directement dans les fibres du tissu polyester. Contrairement au flocage ou à la sérigraphie, les couleurs deviennent partie intégrante du textile. Résultat : des <?= htmlspecialchars($familleNameLower) ?> aux couleurs éclatantes qui résistent à plus de 100 lavages sans s'altérer.</p>
                </div>
                <div class="seo-longtail-item">
                    <h3>Pourquoi choisir FLARE CUSTOM ?</h3>
                    <p>Spécialiste européen des équipements sportifs personnalisés depuis plus de 10 ans. Fabrication dans nos ateliers partenaires certifiés en Europe (Bulgarie, Portugal, Pologne). Contrôle qualité rigoureux, design professionnel inclus, et garantie satisfait ou refabriqué sur tous nos <?= htmlspecialchars($familleNameLower) ?>.</p>
                </div>
                <div class="seo-longtail-item">
                    <h3>Délais et livraison</h3>
                    <p>Production de vos <?= htmlspecialchars($familleNameLower) ?> personnalisés en 3-4 semaines après validation du BAT (Bon à Tirer). Livraison gratuite en France métropolitaine à partir de 500€ d'achat. Suivi de commande en temps réel et tracking DHL/UPS fourni dès expédition.</p>
                </div>
                <div class="seo-longtail-item">
                    <h3>Prix dégressifs</h3>
                    <p>Plus vous commandez, plus vous économisez ! Réductions automatiques : -10% dès 5 pièces, -15% dès 10 pièces, -20% dès 20 pièces, jusqu'à -40% à partir de 250 <?= htmlspecialchars($familleNameLower) ?>. Tarification transparente tout inclus : personnalisation, noms, numéros et création graphique.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="seo-faq" itemscope itemtype="https://schema.org/FAQPage">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Questions fréquentes</div>
                <h2 class="section-title">FAQ <?= htmlspecialchars($familleName) ?> Personnalisés</h2>
            </div>
            <div class="seo-faq-grid">
                <div class="seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="seo-faq-question" itemprop="name">Quel est le délai de fabrication pour des <?= htmlspecialchars($familleNameLower) ?> personnalisés ?</div>
                    <div class="seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Le délai de fabrication est de 3-4 semaines après validation de votre BAT (Bon à Tirer). Ce délai inclut la création graphique, la production en sublimation et le contrôle qualité. Pour les commandes urgentes, contactez-nous pour étudier une solution express.</p>
                    </div>
                </div>
                <div class="seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="seo-faq-question" itemprop="name">Quelle est la quantité minimum de commande ?</div>
                    <div class="seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Aucune quantité minimum ! Vous pouvez commander à partir d'1 seul <?= htmlspecialchars($familleNameLower) ?>. Cependant, les prix dégressifs démarrent dès 5 pièces (-10%) et augmentent progressivement jusqu'à -40% pour 250+ pièces.</p>
                    </div>
                </div>
                <div class="seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="seo-faq-question" itemprop="name">La personnalisation (logos, sponsors, noms) est-elle incluse ?</div>
                    <div class="seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Oui, tout est inclus ! La sublimation permet une personnalisation illimitée sans surcoût : logos multiples, sponsors, dégradés de couleurs, noms et numéros individuels. Notre équipe graphique crée gratuitement votre design sur mesure.</p>
                    </div>
                </div>
                <div class="seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="seo-faq-question" itemprop="name">Où sont fabriqués vos <?= htmlspecialchars($familleNameLower) ?> ?</div>
                    <div class="seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">100% fabrication européenne ! Nos <?= htmlspecialchars($familleNameLower) ?> sont produits dans nos ateliers partenaires certifiés en Bulgarie, Portugal et Pologne. Nous n'avons aucune production asiatique, garantissant qualité, éthique et réactivité.</p>
                    </div>
                </div>
                <div class="seo-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <div class="seo-faq-question" itemprop="name">Comment entretenir mes <?= htmlspecialchars($familleNameLower) ?> sublimés ?</div>
                    <div class="seo-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">Lavage en machine à 30-40°C, retourné sur l'envers. Pas de sèche-linge ni de repassage direct sur l'impression. Les couleurs sublimées résistent à plus de 100 lavages sans s'altérer. Évitez les produits chlorés.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section style="padding: 80px 5%; background: #fff; text-align: center;">
        <div class="container" style="max-width: 800px;">
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 42px; margin-bottom: 20px;">Prêt à équiper votre équipe ?</h2>
            <p style="font-size: 18px; color: #495057; margin-bottom: 30px;">
                Demandez votre devis gratuit et recevez une proposition personnalisée sous 24h.
                Notre équipe vous accompagne de A à Z dans votre projet de <?= htmlspecialchars($familleNameLower) ?> personnalisés.
            </p>
            <a href="/pages/info/contact.html" style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 40px; background: linear-gradient(135deg, #FF4B26, #E63910); color: #fff; font-weight: 700; font-size: 18px; border-radius: 8px; text-decoration: none;">
                Demander un devis gratuit
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </a>
        </div>
    </section>

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>
    <script src="/assets/js/product-cards-linker.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Toggle
            document.querySelectorAll('.seo-faq-question').forEach(function(q) {
                q.addEventListener('click', function() {
                    this.parentElement.classList.toggle('active');
                });
            });

            // Filters
            const filterSport = document.getElementById('filterSport');
            const filterGenre = document.getElementById('filterGenre');
            const sortProducts = document.getElementById('sortProducts');
            const productsGrid = document.getElementById('productsGrid');
            const productsCount = document.getElementById('productsCount');

            function filterAndSortProducts() {
                if (!productsGrid) return;
                const cards = Array.from(productsGrid.querySelectorAll('.product-card'));
                let visibleCount = 0;

                cards.forEach(card => {
                    const sport = card.dataset.sport || '';
                    const genre = card.dataset.genre || '';
                    let show = true;

                    if (filterSport && filterSport.value && !sport.includes(filterSport.value)) show = false;
                    if (filterGenre && filterGenre.value && !genre.includes(filterGenre.value)) show = false;

                    card.style.display = show ? 'block' : 'none';
                    if (show) visibleCount++;
                });

                if (sortProducts && sortProducts.value !== 'default') {
                    const sortedCards = cards.filter(c => c.style.display !== 'none');
                    sortedCards.sort((a, b) => {
                        switch (sortProducts.value) {
                            case 'price-asc': return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                            case 'price-desc': return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                            case 'name': return a.dataset.name.localeCompare(b.dataset.name);
                            default: return 0;
                        }
                    });
                    sortedCards.forEach(card => productsGrid.appendChild(card));
                }

                if (productsCount) {
                    productsCount.textContent = visibleCount + ' <?= htmlspecialchars($familleNameLower) ?>' + (visibleCount > 1 ? 's' : '') + ' disponible' + (visibleCount > 1 ? 's' : '');
                }
            }

            if (filterSport) filterSport.addEventListener('change', filterAndSortProducts);
            if (filterGenre) filterGenre.addEventListener('change', filterAndSortProducts);
            if (sortProducts) sortProducts.addEventListener('change', filterAndSortProducts);

            // Product slider
            document.querySelectorAll('.product-card').forEach(function(card) {
                const slides = card.querySelectorAll('.product-slide');
                const dots = card.querySelectorAll('.slider-dot');
                const prevBtn = card.querySelector('.slider-nav.prev');
                const nextBtn = card.querySelector('.slider-nav.next');
                let currentSlide = 0;

                function showSlide(n) {
                    currentSlide = (n + slides.length) % slides.length;
                    slides.forEach((s, i) => s.classList.toggle('active', i === currentSlide));
                    dots.forEach((d, i) => d.classList.toggle('active', i === currentSlide));
                }

                if (prevBtn) prevBtn.addEventListener('click', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    showSlide(currentSlide - 1);
                });

                if (nextBtn) nextBtn.addEventListener('click', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    showSlide(currentSlide + 1);
                });

                dots.forEach(function(dot, i) {
                    dot.addEventListener('click', function(e) {
                        e.preventDefault(); e.stopPropagation();
                        showSlide(i);
                    });
                });
            });
        });
    </script>
</body>
</html>
