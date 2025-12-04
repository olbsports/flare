<?php
/**
 * ARTICLE DE BLOG DYNAMIQUE - FLARE CUSTOM
 *
 * Charge les articles de blog depuis la base de données
 * Réplique exactement la structure HTML des articles statiques
 *
 * URL: /blog/mon-article → blog.php?slug=mon-article
 */

require_once __DIR__ . '/config/database.php';

// Récupération des paramètres
$slug = $_GET['slug'] ?? '';
$debug = isset($_GET['debug']);

if (empty($slug)) {
    http_response_code(404);
    die("Article non trouvé - slug manquant");
}

try {
    $pdo = getConnection();

    // Charger l'article depuis la BDD
    $stmt = $pdo->prepare("
        SELECT * FROM blog_posts
        WHERE slug = ?
        AND status = 'published'
    ");
    $stmt->execute([$slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        // Essayer avec des variations du slug
        $variations = [
            $slug,
            str_replace('-', '_', $slug),
            'blog-' . $slug
        ];

        foreach ($variations as $variation) {
            $stmt->execute([$variation]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($article) break;
        }

        if (!$article) {
            http_response_code(404);
            if ($debug) {
                echo "<h1>Article non trouvé</h1>";
                echo "<p>Slug recherché: " . htmlspecialchars($slug) . "</p>";
                echo "<h3>Articles disponibles dans la BDD:</h3>";
                $all = $pdo->query("SELECT id, slug, title, status FROM blog_posts ORDER BY slug")->fetchAll();
                echo "<pre>" . print_r($all, true) . "</pre>";
                exit;
            }
            die("Article non trouvé");
        }
    }

    // Récupérer le contenu
    $title = $article['title'] ?? 'Article';
    $content = $article['content'] ?? '';
    $excerpt = $article['excerpt'] ?? '';
    $category = $article['category'] ?? 'Blog';
    $author = $article['author'] ?? 'FLARE CUSTOM';
    $publishedAt = $article['published_at'] ?? date('Y-m-d');
    $featuredImage = $article['featured_image'] ?? '';
    $metaTitle = $article['meta_title'] ?? $title . ' | Blog FLARE CUSTOM';
    $metaDescription = $article['meta_description'] ?? $excerpt;
    $readingTime = ceil(str_word_count(strip_tags($content)) / 200); // 200 mots/min

    // Formatage de la date
    $dateFormatted = date('d F Y', strtotime($publishedAt));
    $months = [
        'January' => 'janvier', 'February' => 'février', 'March' => 'mars',
        'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
        'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
        'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'
    ];
    $dateFormatted = str_replace(array_keys($months), array_values($months), $dateFormatted);

    // Articles liés (même catégorie)
    $stmtRelated = $pdo->prepare("
        SELECT id, title, slug, excerpt, featured_image, published_at
        FROM blog_posts
        WHERE category = ? AND slug != ? AND status = 'published'
        ORDER BY published_at DESC
        LIMIT 3
    ");
    $stmtRelated->execute([$category, $slug]);
    $relatedArticles = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        die("Erreur: " . $e->getMessage());
    }
    die("Erreur de chargement de l'article");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($metaTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://www.flare-custom.com/blog/<?php echo htmlspecialchars($slug); ?>">
    <?php if ($featuredImage): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($featuredImage); ?>">
    <?php endif; ?>
    <meta property="article:published_time" content="<?php echo date('c', strtotime($publishedAt)); ?>">
    <meta property="article:author" content="<?php echo htmlspecialchars($author); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.flare-custom.com/blog/<?php echo htmlspecialchars($slug); ?>">

    <!-- Schema.org Article -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo htmlspecialchars($title); ?>",
        "description": "<?php echo htmlspecialchars($metaDescription); ?>",
        "url": "https://www.flare-custom.com/blog/<?php echo htmlspecialchars($slug); ?>",
        <?php if ($featuredImage): ?>
        "image": "<?php echo htmlspecialchars($featuredImage); ?>",
        <?php endif; ?>
        "datePublished": "<?php echo date('c', strtotime($publishedAt)); ?>",
        "author": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($author); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "FLARE CUSTOM",
            "logo": {
                "@type": "ImageObject",
                "url": "https://www.flare-custom.com/assets/images/logo.png"
            }
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "https://www.flare-custom.com/blog/<?php echo htmlspecialchars($slug); ?>"
        }
    }
    </script>

    <!-- BreadcrumbList Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Accueil",
                "item": "https://www.flare-custom.com/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Blog",
                "item": "https://www.flare-custom.com/blog"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?php echo htmlspecialchars($title); ?>",
                "item": "https://www.flare-custom.com/blog/<?php echo htmlspecialchars($slug); ?>"
            }
        ]
    }
    </script>

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">

    <style>
        /* Hero Article */
        .article-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 160px 40px 100px;
            color: #fff;
        }

        .article-hero-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .article-category {
            display: inline-block;
            background: #FF4B26;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 30px;
        }

        .article-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 72px;
            line-height: 1.1;
            margin-bottom: 35px;
            letter-spacing: 2px;
        }

        .article-meta {
            display: flex;
            gap: 35px;
            font-size: 16px;
            opacity: 0.9;
        }

        .article-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Contenu Article */
        .article-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 100px 40px;
        }

        .article-content h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 42px;
            color: #1a1a2e;
            margin: 60px 0 30px;
            letter-spacing: 1.5px;
        }

        .article-content h3 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
            margin: 50px 0 25px;
        }

        .article-content p {
            font-size: 20px;
            line-height: 2.0;
            color: #333;
            margin-bottom: 30px;
        }

        .article-content ul, .article-content ol {
            margin: 30px 0;
            padding-left: 40px;
        }

        .article-content li {
            font-size: 20px;
            line-height: 2.0;
            color: #333;
            margin-bottom: 18px;
        }

        .article-content strong {
            color: #FF4B26;
            font-weight: 800;
        }

        .article-content a {
            color: #FF4B26;
            font-weight: 700;
            text-decoration: underline;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 40px 0;
        }

        .article-content blockquote {
            border-left: 4px solid #FF4B26;
            padding-left: 30px;
            margin: 40px 0;
            font-style: italic;
            font-size: 22px;
            color: #555;
        }

        /* Featured Image */
        .article-featured-image {
            max-width: 1100px;
            margin: -50px auto 0;
            padding: 0 40px;
            position: relative;
            z-index: 10;
        }

        .article-featured-image img {
            width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        /* Articles liés */
        .related-articles {
            background: #f8f9fa;
            padding: 100px 40px;
        }

        .related-articles-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .related-articles h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 48px;
            text-align: center;
            margin-bottom: 60px;
            letter-spacing: 2px;
        }

        .related-articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .related-article-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .related-article-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }

        .related-article-image {
            height: 200px;
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .related-article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-article-content {
            padding: 30px;
        }

        .related-article-content h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1a1a2e;
            line-height: 1.3;
        }

        .related-article-content p {
            font-size: 16px;
            color: #666;
            line-height: 1.7;
        }

        /* CTA Article */
        .article-cta {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            padding: 80px 40px;
            text-align: center;
            color: #fff;
        }

        .article-cta h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 56px;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }

        .article-cta p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .article-cta .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 40px;
            background: #fff;
            color: #FF4B26;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .article-cta .btn-cta:hover {
            background: #1a1a1a;
            color: #fff;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .article-hero {
                padding: 120px 20px 60px;
            }

            .article-title {
                font-size: 42px;
            }

            .article-meta {
                flex-direction: column;
                gap: 15px;
            }

            .article-content {
                padding: 60px 20px;
            }

            .article-content h2 {
                font-size: 32px;
            }

            .article-content p, .article-content li {
                font-size: 18px;
            }

            .related-articles {
                padding: 60px 20px;
            }

            .article-cta h2 {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Dynamique -->
    <div id="dynamic-header"></div>

    <!-- Hero Article -->
    <section class="article-hero">
        <div class="article-hero-container">
            <span class="article-category"><?php echo htmlspecialchars($category); ?></span>
            <h1 class="article-title"><?php echo htmlspecialchars($title); ?></h1>
            <div class="article-meta">
                <span>📅 <?php echo $dateFormatted; ?></span>
                <span>⏱️ <?php echo $readingTime; ?> min de lecture</span>
                <span>✍️ <?php echo htmlspecialchars($author); ?></span>
            </div>
        </div>
    </section>

    <?php if ($featuredImage): ?>
    <!-- Image à la une -->
    <div class="article-featured-image">
        <img src="<?php echo htmlspecialchars($featuredImage); ?>" alt="<?php echo htmlspecialchars($title); ?>">
    </div>
    <?php endif; ?>

    <!-- Contenu Article -->
    <article class="article-content">
        <?php echo $content; ?>
    </article>

    <!-- Articles liés -->
    <?php if (!empty($relatedArticles)): ?>
    <section class="related-articles">
        <div class="related-articles-container">
            <h2>Articles similaires</h2>
            <div class="related-articles-grid">
                <?php foreach ($relatedArticles as $related): ?>
                <a href="/blog/<?php echo htmlspecialchars($related['slug']); ?>" class="related-article-card">
                    <div class="related-article-image">
                        <?php if (!empty($related['featured_image'])): ?>
                        <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="related-article-content">
                        <h3><?php echo htmlspecialchars($related['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($related['excerpt'] ?? '', 0, 120)); ?>...</p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="article-cta">
        <h2>Envie d'équiper votre club ?</h2>
        <p>Devis gratuit sous 24h • Fabrication européenne • Livraison rapide</p>
        <a href="/info/contact" class="btn-cta">
            <span>Demander un devis gratuit</span>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
            </svg>
        </a>
    </section>

    <!-- Footer Dynamique -->
    <div id="dynamic-footer"></div>

    <!-- Scripts -->
    <script src="/assets/js/components-loader.js" defer></script>
    <script src="/assets/js/script.js" defer></script>

    <?php if ($debug): ?>
    <div style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ddd;border-radius:8px;">
        <h3>Debug Info</h3>
        <pre><?php print_r($article); ?></pre>
    </div>
    <?php endif; ?>
</body>
</html>
