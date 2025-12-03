<?php
/**
 * FLARE CUSTOM - Import Complet du Contenu HTML
 * Extrait tout le contenu des fichiers HTML vers la BDD
 */

session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$results = [];
$error = '';

// Traitement des actions
if ($action) {
    try {
        $pdo = Database::getInstance()->getConnection();

        switch ($action) {
            case 'init_tables':
                $results = initTables($pdo);
                break;
            case 'import_products':
                $results = importProducts($pdo);
                break;
            case 'import_categories':
                $results = importCategories($pdo);
                break;
            case 'import_pages':
                $results = importPages($pdo);
                break;
            case 'import_blog':
                $results = importBlog($pdo);
                break;
            case 'import_all':
                $results['tables'] = initTables($pdo);
                $results['categories'] = importCategories($pdo);
                $results['products'] = importProducts($pdo);
                $results['pages'] = importPages($pdo);
                $results['blog'] = importBlog($pdo);
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/**
 * Créer les tables si elles n'existent pas + admin
 */
function initTables($pdo) {
    $created = [];

    // Table users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'users';

    // Table products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) UNIQUE NOT NULL,
        nom VARCHAR(255) NOT NULL,
        sport VARCHAR(100),
        famille VARCHAR(100),
        description TEXT,
        description_seo TEXT,
        tissu VARCHAR(100),
        grammage VARCHAR(50),
        prix_1 DECIMAL(10,2),
        prix_5 DECIMAL(10,2),
        prix_10 DECIMAL(10,2),
        prix_20 DECIMAL(10,2),
        prix_50 DECIMAL(10,2),
        prix_100 DECIMAL(10,2),
        prix_250 DECIMAL(10,2),
        prix_500 DECIMAL(10,2),
        photo_1 VARCHAR(500),
        photo_2 VARCHAR(500),
        photo_3 VARCHAR(500),
        photo_4 VARCHAR(500),
        photo_5 VARCHAR(500),
        genre ENUM('Homme', 'Femme', 'Mixte', 'Enfant') DEFAULT 'Mixte',
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sport (sport),
        INDEX idx_famille (famille)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'products';

    // Table categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        type ENUM('sport', 'famille') NOT NULL,
        description TEXT,
        image VARCHAR(500),
        parent_id INT NULL,
        ordre INT DEFAULT 0,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'categories';

    // Table pages
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        type ENUM('page', 'category', 'product') DEFAULT 'page',
        template VARCHAR(100) DEFAULT 'default',
        meta_title VARCHAR(255),
        meta_description TEXT,
        meta_keywords VARCHAR(500),
        status ENUM('draft', 'published', 'archived') DEFAULT 'published',
        author_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'pages';

    // Table blog_posts
    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        excerpt TEXT,
        content LONGTEXT,
        featured_image VARCHAR(500),
        category VARCHAR(100),
        tags JSON,
        meta_title VARCHAR(255),
        meta_description TEXT,
        author_name VARCHAR(100) DEFAULT 'Admin',
        status ENUM('draft', 'published', 'archived') DEFAULT 'published',
        published_at TIMESTAMP NULL,
        views_count INT DEFAULT 0,
        reading_time INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'blog_posts';

    // Table quotes
    $pdo->exec("CREATE TABLE IF NOT EXISTS quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) UNIQUE NOT NULL,
        client_prenom VARCHAR(100),
        client_nom VARCHAR(100),
        client_email VARCHAR(150),
        client_telephone VARCHAR(20),
        client_club VARCHAR(150),
        product_reference VARCHAR(50),
        product_nom VARCHAR(255),
        sport VARCHAR(100),
        design_type ENUM('flare', 'client', 'template'),
        design_description TEXT,
        options JSON,
        tailles JSON,
        total_pieces INT,
        prix_unitaire DECIMAL(10,2),
        prix_total DECIMAL(10,2),
        status ENUM('pending', 'sent', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'quotes';

    // Table settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('string', 'text', 'number', 'boolean', 'json') DEFAULT 'string',
        category VARCHAR(50) DEFAULT 'general',
        description TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $created[] = 'settings';

    // Créer admin par défaut
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, email, password, role, active)
                VALUES ('admin', 'admin@flare-custom.com', '$adminPass', 'admin', 1)");

    // Paramètres par défaut
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, category, description) VALUES
        ('site_name', 'FLARE CUSTOM', 'string', 'general', 'Nom du site'),
        ('site_url', 'https://flare-custom.com', 'string', 'general', 'URL du site'),
        ('contact_email', 'contact@flare-custom.com', 'string', 'general', 'Email de contact'),
        ('contact_phone', '+33 1 23 45 67 89', 'string', 'general', 'Téléphone')
    ");

    return ['created' => $created, 'admin' => 'admin / admin123'];
}

/**
 * Import des catégories
 */
function importCategories($pdo) {
    $sports = [
        ['Football', 'football', 'Équipements de football personnalisés'],
        ['Basketball', 'basketball', 'Équipements de basketball personnalisés'],
        ['Rugby', 'rugby', 'Équipements de rugby personnalisés'],
        ['Handball', 'handball', 'Équipements de handball personnalisés'],
        ['Volleyball', 'volleyball', 'Équipements de volleyball personnalisés'],
        ['Running', 'running', 'Équipements de running personnalisés'],
        ['Cyclisme', 'cyclisme', 'Équipements de cyclisme personnalisés'],
        ['Sportswear', 'sportswear', 'Vêtements de sport personnalisés'],
        ['Tennis', 'tennis', 'Équipements de tennis personnalisés'],
        ['Boxe', 'boxe', 'Équipements de boxe personnalisés'],
        ['MMA', 'mma', 'Équipements de MMA personnalisés'],
        ['Athlétisme', 'athletisme', 'Équipements d\'athlétisme personnalisés']
    ];

    $familles = [
        ['Maillot', 'maillot', 'Maillots personnalisables pour tous sports'],
        ['Short', 'short', 'Shorts personnalisables'],
        ['Polo', 'polo', 'Polos personnalisables'],
        ['Veste', 'veste', 'Vestes personnalisables'],
        ['Pantalon', 'pantalon', 'Pantalons personnalisables'],
        ['Débardeur', 'debardeur', 'Débardeurs personnalisables'],
        ['Sweat', 'sweat', 'Sweats personnalisables'],
        ['T-Shirt', 't-shirt', 'T-Shirts personnalisables'],
        ['Survêtement', 'survetement', 'Survêtements personnalisables'],
        ['Coupe-vent', 'coupe-vent', 'Coupe-vents personnalisables']
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (nom, slug, type, description, ordre, active)
                           VALUES (?, ?, ?, ?, ?, 1)
                           ON DUPLICATE KEY UPDATE description = VALUES(description)");

    $count = 0;
    foreach ($sports as $i => $s) {
        $stmt->execute([$s[0], $s[1], 'sport', $s[2], $i + 1]);
        $count++;
    }
    foreach ($familles as $i => $f) {
        $stmt->execute([$f[0], $f[1], 'famille', $f[2], $i + 1]);
        $count++;
    }

    return ['imported' => $count];
}

/**
 * Import des produits depuis CSV
 */
function importProducts($pdo) {
    $csvFile = __DIR__ . '/../assets/data/PRICING-FLARE-2025.csv';
    if (!file_exists($csvFile)) {
        return ['error' => 'Fichier CSV non trouvé', 'imported' => 0];
    }

    $handle = fopen($csvFile, 'r');
    $headers = fgetcsv($handle, 0, ';');
    $headers = array_map(function($h) { return strtolower(trim($h)); }, $headers);

    $sql = "INSERT INTO products (reference, nom, sport, famille, description, tissu, grammage,
            prix_1, prix_5, prix_10, prix_20, prix_50, prix_100, prix_250, prix_500, photo_1, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            nom=VALUES(nom), sport=VALUES(sport), famille=VALUES(famille),
            prix_1=VALUES(prix_1), prix_5=VALUES(prix_5), prix_10=VALUES(prix_10),
            prix_20=VALUES(prix_20), prix_50=VALUES(prix_50), prix_100=VALUES(prix_100),
            prix_250=VALUES(prix_250), prix_500=VALUES(prix_500), photo_1=VALUES(photo_1)";
    $stmt = $pdo->prepare($sql);

    $parsePrice = function($v) {
        if (empty($v)) return null;
        return floatval(str_replace([' ', '€', ','], ['', '', '.'], $v));
    };

    $count = 0;
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (count($row) < 5) continue;

        $data = [];
        foreach ($headers as $i => $h) {
            $data[$h] = isset($row[$i]) ? trim($row[$i]) : '';
        }

        $ref = $data['reference'] ?? $data['ref'] ?? '';
        if (empty($ref)) continue;

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
    return ['imported' => $count];
}

/**
 * Import des pages depuis /pages/info/
 */
function importPages($pdo) {
    $infoDir = __DIR__ . '/../pages/info';
    if (!is_dir($infoDir)) {
        return ['error' => 'Dossier non trouvé', 'imported' => 0];
    }

    $files = glob($infoDir . '/*.html');
    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, excerpt, meta_title, meta_description, type, status, template)
                           VALUES (?, ?, ?, ?, ?, ?, 'page', 'published', 'default')
                           ON DUPLICATE KEY UPDATE
                           title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt),
                           meta_title=VALUES(meta_title), meta_description=VALUES(meta_description)");

    $count = 0;
    foreach ($files as $file) {
        $slug = basename($file, '.html');
        $html = file_get_contents($file);

        // Extraire le titre
        preg_match('/<title>([^<|]+)/i', $html, $titleMatch);
        $title = trim($titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug)));

        // Extraire la meta description
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)/i', $html, $descMatch);
        $metaDesc = $descMatch[1] ?? '';

        // Extraire le contenu principal (entre <main> ou <body> ou après le header)
        $content = $html;

        // Nettoyer - garder seulement le body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bodyMatch)) {
            $content = $bodyMatch[1];
        }

        // Supprimer header et footer pour garder le contenu principal
        $content = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $content);
        $content = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $content);
        $content = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $content);
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);

        // Créer un extrait
        $excerpt = substr(strip_tags($content), 0, 300);
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);

        try {
            $stmt->execute([$title, $slug, $content, $excerpt, $title, $metaDesc]);
            $count++;
        } catch (Exception $e) {}
    }

    return ['imported' => $count, 'source' => $infoDir];
}

