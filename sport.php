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
$sportIcon = $page['sport_icon'] ?? '🏆';

// ============ VALEURS PAR DEFAUT ============

// Trust bar par défaut
if (empty($trustBar)) {
    $trustBar = [
        ['value' => '500+', 'label' => 'Clubs équipés'],
        ['value' => '4.9/5', 'label' => 'Satisfaction client'],
        ['value' => '48h', 'label' => 'Devis sous 48h'],
        ['value' => '100%', 'label' => 'Sublimation française']
    ];
}

// Why items par défaut
if (empty($whyItems)) {
    $whyItems = [
        ['icon' => '⭐', 'title' => 'Design 100% personnalisé', 'description' => "Aucune limite de couleurs, motifs ou logos. Notre équipe de designers professionnels vous accompagne gratuitement pour créer un design unique qui correspond parfaitement à votre identité. Révisions illimitées jusqu'à satisfaction complète."],
        ['icon' => '✅', 'title' => 'Fabrication européenne certifiée', 'description' => "Production dans nos ateliers partenaires certifiés en Europe. Tissus techniques haute performance testés et approuvés. Contrôle qualité rigoureux à chaque étape. Garantie 1 an contre les défauts de fabrication sur tous nos produits."],
        ['icon' => '⚡', 'title' => 'Livraison rapide garantie', 'description' => "Délai standard 3-4 semaines, option express 10-15 jours disponible. Livraison suivie dans toute l'Europe. Nous respectons scrupuleusement nos engagements ou vous êtes remboursé. Emballage soigné et protection optimale."],
        ['icon' => 'ℹ️', 'title' => 'Accompagnement expert complet', 'description' => "Service client dédié du devis à la livraison. BAT (Bon à Tirer) détaillé pour validation avant production. Guide des tailles personnalisé. Conseils techniques gratuits. Suivi en temps réel de votre commande."],
        ['icon' => '💰', 'title' => 'Prix dégressifs ultra-compétitifs', 'description' => "Tarifs agressifs dès 1 pièce. Prix dégressifs jusqu'à -60% selon la quantité. Pas de frais cachés. Devis gratuit et détaillé sous 24h. Facilités de paiement pour les clubs et associations."],
        ['icon' => '🎨', 'title' => 'Sublimation durable premium', 'description' => "Technique de sublimation intégrale garantissant des couleurs éclatantes qui ne se délavent jamais. Impression dans la fibre du tissu pour une durabilité maximale. Résistance aux lavages répétés (50+ cycles testés)."]
    ];
}

