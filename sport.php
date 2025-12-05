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

    // Charger les produits associés
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?> | <?= $siteName ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
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
            <?php if (!empty($page['hero_eyebrow'])): ?>
            <span class="hero-sport-eyebrow"><?= htmlspecialchars($page['hero_eyebrow']) ?></span>
            <?php endif; ?>
            <h1 class="hero-sport-title"><?= htmlspecialchars($page['hero_title'] ?: $page['title']) ?></h1>
            <?php if (!empty($page['hero_subtitle'])): ?>
            <p class="hero-sport-subtitle"><?= htmlspecialchars($page['hero_subtitle']) ?></p>
            <?php endif; ?>
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

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <?php if (!empty($page['products_eyebrow'])): ?>
                <div class="section-eyebrow"><?= htmlspecialchars($page['products_eyebrow']) ?></div>
                <?php endif; ?>
                <h2 class="section-title"><?= htmlspecialchars($page['products_title'] ?: 'Nos équipements ' . $sportNameLower) ?></h2>
                <p class="section-description">
                    <?= htmlspecialchars($page['products_description'] ?: "Plus de $productCount modèles disponibles. Tissus techniques respirants, coutures renforcées, personnalisation illimitée en sublimation.") ?>
                </p>
            </div>

            <!-- Filters -->
            <?php if ($page['show_filters'] && (!empty($uniqueFamilles) || !empty($uniqueGenres))): ?>
            <div class="filters-bar">
                <?php if (!empty($uniqueFamilles)): ?>
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

                <?php if (!empty($uniqueGenres)): ?>
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
    <?php if (!empty($page['why_title']) || !empty($whyItems)): ?>
    <section class="why-us-section" id="why-us">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Nos engagements</div>
                <h2 class="section-title"><?= htmlspecialchars($page['why_title'] ?: 'Pourquoi choisir Flare Custom') ?></h2>
                <?php if (!empty($page['why_subtitle'])): ?>
                <p class="section-desc"><?= htmlspecialchars($page['why_subtitle']) ?></p>
                <?php else: ?>
                <p class="section-desc">La référence européenne en équipements sportifs personnalisés</p>
                <?php endif; ?>
            </div>

            <div class="why-us-grid-redesign">
                <?php
                $defaultWhyItems = [
                    ['icon' => '⭐', 'title' => 'Design 100% personnalisé', 'description' => "Aucune limite de couleurs, motifs ou logos. Notre équipe de designers professionnels vous accompagne gratuitement pour créer un design unique."],
                    ['icon' => '✅', 'title' => 'Fabrication européenne certifiée', 'description' => "Production dans nos ateliers partenaires certifiés en Europe. Tissus techniques haute performance testés et approuvés."],
                    ['icon' => '⚡', 'title' => 'Livraison rapide garantie', 'description' => "Délai standard 3-4 semaines, option express 10-15 jours disponible. Livraison suivie dans toute l'Europe."],
                    ['icon' => 'ℹ️', 'title' => 'Accompagnement expert complet', 'description' => "Service client dédié du devis à la livraison. BAT détaillé pour validation avant production."],
                    ['icon' => '💰', 'title' => 'Prix dégressifs ultra-compétitifs', 'description' => "Tarifs agressifs dès 1 pièce. Prix dégressifs jusqu'à -60% selon la quantité. Pas de frais cachés."],
                    ['icon' => '🎨', 'title' => 'Sublimation durable premium', 'description' => "Technique de sublimation intégrale garantissant des couleurs éclatantes qui ne se délavent jamais."]
                ];
                $displayWhyItems = !empty($whyItems) ? $whyItems : $defaultWhyItems;
                $num = 1;
                foreach ($displayWhyItems as $item):
                ?>
                <div class="why-us-card-redesign">
                    <div class="why-us-number">0<?= $num++ ?></div>
                    <div class="why-us-icon-redesign">
                        <?= $item['icon'] ?? '✓' ?>
                    </div>
                    <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                    <p><?= htmlspecialchars($item['description'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <?php if (!empty($page['cta_title'])): ?>
    <section class="cta-section" id="contact">
        <div class="cta-container">
            <div class="cta-content">
                <h2 class="cta-title"><?= nl2br(htmlspecialchars($page['cta_title'])) ?></h2>
                <?php if (!empty($page['cta_subtitle'])): ?>
                <p class="cta-text"><?= htmlspecialchars($page['cta_subtitle']) ?></p>
                <?php else: ?>
                <p class="cta-text">Devis gratuit sous 24h • Designer dédié • Prix dégressifs • Livraison 3-4 semaines</p>
                <?php endif; ?>
                <div class="cta-buttons">
                    <a href="<?= htmlspecialchars($page['cta_button_link'] ?: '/pages/info/contact.html') ?>" class="btn-cta-primary">
                        <?= htmlspecialchars($page['cta_button_text'] ?: 'Demander un devis ' . $sportNameLower) ?>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <?php if (!empty($page['cta_whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $page['cta_whatsapp']) ?>" class="btn-cta-secondary">
                        <?= htmlspecialchars($page['cta_whatsapp']) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ Sport-Specific Section -->
    <?php if (!empty($faqItems) && !empty(array_filter($faqItems, fn($f) => !empty($f['question'])))): ?>
    <section class="faq-sport-section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Questions fréquentes</div>
                <h2 class="section-title"><?= htmlspecialchars($page['faq_title'] ?: 'FAQ ' . $sportName) ?></h2>
                <p class="section-description">
                    Toutes les réponses à vos questions sur nos équipements <?= htmlspecialchars($sportNameLower) ?> personnalisés.
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
    <?php endif; ?>

    <!-- SEO Footer Sections -->
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

    <!-- 🔥 FOOTER DYNAMIQUE -->
    <div id="dynamic-footer"></div>

    <!-- Components Loader (Header/Footer + Interactions) -->
    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>
    <script src="/assets/js/product-cards-linker.js" defer></script>

    <script>
        // Filters for products
        document.addEventListener('DOMContentLoaded', function() {
            const filterFamily = document.getElementById('filterFamily');
            const filterGenre = document.getElementById('filterGenre');
            const sortProducts = document.getElementById('sortProducts');
            const productsGrid = document.getElementById('productsGrid');
            const productsCount = document.getElementById('productsCount');

            function filterAndSortProducts() {
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

                // Sort
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
        });
    </script>
</body>
</html>
