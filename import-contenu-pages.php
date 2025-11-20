<?php
/**
 * IMPORT CONTENU COMPLET DES PAGES PRODUITS
 *
 * Ce script parse les pages HTML existantes et extrait :
 * - Tous les textes et descriptions
 * - Guides des tailles
 * - Caractéristiques
 * - Images
 * - Meta SEO
 * - TOUT le contenu !
 */

set_time_limit(600);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Import Contenu Pages</title>";
echo "<style>body{font-family:Arial;padding:20px;}.success{color:green;}.error{color:red;}.info{color:blue;}pre{background:#f5f5f5;padding:10px;}</style>";
echo "</head><body>";

echo "<h1>📄 IMPORT CONTENU COMPLET DES PAGES PRODUITS</h1><hr>";

// Connexion BDD
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p class='success'>✅ Connexion BDD OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur connexion : " . $e->getMessage() . "</p>";
    exit;
}

// Fonction pour extraire le contenu d'une page HTML
function parseProductPage($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }

    $html = file_get_contents($filePath);

    // Créer un DOM
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $content = [];

    // Extraire le titre principal (h1)
    $h1 = $xpath->query('//h1')->item(0);
    $content['titre_principal'] = $h1 ? trim($h1->textContent) : '';

    // Extraire meta description
    $metaDesc = $xpath->query('//meta[@name="description"]')->item(0);
    $content['meta_description'] = $metaDesc ? $metaDesc->getAttribute('content') : '';

    // Extraire meta keywords
    $metaKeywords = $xpath->query('//meta[@name="keywords"]')->item(0);
    $content['meta_keywords'] = $metaKeywords ? $metaKeywords->getAttribute('content') : '';

    // Extraire meta title
    $metaTitle = $xpath->query('//title')->item(0);
    $content['meta_title'] = $metaTitle ? trim($metaTitle->textContent) : '';

    // Extraire description courte
    $descCourte = $xpath->query('//p[@class="description-courte"]')->item(0);
    if (!$descCourte) {
        $descCourte = $xpath->query('//div[@class="product-description"]//p')->item(0);
    }
    $content['description_courte'] = $descCourte ? trim($descCourte->textContent) : '';

    // Extraire description longue
    $descLongue = $xpath->query('//div[@class="description-longue"]')->item(0);
    if (!$descLongue) {
        $descLongue = $xpath->query('//div[@class="product-details"]')->item(0);
    }
    $content['description_longue'] = $descLongue ? trim($descLongue->textContent) : '';

    // Extraire caractéristiques
    $caracteristiques = [];
    $caracs = $xpath->query('//ul[@class="caracteristiques"]/li | //div[@class="specs"]//li');
    foreach ($caracs as $carac) {
        $caracteristiques[] = trim($carac->textContent);
    }
    $content['caracteristiques'] = json_encode($caracteristiques, JSON_UNESCAPED_UNICODE);

    // Extraire guide des tailles (tableau)
    $guideTable = $xpath->query('//table[@class="size-guide"] | //table[@class="guide-tailles"]')->item(0);
    if ($guideTable) {
        $guideTailles = [];
        $rows = $xpath->query('.//tr', $guideTable);
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td | .//th', $row);
            $rowData = [];
            foreach ($cells as $cell) {
                $rowData[] = trim($cell->textContent);
            }
            if (!empty($rowData)) {
                $guideTailles[] = $rowData;
            }
        }
        $content['guide_tailles'] = json_encode($guideTailles, JSON_UNESCAPED_UNICODE);
    } else {
        $content['guide_tailles'] = null;
    }

    // Extraire les avantages/bénéfices
    $avantages = [];
    $avantagesNodes = $xpath->query('//ul[@class="avantages"]/li | //div[@class="benefits"]//li');
    foreach ($avantagesNodes as $avantage) {
        $avantages[] = trim($avantage->textContent);
    }
    $content['avantages'] = json_encode($avantages, JSON_UNESCAPED_UNICODE);

    // Extraire composition
    $composition = $xpath->query('//div[@class="composition"] | //p[contains(@class, "composition")]')->item(0);
    $content['composition'] = $composition ? trim($composition->textContent) : '';

    // Extraire conseils d'entretien
    $entretien = $xpath->query('//div[@class="entretien"] | //div[@class="care-instructions"]')->item(0);
    $content['entretien'] = $entretien ? trim($entretien->textContent) : '';

    // Extraire galerie d'images
    $images = [];
    $imgNodes = $xpath->query('//div[@class="product-gallery"]//img | //div[@class="gallery"]//img');
    foreach ($imgNodes as $img) {
        $src = $img->getAttribute('src');
        if ($src && !in_array($src, $images)) {
            $images[] = $src;
        }
    }
    $content['galerie_images'] = json_encode($images, JSON_UNESCAPED_UNICODE);

    // Extraire URL vidéo
    $video = $xpath->query('//iframe[contains(@src, "youtube") or contains(@src, "vimeo")]')->item(0);
    $content['video_url'] = $video ? $video->getAttribute('src') : '';

    return $content;
}

// Scanner les pages produits
echo "<h2>📁 Scan des pages produits...</h2>";

$pagesDir = 'pages/produits/';
if (!is_dir($pagesDir)) {
    echo "<p class='error'>❌ Dossier pages/produits/ introuvable</p>";
    exit;
}

$files = glob($pagesDir . '*.html');
echo "<p class='success'>✅ Trouvé : " . count($files) . " pages HTML</p>";

