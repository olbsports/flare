<?php
/**
 * FLARE CUSTOM - Import des pages HTML
 * Ce script importe toutes les pages HTML des dossiers pages/info et pages/produits
 */

require_once __DIR__ . '/config/database.php';

set_time_limit(600); // 10 minutes max

echo "🚀 FLARE CUSTOM - Import des pages HTML\n";
echo "================================================\n\n";

$db = Database::getInstance()->getConnection();

$directories = [
    __DIR__ . '/pages/info' => 'info',
    __DIR__ . '/pages/produits' => 'produit',
];

$imported = 0;
$updated = 0;
$errors = [];

foreach ($directories as $dir => $type) {
    if (!is_dir($dir)) {
        echo "⏭️  Dossier ignoré (n'existe pas) : $dir\n";
        continue;
    }

    echo "📁 Traitement du dossier : $dir\n";
    echo "   Type de page : $type\n\n";

    $files = glob($dir . '/*.html');

    foreach ($files as $file) {
        $filename = basename($file);
        $slug = str_replace('.html', '', $filename);

        try {
            // Lire le contenu du fichier
            $content = file_get_contents($file);

            if (empty($content)) {
                echo "⏭️  Fichier vide ignoré : $filename\n";
                continue;
            }

            // Extraire le titre depuis le HTML (balise <title> ou premier <h1>)
            $title = $slug; // Par défaut

            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $matches)) {
                $title = strip_tags($matches[1]);
            } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
                $title = strip_tags($matches[1]);
            }

            // Extraire meta description
            $metaDescription = null;
            if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $content, $matches)) {
                $metaDescription = $matches[1];
            }

            // Extraire meta keywords
            $metaKeywords = null;
            if (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/is', $content, $matches)) {
                $metaKeywords = $matches[1];
            }

            // Extraire le meta title
            $metaTitle = null;
            if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']/is', $content, $matches)) {
                $metaTitle = $matches[1];
            }

            // Nettoyer le titre
            $title = html_entity_decode($title);
            $title = trim($title);

            // Vérifier si la page existe déjà
            $stmt = $db->prepare("SELECT id FROM pages WHERE slug = :slug LIMIT 1");
            $stmt->execute([':slug' => $slug]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Mettre à jour
                $sql = "UPDATE pages SET
                    title = :title,
                    content = :content,
                    type = :type,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    meta_keywords = :meta_keywords,
                    status = 'published',
                    published_at = NOW()
                WHERE id = :id";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':content' => $content,
                    ':type' => $type === 'info' ? 'page' : 'product',
                    ':meta_title' => $metaTitle ?: $title,
                    ':meta_description' => $metaDescription,
                    ':meta_keywords' => $metaKeywords,
                    ':id' => $existing['id']
                ]);

                $updated++;
                echo "🔄 Mis à jour : $slug\n";
            } else {
                // Créer
                $sql = "INSERT INTO pages (
                    title, slug, content, type,
                    meta_title, meta_description, meta_keywords,
                    status, published_at
                ) VALUES (
                    :title, :slug, :content, :type,
                    :meta_title, :meta_description, :meta_keywords,
                    'published', NOW()
                )";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':content' => $content,
                    ':type' => $type === 'info' ? 'page' : 'product',
                    ':meta_title' => $metaTitle ?: $title,
                    ':meta_description' => $metaDescription,
                    ':meta_keywords' => $metaKeywords
                ]);

                $imported++;
                echo "✅ Importé : $slug\n";
            }

        } catch (Exception $e) {
            $errors[] = "Erreur pour $filename: " . $e->getMessage();
            echo "❌ Erreur : $filename - " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

// Résumé
echo "================================================\n";
echo "📊 RÉSUMÉ DE L'IMPORT\n";
echo "================================================\n";
echo "✅ Pages importées : $imported\n";
echo "🔄 Pages mises à jour : $updated\n";
echo "❌ Erreurs : " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n📝 Détail des erreurs :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✨ Import terminé !\n";
