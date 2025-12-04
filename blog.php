<?php
/**
 * ARTICLE DE BLOG DYNAMIQUE - FLARE CUSTOM
 *
 * Sert le HTML complet stocké en BDD (conserve le design original de chaque article)
 *
 * URL: /blog/mon-article → blog.php?slug=mon-article
 */

require_once __DIR__ . '/config/database.php';

// Récupération des paramètres
$slug = $_GET['slug'] ?? '';
$debug = isset($_GET['debug']);

if (empty($slug)) {
    http_response_code(404);
    include __DIR__ . '/pages/404.html';
    exit;
}

try {
    $pdo = getConnection();

    // Charger l'article depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        // Essayer avec des variations du slug
        $variations = [$slug, str_replace('-', '_', $slug)];
        foreach ($variations as $variation) {
            $stmt->execute([$variation]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($article) break;
        }
    }

    // Si toujours pas trouvé
    if (!$article) {
        http_response_code(404);
        if ($debug) {
            echo "<h1>Article non trouvé</h1>";
            echo "<p>Slug: " . htmlspecialchars($slug) . "</p>";
            echo "<h3>Articles disponibles:</h3><pre>";
            $all = $pdo->query("SELECT slug, title FROM blog_posts ORDER BY slug")->fetchAll();
            print_r($all);
            echo "</pre>";
            exit;
        }
        // Essayer de servir le fichier HTML statique original
        $staticFile = __DIR__ . '/pages/blog/' . $slug . '.html';
        if (file_exists($staticFile)) {
            readfile($staticFile);
            exit;
        }
        include __DIR__ . '/pages/404.html';
        exit;
    }

    // Incrémenter le compteur de vues
    $pdo->prepare("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = ?")->execute([$article['id']]);

    // Servir le contenu HTML complet
    echo $article['content'] ?? '';

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        echo "Erreur: " . $e->getMessage();
    } else {
        echo "Erreur de chargement";
    }
}
