<?php
/**
 * FLARE CUSTOM - Setup Admin Rapide
 * Création automatique de la BDD et de l'utilisateur admin
 */

// Configuration BDD
$DB_HOST = 'localhost';
$DB_NAME = 'flare_custom';
$DB_USER = 'root';  // Modifier selon votre config
$DB_PASS = '';      // Modifier selon votre config

echo "🚀 FLARE CUSTOM - Setup Admin Rapide\n\n";

try {
    // Connexion sans spécifier la base de données
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer la base de données si elle n'existe pas
    echo "1️⃣ Création de la base de données '$DB_NAME'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✅ Base de données créée\n\n";

    // Sélectionner la base de données
    $pdo->exec("USE `$DB_NAME`");

    // Lire et exécuter le schéma SQL
    echo "2️⃣ Création des tables...\n";
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');

    // Séparer les requêtes SQL
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) { return !empty($stmt); }
    );

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "   ✅ Tables créées\n\n";

    // Créer l'utilisateur admin
    echo "3️⃣ Création de l'utilisateur admin...\n";

    // Vérifier si l'admin existe déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $exists = $stmt->fetchColumn();

    if ($exists) {
        echo "   ⚠️  L'utilisateur admin existe déjà\n";
        echo "   🔄 Mise à jour du mot de passe...\n";

        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
        $stmt->execute([':password' => $hashedPassword]);

        echo "   ✅ Mot de passe réinitialisé\n\n";
    } else {
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:username, :password, :email, :role, 1)");
        $stmt->execute([
            ':username' => 'admin',
            ':password' => $hashedPassword,
            ':email' => 'admin@flarecustom.com',
            ':role' => 'admin'
        ]);

        echo "   ✅ Utilisateur admin créé\n\n";
    }

    // Créer le fichier de configuration
    echo "4️⃣ Création du fichier de configuration...\n";

    $configContent = "<?php
/**
 * FLARE CUSTOM - Database Configuration
 * Généré automatiquement le " . date('Y-m-d H:i:s') . "
 */

define('DB_HOST', '$DB_HOST');
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');
define('DB_CHARSET', 'utf8mb4');

// Classe Database Singleton
class Database {
    private static \$instance = null;
    private \$connection;

    private function __construct() {
        \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
    }

    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    public function getConnection() {
        return \$this->connection;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new Exception(\"Cannot unserialize singleton\");
    }
}
";

    $configDir = __DIR__ . '/config';
    if (!file_exists($configDir)) {
        mkdir($configDir, 0755, true);
    }

    file_put_contents($configDir . '/database.php', $configContent);
    echo "   ✅ Fichier de configuration créé\n\n";

    // Résumé
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Installation terminée avec succès !\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "🔐 Identifiants de connexion :\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n\n";
    echo "🌐 Accéder à l'administration :\n";
    echo "   " . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . "/admin/\n\n";
    echo "⚠️  IMPORTANT : Changez le mot de passe dès votre première connexion !\n\n";

} catch (PDOException $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n\n";

    echo "💡 Vérifiez votre configuration :\n";
    echo "   - Le serveur MySQL est-il démarré ?\n";
    echo "   - Les identifiants sont-ils corrects ?\n";
    echo "   - L'utilisateur a-t-il les droits de créer une base de données ?\n\n";

    echo "📝 Modifiez les variables en haut du fichier setup-admin.php si nécessaire :\n";
    echo "   \$DB_HOST = '$DB_HOST'\n";
    echo "   \$DB_NAME = '$DB_NAME'\n";
    echo "   \$DB_USER = '$DB_USER'\n";
    echo "   \$DB_PASS = '$DB_PASS'\n\n";

    exit(1);
}
