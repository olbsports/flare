<?php
/**
 * IMPORT PRODUITS - VERSION SIMPLE
 *
 * Ce script importe les produits depuis le CSV dans la base de données
 */

// Augmenter les limites
set_time_limit(600);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger la config
require_once 'config/database.php';
require_once 'includes/Product.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Import Produits</title>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; }</style>";
echo "</head><body>";

echo "<h1>📦 IMPORT DES PRODUITS</h1>";
echo "<hr>";

// Vérifier la connexion BDD
echo "<h2>1️⃣ Test connexion BDD...</h2>";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p class='success'>✅ Connexion BDD OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur connexion : " . $e->getMessage() . "</p>";
    echo "<p><strong>➡️ Corrige config/database.php puis recharge cette page</strong></p>";
    exit;
}

// Vérifier que la table products existe
echo "<h2>2️⃣ Vérification table products...</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'products'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "<p class='success'>✅ Table 'products' existe</p>";
    } else {
        echo "<p class='error'>❌ Table 'products' n'existe pas !</p>";
        echo "<p><strong>➡️ Tu dois importer database/schema.sql via PHPMyAdmin</strong></p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . $e->getMessage() . "</p>";
    exit;
}

// Vérifier le fichier CSV
echo "<h2>3️⃣ Vérification fichier CSV...</h2>";
$csvFile = 'assets/data/PRICING-FLARE-2025.csv';

if (!file_exists($csvFile)) {
    echo "<p class='error'>❌ Fichier CSV non trouvé : $csvFile</p>";
    echo "<p><strong>➡️ Vérifie que le fichier existe bien</strong></p>";
    exit;
}

echo "<p class='success'>✅ Fichier CSV trouvé : $csvFile</p>";
$fileSize = round(filesize($csvFile) / 1024, 2);
echo "<p class='info'>📊 Taille : {$fileSize} Ko</p>";

// Lire le CSV
echo "<h2>4️⃣ Lecture du CSV...</h2>";
$handle = fopen($csvFile, 'r');
if (!$handle) {
    echo "<p class='error'>❌ Impossible d'ouvrir le fichier CSV</p>";
    exit;
}

// Sauter l'en-tête
$header = fgetcsv($handle, 0, ';', '"'); // Point-virgule + guillemets pour protéger les descriptions !
echo "<p class='success'>✅ En-tête : " . count($header) . " colonnes</p>";
echo "<pre>" . implode(', ', $header) . "</pre>";

// Compter les lignes
$totalLines = 0;
while (fgetcsv($handle, 0, ';', '"') !== false) {
    $totalLines++;
}
echo "<p class='info'>📊 Nombre de produits à importer : <strong>$totalLines</strong></p>";

// Revenir au début
fseek($handle, 0);
fgetcsv($handle, 0, ';', '"'); // Sauter l'en-tête à nouveau

// Import
echo "<h2>5️⃣ Import en cours...</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 8px;'>";

$productModel = new Product();
$imported = 0;
$updated = 0;
$errors = 0;
$line = 1;

flush();

