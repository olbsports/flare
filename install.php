<?php
/**
 * FLARE CUSTOM - Installation Script
 * Script d'installation et de configuration de la base de données
 */

// Configuration
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Traitement de l'installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Test de connexion à la base de données
        $host = $_POST['db_host'];
        $name = $_POST['db_name'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_pass'];

        try {
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Créer la base de données si elle n'existe pas
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");

            // Lire et exécuter le schéma SQL
            $schemaPath = __DIR__ . '/database/schema.sql';
            if (file_exists($schemaPath)) {
                $schema = file_get_contents($schemaPath);

                // Remplacer le nom de la base de données
                $schema = str_replace('CREATE DATABASE IF NOT EXISTS flare_custom', "CREATE DATABASE IF NOT EXISTS `$name`", $schema);
                $schema = str_replace('USE flare_custom;', "USE `$name`;", $schema);

                // Exécuter le schéma
                $pdo->exec($schema);

                // Créer le fichier de configuration
                $configContent = "<?php
/**
 * FLARE CUSTOM - Database Configuration
 * Configuration de connexion à la base de données
 */

define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe Database - Singleton pour la connexion PDO
 */
class Database {
    private static \$instance = null;
    private \$connection;

    private function __construct() {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;

            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
            ];

            \$this->connection = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            error_log(\"Database connection error: \" . \$e->getMessage());
            die(json_encode([
                'success' => false,
                'error' => 'Database connection failed'
            ]));
        }
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

                file_put_contents(__DIR__ . '/config/database.php', $configContent);

                header('Location: install.php?step=3');
                exit;
            } else {
                $error = "Fichier schema.sql introuvable";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - FLARE CUSTOM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 42px;
            letter-spacing: 3px;
            margin-bottom: 8px;
        }

        .content {
            padding: 48px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 48px;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e5e7;
            color: #86868b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: #FF4B26;
            color: #fff;
        }

        .step.completed .step-number {
            background: #34c759;
            color: #fff;
        }

        .step-label {
            font-size: 13px;
            color: #86868b;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d1d1f;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e5e7;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-input:focus {
            outline: none;
            border-color: #FF4B26;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .info-box {
            background: #f5f5f7;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .info-box h3 {
            margin-bottom: 12px;
            color: #FF4B26;
        }

        .checklist {
            list-style: none;
            margin-top: 16px;
        }

        .checklist li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
        }

        .checklist li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #34c759;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FLARE CUSTOM</h1>
            <p>Installation et Configuration</p>
        </div>

        <div class="content">
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active completed' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">Bienvenue</div>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Base de données</div>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">Terminé</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <div class="info-box">
                    <h3>Bienvenue dans l'installation de FLARE CUSTOM !</h3>
                    <p>Ce script va configurer votre base de données et créer toutes les tables nécessaires.</p>

                    <h4 style="margin-top: 24px; margin-bottom: 12px;">Avant de commencer, assurez-vous d'avoir :</h4>
                    <ul class="checklist">
                        <li>Un serveur MySQL ou MariaDB installé</li>
                        <li>Les identifiants de connexion à la base de données</li>
                        <li>Les droits de création de base de données</li>
                        <li>PHP 7.4+ avec l'extension PDO MySQL</li>
                    </ul>
                </div>

                <a href="install.php?step=2" class="btn-primary">Commencer l'installation →</a>

            <?php elseif ($step == 2): ?>
                <h2 style="margin-bottom: 24px;">Configuration de la base de données</h2>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Hôte de la base de données</label>
                        <input type="text" name="db_host" class="form-input" value="localhost" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nom de la base de données</label>
                        <input type="text" name="db_name" class="form-input" value="flare_custom" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Utilisateur</label>
                        <input type="text" name="db_user" class="form-input" value="root" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="db_pass" class="form-input">
                    </div>

                    <button type="submit" class="btn-primary">Installer →</button>
                </form>

            <?php elseif ($step == 3): ?>
                <div class="alert alert-success">
                    <strong>✓ Installation terminée avec succès !</strong>
                </div>

                <div class="info-box">
                    <h3>Prochaines étapes :</h3>
                    <ul class="checklist">
                        <li>Base de données créée et configurée</li>
                        <li>Tables et structure installées</li>
                        <li>Utilisateur admin créé</li>
                        <li>Catégories par défaut ajoutées</li>
                    </ul>

                    <h4 style="margin-top: 24px; margin-bottom: 12px;">Identifiants par défaut :</h4>
                    <p><strong>Utilisateur :</strong> admin<br>
                    <strong>Mot de passe :</strong> admin123</p>

                    <p style="color: #c62828; margin-top: 16px;">
                        ⚠️ <strong>Important :</strong> Changez ce mot de passe dès votre première connexion !
                    </p>
                </div>

                <div style="display: flex; gap: 16px;">
                    <a href="admin/login.php" class="btn-primary">Accéder à l'administration →</a>
                    <a href="/" class="btn-primary" style="background: #34c759;">Voir le site →</a>
                </div>

                <p style="margin-top: 32px; color: #86868b; font-size: 14px;">
                    💡 Vous pouvez supprimer le fichier <code>install.php</code> pour plus de sécurité.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
