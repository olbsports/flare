<?php
/**
 * TEST DIRECT BDD - Sans passer par config/database.php
 *
 * ⚠️ INSTRUCTIONS :
 * 1. Ligne 12-15 : Mets tes VRAIS identifiants MySQL
 * 2. Enregistre ce fichier
 * 3. Va sur : https://ton-site.com/test-direct.php
 */

// ⚠️ METS TES IDENTIFIANTS ICI :
$host = 'localhost';                        // o2switch = localhost
$dbname = 'sc1ispy2055_flare_custom';      // Ton nom de BDD
$user = 'sc1ispy2055_flare_adm';           // ⚠️ CORRIGÉ : L'user c'est _adm !
$password = 'TON_MOT_DE_PASSE_ICI';        // ⚠️ CHANGE ICI avec ton VRAI mot de passe !

echo "<h1>🔍 TEST DIRECT CONNEXION BDD</h1>";
echo "<hr>";

echo "<h2>📋 Identifiants utilisés :</h2>";
echo "<ul>";
echo "<li><strong>Host :</strong> $host</li>";
echo "<li><strong>Database :</strong> $dbname</li>";
echo "<li><strong>User :</strong> $user</li>";
echo "<li><strong>Password :</strong> " . (empty($password) || $password === 'TON_MOT_DE_PASSE_ICI' ?
    "<span style='color:red;'>⚠️ VIDE OU PAS CHANGÉ - CHANGE LA LIGNE 15 !</span>" :
    "<span style='color:green;'>✅ Défini (" . strlen($password) . " caractères)</span>") . "</li>";
echo "</ul>";

if (empty($password) || $password === 'TON_MOT_DE_PASSE_ICI') {
    echo "<div style='background:#fed7d7;padding:20px;border-radius:10px;margin:20px 0;'>";
    echo "<h2>❌ STOP !</h2>";
    echo "<p>Tu n'as pas changé le mot de passe dans ce fichier !</p>";
    echo "<p><strong>Action :</strong></p>";
    echo "<ol>";
    echo "<li>Ouvre ce fichier (test-direct.php) dans cPanel File Manager</li>";
    echo "<li>Ligne 15, remplace 'TON_MOT_DE_PASSE_ICI' par ton vrai mot de passe MySQL</li>";
    echo "<li>Enregistre</li>";
    echo "<li>Recharge cette page</li>";
    echo "</ol>";
    echo "</div>";
    exit;
}

echo "<hr>";
echo "<h2>🔌 Tentative de connexion...</h2>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<div style='background:#c6f6d5;padding:20px;border-radius:10px;border-left:5px solid #48bb78;'>";
    echo "<h2>✅ CONNEXION RÉUSSIE ! 🎉</h2>";
    echo "<p>La connexion à la base de données fonctionne parfaitement !</p>";
    echo "</div>";

    // Lister les tables
    echo "<hr>";
    echo "<h2>📊 Tables présentes dans la base :</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "<div style='background:#f0f0f0;padding:15px;border-radius:8px;'>";
        echo "<p><strong>Nombre de tables :</strong> " . count($tables) . "</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>✓ $table</li>";
        }
        echo "</ul>";
        echo "</div>";

        // Compter les données
        echo "<hr>";
        echo "<h2>📈 Contenu de la base :</h2>";

        if (in_array('products', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as nb FROM products");
            $count = $stmt->fetch()['nb'];
            echo "<p>🔹 Produits : <strong>$count</strong></p>";
        }

        if (in_array('quotes', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as nb FROM quotes");
            $count = $stmt->fetch()['nb'];
            echo "<p>🔹 Devis : <strong>$count</strong></p>";
        }

        if (in_array('product_configurations', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as nb FROM product_configurations");
            $count = $stmt->fetch()['nb'];
            echo "<p>🔹 Configurations produits : <strong>$count</strong></p>";
        }

    } else {
        echo "<div style='background:#fed7d7;padding:20px;border-radius:10px;'>";
        echo "<h3>⚠️ AUCUNE TABLE TROUVÉE</h3>";
        echo "<p>La connexion fonctionne mais la base est vide.</p>";
        echo "<p><strong>Prochaine étape :</strong> Importer les schémas SQL via PHPMyAdmin</p>";
        echo "</div>";
    }

    echo "<hr>";
    echo "<h2>✅ PROCHAINES ÉTAPES</h2>";
    echo "<div style='background:#e6f7ff;padding:20px;border-radius:10px;'>";
    echo "<ol>";
    echo "<li><strong>Si tables manquantes :</strong> Importe database/schema.sql et database/schema-advanced.sql via PHPMyAdmin</li>";
    echo "<li><strong>Si tables présentes mais vides :</strong> Lance <a href='import-produits-simple.php'>import-produits-simple.php</a></li>";
    echo "<li><strong>Ensuite :</strong> Lance <a href='generate-product-configs.php'>generate-product-configs.php</a></li>";
    echo "<li><strong>Enfin :</strong> Accède à <a href='admin/'>l'admin</a></li>";
    echo "</ol>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background:#fed7d7;padding:20px;border-radius:10px;border-left:5px solid #e53e3e;'>";
    echo "<h2>❌ ERREUR DE CONNEXION</h2>";
    echo "<p><strong>Message d'erreur MySQL :</strong></p>";
    echo "<pre style='background:white;padding:10px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($e->getMessage()) . "</pre>";

    echo "<hr>";
    echo "<h3>🔧 Solutions selon l'erreur :</h3>";

    // Analyser l'erreur
    $errorMsg = $e->getMessage();

    if (strpos($errorMsg, 'Access denied') !== false) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin:10px 0;'>";
        echo "<h4>🔑 Problème : Accès refusé</h4>";
        echo "<p><strong>Causes possibles :</strong></p>";
        echo "<ol>";
        echo "<li><strong>Mot de passe incorrect</strong><br>→ Vérifie ton mot de passe dans cPanel > MySQL Databases<br>→ Si besoin, change le mot de passe de l'utilisateur 'sc1ispy2055_flare_adm'</li>";
        echo "<li><strong>Utilisateur n'a pas les droits</strong><br>→ Dans cPanel > MySQL Databases > Current Databases<br>→ Vérifie que 'sc1ispy2055_flare_adm' est bien associé à 'sc1ispy2055_flare_custom'<br>→ Si non, ajoute-le avec TOUS les privilèges</li>";
        echo "</ol>";
        echo "</div>";
    } elseif (strpos($errorMsg, 'Unknown database') !== false) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin:10px 0;'>";
        echo "<h4>🗄️ Problème : Base de données introuvable</h4>";
        echo "<p><strong>Solution :</strong></p>";
        echo "<ol>";
        echo "<li>Va dans cPanel > MySQL Databases</li>";
        echo "<li>Vérifie que la base <strong>sc1ispy2055_flare_custom</strong> existe</li>";
        echo "<li>Si elle n'existe pas, crée-la avec ce nom exact</li>";
        echo "<li>Recharge cette page</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin:10px 0;'>";
        echo "<h4>❓ Erreur inconnue</h4>";
        echo "<p>Vérifie ces points :</p>";
        echo "<ol>";
        echo "<li>La base 'sc1ispy2055_flare_custom' existe dans cPanel > MySQL Databases</li>";
        echo "<li>L'utilisateur 'sc1ispy2055_flare_adm' existe</li>";
        echo "<li>L'utilisateur a les droits sur cette base</li>";
        echo "<li>Le mot de passe est correct</li>";
        echo "</ol>";
        echo "</div>";
    }

    echo "</div>";
}
?>
