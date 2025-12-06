<?php
/**
 * PAGE SPORT DYNAMIQUE - FLARE CUSTOM
 * Template générique configurable depuis l'admin
 * Design identique à equipement-football-personnalise-sublimation.html
 */

require_once __DIR__ . '/config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Sport non trouvé");
}

try {
    if (!isset($pdo) || !$pdo) {
        $pdo = getConnection();
    }

    $stmt = $pdo->prepare("SELECT * FROM sport_pages WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        die("Sport non trouvé: " . htmlspecialchars($slug));
    }

    // Charger les produits selon le mode (manuel, sport ou famille)
    $productsSource = $page['products_source'] ?? 'manual';

    if ($productsSource === 'sport' && !empty($page['products_sport_filter'])) {
        // Mode: filtrer par sport
        // D'abord récupérer l'ordre personnalisé s'il existe
        $orderStmt = $pdo->prepare("SELECT product_id, position FROM page_products WHERE page_type = 'sport_page' AND page_slug = ?");
        $orderStmt->execute([$slug]);
        $positions = [];
        while ($row = $orderStmt->fetch()) {
            $positions[$row['product_id']] = $row['position'];
        }

        $stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.sport = ? AND p.active = 1 ORDER BY p.nom");
        $stmt->execute([$page['products_sport_filter']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Appliquer l'ordre personnalisé si défini
        if (!empty($positions)) {
            usort($products, function($a, $b) use ($positions) {
                $posA = $positions[$a['id']] ?? 9999;
                $posB = $positions[$b['id']] ?? 9999;
                return $posA - $posB;
            });
        }

    } elseif ($productsSource === 'famille' && !empty($page['products_famille_filter'])) {
        // Mode: filtrer par famille
        // D'abord récupérer l'ordre personnalisé s'il existe
        $orderStmt = $pdo->prepare("SELECT product_id, position FROM page_products WHERE page_type = 'sport_page' AND page_slug = ?");
        $orderStmt->execute([$slug]);
        $positions = [];
        while ($row = $orderStmt->fetch()) {
            $positions[$row['product_id']] = $row['position'];
        }

        $stmt = $pdo->prepare("SELECT p.* FROM products p WHERE p.famille = ? AND p.active = 1 ORDER BY p.nom");
        $stmt->execute([$page['products_famille_filter']]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Appliquer l'ordre personnalisé si défini
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
            WHERE pp.page_type = 'sport_page' AND pp.page_slug = ? AND p.active = 1
            ORDER BY pp.position, p.nom
        ");
        $stmt->execute([$slug]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Décoder les champs JSON
    $trustBar = json_decode($page['trust_bar'] ?? '[]', true) ?: [];
    $ctaFeatures = json_decode($page['cta_features'] ?? '[]', true) ?: [];
    $whyItems = json_decode($page['why_items'] ?? '[]', true) ?: [];
    $faqItems = json_decode($page['faq_items'] ?? '[]', true) ?: [];
    $seoSections = json_decode($page['seo_sections'] ?? '[]', true) ?: [];

    // Extraire les familles/genres uniques
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

    $siteName = 'FLARE CUSTOM';
    $siteUrl = 'https://flare-custom.com';

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur de chargement");
}

$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?: '';
$productCount = count($products);
$sportName = $page['sport_name'] ?: $page['title'];
$sportNameLower = strtolower($sportName);
$sportIcon = $page['sport_icon'] ?? '🏆';

// Pas de valeurs par défaut - tout vient de la BDD
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?: "Équipements $sportName personnalisés sublimation. Maillots, shorts, kits complets sur mesure. Design gratuit, fabrication européenne, livraison 3-4 semaines. Devis gratuit sous 24h.") ?>">
    <meta name="robots" content="index, follow">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
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
</head>
<body>
    <!-- 🔥 HEADER DYNAMIQUE -->
    <div id="dynamic-header"></div>

    <!-- Hero Sport -->
    <section class="hero-sport">
        <div class="hero-sport-content">
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_eyebrow'] ?: "$sportIcon $sportName") ?></span>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: "Équipements $sportName") ?></h1>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['hero_subtitle'] ?: 'Personnalisés Sublimation') ?></p>
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
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_eyebrow'] ?: "Catalogue $sportNameLower") ?></div>
                <h2 class="section-title"><?= htmlspecialchars($page['products_title'] ?: "Nos équipements $sportNameLower") ?></h2>
                <p class="section-description">
                    <?= htmlspecialchars($page['products_description'] ?: "Plus de $productCount modèles de maillots, shorts et kits complets. Tissus techniques respirants, coutures renforcées, personnalisation illimitée en sublimation.") ?>
                </p>
            </div>

            <!-- Filters (configurables depuis l'admin) -->
            <?php if ($page['show_filters'] ?? true): ?>
            <div class="filters-bar">
                <?php if (($page['filter_famille'] ?? 1) && !empty($uniqueFamilles)): ?>
                <div class="filter-group">
                    <label>Famille</label>
                    <label for="filterFamily" class="sr-only">Filtrer par famille de produit</label>
                    <select id="filterFamily" class="filter-select">
                        <option value="">Tous les produits</option>
                        <?php foreach ($uniqueFamilles as $fam): ?>
                        <option value="<?= htmlspecialchars($fam) ?>"><?= htmlspecialchars($fam) ?>s</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (($page['filter_genre'] ?? 1) && !empty($uniqueGenres)): ?>
                <div class="filter-group">
                    <label>Genre</label>
                    <label for="filterGenre" class="sr-only">Filtrer par genre</label>
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
                    <label>Trier par</label>
                    <label for="sortProducts" class="sr-only">Trier les produits</label>
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

            <!-- Products Count -->
            <div class="products-count">
                <span id="productsCount"><?= $productCount ?> produit<?= $productCount > 1 ? 's' : '' ?></span>
            </div>

            <!-- Products Grid -->
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
                <div class="product-card" data-famille="<?= htmlspecialchars($prod['famille'] ?? '') ?>" data-genre="<?= htmlspecialchars($prod['genre'] ?? '') ?>" data-price="<?= floatval($prod['prix_500'] ?? 0) ?>" data-name="<?= htmlspecialchars($prodName) ?>">
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
                <p style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">Aucun produit dans ce sport pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Why Us Section -->
    <section class="why-us-section" id="why-us">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow"><?= htmlspecialchars($page['why_eyebrow'] ?: 'Nos engagements') ?></div>
                <h2 class="section-title"><?= htmlspecialchars($page['why_title'] ?: 'Pourquoi choisir Flare Custom') ?></h2>
                <p class="section-desc"><?= htmlspecialchars($page['why_subtitle'] ?: 'La référence européenne en équipements sportifs personnalisés') ?></p>
            </div>

            <div class="why-us-grid-redesign">
                <?php $num = 1; foreach ($whyItems as $item): ?>
                <div class="why-us-card-redesign">
                    <div class="why-us-number">0<?= $num++ ?></div>
                    <div class="why-us-icon-redesign">
                        <?php if (!empty($item['icon'])): ?>
                        <?= $item['icon'] ?>
                        <?php else: ?>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['description'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="contact">
        <div class="cta-container">
            <div class="cta-content">
                <h2 class="cta-title"><?= nl2br(htmlspecialchars($page['cta_title'] ?: "Équipez votre club\nde $sportNameLower")) ?></h2>
                <p class="cta-text">
                    <?= htmlspecialchars($page['cta_subtitle'] ?: 'Devis gratuit sous 24h • Designer dédié • Prix dégressifs • Livraison 3-4 semaines') ?>
                </p>
                <div class="cta-buttons">
                    <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/contact.html') ?>" class="btn-cta-primary">
                        <?= htmlspecialchars($page['cta_button_text'] ?: "Demander un devis $sportNameLower") ?>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <?php if (!empty($page['cta_whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $page['cta_whatsapp']) ?>" class="btn-cta-secondary">
                        <?= htmlspecialchars($page['cta_whatsapp']) ?>
                    </a>
                    <?php else: ?>
                    <a href="https://wa.me/359885813134" class="btn-cta-secondary">
                        +33 1 23 45 67 89
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Sport-Specific Section -->
    <section class="faq-sport-section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow"><?= htmlspecialchars($page['faq_eyebrow'] ?: 'Questions fréquentes') ?></div>
                <h2 class="section-title"><?= htmlspecialchars($page['faq_title'] ?: "FAQ $sportName") ?></h2>
                <p class="section-description">
                    <?= htmlspecialchars($page['faq_description'] ?: "Toutes les réponses à vos questions sur nos équipements $sportNameLower personnalisés.") ?>
                </p>
            </div>

            <div class="faq-grid">
                <?php foreach ($faqItems as $faq): ?>
                <?php if (!empty($faq['question'])): ?>
                <div class="faq-item">
                    <div class="faq-question"><?= htmlspecialchars($faq['question']) ?></div>
                    <div class="faq-answer">
                        <p><?= $faq['answer'] ?? '' ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="faq-cta">
                <h3>Besoin de plus d'informations ?</h3>
                <p>Consultez notre FAQ complète ou contactez-nous directement pour un conseil personnalisé.</p>
                <div class="faq-cta-buttons">
                    <a href="/#faq" class="btn-primary">
                        Voir toutes les FAQ
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="/pages/info/contact.html" class="btn-secondary">
                        Contactez-nous
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php
    // ========== SECTIONS SEO (tout vient de la BDD) ==========
    foreach ($seoSections as $sec):
        if (!empty($sec['title']) || !empty($sec['blocks'])):
    ?>
    <section class="seo-footer-section">
        <div class="container">
            <div class="section-header">
                <?php if (!empty($sec['eyebrow'])): ?>
                <div class="section-eyebrow"><?= htmlspecialchars($sec['eyebrow']) ?></div>
                <?php endif; ?>
                <?php if (!empty($sec['title'])): ?>
                <h2 class="section-title"><?= htmlspecialchars($sec['title']) ?></h2>
                <?php endif; ?>
            </div>

            <?php if (!empty($sec['blocks'])): ?>
            <div class="seo-content-grid">
                <?php foreach ($sec['blocks'] as $block): ?>
                <div class="seo-content-block">
                    <?php if (!empty($block['title'])): ?>
                    <h3><?= htmlspecialchars($block['title']) ?></h3>
                    <?php endif; ?>
                    <?= $block['content'] ?? '' ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($sec['keywords'])): ?>
            <div class="seo-keywords">
                <h4><?= htmlspecialchars($sec['keywords_title'] ?: 'Mots-clés') ?></h4>
                <p><?= htmlspecialchars($sec['keywords']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
        endif;
    endforeach;
    ?>

    <!-- 🔥 FOOTER DYNAMIQUE -->
    <div id="dynamic-footer"></div>

    <!-- Components Loader (Header/Footer + Interactions) -->
    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>
    <script src="/assets/js/product-cards-linker.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterFamily = document.getElementById('filterFamily');
            const filterGenre = document.getElementById('filterGenre');
            const sortProducts = document.getElementById('sortProducts');
            const productsGrid = document.getElementById('productsGrid');
            const productsCount = document.getElementById('productsCount');

            function filterAndSortProducts() {
                if (!productsGrid) return;
                const cards = Array.from(productsGrid.querySelectorAll('.product-card'));
                let visibleCount = 0;

                cards.forEach(card => {
                    const famille = card.dataset.famille || '';
                    const genre = card.dataset.genre || '';
                    let show = true;

                    if (filterFamily && filterFamily.value && !famille.includes(filterFamily.value)) show = false;
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

            if (filterFamily) filterFamily.addEventListener('change', filterAndSortProducts);
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

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