// FAQ par défaut
if (empty($faqItems) || empty(array_filter($faqItems, fn($f) => !empty($f['question'])))) {
    $faqItems = [
        ['question' => "Quel est le délai de fabrication pour des équipements $sportNameLower personnalisés ?", 'answer' => "Le délai standard est de 3 à 4 semaines après validation du BAT. Nous proposons également un service express en 10-15 jours pour les commandes urgentes."],
        ['question' => "Puis-je commander des tailles mixtes (adultes et enfants) ?", 'answer' => "Oui, vous pouvez mélanger librement les tailles adultes et enfants dans votre commande. Les prix sont calculés selon le barème correspondant à chaque type."],
        ['question' => "Le flocage des numéros et noms est-il inclus dans le prix ?", 'answer' => "Les numéros classiques sont inclus dans le prix de base. Pour des noms ou numéros personnalisés spécifiques, comptez +2€ par pièce."],
        ['question' => "Quelle est la différence entre les tissus ÉCO et PRO ?", 'answer' => "Les tissus ÉCO (130-160g/m²) offrent un excellent rapport qualité-prix pour l'entraînement. Les tissus PRO sont plus techniques et recommandés pour la compétition."],
        ['question' => "Peut-on ajouter plusieurs logos de sponsors ?", 'answer' => "Oui, vous pouvez intégrer autant de logos que vous le souhaitez sans frais supplémentaires. La sublimation permet un nombre illimité d'éléments graphiques."],
        ['question' => "Les couleurs seront-elles fidèles à notre charte graphique ?", 'answer' => "Oui, nous travaillons avec des codes couleurs Pantone ou RVB pour garantir une reproduction fidèle. Vous recevrez un BAT détaillé pour validation avant production."],
        ['question' => "Les équipements résistent-ils au lavage en machine ?", 'answer' => "Oui, nos équipements passent en machine à 30°C sans problème. Les couleurs restent éclatantes même après des dizaines de lavages."],
        ['question' => "Quelle est la quantité minimum pour bénéficier des prix dégressifs ?", 'answer' => "Les prix dégressifs commencent dès 5 pièces et augmentent par paliers (10, 20, 50, 100, 250, 500). Plus vous commandez, plus le prix unitaire baisse."],
        ['question' => "Fournissez-vous un tableau des tailles détaillé ?", 'answer' => "Oui, nous fournissons un guide des tailles complet avec toutes les mesures en cm pour chaque modèle, disponible avant commande."],
        ['question' => "Proposez-vous des designs spécifiques pour gardiens ?", 'answer' => "Oui, nous créons des équipements gardien avec designs différenciés, couleurs distinctes et options de protections rembourrées."]
    ];
}
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

            <!-- Filters -->
            <?php if ($page['show_filters'] ?? true): ?>
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
                <div class="section-eyebrow">Nos engagements</div>
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
                <div class="section-eyebrow">Questions fréquentes</div>
                <h2 class="section-title"><?= htmlspecialchars($page['faq_title'] ?: "FAQ $sportName") ?></h2>
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

    <!-- SEO Footer Section 1 -->
    <section class="seo-footer-section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow">Expertise <?= htmlspecialchars($sportName) ?></div>
                <h2 class="section-title">FLARE CUSTOM - Équipements <?= htmlspecialchars($sportName) ?> Personnalisés Sublimation</h2>
            </div>

            <div class="seo-content-grid">
                <div class="seo-content-block">
                    <h3>Maillots <?= htmlspecialchars($sportName) ?> Sublimation Haute Qualité</h3>
                    <p>Spécialiste français des <strong>équipements de <?= htmlspecialchars($sportNameLower) ?> personnalisés</strong>, FLARE CUSTOM produit vos <strong>maillots, shorts et kits complets</strong> en sublimation haute définition. Notre technologie garantit des <strong>couleurs éclatantes qui ne s'effacent jamais</strong>, même après des dizaines de lavages.</p>
                    <p>Nous proposons des <strong>tissus techniques respirants</strong> de 130g/m² à 160g/m², parfaitement adaptés à l'intensité du <?= htmlspecialchars($sportNameLower) ?>. Performance Mesh et Reversible Pro offrent <strong>évacuation optimale de la transpiration</strong> et confort maximal pendant les matchs.</p>
                </div>

                <div class="seo-content-block">
                    <h3>Personnalisation illimitée pour Votre Club</h3>
                    <p>Design 100% sur-mesure sans limite de couleurs, logos ou sponsors. Notre <strong>service design gratuit</strong> transforme vos idées en équipements professionnels. <strong>BAT détaillé avant production</strong> pour validation complète.</p>
                    <ul>
                        <li>Numéros classiques inclus (noms/numéros spécifiques +2€/pcs)</li>
                        <li>Tous logos vectorisés acceptés</li>
                        <li>Dégradés et effets complexes</li>
                        <li>Reproduction fidèle de vos couleurs</li>
                        <li>Marquages sponsors illimités</li>
                    </ul>
                </div>

                <div class="seo-content-block">
                    <h3>Prix dégressifs & livraison Europe</h3>
                    <p><strong>Tarifs compétitifs dès 22.90€</strong> avec prix dégressifs selon quantité. Production française dans ateliers certifiés, <strong>livraison Europe entière</strong> sous 3-4 semaines standard ou 10-15 jours en express.</p>
                    <ul>
                        <li>À partir de 1 pièce minimum</li>
                        <li>Garantie 1 an défauts fabrication</li>
                        <li>Devis gratuit sous 24h</li>
                        <li>Tableau de tailles détaillé</li>
                        <li>Support client réactif 7j/7</li>
                    </ul>
                </div>

                <div class="seo-content-block">
                    <h3>Gamme Complète <?= htmlspecialchars($sportName) ?> Club</h3>
                    <p>Équipez entièrement votre club avec notre <strong>catalogue complet</strong> : maillots manches courtes/longues, shorts joueurs, maillots gardien avec protections, kits complets économiques, débardeurs entraînement.</p>
                    <p><strong>Options Homme et Femme</strong> avec coupes anatomiques adaptées. Finitions professionnelles : coutures renforcées, ourlets élastiqués, cordons de serrage, étiquettes personnalisées possibles.</p>
                </div>
            </div>

            <div class="seo-keywords">
                <h4>Recherches populaires <?= htmlspecialchars($sportName) ?></h4>
                <p>Maillot <?= htmlspecialchars($sportNameLower) ?> personnalisé sublimation • Kit <?= htmlspecialchars($sportNameLower) ?> club sur mesure • Equipement <?= htmlspecialchars($sportNameLower) ?> personnalisé pas cher • Tenue <?= htmlspecialchars($sportNameLower) ?> complète personnalisée • Maillot <?= htmlspecialchars($sportNameLower) ?> avec sponsors • Short <?= htmlspecialchars($sportNameLower) ?> personnalisé • Equipement <?= htmlspecialchars($sportNameLower) ?> écologique • Tenue <?= htmlspecialchars($sportNameLower) ?> respirante • Kit <?= htmlspecialchars($sportNameLower) ?> fabrication française • Equipement sportif <?= htmlspecialchars($sportNameLower) ?> club • Maillot <?= htmlspecialchars($sportNameLower) ?> sublimation HD • Tenue <?= htmlspecialchars($sportNameLower) ?> professionnelle club</p>
            </div>
        </div>
    </section>

    <!-- SEO Content Section 2 -->
    <section class="seo-footer-section">
        <div class="container">
            <div class="section-header">
                <div class="section-eyebrow"><?= htmlspecialchars($sportName) ?> Excellence</div>
                <h2 class="section-title"><?= htmlspecialchars($sportName) ?> : L'Excellence de l'Équipement Personnalisé</h2>
            </div>

            <div class="seo-content-grid">
                <div class="seo-content-block">
                    <h3>Des Équipements <?= htmlspecialchars($sportName) ?> Personnalisés de Haute Performance</h3>
                    <p>
                        Chez Flare Custom, nous comprenons que chaque équipe, chaque club, chaque athlète mérite des équipements <?= htmlspecialchars($sportName) ?> qui reflètent leur identité unique et leur niveau d'exigence. C'est pourquoi nous avons développé une expertise pointue dans la conception et la fabrication d'équipements sportifs personnalisés en sublimation intégrale, une technique qui garantit des couleurs éclatantes, une durabilité exceptionnelle et un confort optimal.
                    </p>
                    <p>
                        Notre processus de personnalisation est simple et efficace : vous nous partagez votre vision, nos designers créent le design parfait pour vous, vous validez le BAT (Bon à Tirer), et nous lançons la production dans nos ateliers partenaires certifiés en Europe. Du premier contact à la livraison, nous sommes à vos côtés pour garantir un résultat qui dépasse vos attentes.
                    </p>
                </div>
                <div class="seo-content-block">
                    <h3>Pourquoi la Sublimation pour vos Équipements <?= htmlspecialchars($sportName) ?> ?</h3>
                    <p>
                        La sublimation est une technique d'impression révolutionnaire qui offre des avantages incomparables pour les équipements sportifs. Contrairement aux méthodes traditionnelles de sérigraphie ou de flocage, la sublimation intègre directement les couleurs dans les fibres du tissu. Résultat : des designs complexes avec un nombre illimité de couleurs, des dégradés parfaits, des logos ultra-précis, et tout cela sans aucun surcoût ni limite créative.
                    </p>
                    <p>
                        Vos équipements <?= htmlspecialchars($sportName) ?> sublimés conservent leur souplesse naturelle, leur respirabilité optimale et leur légèreté. Pas de zones rigides, pas de risque de craquelage ou de décollement. Les couleurs restent éclatantes même après des dizaines de lavages en machine. C'est la garantie d'équipements qui durent et qui gardent leur aspect neuf saison après saison.
                    </p>
                </div>
                <div class="seo-content-block">
                    <h3>Une Gamme Complète pour Tous vos Besoins <?= htmlspecialchars($sportName) ?></h3>
                    <p>
                        Notre catalogue <?= htmlspecialchars($sportName) ?> propose une large sélection de produits adaptés à tous les niveaux de pratique : maillots manches courtes et manches longues, shorts et cuissards, débardeurs et tops, vestes et survêtements, accessoires coordonnés. Chaque produit est disponible en version homme, femme et enfant, avec des coupes adaptées (slim, regular, large) et un large choix de tailles (du XS au 4XL).
                    </p>
                    <p>
                        Nous proposons différentes qualités de tissus techniques selon vos besoins et votre budget : notre gamme ÉCO en 130g/m² et 160g/m² offre un excellent rapport qualité-prix pour l'entraînement et les matchs amicaux, tandis que notre gamme PRO avec des tissus plus techniques est idéale pour la compétition de haut niveau. Tous nos tissus sont respirants, évacuent efficacement la transpiration et sèchent rapidement.
                    </p>
                </div>
                <div class="seo-content-block">
                    <h3>Personnalisation illimitée sans Contraintes</h3>
                    <p>
                        Avec Flare Custom, la personnalisation de vos équipements <?= htmlspecialchars($sportName) ?> ne connaît aucune limite. Vous pouvez intégrer autant de couleurs que vous le souhaitez, ajouter tous vos sponsors et partenaires, créer des motifs complexes, des dégradés sophistiqués, des effets graphiques modernes. Noms et numéros des joueurs sont inclus dans le prix de base, sans supplément, et chaque équipement peut être personnalisé individuellement.
                    </p>
                    <p>
                        Vous n'avez pas de maquette ? Aucun problème ! Notre équipe de designers professionnels créera pour vous des propositions graphiques sur mesure, gratuitement. Vous avez déjà votre design ? Parfait, nous l'adaptons et l'optimisons pour la sublimation. Dans tous les cas, vous recevrez un BAT détaillé à valider avant toute production, garantissant un résultat 100% conforme à vos attentes.
                    </p>
                </div>
            </div>

            <div class="seo-keywords">
                <h4>En savoir plus sur <?= htmlspecialchars($sportName) ?></h4>
                <p>Équipement <?= htmlspecialchars($sportNameLower) ?> personnalisé • Maillot <?= htmlspecialchars($sportNameLower) ?> sublimation • Kit <?= htmlspecialchars($sportNameLower) ?> sur mesure • Tenue <?= htmlspecialchars($sportNameLower) ?> club personnalisée • Equipement sportif <?= htmlspecialchars($sportNameLower) ?> • Personnalisation textile <?= htmlspecialchars($sportNameLower) ?> • Maillot <?= htmlspecialchars($sportNameLower) ?> pas cher • Kit complet <?= htmlspecialchars($sportNameLower) ?> personnalisé • Fabrication européenne <?= htmlspecialchars($sportNameLower) ?> • Livraison rapide équipement <?= htmlspecialchars($sportNameLower) ?></p>
            </div>
        </div>
    </section>

    <?php // Sections SEO personnalisées depuis l'admin ?>
    <?php if (!empty($seoSections)): ?>
    <?php foreach ($seoSections as $sec): ?>
    <?php if (!empty($sec['title']) || !empty($sec['content'])): ?>
    <section class="seo-footer-section">
        <div class="container">
            <?php if (!empty($sec['title'])): ?>
            <div class="section-header">
                <h2 class="section-title"><?= htmlspecialchars($sec['title']) ?></h2>
            </div>
            <?php endif; ?>
            <?php if (!empty($sec['content'])): ?>
            <div class="seo-content-grid">
                <div class="seo-content-block" style="grid-column: 1/-1;">
                    <?= $sec['content'] ?>
                </div>
            </div>
            <?php endif; ?>
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