while (($data = fgetcsv($handle, 0, ';', '"')) !== false) { // Point-virgule + guillemets !
    $line++;

    try {
        // Mapping des colonnes selon l'en-tête CSV :
        // 0:SPORT, 1:FAMILLE_PRODUIT, 2:CODE, 3:DESCRIPTION, 4-11:PRIX, 12:GRAMMAGE, 13:TISSU,
        // 14:GENRE, 15:TITRE_VENDEUR, 18:REFERENCE_FLARE, 19:DESCRIPTION_SEO, 20-24:PHOTOS, 25:URL
        $productData = [
            'sport' => $data[0] ?? '',
            'famille' => $data[1] ?? '',
            'nom' => $data[15] ?? $data[3] ?? '', // TITRE_VENDEUR ou DESCRIPTION
            'description' => $data[19] ?? $data[3] ?? '', // DESCRIPTION_SEO ou DESCRIPTION
            'reference' => $data[18] ?? '', // REFERENCE_FLARE
            'prix_1' => floatval(str_replace(',', '.', $data[4] ?? 0)),
            'prix_5' => floatval(str_replace(',', '.', $data[5] ?? 0)),
            'prix_10' => floatval(str_replace(',', '.', $data[6] ?? 0)),
            'prix_20' => floatval(str_replace(',', '.', $data[7] ?? 0)),
            'prix_50' => floatval(str_replace(',', '.', $data[8] ?? 0)),
            'prix_100' => floatval(str_replace(',', '.', $data[9] ?? 0)),
            'prix_250' => floatval(str_replace(',', '.', $data[10] ?? 0)),
            'prix_500' => floatval(str_replace(',', '.', $data[11] ?? 0)),
            'grammage' => $data[12] ?? '',
            'tissu' => $data[13] ?? '',
            'genre' => $data[14] ?? '',
            'photo_1' => $data[20] ?? '',
            'photo_2' => $data[21] ?? '',
            'photo_3' => $data[22] ?? '',
            'photo_4' => $data[23] ?? '',
            'photo_5' => $data[24] ?? '',
            'url' => $data[25] ?? ''
        ];

        // Vérifier si le produit existe déjà
        $existing = $productModel->getByReference($productData['reference']);

        if ($existing) {
            // Mettre à jour
            $productModel->update($existing['id'], $productData);
            $updated++;
        } else {
            // Créer
            $productModel->create($productData);
            $imported++;
        }

        // Afficher progression tous les 50 produits
        if (($imported + $updated) % 50 === 0) {
            $progress = round((($imported + $updated) / $totalLines) * 100);
            echo "<p>⏳ Progression : {$progress}% ({$imported} créés, {$updated} mis à jour)</p>";
            flush();
        }

    } catch (Exception $e) {
        $errors++;
        if ($errors <= 5) { // Afficher max 5 erreurs
            echo "<p class='error'>❌ Ligne $line : " . $e->getMessage() . "</p>";
        }
    }
}

fclose($handle);

echo "</div>";

// Résumé
echo "<hr>";
echo "<h2>✅ IMPORT TERMINÉ !</h2>";
echo "<div style='background: #c6f6d5; padding: 20px; border-radius: 10px;'>";
echo "<h3>📊 Résumé :</h3>";
echo "<ul>";
echo "<li><strong>Créés :</strong> $imported produits</li>";
echo "<li><strong>Mis à jour :</strong> $updated produits</li>";
echo "<li><strong>Erreurs :</strong> $errors</li>";
echo "<li><strong>Total traité :</strong> " . ($imported + $updated) . " / $totalLines</li>";
echo "</ul>";
echo "</div>";

// Vérifier dans la base
echo "<h2>6️⃣ Vérification dans la base...</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as nb FROM products");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ Nombre de produits en base : <strong>" . $result['nb'] . "</strong></p>";

    // Afficher quelques exemples
    $stmt = $conn->query("SELECT reference, nom, sport, famille, prix_50 FROM products LIMIT 10");
    $examples = $stmt->fetchAll();

    echo "<h3>📋 Exemples de produits importés :</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Référence</th><th>Nom</th><th>Sport</th><th>Famille</th><th>Prix</th></tr>";
    foreach ($examples as $ex) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($ex['reference']) . "</td>";
        echo "<td>" . htmlspecialchars($ex['nom']) . "</td>";
        echo "<td>" . htmlspecialchars($ex['sport']) . "</td>";
        echo "<td>" . htmlspecialchars($ex['famille']) . "</td>";
        echo "<td>" . number_format($ex['prix_50'], 2) . "€</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p class='error'>Erreur vérification : " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎯 Prochaines étapes</h2>";
echo "<ol>";
echo "<li><strong>Générer les configurations :</strong> Lance <a href='generate-product-configs.php'>generate-product-configs.php</a></li>";
echo "<li><strong>Tester l'admin :</strong> Va sur <a href='admin/'>admin/</a></li>";
echo "<li><strong>Configurer le configurateur :</strong> Va sur <a href='admin/product-configurator.html'>admin/product-configurator.html</a></li>";
echo "</ol>";

echo "</body></html>";
?>
