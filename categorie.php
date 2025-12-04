<?php
/**
 * PAGE CATÉGORIE DYNAMIQUE - FLARE CUSTOM
 * Template générique configurable depuis l'admin
 */

require_once __DIR__ . '/config/database.php';

// Récupérer le slug de la catégorie
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Catégorie non trouvée");
}

try {
    // Utiliser la connexion existante si disponible (quand inclus depuis page.php)
    if (!isset($pdo) || !$pdo) {
        $pdo = getConnection();
    }

    // Charger la page catégorie
    $stmt = $pdo->prepare("SELECT * FROM category_pages WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        die("Catégorie non trouvée: " . htmlspecialchars($slug));
    }

    // Charger les produits associés à cette page
    $stmt = $pdo->prepare("
        SELECT p.*, pp.position
        FROM products p
        INNER JOIN page_products pp ON p.id = pp.product_id
        WHERE pp.page_type = 'category_page' AND pp.page_slug = ? AND p.active = 1
        ORDER BY pp.position, p.nom
    ");
    $stmt->execute([$slug]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Décoder les champs JSON
    $trustBar = json_decode($page['trust_bar'] ?? '[]', true) ?: [];
    $ctaFeatures = json_decode($page['cta_features'] ?? '[]', true) ?: [];
    $excellenceColumns = json_decode($page['excellence_columns'] ?? '[]', true) ?: [];
    $techStats = json_decode($page['tech_stats'] ?? '[]', true) ?: [];
    $featuresSections = json_decode($page['features_sections'] ?? '[]', true) ?: [];
    $testimonials = json_decode($page['testimonials'] ?? '[]', true) ?: [];
    $faqItems = json_decode($page['faq_items'] ?? '[]', true) ?: [];
    $filterSports = json_decode($page['filter_sports'] ?? '[]', true) ?: [];
    $filterGenres = json_decode($page['filter_genres'] ?? '[]', true) ?: [];

    // Paramètres du site
    $siteName = 'FLARE CUSTOM';
    $siteUrl = 'https://flare-custom.com';

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur de chargement");
}

$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?: '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $siteUrl ?>/categorie/<?= htmlspecialchars($slug) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= $siteUrl ?>/categorie/<?= htmlspecialchars($slug) ?>">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">

    <style>
        .cat-hero { background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: #fff; padding: 80px 20px; text-align: center; }
        .cat-hero h1 { font-family: 'Bebas Neue', sans-serif; font-size: 3.5rem; margin-bottom: 20px; }
        .cat-hero p { font-size: 1.2rem; max-width: 800px; margin: 0 auto 30px; opacity: 0.9; }
        .cat-hero .btn-primary { background: #FF4B26; color: #fff; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-block; }

        .trust-bar { background: #000; padding: 20px 0; }
        .trust-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: center; gap: 60px; flex-wrap: wrap; }
        .trust-item { text-align: center; color: #fff; }
        .trust-value { font-size: 2rem; font-weight: 800; color: #FF4B26; }
        .trust-label { font-size: 0.9rem; opacity: 0.8; }

        .products-section { padding: 60px 20px; max-width: 1400px; margin: 0 auto; }
        .products-header { text-align: center; margin-bottom: 40px; }
        .products-header h2 { font-size: 2rem; margin-bottom: 10px; }
        .products-filters { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px; }
        .products-filters select { padding: 10px 20px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
        .product-card img { width: 100%; height: 220px; object-fit: cover; }
        .product-card .info { padding: 20px; }
        .product-card h3 { font-size: 1.1rem; margin-bottom: 8px; }
        .product-card .price { color: #FF4B26; font-weight: 700; font-size: 1.2rem; }
        .product-card .btn { display: block; text-align: center; background: #FF4B26; color: #fff; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 15px; }

        .cta-section { background: linear-gradient(135deg, #FF4B26 0%, #ff6b4a 100%); color: #fff; padding: 80px 20px; text-align: center; }
        .cta-section h2 { font-size: 2.5rem; margin-bottom: 20px; }
        .cta-features { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin: 30px 0; }
        .cta-feature { background: rgba(255,255,255,0.15); padding: 20px 30px; border-radius: 10px; }
        .cta-buttons { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .cta-buttons .btn-white { background: #fff; color: #FF4B26; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-weight: 700; }
        .cta-buttons .btn-outline { border: 2px solid #fff; color: #fff; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-weight: 700; }

        .excellence-section { padding: 80px 20px; background: #f8f9fa; }
        .excellence-container { max-width: 1200px; margin: 0 auto; }
        .excellence-header { text-align: center; margin-bottom: 50px; }
        .excellence-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .excellence-card { background: #fff; padding: 40px 30px; border-radius: 12px; text-align: center; }
        .excellence-card h3 { font-size: 1.3rem; margin: 20px 0 15px; color: #FF4B26; }

        .tech-section { padding: 80px 20px; background: #1a1a1a; color: #fff; }
        .tech-container { max-width: 1000px; margin: 0 auto; text-align: center; }
        .tech-stats { display: flex; justify-content: center; gap: 50px; flex-wrap: wrap; margin-top: 40px; }
        .tech-stat { text-align: center; }
        .tech-stat .value { font-size: 2.5rem; font-weight: 800; color: #FF4B26; }

        .features-section { padding: 60px 20px; }
        .features-container { max-width: 900px; margin: 0 auto; }
        .features-block { margin-bottom: 40px; }
        .features-block h3 { font-size: 1.5rem; margin-bottom: 15px; color: #1a1a1a; }
        .features-block ul { list-style: none; padding: 0; }
        .features-block li { padding: 10px 0; padding-left: 25px; position: relative; }
        .features-block li:before { content: "✓"; position: absolute; left: 0; color: #FF4B26; font-weight: bold; }

        .testimonials-section { padding: 80px 20px; background: #f8f9fa; }
        .testimonials-container { max-width: 1200px; margin: 0 auto; }
        .testimonials-header { text-align: center; margin-bottom: 50px; }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .testimonial-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .testimonial-stars { color: #ffc107; margin-bottom: 15px; font-size: 1.2rem; }
        .testimonial-text { font-style: italic; margin-bottom: 20px; line-height: 1.6; }
        .testimonial-author { font-weight: 700; }
        .testimonial-role { color: #666; font-size: 0.9rem; }

        .faq-section { padding: 80px 20px; }
        .faq-container { max-width: 800px; margin: 0 auto; }
        .faq-header { text-align: center; margin-bottom: 50px; }
        .faq-item { border-bottom: 1px solid #eee; }
        .faq-question { padding: 20px 0; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .faq-question:after { content: "+"; font-size: 1.5rem; color: #FF4B26; }
        .faq-item.open .faq-question:after { content: "−"; }
        .faq-answer { padding: 0 0 20px; display: none; line-height: 1.7; color: #555; }
        .faq-item.open .faq-answer { display: block; }

        .guide-section { padding: 80px 20px; background: #f8f9fa; }
        .guide-container { max-width: 900px; margin: 0 auto; }
        .guide-content { line-height: 1.8; }
        .guide-content h2, .guide-content h3 { margin-top: 30px; color: #1a1a1a; }

        @media (max-width: 768px) {
            .cat-hero h1 { font-size: 2.5rem; }
            .trust-container { gap: 30px; }
            .cta-features { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- HERO -->
    <?php if (!empty($page['hero_title'])): ?>
    <section class="cat-hero" <?php if (!empty($page['hero_image'])): ?>style="background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?= htmlspecialchars($page['hero_image']) ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
        <h1><?= htmlspecialchars($page['hero_title']) ?></h1>
        <?php if (!empty($page['hero_subtitle'])): ?>
        <p><?= $page['hero_subtitle'] ?></p>
        <?php endif; ?>
        <?php if (!empty($page['hero_cta_text'])): ?>
        <a href="<?= htmlspecialchars($page['hero_cta_link'] ?: '#products') ?>" class="btn-primary"><?= htmlspecialchars($page['hero_cta_text']) ?></a>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- TRUST BAR -->
    <?php if (!empty($trustBar)): ?>
    <div class="trust-bar">
        <div class="trust-container">
            <?php foreach ($trustBar as $item): ?>
            <div class="trust-item">
                <div class="trust-value"><?= htmlspecialchars($item['value'] ?? '') ?></div>
                <div class="trust-label"><?= htmlspecialchars($item['label'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUCTS SECTION -->
    <section class="products-section" id="products">
        <div class="products-header">
            <?php if (!empty($page['products_title'])): ?>
            <h2><?= htmlspecialchars($page['products_title']) ?></h2>
            <?php endif; ?>
            <?php if (!empty($page['products_subtitle'])): ?>
            <p><?= htmlspecialchars($page['products_subtitle']) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($page['show_filters'] && (!empty($filterSports) || !empty($filterGenres))): ?>
        <div class="products-filters">
            <?php if (!empty($filterSports)): ?>
            <select id="filterSport" onchange="filterProducts()">
                <option value="">Tous les sports</option>
                <?php foreach ($filterSports as $sport): ?>
                <option value="<?= htmlspecialchars($sport) ?>"><?= htmlspecialchars($sport) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (!empty($filterGenres)): ?>
            <select id="filterGenre" onchange="filterProducts()">
                <option value="">Tous les genres</option>
                <?php foreach ($filterGenres as $genre): ?>
                <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select id="sortProducts" onchange="sortProducts()">
                <option value="default">Tri par défaut</option>
                <option value="price_asc">Prix croissant</option>
                <option value="price_desc">Prix décroissant</option>
                <option value="name">Alphabétique</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $prod):
                $prodName = !empty($prod['meta_title']) ? $prod['meta_title'] : $prod['nom'];
                $prodPrice = $prod['prix_500'] ? number_format($prod['prix_500'], 2, ',', ' ') : '';
            ?>
            <div class="product-card" data-sport="<?= htmlspecialchars($prod['sport']) ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>">
                <a href="/produit/<?= htmlspecialchars($prod['reference']) ?>">
                    <img src="<?= htmlspecialchars($prod['photo_1'] ?: '/photos/placeholder.webp') ?>" alt="<?= htmlspecialchars($prodName) ?>">
                </a>
                <div class="info">
                    <h3><?= htmlspecialchars($prodName) ?></h3>
                    <p style="color: #666; font-size: 0.9rem;"><?= htmlspecialchars($prod['sport'] . ' • ' . $prod['famille']) ?></p>
                    <?php if ($prodPrice): ?>
                    <div class="price">Dès <?= $prodPrice ?> €</div>
                    <?php endif; ?>
                    <a href="/produit/<?= htmlspecialchars($prod['reference']) ?>" class="btn">Voir le produit</a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($products)): ?>
            <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun produit dans cette catégorie pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA SECTION -->
    <?php if (!empty($page['cta_title'])): ?>
    <section class="cta-section">
        <h2><?= htmlspecialchars($page['cta_title']) ?></h2>
        <?php if (!empty($page['cta_subtitle'])): ?>
        <p style="max-width: 600px; margin: 0 auto 30px; font-size: 1.1rem;"><?= htmlspecialchars($page['cta_subtitle']) ?></p>
        <?php endif; ?>

        <?php if (!empty($ctaFeatures)): ?>
        <div class="cta-features">
            <?php foreach ($ctaFeatures as $feature): ?>
            <div class="cta-feature"><?= htmlspecialchars($feature) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="cta-buttons">
            <?php if (!empty($page['cta_button_text'])): ?>
            <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/devis.html') ?>" class="btn-white"><?= htmlspecialchars($page['cta_button_text']) ?></a>
            <?php endif; ?>
            <?php if (!empty($page['cta_whatsapp'])): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $page['cta_whatsapp']) ?>" class="btn-outline">WhatsApp Direct</a>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- EXCELLENCE SECTION -->
    <?php if (!empty($page['excellence_title']) && !empty($excellenceColumns)): ?>
    <section class="excellence-section">
        <div class="excellence-container">
            <div class="excellence-header">
                <h2><?= htmlspecialchars($page['excellence_title']) ?></h2>
                <?php if (!empty($page['excellence_subtitle'])): ?>
                <p><?= htmlspecialchars($page['excellence_subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div class="excellence-grid">
                <?php foreach ($excellenceColumns as $col): ?>
                <div class="excellence-card">
                    <?php if (!empty($col['icon'])): ?>
                    <div style="font-size: 3rem;"><?= $col['icon'] ?></div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($col['title'] ?? '') ?></h3>
                    <p><?= $col['content'] ?? '' ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- TECHNOLOGY SECTION -->
    <?php if (!empty($page['tech_title'])): ?>
    <section class="tech-section">
        <div class="tech-container">
            <h2><?= htmlspecialchars($page['tech_title']) ?></h2>
            <?php if (!empty($page['tech_content'])): ?>
            <div style="margin-top: 20px; line-height: 1.8;"><?= $page['tech_content'] ?></div>
            <?php endif; ?>

            <?php if (!empty($techStats)): ?>
            <div class="tech-stats">
                <?php foreach ($techStats as $stat): ?>
                <div class="tech-stat">
                    <div class="value"><?= htmlspecialchars($stat['value'] ?? '') ?></div>
                    <div><?= htmlspecialchars($stat['label'] ?? '') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- FEATURES SECTIONS -->
    <?php if (!empty($featuresSections)): ?>
    <section class="features-section">
        <div class="features-container">
            <?php foreach ($featuresSections as $section): ?>
            <div class="features-block">
                <h3><?= htmlspecialchars($section['title'] ?? '') ?></h3>
                <?php if (!empty($section['intro'])): ?>
                <p><?= $section['intro'] ?></p>
                <?php endif; ?>
                <?php if (!empty($section['items'])): ?>
                <ul>
                    <?php foreach ($section['items'] as $item): ?>
                    <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- TESTIMONIALS -->
    <?php if (!empty($testimonials)): ?>
    <section class="testimonials-section">
        <div class="testimonials-container">
            <div class="testimonials-header">
                <h2>Ils nous font confiance</h2>
            </div>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <div class="testimonial-text">"<?= htmlspecialchars($testimonial['text'] ?? '') ?>"</div>
                    <div class="testimonial-author"><?= htmlspecialchars($testimonial['author'] ?? '') ?></div>
                    <div class="testimonial-role"><?= htmlspecialchars($testimonial['role'] ?? '') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if (!empty($page['faq_title']) && !empty($faqItems)): ?>
    <section class="faq-section">
        <div class="faq-container">
            <div class="faq-header">
                <h2><?= htmlspecialchars($page['faq_title']) ?></h2>
            </div>
            <?php foreach ($faqItems as $faq): ?>
            <div class="faq-item">
                <div class="faq-question"><?= htmlspecialchars($faq['question'] ?? '') ?></div>
                <div class="faq-answer"><?= $faq['answer'] ?? '' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- GUIDE SEO CONTENT -->
    <?php if (!empty($page['guide_title']) && !empty($page['guide_content'])): ?>
    <section class="guide-section">
        <div class="guide-container">
            <h2><?= htmlspecialchars($page['guide_title']) ?></h2>
            <div class="guide-content">
                <?= $page['guide_content'] ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components-loader.js"></script>
    <script>
        // FAQ Toggle
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                q.parentElement.classList.toggle('open');
            });
        });

        // Product filtering
        function filterProducts() {
            const sport = document.getElementById('filterSport')?.value || '';
            const genre = document.getElementById('filterGenre')?.value || '';

            document.querySelectorAll('.product-card').forEach(card => {
                const cardSport = card.dataset.sport || '';
                const cardGenre = card.dataset.genre || '';
                let show = true;

                if (sport && cardSport !== sport) show = false;
                if (genre && cardGenre !== genre) show = false;

                card.style.display = show ? 'block' : 'none';
            });
        }

        // Product sorting
        function sortProducts() {
            const sort = document.getElementById('sortProducts')?.value || 'default';
            const grid = document.getElementById('productsGrid');
            const cards = Array.from(grid.querySelectorAll('.product-card'));

            cards.sort((a, b) => {
                switch (sort) {
                    case 'price_asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price_desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'name':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    default:
                        return 0;
                }
            });

            cards.forEach(card => grid.appendChild(card));
        }
    </script>
</body>
</html>
