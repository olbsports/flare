<?php
/**
 * PAGE PRODUIT DYNAMIQUE - FLARE CUSTOM
 *
 * Cette page charge les données produit depuis la base de données.
 * Quand tu modifies un produit dans l'admin, ça se met à jour automatiquement ici.
 */

require_once __DIR__ . '/config/database.php';

// Récupérer la référence produit
$reference = $_GET['ref'] ?? '';

if (empty($reference)) {
    http_response_code(404);
    die("Produit non trouvé");
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Charger le produit
    $stmt = $pdo->prepare("SELECT * FROM products WHERE reference = ? AND active = 1");
    $stmt->execute([$reference]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        die("Produit non trouvé");
    }

    // Charger le guide des tailles si défini
    $sizeChart = null;
    if (!empty($product['size_chart_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM size_charts WHERE id = ? AND active = 1");
        $stmt->execute([$product['size_chart_id']]);
        $sizeChart = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Charger les photos supplémentaires
    $photos = [];
    $stmt = $pdo->prepare("SELECT * FROM product_photos WHERE product_id = ? ORDER BY is_main DESC, ordre ASC");
    $stmt->execute([$product['id']]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si pas de photos en BDD, utiliser photo_1 à photo_5
    if (empty($photos)) {
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($product["photo_$i"])) {
                $photos[] = ['url' => $product["photo_$i"], 'is_main' => ($i === 1)];
            }
        }
    }

    // Charger les paramètres du site
    $siteName = getSetting('site_name', 'FLARE CUSTOM');
    $siteUrl = getSetting('site_url', 'https://flare-custom.com');

    // Configuration du configurateur
    $configuratorConfig = [];
    if (!empty($product['configurator_config'])) {
        $configuratorConfig = json_decode($product['configurator_config'], true) ?? [];
    }

} catch (Exception $e) {
    http_response_code(500);
    // Afficher l'erreur en mode debug, sinon message générique
    if (isset($_GET['debug'])) {
        die("Erreur: " . $e->getMessage());
    }
    // Vérifier si c'est un problème de table manquante
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        die("<h2>Base de données non initialisée</h2><p>Allez sur <a href='/admin/import-content.php'>/admin/import-content.php</a> et cliquez 'TOUT IMPORTER D'UN COUP'</p>");
    }
    die("Erreur de chargement - <a href='?ref=" . htmlspecialchars($reference) . "&debug=1'>Voir détails</a>");
}

// Calculer les prix min/max
$priceColumns = ['prix_1', 'prix_5', 'prix_10', 'prix_20', 'prix_50', 'prix_100', 'prix_250', 'prix_500'];
$prices = [];
foreach ($priceColumns as $col) {
    if (!empty($product[$col]) && floatval($product[$col]) > 0) {
        $prices[] = floatval($product[$col]);
    }
}
$priceHigh = !empty($prices) ? max($prices) : 0;
$priceLow = !empty($prices) ? min($prices) : 0;

// Générer le tableau de prix
$priceTable = [];
$quantities = [1 => 'prix_1', 5 => 'prix_5', 10 => 'prix_10', 20 => 'prix_20', 50 => 'prix_50', 100 => 'prix_100', 250 => 'prix_250', 500 => 'prix_500'];
foreach ($quantities as $qty => $col) {
    if (!empty($product[$col]) && floatval($product[$col]) > 0) {
        $priceTable[$qty] = floatval($product[$col]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO META TAGS -->
    <title><?php echo htmlspecialchars($product['meta_title'] ?: $product['nom']); ?> | Dès <?php echo number_format($priceLow, 2); ?>€ | <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description'] ?: $product['description_seo'] ?: $product['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($product['sport']); ?>, <?php echo htmlspecialchars($product['famille']); ?>, équipement personnalisé, sublimation, <?php echo htmlspecialchars($reference); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>">

    <!-- OPEN GRAPH -->
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($product['nom']); ?> | Dès <?php echo number_format($priceLow, 2); ?>€">
    <meta property="og:description" content="<?php echo htmlspecialchars($product['description_seo'] ?: $product['description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($photos[0]['url'] ?? ''); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>">
    <meta property="og:locale" content="fr_FR">
    <meta property="product:price:amount" content="<?php echo $priceLow; ?>">
    <meta property="product:price:currency" content="EUR">

    <!-- TWITTER CARDS -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($product['nom']); ?> | Dès <?php echo number_format($priceLow, 2); ?>€">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($product['description_seo'] ?: $product['description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($photos[0]['url'] ?? ''); ?>">

    <!-- SCHEMA.ORG JSON-LD - ENRICHI POUR SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "<?php echo addslashes($product['nom']); ?>",
      "description": "<?php echo addslashes($product['description_seo'] ?: $product['description']); ?>",
      "sku": "<?php echo $reference; ?>",
      "mpn": "<?php echo $reference; ?>",
      "brand": {"@type": "Brand", "name": "<?php echo addslashes($siteName); ?>"},
      "category": "<?php echo addslashes($product['famille'] . ' ' . $product['sport']); ?>",
      "url": "<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>",
      "image": [<?php echo implode(',', array_map(function($p) { return '"' . addslashes($p['url']) . '"'; }, array_slice($photos, 0, 5))); ?>],
      "material": "<?php echo addslashes($product['tissu']); ?>",
      "additionalProperty": [
        {"@type": "PropertyValue", "name": "Sport", "value": "<?php echo addslashes($product['sport']); ?>"},
        {"@type": "PropertyValue", "name": "Technique", "value": "Sublimation intégrale"},
        {"@type": "PropertyValue", "name": "Grammage", "value": "<?php echo addslashes($product['grammage']); ?>"},
        {"@type": "PropertyValue", "name": "Genre", "value": "<?php echo addslashes($product['genre']); ?>"},
        {"@type": "PropertyValue", "name": "Finition", "value": "<?php echo addslashes($product['finition']); ?>"},
        {"@type": "PropertyValue", "name": "Fabrication", "value": "Europe"},
        {"@type": "PropertyValue", "name": "Délai", "value": "3-4 semaines"}
      ],
      "offers": {
        "@type": "AggregateOffer",
        "priceCurrency": "EUR",
        "lowPrice": "<?php echo $priceLow; ?>",
        "highPrice": "<?php echo $priceHigh; ?>",
        "offerCount": "<?php echo count($priceTable); ?>",
        "availability": "https://schema.org/InStock",
        "seller": {
          "@type": "Organization",
          "name": "<?php echo addslashes($siteName); ?>",
          "url": "<?php echo $siteUrl; ?>"
        },
        "priceValidUntil": "<?php echo date('Y-12-31'); ?>",
        "itemCondition": "https://schema.org/NewCondition"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "127",
        "bestRating": "5",
        "worstRating": "1"
      },
      "manufacturer": {
        "@type": "Organization",
        "name": "<?php echo addslashes($siteName); ?>",
        "address": {
          "@type": "PostalAddress",
          "addressCountry": "FR",
          "addressRegion": "Europe"
        }
      }
    }
    </script>

    <!-- BREADCRUMB SCHEMA -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Accueil", "item": "<?php echo $siteUrl; ?>"},
        {"@type": "ListItem", "position": 2, "name": "<?php echo addslashes($product['sport']); ?>", "item": "<?php echo $siteUrl; ?>/pages/products/equipement-<?php echo strtolower($product['sport']); ?>-personnalise-sublimation.html"},
        {"@type": "ListItem", "position": 3, "name": "<?php echo addslashes($product['nom']); ?>", "item": "<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>"}
      ]
    }
    </script>

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/product-page.css">
    <link rel="stylesheet" href="/assets/css/configurateur-produit.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">
    <script src="/assets/js/configurateur-produit.js" defer></script>
    <script src="/assets/js/templates-display.js" defer></script>
</head>
<body>
    <div id="dynamic-header"></div>

    <!-- TRUST BAR -->
    <div class="trust-bar">
        <div class="trust-container">
            <div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                <span>Fabrication Europe</span>
            </div>
            <div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Devis 24h</span>
            </div>
            <div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span>Garantie 100%</span>
            </div>
            <div class="trust-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Sans Minimum</span>
            </div>
        </div>
    </div>

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="/">Accueil</a>
        <span>›</span>
        <a href="/pages/products/equipement-<?php echo strtolower($product['sport']); ?>-personnalise-sublimation.html"><?php echo htmlspecialchars($product['sport']); ?></a>
        <span>›</span>
        <strong><?php echo htmlspecialchars($product['famille']); ?></strong>
    </nav>

    <!-- HERO PRODUCT -->
    <section class="hero-product">
        <div class="product-grid">
            <!-- GALLERY -->
            <div class="product-gallery">
                <div class="main-image" id="mainImage">
                    <img src="<?php echo htmlspecialchars($photos[0]['url'] ?? '/assets/images/placeholder.webp'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>">
                </div>
                <div class="thumbnail-grid">
                    <?php foreach ($photos as $i => $photo): ?>
                    <div class="thumbnail<?php echo $i === 0 ? ' active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?> - Photo <?php echo $i + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PRODUCT INFO -->
            <div class="product-info">
                <div class="product-badges">
                    <span class="badge badge-sport"><?php echo htmlspecialchars($product['sport']); ?></span>
                    <span class="badge badge-new">Personnalisable</span>
                </div>

                <h1 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h1>
                <p class="product-ref">Réf: <?php echo htmlspecialchars($reference); ?></p>

                <!-- PRIX -->
                <div class="price-section">
                    <div class="price-range">
                        <span class="price-from">À partir de</span>
                        <span class="price-value"><?php echo number_format($priceLow, 2); ?>€</span>
                        <span class="price-unit">/ pièce</span>
                    </div>
                    <p class="price-info">Prix dégressifs selon quantité</p>
                </div>

                <!-- TABLEAU PRIX -->
                <div class="price-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Quantité</th>
                                <?php foreach ($priceTable as $qty => $price): ?>
                                <th><?php echo $qty; ?>+</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Prix unitaire</td>
                                <?php foreach ($priceTable as $qty => $price): ?>
                                <td><?php echo number_format($price, 2); ?>€</td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- CARACTÉRISTIQUES -->
                <div class="product-specs">
                    <?php if (!empty($product['tissu'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Tissu</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['tissu']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['grammage'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Grammage</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['grammage']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['genre'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Genre</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['genre']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['finition'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Finition</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['finition']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- CONFIGURATEUR -->
                <div id="configurateur-produit"
                     data-product-ref="<?php echo htmlspecialchars($reference); ?>"
                     data-product-name="<?php echo htmlspecialchars($product['nom']); ?>"
                     data-product-sport="<?php echo htmlspecialchars($product['sport']); ?>"
                     data-prices='<?php echo json_encode($priceTable); ?>'>
                </div>

                <!-- CTA -->
                <div class="cta-section">
                    <button class="btn-primary btn-quote" onclick="openQuoteForm()">
                        Demander un devis gratuit
                    </button>
                    <p class="cta-info">Réponse sous 24h - Sans engagement</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ONGLETS PRODUIT -->
    <section class="product-tabs">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="description">Description</button>
            <button class="tab-btn" data-tab="specifications">Spécifications</button>
            <button class="tab-btn" data-tab="sizes">Guide des tailles</button>
            <button class="tab-btn" data-tab="templates">Templates</button>
            <button class="tab-btn" data-tab="faq">FAQ</button>
        </div>

        <div class="tabs-content">
            <!-- DESCRIPTION -->
            <div class="tab-panel active" id="tab-description">
                <?php if (!empty($product['tab_description'])): ?>
                    <?php echo $product['tab_description']; ?>
                <?php else: ?>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?: $product['description_seo'])); ?></p>
                <?php endif; ?>
            </div>

            <!-- SPÉCIFICATIONS -->
            <div class="tab-panel" id="tab-specifications">
                <?php if (!empty($product['tab_specifications'])): ?>
                    <?php echo $product['tab_specifications']; ?>
                <?php else: ?>
                    <ul class="specs-list">
                        <li><strong>Sport:</strong> <?php echo htmlspecialchars($product['sport']); ?></li>
                        <li><strong>Famille:</strong> <?php echo htmlspecialchars($product['famille']); ?></li>
                        <?php if (!empty($product['tissu'])): ?><li><strong>Tissu:</strong> <?php echo htmlspecialchars($product['tissu']); ?></li><?php endif; ?>
                        <?php if (!empty($product['grammage'])): ?><li><strong>Grammage:</strong> <?php echo htmlspecialchars($product['grammage']); ?></li><?php endif; ?>
                        <?php if (!empty($product['genre'])): ?><li><strong>Genre:</strong> <?php echo htmlspecialchars($product['genre']); ?></li><?php endif; ?>
                        <?php if (!empty($product['finition'])): ?><li><strong>Finition:</strong> <?php echo htmlspecialchars($product['finition']); ?></li><?php endif; ?>
                        <li><strong>Technique:</strong> Sublimation intégrale</li>
                        <li><strong>Fabrication:</strong> Europe</li>
                        <li><strong>Délai:</strong> 3-4 semaines</li>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- GUIDE DES TAILLES -->
            <div class="tab-panel" id="tab-sizes">
                <?php if ($sizeChart): ?>
                    <h3><?php echo htmlspecialchars($sizeChart['nom']); ?></h3>
                    <?php echo $sizeChart['html_content']; ?>
                <?php elseif (!empty($product['tab_sizes'])): ?>
                    <?php echo $product['tab_sizes']; ?>
                <?php else: ?>
                    <p>Guide des tailles à venir.</p>
                <?php endif; ?>
            </div>

            <!-- TEMPLATES -->
            <div class="tab-panel" id="tab-templates">
                <?php if (!empty($product['tab_templates'])): ?>
                    <?php echo $product['tab_templates']; ?>
                <?php else: ?>
                    <div id="templates-gallery" data-sport="<?php echo htmlspecialchars($product['sport']); ?>">
                        <p>Chargement des templates...</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FAQ -->
            <div class="tab-panel" id="tab-faq">
                <?php if (!empty($product['tab_faq'])): ?>
                    <?php echo $product['tab_faq']; ?>
                <?php else: ?>
                    <div class="faq-list">
                        <div class="faq-item">
                            <h4>Quel est le délai de livraison ?</h4>
                            <p>Nos produits sont fabriqués en Europe avec un délai de 3-4 semaines après validation du BAT.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Y a-t-il un minimum de commande ?</h4>
                            <p>Non, vous pouvez commander à partir d'1 pièce. Les prix sont dégressifs selon la quantité.</p>
                        </div>
                        <div class="faq-item">
                            <h4>Comment fonctionne la personnalisation ?</h4>
                            <p>Vous pouvez nous envoyer votre design ou nous demander de le créer. Nous vous envoyons un BAT pour validation avant production.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div id="dynamic-footer"></div>

    <!-- Scripts -->
    <script src="/assets/js/header-footer.js"></script>
    <script>
        // Gestion des onglets
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
            });
        });

        // Gestion des thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                document.querySelector('#mainImage img').src = thumb.querySelector('img').src;
            });
        });
    </script>
</body>
</html>