// Import
echo "<h2>📥 Import du contenu...</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:8px;'>";

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $index => $file) {
    $fileName = basename($file, '.html');

    // Essayer de trouver le produit correspondant
    // Le nom du fichier peut être la référence ou contenir la référence
    $stmt = $conn->prepare("SELECT id, reference FROM products WHERE reference LIKE :ref OR url LIKE :url LIMIT 1");
    $stmt->execute([
        ':ref' => '%' . $fileName . '%',
        ':url' => '%' . $fileName . '%'
    ]);
    $product = $stmt->fetch();

    if (!$product) {
        $skipped++;
        continue;
    }

    try {
        // Parser la page
        $content = parseProductPage($file);

        if (!$content) {
            $errors++;
            continue;
        }

        // Vérifier si le contenu existe déjà
        $stmt = $conn->prepare("SELECT id FROM product_content WHERE product_id = :pid");
        $stmt->execute([':pid' => $product['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $conn->prepare("
                UPDATE product_content SET
                    titre_principal = :titre,
                    meta_title = :meta_title,
                    meta_description = :meta_desc,
                    meta_keywords = :meta_keywords,
                    description_courte = :desc_courte,
                    description_longue = :desc_longue,
                    caracteristiques = :caracteristiques,
                    avantages = :avantages,
                    composition = :composition,
                    entretien = :entretien,
                    guide_tailles = :guide_tailles,
                    galerie_images = :galerie_images,
                    video_url = :video_url,
                    url_slug = :slug,
                    updated_at = NOW()
                WHERE product_id = :pid
            ");
        } else {
            // Insert
            $stmt = $conn->prepare("
                INSERT INTO product_content (
                    product_id, titre_principal, meta_title, meta_description, meta_keywords,
                    description_courte, description_longue, caracteristiques, avantages,
                    composition, entretien, guide_tailles, galerie_images, video_url, url_slug
                ) VALUES (
                    :pid, :titre, :meta_title, :meta_desc, :meta_keywords,
                    :desc_courte, :desc_longue, :caracteristiques, :avantages,
                    :composition, :entretien, :guide_tailles, :galerie_images, :video_url, :slug
                )
            ");
        }

        $stmt->execute([
            ':pid' => $product['id'],
            ':titre' => $content['titre_principal'],
            ':meta_title' => $content['meta_title'],
            ':meta_desc' => $content['meta_description'],
            ':meta_keywords' => $content['meta_keywords'],
            ':desc_courte' => $content['description_courte'],
            ':desc_longue' => $content['description_longue'],
            ':caracteristiques' => $content['caracteristiques'],
            ':avantages' => $content['avantages'],
            ':composition' => $content['composition'],
            ':entretien' => $content['entretien'],
            ':guide_tailles' => $content['guide_tailles'],
            ':galerie_images' => $content['galerie_images'],
            ':video_url' => $content['video_url'],
            ':slug' => $fileName
        ]);

        $imported++;

        // Progression
        if ($imported % 10 === 0) {
            $progress = round(($index / count($files)) * 100);
            echo "<p>⏳ Progression : {$progress}% ($imported importés)</p>";
            flush();
        }

    } catch (Exception $e) {
        $errors++;
        if ($errors <= 5) {
            echo "<p class='error'>❌ Erreur {$fileName}: " . $e->getMessage() . "</p>";
        }
    }
}

echo "</div>";

echo "<hr><h2>✅ IMPORT TERMINÉ !</h2>";
echo "<div style='background:#c6f6d5;padding:20px;border-radius:10px;'>";
echo "<h3>📊 Résumé :</h3>";
echo "<ul>";
echo "<li><strong>Importés :</strong> $imported contenus</li>";
echo "<li><strong>Ignorés :</strong> $skipped (produit non trouvé)</li>";
echo "<li><strong>Erreurs :</strong> $errors</li>";
echo "</ul>";
echo "</div>";

// Exemples
echo "<h2>📋 Exemples de contenu importé :</h2>";
$stmt = $conn->query("
    SELECT pc.*, p.reference, p.nom
    FROM product_content pc
    JOIN products p ON p.id = pc.product_id
    LIMIT 5
");
$examples = $stmt->fetchAll();

if ($examples) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>";
    echo "<tr><th>Produit</th><th>Titre</th><th>Description courte</th><th>Carac.</th><th>Guide tailles</th></tr>";
    foreach ($examples as $ex) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ex['reference']) . "<br><small>" . htmlspecialchars($ex['nom']) . "</small></td>";
        echo "<td>" . htmlspecialchars(substr($ex['titre_principal'], 0, 50)) . "...</td>";
        echo "<td>" . htmlspecialchars(substr($ex['description_courte'], 0, 100)) . "...</td>";
        $caracs = json_decode($ex['caracteristiques'], true);
        echo "<td>" . (is_array($caracs) ? count($caracs) . " items" : "0") . "</td>";
        echo "<td>" . ($ex['guide_tailles'] ? "✅ Oui" : "❌ Non") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr><h2>🎯 Prochaines étapes</h2>";
echo "<ol>";
echo "<li><strong>Va dans l'admin :</strong> <a href='admin/'>admin/</a></li>";
echo "<li><strong>Gère tout le contenu :</strong> Pages admin créées pour gérer textes, guides tailles, etc.</li>";
echo "<li><strong>Configure les produits similaires :</strong> Dans l'admin produits</li>";
echo "</ol>";

echo "</body></html>";
?>
