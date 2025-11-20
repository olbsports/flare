<?php
/**
 * FLARE CUSTOM - Import des articles de blog
 * Ce script importe tous les articles depuis blog-articles.json
 */

require_once __DIR__ . '/config/database.php';

set_time_limit(300); // 5 minutes max

echo "🚀 FLARE CUSTOM - Import des articles de blog\n";
echo "================================================\n\n";

$db = Database::getInstance()->getConnection();
$jsonFile = __DIR__ . '/assets/data/blog-articles.json';

if (!file_exists($jsonFile)) {
    die("❌ Erreur : Le fichier JSON n'existe pas : $jsonFile\n");
}

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (!$data || !isset($data['articles'])) {
    die("❌ Erreur : Le fichier JSON est invalide ou ne contient pas d'articles\n");
}

$articles = $data['articles'];
echo "📚 " . count($articles) . " articles trouvés dans le fichier JSON\n\n";

$imported = 0;
$updated = 0;
$errors = [];

foreach ($articles as $article) {
    try {
        // Vérifier les champs obligatoires
        if (empty($article['slug']) || empty($article['title'])) {
            echo "⏭️  Article ignoré (slug ou titre manquant)\n";
            continue;
        }

        // Construire le contenu HTML de l'article
        $content = "<article class='blog-article'>\n";
        $content .= "  <header>\n";

        if (!empty($article['image'])) {
            $content .= "    <img src='{$article['image']}' alt='{$article['title']}' class='article-image'>\n";
        }

        $content .= "    <h1>{$article['title']}</h1>\n";

        if (!empty($article['description'])) {
            $content .= "    <p class='article-description'>{$article['description']}</p>\n";
        }

        $content .= "    <div class='article-meta'>\n";
        $content .= "      <span class='author'>{$article['author']}</span>\n";
        $content .= "      <span class='date'>{$article['date']}</span>\n";

        if (!empty($article['category'])) {
            $content .= "      <span class='category'>{$article['category']}</span>\n";
        }

        if (!empty($article['readTime'])) {
            $content .= "      <span class='read-time'>{$article['readTime']}</span>\n";
        }

        $content .= "    </div>\n";
        $content .= "  </header>\n";
        $content .= "  <div class='article-body'>\n";
        $content .= "    <!-- Contenu de l'article à ajouter -->\n";
        $content .= "  </div>\n";
        $content .= "</article>\n";

        // Vérifier si l'article existe déjà
        $stmt = $db->prepare("SELECT id FROM pages WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $article['slug']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Mettre à jour
            $sql = "UPDATE pages SET
                title = :title,
                content = :content,
                type = 'page',
                template = 'blog',
                meta_title = :meta_title,
                meta_description = :meta_description,
                status = 'published',
                published_at = :published_at
            WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':title' => $article['title'],
                ':content' => $content,
                ':meta_title' => $article['title'],
                ':meta_description' => $article['description'] ?? null,
                ':published_at' => $article['date'] ?? date('Y-m-d H:i:s'),
                ':id' => $existing['id']
            ]);

            $updated++;
            echo "🔄 Mis à jour : {$article['slug']}\n";
        } else {
            // Créer
            $sql = "INSERT INTO pages (
                title, slug, content, type, template,
                meta_title, meta_description,
                status, published_at
            ) VALUES (
                :title, :slug, :content, 'page', 'blog',
                :meta_title, :meta_description,
                'published', :published_at
            )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':title' => $article['title'],
                ':slug' => $article['slug'],
                ':content' => $content,
                ':meta_title' => $article['title'],
                ':meta_description' => $article['description'] ?? null,
                ':published_at' => $article['date'] ?? date('Y-m-d H:i:s')
            ]);

            $imported++;
            echo "✅ Importé : {$article['slug']}\n";
        }

    } catch (Exception $e) {
        $errors[] = "Erreur pour {$article['slug']}: " . $e->getMessage();
        echo "❌ Erreur : {$article['slug']} - " . $e->getMessage() . "\n";
    }
}

// Résumé
echo "\n================================================\n";
echo "📊 RÉSUMÉ DE L'IMPORT\n";
echo "================================================\n";
echo "✅ Articles importés : $imported\n";
echo "🔄 Articles mis à jour : $updated\n";
echo "❌ Erreurs : " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n📝 Détail des erreurs :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✨ Import terminé !\n";
