<?php
/**
 * TEST DE CONNEXION BDD - ULTRA SIMPLE
 *
 * ⚠️ INSTRUCTIONS :
 * 1. Ouvre config/database.php
 * 2. Ligne 26 : Remplace define('DB_PASS', ''); par ton VRAI mot de passe
 * 3. Enregistre
 * 4. Visite ce fichier dans ton navigateur : https://ton-site.com/test-connexion-simple.php
 */

echo "<h1>🔍 TEST DE CONNEXION BDD</h1>";
echo "<hr>";

// Charger la config
require_once 'config/database.php';

echo "<h2>📋 Configuration actuelle :</h2>";
echo "<ul>";
echo "<li>Host : <strong>" . DB_HOST . "</strong></li>";
echo "<li>Database : <strong>" . DB_NAME . "</strong></li>";
echo "<li>User : <strong>" . DB_USER . "</strong></li>";
echo "<li>Password : <strong>" . (DB_PASS ? '****** (défini)' : '⚠️ VIDE - IL FAUT LE REMPLIR !') . "</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🔌 Test de connexion...</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "<div style='background: #c6f6d5; padding: 20px; border-radius: 10px; border-left: 5px solid #48bb78;'>";
    echo "<h3>✅ CONNEXION RÉUSSIE !</h3>";
    echo "<p>La connexion à la base de données fonctionne parfaitement !</p>";
    echo "</div>";

    // Tester les tables
    echo "<hr>";
    echo "<h2>📊 Vérification des tables...</h2>";

    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Nombre de tables trouvées : <strong>" . count($tables) . "</strong></p>";

    if (count($tables) > 0) {
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 8px;'>";
        echo "<h3>Tables présentes :</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✓ $table</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #fed7d7; padding: 20px; border-radius: 10px; border-left: 5px solid #e53e3e;'>";
        echo "<h3>⚠️ AUCUNE TABLE TROUVÉE</h3>";
        echo "<p>La connexion fonctionne mais la base est vide.</p>";
        echo "<p><strong>Action requise :</strong> Tu dois importer les schémas SQL :</p>";
        echo "<ol>";
        echo "<li>Via PHPMyAdmin : Importer database/schema.sql</li>";
        echo "<li>Puis importer database/schema-advanced.sql</li>";
        echo "</ol>";
        echo "</div>";
    }

    // Vérifier les tables importantes
    echo "<hr>";
    echo "<h2>🎯 Vérification des tables essentielles...</h2>";

    $requiredTables = ['products', 'categories', 'quotes', 'product_configurations'];
    $missingTables = [];

    foreach ($requiredTables as $reqTable) {
        if (!in_array($reqTable, $tables)) {
            $missingTables[] = $reqTable;
        }
    }

    if (empty($missingTables)) {
        echo "<div style='background: #c6f6d5; padding: 15px; border-radius: 8px;'>";
        echo "<h3>✅ Toutes les tables essentielles sont présentes !</h3>";
        echo "</div>";

        // Compter les produits
        echo "<hr>";
        echo "<h2>📦 Contenu de la base...</h2>";

        try {
            $stmt = $conn->query("SELECT COUNT(*) as nb FROM products");
            $result = $stmt->fetch();
            echo "<p>Produits : <strong>" . $result['nb'] . "</strong></p>";

            $stmt = $conn->query("SELECT COUNT(*) as nb FROM quotes");
            $result = $stmt->fetch();
            echo "<p>Devis : <strong>" . $result['nb'] . "</strong></p>";

            $stmt = $conn->query("SELECT COUNT(*) as nb FROM product_configurations");
            $result = $stmt->fetch();
            echo "<p>Configurations produits : <strong>" . $result['nb'] . "</strong></p>";

        } catch (Exception $e) {
            echo "<p>Impossible de compter les données (c'est normal si la base est vide)</p>";
        }

    } else {
        echo "<div style='background: #fed7d7; padding: 20px; border-radius: 10px;'>";
        echo "<h3>❌ Tables manquantes :</h3>";
        echo "<ul>";
        foreach ($missingTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        echo "<p><strong>Action :</strong> Importe les schémas SQL via PHPMyAdmin</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #fed7d7; padding: 20px; border-radius: 10px; border-left: 5px solid #e53e3e;'>";
    echo "<h3>❌ ERREUR DE CONNEXION</h3>";
    echo "<p><strong>Message d'erreur :</strong></p>";
    echo "<pre style='background: white; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<hr>";
    echo "<h3>🔧 Comment corriger :</h3>";
    echo "<ol>";
    echo "<li><strong>Vérifie ton mot de passe MySQL</strong><br>";
    echo "   Ouvre config/database.php et ligne 26, remplace :<br>";
    echo "   <code>define('DB_PASS', '');</code><br>";
    echo "   par ton vrai mot de passe :<br>";
    echo "   <code>define('DB_PASS', 'ton_vrai_mot_de_passe');</code>";
    echo "</li>";
    echo "<li><strong>Vérifie le nom de la base</strong><br>";
    echo "   Dans cPanel > MySQL Databases, vérifie que la base <strong>sc1ispy2055_flare_custom</strong> existe";
    echo "</li>";
    echo "<li><strong>Vérifie l'utilisateur</strong><br>";
    echo "   Vérifie que l'utilisateur <strong>sc1ispy2055_flare</strong> a les droits sur cette base";
    echo "</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>📚 Prochaines étapes</h2>";
echo "<div style='background: #e6f7ff; padding: 20px; border-radius: 10px;'>";
echo "<ol>";
echo "<li><strong>Si connexion OK :</strong> Tu peux passer à l'import des données</li>";
echo "<li><strong>Si tables manquantes :</strong> Importe les schémas SQL</li>";
echo "<li><strong>Si base vide :</strong> Lance import-produits-simple.php</li>";
echo "</ol>";
echo "</div>";
?>
