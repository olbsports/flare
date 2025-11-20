<?php
/**
 * FLARE CUSTOM - Import complet de toutes les données
 * Ce script exécute tous les imports en une seule fois
 */

set_time_limit(1800); // 30 minutes max

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║          🚀 FLARE CUSTOM - IMPORT COMPLET 🚀                ║\n";
echo "║                                                              ║\n";
echo "║  Ce script va importer toutes vos données dans la BDD :     ║\n";
echo "║  • ~1700 produits depuis le CSV                             ║\n";
echo "║  • ~500+ pages HTML                                         ║\n";
echo "║  • Articles de blog                                         ║\n";
echo "║  • Templates SVG                                            ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$startTime = microtime(true);

// ============================================
// 1. IMPORT DES PRODUITS
// ============================================
echo "\n┌──────────────────────────────────────────────────────────────┐\n";
echo "│ ÉTAPE 1/4 : IMPORT DES PRODUITS                             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

$step1Start = microtime(true);
ob_start();
require __DIR__ . '/import-products.php';
$productsOutput = ob_get_clean();
echo $productsOutput;
$step1Time = round(microtime(true) - $step1Start, 2);
echo "\n⏱️  Durée : {$step1Time}s\n";

// ============================================
// 2. IMPORT DES PAGES HTML
// ============================================
echo "\n┌──────────────────────────────────────────────────────────────┐\n";
echo "│ ÉTAPE 2/4 : IMPORT DES PAGES HTML                           │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

$step2Start = microtime(true);
ob_start();
require __DIR__ . '/import-pages.php';
$pagesOutput = ob_get_clean();
echo $pagesOutput;
$step2Time = round(microtime(true) - $step2Start, 2);
echo "\n⏱️  Durée : {$step2Time}s\n";

// ============================================
// 3. IMPORT DES ARTICLES DE BLOG
// ============================================
echo "\n┌──────────────────────────────────────────────────────────────┐\n";
echo "│ ÉTAPE 3/4 : IMPORT DES ARTICLES DE BLOG                     │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

$step3Start = microtime(true);
ob_start();
require __DIR__ . '/import-blog.php';
$blogOutput = ob_get_clean();
echo $blogOutput;
$step3Time = round(microtime(true) - $step3Start, 2);
echo "\n⏱️  Durée : {$step3Time}s\n";

// ============================================
// 4. SCAN DES TEMPLATES
// ============================================
echo "\n┌──────────────────────────────────────────────────────────────┐\n";
echo "│ ÉTAPE 4/4 : SCAN DES TEMPLATES SVG                          │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

$step4Start = microtime(true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Template.php';

$templateModel = new Template();
$result = $templateModel->scanAndImport();

echo "✅ Templates importés : {$result['imported']}\n";
echo "⏭️  Templates déjà existants : {$result['skipped']}\n";

$step4Time = round(microtime(true) - $step4Start, 2);
echo "\n⏱️  Durée : {$step4Time}s\n";

// ============================================
// RÉSUMÉ FINAL
// ============================================
$totalTime = round(microtime(true) - $startTime, 2);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                     ✨ IMPORT TERMINÉ ✨                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📊 STATISTIQUES GLOBALES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "⏱️  Durée totale : {$totalTime}s\n";
echo "📦 Étape 1 (Produits) : {$step1Time}s\n";
echo "📄 Étape 2 (Pages) : {$step2Time}s\n";
echo "📝 Étape 3 (Blog) : {$step3Time}s\n";
echo "🎨 Étape 4 (Templates) : {$step4Time}s\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
echo "🎉 Toutes vos données sont maintenant dans la base de données !\n";
echo "\n";
echo "📋 PROCHAINES ÉTAPES :\n";
echo "  1. Vérifiez vos données via les APIs :\n";
echo "     • GET /api/products.php\n";
echo "     • GET /api/categories.php\n";
echo "     • GET /api/templates.php\n";
echo "\n";
echo "  2. Créez des catégories depuis vos produits importés\n";
echo "\n";
echo "  3. Testez le configurateur avec les vraies données\n";
echo "\n";
echo "🚀 Votre backend est maintenant complet et opérationnel !\n";
echo "\n";
