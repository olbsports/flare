<?php
/**
 * FLARE CUSTOM - Generate Product Configurations
 * Génère automatiquement les configurations de configurateur pour tous les produits
 * À exécuter après l'import des produits
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Product.php';
require_once __DIR__ . '/includes/ProductConfig.php';

// Couleur pour le terminal
function color($text, $color) {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'cyan' => "\033[0;36m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

echo color("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n", 'cyan');
echo color("   🎨 GÉNÉRATION DES CONFIGURATIONS PRODUITS\n", 'cyan');
echo color("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n", 'cyan');

try {
    $productModel = new Product();
    $configModel = new ProductConfig();

    // Récupérer tous les produits
    echo color("\n📦 Chargement des produits...\n", 'yellow');
    $products = $productModel->getAll(['limit' => 10000]);
    $totalProducts = count($products);

    echo color("   → $totalProducts produits trouvés\n", 'green');

    // Statistiques
    $stats = [
        'created' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    echo color("\n🔨 Génération des configurations...\n", 'yellow');
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($products as $index => $product) {
        $progress = $index + 1;
        $percent = round(($progress / $totalProducts) * 100);
        $bar = str_repeat('█', floor($percent / 2)) . str_repeat('░', 50 - floor($percent / 2));

        // Afficher la progression
        echo "\r" . color("[$bar] $percent%", 'cyan') . " | $progress/$totalProducts produits";

        try {
            // Vérifier si une config existe déjà
            $existingConfig = $configModel->getByProductId($product['id']);

            if ($existingConfig) {
                $stats['skipped']++;
                continue;
            }

            // Générer une configuration par défaut
            $configModel->generateDefault($product['id']);
            $stats['created']++;

        } catch (Exception $e) {
            $stats['errors']++;
            // Ne pas afficher les erreurs pour garder l'affichage propre
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // Afficher les statistiques
    echo color("\n✅ GÉNÉRATION TERMINÉE !\n", 'green');
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo color("   📊 Statistiques :\n", 'cyan');
    echo color("      ✓ Créées      : " . $stats['created'] . " configurations\n", 'green');
    echo color("      ⊘ Ignorées    : " . $stats['skipped'] . " (déjà existantes)\n", 'yellow');
    if ($stats['errors'] > 0) {
        echo color("      ✗ Erreurs     : " . $stats['errors'] . "\n", 'red');
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // Exemples de configurations générées
    echo color("\n💡 Exemple de configuration générée :\n", 'yellow');
    $exampleConfig = $configModel->getByProductId($products[0]['id']);
    if ($exampleConfig) {
        echo "   Produit : " . $products[0]['nom'] . "\n";
        echo "   - Couleurs personnalisables : " . ($exampleConfig['allow_colors'] ? 'Oui' : 'Non') . "\n";
        echo "   - Logos autorisés : " . ($exampleConfig['allow_logos'] ? 'Oui' : 'Non') . "\n";
        echo "   - Textes autorisés : " . ($exampleConfig['allow_text'] ? 'Oui' : 'Non') . "\n";
        echo "   - Numéros autorisés : " . ($exampleConfig['allow_numbers'] ? 'Oui' : 'Non') . "\n";
        echo "   - Quantité min : " . $exampleConfig['min_quantity'] . "\n";
        echo "   - Quantité max : " . $exampleConfig['max_quantity'] . "\n";
        echo "   - Délai : " . $exampleConfig['lead_time_days'] . " jours\n";
    }

    echo color("\n🎉 Configuration terminée ! Les produits sont prêts pour le configurateur !\n", 'green');
    echo color("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n", 'cyan');

    // Instructions suivantes
    echo color("📋 Prochaines étapes :\n", 'yellow');
    echo "   1. Testez l'API du configurateur :\n";
    echo "      → php -S localhost:8000\n";
    echo "      → Visitez : http://localhost:8000/api/configurator-data.php?action=product&reference=" . $products[0]['reference'] . "\n";
    echo "\n   2. Intégrez le nouveau JS dans vos pages produits :\n";
    echo "      → Voir MIGRATION_CONFIGURATEUR.md pour les instructions complètes\n";
    echo "\n   3. Personnalisez les configurations via l'API :\n";
    echo "      → PUT /api/product-config.php?id=123\n";
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (Exception $e) {
    echo color("\n❌ ERREUR : " . $e->getMessage() . "\n", 'red');
    echo color("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n", 'cyan');
    exit(1);
}
