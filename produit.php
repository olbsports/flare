<?php
/**
 * PAGE PRODUIT DYNAMIQUE - FLARE CUSTOM
 * Structure IDENTIQUE aux pages HTML statiques, données depuis BDD
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
        die("Produit non trouvé: " . htmlspecialchars($reference));
    }

    // Charger le guide des tailles si défini
    $sizeChart = null;
    if (!empty($product['size_chart_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM size_charts WHERE id = ? AND active = 1");
        $stmt->execute([$product['size_chart_id']]);
        $sizeChart = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Charger les paramètres du site
    $siteName = getSetting('site_name', 'FLARE CUSTOM');
    $siteUrl = getSetting('site_url', 'https://flare-custom.com');

} catch (Exception $e) {
    http_response_code(500);
    if (isset($_GET['debug'])) {
        die("Erreur: " . $e->getMessage());
    }
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        die("<h2>Base de données non initialisée</h2><p>Allez sur <a href='/admin/import-content.php'>/admin/import-content.php</a> et cliquez 'TOUT IMPORTER D'UN COUP'</p>");
    }
    die("Erreur de chargement - <a href='?ref=" . htmlspecialchars($reference) . "&debug=1'>Voir détails</a>");
}

// Construire les URLs des photos (priorité: product_photos table, puis photo_1-5, puis fallback)
$photos = [];

// D'abord essayer de charger depuis la table product_photos
try {
    // Utilise type='main' pour la photo principale (pas is_main)
    $stmtPhotos = $pdo->prepare("SELECT url FROM product_photos WHERE product_id = ? AND active = 1 ORDER BY CASE WHEN type='main' THEN 0 ELSE 1 END, ordre ASC, id ASC");
    $stmtPhotos->execute([$product['id']]);
    $dbPhotos = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($dbPhotos)) {
        $photos = $dbPhotos;
    }
} catch (Exception $e) {
    // Table peut ne pas exister, ou erreur - ignorer silencieusement
}

// Si pas de photos depuis product_photos, utiliser les champs photo_1-5
if (empty($photos)) {
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($product["photo_$i"])) {
            $photos[] = $product["photo_$i"];
        }
    }
}

// Si toujours pas de photos, utiliser le pattern par défaut
if (empty($photos)) {
    for ($i = 1; $i <= 5; $i++) {
        $photos[] = "https://flare-custom.com/photos/produits/{$reference}-{$i}.webp";
    }
}

// Calculer les prix
$priceData = [];
$quantities = [1, 5, 10, 20, 50, 100, 250, 500];
$priceColumns = ['prix_1', 'prix_5', 'prix_10', 'prix_20', 'prix_50', 'prix_100', 'prix_250', 'prix_500'];

foreach ($quantities as $i => $qty) {
    $col = $priceColumns[$i];
    if (!empty($product[$col]) && floatval($product[$col]) > 0) {
        $priceData[] = ['qty' => $qty, 'price' => floatval($product[$col])];
    }
}

$priceLow = !empty($priceData) ? min(array_column($priceData, 'price')) : 0;
$priceHigh = !empty($priceData) ? max(array_column($priceData, 'price')) : 0;

// Variables pour le template
$nom = $product['nom'] ?? '';
$nomUpper = strtoupper($nom);
$sport = $product['sport'] ?? '';
$sportLower = strtolower($sport);
$famille = $product['famille'] ?? '';
$tissu = $product['tissu'] ?? '';
$grammage = $product['grammage'] ?? '';
$genre = $product['genre'] ?? 'Mixte';
$finition = $product['finition'] ?? '';
$description = $product['description'] ?? '';
$descriptionSeo = $product['description_seo'] ?? $description;
$metaTitle = $product['meta_title'] ?? $nom;
$metaDescription = $product['meta_description'] ?? $descriptionSeo;

// Nouveaux champs pour affichage frontend
$stockStatus = $product['stock_status'] ?? 'in_stock';
$etiquettes = array_filter(array_map('trim', explode(',', $product['etiquettes'] ?? '')));
$isNew = !empty($product['is_new']);
$onSale = !empty($product['on_sale']);
$relatedProductIds = json_decode($product['related_products'] ?? '[]', true) ?: [];

// Charger les produits liés (ou produits par défaut si aucun sélectionné)
$relatedProducts = [];
if (!empty($relatedProductIds)) {
    // Produits sélectionnés manuellement dans l'admin
    $placeholders = implode(',', array_fill(0, count($relatedProductIds), '?'));
    $stmt = $pdo->prepare("SELECT id, reference, nom, meta_title, photo_1, prix_500, sport, famille FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($relatedProductIds);
    $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Charger des produits par défaut basés sur le sport et la famille du produit
    // Stratégie: produits complémentaires de la même catégorie sportive
    $defaultRelatedQuery = "
        SELECT id, reference, nom, meta_title, photo_1, prix_500, sport, famille
        FROM products
        WHERE active = 1
        AND id != ?
        AND sport = ?
        AND famille != ?
        ORDER BY RAND()
        LIMIT 4
    ";
    $stmt = $pdo->prepare($defaultRelatedQuery);
    $stmt->execute([$product['id'], $sport, $famille]);
    $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si pas assez de produits du même sport, prendre d'autres produits
    if (count($relatedProducts) < 4) {
        $excludeIds = array_column($relatedProducts, 'id');
        $excludeIds[] = $product['id'];
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $remaining = 4 - count($relatedProducts);

        $moreQuery = "
            SELECT id, reference, nom, meta_title, photo_1, prix_500, sport, famille
            FROM products
            WHERE active = 1
            AND id NOT IN ($placeholders)
            ORDER BY RAND()
            LIMIT $remaining
        ";
        $stmtMore = $pdo->prepare($moreQuery);
        $stmtMore->execute($excludeIds);
        $relatedProducts = array_merge($relatedProducts, $stmtMore->fetchAll(PDO::FETCH_ASSOC));
    }
}

// Mapper stock_status vers schema.org
$stockSchemaMap = [
    'in_stock' => 'https://schema.org/InStock',
    'preorder' => 'https://schema.org/PreOrder',
    'out_of_stock' => 'https://schema.org/OutOfStock'
];
$stockSchemaUrl = $stockSchemaMap[$stockStatus] ?? 'https://schema.org/InStock';

// Fonction pour nettoyer le HTML généré par Quill
function cleanWysiwygHtml($html) {
    if (empty($html)) return '';

    // Supprimer les balises vides de Quill
    $html = preg_replace('#<p><br></p>#i', '', $html);
    $html = preg_replace('#<p>\s*</p>#i', '', $html);

    // Supprimer les classes Quill (ql-*)
    $html = preg_replace('#\s*class="ql-[^"]*"#i', '', $html);

    // Supprimer TOUS les styles inline (Quill génère des couleurs et fonts qui cassent le design)
    $html = preg_replace('#\s*style="[^"]*"#i', '', $html);

    // Supprimer les attributs data de Quill
    $html = preg_replace('#\s*data-[a-z\-]+="[^"]*"#i', '', $html);

    // Supprimer les spans vides (Quill les génère souvent)
    $html = preg_replace('#<span>([^<]*)</span>#i', '$1', $html);

    // Nettoyer les attributs vides
    $html = preg_replace('#\s+class=""#i', '', $html);
    $html = preg_replace('#\s+id=""#i', '', $html);

    return trim($html);
}

// Contenu des onglets (depuis BDD ou génération par défaut)
$tabDescription = cleanWysiwygHtml($product['tab_description'] ?? '');
$tabSpecifications = cleanWysiwygHtml($product['tab_specifications'] ?? '');
$tabSizes = cleanWysiwygHtml($product['tab_sizes'] ?? '');
$tabFaq = cleanWysiwygHtml($product['tab_faq'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO META TAGS -->
    <title><?php echo htmlspecialchars($metaTitle); ?> | Dès <?php echo number_format($priceLow, 2, ',', ' '); ?>€ | <?php echo htmlspecialchars($sport); ?> Personnalisé | <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($sportLower); ?>, <?php echo htmlspecialchars(strtolower($famille)); ?>, équipement personnalisé, sublimation, <?php echo htmlspecialchars($tissu); ?>, <?php echo htmlspecialchars($reference); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>">

    <!-- OPEN GRAPH -->
    <meta property="og:type" content="product">
    <meta property="og:title" content="<?php echo htmlspecialchars($nom); ?> | Dès <?php echo number_format($priceLow, 2, ',', ' '); ?>€">
    <meta property="og:description" content="<?php echo htmlspecialchars($descriptionSeo); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($photos[0]); ?>">
    <meta property="og:url" content="<?php echo $siteUrl; ?>/produit/<?php echo $reference; ?>">

    <!-- SCHEMA.ORG JSON-LD - ENRICHI POUR LLM -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "<?php echo addslashes($nom); ?>",
      "description": "<?php echo addslashes($descriptionSeo); ?>",
      "sku": "<?php echo $reference; ?>",
      "mpn": "<?php echo $reference; ?>",
      "brand": {"@type": "Brand", "name": "<?php echo addslashes($siteName); ?>"},
      "category": "<?php echo addslashes($famille . ' ' . $sport); ?>",
      "material": "<?php echo addslashes($tissu); ?>",
      "additionalProperty": [
        {"@type": "PropertyValue", "name": "Sport", "value": "<?php echo addslashes($sport); ?>"},
        {"@type": "PropertyValue", "name": "Technique", "value": "Sublimation intégrale"},
        {"@type": "PropertyValue", "name": "Grammage", "value": "<?php echo addslashes($grammage); ?>"},
        {"@type": "PropertyValue", "name": "Genre", "value": "<?php echo addslashes($genre); ?>"},
        {"@type": "PropertyValue", "name": "Finition", "value": "<?php echo addslashes($finition); ?>"},
        {"@type": "PropertyValue", "name": "Fabrication", "value": "Europe"},
        {"@type": "PropertyValue", "name": "Délai", "value": "3-4 semaines"},
        {"@type": "PropertyValue", "name": "Personnalisation", "value": "Illimitée"}
      ],
      "offers": {
        "@type": "AggregateOffer",
        "priceCurrency": "EUR",
        "lowPrice": "<?php echo $priceLow; ?>",
        "highPrice": "<?php echo $priceHigh; ?>",
        "offerCount": "<?php echo count($priceData); ?>",
        "availability": "<?php echo $stockSchemaUrl; ?>",
        "seller": {
          "@type": "Organization",
          "name": "<?php echo addslashes($siteName); ?>",
          "url": "<?php echo $siteUrl; ?>"
        }
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "127"
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

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">

    <!-- STYLE INLINE (copié de produit.html) -->
    <link rel="stylesheet" href="/assets/css/product-page.css">
    <!-- Configurateur Produit -->
    <link rel="stylesheet" href="/assets/css/configurateur-produit.css">
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
        <a href="/index.html">Accueil</a>
        <span>›</span>
        <a href="/pages/products/equipement-<?php echo $sportLower; ?>-personnalise-sublimation.html"><?php echo htmlspecialchars($sport); ?></a>
        <span>›</span>
        <strong><?php echo htmlspecialchars($famille); ?></strong>
    </nav>

    <!-- HERO PRODUCT -->
    <section class="hero-product">
        <div class="product-grid">
            <!-- GALLERY -->
            <div class="product-gallery" style="position: relative;">
                <!-- BADGES -->
                <?php if ($isNew || $onSale || !empty($etiquettes) || $stockStatus !== 'in_stock'): ?>
                <div class="product-badges" style="position: absolute; top: 15px; left: 15px; z-index: 10; display: flex; flex-direction: column; gap: 8px;">
                    <?php if ($isNew): ?>
                    <span style="background: #22c55e; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Nouveau</span>
                    <?php endif; ?>
                    <?php if ($onSale): ?>
                    <span style="background: #ef4444; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Promo</span>
                    <?php endif; ?>
                    <?php if ($stockStatus === 'preorder'): ?>
                    <span style="background: #f59e0b; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 700;">Précommande</span>
                    <?php elseif ($stockStatus === 'out_of_stock'): ?>
                    <span style="background: #6b7280; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 700;">Rupture</span>
                    <?php endif; ?>
                    <?php foreach ($etiquettes as $tag): ?>
                    <span style="background: #FF4B26; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="main-image" id="mainImage" style="position: relative;">
                    <img src="<?php echo htmlspecialchars($photos[0]); ?>" alt="<?php echo htmlspecialchars($nom); ?>">
                </div>
                <div class="thumbnail-grid">
                    <?php foreach ($photos as $i => $photo): ?>
                    <div class="thumbnail<?php echo $i === 0 ? ' active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($nom); ?> - Photo <?php echo $i + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PRODUCT INFO -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($nomUpper); ?></h1>
                <div class="product-reference" style="font-size: 13px; color: #666; margin-bottom: 12px; font-family: monospace; letter-spacing: 0.5px;">
                    Réf: <?php echo htmlspecialchars($reference); ?>
                </div>

                <?php if (!empty($finition)): ?>
                <div class="product-finitions" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 600; padding: 4px 10px; background: rgba(255, 75, 38, 0.1); color: #FF4B26; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.03em;"><?php echo htmlspecialchars($finition); ?></span>
                </div>
                <?php endif; ?>

                <div class="product-rating">
                    <div class="stars">★★★★★</div>
                    <span class="rating-count">4.8/5 · 127 avis clients</span>
                </div>

                <div class="price-box">
                    <div style="font-size: 14px; color: #666; margin-bottom: 8px;">À partir de</div>
                    <div class="price-current"><?php echo number_format($priceLow, 2, ',', ' '); ?> €</div>
                    <div class="price-range">Prix dégressifs de <?php echo number_format($priceHigh, 2, ',', ' '); ?> € à <?php echo number_format($priceLow, 2, ',', ' '); ?> € / pièce TTC</div>
                    <div class="savings-badge">ÉCONOMISEZ JUSQU'À 60% SUR GRANDES QUANTITÉS</div>
                </div>

                <div class="product-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <strong><?php echo htmlspecialchars($tissu ?: 'Tissu Premium'); ?></strong>
                            <span>Ultra-respirant</span>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                <line x1="4" y1="22" x2="4" y2="15"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <strong>Sublimation Intégrale</strong>
                            <span>Couleurs illimitées</span>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <strong>Délai 3-4 Semaines</strong>
                            <span>Livraison Europe express</span>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <strong>Fabrication Europe</strong>
                            <span>Ateliers certifiés</span>
                        </div>
                    </div>
                </div>

                <div class="cta-buttons">
                    <?php
                    // Charger la config du configurateur (spécifique produit ou défaut)
                    $configuratorConfig = json_decode($product['configurator_config'] ?? '', true);
                    if (empty($configuratorConfig)) {
                        // Charger la config par défaut depuis settings
                        $defaultConfig = [
                            'design_options' => [
                                'flare' => (getSetting('configurator_design_flare', '1') === '1'),
                                'client' => (getSetting('configurator_design_client', '1') === '1'),
                                'template' => (getSetting('configurator_design_template', '1') === '1')
                            ],
                            'personalization' => [
                                'nom' => (getSetting('configurator_perso_nom', '1') === '1'),
                                'numero' => (getSetting('configurator_perso_numero', '1') === '1'),
                                'logo' => (getSetting('configurator_perso_logo', '1') === '1'),
                                'sponsor' => (getSetting('configurator_perso_sponsor', '1') === '1')
                            ],
                            'sizes' => array_map('trim', explode(',', getSetting('configurator_sizes', 'XS,S,M,L,XL,XXL,3XL'))),
                            'sizes_kids' => array_map('trim', explode(',', getSetting('configurator_sizes_kids', '6ans,8ans,10ans,12ans,14ans'))),
                            'collar_options' => array_map('trim', explode(',', getSetting('configurator_collars', 'col_v,col_rond,col_polo'))),
                            'colors_available' => true,
                            'min_quantity' => 1,
                            'delivery_time' => '3-4 semaines'
                        ];
                        $configuratorConfig = $defaultConfig;
                    }
                    ?>
                    <button class="btn-primary" onclick='initConfigurateurProduit(<?php echo json_encode([
                        "reference" => $reference,
                        "nom" => $nom,
                        "sport" => $sport,
                        "famille" => $famille,
                        "photo" => $photos[0],
                        "tissu" => $tissu,
                        "grammage" => $grammage,
                        "prixBase" => $priceLow,
                        "config" => $configuratorConfig
                    ]); ?>)'>CONFIGURER MON DEVIS</button>
                    <a href="#description" class="btn-secondary">EN SAVOIR PLUS</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ZONE D'INJECTION DYNAMIQUE POUR CONTENU PERSONNALISÉ (avant le configurateur) -->
    <div id="configurator-dynamic-content"></div>

    <!-- ZONE D'INJECTION CONFIGURATEUR - Le configurateur sera injecté dynamiquement ici -->
    <div id="configurator-container"></div>

    <!-- PRODUCT TABS - CONTENU ADAPTÉ AU PRODUIT -->
    <section id="description" class="product-tabs">
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="description">Description Complète</button>
            <button class="tab-btn" data-tab="specifications">Caractéristiques</button>
            <button class="tab-btn" data-tab="sizes">Guide des Tailles</button>
            <button class="tab-btn" data-tab="templates">Templates</button>
            <button class="tab-btn" data-tab="faq">Questions Fréquentes</button>
        </div>

        <!-- TAB: DESCRIPTION -->
        <div class="tab-content active" id="tab-description">
            <?php if (!empty($tabDescription)): ?>
                <div class="wysiwyg-content"><?php echo $tabDescription; ?></div>
            <?php else: ?>
            <h2><?php echo htmlspecialchars($nom); ?> - Équipement <?php echo htmlspecialchars($sportLower); ?> personnalisé</h2>

            <p>Le <?php echo htmlspecialchars(strtolower($famille)); ?> <?php echo htmlspecialchars($sportLower); ?> <?php echo htmlspecialchars($grammage); ?> représente l'excellence en matière d'équipement sportif personnalisé. Conçu spécifiquement pour les pratiquants de <?php echo htmlspecialchars($sportLower); ?>, ce produit combine performance technique et personnalisation illimitée par sublimation.</p>

            <h3>Performance et Confort pour le <?php echo htmlspecialchars($sportLower); ?></h3>

            <p>Notre tissu <?php echo htmlspecialchars($tissu); ?> <?php echo htmlspecialchars($grammage); ?> a été spécialement développé pour répondre aux exigences du <?php echo htmlspecialchars($sportLower); ?>. Sa structure technique favorise une circulation d'air optimale pendant l'effort, permettant de rester au sec même lors des entraînements les plus intenses.</p>

            <h3>Sublimation Intégrale : Design Sans Limites</h3>

            <p>La sublimation intégrale intègre les encres directement dans les fibres du tissu. Résultat : votre design fait corps avec le <?php echo htmlspecialchars(strtolower($famille)); ?> et ne se détériorera jamais, même après 50 lavages ou plus. Vous pouvez utiliser autant de couleurs que vous le souhaitez, créer des dégradés complexes, ajouter logos, noms, numéros et sponsors sans limitation.</p>

            <h3>Fabrication Européenne Certifiée</h3>

            <p>Tous nos équipements de <?php echo htmlspecialchars($sportLower); ?> sont fabriqués dans des ateliers certifiés en Europe, garantissant qualité professionnelle et respect de l'environnement. Délai de fabrication : 3-4 semaines. Livraison express Europe en 3-5 jours.</p>

            <!-- CONTENU STRUCTURÉ POUR RÉFÉRENCEMENT LLM -->
            <div class="llm-context" style="padding: 1rem; background: #fafafa; border: 1px solid #f0f0f0; margin: 1.5rem 0; font-size: 0.9rem; color: #666;">
                <details>
                    <summary style="cursor: pointer; font-weight: 600; color: #333; margin-bottom: 0.5rem;">Informations détaillées produit</summary>
                    <div style="line-height: 1.6; margin-top: 0.75rem;">
                        <p><strong>Produit:</strong> <?php echo htmlspecialchars($nom); ?></p>
                        <p><strong>Référence:</strong> <?php echo htmlspecialchars($reference); ?></p>
                        <p><strong>Catégorie:</strong> <?php echo htmlspecialchars($famille . ' ' . $sport); ?> personnalisé</p>
                        <p><strong>Technique:</strong> Sublimation intégrale textile</p>
                        <p><strong>Tissu:</strong> <?php echo htmlspecialchars($tissu); ?> - <?php echo htmlspecialchars($grammage); ?></p>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($genre); ?></p>
                        <p><strong>Fabrication:</strong> Europe - Ateliers certifiés</p>
                        <p><strong>Délai:</strong> 3-4 semaines + livraison express 3-5 jours</p>
                        <p><strong>Minimum:</strong> Aucune quantité minimum (dès 1 pièce)</p>
                        <p><strong>Prix indicatif:</strong> À partir de <?php echo number_format($priceLow, 2, ',', ' '); ?>€ l'unité (sur volume)</p>
                        <p><strong>Personnalisation:</strong> Illimitée - logos, noms, numéros, sponsors, dégradés</p>
                        <p><strong>Cas d'usage:</strong> Clubs sportifs, écoles, entreprises, événements, équipes amateurs et professionnelles</p>
                        <p><strong>Avantages:</strong> Durabilité exceptionnelle, design unique, couleurs illimitées, fabrication européenne</p>
                    </div>
                </details>
            </div>
            <?php endif; ?>

            <!-- PRODUITS LIÉS -->
            <?php if (!empty($relatedProducts)): ?>
            <div class="related-products-section">
                <div class="related-products-wrapper">
                    <button class="related-nav related-prev" onclick="scrollRelatedProducts(-1)">‹</button>
                    <div class="related-products-grid">
                    <?php foreach ($relatedProducts as $related):
                        $relatedName = !empty($related['meta_title']) ? $related['meta_title'] : $related['nom'];
                        $relatedPrice = $related['prix_500'] ? number_format($related['prix_500'], 2, ',', ' ') . ' €' : '';
                    ?>
                    <a href="/produit/<?php echo htmlspecialchars($related['reference']); ?>" class="related-product-card">
                        <div class="related-product-image">
                            <img src="<?php echo htmlspecialchars($related['photo_1'] ?: '/photos/placeholder.webp'); ?>" alt="<?php echo htmlspecialchars($relatedName); ?>" loading="lazy">
                            <span class="related-product-badge"><?php echo htmlspecialchars($related['famille']); ?></span>
                        </div>
                        <div class="related-product-info">
                            <div class="related-product-sport"><?php echo htmlspecialchars($related['sport']); ?></div>
                            <div class="related-product-name"><?php echo htmlspecialchars($relatedName); ?></div>
                            <?php if ($relatedPrice): ?>
                            <div class="related-product-price">Dès <?php echo $relatedPrice; ?></div>
                            <?php else: ?>
                            <div class="related-product-price">Demander un devis</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    </div>
                    <button class="related-nav related-next" onclick="scrollRelatedProducts(1)">›</button>
                </div>
            </div>
            <script>
            (function() {
                const grid = document.querySelector('.related-products-grid');
                const prevBtn = document.querySelector('.related-prev');
                const nextBtn = document.querySelector('.related-next');
                if (grid && grid.children.length > 4) {
                    nextBtn.style.opacity = '1';
                    nextBtn.style.pointerEvents = 'auto';
                }
                grid.addEventListener('scroll', function() {
                    prevBtn.style.opacity = grid.scrollLeft > 10 ? '1' : '0.3';
                    prevBtn.style.pointerEvents = grid.scrollLeft > 10 ? 'auto' : 'none';
                    const maxScroll = grid.scrollWidth - grid.clientWidth;
                    nextBtn.style.opacity = grid.scrollLeft < maxScroll - 10 ? '1' : '0.3';
                    nextBtn.style.pointerEvents = grid.scrollLeft < maxScroll - 10 ? 'auto' : 'none';
                });
                window.scrollRelatedProducts = function(dir) {
                    grid.scrollBy({ left: dir * 240, behavior: 'smooth' });
                };
            })();
            </script>
            <?php endif; ?>
        </div>

        <!-- TAB: SPECIFICATIONS -->
        <div class="tab-content" id="tab-specifications">
            <?php if (!empty($tabSpecifications)): ?>
                <div class="wysiwyg-content"><?php echo $tabSpecifications; ?></div>
            <?php else: ?>
            <h2>Fiche Technique Complète</h2>
            <h3>Spécifications Produit</h3>
            <table class="specs-table">
                <tr>
                    <td>Référence produit</td>
                    <td><?php echo htmlspecialchars($reference); ?></td>
                </tr>
                <tr>
                    <td>Sport</td>
                    <td><?php echo htmlspecialchars(strtoupper($sport)); ?></td>
                </tr>
                <tr>
                    <td>Catégorie</td>
                    <td><?php echo htmlspecialchars($famille . ' ' . strtoupper($sport)); ?></td>
                </tr>
                <tr>
                    <td>Matière</td>
                    <td><?php echo htmlspecialchars($tissu ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Grammage</td>
                    <td><?php echo htmlspecialchars($grammage ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Genre</td>
                    <td><?php echo htmlspecialchars($genre); ?></td>
                </tr>
                <tr>
                    <td>Finition</td>
                    <td><?php echo htmlspecialchars($finition ?: 'Standard'); ?></td>
                </tr>
                <tr>
                    <td>Fabrication</td>
                    <td>Ateliers certifiés Europe</td>
                </tr>
                <tr>
                    <td>Délai</td>
                    <td>3-4 semaines + livraison 3-5 jours</td>
                </tr>
                <tr>
                    <td>Quantité minimum</td>
                    <td>Aucune (dès 1 pièce)</td>
                </tr>
            </table>
            <?php endif; ?>
        </div>

        <!-- TAB: SIZE GUIDE -->
        <div class="tab-content" id="tab-sizes">
            <?php if ($sizeChart): ?>
                <h2><?php echo htmlspecialchars($sizeChart['nom']); ?></h2>
                <?php echo $sizeChart['html_content']; ?>
            <?php elseif (!empty($tabSizes)): ?>
                <div class="wysiwyg-content"><?php echo $tabSizes; ?></div>
            <?php else: ?>
            <h2>Guide des Tailles</h2>

            <h3>Tableau des Tailles Adultes</h3>
            <table class="size-table">
                <thead><tr><th>Taille</th><th>Tour de Poitrine</th><th>Longueur</th><th>Largeur</th><th>Manche</th></tr></thead>
                <tbody>
                    <tr><td><strong>XS</strong></td><td>84-90 cm</td><td>68 cm</td><td>44 cm</td><td>20 cm</td></tr>
                    <tr><td><strong>S</strong></td><td>90-96 cm</td><td>70 cm</td><td>46 cm</td><td>21 cm</td></tr>
                    <tr><td><strong>M</strong></td><td>96-102 cm</td><td>72 cm</td><td>48 cm</td><td>22 cm</td></tr>
                    <tr><td><strong>L</strong></td><td>102-108 cm</td><td>74 cm</td><td>50 cm</td><td>23 cm</td></tr>
                    <tr><td><strong>XL</strong></td><td>108-114 cm</td><td>76 cm</td><td>52 cm</td><td>24 cm</td></tr>
                    <tr><td><strong>2XL</strong></td><td>114-120 cm</td><td>78 cm</td><td>54 cm</td><td>25 cm</td></tr>
                    <tr><td><strong>3XL</strong></td><td>120-126 cm</td><td>80 cm</td><td>56 cm</td><td>26 cm</td></tr>
                    <tr><td><strong>4XL</strong></td><td>126-132 cm</td><td>82 cm</td><td>58 cm</td><td>27 cm</td></tr>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- TAB: TEMPLATES -->
        <div class="tab-content" id="tab-templates">
            <h2>Templates de Design</h2>
            <div id="templates-dynamic-content">
                <p style="text-align: center; padding: 60px 20px; color: #666; font-size: 16px;">
                    Nous sommes en train de préparer une bibliothèque de templates personnalisables pour ce produit.<br>
                    Cette section sera bientôt disponible avec de nombreux designs prêts à l'emploi.
                </p>
            </div>
        </div>

        <!-- TAB: FAQ -->
        <div class="tab-content" id="tab-faq">
            <?php if (!empty($tabFaq)): ?>
                <div class="wysiwyg-content"><?php echo $tabFaq; ?></div>
            <?php else: ?>
            <h2>Questions Fréquentes - <?php echo htmlspecialchars($nom); ?></h2>

            <h3>Quelle est la quantité minimum de commande ?</h3>
            <p>Il n'y a aucune quantité minimum. Vous pouvez commander dès 1 seul <?php echo htmlspecialchars(strtolower($famille)); ?> personnalisé pour votre club de <?php echo htmlspecialchars($sportLower); ?>. Notre système de production flexible nous permet de gérer aussi bien les commandes unitaires que les grandes séries de 500 pièces ou plus.</p>

            <h3>Quel est le délai de fabrication pour ce <?php echo htmlspecialchars(strtolower($famille)); ?> <?php echo htmlspecialchars($sportLower); ?> ?</h3>
            <p>Le délai de fabrication est de 3 à 4 semaines après validation de votre design. La livraison express en Europe prend ensuite 3-5 jours ouvrés. Comptez donc 4-5 semaines au total du devis à la réception.</p>

            <h3>La personnalisation est-elle vraiment gratuite ?</h3>
            <p>Oui, 100% gratuit sans aucune restriction. Notre équipe graphique crée ou adapte votre design sans frais supplémentaires, quelle que soit la complexité. Vous pouvez ajouter autant de logos, textes, noms, numéros et sponsors que vous le souhaitez.</p>

            <h3>Les couleurs resteront-elles vives après plusieurs lavages ?</h3>
            <p>Oui ! La sublimation intégrale intègre les encres directement dans les fibres du tissu. Les couleurs font partie du <?php echo htmlspecialchars(strtolower($famille)); ?> et ne peuvent ni se craqueler ni se décoller. Même après 50 lavages ou plus, votre équipement conservera son éclat d'origine.</p>

            <h3>Quelles tailles sont disponibles ?</h3>
            <p>Nous proposons toutes les tailles adultes (XS à 4XL) ainsi que les tailles enfants (6 à 14 ans) pour s'adapter à tous les joueurs. Consultez notre guide des tailles détaillé ci-dessus pour choisir la taille parfaite.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- REVIEWS -->
    <section class="reviews-section">
        <div class="reviews-container">
            <div class="section-header">
                <h2>ILS NOUS FONT CONFIANCE</h2>
                <p>127 avis vérifiés · Note moyenne 4.8/5</p>
            </div>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-stars">★★★★★</div>
                    <div class="review-text">"Excellente qualité, les couleurs sont éclatantes même après plusieurs lavages. Très satisfait du résultat."</div>
                    <div class="review-author">Club <?php echo htmlspecialchars($sport); ?> - J. Martin</div>
                    <div class="review-meta">Commande de 45 pièces · Décembre 2024</div>
                </div>
                <div class="review-card">
                    <div class="review-stars">★★★★★</div>
                    <div class="review-text">"Délais respectés, design parfait, prix compétitifs. Je recommande vivement !"</div>
                    <div class="review-author">Association Sportive - T. Dubois</div>
                    <div class="review-meta">Commande de 45 pièces · Mai 2024</div>
                </div>
                <div class="review-card">
                    <div class="review-stars">★★★★★</div>
                    <div class="review-text">"Le tissu est vraiment respirant et confortable. Rendu professionnel. Merci FLARE CUSTOM !"</div>
                    <div class="review-author">Équipe Locale - M. Dupont</div>
                    <div class="review-meta">Commande de 45 pièces · Septembre 2024</div>
                </div>
            </div>
        </div>
    </section>

    <div id="dynamic-footer"></div>

    <script src="/assets/js/components-loader.js"></script>

    <script>
        // PRICING DATA POUR CE PRODUIT
        const priceTiers = <?php echo json_encode($priceData); ?>;

        // GALLERY
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => {
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                const img = thumb.querySelector('img');
                document.querySelector('#mainImage img').src = img.src;
            });
        });

        // TABS
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = 'tab-' + btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

        function scrollToConfigurator() {
            document.getElementById('configurator-container').scrollIntoView({behavior: 'smooth'});
        }
    </script>
</body>
</html>