/**
 * Import du blog depuis /pages/blog/
 */
function importBlog($pdo) {
    $blogDir = __DIR__ . '/../pages/blog';
    if (!is_dir($blogDir)) {
        return ['error' => 'Dossier non trouvé', 'imported' => 0];
    }

    $files = glob($blogDir . '/*.html');
    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, meta_title, meta_description, category, status, published_at, author_name)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'published', NOW(), 'Admin')
                           ON DUPLICATE KEY UPDATE
                           title=VALUES(title), content=VALUES(content), excerpt=VALUES(excerpt),
                           meta_title=VALUES(meta_title), meta_description=VALUES(meta_description)");

    $count = 0;
    foreach ($files as $file) {
        if (basename($file) === 'README.md') continue;

        $slug = basename($file, '.html');
        $html = file_get_contents($file);

        // Extraire le titre
        preg_match('/<title>([^<|]+)/i', $html, $titleMatch);
        $title = trim($titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug)));

        // Extraire la meta description
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)/i', $html, $descMatch);
        $metaDesc = $descMatch[1] ?? '';

        // Extraire le contenu
        $content = $html;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bodyMatch)) {
            $content = $bodyMatch[1];
        }

        // Nettoyer
        $content = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $content);
        $content = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $content);
        $content = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $content);
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);

        // Extrait
        $excerpt = substr(strip_tags($content), 0, 250);
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);

        // Catégorie par défaut
        $category = 'conseils';
        if (strpos($slug, 'guide') !== false) $category = 'tutoriels';
        if (strpos($slug, 'nouveau') !== false) $category = 'nouveautes';

        try {
            $stmt->execute([$title, $slug, $content, $excerpt, $title, $metaDesc, $category]);
            $count++;
        } catch (Exception $e) {}
    }

    return ['imported' => $count, 'source' => $blogDir];
}

