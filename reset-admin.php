<?php
/**
 * FLARE CUSTOM - Reset Admin Password
 * Réinitialisation du mot de passe admin
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "<h1>🔐 FLARE CUSTOM - Réinitialisation Admin</h1>";
    echo "<pre>";

    // Vérifier si l'utilisateur admin existe
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    if ($admin) {
        echo "✅ Utilisateur 'admin' trouvé (ID: {$admin['id']})\n";
        echo "🔄 Réinitialisation du mot de passe...\n\n";

        // Mettre à jour le mot de passe
        $stmt = $db->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
        $stmt->execute([':password' => $hashedPassword]);

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ Mot de passe réinitialisé !\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    } else {
        echo "⚠️  Utilisateur 'admin' introuvable\n";
        echo "➕ Création de l'utilisateur admin...\n\n";

        // Créer l'utilisateur admin
        $stmt = $db->prepare("
            INSERT INTO users (username, password, email, role, active)
            VALUES (:username, :password, :email, 'admin', 1)
        ");
        $stmt->execute([
            ':username' => 'admin',
            ':password' => $hashedPassword,
            ':email' => 'admin@flarecustom.com'
        ]);

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ Utilisateur admin créé !\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }

    echo "🔐 <strong>Identifiants de connexion :</strong>\n";
    echo "   Username: <strong style='color: #FF4B26;'>admin</strong>\n";
    echo "   Password: <strong style='color: #FF4B26;'>admin123</strong>\n\n";

    echo "🌐 <strong>Accéder à l'administration :</strong>\n";
    echo "   <a href='/admin/' style='color: #FF4B26; font-weight: bold;'>→ Cliquer ici pour accéder à l'admin</a>\n\n";

    echo "⚠️  <strong>IMPORTANT</strong> : Supprimez ce fichier (reset-admin.php) après utilisation !\n\n";

    // Test du hash
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔍 <strong>Test du hash :</strong>\n";
    echo "   Password en clair: admin123\n";
    echo "   Hash généré: " . substr($hashedPassword, 0, 30) . "...\n";
    echo "   Vérification: " . (password_verify('admin123', $hashedPassword) ? '✅ OK' : '❌ ERREUR') . "\n";

    echo "</pre>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERREUR</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    echo "</pre>";

    echo "<h3>💡 Solutions possibles :</h3>";
    echo "<ul>";
    echo "<li>Vérifiez que le fichier <code>config/database.php</code> existe</li>";
    echo "<li>Vérifiez que les identifiants MySQL sont corrects</li>";
    echo "<li>Vérifiez que la table <code>users</code> existe dans la base de données</li>";
    echo "</ul>";
}
?>
<style>
    body {
        font-family: 'Courier New', monospace;
        background: #f5f5f7;
        padding: 40px;
        max-width: 800px;
        margin: 0 auto;
    }
    h1 {
        color: #FF4B26;
    }
    pre {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        line-height: 1.6;
    }
</style>
