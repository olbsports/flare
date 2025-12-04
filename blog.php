<?php
/**
 * ARTICLE DE BLOG DYNAMIQUE - FLARE CUSTOM
 *
 * Sert les articles de blog avec le design original
 * Priorise les fichiers HTML statiques originaux
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

    // Essayer d'abord le fichier HTML statique original
    $staticFile = __DIR__ . '/pages/blog/' . $slug . '.html';
    if (file_exists($staticFile)) {
        $content = file_get_contents($staticFile);
        $content = fixBlogUrls($content);
        echo $content;
        exit;
    }

    // Sinon, charger depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        http_response_code(404);
        if ($debug) {
            echo "<h1>Article non trouvé</h1>";
            echo "<p>Slug: " . htmlspecialchars($slug) . "</p>";
            echo "<p>Fichier statique cherché: " . htmlspecialchars($staticFile) . "</p>";
            exit;
        }
        include __DIR__ . '/pages/404.html';
        exit;
    }

    // Incrémenter le compteur de vues
    $pdo->prepare("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = ?")->execute([$article['id']]);

    // Servir le contenu HTML
    $content = $article['content'] ?? '';
    $content = fixBlogUrls($content);
    echo $content;

} catch (Exception $e) {
    http_response_code(500);
    if ($debug) {
        echo "Erreur: " . $e->getMessage();
    } else {
        echo "Erreur de chargement";
    }
}

/**
 * Corriger les URLs relatives dans le HTML du blog
 */
function fixBlogUrls($content) {
    // Corriger les liens produits
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/produits\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/produit/$1"', $content);

    // Corriger les liens catégories
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/products\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/categorie/$1"', $content);

    // Corriger les liens info
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/info\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/info/$1"', $content);

    // Corriger les liens blog
    $content = preg_replace('/href=["\'](?:\.\.\/)*pages\/blog\/([A-Za-z0-9_-]+)\.html["\']/i', 'href="/blog/$1"', $content);

    // Corriger les chemins assets
    $content = preg_replace('/(?:\.\.\/)+assets\//i', '/assets/', $content);

    return $content;
}