// Compter les éléments existants
function getCounts($pdo) {
    $counts = [];
    try {
        $counts['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $counts['categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $counts['pages'] = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
        $counts['blog'] = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
        $counts['quotes'] = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
    } catch (Exception $e) {
        $counts = ['products' => 0, 'categories' => 0, 'pages' => 0, 'blog' => 0, 'quotes' => 0];
    }
    return $counts;
}

try {
    $pdo = Database::getInstance()->getConnection();
    $counts = getCounts($pdo);
    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $counts = ['products' => 0, 'categories' => 0, 'pages' => 0, 'blog' => 0, 'quotes' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Contenu - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --success: #34c759;
            --warning: #ff9500;
            --danger: #ff3b30;
            --dark: #1d1d1f;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-500: #86868b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); min-height: 100vh; }

        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }

        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 32px; color: var(--primary); margin-bottom: 8px; }
        .header p { color: var(--gray-500); }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .card h2 { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .status-bar {
            display: flex;
            align-items: center;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .status-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .status-error { background: rgba(255,59,48,0.1); color: var(--danger); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 12px; color: var(--gray-500); margin-top: 4px; }

        .import-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }

        .import-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--dark);
        }
        .import-btn:hover { border-color: var(--primary); background: rgba(255,75,38,0.03); }
        .import-btn .icon { font-size: 32px; margin-bottom: 12px; }
        .import-btn .title { font-weight: 700; margin-bottom: 4px; }
        .import-btn .desc { font-size: 12px; color: var(--gray-500); text-align: center; }

        .btn-full {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 16px;
        }
        .btn-full:hover { background: #E63910; }

        .results {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-200);
        }
        .result-item:last-child { border-bottom: none; }
        .result-value { font-weight: 700; color: var(--success); }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--gray-500);
            text-decoration: none;
        }
        .back-link:hover { color: var(--primary); }

        .alert { padding: 16px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: rgba(52,199,89,0.1); color: var(--success); }
        .alert-error { background: rgba(255,59,48,0.1); color: var(--danger); }
        .alert-info { background: rgba(0,122,255,0.1); color: #007AFF; }

        .credentials {
            background: #1a1a1c;
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        .credentials h3 { color: var(--primary); margin-bottom: 12px; }
        .credentials p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Retour au dashboard</a>

        <div class="header">
            <h1>🚀 Import du Contenu</h1>
            <p>Importer tout le contenu HTML existant dans la base de données</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($results && !$error): ?>
        <div class="alert alert-success">✅ Import terminé avec succès !</div>
        <div class="results">
            <?php if (isset($results['tables'])): ?>
            <div class="result-item">
                <span>📋 Tables créées</span>
                <span class="result-value"><?php echo implode(', ', $results['tables']['created'] ?? []); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($results['categories'])): ?>
            <div class="result-item">
                <span>📁 Catégories</span>
                <span class="result-value"><?php echo $results['categories']['imported'] ?? 0; ?> importées</span>
            </div>
            <?php endif; ?>
            <?php if (isset($results['products'])): ?>
            <div class="result-item">
                <span>📦 Produits</span>
                <span class="result-value"><?php echo $results['products']['imported'] ?? 0; ?> importés</span>
            </div>
            <?php endif; ?>
            <?php if (isset($results['pages'])): ?>
            <div class="result-item">
                <span>📄 Pages</span>
                <span class="result-value"><?php echo $results['pages']['imported'] ?? 0; ?> importées</span>
            </div>
            <?php endif; ?>
            <?php if (isset($results['blog'])): ?>
            <div class="result-item">
                <span>📝 Articles blog</span>
                <span class="result-value"><?php echo $results['blog']['imported'] ?? 0; ?> importés</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($results['tables'])): ?>
        <div class="credentials">
            <h3>🔐 Identifiants Admin</h3>
            <p><strong>Utilisateur :</strong> admin</p>
            <p><strong>Mot de passe :</strong> admin123</p>
            <p style="color: #ff9500; margin-top: 12px;">⚠️ Changez ce mot de passe après connexion !</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- État de la connexion -->
        <div class="card">
            <h2>📊 État actuel</h2>

            <?php if ($dbConnected): ?>
            <div class="status-bar status-success">
                ✅ Connexion à la base de données OK
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $counts['products']; ?></div>
                    <div class="stat-label">Produits</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $counts['categories']; ?></div>
                    <div class="stat-label">Catégories</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $counts['pages']; ?></div>
                    <div class="stat-label">Pages</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $counts['blog']; ?></div>
                    <div class="stat-label">Articles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $counts['quotes']; ?></div>
                    <div class="stat-label">Devis</div>
                </div>
            </div>
            <?php else: ?>
            <div class="status-bar status-error">
                ❌ Impossible de se connecter à la base de données
            </div>
            <p>Vérifiez que le mot de passe est correct dans <code>config/database.php</code></p>
            <?php endif; ?>
        </div>

        <!-- Actions d'import -->
        <div class="card">
            <h2>📥 Actions d'import</h2>

            <form method="POST">
                <div class="import-grid">
                    <button type="submit" name="action" value="init_tables" class="import-btn">
                        <div class="icon">🗄️</div>
                        <div class="title">1. Créer les tables</div>
                        <div class="desc">Initialise la BDD et crée l'admin</div>
                    </button>

                    <button type="submit" name="action" value="import_categories" class="import-btn">
                        <div class="icon">📁</div>
                        <div class="title">2. Catégories</div>
                        <div class="desc">Sports et familles de produits</div>
                    </button>

                    <button type="submit" name="action" value="import_products" class="import-btn">
                        <div class="icon">📦</div>
                        <div class="title">3. Produits</div>
                        <div class="desc">~1700 produits depuis CSV</div>
                    </button>

                    <button type="submit" name="action" value="import_pages" class="import-btn">
                        <div class="icon">📄</div>
                        <div class="title">4. Pages</div>
                        <div class="desc">Pages info (CGV, FAQ, etc.)</div>
                    </button>

                    <button type="submit" name="action" value="import_blog" class="import-btn">
                        <div class="icon">📝</div>
                        <div class="title">5. Blog</div>
                        <div class="desc">Articles de blog</div>
                    </button>
                </div>

                <button type="submit" name="action" value="import_all" class="btn-full">
                    🚀 TOUT IMPORTER D'UN COUP
                </button>
            </form>
        </div>

        <!-- Instructions -->
        <div class="card">
            <h2>📋 Comment ça marche ?</h2>
            <ol style="padding-left: 20px; line-height: 2;">
                <li><strong>Créer les tables</strong> - Initialise la base de données</li>
                <li><strong>Importer les catégories</strong> - Sports et familles de produits</li>
                <li><strong>Importer les produits</strong> - Depuis le fichier CSV (~1700 produits)</li>
                <li><strong>Importer les pages</strong> - Contenu HTML des pages info (FAQ, CGV, Contact...)</li>
                <li><strong>Importer le blog</strong> - Articles de blog existants</li>
            </ol>
            <p style="margin-top: 16px; color: var(--gray-500);">
                Une fois l'import terminé, vous pourrez modifier tout le contenu depuis le panneau d'administration !
            </p>
        </div>
    </div>
</body>
</html>
