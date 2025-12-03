<?php
/**
 * FLARE CUSTOM - Setup Initial
 * Script de configuration et import de toutes les données
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600); // 10 minutes

$step = $_GET['step'] ?? '1';
$message = '';
$error = '';

// Récupérer le mot de passe actuel de la config
$configFile = __DIR__ . '/config/database.php';
$currentPassword = '';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    if (preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $configContent, $matches)) {
        $currentPassword = $matches[1];
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $dbPass = $_POST['db_password'] ?? '';
                if ($dbPass) {
                    // Mettre à jour le fichier de config
                    $newConfig = preg_replace(
                        "/define\('DB_PASS',\s*'[^']*'\)/",
                        "define('DB_PASS', '$dbPass')",
                        $configContent
                    );
                    file_put_contents($configFile, $newConfig);
                    header('Location: setup.php?step=2');
                    exit;
                }
                break;

            case 'create_tables':
                try {
                    require_once __DIR__ . '/config/database.php';
                    $pdo = Database::getInstance()->getConnection();

                    // Exécuter le schéma principal
                    $schemaFiles = [
                        __DIR__ . '/database/schema.sql',
                        __DIR__ . '/database/schema-blog.sql',
                        __DIR__ . '/database/schema-advanced.sql'
                    ];

                    foreach ($schemaFiles as $file) {
                        if (file_exists($file)) {
                            $sql = file_get_contents($file);
                            // Séparer les requêtes et les exécuter une par une
                            $queries = array_filter(array_map('trim', explode(';', $sql)));
                            foreach ($queries as $query) {
                                if (!empty($query) && stripos($query, 'INSERT') === false) {
                                    try {
                                        $pdo->exec($query);
                                    } catch (Exception $e) {
                                        // Ignorer les erreurs de table déjà existante
                                    }
                                }
                            }
                        }
                    }

                    // Créer l'utilisateur admin
                    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $pdo->exec("INSERT IGNORE INTO users (username, email, password, role, active)
                               VALUES ('admin', 'admin@flare-custom.com', '$adminPassword', 'admin', 1)");

                    header('Location: setup.php?step=3&success=tables');
                    exit;
                } catch (Exception $e) {
                    $error = "Erreur: " . $e->getMessage();
                }
                break;

            case 'import_data':
                try {
                    require_once __DIR__ . '/config/database.php';
                    $pdo = Database::getInstance()->getConnection();
                    $results = [];

                    // Import des catégories
                    $results['categories'] = importCategories($pdo);

                    // Import des produits
                    $results['products'] = importProducts($pdo);

                    // Import des pages
                    $results['pages'] = importPages($pdo);

                    // Import du blog
                    $results['blog'] = importBlog($pdo);

                    $_SESSION['import_results'] = $results;
                    header('Location: setup.php?step=4');
                    exit;
                } catch (Exception $e) {
                    $error = "Erreur: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fonctions d'import
function importCategories($pdo) {
    $sports = [
        ['Football', 'football'], ['Basketball', 'basketball'], ['Rugby', 'rugby'],
        ['Handball', 'handball'], ['Volleyball', 'volleyball'], ['Running', 'running'],
        ['Cyclisme', 'cyclisme'], ['Sportswear', 'sportswear'], ['Tennis', 'tennis'],
        ['Boxe', 'boxe'], ['MMA', 'mma'], ['Athlétisme', 'athletisme']
    ];

    $familles = [
        ['Maillot', 'maillot'], ['Short', 'short'], ['Polo', 'polo'],
        ['Veste', 'veste'], ['Pantalon', 'pantalon'], ['Débardeur', 'debardeur'],
        ['Sweat', 'sweat'], ['T-Shirt', 't-shirt'], ['Survêtement', 'survetement']
    ];

    $count = 0;
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (nom, slug, type, ordre, active) VALUES (?, ?, ?, ?, 1)");

    foreach ($sports as $i => $s) {
        $stmt->execute([$s[0], $s[1], 'sport', $i + 1]);
        $count++;
    }
    foreach ($familles as $i => $f) {
        $stmt->execute([$f[0], $f[1], 'famille', $i + 1]);
        $count++;
    }

    return $count;
}

function importProducts($pdo) {
    $csvFile = __DIR__ . '/assets/data/PRICING-FLARE-2025.csv';
    if (!file_exists($csvFile)) return 0;

    $handle = fopen($csvFile, 'r');
    if (!$handle) return 0;

    $headers = fgetcsv($handle, 0, ';');
    if (!$headers) { fclose($handle); return 0; }

    $headers = array_map(function($h) { return strtolower(trim($h)); }, $headers);
    $count = 0;

    $sql = "INSERT INTO products (reference, nom, sport, famille, description, tissu, grammage,
            prix_1, prix_5, prix_10, prix_20, prix_50, prix_100, prix_250, prix_500, photo_1, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE nom=VALUES(nom), sport=VALUES(sport), famille=VALUES(famille),
            prix_1=VALUES(prix_1), prix_5=VALUES(prix_5), prix_10=VALUES(prix_10), prix_20=VALUES(prix_20),
            prix_50=VALUES(prix_50), prix_100=VALUES(prix_100), prix_250=VALUES(prix_250), prix_500=VALUES(prix_500)";
    $stmt = $pdo->prepare($sql);

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (count($row) < 5) continue;

        $data = [];
        foreach ($headers as $i => $h) {
            $data[$h] = isset($row[$i]) ? trim($row[$i]) : '';
        }

        $ref = $data['reference'] ?? $data['ref'] ?? '';
        if (empty($ref)) continue;

        $parsePrice = function($v) {
            if (empty($v)) return null;
            return floatval(str_replace([' ', '€', ','], ['', '', '.'], $v));
        };

        try {
            $stmt->execute([
                $ref,
                $data['nom'] ?? $data['name'] ?? $ref,
                $data['sport'] ?? '',
                $data['famille'] ?? $data['family'] ?? '',
                $data['description'] ?? '',
                $data['tissu'] ?? '',
                $data['grammage'] ?? '',
                $parsePrice($data['prix_1'] ?? $data['1'] ?? ''),
                $parsePrice($data['prix_5'] ?? $data['5'] ?? ''),
                $parsePrice($data['prix_10'] ?? $data['10'] ?? ''),
                $parsePrice($data['prix_20'] ?? $data['20'] ?? ''),
                $parsePrice($data['prix_50'] ?? $data['50'] ?? ''),
                $parsePrice($data['prix_100'] ?? $data['100'] ?? ''),
                $parsePrice($data['prix_250'] ?? $data['250'] ?? ''),
                $parsePrice($data['prix_500'] ?? $data['500'] ?? ''),
                $data['photo_1'] ?? $data['image'] ?? ''
            ]);
            $count++;
        } catch (Exception $e) {}
    }

    fclose($handle);
    return $count;
}

function importPages($pdo) {
    $infoDir = __DIR__ . '/pages/info';
    if (!is_dir($infoDir)) return 0;

    $files = glob($infoDir . '/*.html');
    $count = 0;

    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, type, status, template)
                           VALUES (?, ?, ?, 'page', 'published', 'default')
                           ON DUPLICATE KEY UPDATE content=VALUES(content)");

    foreach ($files as $file) {
        $slug = basename($file, '.html');
        $content = file_get_contents($file);
        preg_match('/<h[12][^>]*>([^<]+)<\/h[12]>/i', $content, $m);
        $title = $m[1] ?? ucwords(str_replace('-', ' ', $slug));

        try {
            $stmt->execute([$title, $slug, $content]);
            $count++;
        } catch (Exception $e) {}
    }

    return $count;
}

function importBlog($pdo) {
    $blogDir = __DIR__ . '/pages/blog';
    if (!is_dir($blogDir)) return 0;

    $files = glob($blogDir . '/*.html');
    $count = 0;

    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, status, author_name, category)
                           VALUES (?, ?, ?, ?, 'published', 'Admin', 'conseils')
                           ON DUPLICATE KEY UPDATE content=VALUES(content)");

    foreach ($files as $file) {
        $slug = basename($file, '.html');
        $content = file_get_contents($file);
        preg_match('/<h[12][^>]*>([^<]+)<\/h[12]>/i', $content, $m);
        $title = $m[1] ?? ucwords(str_replace('-', ' ', $slug));
        preg_match('/<p[^>]*>([^<]{0,200})/i', $content, $e);
        $excerpt = strip_tags($e[1] ?? '');

        try {
            $stmt->execute([$title, $slug, $content, $excerpt]);
            $count++;
        } catch (Exception $e) {}
    }

    return $count;
}

// Tester la connexion BDD
function testConnection() {
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = Database::getInstance()->getConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - FLARE CUSTOM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #1a1a1c 0%, #2d2d30 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 24px;
            max-width: 700px;
            width: 100%;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo h1 {
            font-size: 36px;
            color: #FF4B26;
            letter-spacing: 3px;
        }
        .logo p { color: #86868b; margin-top: 8px; }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 40px;
            right: 40px;
            height: 3px;
            background: #e5e5e7;
            z-index: 0;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e5e7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .step.active .step-number { background: #FF4B26; color: #fff; }
        .step.done .step-number { background: #34c759; color: #fff; }
        .step-label { font-size: 12px; color: #86868b; }

        .content { margin-bottom: 32px; }
        h2 { font-size: 24px; margin-bottom: 16px; }
        p { color: #424245; line-height: 1.6; margin-bottom: 16px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e5e7;
            border-radius: 12px;
            font-size: 16px;
        }
        input:focus { outline: none; border-color: #FF4B26; }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #FF4B26; color: #fff; }
        .btn-primary:hover { background: #E63910; }
        .btn-secondary { background: #e5e5e7; color: #1d1d1f; }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-info { background: #e3f2fd; color: #1565c0; }

        .results {
            background: #f5f5f7;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e5e7;
        }
        .result-item:last-child { border-bottom: none; }
        .result-value { font-weight: 700; color: #34c759; }

        .code-block {
            background: #1a1a1c;
            color: #fff;
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            margin: 16px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>FLARE CUSTOM</h1>
            <p>Assistant de configuration</p>
        </div>

        <div class="steps">
            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <div class="step-label">Base de données</div>
            </div>
            <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                <div class="step-label">Créer les tables</div>
            </div>
            <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'done' : 'active') : ''; ?>">
                <div class="step-number"><?php echo $step > 3 ? '✓' : '3'; ?></div>
                <div class="step-label">Importer données</div>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number"><?php echo $step >= 4 ? '✓' : '4'; ?></div>
                <div class="step-label">Terminé</div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($step == '1'): ?>
        <!-- ÉTAPE 1: Configuration BDD -->
        <div class="content">
            <h2>1. Configuration de la base de données</h2>
            <p>Entrez le mot de passe de votre base de données MySQL (o2switch).</p>

            <?php if ($currentPassword === 'TON_MOT_DE_PASSE_ICI' || empty($currentPassword)): ?>
            <div class="alert alert-info">
                Le mot de passe n'est pas encore configuré. Vous le trouverez dans cPanel > Bases de données MySQL.
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                Un mot de passe est déjà configuré. Vous pouvez le modifier ou passer à l'étape suivante.
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="save_config">
                <div class="form-group">
                    <label>Mot de passe MySQL</label>
                    <input type="password" name="db_password" placeholder="Votre mot de passe MySQL" required>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer et continuer</button>

                <?php if ($currentPassword !== 'TON_MOT_DE_PASSE_ICI' && !empty($currentPassword)): ?>
                <a href="?step=2" class="btn btn-secondary" style="margin-left: 12px;">Passer cette étape</a>
                <?php endif; ?>
            </form>
        </div>

        <?php elseif ($step == '2'): ?>
        <!-- ÉTAPE 2: Créer les tables -->
        <div class="content">
            <h2>2. Création des tables</h2>

            <?php if (testConnection()): ?>
            <div class="alert alert-success">✅ Connexion à la base de données réussie !</div>
            <p>Cliquez sur le bouton pour créer toutes les tables nécessaires et l'utilisateur admin.</p>

            <form method="POST">
                <input type="hidden" name="action" value="create_tables">
                <button type="submit" class="btn btn-primary">Créer les tables et l'admin</button>
            </form>
            <?php else: ?>
            <div class="alert alert-error">
                ❌ Impossible de se connecter à la base de données.<br>
                Vérifiez votre mot de passe et réessayez.
            </div>
            <a href="?step=1" class="btn btn-secondary">← Retour</a>
            <?php endif; ?>
        </div>

        <?php elseif ($step == '3'): ?>
        <!-- ÉTAPE 3: Importer les données -->
        <div class="content">
            <h2>3. Import des données existantes</h2>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Tables créées avec succès !</div>
            <?php endif; ?>

            <p>Cette étape va importer tout le contenu existant dans votre base de données :</p>
            <ul style="margin: 16px 0 24px 24px;">
                <li>📦 Produits depuis le fichier CSV (~1700 produits)</li>
                <li>📁 Catégories (sports et familles)</li>
                <li>📄 Pages depuis /pages/info/</li>
                <li>📝 Articles de blog depuis /pages/blog/</li>
            </ul>

            <form method="POST">
                <input type="hidden" name="action" value="import_data">
                <button type="submit" class="btn btn-primary">🚀 Lancer l'import</button>
            </form>
        </div>

        <?php elseif ($step == '4'): ?>
        <!-- ÉTAPE 4: Terminé -->
        <div class="content">
            <h2>🎉 Configuration terminée !</h2>

            <div class="alert alert-success">
                Votre panneau d'administration est prêt à être utilisé !
            </div>

            <?php if (isset($_SESSION['import_results'])): ?>
            <div class="results">
                <h3 style="margin-bottom: 16px;">Résultats de l'import</h3>
                <div class="result-item">
                    <span>📁 Catégories</span>
                    <span class="result-value"><?php echo $_SESSION['import_results']['categories'] ?? 0; ?> importées</span>
                </div>
                <div class="result-item">
                    <span>📦 Produits</span>
                    <span class="result-value"><?php echo $_SESSION['import_results']['products'] ?? 0; ?> importés</span>
                </div>
                <div class="result-item">
                    <span>📄 Pages</span>
                    <span class="result-value"><?php echo $_SESSION['import_results']['pages'] ?? 0; ?> importées</span>
                </div>
                <div class="result-item">
                    <span>📝 Articles blog</span>
                    <span class="result-value"><?php echo $_SESSION['import_results']['blog'] ?? 0; ?> importés</span>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: #f5f5f7; padding: 20px; border-radius: 12px; margin-top: 24px;">
                <h3 style="margin-bottom: 12px;">🔐 Identifiants de connexion</h3>
                <p><strong>URL :</strong> /admin/login.php</p>
                <p><strong>Utilisateur :</strong> admin</p>
                <p><strong>Mot de passe :</strong> admin123</p>
                <p style="color: #c62828; margin-top: 12px;">⚠️ Changez ce mot de passe dès la première connexion !</p>
            </div>

            <div style="margin-top: 24px;">
                <a href="admin/login.php" class="btn btn-primary">Accéder à l'administration</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
