<?php
/**
 * FLARE CUSTOM - Import des produits depuis CSV
 * Ce script importe tous les produits du fichier PRICING-FLARE-2025.csv dans la base de données
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Product.php';

set_time_limit(600); // 10 minutes max

echo "🚀 FLARE CUSTOM - Import des produits depuis CSV\n";
echo "================================================\n\n";

$csvFile = __DIR__ . '/assets/data/PRICING-FLARE-2025.csv';

if (!file_exists($csvFile)) {
    die("❌ Erreur : Le fichier CSV n'existe pas : $csvFile\n");
}

$productModel = new Product();

// Ouvrir le fichier CSV
$handle = fopen($csvFile, 'r');

if (!$handle) {
    die("❌ Erreur : Impossible d'ouvrir le fichier CSV\n");
}

// Lire la première ligne (headers)
$headers = fgetcsv($handle, 0, ';');

if (!$headers) {
    die("❌ Erreur : Le fichier CSV est vide\n");
}

echo "📋 Headers trouvés : " . implode(', ', $headers) . "\n\n";

$imported = 0;
$updated = 0;
$skipped = 0;
$errors = [];

// Lire chaque ligne
while (($row = fgetcsv($handle, 0, ';')) !== false) {
    // Combiner headers avec valeurs
    $data = array_combine($headers, $row);

    // Vérifier que les champs obligatoires sont présents
    if (empty($data['REFERENCE_FLARE']) || empty($data['TITRE_VENDEUR'])) {
        $skipped++;
        continue;
    }

    try {
        // Générer le slug depuis l'URL si présente, sinon depuis le titre
        $slug = null;
        if (!empty($data['URL'])) {
            // Extraire le slug de l'URL
            $urlParts = explode('/', trim($data['URL'], '/'));
            $slug = end($urlParts);
        }

        // Préparer les données pour l'insertion
        $productData = [
            'reference' => $data['REFERENCE_FLARE'],
            'nom' => $data['TITRE_VENDEUR'],
            'sport' => $data['SPORT'] ?? null,
            'famille' => $data['FAMILLE_PRODUIT'] ?? null,
            'description' => $data['DESCRIPTION'] ?? null,
            'description_seo' => $data['DESCRIPTION_SEO'] ?? null,
            'tissu' => $data['TISSU'] ?? null,
            'grammage' => $data['GRAMMAGE'] ?? null,

            // Prix
            'prix_1' => !empty($data['QTY_1']) ? (float)str_replace(',', '.', $data['QTY_1']) : null,
            'prix_5' => !empty($data['QTY_5']) ? (float)str_replace(',', '.', $data['QTY_5']) : null,
            'prix_10' => !empty($data['QTY_10']) ? (float)str_replace(',', '.', $data['QTY_10']) : null,
            'prix_20' => !empty($data['QTY_20']) ? (float)str_replace(',', '.', $data['QTY_20']) : null,
            'prix_50' => !empty($data['QTY_50']) ? (float)str_replace(',', '.', $data['QTY_50']) : null,
            'prix_100' => !empty($data['QTY_100']) ? (float)str_replace(',', '.', $data['QTY_100']) : null,
            'prix_250' => !empty($data['QTY_250']) ? (float)str_replace(',', '.', $data['QTY_250']) : null,
            'prix_500' => !empty($data['QTY_500']) ? (float)str_replace(',', '.', $data['QTY_500']) : null,

            // Photos
            'photo_1' => $data['PHOTO_1'] ?? null,
            'photo_2' => $data['PHOTO_2'] ?? null,
            'photo_3' => $data['PHOTO_3'] ?? null,
            'photo_4' => $data['PHOTO_4'] ?? null,
            'photo_5' => $data['PHOTO_5'] ?? null,

            // SEO
            'slug' => $slug,
            'url' => $data['URL'] ?? null,

            // Caractéristiques
            'genre' => $data['GENRE'] ?? null,
            'finition' => $data['FINITION'] ?? null,
            'etiquettes' => $data['ETIQUETTES'] ?? null,

            'active' => 1
        ];

        // Vérifier si le produit existe déjà
        $existing = $productModel->getByReference($data['REFERENCE_FLARE']);

        if ($existing) {
            // Mettre à jour
            $productModel->update($existing['id'], $productData);
            $updated++;
            echo "🔄 Mis à jour : {$data['REFERENCE_FLARE']} - {$data['TITRE_VENDEUR']}\n";
        } else {
            // Créer
            $productModel->create($productData);
            $imported++;
            echo "✅ Importé : {$data['REFERENCE_FLARE']} - {$data['TITRE_VENDEUR']}\n";
        }

    } catch (Exception $e) {
        $errors[] = "Erreur pour {$data['REFERENCE_FLARE']}: " . $e->getMessage();
        echo "❌ Erreur : {$data['REFERENCE_FLARE']} - " . $e->getMessage() . "\n";
    }
}

fclose($handle);

// Résumé
echo "\n================================================\n";
echo "📊 RÉSUMÉ DE L'IMPORT\n";
echo "================================================\n";
echo "✅ Produits importés : $imported\n";
echo "🔄 Produits mis à jour : $updated\n";
echo "⏭️  Produits ignorés : $skipped\n";
echo "❌ Erreurs : " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n📝 Détail des erreurs :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✨ Import terminé !\n";
