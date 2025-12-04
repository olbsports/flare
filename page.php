<?php
/**
 * PAGE DYNAMIQUE - FLARE CUSTOM
 *
 * Charge les pages info, catégories produits depuis la base de données
 * Réplique exactement la structure HTML des pages statiques
 *
 * URLs:
 * - /info/contact → page.php?slug=contact&type=info
 * - /categorie/maillots-football → page.php?slug=maillots-football&type=category
 */

require_once __DIR__ . '/config/database.php';

// Récupération des paramètres
$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? 'info';
$debug = isset($_GET['debug']);

if (empty($slug)) {
    http_response_code(404);
    die("Page non trouvée - slug manquant");
}

try {
    $pdo = getConnection();

    // Charger la page depuis la BDD
    $stmt = $pdo->prepare("
        SELECT * FROM pages
        WHERE slug = ?
        AND status = 'published'
    ");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        // Fallback: chercher sans le préfixe si type=info
        if ($type === 'info') {
            // Essayer avec différentes variations du slug
            $variations = [
                $slug,
                str_replace('-', '_', $slug),
                'info-' . $slug,
                'page-' . $slug
            ];

            foreach ($variations as $variation) {
                $stmt->execute([$variation]);
                $page = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($page) break;
            }
        }

        // Si toujours pas trouvé, afficher 404
        if (!$page) {
            http_response_code(404);
            if ($debug) {
                echo "<h1>Page non trouvée</h1>";
                echo "<p>Slug recherché: " . htmlspecialchars($slug) . "</p>";
                echo "<p>Type: " . htmlspecialchars($type) . "</p>";
                echo "<h3>Pages disponibles dans la BDD:</h3>";
                $all = $pdo->query("SELECT id, slug, title, status FROM pages ORDER BY slug")->fetchAll();
                echo "<pre>" . print_r($all, true) . "</pre>";
                exit;
            }
            die("Page non trouvée");
        }
    }

    // Récupérer le contenu
    $title = $page['title'] ?? 'Page';
    $content = $page['content'] ?? '';
    $metaTitle = $page['meta_title'] ?? $title . ' | FLARE CUSTOM';
    $metaDescription = $page['meta_description'] ?? '';
    $metaKeywords = $page['meta_keywords'] ?? '';
    $template = $page['template'] ?? 'default';

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        die("Erreur: " . $e->getMessage());
    }
    die("Erreur de chargement de la page");
}

// Déterminer le type de hero selon le template ou le slug
$heroClass = 'hero-page';
if (strpos($slug, 'contact') !== false) {
    $heroClass = 'hero-contact';
} elseif (strpos($slug, 'faq') !== false) {
    $heroClass = 'hero-faq';
} elseif (strpos($slug, 'livraison') !== false) {
    $heroClass = 'hero-livraison';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($metaTitle); ?></title>
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">

    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <?php if ($metaKeywords): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.flare-custom.com/<?php echo $type; ?>/<?php echo htmlspecialchars($slug); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.flare-custom.com/<?php echo $type; ?>/<?php echo htmlspecialchars($slug); ?>">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "<?php echo htmlspecialchars($title); ?>",
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "url": "https://www.flare-custom.com/<?php echo $type; ?>/<?php echo htmlspecialchars($slug); ?>",
        "publisher": {
            "@type": "Organization",
            "name": "FLARE CUSTOM",
            "logo": "https://www.flare-custom.com/assets/images/logo.png"
        }
    }
    </script>

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/style-sport.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Bebas+Neue&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Bebas+Neue&display=swap"></noscript>

    <style>
        /* Hero universel */
        .hero-page {
            position: relative;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 5% 80px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
            overflow: hidden;
        }

        .hero-page-content {
            max-width: 900px;
            margin: 0 auto 2em;
            position: relative;
            z-index: 1;
        }

        .hero-page-eyebrow {
            display: inline-block;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #FF4B26;
            margin-bottom: 20px;
        }

        .hero-page-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 64px;
            font-weight: 700;
            letter-spacing: 2px;
            line-height: 1.1;
            margin-bottom: 24px;
            color: #1a1a1a;
        }

        .hero-page-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            line-height: 1.6;
            color: #495057;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Contenu principal */
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 5%;
        }

        .page-content h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 42px;
            color: #1a1a1a;
            margin: 50px 0 25px;
            letter-spacing: 1.5px;
        }

        .page-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 40px 0 20px;
        }

        .page-content p {
            font-size: 18px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 25px;
        }

        .page-content ul, .page-content ol {
            margin: 25px 0;
            padding-left: 30px;
        }

        .page-content li {
            font-size: 18px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 12px;
        }

        .page-content a {
            color: #FF4B26;
            text-decoration: underline;
        }

        .page-content strong {
            color: #FF4B26;
            font-weight: 700;
        }

        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 30px 0;
        }

        .page-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .page-content th, .page-content td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .page-content th {
            background: #FF4B26;
            color: white;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-page {
                min-height: auto;
                padding: 60px 5% 50px;
            }

            .hero-page-title {
                font-size: 42px;
            }

            .page-content {
                padding: 50px 5%;
            }

            .page-content h2 {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Dynamique -->
    <div id="dynamic-header"></div>

    <!-- Hero Section -->
    <section class="<?php echo $heroClass; ?>">
        <div class="hero-page-content">
            <span class="hero-page-eyebrow">FLARE CUSTOM</span>
            <h1 class="hero-page-title"><?php echo htmlspecialchars($title); ?></h1>
            <?php if ($metaDescription): ?>
            <p class="hero-page-subtitle"><?php echo htmlspecialchars($metaDescription); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contenu Principal -->
    <main class="page-content">
        <?php
        // Afficher le contenu HTML (depuis le WYSIWYG)
        echo $content;
        ?>
    </main>

    <!-- CTA Section -->
    <section class="cta-redesign">
        <div class="cta-redesign-container">
            <h2 class="cta-redesign-title">Besoin d'équipements personnalisés ?</h2>
            <p class="cta-redesign-subtitle">Devis gratuit sous 24h • Fabrication européenne • Livraison rapide</p>
            <div class="cta-redesign-buttons">
                <a href="/info/contact" class="btn-cta-main">
                    <span>Demander un devis gratuit</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer Dynamique -->
    <div id="dynamic-footer"></div>

    <!-- Scripts -->
    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>

    <?php if ($debug): ?>
    <div style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ddd;border-radius:8px;">
        <h3>Debug Info</h3>
        <pre><?php print_r($page); ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>
