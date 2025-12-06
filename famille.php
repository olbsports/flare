<?php
/**
 * PAGE FAMILLE DYNAMIQUE - FLARE CUSTOM
 * Template pour pages famille de produits (maillots, shorts, etc.)
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

    // Charger les produits selon le mode (famille, sport ou manuel)
    $productsSource = $page['products_source'] ?? 'famille';

    if ($productsSource === 'famille' && !empty($page['products_famille_filter'])) {
        // Mode: filtrer par famille
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
        // Mode: filtrer par sport
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
        // Mode: sélection manuelle via page_products
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?: "$familleName personnalisés sublimation. Tous sports. Design gratuit, fabrication européenne, livraison 3-4 semaines. Devis gratuit sous 24h.") ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= $siteUrl ?>/famille/<?= htmlspecialchars($slug) ?>">

    <link rel="preload" href="/assets/css/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/style.css"></noscript>
    <link rel="stylesheet" href="/assets/css/style-sport.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Bebas+Neue&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Bebas+Neue&display=swap"></noscript>
    <link rel="preload" href="/assets/css/components.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/css/components.css"></noscript>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- Hero -->
    <section class="hero-sport">
        <div class="hero-sport-content">
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_eyebrow'] ?: "$familleIcon $familleName personnalisés") ?></span>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: "$familleName Sport Sublimation") ?></h1>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['hero_subtitle'] ?: "$productCount modèles personnalisables tous sports") ?></p>
        </div>
    </section>

    <!-- Trust Bar -->
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

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_eyebrow'] ?: "Catalogue $familleName") ?></div>
                <h2 class="section-title"><?= htmlspecialchars($page['products_title'] ?: "Nos $familleNameLower personnalisés") ?></h2>
                <p class="section-description">
                    <?= htmlspecialchars($page['products_description'] ?: "$productCount modèles tous sports. Tissus techniques, personnalisation illimitée, fabrication européenne.") ?>
                </p>
            </div>

            <?php if ($page['show_filters'] ?? true): ?>
            <div class="filters-bar">
                <?php if (($page['filter_sport'] ?? 1) && !empty($uniqueSports)): ?>
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
                    $isEco = stripos($prodName, 'eco') !== false || stripos($prod['tissu'] ?? '', 'eco') !== false;
                ?>
                <div class="product-card" data-sport="<?= htmlspecialchars($prod['sport'] ?? '') ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>">
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
                            $prixEnfant = number_format(floatval($prod['prix_500']) * 0.80, 2, '.', '');
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
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun produit dans cette famille pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section (redesign style) -->
    <section class="cta-redesign">
        <div class="cta-redesign-container">
            <h2 class="cta-redesign-title"><?= htmlspecialchars($page['cta_title'] ?: "Créons ensemble vos $familleNameLower personnalisés") ?></h2>
            <p class="cta-redesign-subtitle"><?= htmlspecialchars($page['cta_subtitle'] ?: "Équipez votre club avec des $familleNameLower uniques") ?></p>

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
                <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/contact.html') ?>" class="btn-cta-main">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 8L10.89 13.26C11.24 13.48 11.62 13.59 12 13.59C12.38 13.59 12.76 13.48 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z"/>
                    </svg>
                    <span><?= htmlspecialchars($page['cta_button_text'] ?: 'Demander un Devis') ?></span>
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

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>
    <script src="/assets/js/product-cards-linker.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    productsCount.textContent = visibleCount + ' produit' + (visibleCount > 1 ? 's' : '');
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
