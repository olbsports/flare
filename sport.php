<?php
/**
 * PAGE SPORT DYNAMIQUE - FLARE CUSTOM
 * Template générique configurable depuis l'admin
 * Design identique à equipement-football-personnalise-sublimation.html
 */

require_once __DIR__ . '/config/database.php';

// Récupérer le slug du sport
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Sport non trouvé");
}

try {
    // Utiliser la connexion existante si disponible (quand inclus depuis page.php)
    if (!isset($pdo) || !$pdo) {
        $pdo = getConnection();
    }

    // Charger la page sport
    $stmt = $pdo->prepare("SELECT * FROM sport_pages WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        die("Sport non trouvé: " . htmlspecialchars($slug));
    }

    // Charger les produits associés à cette page
    $stmt = $pdo->prepare("
        SELECT p.*, pp.position
        FROM products p
        INNER JOIN page_products pp ON p.id = pp.product_id
        WHERE pp.page_type = 'sport_page' AND pp.page_slug = ? AND p.active = 1
        ORDER BY pp.position, p.nom
    ");
    $stmt->execute([$slug]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Décoder les champs JSON
    $trustBar = json_decode($page['trust_bar'] ?? '[]', true) ?: [];
    $ctaFeatures = json_decode($page['cta_features'] ?? '[]', true) ?: [];
    $whyItems = json_decode($page['why_items'] ?? '[]', true) ?: [];
    $faqItems = json_decode($page['faq_items'] ?? '[]', true) ?: [];
    $seoSections = json_decode($page['seo_sections'] ?? '[]', true) ?: [];

    // Extraire les familles/genres uniques des produits pour les filtres
    $uniqueFamilles = [];
    $uniqueGenres = [];
    foreach ($products as $prod) {
        if (!empty($prod['famille']) && !in_array($prod['famille'], $uniqueFamilles)) {
            $uniqueFamilles[] = $prod['famille'];
        }
        if (!empty($prod['genre']) && !in_array($prod['genre'], $uniqueGenres)) {
            $uniqueGenres[] = $prod['genre'];
        }
    }
    sort($uniqueFamilles);
    sort($uniqueGenres);

    // Paramètres du site
    $siteName = 'FLARE CUSTOM';
    $siteUrl = 'https://flare-custom.com';

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur de chargement");
}

$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?: '';
$productCount = count($products);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $siteUrl ?>/sport/<?= htmlspecialchars($slug) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= $siteUrl ?>/sport/<?= htmlspecialchars($slug) ?>">

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
    /* Product Cards */
    .product-title {
        font-size: 16px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 8px;
        color: #1a1a1a;
    }
    .product-link {
        display: inline-block;
        color: #FF4B26;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: color 0.2s;
    }
    .product-link:hover { color: #E63910; }
    .product-card {
        position: relative;
    }
    .product-card-link {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 1;
    }
    .product-card .slider-controls,
    .product-card .slider-dot {
        position: relative;
        z-index: 2;
    }
    </style>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- HERO SECTION -->
    <section class="hero-sport">
        <div class="hero-sport-content">
            <?php if (!empty($page['hero_eyebrow'])): ?>
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_eyebrow']) ?></span>
            <?php endif; ?>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: $page['title']) ?></h1>
            <?php if (!empty($page['hero_subtitle'])): ?>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['hero_subtitle']) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- TRUST BAR -->
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

    <!-- PRODUCTS SECTION -->
    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <?php if (!empty($page['products_eyebrow'])): ?>
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_eyebrow']) ?></div>
                <?php endif; ?>
                <h2 class="section-title"><?= htmlspecialchars($page['products_title'] ?: 'Nos équipements') ?></h2>
                <?php if (!empty($page['products_description'])): ?>
                <p class="section-description"><?= htmlspecialchars($page['products_description']) ?></p>
                <?php else: ?>
                <p class="section-description">
                    <?= $productCount ?> modèles disponibles. Tissus techniques haute performance,<br>
                    Personnalisation illimitée, fabrication européenne certifiée.
                </p>
                <?php endif; ?>
            </div>

            <?php if ($page['show_filters'] && (!empty($uniqueFamilles) || !empty($uniqueGenres))): ?>
            <div class="filters-bar">
                <?php if (!empty($uniqueFamilles)): ?>
                <div class="filter-group">
                    <label for="filterFamily">Famille</label>
                    <select id="filterFamily" class="filter-select">
                        <option value="">Tous</option>
                        <?php foreach ($uniqueFamilles as $fam): ?>
                        <option value="<?= htmlspecialchars($fam) ?>"><?= htmlspecialchars($fam) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (!empty($uniqueGenres)): ?>
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

                <div class="filter-group">
                    <label for="sortProducts">Trier par</label>
                    <select id="sortProducts" class="filter-select">
                        <option value="default">Par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="name">Nom A-Z</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="products-count">
                <span id="productsCount"><?= $productCount ?> produit<?= $productCount > 1 ? 's' : '' ?></span>
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
                ?>
                <div class="product-card" data-famille="<?= htmlspecialchars($prod['famille'] ?? '') ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>">
                    <a href="/produit/<?= htmlspecialchars($prod['reference']) ?>" class="product-card-link"></a>
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            <?php foreach ($photos as $idx => $photo): ?>
                            <div class="product-slide <?= $idx === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($prodName) ?> - Photo <?= $idx + 1 ?>" class="product-image" loading="lazy" width="420" height="560" decoding="async">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($photos) > 1): ?>
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
                        <div class="product-slider-dots">
                            <?php foreach ($photos as $idx => $photo): ?>
                            <button class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" data-slide="<?= $idx ?>" aria-label="Voir photo <?= $idx + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if (!empty($prod['famille'])): ?>
                        <div class="product-family"><?= htmlspecialchars($prod['famille']) ?></div>
                        <?php endif; ?>
                        <h3 class="product-name"><?= htmlspecialchars($prodName) ?></h3>
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
                            $prixEnfant = number_format(floatval($prod['prix_500']) * 0.90, 2, '.', '');
                        ?>
                        <div class="product-pricing">
                            <div class="product-price-label">À partir de</div>
                            <div class="product-price-adulte">
                                <span class="product-price-type">Adulte</span>
                                <span class="product-price"><?= $prodPrice ?>€</span>
                            </div>
                            <div class="product-price-enfant">
                                <span class="product-price-type">Enfant</span>
                                <span class="product-price-small"><?= $prixEnfant ?>€</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <span class="product-link">Voir le produit →</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun produit dans ce sport pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- WHY US SECTION -->
    <?php if (!empty($page['why_title']) || !empty($whyItems)): ?>
    <section class="why-us-section" id="why-us">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?= htmlspecialchars($page['why_title'] ?: 'Pourquoi choisir Flare Custom') ?></h2>
                <?php if (!empty($page['why_subtitle'])): ?>
                <p class="section-description"><?= htmlspecialchars($page['why_subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($whyItems)): ?>
            <div class="why-grid">
                <?php foreach ($whyItems as $item): ?>
                <div class="why-item">
                    <?php if (!empty($item['icon'])): ?>
                    <div class="why-icon"><?= $item['icon'] ?></div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['description'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA SECTION -->
    <?php if (!empty($page['cta_title'])): ?>
    <section class="cta-section" id="contact">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title"><?= htmlspecialchars($page['cta_title']) ?></h2>
                <?php if (!empty($page['cta_subtitle'])): ?>
                <p class="cta-subtitle"><?= htmlspecialchars($page['cta_subtitle']) ?></p>
                <?php endif; ?>

                <?php if (!empty($ctaFeatures)): ?>
                <div class="cta-features">
                    <?php foreach ($ctaFeatures as $feature): ?>
                    <div class="cta-feature">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/>
                        </svg>
                        <span><?= htmlspecialchars($feature) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="cta-buttons">
                    <?php if (!empty($page['cta_button_text'])): ?>
                    <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/contact.html') ?>" class="btn-cta-main">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 8L10.89 13.26C11.24 13.48 11.62 13.59 12 13.59C12.38 13.59 12.76 13.48 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z"/>
                        </svg>
                        <span><?= htmlspecialchars($page['cta_button_text']) ?></span>
                    </a>
                    <?php endif; ?>
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
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ SECTION -->
    <?php if (!empty($faqItems) && !empty(array_filter($faqItems, fn($f) => !empty($f['question'])))): ?>
    <section class="faq-sport-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?= htmlspecialchars($page['faq_title'] ?: 'Questions fréquentes') ?></h2>
            </div>
            <div class="faq-accordion">
                <?php foreach ($faqItems as $faq): ?>
                <?php if (!empty($faq['question'])): ?>
                <div class="faq-item">
                    <button class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                        <span><?= htmlspecialchars($faq['question']) ?></span>
                        <svg class="faq-icon" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="faq-answer">
                        <p><?= $faq['answer'] ?? '' ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- SEO SECTIONS -->
    <?php if (!empty($seoSections)): ?>
    <?php foreach ($seoSections as $sec): ?>
    <?php if (!empty($sec['title']) || !empty($sec['content'])): ?>
    <section class="seo-footer-section">
        <div class="container">
            <div class="seo-content">
                <?php if (!empty($sec['title'])): ?>
                <h2 class="section-title"><?= htmlspecialchars($sec['title']) ?></h2>
                <?php endif; ?>
                <?php if (!empty($sec['content'])): ?>
                <div class="seo-text"><?= $sec['content'] ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Product slider
        document.querySelectorAll('.product-card').forEach(function(card) {
            var slides = card.querySelectorAll('.product-slide');
            var dots = card.querySelectorAll('.slider-dot');
            var prevBtn = card.querySelector('.slider-nav.prev');
            var nextBtn = card.querySelector('.slider-nav.next');
            var currentSlide = 0;

            function showSlide(n) {
                currentSlide = (n + slides.length) % slides.length;
                slides.forEach(function(s, i) {
                    s.classList.toggle('active', i === currentSlide);
                });
                dots.forEach(function(d, i) {
                    d.classList.toggle('active', i === currentSlide);
                });
            }

            if (prevBtn) prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showSlide(currentSlide - 1);
            });

            if (nextBtn) nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showSlide(currentSlide + 1);
            });

            dots.forEach(function(dot, i) {
                dot.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showSlide(i);
                });
            });
        });

        // Filters
        var filterFamily = document.getElementById('filterFamily');
        var filterGenre = document.getElementById('filterGenre');
        var sortProducts = document.getElementById('sortProducts');
        var productsGrid = document.getElementById('productsGrid');
        var productsCount = document.getElementById('productsCount');

        function applyFilters() {
            var family = filterFamily ? filterFamily.value : '';
            var genre = filterGenre ? filterGenre.value : '';
            var sort = sortProducts ? sortProducts.value : 'default';
            var cards = Array.from(productsGrid.querySelectorAll('.product-card'));
            var visible = 0;

            cards.forEach(function(card) {
                var matchFamily = !family || card.dataset.famille === family;
                var matchGenre = !genre || card.dataset.genre === genre;
                var show = matchFamily && matchGenre;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Sort
            if (sort !== 'default') {
                var sortedCards = cards.filter(function(c) { return c.style.display !== 'none'; });
                sortedCards.sort(function(a, b) {
                    if (sort === 'price-asc') return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    if (sort === 'price-desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    if (sort === 'name') return a.dataset.name.localeCompare(b.dataset.name);
                    return 0;
                });
                sortedCards.forEach(function(card) {
                    productsGrid.appendChild(card);
                });
            }

            if (productsCount) {
                productsCount.textContent = visible + ' produit' + (visible > 1 ? 's' : '');
            }
        }

        if (filterFamily) filterFamily.addEventListener('change', applyFilters);
        if (filterGenre) filterGenre.addEventListener('change', applyFilters);
        if (sortProducts) sortProducts.addEventListener('change', applyFilters);
    });
    </script>
</body>
</html>
