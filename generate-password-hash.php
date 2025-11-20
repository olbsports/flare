<?php
/**
 * FLARE CUSTOM - Générateur de hash de mot de passe
 * Génère un hash bcrypt pour mettre à jour directement dans la BDD
 */

$newPassword = 'admin123';  // Modifier si vous voulez un autre mot de passe

echo "<h1>🔐 Générateur de hash de mot de passe</h1>";
echo "<style>body { font-family: monospace; padding: 40px; background: #f5f5f7; } pre { background: white; padding: 20px; border-radius: 8px; }</style>";

echo "<h2>Nouveau mot de passe : <strong style='color: #FF4B26;'>$newPassword</strong></h2>";

// Générer le hash
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

echo "<h3>Hash généré :</h3>";
echo "<pre>$hash</pre>";

// Test de vérification
$verification = password_verify($newPassword, $hash);
echo "<p><strong>Test de vérification :</strong> " . ($verification ? '✅ OK' : '❌ ERREUR') . "</p>";

// Requête SQL à exécuter
echo "<h3>📋 Requête SQL à exécuter dans phpMyAdmin :</h3>";
echo "<pre>";
echo "UPDATE users\n";
echo "SET password = '$hash'\n";
echo "WHERE username = 'admin';";
echo "</pre>";

echo "<hr>";
echo "<h3>🔍 Hash actuel vs nouveau hash :</h3>";
echo "<pre>";
echo "Hash actuel (dans ta BDD) :\n";
echo "\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\n";
echo "→ Mot de passe : <strong>password</strong>\n\n";

echo "Nouveau hash généré :\n";
echo "$hash\n";
echo "→ Mot de passe : <strong>$newPassword</strong>\n";
echo "</pre>";

echo "<hr>";
echo "<h3>💡 Instructions :</h3>";
echo "<ol>";
echo "<li>Va dans phpMyAdmin de ton hébergement</li>";
echo "<li>Sélectionne la base <strong>sc1ispy2055_flare_custom</strong></li>";
echo "<li>Clique sur l'onglet <strong>SQL</strong></li>";
echo "<li>Copie-colle la requête SQL ci-dessus</li>";
echo "<li>Clique sur <strong>Exécuter</strong></li>";
echo "<li>Connecte-toi avec <strong>admin / $newPassword</strong></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>🧪 Test du hash actuel :</h3>";
$currentHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "<p>Test avec 'password' : " . (password_verify('password', $currentHash) ? '✅ MATCH!' : '❌ Pas de match') . "</p>";
echo "<p>Test avec 'admin123' : " . (password_verify('admin123', $currentHash) ? '✅ MATCH!' : '❌ Pas de match') . "</p>";
echo "<p>Test avec 'admin' : " . (password_verify('admin', $currentHash) ? '✅ MATCH!' : '❌ Pas de match') . "</p>";
?>
