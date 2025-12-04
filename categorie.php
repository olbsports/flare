<?php
/**
 * PAGE CATÉGORIE DYNAMIQUE - FLARE CUSTOM
 * Template générique configurable depuis l'admin
 * Design identique à maillots-football-personnalises.html
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

    // Extraire les sports/genres uniques des produits pour les filtres
    $uniqueSports = [];
    $uniqueGenres = [];
    foreach ($products as $prod) {
        if (!empty($prod['sport']) && !in_array($prod['sport'], $uniqueSports)) {
            $uniqueSports[] = $prod['sport'];
        }
        if (!empty($prod['genre']) && !in_array($prod['genre'], $uniqueGenres)) {
            $uniqueGenres[] = $prod['genre'];
        }
    }
    sort($uniqueSports);
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
    <link rel="canonical" href="<?= $siteUrl ?>/categorie/<?= htmlspecialchars($slug) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= $siteUrl ?>/categorie/<?= htmlspecialchars($slug) ?>">

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
    /* Product Cards - Styles complémentaires */
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

    .product-link:hover {
        color: #E63910;
    }

    /* Badge styles */
    .badge-genre {
        background: #f3f4f6;
        color: #374151;
    }

    .badge-tissu {
        background: #FF4B26;
        color: #fff;
    }
    </style>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- HERO SECTION -->
    <section class="hero-sport">
        <div class="hero-sport-content">
            <?php if (!empty($page['hero_subtitle'])): ?>
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_subtitle']) ?></span>
            <?php endif; ?>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: $page['title']) ?></h1>
            <?php if (!empty($page['products_subtitle'])): ?>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['products_subtitle']) ?></p>
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
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_title'] ?: 'Catalogue') ?></div>
                <h2 class="section-title">Nos produits personnalisés</h2>
                <p class="section-description">
                    <?= $productCount ?> modèles disponibles. Tissus techniques haute performance,<br>
                    Personnalisation illimitée, fabrication européenne certifiée.
                </p>
            </div>

            <?php if ($page['show_filters'] && (!empty($uniqueSports) || !empty($uniqueGenres))): ?>
            <div class="filters-bar">
                <?php if (!empty($uniqueSports)): ?>
                <div class="filter-group">
                    <label for="filterSport">Sport</label>
                    <select id="filterSport" class="filter-select">
                        <option value="">Tous</option>
                        <?php foreach ($uniqueSports as $sport): ?>
                        <option value="<?= htmlspecialchars($sport) ?>"><?= htmlspecialchars($sport) ?></option>
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
                <div class="product-card" data-sport="<?= htmlspecialchars($prod['sport'] ?? '') ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-famille="<?= htmlspecialchars($prod['famille'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            <?php foreach ($photos as $idx => $photo): ?>
                            <div class="product-slide <?= $idx === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($prodName) ?> - Photo <?= $idx + 1 ?>" class="product-image" loading="lazy" width="420" height="560" decoding="async">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($photos) > 1): ?>
                        <div class="slider-controls">
                            <div class="slider-dots">
                                <?php foreach ($photos as $idx => $photo): ?>
                                <button class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" data-slide="<?= $idx ?>" aria-label="Photo <?= $idx + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?= htmlspecialchars($prodName) ?></h3>
                        <div class="product-specs">
                            <?php if (!empty($prod['grammage'])): ?>
                            <span class="product-spec"><?= htmlspecialchars($prod['grammage']) ?> gr/m²</span>
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
                        <a href="/produit/<?= htmlspecialchars($prod['reference']) ?>" class="product-link">Voir le produit →</a>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun produit dans cette catégorie pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <?php if (!empty($page['cta_title'])): ?>
    <section class="cta-redesign">
        <div class="cta-redesign-container">
            <h2 class="cta-redesign-title"><?= htmlspecialchars($page['cta_title']) ?></h2>
            <?php if (!empty($page['cta_subtitle'])): ?>
            <p class="cta-redesign-subtitle"><?= htmlspecialchars($page['cta_subtitle']) ?></p>
            <?php endif; ?>

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
            <?php endif; ?>

            <div class="cta-redesign-buttons">
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
    </section>
    <?php endif; ?>

    <!-- SEO MEGA SECTION -->
    <?php if (!empty($page['excellence_title']) || !empty($testimonials) || !empty($faqItems)): ?>
    <section class="seo-mega">
        <div class="container">

            <?php if (!empty($page['excellence_title'])): ?>
            <div class="seo-hero">
                <div class="seo-hero-badge"><?= htmlspecialchars($page['excellence_subtitle'] ?: 'Excellence') ?></div>
                <h2 class="seo-hero-title"><?= htmlspecialchars($page['excellence_title']) ?></h2>
                <?php if (!empty($page['tech_content'])): ?>
                <p class="seo-hero-intro"><?= htmlspecialchars(strip_tags($page['tech_content'])) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($excellenceColumns)): ?>
            <div class="seo-cards-3">
                <?php foreach ($excellenceColumns as $col): ?>
                <div class="seo-card">
                    <?php if (!empty($col['icon'])): ?>
                    <div class="seo-card-icon"><?= $col['icon'] ?></div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($col['title'] ?? '') ?></h3>
                    <p><?= $col['content'] ?? '' ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($techStats)): ?>
            <div class="seo-stats">
                <div class="seo-stats-grid">
                    <?php foreach ($techStats as $stat): ?>
                    <div class="seo-stat">
                        <div class="seo-stat-number"><?= htmlspecialchars($stat['value'] ?? '') ?></div>
                        <div class="seo-stat-label"><?= htmlspecialchars($stat['label'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($featuresSections)): ?>
            <div class="seo-alternating">
                <?php foreach ($featuresSections as $section): ?>
                <div class="seo-alt-block">
                    <div class="seo-alt-content">
                        <h3><?= htmlspecialchars($section['title'] ?? '') ?></h3>
                        <?php if (!empty($section['intro'])): ?>
                        <p><?= $section['intro'] ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($section['items'])): ?>
                    <div class="seo-alt-visual">
                        <ul>
                            <?php foreach ($section['items'] as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($testimonials)): ?>
            <div class="seo-testimonials">
                <h2>Ils nous font confiance</h2>
                <div class="seo-testimonials-grid">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <div class="seo-testimonial">
                        <div class="testimonial-stars">★★★★★</div>
                        <p class="testimonial-text"><?= htmlspecialchars($testimonial['text'] ?? '') ?></p>
                        <div class="testimonial-author"><?= htmlspecialchars($testimonial['author'] ?? '') ?><?php if (!empty($testimonial['role'])): ?> - <?= htmlspecialchars($testimonial['role']) ?><?php endif; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($page['faq_title']) && !empty($faqItems)): ?>
            <div class="seo-faq">
                <h2><?= htmlspecialchars($page['faq_title']) ?></h2>

                <?php foreach ($faqItems as $faq): ?>
                <div class="faq-accordion-item">
                    <button class="faq-accordion-question" onclick="this.parentElement.classList.toggle('active')">
                        <span><?= htmlspecialchars($faq['question'] ?? '') ?></span>
                        <span class="faq-accordion-icon">+</span>
                    </button>
                    <div class="faq-accordion-answer">
                        <p><?= $faq['answer'] ?? '' ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </section>
    <?php endif; ?>

    <!-- GUIDE SEO CONTENT -->
    <?php if (!empty($page['guide_title']) && !empty($page['guide_content'])): ?>
    <section class="seo-longtail-mega">
        <div class="seo-longtail-container">
            <div class="seo-longtail-header">
                <span class="seo-longtail-badge">Guide complet</span>
                <h2 class="seo-longtail-title"><?= htmlspecialchars($page['guide_title']) ?></h2>
            </div>
            <div class="seo-longtail-content">
                <?= $page['guide_content'] ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components-loader.js"></script>
    <script>
        // Product filtering
        document.getElementById('filterSport')?.addEventListener('change', filterProducts);
        document.getElementById('filterGenre')?.addEventListener('change', filterProducts);
        document.getElementById('sortProducts')?.addEventListener('change', sortProducts);

        function filterProducts() {
            const sport = document.getElementById('filterSport')?.value || '';
            const genre = document.getElementById('filterGenre')?.value || '';
            let visibleCount = 0;

            document.querySelectorAll('.product-card').forEach(card => {
                const cardSport = card.dataset.sport || '';
                const cardGenre = card.dataset.genre || '';
                let show = true;

                if (sport && cardSport !== sport) show = false;
                if (genre && cardGenre !== genre) show = false;

                card.style.display = show ? 'block' : 'none';
                if (show) visibleCount++;
            });

            document.getElementById('productsCount').textContent = visibleCount + ' produit' + (visibleCount > 1 ? 's' : '');
        }

        function sortProducts() {
            const sort = document.getElementById('sortProducts')?.value || 'default';
            const grid = document.getElementById('productsGrid');
            const cards = Array.from(grid.querySelectorAll('.product-card'));

            cards.sort((a, b) => {
                switch (sort) {
                    case 'price-asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'name':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    default:
                        return 0;
                }
            });

            cards.forEach(card => grid.appendChild(card));
        }

        // Product image slider
        document.querySelectorAll('.product-card').forEach(card => {
            const slides = card.querySelectorAll('.product-slide');
            const dots = card.querySelectorAll('.slider-dot');

            if (slides.length <= 1) return;

            let currentSlide = 0;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
                currentSlide = index;
            }

            dots.forEach((dot, index) => {
                dot.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    showSlide(index);
                });
            });

            // Auto-slide on hover
            card.addEventListener('mouseenter', () => {
                if (slides.length > 1) {
                    showSlide((currentSlide + 1) % slides.length);
                }
            });

            card.addEventListener('mouseleave', () => {
                showSlide(0);
            });
        });
    </script>
</body>
</html>
