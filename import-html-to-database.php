<?php
/**
 * FLARE CUSTOM - Import du contenu des pages HTML vers la base de données
 *
 * Ce script scanne toutes les pages HTML du site et importe leur contenu
 * dans les tables de la base de données (content_blocks, product_content, etc.)
 *
 * Usage: php import-html-to-database.php
 * Ou accédez via navigateur
 */

set_time_limit(300); // 5 minutes max
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';

// Connexion BDD
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("❌ Erreur connexion BDD: " . $e->getMessage());
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Import HTML → BDD - FLARE CUSTOM</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #FF4B26;
            margin-bottom: 30px;
        }
        .log {
            background: #1d1d1f;
            color: #0f0;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #09f; }
        .warning { color: #fa0; }
        .stat {
            display: inline-block;
            background: #FF4B26;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            margin: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class='container'>
<h1>📥 Import du contenu HTML vers la base de données</h1>
";

$stats = [
    'pages_scanned' => 0,
    'blocks_created' => 0,
    'blocks_updated' => 0,
    'errors' => 0
];

echo "<div class='log'>";

/**
 * Parse une page HTML et extrait le contenu
 */
function parseHTMLPage($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }

    $html = file_get_contents($filePath);

    // Crée un DOM
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $content = [];

    // Extraction du titre
    $titleNode = $xpath->query('//title')->item(0);
    $content['title'] = $titleNode ? trim($titleNode->textContent) : '';

    // Extraction h1
    $h1Node = $xpath->query('//h1')->item(0);
    $content['h1'] = $h1Node ? trim($h1Node->textContent) : '';

    // Extraction meta description
    $metaDesc = $xpath->query('//meta[@name="description"]')->item(0);
    $content['meta_description'] = $metaDesc ? $metaDesc->getAttribute('content') : '';

    // Extraction de tout le contenu textuel (paragraphes)
    $paragraphs = [];
    foreach ($xpath->query('//p') as $p) {
        $text = trim($p->textContent);
        if ($text && strlen($text) > 10) { // Ignore les petits textes
            $paragraphs[] = $text;
        }
    }
    $content['paragraphs'] = $paragraphs;
    $content['full_text'] = implode("\n\n", $paragraphs);

    // Extraction des listes
    $lists = [];
    foreach ($xpath->query('//ul | //ol') as $list) {
        $items = [];
        foreach ($list->childNodes as $li) {
            if ($li->nodeName === 'li') {
                $items[] = trim($li->textContent);
            }
        }
        if (!empty($items)) {
            $lists[] = $items;
        }
    }
    $content['lists'] = $lists;

    // Extraction des images
    $images = [];
    foreach ($xpath->query('//img') as $img) {
        $images[] = [
            'src' => $img->getAttribute('src'),
            'alt' => $img->getAttribute('alt'),
            'title' => $img->getAttribute('title')
        ];
    }
    $content['images'] = $images;

    // Extraction des liens
    $links = [];
    foreach ($xpath->query('//a[@href]') as $a) {
        $href = $a->getAttribute('href');
        if ($href && !str_starts_with($href, '#')) {
            $links[] = [
                'href' => $href,
                'text' => trim($a->textContent)
            ];
        }
    }
    $content['links'] = $links;

    // Extraction tableaux
    $tables = [];
    foreach ($xpath->query('//table') as $table) {
        $tableData = [];
        foreach ($table->childNodes as $row) {
            if ($row->nodeName === 'tr') {
                $rowData = [];
                foreach ($row->childNodes as $cell) {
                    if ($cell->nodeName === 'td' || $cell->nodeName === 'th') {
                        $rowData[] = trim($cell->textContent);
                    }
                }
                if (!empty($rowData)) {
                    $tableData[] = $rowData;
                }
            }
        }
        if (!empty($tableData)) {
            $tables[] = $tableData;
        }
    }
    $content['tables'] = $tables;

    return $content;
}

/**
 * Crée ou met à jour un content block
 */
function saveContentBlock($db, $key, $content, $filePath) {
    global $stats;

    // Vérifie si existe déjà
    $stmt = $db->prepare("SELECT id FROM content_blocks WHERE block_key = ?");
    $stmt->execute([$key]);
    $existing = $stmt->fetch();

    $titre = $content['h1'] ?: $content['title'];
    $contenuJson = json_encode([
        'title' => $content['title'],
        'h1' => $content['h1'],
        'meta_description' => $content['meta_description'],
        'full_text' => $content['full_text'],
        'paragraphs' => $content['paragraphs'],
        'lists' => $content['lists'],
        'images' => $content['images'],
        'links' => $content['links'],
        'tables' => $content['tables'],
        'source_file' => $filePath
    ], JSON_UNESCAPED_UNICODE);

    if ($existing) {
        // Mise à jour
        $stmt = $db->prepare("
            UPDATE content_blocks
            SET titre = ?, contenu = ?, block_type = 'html', active = 1, updated_at = NOW()
            WHERE block_key = ?
        ");
        $stmt->execute([$titre, $contenuJson, $key]);
        $stats['blocks_updated']++;
        echo "<span class='info'>✏️  Mis à jour: $key</span>\n";
    } else {
        // Création
        $stmt = $db->prepare("
            INSERT INTO content_blocks (block_key, block_type, titre, contenu, active)
            VALUES (?, 'html', ?, ?, 1)
        ");
        $stmt->execute([$key, $titre, $contenuJson]);
        $stats['blocks_created']++;
        echo "<span class='success'>✅ Créé: $key</span>\n";
    }
}

// ========================================
// SCAN DES PAGES HTML
// ========================================

echo "<span class='success'>🚀 Démarrage de l'import...</span>\n\n";

// 1. Pages produits
$pagesDir = __DIR__ . '/pages/products/';
if (is_dir($pagesDir)) {
    echo "<span class='info'>📂 Scan du dossier: $pagesDir</span>\n";

    $files = glob($pagesDir . '*.html');

    foreach ($files as $file) {
        $stats['pages_scanned']++;
        $filename = basename($file, '.html');
        $blockKey = 'product_page_' . $filename;

        echo "\n<span class='info'>📄 Traitement: " . basename($file) . "</span>\n";

        try {
            $content = parseHTMLPage($file);

            if ($content) {
                saveContentBlock($db, $blockKey, $content, $file);

                // Affiche un aperçu
                echo "   Titre: " . substr($content['h1'] ?: $content['title'], 0, 60) . "...\n";
                echo "   Paragraphes: " . count($content['paragraphs']) . "\n";
                echo "   Images: " . count($content['images']) . "\n";
                echo "   Listes: " . count($content['lists']) . "\n";
                echo "   Tableaux: " . count($content['tables']) . "\n";
            } else {
                echo "<span class='warning'>⚠️  Aucun contenu extrait</span>\n";
            }

        } catch (Exception $e) {
            $stats['errors']++;
            echo "<span class='error'>❌ Erreur: " . $e->getMessage() . "</span>\n";
        }
    }
}

// 2. Pages générales
$generalPages = [
    __DIR__ . '/index.html' => 'home',
    __DIR__ . '/pages/a-propos.html' => 'about',
    __DIR__ . '/pages/contact.html' => 'contact',
    __DIR__ . '/pages/guide-tailles.html' => 'size_guide',
    __DIR__ . '/pages/collections.html' => 'collections',
];

echo "\n<span class='info'>📂 Scan des pages générales...</span>\n";

foreach ($generalPages as $file => $key) {
    if (file_exists($file)) {
        $stats['pages_scanned']++;

        echo "\n<span class='info'>📄 Traitement: " . basename($file) . "</span>\n";

        try {
            $content = parseHTMLPage($file);

            if ($content) {
                saveContentBlock($db, 'page_' . $key, $content, $file);

                echo "   Titre: " . substr($content['h1'] ?: $content['title'], 0, 60) . "...\n";
                echo "   Paragraphes: " . count($content['paragraphs']) . "\n";
            }

        } catch (Exception $e) {
            $stats['errors']++;
            echo "<span class='error'>❌ Erreur: " . $e->getMessage() . "</span>\n";
        }
    }
}

// ========================================
// RÉSULTATS FINAUX
// ========================================

echo "\n\n<span class='success'>════════════════════════════════════════</span>\n";
echo "<span class='success'>✅ IMPORT TERMINÉ !</span>\n";
echo "<span class='success'>════════════════════════════════════════</span>\n\n";

echo "</div>";

echo "<div style='margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;'>";
echo "<h2>📊 Statistiques</h2>";
echo "<span class='stat'>📄 Pages scannées: " . $stats['pages_scanned'] . "</span>";
echo "<span class='stat'>✅ Blocks créés: " . $stats['blocks_created'] . "</span>";
echo "<span class='stat'>✏️ Blocks mis à jour: " . $stats['blocks_updated'] . "</span>";
echo "<span class='stat'>❌ Erreurs: " . $stats['errors'] . "</span>";
echo "</div>";

echo "<div style='margin-top: 20px; text-align: center;'>";
echo "<a href='admin/index.php' style='display: inline-block; padding: 14px 28px; background: #FF4B26; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>🏠 Retour à l'admin</a>";
echo "</div>";

echo "</div>
</body>
</html>";
