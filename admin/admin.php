<?php
/**
 * FLARE CUSTOM - Administration Professionnelle
 * Interface style WordPress/Shopify
 *
 * SECURITY: CSRF, XSS, SQL Injection protected
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/page-builder-modules.php';

// ============ SECURITY FUNCTIONS ============

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Encryption for sensitive data (API keys, SMTP passwords)
function getEncryptionKey() {
    return hash('sha256', DB_PASS . '_flare_secure_key_2024', true);
}

function encryptSensitive($data) {
    if (empty($data)) return '';
    $key = getEncryptionKey();
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptSensitive($data) {
    if (empty($data)) return '';
    try {
        $key = getEncryptionKey();
        $decoded = base64_decode($data);
        if (strlen($decoded) < 17) return $data; // Not encrypted
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : $data;
    } catch (Exception $e) {
        return $data; // Return as-is if decryption fails
    }
}

// Rate limiting for login
function checkLoginRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];

    // Reset after 15 minutes
    if (time() - $attempts['time'] > 900) {
        $attempts = ['count' => 0, 'time' => time()];
    }

    $_SESSION[$key] = $attempts;
    return $attempts['count'] < 5; // Max 5 attempts per 15 minutes
}

function incrementLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    $_SESSION[$key]['count']++;
}

function resetLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

// ============ INITIALIZATION ============

$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;
$tab = $_GET['tab'] ?? 'general';

// Sanitize inputs
$isNew = ($id === 'new');
$id = ($id !== null && $id !== 'new') ? intval($id) : null;
$page = preg_replace('/[^a-z_]/', '', $page);

// Auth check
if ($page !== 'login' && !isset($_SESSION['admin_user'])) {
    $page = 'login';
}

// CSRF check for POST requests (except login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page !== 'login') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('<div style="font-family:sans-serif;padding:50px;text-align:center;"><h1>Erreur de sécurité</h1><p>Token CSRF invalide ou expiré.</p><a href="admin.php" style="color:#FF4B26;">Retour à l\'admin</a></div>');
    }
}

// DB Connection
$pdo = null;
$dbError = null;
try {
    $pdo = Database::getInstance()->getConnection();

    // Ensure page_blocks column exists
    try {
        $pdo->exec("ALTER TABLE pages ADD COLUMN page_blocks LONGTEXT AFTER content");
    } catch (PDOException $e) {
        // Column likely already exists, ignore
    }

    // Create page_products table for managing products on pages
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS page_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_type VARCHAR(50) NOT NULL,
            page_slug VARCHAR(255) NOT NULL,
            product_id INT NOT NULL,
            position INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_page_product (page_type, page_slug, product_id),
            INDEX idx_page (page_type, page_slug),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        // Table likely already exists
    }

    // Create product_photos table for unlimited product photos
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            url VARCHAR(500) NOT NULL,
            filename VARCHAR(255),
            alt_text VARCHAR(255),
            title VARCHAR(255),
            type ENUM('main', 'gallery', 'thumbnail', 'hover', 'zoom') DEFAULT 'gallery',
            ordre INT DEFAULT 0,
            width INT,
            height INT,
            size_bytes INT,
            mime_type VARCHAR(100),
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            INDEX idx_type (type),
            INDEX idx_ordre (ordre),
            INDEX idx_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        // Table likely already exists
    }

    // Add new product columns for enhanced settings
    $newColumns = [
        'featured' => 'BOOLEAN DEFAULT FALSE',
        'is_new' => 'BOOLEAN DEFAULT FALSE',
        'on_sale' => 'BOOLEAN DEFAULT FALSE',
        'sort_order' => 'INT DEFAULT 0',
        'related_products' => 'JSON',
        'stock_status' => "VARCHAR(50) DEFAULT 'in_stock'",
        'slug' => 'VARCHAR(255) DEFAULT NULL',
        'meta_title' => 'VARCHAR(255) DEFAULT NULL',
        'meta_description' => 'TEXT DEFAULT NULL',
        'tab_description' => 'LONGTEXT DEFAULT NULL',
        'tab_specifications' => 'LONGTEXT DEFAULT NULL',
        'tab_sizes' => 'LONGTEXT DEFAULT NULL',
        'tab_templates' => 'LONGTEXT DEFAULT NULL',
        'tab_faq' => 'LONGTEXT DEFAULT NULL',
        'configurator_config' => 'LONGTEXT DEFAULT NULL',
        'size_chart_id' => 'INT DEFAULT NULL',
        // Prix enfants par quantité (-10% du prix adulte par défaut)
        'prix_enfant_1' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_5' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_10' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_20' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_50' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_100' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_250' => 'DECIMAL(10,2) DEFAULT NULL',
        'prix_enfant_500' => 'DECIMAL(10,2) DEFAULT NULL'
    ];
    foreach ($newColumns as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN $col $definition");
        } catch (PDOException $e) {
            // Column likely already exists
        }
    }

    // Create category_pages table for dynamic category pages
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS category_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            meta_title VARCHAR(255),
            meta_description TEXT,

            -- Hero section
            hero_title VARCHAR(255),
            hero_subtitle TEXT,
            hero_image VARCHAR(500),
            hero_cta_text VARCHAR(100),
            hero_cta_link VARCHAR(255),

            -- Trust bar (JSON: [{icon, value, label}])
            trust_bar JSON,

            -- Products section
            products_title VARCHAR(255),
            products_subtitle TEXT,
            show_filters BOOLEAN DEFAULT TRUE,
            filter_sports JSON,
            filter_genres JSON,

            -- CTA section (Créons ensemble)
            cta_title VARCHAR(255),
            cta_subtitle TEXT,
            cta_features JSON,
            cta_button_text VARCHAR(100),
            cta_button_link VARCHAR(255),
            cta_whatsapp VARCHAR(50),

            -- Excellence section (3 columns)
            excellence_title VARCHAR(255),
            excellence_subtitle TEXT,
            excellence_columns JSON,

            -- Technology section
            tech_title VARCHAR(255),
            tech_content TEXT,
            tech_stats JSON,

            -- Features section (tissus, service, etc.)
            features_sections JSON,

            -- Testimonials
            testimonials JSON,

            -- FAQ
            faq_title VARCHAR(255),
            faq_items JSON,

            -- Guide/SEO content
            guide_title VARCHAR(255),
            guide_content LONGTEXT,

            -- Settings
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {
        // Table likely already exists
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// LOGIN with rate limiting
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkLoginRateLimit()) {
        $loginError = "Trop de tentatives. Réessayez dans 15 minutes.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($pdo && $username && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_user'] = $user;
                $_SESSION['admin_login_time'] = time();
                resetLoginAttempts();
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                header('Location: admin.php');
                exit;
            }
            incrementLoginAttempts();
            $loginError = "Identifiants incorrects";
        }
    }
}

// LOGOUT
if ($page === 'logout') {
    session_destroy();
    header('Location: admin.php?page=login');
    exit;
}

// ACTIONS
$toast = '';
if ($action && $pdo) {
    try {
        switch ($action) {
            case 'save_product':
                $fields = ['nom', 'sport', 'famille', 'description', 'description_seo', 'tissu', 'grammage',
                    'prix_1', 'prix_5', 'prix_10', 'prix_20', 'prix_50', 'prix_100', 'prix_250', 'prix_500',
                    'prix_enfant_1', 'prix_enfant_5', 'prix_enfant_10', 'prix_enfant_20', 'prix_enfant_50', 'prix_enfant_100', 'prix_enfant_250', 'prix_enfant_500',
                    'photo_1', 'photo_2', 'photo_3', 'photo_4', 'photo_5', 'genre', 'finition',
                    'meta_title', 'meta_description', 'tab_description', 'tab_specifications',
                    'tab_sizes', 'tab_templates', 'tab_faq', 'configurator_config', 'size_chart_id',
                    'active', 'stock_status', 'slug', 'url', 'featured', 'is_new', 'on_sale', 'sort_order'];
                $set = implode('=?, ', $fields) . '=?';
                $values = array_map(fn($f) => $_POST[$f] ?? null, $fields);

                // Convertir size_chart_id en int ou null
                $idx = array_search('size_chart_id', $fields);
                if ($idx !== false && $values[$idx] !== null) {
                    $values[$idx] = $values[$idx] === '' ? null : intval($values[$idx]);
                }

                // Convertir les booléens (checkboxes)
                foreach (['featured', 'is_new', 'on_sale'] as $boolField) {
                    $idx = array_search($boolField, $fields);
                    if ($idx !== false) {
                        $values[$idx] = isset($_POST[$boolField]) ? 1 : 0;
                    }
                }

                // Traiter 'active' séparément (c'est un radio button, pas checkbox)
                $idx = array_search('active', $fields);
                if ($idx !== false) {
                    $values[$idx] = ($_POST['active'] ?? '1') === '1' ? 1 : 0;
                }

                // Convertir sort_order en int
                $idx = array_search('sort_order', $fields);
                if ($idx !== false) {
                    $values[$idx] = intval($values[$idx] ?? 0);
                }

                // Gérer les étiquettes (combiner checkboxes + custom)
                $etiquettes = $_POST['etiquettes'] ?? [];
                $customTags = trim($_POST['etiquettes_custom'] ?? '');
                if ($customTags) {
                    $etiquettes = array_merge($etiquettes, array_map('trim', explode(',', $customTags)));
                }
                $etiquettesStr = implode(',', array_unique(array_filter($etiquettes)));

                // Gérer les produits liés (JSON)
                $relatedProducts = $_POST['related_products'] ?? [];
                $relatedProductsJson = json_encode(array_map('intval', $relatedProducts));

                // Récupérer la référence (pour nouveau produit)
                $reference = trim($_POST['reference'] ?? '');

                if ($id) {
                    // Mise à jour d'un produit existant
                    // Debug: log les valeurs reçues pour les onglets
                    error_log("SAVE_PRODUCT ID=$id");
                    error_log("tab_description: " . substr($_POST['tab_description'] ?? 'NULL', 0, 100));
                    error_log("configurator_config: " . substr($_POST['configurator_config'] ?? 'NULL', 0, 100));
                    error_log("meta_title: " . ($_POST['meta_title'] ?? 'NULL'));

                    $values[] = $id;
                    $stmt = $pdo->prepare("UPDATE products SET $set, etiquettes=?, related_products=?, updated_at=NOW() WHERE id=?");
                    $result = $stmt->execute(array_merge($values, [$etiquettesStr, $relatedProductsJson]));
                    if (!$result) {
                        $toast = 'Erreur SQL: ' . implode(', ', $stmt->errorInfo());
                        break;
                    }
                    $productId = $id;
                } else {
                    // Création d'un nouveau produit
                    if (empty($reference)) {
                        $reference = 'FLARE-' . strtoupper(substr(md5(uniqid()), 0, 6));
                    }
                    // Vérifier que la référence n'existe pas
                    $checkRef = $pdo->prepare("SELECT id FROM products WHERE reference = ?");
                    $checkRef->execute([$reference]);
                    if ($checkRef->fetch()) {
                        $toast = 'Erreur: Cette référence existe déjà';
                        break;
                    }
                    // Construire la requête INSERT avec created_at et updated_at
                    $insertFields = array_merge(['reference'], $fields);
                    $insertFieldNames = implode(', ', $insertFields) . ', etiquettes, related_products, created_at, updated_at';
                    $insertPlaceholders = implode(', ', array_fill(0, count($insertFields) + 2, '?')) . ', NOW(), NOW()';
                    $insertValues = array_merge([$reference], $values, [$etiquettesStr, $relatedProductsJson]);

                    $pdo->prepare("INSERT INTO products ($insertFieldNames) VALUES ($insertPlaceholders)")
                        ->execute($insertValues);
                    $productId = $pdo->lastInsertId();

                    // Rediriger vers la page du nouveau produit
                    header("Location: ?page=product&id=$productId&toast=created");
                    exit;
                }

                // Sauvegarder les templates associés au produit
                $productTemplates = $_POST['product_templates'] ?? [];
                try {
                    // Supprimer les anciennes associations
                    $pdo->prepare("DELETE FROM template_products WHERE product_id = ?")->execute([$productId]);
                    // Ajouter les nouvelles associations
                    if (!empty($productTemplates)) {
                        $stmt = $pdo->prepare("INSERT INTO template_products (template_id, product_id) VALUES (?, ?)");
                        foreach ($productTemplates as $templateId) {
                            $stmt->execute([(int)$templateId, $productId]);
                        }
                    }
                } catch (Exception $e) {
                    // Table peut ne pas exister encore
                }

                $toast = 'Produit enregistré';
                break;

            case 'add_photo':
                $productId = intval($_POST['product_id'] ?? 0);
                $photoUrl = trim($_POST['photo_url'] ?? '');
                $altText = trim($_POST['alt_text'] ?? '');
                if ($productId && $photoUrl) {
                    $maxOrdre = $pdo->prepare("SELECT MAX(ordre) FROM product_photos WHERE product_id=?");
                    $maxOrdre->execute([$productId]);
                    $ordre = intval($maxOrdre->fetchColumn()) + 1;
                    // Vérifie s'il y a déjà une photo principale
                    $hasMain = $pdo->prepare("SELECT COUNT(*) FROM product_photos WHERE product_id=? AND type='main'");
                    $hasMain->execute([$productId]);
                    $photoType = ($hasMain->fetchColumn() == 0 && $ordre == 1) ? 'main' : 'gallery';
                    $pdo->prepare("INSERT INTO product_photos (product_id, url, alt_text, ordre, type, active) VALUES (?,?,?,?,?,1)")
                        ->execute([$productId, $photoUrl, $altText, $ordre, $photoType]);
                    // Si c'est la photo principale, mettre à jour photo_1 du produit
                    if ($photoType === 'main') {
                        $pdo->prepare("UPDATE products SET photo_1=? WHERE id=?")->execute([$photoUrl, $productId]);
                    }
                    $toast = 'Photo ajoutée';
                }
                break;

            case 'delete_photo':
                $photoId = intval($_POST['photo_id'] ?? 0);
                if ($photoId) {
                    $pdo->prepare("DELETE FROM product_photos WHERE id=?")->execute([$photoId]);
                    $toast = 'Photo supprimée';
                }
                break;

            case 'set_main_photo':
                $photoId = intval($_POST['photo_id'] ?? 0);
                $productId = intval($_POST['product_id'] ?? 0);
                if ($photoId && $productId) {
                    // Enlever le type 'main' des autres photos
                    $pdo->prepare("UPDATE product_photos SET type='gallery' WHERE product_id=? AND type='main'")->execute([$productId]);
                    // Définir celle-ci comme principale
                    $pdo->prepare("UPDATE product_photos SET type='main' WHERE id=?")->execute([$photoId]);
                    // Mettre à jour photo_1 du produit
                    $stmt = $pdo->prepare("SELECT url FROM product_photos WHERE id=?");
                    $stmt->execute([$photoId]);
                    $url = $stmt->fetchColumn();
                    if ($url) {
                        $pdo->prepare("UPDATE products SET photo_1=? WHERE id=?")->execute([$url, $productId]);
                    }
                    $toast = 'Photo principale définie';
                }
                break;

            case 'save_category':
                if ($id) {
                    $pdo->prepare("UPDATE categories SET nom=?, slug=?, type=?, description=?, image=? WHERE id=?")
                        ->execute([$_POST['nom'], $_POST['slug'], $_POST['type'], $_POST['description'], $_POST['image'], $id]);
                } else {
                    $pdo->prepare("INSERT INTO categories (nom, slug, type, description, image, active) VALUES (?,?,?,?,?,1)")
                        ->execute([$_POST['nom'], $_POST['slug'], $_POST['type'], $_POST['description'], $_POST['image']]);
                }
                $toast = 'Catégorie enregistrée';
                break;

            case 'save_category_page':
                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug'] ?? ''));
                if (empty($slug)) {
                    $toast = 'Erreur: Slug requis';
                    break;
                }

                // Préparer les données JSON
                $trustBar = json_encode($_POST['trust_bar'] ?? [], JSON_UNESCAPED_UNICODE);
                $ctaFeatures = json_encode(array_filter(array_map('trim', explode("\n", $_POST['cta_features'] ?? ''))), JSON_UNESCAPED_UNICODE);
                $excellenceColumns = json_encode($_POST['excellence_columns'] ?? [], JSON_UNESCAPED_UNICODE);
                $techStats = json_encode($_POST['tech_stats'] ?? [], JSON_UNESCAPED_UNICODE);
                $featuresSections = json_encode($_POST['features_sections'] ?? [], JSON_UNESCAPED_UNICODE);
                $testimonials = json_encode($_POST['testimonials'] ?? [], JSON_UNESCAPED_UNICODE);
                $faqItems = json_encode($_POST['faq_items'] ?? [], JSON_UNESCAPED_UNICODE);
                $filterSports = json_encode(array_filter(array_map('trim', explode("\n", $_POST['filter_sports'] ?? ''))), JSON_UNESCAPED_UNICODE);
                $filterGenres = json_encode(array_filter(array_map('trim', explode("\n", $_POST['filter_genres'] ?? ''))), JSON_UNESCAPED_UNICODE);

                $fields = [
                    'slug', 'title', 'meta_title', 'meta_description',
                    'hero_title', 'hero_subtitle', 'hero_image', 'hero_cta_text', 'hero_cta_link',
                    'products_title', 'products_subtitle', 'show_filters',
                    'cta_title', 'cta_subtitle', 'cta_button_text', 'cta_button_link', 'cta_whatsapp',
                    'excellence_title', 'excellence_subtitle',
                    'tech_title', 'tech_content',
                    'faq_title', 'guide_title', 'guide_content', 'active'
                ];

                $values = [
                    $slug,
                    $_POST['title'] ?? '',
                    $_POST['meta_title'] ?? '',
                    $_POST['meta_description'] ?? '',
                    $_POST['hero_title'] ?? '',
                    $_POST['hero_subtitle'] ?? '',
                    $_POST['hero_image'] ?? '',
                    $_POST['hero_cta_text'] ?? '',
                    $_POST['hero_cta_link'] ?? '',
                    $_POST['products_title'] ?? '',
                    $_POST['products_subtitle'] ?? '',
                    isset($_POST['show_filters']) ? 1 : 0,
                    $_POST['cta_title'] ?? '',
                    $_POST['cta_subtitle'] ?? '',
                    $_POST['cta_button_text'] ?? '',
                    $_POST['cta_button_link'] ?? '',
                    $_POST['cta_whatsapp'] ?? '',
                    $_POST['excellence_title'] ?? '',
                    $_POST['excellence_subtitle'] ?? '',
                    $_POST['tech_title'] ?? '',
                    $_POST['tech_content'] ?? '',
                    $_POST['faq_title'] ?? '',
                    $_POST['guide_title'] ?? '',
                    $_POST['guide_content'] ?? '',
                    isset($_POST['active']) ? 1 : 0
                ];

                // Ajouter les champs JSON
                $jsonFields = ['trust_bar', 'filter_sports', 'filter_genres', 'cta_features', 'excellence_columns', 'tech_stats', 'features_sections', 'testimonials', 'faq_items'];
                $jsonValues = [$trustBar, $filterSports, $filterGenres, $ctaFeatures, $excellenceColumns, $techStats, $featuresSections, $testimonials, $faqItems];

                if ($id) {
                    $set = implode('=?, ', $fields) . '=?, ' . implode('=?, ', $jsonFields) . '=?';
                    $allValues = array_merge($values, $jsonValues, [$id]);
                    $pdo->prepare("UPDATE category_pages SET $set WHERE id=?")->execute($allValues);
                    $savedSlug = $slug;
                } else {
                    $allFields = array_merge($fields, $jsonFields);
                    $placeholders = implode(',', array_fill(0, count($allFields), '?'));
                    $pdo->prepare("INSERT INTO category_pages (" . implode(',', $allFields) . ") VALUES ($placeholders)")
                        ->execute(array_merge($values, $jsonValues));
                    $id = $pdo->lastInsertId();
                    $savedSlug = $slug;
                }

                // Sauvegarder les produits associés
                $pdo->prepare("DELETE FROM page_products WHERE page_type='category_page' AND page_slug=?")->execute([$savedSlug]);
                $productIds = $_POST['page_products'] ?? [];
                if (!empty($productIds)) {
                    $insertStmt = $pdo->prepare("INSERT INTO page_products (page_type, page_slug, product_id, position) VALUES ('category_page', ?, ?, ?)");
                    foreach ($productIds as $pos => $prodId) {
                        $insertStmt->execute([$savedSlug, intval($prodId), $pos]);
                    }
                }

                $toast = 'Page catégorie enregistrée';
                header("Location: ?page=category_page&id=$id&toast=" . urlencode($toast));
                exit;

            case 'delete_category_page':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT slug FROM category_pages WHERE id=?");
                    $stmt->execute([$id]);
                    $row = $stmt->fetch();
                    if ($row) {
                        $pdo->prepare("DELETE FROM page_products WHERE page_type='category_page' AND page_slug=?")->execute([$row['slug']]);
                        $pdo->prepare("DELETE FROM category_pages WHERE id=?")->execute([$id]);
                        $toast = 'Page supprimée';
                    }
                }
                header("Location: ?page=category_pages&toast=" . urlencode($toast));
                exit;

            case 'save_page':
                // Sauvegarder directement dans le fichier HTML via type et slug
                $pageType = $_POST['page_type'] ?? 'info';
                $pageSlug = $_POST['page_slug'] ?? '';
                $content = $_POST['content'] ?? '';

                // Construire le chemin selon le type
                $directories = [
                    'info' => __DIR__ . '/../pages/info/',
                    'category' => __DIR__ . '/../pages/products/'
                ];

                if (!$pageSlug || !isset($directories[$pageType])) {
                    $toast = 'Erreur: Type ou slug invalide';
                    break;
                }

                // Nettoyer le slug (sécurité)
                $pageSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $pageSlug);
                $filePath = $directories[$pageType] . $pageSlug . '.html';

                if (!file_exists($filePath)) {
                    $toast = 'Erreur: Fichier introuvable';
                    break;
                }

                // Sauvegarder le fichier
                if (file_put_contents($filePath, $content) !== false) {
                    $toast = 'Page HTML sauvegardée avec succès !';
                } else {
                    $toast = 'Erreur: Impossible de sauvegarder le fichier';
                }

                // Sauvegarder les produits associés (pour pages catégories)
                if ($pageType === 'category' && isset($_POST['page_products'])) {
                    // Supprimer les anciens
                    $stmt = $pdo->prepare("DELETE FROM page_products WHERE page_type = ? AND page_slug = ?");
                    $stmt->execute([$pageType, $pageSlug]);

                    // Ajouter les nouveaux
                    $productIds = array_filter(array_map('intval', $_POST['page_products']));
                    if (!empty($productIds)) {
                        $insertStmt = $pdo->prepare("INSERT INTO page_products (page_type, page_slug, product_id, position) VALUES (?, ?, ?, ?)");
                        $pos = 0;
                        foreach ($productIds as $prodId) {
                            $insertStmt->execute([$pageType, $pageSlug, $prodId, $pos++]);
                        }
                        $toast .= ' + ' . count($productIds) . ' produits associés';
                    }
                }
                break;

            case 'import_html_pages':
                $importType = $_POST['import_type'] ?? 'info';
                $dir = $importType === 'category' ? __DIR__ . '/../pages/products/' : __DIR__ . '/../pages/info/';
                $dbType = $importType === 'category' ? 'category' : 'info';
                $imported = 0;

                if (is_dir($dir)) {
                    $files = glob($dir . '*.html');
                    foreach ($files as $file) {
                        $slug = basename($file, '.html');
                        // Vérifier si existe déjà
                        $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
                        $stmt->execute([$slug]);
                        if (!$stmt->fetch()) {
                            $html = file_get_contents($file);
                            // Extraire titre et meta description
                            preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
                            preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $descMatch);

                            $title = $titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug));
                            $metaDesc = $descMatch[1] ?? '';

                            // Filtres auto pour catégories
                            $filters = [];
                            if ($dbType === 'category') {
                                $slugLower = strtolower($slug);
                                $sportMap = ['football'=>'Football','rugby'=>'Rugby','basketball'=>'Basketball','handball'=>'Handball','volleyball'=>'Volleyball','running'=>'Running','cyclisme'=>'Cyclisme','triathlon'=>'Triathlon','petanque'=>'Pétanque'];
                                foreach ($sportMap as $k => $v) {
                                    if (strpos($slugLower, $k) !== false) {
                                        $filters['sport'] = $v;
                                        break;
                                    }
                                }
                            }

                            $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description, content, product_filters) VALUES (?,?,?,?,?,?,?,?)");
                            $stmt->execute([$slug, $title, $dbType, 'published', $title, $metaDesc, $html, json_encode($filters)]);
                            $imported++;
                        }
                    }
                }
                $toast = "$imported pages $importType importées !";
                break;

            case 'import_html_blog':
                $dir = __DIR__ . '/../pages/blog/';
                $imported = 0;

                if (is_dir($dir)) {
                    $files = glob($dir . '*.html');
                    foreach ($files as $file) {
                        $slug = basename($file, '.html');
                        // Vérifier si existe déjà
                        $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ?");
                        $stmt->execute([$slug]);
                        if (!$stmt->fetch()) {
                            $html = file_get_contents($file);
                            // Extraire titre et meta description
                            preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
                            preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $descMatch);

                            $title = $titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug));
                            $metaDesc = $descMatch[1] ?? '';

                            $stmt = $pdo->prepare("INSERT INTO blog_posts (slug, title, status, meta_title, meta_description, content, published_at) VALUES (?,?,?,?,?,?,NOW())");
                            $stmt->execute([$slug, $title, 'published', $title, $metaDesc, $html]);
                            $imported++;
                        }
                    }
                }
                $toast = "$imported articles blog importés !";
                break;

            case 'save_blog':
                if ($id) {
                    $pdo->prepare("UPDATE blog_posts SET title=?, slug=?, content=?, excerpt=?, featured_image=?, category=?, meta_title=?, meta_description=?, status=? WHERE id=?")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['featured_image'], $_POST['category'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status'], $id]);
                } else {
                    $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, category, meta_title, meta_description, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['featured_image'], $_POST['category'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status']]);
                }
                $toast = 'Article enregistré';
                break;

            case 'update_quote':
                $pdo->prepare("UPDATE quotes SET status=?, notes=? WHERE id=?")->execute([$_POST['status'], $_POST['notes'], $id]);
                $toast = 'Devis mis à jour';
                break;

            case 'delete':
                $table = $_POST['table'] ?? '';
                if (in_array($table, ['products', 'categories', 'pages', 'blog_posts'])) {
                    $pdo->prepare("UPDATE $table SET active=0 WHERE id=?")->execute([$id]);
                    $toast = 'Élément supprimé';
                }
                break;

            case 'delete_product':
                if ($id) {
                    // Supprimer les photos associées
                    $pdo->prepare("DELETE FROM product_photos WHERE product_id = ?")->execute([$id]);
                    // Supprimer les associations templates
                    try {
                        $pdo->prepare("DELETE FROM template_products WHERE product_id = ?")->execute([$id]);
                    } catch (Exception $e) {}
                    // Supprimer le produit
                    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                    $toast = 'Produit supprimé définitivement';
                }
                break;

            case 'save_settings':
                $settings = [
                    'site_name' => $_POST['site_name'] ?? 'FLARE CUSTOM',
                    'site_tagline' => $_POST['site_tagline'] ?? '',
                    'site_email' => $_POST['site_email'] ?? '',
                    'site_phone' => $_POST['site_phone'] ?? '',
                    'site_address' => $_POST['site_address'] ?? '',
                    'site_logo' => $_POST['site_logo'] ?? '',
                    'site_favicon' => $_POST['site_favicon'] ?? '',
                    'social_facebook' => $_POST['social_facebook'] ?? '',
                    'social_instagram' => $_POST['social_instagram'] ?? '',
                    'social_twitter' => $_POST['social_twitter'] ?? '',
                    'social_linkedin' => $_POST['social_linkedin'] ?? '',
                    'social_youtube' => $_POST['social_youtube'] ?? '',
                    'smtp_host' => $_POST['smtp_host'] ?? '',
                    'smtp_port' => $_POST['smtp_port'] ?? '587',
                    'smtp_user' => $_POST['smtp_user'] ?? '',
                    'smtp_pass' => $_POST['smtp_pass'] ?? '',
                    'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
                    'smtp_from_name' => $_POST['smtp_from_name'] ?? '',
                    'payment_mode' => $_POST['payment_mode'] ?? 'quote',
                    'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                    'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                    'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                    'paypal_secret' => $_POST['paypal_secret'] ?? '',
                    'shipping_france' => $_POST['shipping_france'] ?? '0',
                    'shipping_europe' => $_POST['shipping_europe'] ?? '0',
                    'shipping_world' => $_POST['shipping_world'] ?? '0',
                    'shipping_free_above' => $_POST['shipping_free_above'] ?? '0',
                    'default_delivery_time' => $_POST['default_delivery_time'] ?? '3-4 semaines',
                    'min_order_quantity' => $_POST['min_order_quantity'] ?? '1',
                    'tva_rate' => $_POST['tva_rate'] ?? '20',
                    'quote_validity_days' => $_POST['quote_validity_days'] ?? '30',
                    'quote_prefix' => $_POST['quote_prefix'] ?? 'DEV-',
                    'notification_email' => $_POST['notification_email'] ?? '',
                    'google_analytics' => $_POST['google_analytics'] ?? '',
                    'google_tag_manager' => $_POST['google_tag_manager'] ?? '',
                    'meta_pixel' => $_POST['meta_pixel'] ?? '',
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                    'maintenance_message' => $_POST['maintenance_message'] ?? '',
                    'configurator_design_flare' => isset($_POST['configurator_design_flare']) ? '1' : '0',
                    'configurator_design_client' => isset($_POST['configurator_design_client']) ? '1' : '0',
                    'configurator_design_template' => isset($_POST['configurator_design_template']) ? '1' : '0',
                    'configurator_perso_nom' => isset($_POST['configurator_perso_nom']) ? '1' : '0',
                    'configurator_perso_numero' => isset($_POST['configurator_perso_numero']) ? '1' : '0',
                    'configurator_perso_logo' => isset($_POST['configurator_perso_logo']) ? '1' : '0',
                    'configurator_perso_sponsor' => isset($_POST['configurator_perso_sponsor']) ? '1' : '0',
                    'configurator_sizes' => $_POST['configurator_sizes'] ?? 'XS,S,M,L,XL,XXL,3XL',
                    'configurator_sizes_kids' => $_POST['configurator_sizes_kids'] ?? '6ans,8ans,10ans,12ans,14ans',
                    'configurator_collars' => $_POST['configurator_collars'] ?? 'col_v,col_rond,col_polo',
                ];
                foreach ($settings as $key => $value) {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$key, $value]);
                }
                $toast = 'Paramètres enregistrés';
                break;

            case 'change_password':
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if ($new !== $confirm) {
                    $toast = 'Erreur: Les mots de passe ne correspondent pas';
                } elseif (strlen($new) < 6) {
                    $toast = 'Erreur: Le mot de passe doit faire au moins 6 caractères';
                } else {
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['admin_user']['id']]);
                    $user = $stmt->fetch();
                    if ($user && password_verify($current, $user['password'])) {
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_user']['id']]);
                        $toast = 'Mot de passe modifié avec succès';
                    } else {
                        $toast = 'Erreur: Mot de passe actuel incorrect';
                    }
                }
                break;

            case 'import_csv':
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    $imported = 0;
                    $updated = 0;
                    $errors = 0;
                    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                    $headers = fgetcsv($handle, 0, ';');
                    $headers = array_map(fn($h) => strtoupper(trim($h)), $headers);

                    while (($row = fgetcsv($handle, 0, ';')) !== false) {
                        if (count($row) < 5) continue;
                        $data = array_combine($headers, array_pad($row, count($headers), ''));

                        $reference = $data['REFERENCE_FLARE'] ?? $data['REFERENCE'] ?? '';
                        if (empty($reference)) continue;

                        try {
                            // Check if exists
                            $stmt = $pdo->prepare("SELECT id FROM products WHERE reference = ?");
                            $stmt->execute([$reference]);
                            $exists = $stmt->fetch();

                            $productData = [
                                'reference' => $reference,
                                'nom' => $data['TITRE_VENDEUR'] ?? $data['NOM'] ?? $reference,
                                'sport' => $data['SPORT'] ?? '',
                                'famille' => $data['FAMILLE_PRODUIT'] ?? $data['FAMILLE'] ?? '',
                                'description' => $data['DESCRIPTION'] ?? '',
                                'description_seo' => $data['DESCRIPTION_SEO'] ?? '',
                                'tissu' => $data['TISSU'] ?? '',
                                'grammage' => $data['GRAMMAGE'] ?? '',
                                'genre' => $data['GENRE'] ?? 'Mixte',
                                'finition' => $data['FINITION'] ?? '',
                                'prix_1' => floatval(str_replace(',', '.', $data['QTY_1'] ?? $data['PRIX_1'] ?? 0)),
                                'prix_5' => floatval(str_replace(',', '.', $data['QTY_5'] ?? $data['PRIX_5'] ?? 0)),
                                'prix_10' => floatval(str_replace(',', '.', $data['QTY_10'] ?? $data['PRIX_10'] ?? 0)),
                                'prix_20' => floatval(str_replace(',', '.', $data['QTY_20'] ?? $data['PRIX_20'] ?? 0)),
                                'prix_50' => floatval(str_replace(',', '.', $data['QTY_50'] ?? $data['PRIX_50'] ?? 0)),
                                'prix_100' => floatval(str_replace(',', '.', $data['QTY_100'] ?? $data['PRIX_100'] ?? 0)),
                                'prix_250' => floatval(str_replace(',', '.', $data['QTY_250'] ?? $data['PRIX_250'] ?? 0)),
                                'prix_500' => floatval(str_replace(',', '.', $data['QTY_500'] ?? $data['PRIX_500'] ?? 0)),
                                'photo_1' => $data['PHOTO_1'] ?? '',
                                'photo_2' => $data['PHOTO_2'] ?? '',
                                'photo_3' => $data['PHOTO_3'] ?? '',
                                'photo_4' => $data['PHOTO_4'] ?? '',
                                'photo_5' => $data['PHOTO_5'] ?? '',
                                'url' => $data['URL'] ?? '',
                            ];

                            if ($exists) {
                                $sql = "UPDATE products SET nom=?, sport=?, famille=?, description=?, description_seo=?, tissu=?, grammage=?, genre=?, finition=?, prix_1=?, prix_5=?, prix_10=?, prix_20=?, prix_50=?, prix_100=?, prix_250=?, prix_500=?, photo_1=?, photo_2=?, photo_3=?, photo_4=?, photo_5=?, url=?, updated_at=NOW() WHERE reference=?";
                                $pdo->prepare($sql)->execute([
                                    $productData['nom'], $productData['sport'], $productData['famille'], $productData['description'], $productData['description_seo'],
                                    $productData['tissu'], $productData['grammage'], $productData['genre'], $productData['finition'],
                                    $productData['prix_1'], $productData['prix_5'], $productData['prix_10'], $productData['prix_20'],
                                    $productData['prix_50'], $productData['prix_100'], $productData['prix_250'], $productData['prix_500'],
                                    $productData['photo_1'], $productData['photo_2'], $productData['photo_3'], $productData['photo_4'], $productData['photo_5'],
                                    $productData['url'], $reference
                                ]);
                                $updated++;
                            } else {
                                $sql = "INSERT INTO products (reference, nom, sport, famille, description, description_seo, tissu, grammage, genre, finition, prix_1, prix_5, prix_10, prix_20, prix_50, prix_100, prix_250, prix_500, photo_1, photo_2, photo_3, photo_4, photo_5, url, active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW())";
                                $pdo->prepare($sql)->execute([
                                    $productData['reference'], $productData['nom'], $productData['sport'], $productData['famille'], $productData['description'], $productData['description_seo'],
                                    $productData['tissu'], $productData['grammage'], $productData['genre'], $productData['finition'],
                                    $productData['prix_1'], $productData['prix_5'], $productData['prix_10'], $productData['prix_20'],
                                    $productData['prix_50'], $productData['prix_100'], $productData['prix_250'], $productData['prix_500'],
                                    $productData['photo_1'], $productData['photo_2'], $productData['photo_3'], $productData['photo_4'], $productData['photo_5'],
                                    $productData['url']
                                ]);
                                $imported++;
                            }
                        } catch (Exception $e) {
                            $errors++;
                        }
                    }
                    fclose($handle);
                    $toast = "Import terminé: $imported nouveaux, $updated mis à jour, $errors erreurs";
                } else {
                    $toast = 'Erreur: Fichier CSV non reçu';
                }
                break;
        }
    } catch (Exception $e) {
        $toast = 'Erreur: ' . $e->getMessage();
    }
}

// FETCH DATA
$data = [];
if ($pdo && $page !== 'login') {
    try {
        switch ($page) {
            case 'dashboard':
                $data['products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();
                $data['categories'] = $pdo->query("SELECT COUNT(*) FROM categories WHERE active=1")->fetchColumn();
                $data['pages'] = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
                $data['blog'] = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
                $data['quotes_pending'] = $pdo->query("SELECT COUNT(*) FROM quotes WHERE status='pending'")->fetchColumn();
                $data['quotes_total'] = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
                $data['recent_quotes'] = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC LIMIT 5")->fetchAll();
                $data['recent_products'] = $pdo->query("SELECT * FROM products WHERE active=1 ORDER BY updated_at DESC LIMIT 5")->fetchAll();
                break;

            case 'products':
                $where = "WHERE active=1";
                $params = [];
                if (!empty($_GET['search'])) {
                    $where .= " AND (nom LIKE ? OR reference LIKE ?)";
                    $params[] = '%'.$_GET['search'].'%';
                    $params[] = '%'.$_GET['search'].'%';
                }
                if (!empty($_GET['sport'])) {
                    $where .= " AND sport=?";
                    $params[] = $_GET['sport'];
                }
                $stmt = $pdo->prepare("SELECT * FROM products $where ORDER BY updated_at DESC LIMIT 100");
                $stmt->execute($params);
                $data['items'] = $stmt->fetchAll();
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport!='' AND active=1 ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                break;

            case 'product':
                $data['isNew'] = $isNew;
                if ($id && !$isNew) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                    // Récupérer les photos de la galerie
                    $stmt = $pdo->prepare("SELECT * FROM product_photos WHERE product_id=? ORDER BY ordre, id");
                    $stmt->execute([$id]);
                    $data['photos'] = $stmt->fetchAll();
                    // Récupérer les templates associés à ce produit
                    try {
                        $stmt = $pdo->prepare("SELECT template_id FROM template_products WHERE product_id = ?");
                        $stmt->execute([$id]);
                        $data['associated_templates'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (Exception $e) {
                        $data['associated_templates'] = [];
                    }
                } elseif ($isNew) {
                    // Nouveau produit - valeurs par défaut
                    $data['item'] = [
                        'id' => null,
                        'reference' => 'FLARE-' . strtoupper(substr(md5(uniqid()), 0, 6)),
                        'nom' => '',
                        'sport' => '',
                        'famille' => '',
                        'active' => 1
                    ];
                    $data['photos'] = [];
                    $data['associated_templates'] = [];
                }
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport!='' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                $data['familles'] = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille!='' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);
                // Récupérer les guides de tailles disponibles
                try {
                    $data['size_charts'] = $pdo->query("SELECT * FROM size_charts WHERE active=1 ORDER BY sport, ordre")->fetchAll();
                } catch (Exception $e) {
                    $data['size_charts'] = [];
                }
                // Tous les produits pour le sélecteur de produits liés (avec nom SEO)
                $data['all_products'] = $pdo->query("
                    SELECT id, nom, meta_title, sport, famille
                    FROM products WHERE active=1
                    ORDER BY sport, famille, nom
                ")->fetchAll();
                // Tous les templates disponibles pour le sélecteur
                try {
                    $data['all_templates'] = $pdo->query("
                        SELECT id, nom, filename, path, sport, famille
                        FROM templates WHERE active=1
                        ORDER BY sport, nom
                    ")->fetchAll();
                } catch (Exception $e) {
                    $data['all_templates'] = [];
                }
                break;

            case 'categories':
                $data['items'] = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY type, ordre, nom")->fetchAll();
                break;

            case 'category':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'category_pages':
                // Liste des pages catégorie dynamiques
                try {
                    $data['items'] = $pdo->query("SELECT * FROM category_pages ORDER BY title")->fetchAll();
                } catch (Exception $e) {
                    $data['items'] = [];
                }
                break;

            case 'category_page':
                // Édition d'une page catégorie
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM category_pages WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();

                    // Charger les produits sélectionnés
                    if ($data['item']) {
                        $stmt = $pdo->prepare("SELECT product_id FROM page_products WHERE page_type='category_page' AND page_slug=? ORDER BY position");
                        $stmt->execute([$data['item']['slug']]);
                        $data['selected_products'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                }
                // Tous les produits pour le sélecteur
                $data['all_products'] = $pdo->query("
                    SELECT id, reference, nom, meta_title, sport, famille, photo_1, prix_500
                    FROM products WHERE active=1
                    ORDER BY sport, famille, nom
                ")->fetchAll();
                break;

            case 'pages':
                // Lister les fichiers HTML directement
                $data['items'] = [];
                $directories = [
                    'info' => __DIR__ . '/../pages/info/',
                    'category' => __DIR__ . '/../pages/products/'
                ];
                foreach ($directories as $type => $dir) {
                    if (is_dir($dir)) {
                        $files = glob($dir . '*.html');
                        foreach ($files as $file) {
                            $filename = basename($file);
                            $slug = basename($file, '.html');
                            // Extraire le titre depuis le HTML
                            $html = file_get_contents($file);
                            preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
                            $title = $titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug));
                            $data['items'][] = [
                                'file' => $file,
                                'filename' => $filename,
                                'slug' => $slug,
                                'title' => $title,
                                'type' => $type,
                                'size' => filesize($file),
                                'modified' => filemtime($file)
                            ];
                        }
                    }
                }
                // Trier par titre
                usort($data['items'], fn($a, $b) => strcmp($a['title'], $b['title']));
                break;

            case 'page':
                // Charger un fichier HTML pour édition via type et slug
                $pageType = $_GET['type'] ?? 'info';
                $pageSlug = $_GET['slug'] ?? '';

                // Construire le chemin selon le type
                $directories = [
                    'info' => __DIR__ . '/../pages/info/',
                    'category' => __DIR__ . '/../pages/products/'
                ];

                if ($pageSlug && isset($directories[$pageType])) {
                    // Nettoyer le slug (sécurité)
                    $pageSlug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $pageSlug);
                    $filePath = $directories[$pageType] . $pageSlug . '.html';

                    if (file_exists($filePath)) {
                        $data['file_path'] = $filePath;
                        $data['content'] = file_get_contents($filePath);
                        $data['filename'] = basename($filePath);
                        $data['slug'] = $pageSlug;
                        $data['type'] = $pageType;

                        // Pour les pages catégories, charger les produits
                        if ($pageType === 'category') {
                            // Tous les produits disponibles (avec nom SEO)
                            $data['all_products'] = $pdo->query("
                                SELECT id, reference, nom,
                                       COALESCE(NULLIF(meta_title, ''), nom) as nom_seo,
                                       sport, famille, photo_1, prix_500, active
                                FROM products
                                WHERE active = 1
                                ORDER BY sport, famille, nom
                            ")->fetchAll();

                            // Produits sélectionnés pour cette page
                            $stmt = $pdo->prepare("
                                SELECT product_id FROM page_products
                                WHERE page_type = ? AND page_slug = ?
                                ORDER BY position
                            ");
                            $stmt->execute([$pageType, $pageSlug]);
                            $data['selected_products'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                    }
                }
                break;

            case 'blog':
                $data['items'] = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
                break;

            case 'blog_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'quotes':
                $where = "1=1";
                if (!empty($_GET['status'])) $where = "status='".$_GET['status']."'";
                $data['items'] = $pdo->query("SELECT * FROM quotes WHERE $where ORDER BY created_at DESC")->fetchAll();
                break;

            case 'quote':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'photos':
                // Scanner le dossier /photos/ et ses sous-dossiers
                $photosPath = __DIR__ . '/../photos';
                $data['folders'] = [];
                $data['photos'] = [];
                $currentFolder = $_GET['folder'] ?? '';

                if (is_dir($photosPath)) {
                    // Lister les sous-dossiers
                    $dirs = array_filter(glob($photosPath . '/*'), 'is_dir');
                    foreach ($dirs as $dir) {
                        $folderName = basename($dir);
                        $fileCount = count(glob($dir . '/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE));
                        $data['folders'][] = [
                            'name' => $folderName,
                            'path' => $folderName,
                            'count' => $fileCount
                        ];
                    }

                    // Lister les photos du dossier actuel
                    $scanPath = $currentFolder ? $photosPath . '/' . $currentFolder : $photosPath;
                    if (is_dir($scanPath)) {
                        $files = glob($scanPath . '/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE);
                        foreach ($files as $file) {
                            $data['photos'][] = [
                                'name' => basename($file),
                                'url' => '/photos/' . ($currentFolder ? $currentFolder . '/' : '') . basename($file),
                                'size' => filesize($file),
                                'modified' => filemtime($file)
                            ];
                        }
                        // Trier par date de modification (plus récent en premier)
                        usort($data['photos'], fn($a, $b) => $b['modified'] - $a['modified']);
                    }
                }
                $data['current_folder'] = $currentFolder;
                break;

            case 'settings':
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
                $data['settings'] = [];
                while ($row = $stmt->fetch()) {
                    $data['settings'][$row['setting_key']] = $row['setting_value'];
                }
                break;

            case 'import':
                $data['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $data['last_import'] = $pdo->query("SELECT MAX(created_at) FROM products")->fetchColumn();
                break;

            case 'templates':
                // Liste des templates avec filtres
                $where = "WHERE 1=1";
                $params = [];
                if (!empty($_GET['search'])) {
                    $where .= " AND (t.nom LIKE ? OR t.description LIKE ? OR t.tags LIKE ?)";
                    $searchTerm = '%'.$_GET['search'].'%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                if (!empty($_GET['sport'])) {
                    // Recherche dans un champ multi-valeurs séparé par virgule
                    $where .= " AND (t.sport = ? OR t.sport LIKE ? OR t.sport LIKE ? OR t.sport LIKE ?)";
                    $sportFilter = $_GET['sport'];
                    $params[] = $sportFilter;           // exact match
                    $params[] = $sportFilter . ',%';    // début
                    $params[] = '%,' . $sportFilter;    // fin
                    $params[] = '%,' . $sportFilter . ',%'; // milieu
                }
                if (!empty($_GET['famille'])) {
                    // Recherche dans un champ multi-valeurs séparé par virgule
                    $where .= " AND (t.famille = ? OR t.famille LIKE ? OR t.famille LIKE ? OR t.famille LIKE ?)";
                    $familleFilter = $_GET['famille'];
                    $params[] = $familleFilter;
                    $params[] = $familleFilter . ',%';
                    $params[] = '%,' . $familleFilter;
                    $params[] = '%,' . $familleFilter . ',%';
                }
                if (!empty($_GET['category'])) {
                    $where .= " AND t.category_id = ?";
                    $params[] = $_GET['category'];
                }
                if (isset($_GET['active']) && $_GET['active'] !== '') {
                    $where .= " AND t.active = ?";
                    $params[] = (int)$_GET['active'];
                }
                try {
                    $stmt = $pdo->prepare("
                        SELECT t.*, tc.nom as category_name
                        FROM templates t
                        LEFT JOIN template_categories tc ON t.category_id = tc.id
                        $where
                        ORDER BY t.ordre ASC, t.created_at DESC
                    ");
                    $stmt->execute($params);
                    $data['items'] = $stmt->fetchAll();
                    // Récupérer tous les sports uniques depuis les produits (pas les templates)
                    $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                    $data['familles'] = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);
                    $data['categories'] = $pdo->query("SELECT * FROM template_categories ORDER BY nom")->fetchAll();
                } catch (Exception $e) {
                    $data['items'] = [];
                    $data['sports'] = [];
                    $data['familles'] = [];
                    $data['categories'] = [];
                }
                break;

            case 'template':
                // Édition d'un template
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                try {
                    $data['categories'] = $pdo->query("SELECT * FROM template_categories ORDER BY nom")->fetchAll();
                    $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                    $data['familles'] = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);
                    // Produits associés à ce template
                    if ($id) {
                        $stmt = $pdo->prepare("SELECT product_id FROM template_products WHERE template_id = ?");
                        $stmt->execute([$id]);
                        $data['associated_products'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                    // Tous les produits pour le sélecteur
                    $data['all_products'] = $pdo->query("
                        SELECT id, reference, nom, meta_title, sport, famille, photo_1
                        FROM products WHERE active=1
                        ORDER BY sport, famille, nom
                    ")->fetchAll();
                } catch (Exception $e) {
                    $data['categories'] = [];
                    $data['sports'] = [];
                    $data['familles'] = [];
                    $data['associated_products'] = [];
                    $data['all_products'] = [];
                }
                break;
        }
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

$user = $_SESSION['admin_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - FLARE CUSTOM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Quill Editor (gratuit, open source) -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <style>
        :root {
            --primary: #FF4B26;
            --primary-hover: #E6401F;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: #2a2a3c;
            --body-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #1e1e2d;
            --text-muted: #7e8299;
            --border: #e4e6ef;
            --success: #50cd89;
            --warning: #ffc700;
            --danger: #f1416c;
            --info: #7239ea;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--body-bg); color: var(--text-dark); font-size: 13px; }

        /* SIDEBAR */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 265px;
            background: var(--sidebar-bg); z-index: 100; display: flex; flex-direction: column;
        }
        .sidebar-header {
            padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-logo {
            color: #fff; font-size: 22px; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-logo span { color: var(--primary); }
        .sidebar-menu { padding: 15px 0; flex: 1; overflow-y: auto; }
        .menu-section { padding: 10px 25px 5px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .menu-item {
            display: flex; align-items: center; gap: 12px; padding: 11px 25px;
            color: #9d9da6; text-decoration: none; transition: all 0.2s;
        }
        .menu-item:hover { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active::before {
            content: ''; position: absolute; left: 0; width: 3px; height: 100%;
            background: var(--primary);
        }
        .menu-item { position: relative; }
        .menu-icon { width: 20px; height: 20px; opacity: 0.7; }
        .menu-badge {
            margin-left: auto; background: var(--primary); color: #fff;
            padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;
        }
        .sidebar-footer {
            padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.07);
        }
        .user-box {
            display: flex; align-items: center; gap: 12px; color: #fff;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 8px; background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .user-info { flex: 1; }
        .user-name { font-weight: 600; font-size: 14px; }
        .user-role { color: var(--text-muted); font-size: 12px; }

        /* MAIN */
        .main { margin-left: 265px; min-height: 100vh; }
        .topbar {
            background: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .breadcrumb { display: flex; align-items: center; gap: 8px; color: var(--text-muted); }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--primary); }
        .topbar-actions { display: flex; gap: 10px; }

        .content { padding: 30px; }

        /* CARDS */
        .card {
            background: var(--card-bg); border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.03); margin-bottom: 25px;
        }
        .card-header {
            padding: 20px 25px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-title { font-size: 16px; font-weight: 600; }
        .card-body { padding: 25px; }
        .card-footer { padding: 15px 25px; border-top: 1px solid var(--border); background: #fafbfc; border-radius: 0 0 12px 12px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card {
            background: var(--card-bg); border-radius: 12px; padding: 25px;
            box-shadow: 0 0 20px rgba(0,0,0,0.03);
        }
        .stat-card.primary { background: linear-gradient(135deg, var(--primary) 0%, #ff6b4a 100%); color: #fff; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 24px; }
        .stat-card:not(.primary) .stat-icon { background: rgba(255,75,38,0.1); }
        .stat-value { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { color: var(--text-muted); font-size: 13px; }
        .stat-card.primary .stat-label { color: rgba(255,255,255,0.8); }

        /* TABLE */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 15px; background: #fafbfc; font-weight: 600; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover td { background: #fafbfc; }
        .table-img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; background: var(--body-bg); }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px;
            border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer;
            border: none; text-decoration: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-light { background: #f4f6f9; color: var(--text-dark); }
        .btn-light:hover { background: #e9ecef; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-icon { padding: 8px; }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-dark); }
        .form-control {
            width: 100%; padding: 10px 15px; border: 1px solid var(--border);
            border-radius: 8px; font-size: 13px; transition: all 0.2s;
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255,75,38,0.1); }
        textarea.form-control { min-height: 150px; resize: vertical; }
        select.form-control { cursor: pointer; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }

        /* TABS */
        .tabs-nav {
            display: flex; gap: 5px; border-bottom: 1px solid var(--border);
            padding: 0 25px; background: #fafbfc; border-radius: 12px 12px 0 0;
        }
        .tab-btn {
            padding: 15px 20px; background: none; border: none; cursor: pointer;
            font-weight: 500; color: var(--text-muted); position: relative;
            font-size: 13px; transition: all 0.2s;
        }
        .tab-btn:hover { color: var(--text-dark); }
        .tab-btn.active { color: var(--primary); }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
            height: 2px; background: var(--primary);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* BADGES */
        .badge {
            display: inline-flex; align-items: center; padding: 5px 10px;
            border-radius: 6px; font-size: 11px; font-weight: 600;
        }
        .badge-success { background: rgba(80,205,137,0.1); color: var(--success); }
        .badge-warning { background: rgba(255,199,0,0.1); color: #b58b00; }
        .badge-danger { background: rgba(241,65,108,0.1); color: var(--danger); }
        .badge-info { background: rgba(114,57,234,0.1); color: var(--info); }
        .badge-primary { background: rgba(255,75,38,0.1); color: var(--primary); }

        /* TOAST */
        .toast {
            position: fixed; top: 20px; right: 20px; background: var(--success); color: #fff;
            padding: 15px 25px; border-radius: 8px; font-weight: 500; z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } }

        /* LOGIN */
        .login-page {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sidebar-bg) 0%, #2d2d42 100%);
        }
        .login-box {
            background: var(--card-bg); padding: 40px; border-radius: 16px; width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo h1 { font-size: 28px; }
        .login-logo span { color: var(--primary); }

        /* ALERTS */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-danger { background: rgba(241,65,108,0.1); color: var(--danger); border: 1px solid rgba(241,65,108,0.2); }
        .alert-success { background: rgba(80,205,137,0.1); color: var(--success); border: 1px solid rgba(80,205,137,0.2); }

        /* EDITOR */
        .editor-toolbar {
            display: flex; gap: 5px; padding: 10px; background: #fafbfc;
            border: 1px solid var(--border); border-bottom: none; border-radius: 8px 8px 0 0;
        }
        .editor-toolbar button {
            padding: 8px 12px; background: none; border: 1px solid var(--border);
            border-radius: 4px; cursor: pointer; font-size: 12px;
        }
        .editor-toolbar button:hover { background: #fff; }

        /* FILTERS */
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }

        /* RESPONSIVE */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; }
        }

        /* CodeMirror custom styles */
        .CodeMirror {
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            height: auto;
            min-height: 400px;
        }
        .CodeMirror-scroll {
            min-height: 400px;
        }
        .html-editor-toolbar {
            background: var(--body-bg);
            border: 1px solid var(--border);
            border-bottom: none;
            border-radius: 6px 6px 0 0;
            padding: 8px 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .html-editor-toolbar .btn { padding: 6px 12px; font-size: 12px; }
        .editor-status {
            margin-left: auto;
            font-size: 12px;
            color: var(--text-muted);
        }
        .preview-frame {
            border: 1px solid var(--border);
            border-radius: 6px;
            width: 100%;
            height: 500px;
            background: #fff;
        }

        /* ========== PAGE BUILDER STYLES ========== */
        .pb-module-item:hover {
            border-color: var(--primary);
            background: #fff5f3;
            transform: translateY(-1px);
        }
        .pb-module-item:active {
            cursor: grabbing;
        }
        .pb-drop-zone {
            transition: all 0.3s;
        }
        .pb-drop-zone.drag-over {
            border-color: var(--primary);
            background: #fff5f3;
        }
        .pb-block {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .pb-block:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(255,75,38,0.1);
        }
        .pb-block.dragging {
            opacity: 0.5;
            border: 2px dashed var(--primary);
        }
        .pb-block-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .pb-block-handle {
            cursor: grab;
            color: var(--text-muted);
            font-size: 16px;
        }
        .pb-block-name {
            font-size: 13px;
            font-weight: 600;
            flex: 1;
        }
        .pb-block-actions {
            display: flex;
            gap: 5px;
        }
        .pb-block-actions button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .pb-block-actions button:hover {
            background: #e2e8f0;
        }
        .pb-block-preview {
            padding: 12px 15px;
        }
        .pb-drop-indicator {
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
            margin: 5px 0;
        }

        /* Modal styles */
        .pb-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pb-modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
        }
        .pb-modal-content {
            position: relative;
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .pb-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }
        .pb-modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .pb-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-muted);
            line-height: 1;
        }
        .pb-modal-body {
            padding: 25px;
            overflow-y: auto;
            flex: 1;
        }
        .pb-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px 25px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .pb-field-group {
            margin-bottom: 20px;
        }
        .pb-field-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }
        .pb-field-group input,
        .pb-field-group textarea,
        .pb-field-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .pb-field-group input:focus,
        .pb-field-group textarea:focus,
        .pb-field-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .pb-field-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .pb-tab-btn.active {
            background: var(--primary);
            color: #fff;
        }

        /* Quill Editor Custom Styles */
        .quill-editor-container { margin-bottom: 15px; }
        .quill-editor-container .ql-container { min-height: 200px; font-size: 14px; }
        .quill-editor-container .ql-editor { min-height: 200px; }
        .quill-editor-container .ql-toolbar { border-radius: 6px 6px 0 0; background: #fafbfc; }
        .quill-editor-container .ql-container { border-radius: 0 0 6px 6px; }

        /* Photo Gallery Manager */
        .photo-gallery-manager { margin-bottom: 30px; }
        .photo-dropzone {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafbfc;
            margin-bottom: 20px;
        }
        .photo-dropzone:hover, .photo-dropzone.dragover {
            border-color: var(--primary);
            background: #fff5f3;
        }
        .photo-dropzone-icon { font-size: 48px; margin-bottom: 10px; opacity: 0.5; }
        .photo-dropzone-text { color: var(--text-muted); margin-bottom: 10px; }
        .photo-dropzone-hint { font-size: 12px; color: var(--text-muted); }
        .photo-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .photo-gallery-item {
            position: relative;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            cursor: grab;
            transition: all 0.2s ease;
        }
        .photo-gallery-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .photo-gallery-item.dragging { opacity: 0.5; }
        .photo-gallery-item.main-photo { border: 2px solid var(--success); }
        .photo-gallery-item img { width: 100%; height: 120px; object-fit: cover; display: block; }
        .photo-gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: flex-end;
            gap: 4px;
            padding: 6px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .photo-gallery-item:hover .photo-gallery-item-overlay { opacity: 1; }
        .photo-gallery-item-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-gallery-item-btn.star { background: rgba(255,255,255,0.9); color: #f59e0b; }
        .photo-gallery-item-btn.delete { background: rgba(241,65,108,0.9); color: #fff; }
        .photo-gallery-item-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 6px;
            background: var(--success);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
        }
        .photo-upload-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(0,0,0,0.1);
        }
        .photo-upload-progress-bar {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s;
        }
    </style>
    <!-- CodeMirror for HTML editing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closetag.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchtags.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/xml-fold.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/fold/foldgutter.min.css">
</head>
<body>

<?php if ($page === 'login'): ?>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <h1>FLARE <span>CUSTOM</span></h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Administration</p>
        </div>
        <?php if (isset($loginError)): ?>
            <div class="alert alert-danger"><?= $loginError ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <div class="alert alert-danger">Erreur BDD: <?= htmlspecialchars($dbError) ?><br><a href="import-content.php">Lancer l'import</a></div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Se connecter</button>
        </form>
        <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 12px;">
            Identifiants par défaut: admin / admin123
        </p>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<?php if ($toast): ?>
<div class="toast"><?= htmlspecialchars($toast) ?></div>
<script>setTimeout(() => document.querySelector('.toast').remove(), 3000);</script>
<?php endif; ?>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="sidebar-logo">FLARE <span>CUSTOM</span></a>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-section">Principal</div>
        <a href="?page=dashboard" class="menu-item <?= $page === 'dashboard' ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Tableau de bord
        </a>

        <div class="menu-section">Catalogue</div>
        <a href="?page=products" class="menu-item <?= in_array($page, ['products', 'product']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Produits
        </a>
        <a href="?page=categories" class="menu-item <?= in_array($page, ['categories', 'category']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Catégories
        </a>

        <div class="menu-section">Contenu</div>
        <a href="?page=pages&filter=info" class="menu-item <?= (in_array($page, ['pages', 'page']) && ($_GET['filter'] ?? '') === 'info') || ($page === 'page' && ($data['item']['type'] ?? 'info') === 'info') ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Pages Info
        </a>
        <a href="?page=pages&filter=category" class="menu-item <?= ($page === 'pages' && ($_GET['filter'] ?? '') === 'category') || ($page === 'page' && ($data['item']['type'] ?? '') === 'category') ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Pages Catégories (HTML)
        </a>
        <a href="?page=category_pages" class="menu-item <?= in_array($page, ['category_pages', 'category_page']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            Pages Catégories (DB)
        </a>
        <a href="?page=blog" class="menu-item <?= in_array($page, ['blog', 'blog_edit']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            Articles Blog
        </a>

        <div class="menu-section">Ventes</div>
        <a href="?page=quotes" class="menu-item <?= in_array($page, ['quotes', 'quote']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Devis
            <?php if (($data['quotes_pending'] ?? 0) > 0): ?>
            <span class="menu-badge"><?= $data['quotes_pending'] ?? 0 ?></span>
            <?php endif; ?>
        </a>

        <div class="menu-section">Outils</div>
        <a href="?page=import" class="menu-item <?= $page === 'import' ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import CSV
        </a>
        <a href="?page=templates" class="menu-item <?= in_array($page, ['templates', 'template']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            Templates
        </a>
        <a href="?page=photos" class="menu-item <?= $page === 'photos' ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Photos
        </a>
        <a href="?page=settings" class="menu-item <?= in_array($page, ['settings', 'settings_password']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Paramètres
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-avatar"><?= strtoupper(substr($user['username'] ?? 'A', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['username'] ?? 'Admin') ?></div>
                <div class="user-role"><?= ucfirst($user['role'] ?? 'admin') ?></div>
            </div>
            <a href="?page=logout" style="color: var(--text-muted);" title="Déconnexion">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="topbar">
        <div class="breadcrumb">
            <a href="admin.php">Admin</a>
            <span>/</span>
            <span><?= ucfirst($page) ?></span>
        </div>
    </div>

    <div class="content">
        <?php if ($dbError): ?>
        <div class="alert alert-danger">Erreur BDD: <?= htmlspecialchars($dbError) ?> — <a href="import-content.php">Lancer l'import</a></div>
        <?php endif; ?>

        <?php // ============ DASHBOARD ============ ?>
        <?php if ($page === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= number_format($data['products'] ?? 0) ?></div>
                <div class="stat-label">Produits actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?= number_format($data['quotes_pending'] ?? 0) ?></div>
                <div class="stat-label">Devis en attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?= number_format($data['categories'] ?? 0) ?></div>
                <div class="stat-label">Catégories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-value"><?= number_format($data['pages'] ?? 0) ?></div>
                <div class="stat-label">Pages publiées</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Derniers devis</span>
                    <a href="?page=quotes" class="btn btn-sm btn-light">Voir tout</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Référence</th><th>Client</th><th>Statut</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['recent_quotes'] ?? [] as $q): ?>
                            <tr>
                                <td><a href="?page=quote&id=<?= $q['id'] ?>"><?= htmlspecialchars($q['reference']) ?></a></td>
                                <td><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></td>
                                <td><span class="badge badge-<?= $q['status'] === 'pending' ? 'warning' : ($q['status'] === 'accepted' ? 'success' : 'info') ?>"><?= $q['status'] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Produits récemment modifiés</span>
                    <a href="?page=products" class="btn btn-sm btn-light">Voir tout</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Produit</th><th>Sport</th><th>Modifié</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['recent_products'] ?? [] as $p): ?>
                            <tr>
                                <td>
                                    <a href="?page=product&id=<?= $p['id'] ?>" style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($p['photo_1'] ?: '/photos/placeholder.webp') ?>" class="table-img">
                                        <?= htmlspecialchars(mb_substr(!empty($p['meta_title']) ? $p['meta_title'] : $p['nom'], 0, 40)) ?>
                                    </a>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($p['sport']) ?></span></td>
                                <td><?= date('d/m H:i', strtotime($p['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php // ============ PRODUCTS LIST ============ ?>
        <?php elseif ($page === 'products'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Produits (<?= count($data['items'] ?? []) ?>)</span>
                <a href="?page=product&id=new" class="btn btn-primary">+ Nouveau produit</a>
            </div>
            <div class="card-body">
                <form class="filters" method="GET">
                    <input type="hidden" name="page" value="products">
                    <input type="text" name="search" class="form-control" style="width: 250px;" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <select name="sport" class="form-control" style="width: 180px;">
                        <option value="">Tous les sports</option>
                        <?php foreach ($data['sports'] ?? [] as $s): ?>
                        <option value="<?= $s ?>" <?= ($_GET['sport'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-light">Filtrer</button>
                </form>

                <div class="table-container">
                    <table>
                        <thead><tr><th style="width:60px"></th><th>Référence</th><th>Nom SEO / Nom</th><th>Sport</th><th>Prix 500</th><th style="width:100px">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['items'] ?? [] as $p): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($p['photo_1'] ?: '/photos/placeholder.webp') ?>" class="table-img"></td>
                                <td><strong><?= htmlspecialchars($p['reference']) ?></strong></td>
                                <td>
                                    <a href="?page=product&id=<?= $p['id'] ?>"><?= htmlspecialchars(mb_substr(!empty($p['meta_title']) ? $p['meta_title'] : $p['nom'], 0, 50)) ?></a>
                                    <?php if (!empty($p['meta_title']) && $p['meta_title'] !== $p['nom']): ?>
                                    <br><small style="color: var(--text-muted);"><?= htmlspecialchars(mb_substr($p['nom'], 0, 30)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($p['sport']) ?></span></td>
                                <td><?= $p['prix_500'] ? number_format($p['prix_500'], 2).'€' : '-' ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="/produit/<?= htmlspecialchars($p['reference']) ?>" target="_blank" class="btn btn-sm btn-light" title="Voir sur le site">👁️</a>
                                    <a href="?page=product&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php // ============ PRODUCT EDIT ============ ?>
        <?php elseif ($page === 'product' && ($id || $isNew)): ?>
        <?php
        $p = $data['item'] ?? [];
        $productIsNew = $data['isNew'] ?? false;
        ?>
        <form method="POST" action="?page=product<?= $id ? '&id='.$id : '' ?>" id="product-form" onsubmit="return prepareProductSubmit()">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="save_product">

            <?php if ($productIsNew): ?>
            <div class="alert" style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Nouveau produit</strong> - Remplissez les informations et cliquez sur "Créer le produit"
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="tabs-nav">
                    <button type="button" class="tab-btn <?= $tab === 'general' ? 'active' : '' ?>" onclick="switchTab('general')">Général</button>
                    <button type="button" class="tab-btn <?= $tab === 'prices' ? 'active' : '' ?>" onclick="switchTab('prices')">Prix</button>
                    <?php if (!$productIsNew): ?>
                    <button type="button" class="tab-btn <?= $tab === 'photos' ? 'active' : '' ?>" onclick="switchTab('photos')">Photos</button>
                    <button type="button" class="tab-btn <?= $tab === 'tabs' ? 'active' : '' ?>" onclick="switchTab('tabs')">Contenu onglets</button>
                    <button type="button" class="tab-btn <?= $tab === 'configurator' ? 'active' : '' ?>" onclick="switchTab('configurator')">Configurateur</button>
                    <button type="button" class="tab-btn <?= $tab === 'seo' ? 'active' : '' ?>" onclick="switchTab('seo')">SEO</button>
                    <button type="button" class="tab-btn <?= $tab === 'settings' ? 'active' : '' ?>" onclick="switchTab('settings')">⚙️ Paramètres</button>
                    <?php endif; ?>
                </div>

                <!-- TAB: GENERAL -->
                <div class="tab-content <?= $tab === 'general' ? 'active' : '' ?>" id="tab-general">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Référence <?= $productIsNew ? '*' : '' ?></label>
                                <?php if ($productIsNew): ?>
                                <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($p['reference'] ?? '') ?>" required placeholder="FLARE-XXXXX">
                                <div class="form-hint">Format: FLARE-XXXXX (unique)</div>
                                <?php else: ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($p['reference'] ?? '') ?>" readonly style="background: #f4f6f9;">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sport *</label>
                                <input type="text" name="sport" class="form-control" value="<?= htmlspecialchars($p['sport'] ?? '') ?>" list="sports-list" required placeholder="Ex: Football, Handball...">
                                <datalist id="sports-list">
                                    <?php foreach ($data['sports'] ?? [] as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Famille</label>
                                <input type="text" name="famille" class="form-control" value="<?= htmlspecialchars($p['famille'] ?? '') ?>" list="familles-list" placeholder="Ex: Maillot, Short...">
                                <datalist id="familles-list">
                                    <?php foreach ($data['familles'] ?? [] as $f): ?>
                                    <option value="<?= htmlspecialchars($f) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($p['nom'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tissu</label>
                                <input type="text" name="tissu" class="form-control" value="<?= htmlspecialchars($p['tissu'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Grammage</label>
                                <input type="text" name="grammage" class="form-control" value="<?= htmlspecialchars($p['grammage'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Genre</label>
                                <select name="genre" class="form-control">
                                    <option value="Mixte" <?= ($p['genre'] ?? '') === 'Mixte' ? 'selected' : '' ?>>Mixte</option>
                                    <option value="Homme" <?= ($p['genre'] ?? '') === 'Homme' ? 'selected' : '' ?>>Homme</option>
                                    <option value="Femme" <?= ($p['genre'] ?? '') === 'Femme' ? 'selected' : '' ?>>Femme</option>
                                    <option value="Enfant" <?= ($p['genre'] ?? '') === 'Enfant' ? 'selected' : '' ?>>Enfant</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Finition</label>
                                <input type="text" name="finition" class="form-control" value="<?= htmlspecialchars($p['finition'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description courte</label>
                            <textarea name="description" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description SEO</label>
                            <textarea name="description_seo" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['description_seo'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB: PRICES -->
                <div class="tab-content <?= $tab === 'prices' ? 'active' : '' ?>" id="tab-prices">
                    <div class="card-body">
                        <h4 style="margin-bottom: 15px;">Prix Adulte</h4>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">Prix unitaire TTC par quantité</p>
                        <div class="form-row">
                            <?php foreach ([1, 5, 10, 20, 50, 100, 250, 500] as $qty): ?>
                            <div class="form-group">
                                <label class="form-label"><?= $qty ?> pièce<?= $qty > 1 ? 's' : '' ?></label>
                                <input type="number" step="0.01" name="prix_<?= $qty ?>" class="form-control" value="<?= $p['prix_'.$qty] ?? '' ?>" placeholder="0.00">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 15px;">Prix Enfant</h4>
                        <p style="color: var(--text-muted); margin-bottom: 10px;">Prix unitaire TTC par quantité (laisser vide = -10% du prix adulte automatique)</p>
                        <button type="button" class="btn btn-sm btn-light" style="margin-bottom: 20px;" onclick="calculerPrixEnfants()">Calculer auto (-10%)</button>
                        <div class="form-row">
                            <?php foreach ([1, 5, 10, 20, 50, 100, 250, 500] as $qty): ?>
                            <div class="form-group">
                                <label class="form-label"><?= $qty ?> pièce<?= $qty > 1 ? 's' : '' ?></label>
                                <input type="number" step="0.01" name="prix_enfant_<?= $qty ?>" id="prix_enfant_<?= $qty ?>" class="form-control" value="<?= $p['prix_enfant_'.$qty] ?? '' ?>" placeholder="Auto -10%">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <script>
                        function calculerPrixEnfants() {
                            <?php foreach ([1, 5, 10, 20, 50, 100, 250, 500] as $qty): ?>
                            var prixAdulte<?= $qty ?> = parseFloat(document.querySelector('input[name="prix_<?= $qty ?>"]').value) || 0;
                            if (prixAdulte<?= $qty ?> > 0) {
                                document.getElementById('prix_enfant_<?= $qty ?>').value = (prixAdulte<?= $qty ?> * 0.90).toFixed(2);
                            }
                            <?php endforeach; ?>
                        }
                        </script>
                    </div>
                </div>

                <!-- TAB: PHOTOS -->
                <div class="tab-content <?= $tab === 'photos' ? 'active' : '' ?>" id="tab-photos">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Photo principale</h4>
                        <div class="form-group">
                            <label class="form-label">Photo principale (photo_1)</label>
                            <input type="text" name="photo_1" class="form-control" value="<?= htmlspecialchars($p['photo_1'] ?? '') ?>" placeholder="URL de l'image principale">
                            <?php if (!empty($p['photo_1'])): ?>
                            <img src="<?= htmlspecialchars($p['photo_1']) ?>" style="max-width: 200px; margin-top: 10px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">📸 Galerie photos</h4>
                        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 13px;">
                            Glissez-déposez vos images ou cliquez pour sélectionner. Les photos sont stockées dans <code>/photos/produits/</code>
                        </p>

                        <div class="photo-gallery-manager" id="photoGalleryManager" data-product-id="<?= $id ?>">
                            <!-- Zone de drop pour upload -->
                            <div class="photo-dropzone" id="photoDropzone">
                                <div class="photo-dropzone-icon">📁</div>
                                <div class="photo-dropzone-text">
                                    <strong>Glissez-déposez vos photos ici</strong><br>
                                    ou cliquez pour parcourir
                                </div>
                                <div class="photo-dropzone-hint">PNG, JPG, WebP - Max 10 Mo par fichier</div>
                                <input type="file" id="photoFileInput" multiple accept="image/*" style="display: none;">
                            </div>

                            <!-- Grille des photos -->
                            <div class="photo-gallery-grid" id="photoGalleryGrid">
                                <?php foreach ($data['photos'] ?? [] as $photo): ?>
                                <?php $isMain = ($photo['type'] ?? '') === 'main'; ?>
                                <div class="photo-gallery-item <?= $isMain ? 'main-photo' : '' ?>" data-id="<?= $photo['id'] ?>" draggable="true">
                                    <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($photo['alt_text'] ?? '') ?>">
                                    <div class="photo-gallery-item-overlay">
                                        <?php if (!$isMain): ?>
                                        <button type="button" class="photo-gallery-item-btn star" onclick="setMainPhoto(<?= $photo['id'] ?>)" title="Définir comme principale">⭐</button>
                                        <?php endif; ?>
                                        <button type="button" class="photo-gallery-item-btn delete" onclick="deletePhoto(<?= $photo['id'] ?>)" title="Supprimer">✕</button>
                                    </div>
                                    <?php if ($isMain): ?>
                                    <div class="photo-gallery-item-badge">Photo principale</div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>

                                <?php if (empty($data['photos'])): ?>
                                <p style="color: var(--text-muted); grid-column: 1/-1; text-align: center; padding: 20px;" id="noPhotosMessage">
                                    Aucune photo. Ajoutez des photos en les glissant-déposant ci-dessus.
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <script>
                        (function() {
                            const productId = <?= $id ?>;
                            const dropzone = document.getElementById('photoDropzone');
                            const fileInput = document.getElementById('photoFileInput');
                            const grid = document.getElementById('photoGalleryGrid');
                            const csrfToken = '<?= generateCsrfToken() ?>';

                            // Click to browse
                            dropzone.addEventListener('click', () => fileInput.click());

                            // File input change
                            fileInput.addEventListener('change', (e) => {
                                handleFiles(e.target.files);
                                fileInput.value = '';
                            });

                            // Drag and drop
                            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                                dropzone.addEventListener(eventName, preventDefaults);
                            });

                            function preventDefaults(e) {
                                e.preventDefault();
                                e.stopPropagation();
                            }

                            ['dragenter', 'dragover'].forEach(eventName => {
                                dropzone.addEventListener(eventName, () => dropzone.classList.add('dragover'));
                            });

                            ['dragleave', 'drop'].forEach(eventName => {
                                dropzone.addEventListener(eventName, () => dropzone.classList.remove('dragover'));
                            });

                            dropzone.addEventListener('drop', (e) => {
                                handleFiles(e.dataTransfer.files);
                            });

                            function handleFiles(files) {
                                // Remove "no photos" message
                                const noMsg = document.getElementById('noPhotosMessage');
                                if (noMsg) noMsg.remove();

                                [...files].forEach(uploadFile);
                            }

                            function uploadFile(file) {
                                if (!file.type.startsWith('image/')) {
                                    alert('Seules les images sont autorisées');
                                    return;
                                }
                                if (file.size > 10 * 1024 * 1024) {
                                    alert('Fichier trop volumineux (max 10 Mo)');
                                    return;
                                }

                                // Create preview element
                                const item = document.createElement('div');
                                item.className = 'photo-gallery-item';
                                item.innerHTML = `
                                    <img src="${URL.createObjectURL(file)}" alt="Uploading...">
                                    <div class="photo-upload-progress">
                                        <div class="photo-upload-progress-bar" style="width: 0%"></div>
                                    </div>
                                `;
                                grid.appendChild(item);

                                // Upload via API
                                const formData = new FormData();
                                formData.append('file', file);

                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', '/api/product-photos.php?action=upload', true);

                                xhr.upload.onprogress = (e) => {
                                    if (e.lengthComputable) {
                                        const pct = (e.loaded / e.total) * 100;
                                        item.querySelector('.photo-upload-progress-bar').style.width = pct + '%';
                                    }
                                };

                                xhr.onload = function() {
                                    const progress = item.querySelector('.photo-upload-progress');
                                    if (progress) progress.remove();

                                    if (xhr.status === 200) {
                                        const resp = JSON.parse(xhr.responseText);
                                        if (resp.success) {
                                            // Add to database
                                            addPhotoToDatabase(resp.file.url, resp.file.filename, item);
                                        } else {
                                            item.remove();
                                            alert('Erreur: ' + resp.error);
                                        }
                                    } else {
                                        item.remove();
                                        alert('Erreur lors de l\'upload');
                                    }
                                };

                                xhr.onerror = function() {
                                    item.remove();
                                    alert('Erreur réseau');
                                };

                                xhr.send(formData);
                            }

                            function addPhotoToDatabase(url, filename, element) {
                                fetch('/api/product-photos.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        product_id: productId,
                                        url: url,
                                        filename: filename
                                    })
                                })
                                .then(r => r.json())
                                .then(resp => {
                                    if (resp.success) {
                                        element.dataset.id = resp.id;
                                        element.setAttribute('draggable', 'true');
                                        element.innerHTML = `
                                            <img src="${url}" alt="">
                                            <div class="photo-gallery-item-overlay">
                                                <button type="button" class="photo-gallery-item-btn star" onclick="setMainPhoto(${resp.id})" title="Définir comme principale">⭐</button>
                                                <button type="button" class="photo-gallery-item-btn delete" onclick="deletePhoto(${resp.id})" title="Supprimer">✕</button>
                                            </div>
                                        `;
                                        initDragDrop();
                                    }
                                });
                            }

                            // Drag & drop reorder
                            function initDragDrop() {
                                const items = grid.querySelectorAll('.photo-gallery-item');
                                items.forEach(item => {
                                    item.addEventListener('dragstart', handleDragStart);
                                    item.addEventListener('dragend', handleDragEnd);
                                    item.addEventListener('dragover', handleDragOver);
                                    item.addEventListener('drop', handleDropReorder);
                                });
                            }

                            let draggedItem = null;

                            function handleDragStart(e) {
                                draggedItem = this;
                                this.classList.add('dragging');
                            }

                            function handleDragEnd(e) {
                                this.classList.remove('dragging');
                                draggedItem = null;
                            }

                            function handleDragOver(e) {
                                e.preventDefault();
                                if (draggedItem && draggedItem !== this) {
                                    const rect = this.getBoundingClientRect();
                                    const midX = rect.left + rect.width / 2;
                                    if (e.clientX < midX) {
                                        grid.insertBefore(draggedItem, this);
                                    } else {
                                        grid.insertBefore(draggedItem, this.nextSibling);
                                    }
                                }
                            }

                            function handleDropReorder(e) {
                                e.preventDefault();
                                saveOrder();
                            }

                            function saveOrder() {
                                const order = [...grid.querySelectorAll('.photo-gallery-item')]
                                    .map(el => el.dataset.id)
                                    .filter(id => id);

                                fetch('/api/product-photos.php?action=reorder', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ product_id: productId, order: order })
                                });
                            }

                            initDragDrop();

                            // Global functions
                            window.setMainPhoto = function(photoId) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.innerHTML = `
                                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                                    <input type="hidden" name="action" value="set_main_photo">
                                    <input type="hidden" name="photo_id" value="${photoId}">
                                    <input type="hidden" name="product_id" value="${productId}">
                                `;
                                document.body.appendChild(form);
                                form.submit();
                            };

                            window.deletePhoto = function(photoId) {
                                if (!confirm('Supprimer cette photo ?')) return;
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.innerHTML = `
                                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                                    <input type="hidden" name="action" value="delete_photo">
                                    <input type="hidden" name="photo_id" value="${photoId}">
                                `;
                                document.body.appendChild(form);
                                form.submit();
                            };
                        })();
                        </script>
                    </div>
                </div>

                <!-- TAB: TABS CONTENT -->
                <div class="tab-content <?= $tab === 'tabs' ? 'active' : '' ?>" id="tab-tabs">
                    <div class="card-body">
                        <p style="color: var(--text-muted); margin-bottom: 20px;">Contenu des onglets affichés sur la fiche produit.</p>

                        <div class="form-group">
                            <label class="form-label">📝 Onglet Description</label>
                            <textarea name="tab_description" class="form-control wysiwyg"><?= htmlspecialchars($p['tab_description'] ?? '') ?></textarea>
                            <div class="form-hint">Éditeur visuel. Laissez vide pour utiliser la description SEO.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📋 Onglet Caractéristiques</label>
                            <textarea name="tab_specifications" class="form-control wysiwyg"><?= htmlspecialchars($p['tab_specifications'] ?? '') ?></textarea>
                            <div class="form-hint">Éditeur visuel. Tableau des spécifications techniques.</div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">📏 Guide des Tailles</h4>

                        <div class="form-group">
                            <label class="form-label">Sélectionner un guide de tailles prédéfini</label>
                            <select name="size_chart_id" class="form-control" style="max-width: 400px;" onchange="toggleCustomSizes(this)">
                                <option value="">-- Aucun (contenu personnalisé) --</option>
                                <?php
                                $currentSport = $p['sport'] ?? '';
                                $sizeChartsByGroup = [];
                                foreach ($data['size_charts'] ?? [] as $sc) {
                                    $group = $sc['sport'] ?: 'Général';
                                    $sizeChartsByGroup[$group][] = $sc;
                                }
                                foreach ($sizeChartsByGroup as $group => $charts): ?>
                                <optgroup label="<?= htmlspecialchars($group) ?>">
                                    <?php foreach ($charts as $sc): ?>
                                    <option value="<?= $sc['id'] ?>" <?= ($p['size_chart_id'] ?? '') == $sc['id'] ? 'selected' : '' ?>
                                        data-content="<?= htmlspecialchars($sc['html_content']) ?>">
                                        <?= htmlspecialchars($sc['nom']) ?> (<?= $sc['type'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">Choisissez un guide prédéfini ou créez un contenu personnalisé ci-dessous.</div>
                        </div>

                        <!-- Prévisualisation du guide sélectionné -->
                        <div id="size-chart-preview" style="background: #fafbfc; border-radius: 8px; padding: 15px; margin: 15px 0; display: none;">
                            <strong style="font-size: 12px; color: var(--text-muted);">APERÇU DU GUIDE:</strong>
                            <div id="size-chart-preview-content" style="margin-top: 10px; overflow-x: auto;"></div>
                        </div>

                        <div class="form-group" id="custom-sizes-area">
                            <label class="form-label">Contenu personnalisé (si pas de guide sélectionné)</label>
                            <textarea name="tab_sizes" class="form-control wysiwyg"><?= htmlspecialchars($p['tab_sizes'] ?? '') ?></textarea>
                            <div class="form-hint">Éditeur visuel. Utilisé uniquement si aucun guide n'est sélectionné.</div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <div class="form-group">
                            <label class="form-label">🎨 Onglet Templates</label>
                            <textarea name="tab_templates" class="form-control wysiwyg"><?= htmlspecialchars($p['tab_templates'] ?? '') ?></textarea>
                            <div class="form-hint">Éditeur visuel. Galerie de templates disponibles.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">❓ Onglet FAQ</label>
                            <textarea name="tab_faq" class="form-control wysiwyg"><?= htmlspecialchars($p['tab_faq'] ?? '') ?></textarea>
                            <div class="form-hint">Éditeur visuel. Questions fréquentes sur ce produit.</div>
                        </div>
                    </div>
                </div>

                <script>
                function toggleCustomSizes(select) {
                    var preview = document.getElementById('size-chart-preview');
                    var previewContent = document.getElementById('size-chart-preview-content');
                    var customArea = document.getElementById('custom-sizes-area');

                    if (select.value) {
                        var option = select.options[select.selectedIndex];
                        var content = option.getAttribute('data-content');
                        previewContent.innerHTML = content || 'Aucun aperçu disponible';
                        preview.style.display = 'block';
                        customArea.style.opacity = '0.5';
                    } else {
                        preview.style.display = 'none';
                        customArea.style.opacity = '1';
                    }
                }
                // Init on load
                document.addEventListener('DOMContentLoaded', function() {
                    var select = document.querySelector('select[name="size_chart_id"]');
                    if (select && select.value) toggleCustomSizes(select);
                });
                </script>

                <!-- TAB: CONFIGURATOR -->
                <div class="tab-content <?= $tab === 'configurator' ? 'active' : '' ?>" id="tab-configurator">
                    <div class="card-body">
                        <?php
                        $defaultConfig = [
                            'design_options' => ['flare' => true, 'client' => true, 'template' => true],
                            'personalization' => ['nom' => true, 'numero' => true, 'logo' => true, 'sponsor' => true],
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
                            'sizes_kids' => ['6ans', '8ans', '10ans', '12ans', '14ans'],
                            'colors_available' => true,
                            'collar_options' => ['col_v', 'col_rond', 'col_polo'],
                            'min_quantity' => 1,
                            'delivery_time' => '3-4 semaines'
                        ];
                        $config = json_decode($p['configurator_config'] ?? '', true) ?: $defaultConfig;
                        ?>

                        <h4 style="margin-bottom: 20px;">🎨 Options de design</h4>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 25px; padding: 20px; background: #fafbfc; border-radius: 8px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" id="cfg_design_flare" <?= ($config['design_options']['flare'] ?? true) ? 'checked' : '' ?>>
                                <span><strong>Design FLARE</strong><br><small style="color: var(--text-muted);">FLARE crée le design</small></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" id="cfg_design_client" <?= ($config['design_options']['client'] ?? true) ? 'checked' : '' ?>>
                                <span><strong>Design Client</strong><br><small style="color: var(--text-muted);">Le client fournit son design</small></span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" id="cfg_design_template" <?= ($config['design_options']['template'] ?? true) ? 'checked' : '' ?>>
                                <span><strong>Template Catalogue</strong><br><small style="color: var(--text-muted);">Choisir un template</small></span>
                            </label>
                        </div>

                        <h4 style="margin-bottom: 20px;">✏️ Options de personnalisation</h4>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-bottom: 25px; padding: 20px; background: #fafbfc; border-radius: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="cfg_perso_nom" <?= ($config['personalization']['nom'] ?? true) ? 'checked' : '' ?>>
                                Nom / Flocage
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="cfg_perso_numero" <?= ($config['personalization']['numero'] ?? true) ? 'checked' : '' ?>>
                                Numéro
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="cfg_perso_logo" <?= ($config['personalization']['logo'] ?? true) ? 'checked' : '' ?>>
                                Logo club
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="cfg_perso_sponsor" <?= ($config['personalization']['sponsor'] ?? true) ? 'checked' : '' ?>>
                                Sponsors
                            </label>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">📐 Tailles adultes disponibles</label>
                                <input type="text" id="cfg_sizes" class="form-control" value="<?= htmlspecialchars(implode(', ', $config['sizes'] ?? ['XS','S','M','L','XL','XXL','3XL'])) ?>">
                                <div class="form-hint">Séparées par des virgules</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">👶 Tailles enfants disponibles</label>
                                <input type="text" id="cfg_sizes_kids" class="form-control" value="<?= htmlspecialchars(implode(', ', $config['sizes_kids'] ?? ['6ans','8ans','10ans','12ans','14ans'])) ?>">
                                <div class="form-hint">Séparées par des virgules</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">👔 Options de col</label>
                                <input type="text" id="cfg_collars" class="form-control" value="<?= htmlspecialchars(implode(', ', $config['collar_options'] ?? ['col_v','col_rond','col_polo'])) ?>">
                                <div class="form-hint">Ex: col_v, col_rond, col_polo</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">🎨 Couleurs personnalisables</label>
                                <select id="cfg_colors" class="form-control">
                                    <option value="true" <?= ($config['colors_available'] ?? true) ? 'selected' : '' ?>>Oui</option>
                                    <option value="false" <?= !($config['colors_available'] ?? true) ? 'selected' : '' ?>>Non</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">📦 Quantité minimum</label>
                                <input type="number" id="cfg_min_qty" class="form-control" value="<?= intval($config['min_quantity'] ?? 1) ?>" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">🚚 Délai de livraison</label>
                                <input type="text" id="cfg_delivery" class="form-control" value="<?= htmlspecialchars($config['delivery_time'] ?? '3-4 semaines') ?>">
                            </div>
                        </div>

                        <hr style="margin: 25px 0; border: none; border-top: 1px solid var(--border);">

                        <!-- Hidden field pour stocker le JSON -->
                        <textarea name="configurator_config" id="configurator_config_json" class="form-control" style="min-height: 150px; font-family: monospace; font-size: 11px; display: none;"><?= htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="show_json_config" onchange="document.getElementById('configurator_config_json').style.display = this.checked ? 'block' : 'none'">
                                Afficher/modifier le JSON brut (avancé)
                            </label>
                        </div>

                        <script>
                        function updateConfigJSON() {
                            var config = {
                                design_options: {
                                    flare: document.getElementById('cfg_design_flare').checked,
                                    client: document.getElementById('cfg_design_client').checked,
                                    template: document.getElementById('cfg_design_template').checked
                                },
                                personalization: {
                                    nom: document.getElementById('cfg_perso_nom').checked,
                                    numero: document.getElementById('cfg_perso_numero').checked,
                                    logo: document.getElementById('cfg_perso_logo').checked,
                                    sponsor: document.getElementById('cfg_perso_sponsor').checked
                                },
                                sizes: document.getElementById('cfg_sizes').value.split(',').map(s => s.trim()).filter(s => s),
                                sizes_kids: document.getElementById('cfg_sizes_kids').value.split(',').map(s => s.trim()).filter(s => s),
                                colors_available: document.getElementById('cfg_colors').value === 'true',
                                collar_options: document.getElementById('cfg_collars').value.split(',').map(s => s.trim()).filter(s => s),
                                min_quantity: parseInt(document.getElementById('cfg_min_qty').value) || 1,
                                delivery_time: document.getElementById('cfg_delivery').value
                            };
                            document.getElementById('configurator_config_json').value = JSON.stringify(config, null, 2);
                        }
                        // Attach to all config inputs
                        document.querySelectorAll('[id^="cfg_"]').forEach(function(el) {
                            el.addEventListener('change', updateConfigJSON);
                            el.addEventListener('input', updateConfigJSON);
                        });
                        // Initialiser le JSON au chargement
                        updateConfigJSON();
                        </script>
                        <script>
                        // Fonction appelée avant la soumission du formulaire produit
                        function prepareProductSubmit() {
                            // Mettre à jour le JSON du configurateur
                            if (typeof updateConfigJSON === 'function') {
                                updateConfigJSON();
                            }
                            // Synchroniser tous les éditeurs Quill vers leurs textareas
                            document.querySelectorAll('textarea.wysiwyg').forEach(function(textarea) {
                                var container = textarea.previousElementSibling;
                                if (container && container.classList.contains('quill-editor-container')) {
                                    var editor = container.querySelector('.ql-editor');
                                    if (editor) {
                                        textarea.value = editor.innerHTML;
                                        console.log('Synced ' + textarea.name + ':', textarea.value.substring(0, 100));
                                    }
                                }
                            });
                            // Debug: afficher les valeurs importantes
                            console.log('tab_description:', document.querySelector('[name="tab_description"]')?.value?.substring(0, 100));
                            console.log('configurator_config:', document.querySelector('[name="configurator_config"]')?.value?.substring(0, 100));
                            console.log('meta_title:', document.querySelector('[name="meta_title"]')?.value);
                            return true; // Permettre la soumission
                        }
                        </script>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 15px;">🎨 Templates associés à ce produit</h4>
                        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 13px;">
                            Sélectionnez les templates disponibles pour ce produit dans le configurateur. Si aucun n'est sélectionné, tous les templates actifs seront disponibles.
                        </p>

                        <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                            <input type="text" id="template-search-product" class="form-control" placeholder="Rechercher un template..." style="max-width: 250px;">
                            <select id="template-sport-filter" class="form-control" style="max-width: 180px;" onchange="filterProductTemplates()">
                                <option value="">-- Tous les sports --</option>
                                <?php
                                $allSportsForTemplates = [];
                                foreach ($data['all_templates'] ?? [] as $tpl) {
                                    if (!empty($tpl['sport'])) {
                                        foreach (explode(',', $tpl['sport']) as $s) {
                                            $allSportsForTemplates[trim($s)] = true;
                                        }
                                    }
                                }
                                foreach (array_keys($allSportsForTemplates) as $sport): ?>
                                <option value="<?= htmlspecialchars($sport) ?>"><?= htmlspecialchars($sport) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-light" onclick="selectAllProductTemplates()">Tout sélectionner</button>
                            <button type="button" class="btn btn-light" onclick="deselectAllProductTemplates()">Tout désélectionner</button>
                        </div>

                        <div id="product-templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 15px; background: #fafbfc; border-radius: 8px; border: 1px solid var(--border);">
                            <?php
                            $associatedTemplates = $data['associated_templates'] ?? [];
                            foreach ($data['all_templates'] ?? [] as $tpl):
                                $isSelected = in_array($tpl['id'], $associatedTemplates);
                            ?>
                            <label class="product-template-item" data-id="<?= $tpl['id'] ?>" data-name="<?= htmlspecialchars(strtolower($tpl['nom'] ?? $tpl['filename'])) ?>" data-sport="<?= htmlspecialchars($tpl['sport'] ?? '') ?>" style="display: flex; flex-direction: column; background: #fff; border: 2px solid <?= $isSelected ? 'var(--primary)' : 'var(--border)' ?>; border-radius: 8px; overflow: hidden; cursor: pointer; transition: all 0.2s;">
                                <div style="height: 80px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if (!empty($tpl['path'])): ?>
                                    <img src="<?= htmlspecialchars($tpl['path']) ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    <?php else: ?>
                                    <span style="font-size: 32px; opacity: 0.3;">🎨</span>
                                    <?php endif; ?>
                                </div>
                                <div style="padding: 8px; display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="product_templates[]" value="<?= $tpl['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="flex-shrink: 0;">
                                    <div style="overflow: hidden;">
                                        <div style="font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($tpl['nom'] ?? $tpl['filename']) ?></div>
                                        <?php if (!empty($tpl['sport'])): ?>
                                        <div style="font-size: 10px; color: var(--text-muted);"><?= htmlspecialchars($tpl['sport']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>

                            <?php if (empty($data['all_templates'])): ?>
                            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 20px;">
                                Aucun template disponible. <a href="?page=templates">Gérer les templates</a>
                            </p>
                            <?php endif; ?>
                        </div>

                        <script>
                        (function() {
                            const grid = document.getElementById('product-templates-grid');
                            const searchInput = document.getElementById('template-search-product');
                            const sportFilter = document.getElementById('template-sport-filter');

                            // Recherche
                            searchInput?.addEventListener('input', filterProductTemplates);

                            window.filterProductTemplates = function() {
                                const term = searchInput.value.toLowerCase();
                                const sport = sportFilter.value;
                                grid.querySelectorAll('.product-template-item').forEach(item => {
                                    const name = item.dataset.name || '';
                                    const itemSport = item.dataset.sport || '';
                                    const matchSearch = name.includes(term);
                                    const matchSport = !sport || itemSport.includes(sport);
                                    item.style.display = (matchSearch && matchSport) ? 'flex' : 'none';
                                });
                            };

                            window.selectAllProductTemplates = function() {
                                grid.querySelectorAll('.product-template-item').forEach(item => {
                                    if (item.style.display !== 'none') {
                                        const cb = item.querySelector('input[type="checkbox"]');
                                        cb.checked = true;
                                        item.style.borderColor = 'var(--primary)';
                                    }
                                });
                            };

                            window.deselectAllProductTemplates = function() {
                                grid.querySelectorAll('.product-template-item').forEach(item => {
                                    const cb = item.querySelector('input[type="checkbox"]');
                                    cb.checked = false;
                                    item.style.borderColor = 'var(--border)';
                                });
                            };

                            // Visual feedback on checkbox change
                            grid.querySelectorAll('.product-template-item input[type="checkbox"]').forEach(cb => {
                                cb.addEventListener('change', function() {
                                    this.closest('.product-template-item').style.borderColor = this.checked ? 'var(--primary)' : 'var(--border)';
                                });
                            });
                        })();
                        </script>
                    </div>
                </div>

                <!-- TAB: SEO -->
                <div class="tab-content <?= $tab === 'seo' ? 'active' : '' ?>" id="tab-seo">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Meta Title (Nom SEO)</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($p['meta_title'] ?? '') ?>">
                            <div class="form-hint">Ce nom sera utilisé pour l'affichage sur le site. Recommandé: 50-60 caractères</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['meta_description'] ?? '') ?></textarea>
                            <div class="form-hint">Recommandé: 150-160 caractères</div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SETTINGS -->
                <div class="tab-content <?= $tab === 'settings' ? 'active' : '' ?>" id="tab-settings">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">🔧 Statut du produit</h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Produit actif</label>
                                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #fafbfc; border-radius: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="active" value="1" <?= ($p['active'] ?? 1) ? 'checked' : '' ?>>
                                        <span style="color: #22c55e; font-weight: 600;">✓ Actif</span>
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="radio" name="active" value="0" <?= !($p['active'] ?? 1) ? 'checked' : '' ?>>
                                        <span style="color: #ef4444; font-weight: 600;">✕ Inactif</span>
                                    </label>
                                </div>
                                <div class="form-hint">Un produit inactif n'apparaît pas sur le site</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Statut du stock</label>
                                <select name="stock_status" class="form-control">
                                    <option value="in_stock" <?= ($p['stock_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : '' ?>>✅ En stock</option>
                                    <option value="preorder" <?= ($p['stock_status'] ?? '') === 'preorder' ? 'selected' : '' ?>>⏳ Précommande</option>
                                    <option value="out_of_stock" <?= ($p['stock_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>❌ Rupture de stock</option>
                                </select>
                            </div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">🏷️ Étiquettes et badges</h4>

                        <div class="form-group">
                            <label class="form-label">Étiquettes</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                                <?php
                                $currentTags = array_filter(array_map('trim', explode(',', $p['etiquettes'] ?? '')));
                                $availableTags = ['Nouveau', 'Promo', 'Best-seller', 'Exclusif', 'Limited Edition', 'Éco-responsable'];
                                foreach ($availableTags as $tag):
                                    $isChecked = in_array($tag, $currentTags);
                                ?>
                                <label style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: <?= $isChecked ? '#fff5f3' : '#fafbfc' ?>; border: 1px solid <?= $isChecked ? '#FF4B26' : '#e2e8f0' ?>; border-radius: 20px; cursor: pointer; transition: all 0.2s;">
                                    <input type="checkbox" name="etiquettes[]" value="<?= $tag ?>" <?= $isChecked ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.background = this.checked ? '#fff5f3' : '#fafbfc'; this.parentElement.style.borderColor = this.checked ? '#FF4B26' : '#e2e8f0';">
                                    <?= $tag ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="text" name="etiquettes_custom" class="form-control" placeholder="Autres étiquettes séparées par des virgules" style="margin-top: 8px;">
                            <div class="form-hint">Les étiquettes apparaissent comme badges sur la fiche produit</div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">🔗 URL et référencement</h4>

                        <div class="form-group">
                            <label class="form-label">Slug (URL)</label>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="color: var(--text-muted);">/produit/</span>
                                <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($p['slug'] ?? '') ?>" style="flex: 1;">
                            </div>
                            <div class="form-hint">Laissez vide pour générer automatiquement depuis le nom</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">URL personnalisée (optionnel)</label>
                            <input type="text" name="url" class="form-control" value="<?= htmlspecialchars($p['url'] ?? '') ?>" placeholder="https://...">
                            <div class="form-hint">Remplace l'URL par défaut du produit</div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">📊 Options d'affichage</h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Mise en avant</label>
                                <div style="display: flex; flex-direction: column; gap: 10px; padding: 15px; background: #fafbfc; border-radius: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="featured" value="1" <?= !empty($p['featured']) ? 'checked' : '' ?>>
                                        ⭐ Produit vedette (page d'accueil)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="is_new" value="1" <?= !empty($p['is_new']) ? 'checked' : '' ?>>
                                        🆕 Nouveauté (badge "Nouveau")
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="on_sale" value="1" <?= !empty($p['on_sale']) ? 'checked' : '' ?>>
                                        💰 En promotion
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Position / Ordre</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= intval($p['sort_order'] ?? 0) ?>" min="0">
                                <div class="form-hint">Les produits avec un ordre plus bas apparaissent en premier</div>
                            </div>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">🔗 Produits liés</h4>

                        <div class="form-group">
                            <label class="form-label">Produits associés</label>
                            <select name="related_products[]" class="form-control" multiple size="6" style="min-height: 150px;">
                                <?php
                                $relatedIds = json_decode($p['related_products'] ?? '[]', true) ?: [];
                                foreach ($data['all_products'] ?? [] as $relProd):
                                    if ($relProd['id'] == $id) continue;
                                    $selName = !empty($relProd['meta_title']) ? $relProd['meta_title'] : $relProd['nom'];
                                ?>
                                <option value="<?= $relProd['id'] ?>" <?= in_array($relProd['id'], $relatedIds) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($selName) ?> (<?= $relProd['sport'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs produits</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="?page=products" class="btn btn-light">← Retour aux produits</a>
                    <div style="display: flex; gap: 10px;">
                        <?php if (!$productIsNew && !empty($p['reference'])): ?>
                        <a href="/produit/<?= htmlspecialchars($p['reference']) ?>" target="_blank" class="btn btn-light" style="display: inline-flex; align-items: center; gap: 6px;">
                            👁️ Voir le produit
                        </a>
                        <button type="button" class="btn btn-danger" onclick="deleteProduct(<?= $id ?>, '<?= htmlspecialchars($p['nom'] ?? $p['reference']) ?>')">
                            🗑️ Supprimer
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <?= $productIsNew ? '✅ Créer le produit' : '💾 Enregistrer les modifications' ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }

        function deleteProduct(id, name) {
            if (!confirm('⚠️ ATTENTION!\n\nVoulez-vous vraiment supprimer définitivement le produit:\n"' + name + '" ?\n\nCette action est IRRÉVERSIBLE!')) {
                return;
            }
            // Double confirmation
            if (!confirm('Dernière confirmation: Supprimer définitivement ce produit?')) {
                return;
            }
            // Créer un formulaire caché et soumettre
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=products';
            form.innerHTML = '<input type="hidden" name="action" value="delete_product">' +
                           '<input type="hidden" name="id" value="' + id + '">' +
                           '<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">';
            document.body.appendChild(form);
            form.submit();
        }
        </script>

        <?php // ============ CATEGORIES ============ ?>
        <?php elseif ($page === 'categories'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Catégories</span>
                <a href="?page=category" class="btn btn-primary">+ Nouvelle catégorie</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Nom</th><th>Type</th><th>Slug</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                            <td><span class="badge badge-<?= $c['type'] === 'sport' ? 'info' : 'success' ?>"><?= $c['type'] ?></span></td>
                            <td><?= htmlspecialchars($c['slug']) ?></td>
                            <td><a href="?page=category&id=<?= $c['id'] ?>" class="btn btn-sm btn-light">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ CATEGORY EDIT ============ ?>
        <?php elseif ($page === 'category'): ?>
        <?php $c = $data['item'] ?? []; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $id ? 'Modifier' : 'Nouvelle' ?> catégorie</span>
            </div>
            <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
                <input type="hidden" name="action" value="save_category">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($c['nom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($c['slug'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control">
                                <option value="sport" <?= ($c['type'] ?? '') === 'sport' ? 'selected' : '' ?>>Sport</option>
                                <option value="famille" <?= ($c['type'] ?? '') === 'famille' ? 'selected' : '' ?>>Famille produit</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image (URL)</label>
                        <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($c['image'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($c['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?page=categories" class="btn btn-light">← Retour</a>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>

        <?php // ============ PAGES HTML ============ ?>
        <?php elseif ($page === 'pages'): ?>
        <?php $filterType = $_GET['filter'] ?? ''; ?>

        <!-- Filtres -->
        <div class="filters" style="margin-bottom: 20px;">
            <a href="?page=pages" class="btn <?= $filterType === '' ? 'btn-primary' : 'btn-light' ?>">Toutes (<?= count($data['items'] ?? []) ?>)</a>
            <a href="?page=pages&filter=info" class="btn <?= $filterType === 'info' ? 'btn-primary' : 'btn-light' ?>">Info (<?= count(array_filter($data['items'] ?? [], fn($p) => $p['type'] === 'info')) ?>)</a>
            <a href="?page=pages&filter=category" class="btn <?= $filterType === 'category' ? 'btn-primary' : 'btn-light' ?>">Catégories (<?= count(array_filter($data['items'] ?? [], fn($p) => $p['type'] === 'category')) ?>)</a>
            <a href="?page=pages&filter=blog" class="btn <?= $filterType === 'blog' ? 'btn-primary' : 'btn-light' ?>">Blog (<?= count(array_filter($data['items'] ?? [], fn($p) => $p['type'] === 'blog')) ?>)</a>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Pages HTML</span>
                <span style="font-size: 12px; color: var(--text-muted);">Édition directe des fichiers HTML</span>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Titre</th><th>Type</th><th>Fichier</th><th>URL</th><th>Modifié</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $pg):
                        $pageType = $pg['type'] ?? 'info';
                        if ($filterType !== '' && $pageType !== $filterType) continue;
                        if ($pageType === 'category') {
                            $pageUrl = '/categorie/' . $pg['slug'];
                        } elseif ($pageType === 'blog') {
                            $pageUrl = '/blog/' . $pg['slug'];
                        } else {
                            $pageUrl = '/info/' . $pg['slug'];
                        }
                        $badgeClass = $pageType === 'category' ? 'info' : ($pageType === 'blog' ? 'warning' : 'secondary');
                        $typeLabel = $pageType === 'category' ? 'Catégorie' : ($pageType === 'blog' ? 'Blog' : 'Info');
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pg['title']) ?></strong></td>
                            <td><span class="badge badge-<?= $badgeClass ?>"><?= $typeLabel ?></span></td>
                            <td><code style="font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($pg['filename']) ?></code></td>
                            <td><a href="<?= $pageUrl ?>" target="_blank" style="color:var(--primary); font-size: 12px;"><?= $pageUrl ?></a></td>
                            <td style="font-size: 12px; color: var(--text-muted);"><?= date('d/m/Y H:i', $pg['modified']) ?></td>
                            <td>
                                <a href="?page=page&type=<?= urlencode($pg['type']) ?>&slug=<?= urlencode($pg['slug']) ?>" class="btn btn-sm btn-primary">Modifier</a>
                                <a href="<?= $pageUrl ?>" target="_blank" class="btn btn-sm btn-light">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ PAGE EDIT - FICHIER HTML ============ ?>
        <?php elseif ($page === 'page'): ?>
        <?php
        $filePath = $data['file_path'] ?? '';
        $htmlContent = $data['content'] ?? '';
        $filename = $data['filename'] ?? '';
        $slug = $data['slug'] ?? '';
        $pageType = $data['type'] ?? 'info';

        // Déterminer l'URL de preview
        if ($pageType === 'category') {
            $previewUrl = '/categorie/' . $slug;
        } elseif ($pageType === 'blog') {
            $previewUrl = '/blog/' . $slug;
        } else {
            $previewUrl = '/info/' . $slug;
        }

        // Extraire le titre du HTML
        preg_match('/<title>([^<]+)<\/title>/i', $htmlContent, $titleMatch);
        $pageTitle = $titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug));

        if (empty($filePath)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <p style="color: var(--text-muted);">Aucun fichier sélectionné.</p>
                <a href="?page=pages" class="btn btn-primary">← Retour à la liste des pages</a>
            </div>
        </div>
        <?php else: ?>

        <!-- ========== ÉDITEUR VISUEL COMPLET ========== -->
        <style>
        .ve-container { display: flex; height: calc(100vh - 80px); margin: -30px; }
        .ve-sidebar { width: 340px; background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .ve-main { flex: 1; display: flex; flex-direction: column; background: #e5e7eb; }
        .ve-toolbar { padding: 10px 15px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; gap: 8px; align-items: center; }
        .ve-preview-wrap { flex: 1; padding: 20px; overflow: auto; display: flex; justify-content: center; }
        .ve-group { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .ve-group:last-child { border-bottom: none; }
        .ve-group-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px; }
        .ve-row { display: flex; gap: 8px; margin-bottom: 8px; }
        .ve-field { flex: 1; }
        .ve-label { font-size: 11px; color: #64748b; margin-bottom: 4px; display: block; }
        .ve-input { width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; }
        .ve-input:focus { outline: none; border-color: #FF4B26; }
        .ve-color-wrap { display: flex; gap: 6px; align-items: center; }
        .ve-color { width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 4px; cursor: pointer; padding: 2px; }
        </style>

        <div class="ve-container">
            <!-- SIDEBAR -->
            <div class="ve-sidebar">
                <div style="padding: 15px; border-bottom: 1px solid #e2e8f0;">
                    <h3 style="margin: 0 0 5px; font-size: 15px;"><?= htmlspecialchars($pageTitle) ?></h3>
                    <code style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($filename) ?></code>
                </div>

                <?php if ($pageType === 'category'): ?>
                <!-- Onglets pour pages catégorie -->
                <div style="display: flex; border-bottom: 1px solid #e2e8f0;">
                    <button class="ve-tab active" data-tab="styles" onclick="switchTab('styles')">🎨 Styles</button>
                    <button class="ve-tab" data-tab="products" onclick="switchTab('products')">📦 Produits</button>
                </div>
                <style>
                .ve-tab { flex: 1; padding: 12px; border: none; background: none; font-size: 13px; cursor: pointer; color: #64748b; transition: all 0.2s; }
                .ve-tab:hover { background: #f8fafc; }
                .ve-tab.active { color: #FF4B26; border-bottom: 2px solid #FF4B26; margin-bottom: -1px; background: #fff; }
                .ve-tab-content { display: none; }
                .ve-tab-content.active { display: block; }
                .product-item { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; background: #fff; cursor: grab; }
                .product-item:hover { border-color: #FF4B26; }
                .product-item.selected { border-color: #FF4B26; background: #fff5f3; }
                .product-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; background: #f1f5f9; }
                .product-item .info { flex: 1; overflow: hidden; }
                .product-item .name { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .product-item .meta { font-size: 10px; color: #64748b; }
                .product-item .checkbox { width: 18px; height: 18px; }
                .selected-products { min-height: 100px; border: 2px dashed #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 15px; }
                .selected-products.drag-over { border-color: #FF4B26; background: #fff5f3; }
                </style>
                <?php endif; ?>

                <!-- Tab Styles -->
                <div id="tabStyles" class="ve-tab-content <?= $pageType !== 'category' ? '' : 'active' ?>" style="flex: 1; overflow-y: auto; padding: 15px;">
                    <div id="stylePanel">
                        <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                            <p>👆 Cliquez sur un élément dans la preview pour le modifier</p>
                        </div>
                    </div>
                </div>

                <?php if ($pageType === 'category'): ?>
                <!-- Tab Produits -->
                <div id="tabProducts" class="ve-tab-content" style="flex: 1; overflow-y: auto; padding: 15px;">
                    <div style="margin-bottom: 15px;">
                        <div class="ve-group-title">Produits sélectionnés (<?= count($data['selected_products'] ?? []) ?>)</div>
                        <div class="selected-products" id="selectedProducts">
                            <?php
                            $selectedIds = $data['selected_products'] ?? [];
                            $allProducts = $data['all_products'] ?? [];
                            foreach ($selectedIds as $prodId):
                                $prod = array_filter($allProducts, fn($p) => $p['id'] == $prodId);
                                $prod = reset($prod);
                                if ($prod):
                            ?>
                            <div class="product-item selected" data-id="<?= $prod['id'] ?>" draggable="true">
                                <img src="<?= htmlspecialchars($prod['photo_1'] ?: '/assets/images/placeholder.jpg') ?>" alt="">
                                <div class="info">
                                    <div class="name"><?= htmlspecialchars($prod['nom_seo']) ?></div>
                                    <div class="meta"><?= htmlspecialchars($prod['sport'] . ' • ' . $prod['famille']) ?></div>
                                </div>
                                <button type="button" class="btn btn-sm" style="color:#ef4444;" onclick="removeProduct(<?= $prod['id'] ?>)">✕</button>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;">Glissez pour réorganiser</p>
                    </div>

                    <div>
                        <div class="ve-group-title">Tous les produits</div>
                        <input type="text" class="ve-input" id="productSearch" placeholder="Rechercher un produit..." style="margin-bottom: 10px;">
                        <select class="ve-input" id="productFilter" style="margin-bottom: 10px;">
                            <option value="">Tous les sports</option>
                            <?php
                            $sports = array_unique(array_column($allProducts, 'sport'));
                            foreach ($sports as $sport): ?>
                            <option value="<?= htmlspecialchars($sport) ?>"><?= htmlspecialchars($sport) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="availableProducts" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($allProducts as $prod):
                                $isSelected = in_array($prod['id'], $selectedIds);
                            ?>
                            <div class="product-item <?= $isSelected ? 'selected' : '' ?>" data-id="<?= $prod['id'] ?>" data-sport="<?= htmlspecialchars($prod['sport']) ?>" data-name="<?= htmlspecialchars(strtolower($prod['nom_seo'])) ?>">
                                <input type="checkbox" class="checkbox" <?= $isSelected ? 'checked' : '' ?> onchange="toggleProduct(<?= $prod['id'] ?>, this.checked)">
                                <img src="<?= htmlspecialchars($prod['photo_1'] ?: '/assets/images/placeholder.jpg') ?>" alt="">
                                <div class="info">
                                    <div class="name"><?= htmlspecialchars($prod['nom_seo']) ?></div>
                                    <div class="meta"><?= htmlspecialchars($prod['sport'] . ' • ' . number_format($prod['prix_500'] ?? 0, 2)) ?> €</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="padding: 15px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                    <form method="POST" id="saveForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="save_page">
                        <input type="hidden" name="page_type" value="<?= htmlspecialchars($pageType) ?>">
                        <input type="hidden" name="page_slug" value="<?= htmlspecialchars($slug) ?>">
                        <input type="hidden" name="content" id="finalContent">
                        <div id="productInputs"></div>
                        <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:10px;">💾 Enregistrer</button>
                    </form>
                    <div style="display:flex; gap:8px;">
                        <a href="?page=pages" class="btn btn-light" style="flex:1;">← Retour</a>
                        <a href="<?= $previewUrl ?>" target="_blank" class="btn btn-light" style="flex:1;">Voir ↗</a>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div class="ve-main">
                <div class="ve-toolbar">
                    <button class="btn btn-light btn-sm" onclick="veUndo()">↩️ Annuler</button>
                    <button class="btn btn-light btn-sm" onclick="veRedo()">↪️ Rétablir</button>
                    <div style="flex:1;"></div>
                    <span style="font-size:12px; color:#64748b;" id="selectedInfo">Aucun élément sélectionné</span>
                    <div style="flex:1;"></div>
                    <button class="btn btn-light btn-sm" onclick="setWidth('100%')">🖥️</button>
                    <button class="btn btn-light btn-sm" onclick="setWidth('768px')">📱</button>
                    <button class="btn btn-light btn-sm" onclick="setWidth('375px')">📲</button>
                </div>
                <div class="ve-preview-wrap">
                    <div id="previewBox" style="width:100%; background:#fff; box-shadow:0 4px 20px rgba(0,0,0,0.12); border-radius:4px; overflow:hidden;">
                        <iframe id="veFrame" style="width:100%; height:800px; border:none;"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function(){
            var frame = document.getElementById('veFrame');
            var fDoc = null;
            var selected = null;
            var history = [];
            var hIdx = -1;
            var html = <?= json_encode($htmlContent, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

            // Load
            function load(h) {
                fDoc = frame.contentDocument || frame.contentWindow.document;
                fDoc.open();
                fDoc.write(h);
                fDoc.close();
                frame.onload = setup;
                setTimeout(setup, 200);
            }

            // Setup
            function setup() {
                if (!fDoc || !fDoc.body) return;

                // Style
                var s = fDoc.createElement('style');
                s.id = 'veS';
                s.textContent = '.veH{outline:2px dashed #3b82f6!important;cursor:pointer}.veS{outline:3px solid #FF4B26!important;outline-offset:2px}';
                if (!fDoc.getElementById('veS')) fDoc.head.appendChild(s);

                // Events
                fDoc.body.querySelectorAll('*').forEach(function(el){
                    el.onmouseenter = function(){ if(!this.classList.contains('veS')) this.classList.add('veH'); };
                    el.onmouseleave = function(){ this.classList.remove('veH'); };
                    el.onclick = function(e){ e.preventDefault(); e.stopPropagation(); sel(this); };
                });

                saveH();
            }

            // Select
            function sel(el) {
                if (selected) { selected.classList.remove('veS'); selected.contentEditable = 'false'; }
                selected = el;
                el.classList.remove('veH');
                el.classList.add('veS');
                document.getElementById('selectedInfo').innerHTML = '&lt;' + el.tagName.toLowerCase() + '&gt;' + (el.className ? ' .' + el.className.split(' ')[0] : '');
                showPanel(el);
            }

            // Show panel
            function showPanel(el) {
                var cs = fDoc.defaultView.getComputedStyle(el);
                var p = document.getElementById('stylePanel');

                p.innerHTML =
                '<div class="ve-group">' +
                    '<div class="ve-group-title">📝 Contenu</div>' +
                    '<textarea id="pText" class="ve-input" rows="4">' + el.innerHTML.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</textarea>' +
                    '<button class="btn btn-sm btn-primary" style="width:100%;margin-top:8px;" onclick="applyText()">Appliquer</button>' +
                '</div>' +
                '<div class="ve-group">' +
                    '<div class="ve-group-title">🎨 Couleurs</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Texte</label><div class="ve-color-wrap"><input type="color" id="pColor" class="ve-color" value="' + rgb2hex(cs.color) + '"><input type="text" class="ve-input" id="pColorT" value="' + cs.color + '" style="flex:1;"></div></div>' +
                    '</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Fond</label><div class="ve-color-wrap"><input type="color" id="pBg" class="ve-color" value="' + rgb2hex(cs.backgroundColor) + '"><input type="text" class="ve-input" id="pBgT" value="' + cs.backgroundColor + '" style="flex:1;"></div></div>' +
                    '</div>' +
                '</div>' +
                '<div class="ve-group">' +
                    '<div class="ve-group-title">📏 Typographie</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Taille</label><input type="text" id="pSize" class="ve-input" value="' + cs.fontSize + '"></div>' +
                        '<div class="ve-field"><label class="ve-label">Graisse</label><select id="pWeight" class="ve-input"><option value="400"' + (cs.fontWeight==='400'?' selected':'') + '>Normal</option><option value="600"' + (cs.fontWeight==='600'?' selected':'') + '>Semi-bold</option><option value="700"' + (cs.fontWeight==='700'?' selected':'') + '>Bold</option></select></div>' +
                    '</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Line Height</label><input type="text" id="pLh" class="ve-input" value="' + cs.lineHeight + '"></div>' +
                        '<div class="ve-field"><label class="ve-label">Letter Spacing</label><input type="text" id="pLs" class="ve-input" value="' + cs.letterSpacing + '"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="ve-group">' +
                    '<div class="ve-group-title">📐 Espacement</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Padding</label><input type="text" id="pPad" class="ve-input" value="' + cs.padding + '"></div>' +
                        '<div class="ve-field"><label class="ve-label">Margin</label><input type="text" id="pMar" class="ve-input" value="' + cs.margin + '"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="ve-group">' +
                    '<div class="ve-group-title">📦 Bordure</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Border</label><input type="text" id="pBorder" class="ve-input" value="' + cs.border + '"></div>' +
                        '<div class="ve-field"><label class="ve-label">Radius</label><input type="text" id="pRadius" class="ve-input" value="' + cs.borderRadius + '"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="ve-group">' +
                    '<div class="ve-group-title">📐 Dimensions</div>' +
                    '<div class="ve-row">' +
                        '<div class="ve-field"><label class="ve-label">Width</label><input type="text" id="pW" class="ve-input" value="' + (el.style.width||'auto') + '"></div>' +
                        '<div class="ve-field"><label class="ve-label">Height</label><input type="text" id="pH" class="ve-input" value="' + (el.style.height||'auto') + '"></div>' +
                    '</div>' +
                '</div>' +
                '<button class="btn btn-primary" style="width:100%;" onclick="applyAll()">✓ Appliquer tous les styles</button>';

                // Bind colors
                setTimeout(function(){
                    var pc = document.getElementById('pColor');
                    var pb = document.getElementById('pBg');
                    if(pc) pc.oninput = function(){ document.getElementById('pColorT').value = this.value; };
                    if(pb) pb.oninput = function(){ document.getElementById('pBgT').value = this.value; };
                }, 50);
            }

            // Apply text
            window.applyText = function() {
                if (!selected) return;
                var t = document.getElementById('pText').value;
                selected.innerHTML = t.replace(/&lt;/g,'<').replace(/&gt;/g,'>');
                saveH();
            };

            // Apply all
            window.applyAll = function() {
                if (!selected) return;
                selected.style.color = document.getElementById('pColor').value;
                selected.style.backgroundColor = document.getElementById('pBg').value;
                selected.style.fontSize = document.getElementById('pSize').value;
                selected.style.fontWeight = document.getElementById('pWeight').value;
                selected.style.lineHeight = document.getElementById('pLh').value;
                selected.style.letterSpacing = document.getElementById('pLs').value;
                selected.style.padding = document.getElementById('pPad').value;
                selected.style.margin = document.getElementById('pMar').value;
                selected.style.border = document.getElementById('pBorder').value;
                selected.style.borderRadius = document.getElementById('pRadius').value;
                var w = document.getElementById('pW').value;
                var h = document.getElementById('pH').value;
                if (w && w !== 'auto') selected.style.width = w;
                if (h && h !== 'auto') selected.style.height = h;
                saveH();
            };

            // Save history
            function saveH() {
                var h = getHtml();
                if (history[hIdx] !== h) {
                    history = history.slice(0, hIdx + 1);
                    history.push(h);
                    hIdx = history.length - 1;
                }
                document.getElementById('finalContent').value = h;
            }

            // Get clean HTML
            function getHtml() {
                if (!fDoc) return html;
                var c = fDoc.documentElement.cloneNode(true);
                var s = c.querySelector('#veS'); if(s) s.remove();
                c.querySelectorAll('.veH,.veS').forEach(function(e){
                    e.classList.remove('veH','veS');
                    if(!e.className) e.removeAttribute('class');
                    e.removeAttribute('contenteditable');
                });
                return '<!DOCTYPE html>\n' + c.outerHTML;
            }

            // Undo/Redo
            window.veUndo = function() { if(hIdx>0){ hIdx--; load(history[hIdx]); } };
            window.veRedo = function() { if(hIdx<history.length-1){ hIdx++; load(history[hIdx]); } };

            // Width
            window.setWidth = function(w) { document.getElementById('previewBox').style.width = w; };

            // RGB to Hex
            function rgb2hex(rgb) {
                if (!rgb || rgb==='transparent' || rgb.indexOf('rgba')===0 && rgb.indexOf(', 0)')>-1) return '#ffffff';
                var m = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                return m ? '#'+[m[1],m[2],m[3]].map(function(x){return('0'+parseInt(x).toString(16)).slice(-2);}).join('') : '#000000';
            }

            // Init
            load(html);
        })();

        <?php if ($pageType === 'category'): ?>
        // ========== GESTION DES PRODUITS ==========
        var selectedProductIds = <?= json_encode($data['selected_products'] ?? []) ?>;
        var allProductsData = <?= json_encode($data['all_products'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        function switchTab(tab) {
            document.querySelectorAll('.ve-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.ve-tab-content').forEach(function(c) { c.classList.remove('active'); });
            document.querySelector('.ve-tab[data-tab="' + tab + '"]').classList.add('active');
            document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
        }

        function toggleProduct(id, checked) {
            if (checked) {
                if (!selectedProductIds.includes(id)) {
                    selectedProductIds.push(id);
                    var prod = allProductsData.find(function(p) { return p.id == id; });
                    if (prod) {
                        var html = '<div class="product-item selected" data-id="' + prod.id + '" draggable="true">' +
                            '<img src="' + (prod.photo_1 || '/assets/images/placeholder.jpg') + '" alt="">' +
                            '<div class="info"><div class="name">' + escapeHtml(prod.nom_seo) + '</div>' +
                            '<div class="meta">' + escapeHtml(prod.sport + ' • ' + prod.famille) + '</div></div>' +
                            '<button type="button" class="btn btn-sm" style="color:#ef4444;" onclick="removeProduct(' + prod.id + ')">✕</button></div>';
                        document.getElementById('selectedProducts').insertAdjacentHTML('beforeend', html);
                    }
                }
            } else {
                removeProduct(id);
            }
            updateProductCount();
            setupDragDrop();
        }

        function removeProduct(id) {
            selectedProductIds = selectedProductIds.filter(function(x) { return x != id; });
            var selEl = document.querySelector('#selectedProducts .product-item[data-id="' + id + '"]');
            if (selEl) selEl.remove();
            var avEl = document.querySelector('#availableProducts .product-item[data-id="' + id + '"]');
            if (avEl) {
                avEl.classList.remove('selected');
                var cb = avEl.querySelector('.checkbox');
                if (cb) cb.checked = false;
            }
            updateProductCount();
        }

        function updateProductCount() {
            var title = document.querySelector('#tabProducts .ve-group-title');
            if (title) title.textContent = 'Produits sélectionnés (' + selectedProductIds.length + ')';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // Recherche et filtre
        document.getElementById('productSearch')?.addEventListener('input', function() {
            filterProducts();
        });
        document.getElementById('productFilter')?.addEventListener('change', function() {
            filterProducts();
        });

        function filterProducts() {
            var search = (document.getElementById('productSearch')?.value || '').toLowerCase();
            var sport = document.getElementById('productFilter')?.value || '';
            document.querySelectorAll('#availableProducts .product-item').forEach(function(el) {
                var name = el.dataset.name || '';
                var elSport = el.dataset.sport || '';
                var show = true;
                if (search && name.indexOf(search) === -1) show = false;
                if (sport && elSport !== sport) show = false;
                el.style.display = show ? 'flex' : 'none';
            });
        }

        // Drag and drop pour réorganiser
        function setupDragDrop() {
            var container = document.getElementById('selectedProducts');
            if (!container) return;
            var items = container.querySelectorAll('.product-item');
            items.forEach(function(item) {
                item.ondragstart = function(e) {
                    e.dataTransfer.setData('text/plain', item.dataset.id);
                    item.style.opacity = '0.5';
                };
                item.ondragend = function() {
                    item.style.opacity = '1';
                };
                item.ondragover = function(e) {
                    e.preventDefault();
                    item.style.borderTop = '3px solid #FF4B26';
                };
                item.ondragleave = function() {
                    item.style.borderTop = '';
                };
                item.ondrop = function(e) {
                    e.preventDefault();
                    item.style.borderTop = '';
                    var dragId = e.dataTransfer.getData('text/plain');
                    var dragEl = container.querySelector('.product-item[data-id="' + dragId + '"]');
                    if (dragEl && dragEl !== item) {
                        container.insertBefore(dragEl, item);
                        updateSelectedOrder();
                    }
                };
            });
        }

        function updateSelectedOrder() {
            selectedProductIds = [];
            document.querySelectorAll('#selectedProducts .product-item').forEach(function(el) {
                selectedProductIds.push(parseInt(el.dataset.id));
            });
        }

        // Avant soumission du formulaire
        document.getElementById('saveForm').addEventListener('submit', function() {
            var container = document.getElementById('productInputs');
            container.innerHTML = '';
            selectedProductIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'page_products[]';
                input.value = id;
                container.appendChild(input);
            });
        });

        setupDragDrop();
        <?php endif; ?>
        </script>
        <?php endif; ?>

        <?php // ============ BLOG ============ ?>
        <?php elseif ($page === 'blog'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Articles de blog (<?= count($data['items'] ?? []) ?>)</span>
                <a href="?page=blog_edit" class="btn btn-primary">+ Nouvel article</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Titre</th><th>URL</th><th>Catégorie</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $post):
                        $blogUrl = '/blog/' . $post['slug'];
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($post['title']) ?></strong></td>
                            <td><a href="<?= $blogUrl ?>" target="_blank" style="color:var(--primary)"><?= $blogUrl ?></a></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($post['category'] ?? '-') ?></span></td>
                            <td><span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>"><?= $post['status'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                            <td><a href="?page=blog_edit&id=<?= $post['id'] ?>" class="btn btn-sm btn-light">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ BLOG EDIT - SIMPLE TEXTE ============ ?>
        <?php elseif ($page === 'blog_edit'): ?>
        <?php $post = $data['item'] ?? []; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $id ? 'Modifier' : 'Nouvel' ?> article</span>
                <?php if ($id && !empty($post['slug'])): ?>
                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" target="_blank" class="btn btn-light">Voir l'article</a>
                <?php endif; ?>
            </div>
            <form method="POST" id="blogForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="save_blog">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titre de l'article</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required placeholder="Ex: Comment personnaliser vos maillots">
                        </div>
                        <div class="form-group">
                            <label class="form-label">URL (slug)</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" required placeholder="comment-personnaliser-maillots">
                            <small style="color:var(--text-muted)">URL: /blog/<?= htmlspecialchars($post['slug'] ?? 'mon-article') ?></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Catégorie</label>
                            <select name="category" class="form-control">
                                <option value="conseils" <?= ($post['category'] ?? '') === 'conseils' ? 'selected' : '' ?>>Conseils</option>
                                <option value="tutoriels" <?= ($post['category'] ?? '') === 'tutoriels' ? 'selected' : '' ?>>Tutoriels</option>
                                <option value="nouveautes" <?= ($post['category'] ?? '') === 'nouveautes' ? 'selected' : '' ?>>Nouveautés</option>
                                <option value="actualites" <?= ($post['category'] ?? '') === 'actualites' ? 'selected' : '' ?>>Actualités</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
                                <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Image mise en avant (URL)</label>
                            <input type="text" name="featured_image" class="form-control" value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>" placeholder="/assets/images/blog/mon-image.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Extrait (affiché dans la liste des articles)</label>
                        <textarea name="excerpt" class="form-control" rows="3" placeholder="Résumé court de l'article..."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            Contenu de l'article
                            <div style="display: flex; gap: 5px;">
                                <button type="button" class="btn btn-sm" id="modeSimpleBtn" onclick="setEditorMode('simple')" style="background: var(--primary); color: #fff;">Texte Simple</button>
                                <button type="button" class="btn btn-sm btn-light" id="modeVisuelBtn" onclick="setEditorMode('visuel')">Éditeur Visuel</button>
                            </div>
                        </label>

                        <!-- MODE SIMPLE: Textarea -->
                        <div id="modeSimple">
                            <textarea name="content" id="contentSimple" class="form-control" rows="20" style="font-size: 14px; line-height: 1.6; font-family: inherit;"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                            <small style="color: var(--text-muted);">Écrivez en HTML simple: &lt;h2&gt;Titre&lt;/h2&gt; &lt;p&gt;Paragraphe&lt;/p&gt; &lt;strong&gt;Gras&lt;/strong&gt;</small>
                        </div>

                        <!-- MODE VISUEL: Iframe WYSIWYG -->
                        <div id="modeVisuel" style="display: none;">
                            <div style="background: #f1f5f9; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 6px 6px 0 0; padding: 8px; display: flex; gap: 4px; flex-wrap: wrap;">
                                <button type="button" class="we-btn" onclick="weCmd('bold')" title="Gras"><b>G</b></button>
                                <button type="button" class="we-btn" onclick="weCmd('italic')" title="Italique"><i>I</i></button>
                                <button type="button" class="we-btn" onclick="weCmd('underline')" title="Souligné"><u>S</u></button>
                                <span class="we-sep"></span>
                                <button type="button" class="we-btn" onclick="weBlock('h2')">H2</button>
                                <button type="button" class="we-btn" onclick="weBlock('h3')">H3</button>
                                <button type="button" class="we-btn" onclick="weBlock('p')">P</button>
                                <span class="we-sep"></span>
                                <button type="button" class="we-btn" onclick="weCmd('insertUnorderedList')">• Liste</button>
                                <button type="button" class="we-btn" onclick="weCmd('insertOrderedList')">1. Liste</button>
                                <span class="we-sep"></span>
                                <button type="button" class="we-btn" onclick="weLink()">Lien</button>
                                <button type="button" class="we-btn" onclick="weImage()">Image</button>
                            </div>
                            <iframe id="weFrame" style="width: 100%; height: 400px; border: 1px solid #e2e8f0; border-radius: 0 0 6px 6px; background: #fff;"></iframe>
                        </div>
                    </div>
                    <style>
                    .we-btn { padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; font-size: 13px; }
                    .we-btn:hover { background: #f3f4f6; }
                    .we-sep { width: 1px; background: #d1d5db; margin: 0 4px; }
                    </style>
                    <div class="form-row" style="margin-top: 15px;">
                        <div class="form-group">
                            <label class="form-label">Meta Title (SEO)</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>" placeholder="Titre pour Google (60 car. max)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description (SEO)</label>
                            <input type="text" name="meta_description" class="form-control" value="<?= htmlspecialchars($post['meta_description'] ?? '') ?>" placeholder="Description pour Google (160 car. max)">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?page=blog" class="btn btn-light">← Retour</a>
                    <button type="submit" class="btn btn-primary">Enregistrer l'article</button>
                </div>
            </form>
        </div>

        <script>
        var currentMode = 'simple';
        var weDoc = null;
        var initialContent = <?= json_encode($post['content'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

        function setEditorMode(mode) {
            currentMode = mode;
            document.getElementById('modeSimple').style.display = mode === 'simple' ? 'block' : 'none';
            document.getElementById('modeVisuel').style.display = mode === 'visuel' ? 'block' : 'none';
            document.getElementById('modeSimpleBtn').className = 'btn btn-sm ' + (mode === 'simple' ? '' : 'btn-light');
            document.getElementById('modeSimpleBtn').style.background = mode === 'simple' ? 'var(--primary)' : '';
            document.getElementById('modeSimpleBtn').style.color = mode === 'simple' ? '#fff' : '';
            document.getElementById('modeVisuelBtn').className = 'btn btn-sm ' + (mode === 'visuel' ? '' : 'btn-light');
            document.getElementById('modeVisuelBtn').style.background = mode === 'visuel' ? 'var(--primary)' : '';
            document.getElementById('modeVisuelBtn').style.color = mode === 'visuel' ? '#fff' : '';

            if (mode === 'visuel') {
                initWysiwygEditor();
            } else {
                // Sync back to textarea
                if (weDoc && weDoc.body) {
                    document.getElementById('contentSimple').value = weDoc.body.innerHTML;
                }
            }
        }

        function initWysiwygEditor() {
            var iframe = document.getElementById('weFrame');
            weDoc = iframe.contentDocument || iframe.contentWindow.document;
            weDoc.open();
            weDoc.write('<!DOCTYPE html><html><head><style>body{font-family:system-ui,sans-serif;font-size:15px;line-height:1.7;padding:20px;margin:0;} h2{font-size:24px;margin:20px 0 10px;} h3{font-size:20px;margin:18px 0 8px;} p{margin:10px 0;} img{max-width:100%;height:auto;}</style></head><body contenteditable="true">' + document.getElementById('contentSimple').value + '</body></html>');
            weDoc.close();
            weDoc.body.focus();
        }

        function weCmd(cmd) {
            if (weDoc) {
                weDoc.execCommand(cmd, false, null);
                weDoc.body.focus();
            }
        }

        function weBlock(tag) {
            if (weDoc) {
                weDoc.execCommand('formatBlock', false, '<' + tag + '>');
                weDoc.body.focus();
            }
        }

        function weLink() {
            var url = prompt('URL du lien:', 'https://');
            if (url && weDoc) {
                weDoc.execCommand('createLink', false, url);
            }
        }

        function weImage() {
            var url = prompt('URL de l\'image:', '/assets/images/');
            if (url && weDoc) {
                weDoc.execCommand('insertImage', false, url);
            }
        }

        // Sync before submit
        document.getElementById('blogForm').addEventListener('submit', function(e) {
            if (currentMode === 'visuel' && weDoc && weDoc.body) {
                document.getElementById('contentSimple').value = weDoc.body.innerHTML;
            }
        });

        // Auto-generate slug from title
        document.querySelector('input[name="title"]')?.addEventListener('input', function() {
            var slugInput = document.querySelector('input[name="slug"]');
            if (slugInput && !slugInput.dataset.edited) {
                slugInput.value = this.value
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });
        document.querySelector('input[name="slug"]')?.addEventListener('input', function() {
            this.dataset.edited = 'true';
        });
        </script>

        <?php // ============ CATEGORY PAGES LIST ============ ?>
        <?php elseif ($page === 'category_pages'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Pages Catégorie Dynamiques (<?= count($data['items'] ?? []) ?>)</span>
                <a href="?page=category_page" class="btn btn-primary">+ Nouvelle page</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Titre</th><th>Slug</th><th>Produits</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($data['items'])): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">Aucune page catégorie. Créez-en une !</td></tr>
                    <?php else: ?>
                    <?php foreach ($data['items'] as $cp): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cp['title']) ?></strong></td>
                            <td><code>/categorie/<?= htmlspecialchars($cp['slug']) ?></code></td>
                            <td>
                                <?php
                                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM page_products WHERE page_type='category_page' AND page_slug=?");
                                $countStmt->execute([$cp['slug']]);
                                echo $countStmt->fetchColumn();
                                ?> produits
                            </td>
                            <td>
                                <span class="badge badge-<?= $cp['active'] ? 'success' : 'secondary' ?>"><?= $cp['active'] ? 'Actif' : 'Inactif' ?></span>
                            </td>
                            <td>
                                <a href="?page=category_page&id=<?= $cp['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                                <a href="/categorie/<?= htmlspecialchars($cp['slug']) ?>" target="_blank" class="btn btn-sm btn-light">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ CATEGORY PAGE EDIT ============ ?>
        <?php elseif ($page === 'category_page'): ?>
        <?php
        $cp = $data['item'] ?? [];
        $selectedProducts = $data['selected_products'] ?? [];
        $allProducts = $data['all_products'] ?? [];
        ?>
        <form method="POST" action="?page=category_page<?= $id ? "&id=$id" : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="save_category_page">

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><?= $id ? 'Modifier' : 'Nouvelle' ?> page catégorie</span>
                    <div>
                        <?php if ($id): ?>
                        <a href="/categorie/<?= htmlspecialchars($cp['slug'] ?? '') ?>" target="_blank" class="btn btn-light">Voir la page</a>
                        <?php endif; ?>
                        <a href="?page=category_pages" class="btn btn-light">← Retour</a>
                    </div>
                </div>

                <div class="tabs-nav">
                    <button type="button" class="tab-btn active" onclick="switchCatTab('general')">Général</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('hero')">Hero</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('products')">Produits</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('cta')">CTA</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('content')">Contenu</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('testimonials')">Témoignages</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('faq')">FAQ</button>
                    <button type="button" class="tab-btn" onclick="switchCatTab('seo')">SEO</button>
                </div>

                <!-- TAB GENERAL -->
                <div class="tab-content active" id="cat-tab-general">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Titre de la page *</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($cp['title'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Slug (URL) *</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <span style="color: var(--text-muted);">/categorie/</span>
                                    <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($cp['slug'] ?? '') ?>" required pattern="[a-z0-9\-]+" style="flex: 1;">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="active" value="1" <?= ($cp['active'] ?? 1) ? 'checked' : '' ?>>
                                Page active (visible sur le site)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- TAB HERO -->
                <div class="tab-content" id="cat-tab-hero">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Titre Hero</label>
                            <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($cp['hero_title'] ?? '') ?>" placeholder="Ex: MAILLOTS FOOTBALL PERSONNALISÉS">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sous-titre Hero</label>
                            <textarea name="hero_subtitle" class="form-control" rows="3" placeholder="Description affichée sous le titre"><?= htmlspecialchars($cp['hero_subtitle'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Image de fond (URL)</label>
                                <input type="text" name="hero_image" class="form-control" value="<?= htmlspecialchars($cp['hero_image'] ?? '') ?>" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Texte du bouton CTA</label>
                                <input type="text" name="hero_cta_text" class="form-control" value="<?= htmlspecialchars($cp['hero_cta_text'] ?? '') ?>" placeholder="Ex: Voir le catalogue">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lien du bouton CTA</label>
                                <input type="text" name="hero_cta_link" class="form-control" value="<?= htmlspecialchars($cp['hero_cta_link'] ?? '') ?>" placeholder="#products">
                            </div>
                        </div>

                        <hr style="margin: 30px 0;">
                        <h4>Barre de confiance (Trust Bar)</h4>
                        <p style="color: var(--text-muted); margin-bottom: 15px;">Statistiques affichées sous le hero (ex: 500+ clubs, 4.9/5 satisfaction)</p>
                        <div id="trustBarItems">
                            <?php
                            $trustBar = json_decode($cp['trust_bar'] ?? '[]', true) ?: [];
                            if (empty($trustBar)) $trustBar = [['value' => '', 'label' => '']];
                            foreach ($trustBar as $i => $item): ?>
                            <div class="form-row trust-item" style="margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <input type="text" name="trust_bar[<?= $i ?>][value]" class="form-control" value="<?= htmlspecialchars($item['value'] ?? '') ?>" placeholder="500+">
                                </div>
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" name="trust_bar[<?= $i ?>][label]" class="form-control" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="Clubs équipés">
                                </div>
                                <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="addTrustItem()">+ Ajouter un élément</button>
                    </div>
                </div>

                <!-- TAB PRODUCTS -->
                <div class="tab-content" id="cat-tab-products">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Titre section produits</label>
                                <input type="text" name="products_title" class="form-control" value="<?= htmlspecialchars($cp['products_title'] ?? '') ?>" placeholder="Nos produits">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sous-titre</label>
                                <input type="text" name="products_subtitle" class="form-control" value="<?= htmlspecialchars($cp['products_subtitle'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="show_filters" value="1" <?= ($cp['show_filters'] ?? 1) ? 'checked' : '' ?>>
                                Afficher les filtres (sport, genre, tri)
                            </label>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Sports à filtrer (un par ligne)</label>
                                <textarea name="filter_sports" class="form-control" rows="4" placeholder="Football&#10;Rugby&#10;Basketball"><?= htmlspecialchars(implode("\n", json_decode($cp['filter_sports'] ?? '[]', true) ?: [])) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Genres à filtrer (un par ligne)</label>
                                <textarea name="filter_genres" class="form-control" rows="4" placeholder="Homme&#10;Femme&#10;Enfant"><?= htmlspecialchars(implode("\n", json_decode($cp['filter_genres'] ?? '[]', true) ?: [])) ?></textarea>
                            </div>
                        </div>

                        <hr style="margin: 30px 0;">
                        <h4>Produits à afficher</h4>
                        <p style="color: var(--text-muted); margin-bottom: 15px;">Sélectionnez les produits qui apparaîtront sur cette page catégorie.</p>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label class="form-label">Produits sélectionnés (<?= count($selectedProducts) ?>)</label>
                                <div id="selectedCatProducts" style="min-height: 200px; max-height: 400px; overflow-y: auto; border: 2px dashed #e2e8f0; border-radius: 8px; padding: 10px;">
                                    <?php foreach ($selectedProducts as $prodId):
                                        $prod = array_filter($allProducts, fn($p) => $p['id'] == $prodId);
                                        $prod = reset($prod);
                                        if ($prod):
                                            $prodName = !empty($prod['meta_title']) ? $prod['meta_title'] : $prod['nom'];
                                    ?>
                                    <div class="cat-prod-item" data-id="<?= $prod['id'] ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #fff5f3; border: 1px solid #FF4B26; border-radius: 6px; margin-bottom: 8px;">
                                        <img src="<?= htmlspecialchars($prod['photo_1'] ?: '/photos/placeholder.webp') ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <div style="flex: 1; overflow: hidden;">
                                            <div style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($prodName) ?></div>
                                            <div style="font-size: 10px; color: #666;"><?= htmlspecialchars($prod['sport']) ?></div>
                                        </div>
                                        <input type="hidden" name="page_products[]" value="<?= $prod['id'] ?>">
                                        <button type="button" class="btn btn-sm" style="color: #ef4444;" onclick="removeCatProduct(this)">✕</button>
                                    </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Tous les produits</label>
                                <input type="text" id="catProductSearch" class="form-control" placeholder="Rechercher..." style="margin-bottom: 10px;" oninput="filterCatProducts()">
                                <div id="availableCatProducts" style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($allProducts as $prod):
                                        $prodName = !empty($prod['meta_title']) ? $prod['meta_title'] : $prod['nom'];
                                        $isSelected = in_array($prod['id'], $selectedProducts);
                                    ?>
                                    <div class="cat-prod-avail <?= $isSelected ? 'selected' : '' ?>" data-id="<?= $prod['id'] ?>" data-name="<?= htmlspecialchars(strtolower($prodName)) ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 6px; cursor: pointer; <?= $isSelected ? 'opacity: 0.5;' : '' ?>" onclick="addCatProduct(this, <?= $prod['id'] ?>, <?= htmlspecialchars(json_encode($prod)) ?>)">
                                        <img src="<?= htmlspecialchars($prod['photo_1'] ?: '/photos/placeholder.webp') ?>" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px;">
                                        <div style="flex: 1; overflow: hidden;">
                                            <div style="font-size: 12px; font-weight: 600;"><?= htmlspecialchars($prodName) ?></div>
                                            <div style="font-size: 10px; color: #666;"><?= htmlspecialchars($prod['sport'] . ' • ' . ($prod['prix_500'] ? number_format($prod['prix_500'], 2) . '€' : '-')) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB CTA -->
                <div class="tab-content" id="cat-tab-cta">
                    <div class="card-body">
                        <h4>Section CTA (Créons ensemble)</h4>
                        <div class="form-group">
                            <label class="form-label">Titre CTA</label>
                            <input type="text" name="cta_title" class="form-control" value="<?= htmlspecialchars($cp['cta_title'] ?? '') ?>" placeholder="Créons ensemble votre équipement">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sous-titre CTA</label>
                            <input type="text" name="cta_subtitle" class="form-control" value="<?= htmlspecialchars($cp['cta_subtitle'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Points forts (un par ligne)</label>
                            <textarea name="cta_features" class="form-control" rows="4" placeholder="Devis gratuit sous 24h&#10;Design professionnel inclus&#10;Prix dégressifs garantis"><?= htmlspecialchars(implode("\n", json_decode($cp['cta_features'] ?? '[]', true) ?: [])) ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Texte bouton principal</label>
                                <input type="text" name="cta_button_text" class="form-control" value="<?= htmlspecialchars($cp['cta_button_text'] ?? '') ?>" placeholder="Demander un devis">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lien bouton</label>
                                <input type="text" name="cta_button_link" class="form-control" value="<?= htmlspecialchars($cp['cta_button_link'] ?? '') ?>" placeholder="/pages/info/devis.html">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Numéro WhatsApp (optionnel)</label>
                            <input type="text" name="cta_whatsapp" class="form-control" value="<?= htmlspecialchars($cp['cta_whatsapp'] ?? '') ?>" placeholder="+33612345678">
                        </div>

                        <hr style="margin: 30px 0;">
                        <h4>Section Excellence (3 colonnes)</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Titre section</label>
                                <input type="text" name="excellence_title" class="form-control" value="<?= htmlspecialchars($cp['excellence_title'] ?? '') ?>" placeholder="Excellence de nos produits">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sous-titre</label>
                                <input type="text" name="excellence_subtitle" class="form-control" value="<?= htmlspecialchars($cp['excellence_subtitle'] ?? '') ?>">
                            </div>
                        </div>
                        <div id="excellenceColumns">
                            <?php
                            $excCols = json_decode($cp['excellence_columns'] ?? '[]', true) ?: [];
                            if (empty($excCols)) $excCols = [['icon' => '', 'title' => '', 'content' => '']];
                            foreach ($excCols as $i => $col): ?>
                            <div class="exc-col" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <div class="form-row">
                                    <div class="form-group" style="width: 80px;">
                                        <label class="form-label">Icône</label>
                                        <input type="text" name="excellence_columns[<?= $i ?>][icon]" class="form-control" value="<?= htmlspecialchars($col['icon'] ?? '') ?>" placeholder="🎨">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label class="form-label">Titre</label>
                                        <input type="text" name="excellence_columns[<?= $i ?>][title]" class="form-control" value="<?= htmlspecialchars($col['title'] ?? '') ?>">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.parentElement.remove()" style="align-self: flex-end;">✕</button>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contenu</label>
                                    <textarea name="excellence_columns[<?= $i ?>][content]" class="form-control" rows="3"><?= htmlspecialchars($col['content'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="addExcellenceCol()">+ Ajouter une colonne</button>
                    </div>
                </div>

                <!-- TAB CONTENT -->
                <div class="tab-content" id="cat-tab-content">
                    <div class="card-body">
                        <h4>Section Technologie</h4>
                        <div class="form-group">
                            <label class="form-label">Titre</label>
                            <input type="text" name="tech_title" class="form-control" value="<?= htmlspecialchars($cp['tech_title'] ?? '') ?>" placeholder="Sublimation Intégrale">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contenu (HTML autorisé)</label>
                            <textarea name="tech_content" class="form-control" rows="5"><?= htmlspecialchars($cp['tech_content'] ?? '') ?></textarea>
                        </div>
                        <div id="techStats">
                            <label class="form-label">Statistiques</label>
                            <?php
                            $techStats = json_decode($cp['tech_stats'] ?? '[]', true) ?: [];
                            if (empty($techStats)) $techStats = [['value' => '', 'label' => '']];
                            foreach ($techStats as $i => $stat): ?>
                            <div class="form-row" style="margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <input type="text" name="tech_stats[<?= $i ?>][value]" class="form-control" value="<?= htmlspecialchars($stat['value'] ?? '') ?>" placeholder="500+">
                                </div>
                                <div class="form-group" style="flex: 2;">
                                    <input type="text" name="tech_stats[<?= $i ?>][label]" class="form-control" value="<?= htmlspecialchars($stat['label'] ?? '') ?>" placeholder="Clubs équipés">
                                </div>
                                <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="addTechStat()">+ Ajouter une stat</button>

                        <hr style="margin: 30px 0;">
                        <h4>Guide SEO (contenu long)</h4>
                        <div class="form-group">
                            <label class="form-label">Titre du guide</label>
                            <input type="text" name="guide_title" class="form-control" value="<?= htmlspecialchars($cp['guide_title'] ?? '') ?>" placeholder="Tout savoir sur nos produits">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contenu du guide (HTML autorisé)</label>
                            <textarea name="guide_content" class="form-control" rows="10"><?= htmlspecialchars($cp['guide_content'] ?? '') ?></textarea>
                            <div class="form-hint">Contenu SEO détaillé. Utilisez H2, H3, listes, etc.</div>
                        </div>
                    </div>
                </div>

                <!-- TAB TESTIMONIALS -->
                <div class="tab-content" id="cat-tab-testimonials">
                    <div class="card-body">
                        <h4>Témoignages clients</h4>
                        <div id="testimonialsList">
                            <?php
                            $testimonials = json_decode($cp['testimonials'] ?? '[]', true) ?: [];
                            if (empty($testimonials)) $testimonials = [['text' => '', 'author' => '', 'role' => '']];
                            foreach ($testimonials as $i => $t): ?>
                            <div class="testimonial-item" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Texte du témoignage</label>
                                    <textarea name="testimonials[<?= $i ?>][text]" class="form-control" rows="2"><?= htmlspecialchars($t['text'] ?? '') ?></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Auteur</label>
                                        <input type="text" name="testimonials[<?= $i ?>][author]" class="form-control" value="<?= htmlspecialchars($t['author'] ?? '') ?>" placeholder="Club XYZ">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Rôle / Info</label>
                                        <input type="text" name="testimonials[<?= $i ?>][role]" class="form-control" value="<?= htmlspecialchars($t['role'] ?? '') ?>" placeholder="Président du club">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.parentElement.remove()" style="align-self: flex-end;">✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="addTestimonial()">+ Ajouter un témoignage</button>
                    </div>
                </div>

                <!-- TAB FAQ -->
                <div class="tab-content" id="cat-tab-faq">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Titre section FAQ</label>
                            <input type="text" name="faq_title" class="form-control" value="<?= htmlspecialchars($cp['faq_title'] ?? '') ?>" placeholder="Questions fréquentes">
                        </div>
                        <div id="faqList">
                            <?php
                            $faqItems = json_decode($cp['faq_items'] ?? '[]', true) ?: [];
                            if (empty($faqItems)) $faqItems = [['question' => '', 'answer' => '']];
                            foreach ($faqItems as $i => $faq): ?>
                            <div class="faq-item-edit" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Question</label>
                                    <input type="text" name="faq_items[<?= $i ?>][question]" class="form-control" value="<?= htmlspecialchars($faq['question'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Réponse (HTML autorisé)</label>
                                    <textarea name="faq_items[<?= $i ?>][answer]" class="form-control" rows="3"><?= htmlspecialchars($faq['answer'] ?? '') ?></textarea>
                                </div>
                                <button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕ Supprimer</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" onclick="addFaqItem()">+ Ajouter une question</button>
                    </div>
                </div>

                <!-- TAB SEO -->
                <div class="tab-content" id="cat-tab-seo">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($cp['meta_title'] ?? '') ?>">
                            <div class="form-hint">Recommandé: 50-60 caractères</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars($cp['meta_description'] ?? '') ?></textarea>
                            <div class="form-hint">Recommandé: 150-160 caractères</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="display: flex; justify-content: space-between;">
                    <div>
                        <?php if ($id): ?>
                        <a href="?page=category_pages&action=delete_category_page&id=<?= $id ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-danger" onclick="return confirm('Supprimer cette page ?')">Supprimer</a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </div>
        </form>

        <script>
        function switchCatTab(tabId) {
            document.querySelectorAll('.tabs-nav .tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('cat-tab-' + tabId).classList.add('active');
        }

        var trustIdx = <?= count($trustBar) ?>;
        function addTrustItem() {
            var html = '<div class="form-row trust-item" style="margin-bottom: 10px;"><div class="form-group" style="flex: 1;"><input type="text" name="trust_bar[' + trustIdx + '][value]" class="form-control" placeholder="500+"></div><div class="form-group" style="flex: 2;"><input type="text" name="trust_bar[' + trustIdx + '][label]" class="form-control" placeholder="Clubs équipés"></div><button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕</button></div>';
            document.getElementById('trustBarItems').insertAdjacentHTML('beforeend', html);
            trustIdx++;
        }

        var excIdx = <?= count($excCols) ?>;
        function addExcellenceCol() {
            var html = '<div class="exc-col" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;"><div class="form-row"><div class="form-group" style="width: 80px;"><label class="form-label">Icône</label><input type="text" name="excellence_columns[' + excIdx + '][icon]" class="form-control" placeholder="🎨"></div><div class="form-group" style="flex: 1;"><label class="form-label">Titre</label><input type="text" name="excellence_columns[' + excIdx + '][title]" class="form-control"></div><button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.parentElement.remove()" style="align-self: flex-end;">✕</button></div><div class="form-group"><label class="form-label">Contenu</label><textarea name="excellence_columns[' + excIdx + '][content]" class="form-control" rows="3"></textarea></div></div>';
            document.getElementById('excellenceColumns').insertAdjacentHTML('beforeend', html);
            excIdx++;
        }

        var techIdx = <?= count($techStats) ?>;
        function addTechStat() {
            var html = '<div class="form-row" style="margin-bottom: 10px;"><div class="form-group" style="flex: 1;"><input type="text" name="tech_stats[' + techIdx + '][value]" class="form-control" placeholder="500+"></div><div class="form-group" style="flex: 2;"><input type="text" name="tech_stats[' + techIdx + '][label]" class="form-control" placeholder="Label"></div><button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕</button></div>';
            document.getElementById('techStats').insertAdjacentHTML('beforeend', html);
            techIdx++;
        }

        var testIdx = <?= count($testimonials) ?>;
        function addTestimonial() {
            var html = '<div class="testimonial-item" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;"><div class="form-group"><label class="form-label">Texte du témoignage</label><textarea name="testimonials[' + testIdx + '][text]" class="form-control" rows="2"></textarea></div><div class="form-row"><div class="form-group"><label class="form-label">Auteur</label><input type="text" name="testimonials[' + testIdx + '][author]" class="form-control" placeholder="Club XYZ"></div><div class="form-group"><label class="form-label">Rôle / Info</label><input type="text" name="testimonials[' + testIdx + '][role]" class="form-control" placeholder="Président"></div><button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.parentElement.remove()" style="align-self: flex-end;">✕</button></div></div>';
            document.getElementById('testimonialsList').insertAdjacentHTML('beforeend', html);
            testIdx++;
        }

        var faqIdx = <?= count($faqItems) ?>;
        function addFaqItem() {
            var html = '<div class="faq-item-edit" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;"><div class="form-group"><label class="form-label">Question</label><input type="text" name="faq_items[' + faqIdx + '][question]" class="form-control"></div><div class="form-group"><label class="form-label">Réponse (HTML autorisé)</label><textarea name="faq_items[' + faqIdx + '][answer]" class="form-control" rows="3"></textarea></div><button type="button" class="btn btn-sm btn-light" onclick="this.parentElement.remove()">✕ Supprimer</button></div>';
            document.getElementById('faqList').insertAdjacentHTML('beforeend', html);
            faqIdx++;
        }

        // Product selection
        function addCatProduct(el, id, prod) {
            if (el.classList.contains('selected')) return;
            el.classList.add('selected');
            el.style.opacity = '0.5';

            var prodName = prod.meta_title || prod.nom;
            var html = '<div class="cat-prod-item" data-id="' + id + '" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #fff5f3; border: 1px solid #FF4B26; border-radius: 6px; margin-bottom: 8px;"><img src="' + (prod.photo_1 || '/photos/placeholder.webp') + '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"><div style="flex: 1; overflow: hidden;"><div style="font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + escapeHtml(prodName) + '</div><div style="font-size: 10px; color: #666;">' + escapeHtml(prod.sport) + '</div></div><input type="hidden" name="page_products[]" value="' + id + '"><button type="button" class="btn btn-sm" style="color: #ef4444;" onclick="removeCatProduct(this)">✕</button></div>';
            document.getElementById('selectedCatProducts').insertAdjacentHTML('beforeend', html);
        }

        function removeCatProduct(btn) {
            var item = btn.closest('.cat-prod-item');
            var id = item.dataset.id;
            item.remove();
            var avail = document.querySelector('.cat-prod-avail[data-id="' + id + '"]');
            if (avail) {
                avail.classList.remove('selected');
                avail.style.opacity = '1';
            }
        }

        function filterCatProducts() {
            var search = document.getElementById('catProductSearch').value.toLowerCase();
            document.querySelectorAll('.cat-prod-avail').forEach(function(el) {
                var name = el.dataset.name || '';
                el.style.display = name.includes(search) ? 'flex' : 'none';
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        </script>

        <?php // ============ QUOTES ============ ?>
        <?php elseif ($page === 'quotes'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Devis</span>
                <div class="filters" style="margin: 0;">
                    <a href="?page=quotes" class="btn btn-sm <?= empty($_GET['status']) ? 'btn-primary' : 'btn-light' ?>">Tous</a>
                    <a href="?page=quotes&status=pending" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'pending' ? 'btn-primary' : 'btn-light' ?>">En attente</a>
                    <a href="?page=quotes&status=sent" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'sent' ? 'btn-primary' : 'btn-light' ?>">Envoyés</a>
                    <a href="?page=quotes&status=accepted" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'accepted' ? 'btn-primary' : 'btn-light' ?>">Acceptés</a>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Réf</th><th>Client</th><th>Produit</th><th>Qté</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $q): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($q['reference']) ?></strong></td>
                            <td><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($q['product_nom'] ?? '', 0, 30)) ?></td>
                            <td><?= $q['total_pieces'] ?></td>
                            <td><strong><?= number_format($q['prix_total'] ?? 0, 2) ?>€</strong></td>
                            <td>
                                <?php $colors = ['pending' => 'warning', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger']; ?>
                                <span class="badge badge-<?= $colors[$q['status']] ?? 'info' ?>"><?= $q['status'] ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                            <td><a href="?page=quote&id=<?= $q['id'] ?>" class="btn btn-sm btn-light">Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ QUOTE VIEW ============ ?>
        <?php elseif ($page === 'quote' && $id): ?>
        <?php $q = $data['item'] ?? []; ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
            <div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Devis <?= htmlspecialchars($q['reference']) ?></span>
                        <span class="badge badge-<?= ['pending' => 'warning', 'sent' => 'info', 'accepted' => 'success'][$q['status']] ?? 'info' ?>"><?= $q['status'] ?></span>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Client</h4>
                                <p><strong><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></strong></p>
                                <p><?= htmlspecialchars($q['client_email']) ?></p>
                                <p><?= htmlspecialchars($q['client_telephone']) ?></p>
                                <p><?= htmlspecialchars($q['client_club']) ?></p>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Produit</h4>
                                <p><strong><?= htmlspecialchars($q['product_nom']) ?></strong></p>
                                <p>Réf: <?= htmlspecialchars($q['product_reference']) ?></p>
                                <p>Sport: <?= htmlspecialchars($q['sport']) ?></p>
                            </div>
                        </div>
                        <hr style="margin: 25px 0; border: none; border-top: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="color: var(--text-muted);">Quantité:</span>
                                <strong style="font-size: 18px; margin-left: 10px;"><?= $q['total_pieces'] ?> pièces</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);">Prix unitaire:</span>
                                <strong style="margin-left: 10px;"><?= number_format($q['prix_unitaire'] ?? 0, 2) ?>€</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);">Total TTC:</span>
                                <strong style="font-size: 24px; color: var(--primary); margin-left: 10px;"><?= number_format($q['prix_total'] ?? 0, 2) ?>€</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Mettre à jour</span>
                </div>
                <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
                    <input type="hidden" name="action" value="update_quote">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="pending" <?= $q['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                <option value="sent" <?= $q['status'] === 'sent' ? 'selected' : '' ?>>Envoyé</option>
                                <option value="accepted" <?= $q['status'] === 'accepted' ? 'selected' : '' ?>>Accepté</option>
                                <option value="rejected" <?= $q['status'] === 'rejected' ? 'selected' : '' ?>>Refusé</option>
                                <option value="completed" <?= $q['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes internes</label>
                            <textarea name="notes" class="form-control"><?= htmlspecialchars($q['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
        <a href="?page=quotes" class="btn btn-light" style="margin-top: 20px;">← Retour aux devis</a>

        <?php // ============ PHOTOS MANAGER ============ ?>
        <?php elseif ($page === 'photos'): ?>
        <?php $currentFolder = $data['current_folder'] ?? ''; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">📷 Gestionnaire de photos</span>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-light" onclick="createFolder()">
                        📁 Nouveau dossier
                    </button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('uploadPhotoInput').click()">
                        + Uploader des photos
                    </button>
                    <input type="file" id="uploadPhotoInput" multiple accept="image/*" style="display: none;" onchange="uploadPhotos(this.files)">
                </div>
            </div>
            <div class="card-body">
                <!-- Sélecteur de dossier -->
                <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
                    <a href="?page=photos" class="btn <?= empty($currentFolder) ? 'btn-primary' : 'btn-light' ?>">
                        📁 Racine /photos/
                    </a>
                    <?php foreach ($data['folders'] ?? [] as $folder): ?>
                    <a href="?page=photos&folder=<?= urlencode($folder['path']) ?>" class="btn <?= $currentFolder === $folder['path'] ? 'btn-primary' : 'btn-light' ?>">
                        📁 <?= htmlspecialchars($folder['name']) ?>
                        <span style="background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 5px;"><?= $folder['count'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($currentFolder)): ?>
                <div style="margin-bottom: 15px; padding: 10px 15px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                    <span>📂</span>
                    <span><strong>/photos/<?= htmlspecialchars($currentFolder) ?>/</strong></span>
                    <a href="?page=photos" style="margin-left: auto; color: var(--text-muted); font-size: 12px;">← Revenir à la racine</a>
                </div>
                <?php endif; ?>

                <!-- Zone de drop -->
                <div id="photosDropzone" style="border: 2px dashed var(--border); border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 20px; background: #fafbfc; transition: all 0.3s;">
                    <div style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;">📁</div>
                    <div style="color: var(--text-muted);">Glissez-déposez des photos ici pour les uploader</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        Destination: <strong>/photos/<?= htmlspecialchars($currentFolder ?: 'produits') ?>/</strong>
                    </div>
                </div>

                <!-- Grille des photos -->
                <div id="photosGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
                    <?php foreach ($data['photos'] ?? [] as $photo): ?>
                    <div class="photo-manager-item" style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; background: #fff; position: relative;">
                        <div style="height: 140px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer;" onclick="showPhotoInfo('<?= htmlspecialchars($photo['url']) ?>')">
                            <img src="<?= htmlspecialchars($photo['url']) ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div style="padding: 10px;">
                            <div style="font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px;" title="<?= htmlspecialchars($photo['name']) ?>">
                                <?= htmlspecialchars($photo['name']) ?>
                            </div>
                            <div style="font-size: 10px; color: var(--text-muted); display: flex; justify-content: space-between;">
                                <span><?= number_format($photo['size'] / 1024, 1) ?> Ko</span>
                                <span><?= date('d/m/Y', $photo['modified']) ?></span>
                            </div>
                            <div style="margin-top: 8px; display: flex; gap: 3px; flex-wrap: wrap;">
                                <button type="button" class="btn btn-sm btn-light" style="flex: 1; font-size: 9px; padding: 4px 6px;" onclick="copyPhotoUrl('<?= htmlspecialchars($photo['url']) ?>')" title="Copier URL">📋</button>
                                <button type="button" class="btn btn-sm btn-light" style="flex: 1; font-size: 9px; padding: 4px 6px;" onclick="renamePhoto('<?= htmlspecialchars($photo['url']) ?>', '<?= htmlspecialchars($photo['name']) ?>')" title="Renommer">✏️</button>
                                <button type="button" class="btn btn-sm btn-light" style="flex: 1; font-size: 9px; padding: 4px 6px;" onclick="movePhoto('<?= htmlspecialchars($photo['url']) ?>')" title="Déplacer">📦</button>
                                <button type="button" class="btn btn-sm btn-danger" style="font-size: 9px; padding: 4px 6px;" onclick="deletePhoto('<?= htmlspecialchars($photo['url']) ?>')" title="Supprimer">🗑</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($data['photos'])): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                        <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;">📷</div>
                        <p>Aucune photo dans ce dossier</p>
                        <p style="font-size: 12px;">Uploadez des photos en cliquant sur le bouton ci-dessus ou en glissant-déposant</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        (function() {
            const dropzone = document.getElementById('photosDropzone');
            const currentFolder = '<?= htmlspecialchars($currentFolder ?: 'produits') ?>';

            // Drag & drop events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
                dropzone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); });
            });
            ['dragenter', 'dragover'].forEach(e => {
                dropzone.addEventListener(e, () => { dropzone.style.borderColor = 'var(--primary)'; dropzone.style.background = '#fff5f3'; });
            });
            ['dragleave', 'drop'].forEach(e => {
                dropzone.addEventListener(e, () => { dropzone.style.borderColor = 'var(--border)'; dropzone.style.background = '#fafbfc'; });
            });
            dropzone.addEventListener('drop', e => uploadPhotos(e.dataTransfer.files));

            window.uploadPhotos = async function(files) {
                for (const file of files) {
                    if (!file.type.startsWith('image/')) continue;
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('folder', currentFolder);

                    try {
                        const res = await fetch('/api/photos-manager.php?action=upload', { method: 'POST', body: formData });
                        const result = await res.json();
                        if (result.success) {
                            location.reload();
                        } else {
                            alert('Erreur: ' + result.error);
                        }
                    } catch(e) {
                        alert('Erreur réseau');
                    }
                }
            };

            window.copyPhotoUrl = function(url) {
                const fullUrl = window.location.origin + url;
                navigator.clipboard.writeText(fullUrl).then(() => {
                    alert('URL copiée: ' + fullUrl);
                });
            };

            window.deletePhoto = async function(url) {
                if (!confirm('Supprimer cette photo ?')) return;
                const res = await fetch('/api/photos-manager.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            };

            // Créer un nouveau dossier
            window.createFolder = async function() {
                const name = prompt('Nom du nouveau dossier (lettres, chiffres, tirets uniquement):');
                if (!name) return;
                const res = await fetch('/api/photos-manager.php?action=create_folder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            };

            // Renommer une photo
            window.renamePhoto = async function(url, currentName) {
                const baseName = currentName.replace(/\.[^/.]+$/, '');
                const newName = prompt('Nouveau nom (sans extension):', baseName);
                if (!newName || newName === baseName) return;
                const res = await fetch('/api/photos-manager.php?action=rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url, new_name: newName })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            };

            // Déplacer une photo
            window.movePhoto = async function(url) {
                // Charger la liste des dossiers
                const foldersRes = await fetch('/api/photos-manager.php?action=folders');
                const foldersData = await foldersRes.json();
                if (!foldersData.success) {
                    alert('Erreur lors du chargement des dossiers');
                    return;
                }
                const folders = foldersData.folders;
                let options = 'Dossiers disponibles:\n0. Racine (/photos/)\n';
                folders.forEach((f, i) => {
                    options += (i + 1) + '. ' + f.name + ' (' + f.count + ' photos)\n';
                });
                const choice = prompt(options + '\nEntrez le numéro du dossier de destination:');
                if (choice === null) return;
                const idx = parseInt(choice);
                let targetFolder = '';
                if (idx === 0) {
                    targetFolder = '';
                } else if (idx > 0 && idx <= folders.length) {
                    targetFolder = folders[idx - 1].path;
                } else {
                    alert('Choix invalide');
                    return;
                }
                const res = await fetch('/api/photos-manager.php?action=move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url, target_folder: targetFolder })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            };

            // Afficher les infos d'une photo
            window.showPhotoInfo = async function(url) {
                const res = await fetch('/api/photos-manager.php?action=info&url=' + encodeURIComponent(url));
                const result = await res.json();
                if (!result.success) {
                    alert('Erreur: ' + result.error);
                    return;
                }
                const info = result.info;
                const sizeKo = (info.size / 1024).toFixed(1);
                const sizeMo = (info.size / (1024 * 1024)).toFixed(2);
                const date = new Date(info.modified * 1000).toLocaleString('fr-FR');
                let msg = '📷 ' + info.name + '\n\n';
                msg += '📏 Dimensions: ' + (info.width || '?') + ' x ' + (info.height || '?') + ' px\n';
                msg += '📦 Taille: ' + sizeKo + ' Ko (' + sizeMo + ' Mo)\n';
                msg += '📁 Type: ' + info.type + '\n';
                msg += '📅 Modifié: ' + date + '\n';
                msg += '\n🔗 URL: ' + info.url;
                alert(msg);
            };
        })();
        </script>

        <?php // ============ SETTINGS ============ ?>
        <?php elseif ($page === 'settings'): ?>
        <?php $s = $data['settings'] ?? []; ?>
        <form method="POST" action="?page=settings" id="settings-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="card">
                <div class="tabs-nav">
                    <button type="button" class="tab-btn active" onclick="switchTab('general')">Général</button>
                    <button type="button" class="tab-btn" onclick="switchTab('social')">Réseaux sociaux</button>
                    <button type="button" class="tab-btn" onclick="switchTab('email')">Email / SMTP</button>
                    <button type="button" class="tab-btn" onclick="switchTab('payment')">Paiement</button>
                    <button type="button" class="tab-btn" onclick="switchTab('shipping')">Livraison</button>
                    <button type="button" class="tab-btn" onclick="switchTab('quotes')">Devis</button>
                    <button type="button" class="tab-btn" onclick="switchTab('configurator')">Configurateur</button>
                    <button type="button" class="tab-btn" onclick="switchTab('tracking')">Tracking</button>
                    <button type="button" class="tab-btn" onclick="switchTab('security')">Sécurité</button>
                </div>

                <!-- TAB: GENERAL -->
                <div class="tab-content active" id="tab-general">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Informations du site</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nom du site</label>
                                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($s['site_name'] ?? 'FLARE CUSTOM') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Slogan</label>
                                <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($s['site_tagline'] ?? '') ?>" placeholder="Votre slogan ici">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email de contact</label>
                                <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($s['site_email'] ?? '') ?>" placeholder="contact@flare-custom.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="site_phone" class="form-control" value="<?= htmlspecialchars($s['site_phone'] ?? '') ?>" placeholder="+33 1 23 45 67 89">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Adresse</label>
                            <textarea name="site_address" class="form-control" style="min-height: 80px;"><?= htmlspecialchars($s['site_address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Logo (URL)</label>
                                <input type="text" name="site_logo" class="form-control" value="<?= htmlspecialchars($s['site_logo'] ?? '') ?>" placeholder="/assets/images/logo.png">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Favicon (URL)</label>
                                <input type="text" name="site_favicon" class="form-control" value="<?= htmlspecialchars($s['site_favicon'] ?? '') ?>" placeholder="/favicon.ico">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SOCIAL -->
                <div class="tab-content" id="tab-social">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Réseaux sociaux</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Facebook</label>
                                <input type="url" name="social_facebook" class="form-control" value="<?= htmlspecialchars($s['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/flarecustom">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Instagram</label>
                                <input type="url" name="social_instagram" class="form-control" value="<?= htmlspecialchars($s['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/flarecustom">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Twitter / X</label>
                                <input type="url" name="social_twitter" class="form-control" value="<?= htmlspecialchars($s['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/flarecustom">
                            </div>
                            <div class="form-group">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" name="social_linkedin" class="form-control" value="<?= htmlspecialchars($s['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/company/flarecustom">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">YouTube</label>
                            <input type="url" name="social_youtube" class="form-control" value="<?= htmlspecialchars($s['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@flarecustom">
                        </div>
                    </div>
                </div>

                <!-- TAB: EMAIL -->
                <div class="tab-content" id="tab-email">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Configuration SMTP</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Serveur SMTP</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Port SMTP</label>
                                <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>" placeholder="587">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Utilisateur SMTP</label>
                                <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>" placeholder="user@gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mot de passe SMTP</label>
                                <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email expéditeur</label>
                                <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($s['smtp_from_email'] ?? '') ?>" placeholder="noreply@flare-custom.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom expéditeur</label>
                                <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($s['smtp_from_name'] ?? '') ?>" placeholder="FLARE CUSTOM">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email de notification (nouveaux devis)</label>
                            <input type="email" name="notification_email" class="form-control" value="<?= htmlspecialchars($s['notification_email'] ?? '') ?>" placeholder="admin@flare-custom.com">
                        </div>
                    </div>
                </div>

                <!-- TAB: PAYMENT -->
                <div class="tab-content" id="tab-payment">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Mode de paiement</h4>
                        <div class="form-group">
                            <label class="form-label">Mode de fonctionnement</label>
                            <select name="payment_mode" class="form-control" style="max-width: 300px;">
                                <option value="quote" <?= ($s['payment_mode'] ?? '') === 'quote' ? 'selected' : '' ?>>Devis uniquement (pas de paiement en ligne)</option>
                                <option value="stripe" <?= ($s['payment_mode'] ?? '') === 'stripe' ? 'selected' : '' ?>>Paiement Stripe</option>
                                <option value="paypal" <?= ($s['payment_mode'] ?? '') === 'paypal' ? 'selected' : '' ?>>Paiement PayPal</option>
                                <option value="both" <?= ($s['payment_mode'] ?? '') === 'both' ? 'selected' : '' ?>>Stripe + PayPal</option>
                            </select>
                        </div>

                        <h4 style="margin: 30px 0 20px;">Stripe</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Clé publique Stripe</label>
                                <input type="text" name="stripe_public_key" class="form-control" value="<?= htmlspecialchars($s['stripe_public_key'] ?? '') ?>" placeholder="pk_live_...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Clé secrète Stripe</label>
                                <input type="password" name="stripe_secret_key" class="form-control" value="<?= htmlspecialchars($s['stripe_secret_key'] ?? '') ?>" placeholder="sk_live_...">
                            </div>
                        </div>

                        <h4 style="margin: 30px 0 20px;">PayPal</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Client ID PayPal</label>
                                <input type="text" name="paypal_client_id" class="form-control" value="<?= htmlspecialchars($s['paypal_client_id'] ?? '') ?>" placeholder="Client ID...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Secret PayPal</label>
                                <input type="password" name="paypal_secret" class="form-control" value="<?= htmlspecialchars($s['paypal_secret'] ?? '') ?>" placeholder="Secret...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SHIPPING -->
                <div class="tab-content" id="tab-shipping">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Frais de livraison</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">France métropolitaine (€)</label>
                                <input type="number" step="0.01" name="shipping_france" class="form-control" value="<?= htmlspecialchars($s['shipping_france'] ?? '0') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Europe (€)</label>
                                <input type="number" step="0.01" name="shipping_europe" class="form-control" value="<?= htmlspecialchars($s['shipping_europe'] ?? '0') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">International (€)</label>
                                <input type="number" step="0.01" name="shipping_world" class="form-control" value="<?= htmlspecialchars($s['shipping_world'] ?? '0') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Livraison gratuite au-dessus de (€)</label>
                                <input type="number" step="0.01" name="shipping_free_above" class="form-control" value="<?= htmlspecialchars($s['shipping_free_above'] ?? '0') ?>">
                                <div class="form-hint">Mettre 0 pour désactiver</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Délai de livraison par défaut</label>
                                <input type="text" name="default_delivery_time" class="form-control" value="<?= htmlspecialchars($s['default_delivery_time'] ?? '3-4 semaines') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: QUOTES -->
                <div class="tab-content" id="tab-quotes">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Paramètres des devis</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Préfixe des devis</label>
                                <input type="text" name="quote_prefix" class="form-control" value="<?= htmlspecialchars($s['quote_prefix'] ?? 'DEV-') ?>" placeholder="DEV-">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Validité des devis (jours)</label>
                                <input type="number" name="quote_validity_days" class="form-control" value="<?= htmlspecialchars($s['quote_validity_days'] ?? '30') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Quantité minimum de commande</label>
                                <input type="number" name="min_order_quantity" class="form-control" value="<?= htmlspecialchars($s['min_order_quantity'] ?? '1') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Taux de TVA (%)</label>
                                <input type="number" step="0.01" name="tva_rate" class="form-control" value="<?= htmlspecialchars($s['tva_rate'] ?? '20') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: CONFIGURATOR -->
                <div class="tab-content" id="tab-configurator">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Options du configurateur (par défaut)</h4>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Options de design</h5>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_flare" value="1" <?= ($s['configurator_design_flare'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Design FLARE
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_client" value="1" <?= ($s['configurator_design_client'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Design client
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_template" value="1" <?= ($s['configurator_design_template'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Template catalogue
                            </label>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Personnalisation</h5>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_nom" value="1" <?= ($s['configurator_perso_nom'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Nom
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_numero" value="1" <?= ($s['configurator_perso_numero'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Numéro
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_logo" value="1" <?= ($s['configurator_perso_logo'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Logo
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_sponsor" value="1" <?= ($s['configurator_perso_sponsor'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Sponsor
                            </label>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Tailles disponibles</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tailles adultes (séparées par virgule)</label>
                                <input type="text" name="configurator_sizes" class="form-control" value="<?= htmlspecialchars($s['configurator_sizes'] ?? 'XS,S,M,L,XL,XXL,3XL') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tailles enfants (séparées par virgule)</label>
                                <input type="text" name="configurator_sizes_kids" class="form-control" value="<?= htmlspecialchars($s['configurator_sizes_kids'] ?? '6ans,8ans,10ans,12ans,14ans') ?>">
                            </div>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Options de col</h5>
                        <div class="form-group">
                            <label class="form-label">Types de col (séparés par virgule)</label>
                            <input type="text" name="configurator_collars" class="form-control" value="<?= htmlspecialchars($s['configurator_collars'] ?? 'col_v,col_rond,col_polo') ?>">
                        </div>
                    </div>
                </div>

                <!-- TAB: TRACKING -->
                <div class="tab-content" id="tab-tracking">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Codes de suivi</h4>
                        <div class="form-group">
                            <label class="form-label">Google Analytics (ID)</label>
                            <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars($s['google_analytics'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Google Tag Manager (ID)</label>
                            <input type="text" name="google_tag_manager" class="form-control" value="<?= htmlspecialchars($s['google_tag_manager'] ?? '') ?>" placeholder="GTM-XXXXXXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Pixel (ID)</label>
                            <input type="text" name="meta_pixel" class="form-control" value="<?= htmlspecialchars($s['meta_pixel'] ?? '') ?>" placeholder="1234567890123456">
                        </div>
                    </div>
                </div>

                <!-- TAB: SECURITY -->
                <div class="tab-content" id="tab-security">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Maintenance</h4>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= ($s['maintenance_mode'] ?? '') === '1' ? 'checked' : '' ?>>
                                <strong>Mode maintenance activé</strong>
                            </label>
                            <div class="form-hint">Le site sera inaccessible aux visiteurs (sauf admin)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message de maintenance</label>
                            <textarea name="maintenance_message" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($s['maintenance_message'] ?? 'Site en maintenance. Nous revenons bientôt !') ?></textarea>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">Changer le mot de passe admin</h4>
                        </form>
                        <form method="POST" action="?page=settings">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" form="settings-form" class="btn btn-primary">Enregistrer les paramètres</button>
                </div>
            </div>
        </form>

        <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
        </script>

        <?php // ============ IMPORT CSV ============ ?>
        <?php elseif ($page === 'import'): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Import CSV Produits</span>
                </div>
                <form method="POST" action="?page=import" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="import_csv">
                    <div class="card-body">
                        <div class="alert alert-success" style="background: rgba(80,205,137,0.1); border: 1px solid rgba(80,205,137,0.2); color: var(--success);">
                            <strong>Base de données:</strong> <?= number_format($data['total_products'] ?? 0) ?> produits
                            <?php if ($data['last_import'] ?? null): ?>
                            <br><small>Dernier import: <?= date('d/m/Y H:i', strtotime($data['last_import'])) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fichier CSV</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-hint">Format CSV avec séparateur point-virgule (;)</div>
                        </div>

                        <div style="background: #fafbfc; border-radius: 8px; padding: 15px; margin-top: 20px;">
                            <strong style="font-size: 12px; color: var(--text-muted);">COLONNES SUPPORTÉES:</strong>
                            <p style="font-size: 12px; margin-top: 10px; color: var(--text-dark);">
                                REFERENCE_FLARE, TITRE_VENDEUR, SPORT, FAMILLE_PRODUIT, DESCRIPTION, DESCRIPTION_SEO,
                                TISSU, GRAMMAGE, GENRE, FINITION, QTY_1, QTY_5, QTY_10, QTY_20, QTY_50, QTY_100, QTY_250, QTY_500,
                                PHOTO_1 à PHOTO_5, URL
                            </p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Importer le CSV</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informations</span>
                </div>
                <div class="card-body">
                    <h5 style="margin-bottom: 15px;">Comment ça marche ?</h5>
                    <ul style="color: var(--text-muted); line-height: 2;">
                        <li>Le CSV met à jour les produits existants (par référence)</li>
                        <li>Les nouveaux produits sont créés automatiquement</li>
                        <li>Les produits non présents dans le CSV ne sont pas supprimés</li>
                        <li>Toutes les modifications sont enregistrées en base de données</li>
                    </ul>

                    <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">

                    <h5 style="margin-bottom: 15px;">Format attendu</h5>
                    <p style="color: var(--text-muted); font-size: 13px;">
                        Le fichier doit être au format CSV avec un séparateur point-virgule (;).
                        La première ligne doit contenir les noms des colonnes.
                    </p>

                    <div style="background: var(--sidebar-bg); color: #fff; padding: 15px; border-radius: 8px; margin-top: 15px; font-family: monospace; font-size: 11px; overflow-x: auto;">
                        REFERENCE_FLARE;TITRE_VENDEUR;SPORT;QTY_1;QTY_5;...<br>
                        FLARE-MFOOT-001;Maillot Football Pro;Football;45.00;42.00;...
                    </div>
                </div>
            </div>
        </div>

        <?php // ============ TEMPLATES LIST ============ ?>
        <?php elseif ($page === 'templates'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Templates de design (<?= count($data['items'] ?? []) ?>)</span>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-light" onclick="syncTemplates()" id="sync-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Sync /templates/
                    </button>
                    <a href="?page=template" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nouveau template
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtres -->
                <form method="get" class="form-row" style="margin-bottom: 20px; gap: 10px;">
                    <input type="hidden" name="page" value="templates">
                    <div style="flex: 2;">
                        <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div>
                        <select name="sport" class="form-control">
                            <option value="">Tous les sports</option>
                            <?php foreach ($data['sports'] ?? [] as $sport): ?>
                            <option value="<?= htmlspecialchars($sport) ?>" <?= ($_GET['sport'] ?? '') === $sport ? 'selected' : '' ?>><?= htmlspecialchars($sport) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="famille" class="form-control">
                            <option value="">Toutes les familles</option>
                            <?php foreach ($data['familles'] ?? [] as $famille): ?>
                            <option value="<?= htmlspecialchars($famille) ?>" <?= ($_GET['famille'] ?? '') === $famille ? 'selected' : '' ?>><?= htmlspecialchars($famille) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="active" class="form-control">
                            <option value="">Tous</option>
                            <option value="1" <?= ($_GET['active'] ?? '') === '1' ? 'selected' : '' ?>>Actifs</option>
                            <option value="0" <?= ($_GET['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactifs</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-light">Filtrer</button>
                        <?php if (!empty($_GET['search']) || !empty($_GET['sport']) || !empty($_GET['famille']) || isset($_GET['active'])): ?>
                        <a href="?page=templates" class="btn btn-light" style="margin-left: 5px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($data['items'])): ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 15px; opacity: 0.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    <p>Aucun template trouvé</p>
                    <p style="font-size: 13px; margin-top: 10px;">
                        <a href="?page=template" class="btn btn-primary">Créer un template</a>
                    </p>
                </div>
                <?php else: ?>
                <!-- Grille de templates -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;" id="templates-grid">
                    <?php foreach ($data['items'] as $tpl): ?>
                    <div class="template-card" data-id="<?= $tpl['id'] ?>" style="background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: all 0.2s ease; <?= !$tpl['active'] ? 'opacity: 0.6;' : '' ?>">
                        <div style="aspect-ratio: 3/4; background: #f8f9fa; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                            <?php if (!empty($tpl['path'])): ?>
                            <img src="<?= htmlspecialchars($tpl['path']) ?>" alt="<?= htmlspecialchars($tpl['nom'] ?? 'Template') ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <?php endif; ?>
                            <?php if (!$tpl['active']): ?>
                            <span style="position: absolute; top: 8px; right: 8px; background: #6b7280; color: #fff; padding: 4px 8px; font-size: 10px; border-radius: 4px;">Inactif</span>
                            <?php endif; ?>
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 8px; font-size: 10px; border-radius: 4px; font-weight: 600;">#<?= $tpl['id'] ?></span>
                        </div>
                        <div style="padding: 15px;">
                            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($tpl['nom'] ?: 'Sans nom') ?></h4>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
                                <?php if (!empty($tpl['sport'])): ?><span style="margin-right: 8px;"><?= htmlspecialchars($tpl['sport']) ?></span><?php endif; ?>
                                <?php if (!empty($tpl['famille'])): ?><span><?= htmlspecialchars($tpl['famille']) ?></span><?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="?page=template&id=<?= $tpl['id'] ?>" class="btn btn-sm btn-light" style="flex: 1; text-align: center;">Modifier</a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteTemplate(<?= $tpl['id'] ?>)" title="Supprimer">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function deleteTemplate(id) {
            if (!confirm('Supprimer ce template ?')) return;
            fetch('/api/templates-manager.php?id=' + id, { method: 'DELETE' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.template-card[data-id="' + id + '"]').remove();
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
                    }
                })
                .catch(e => alert('Erreur: ' + e.message));
        }

        function syncTemplates() {
            const btn = document.getElementById('sync-btn');
            btn.disabled = true;
            btn.innerHTML = '<span style="animation: spin 1s linear infinite; display: inline-block;">⟳</span> Synchronisation...';

            fetch('/api/templates-manager.php?action=sync', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        if (data.synced > 0) {
                            window.location.reload();
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Sync /templates/';
                        }
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de synchroniser'));
                        btn.disabled = false;
                        btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Sync /templates/';
                    }
                })
                .catch(e => {
                    alert('Erreur: ' + e.message);
                    btn.disabled = false;
                    btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Sync /templates/';
                });
        }
        </script>

        <?php // ============ TEMPLATE EDIT ============ ?>
        <?php elseif ($page === 'template'): ?>
        <?php $t = $data['item'] ?? []; $isNew = empty($t['id']); ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $isNew ? 'Nouveau template' : 'Modifier le template' ?></span>
                <a href="?page=templates" class="btn btn-light">← Retour</a>
            </div>
            <div class="card-body">
                <form id="template-form" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $t['id'] ?? '' ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom du template *</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($t['nom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fichier SVG</label>
                            <input type="file" name="svg_file" class="form-control" accept=".svg">
                            <?php if (!empty($t['path'])): ?>
                            <div class="form-hint">Fichier actuel dans /templates/</div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isNew && !empty($t['filename'])): ?>
                        <div class="form-group">
                            <label class="form-label">Nom du fichier</label>
                            <input type="text" name="new_filename" class="form-control" value="<?= htmlspecialchars($t['filename'] ?? '') ?>" placeholder="template.svg">
                            <div class="form-hint">Renommer le fichier SVG (sans caractères spéciaux)</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sports (sélection multiple)</label>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 10px;">
                            Cochez les sports pour lesquels ce template sera disponible. Si aucun n'est coché, le template est disponible pour tous.
                        </p>
                        <?php
                        // Récupérer les sports déjà associés (stockés dans un champ JSON ou séparés par virgule)
                        $templateSports = [];
                        if (!empty($t['sports'])) {
                            $templateSports = is_array($t['sports']) ? $t['sports'] : explode(',', $t['sports']);
                            $templateSports = array_map('trim', $templateSports);
                        } elseif (!empty($t['sport'])) {
                            $templateSports = [$t['sport']];
                        }
                        ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 15px; background: #fafbfc; border-radius: 8px; border: 1px solid var(--border);">
                            <?php foreach ($data['sports'] ?? [] as $sport): ?>
                            <label style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #fff; border: 1px solid var(--border); border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="sports[]" value="<?= htmlspecialchars($sport) ?>" <?= in_array($sport, $templateSports) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($sport) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Familles produit (sélection multiple)</label>
                            <?php
                            $templateFamilles = [];
                            if (!empty($t['familles'])) {
                                $templateFamilles = is_array($t['familles']) ? $t['familles'] : explode(',', $t['familles']);
                                $templateFamilles = array_map('trim', $templateFamilles);
                            } elseif (!empty($t['famille'])) {
                                $templateFamilles = [$t['famille']];
                            }
                            ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; background: #fafbfc; border-radius: 8px; border: 1px solid var(--border); max-height: 150px; overflow-y: auto;">
                                <?php foreach ($data['familles'] ?? [] as $famille): ?>
                                <label style="display: flex; align-items: center; gap: 5px; padding: 6px 10px; background: #fff; border: 1px solid var(--border); border-radius: 4px; cursor: pointer; font-size: 13px;">
                                    <input type="checkbox" name="familles[]" value="<?= htmlspecialchars($famille) ?>" <?= in_array($famille, $templateFamilles) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($famille) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catégorie</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($data['categories'] ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($t['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($t['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tags (séparés par virgule)</label>
                            <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($t['tags'] ?? '') ?>" placeholder="moderne, rayures, gradient...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" name="ordre" class="form-control" value="<?= intval($t['ordre'] ?? 0) ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="active" class="form-control">
                                <option value="1" <?= ($t['active'] ?? 1) ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= !($t['active'] ?? 1) ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </div>
                    </div>

                    <?php if (!empty($t['path'])): ?>
                    <div class="form-group">
                        <label class="form-label">Aperçu</label>
                        <div style="max-width: 300px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                            <img src="<?= htmlspecialchars($t['path']) ?>" alt="Preview" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                    <?php endif; ?>

                    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                    <h4 style="margin-bottom: 20px;">Produits associés</h4>
                    <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 13px;">
                        Sélectionnez les produits pour lesquels ce template sera disponible. Si aucun produit n'est sélectionné, le template sera disponible pour tous.
                    </p>

                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <input type="text" id="product-search" class="form-control" placeholder="Rechercher un produit..." style="max-width: 300px;">
                        <button type="button" class="btn btn-light" onclick="selectAllProducts()">Tout sélectionner</button>
                        <button type="button" class="btn btn-light" onclick="deselectAllProducts()">Tout désélectionner</button>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 10px;">
                        <?php
                        $currentSport = '';
                        $associatedProducts = $data['associated_products'] ?? [];
                        foreach ($data['all_products'] ?? [] as $prod):
                            if ($prod['sport'] !== $currentSport):
                                $currentSport = $prod['sport'];
                        ?>
                        <div class="product-sport-group" style="font-weight: 600; color: var(--primary); margin: 15px 0 10px; border-bottom: 1px solid var(--border); padding-bottom: 5px;"><?= htmlspecialchars($currentSport ?: 'Sans sport') ?></div>
                        <?php endif; ?>
                        <label class="product-checkbox" style="display: flex; align-items: center; gap: 10px; padding: 8px; margin: 2px 0; border-radius: 6px; cursor: pointer; transition: background 0.2s;" data-name="<?= htmlspecialchars(strtolower($prod['nom'])) ?>">
                            <input type="checkbox" name="products[]" value="<?= $prod['id'] ?>" <?= in_array($prod['id'], $associatedProducts) ? 'checked' : '' ?>>
                            <?php if (!empty($prod['photo_1'])): ?>
                            <img src="<?= htmlspecialchars($prod['photo_1']) ?>" alt="" style="width: 30px; height: 40px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                            <span style="flex: 1;"><?= htmlspecialchars($prod['nom']) ?></span>
                            <span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($prod['reference']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 15px;">
                        <button type="submit" class="btn btn-primary" id="save-btn">
                            <?= $isNew ? 'Créer le template' : 'Enregistrer les modifications' ?>
                        </button>
                        <a href="?page=templates" class="btn btn-light">Annuler</a>
                        <?php if (!$isNew): ?>
                        <button type="button" class="btn btn-danger" style="margin-left: auto;" onclick="deleteTemplate(<?= $t['id'] ?>)">Supprimer</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // Recherche de produits
        document.getElementById('product-search')?.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.product-checkbox').forEach(el => {
                const name = el.dataset.name || '';
                el.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });

        function selectAllProducts() {
            document.querySelectorAll('.product-checkbox input[type="checkbox"]').forEach(cb => cb.checked = true);
        }

        function deselectAllProducts() {
            document.querySelectorAll('.product-checkbox input[type="checkbox"]').forEach(cb => cb.checked = false);
        }

        // Soumission du formulaire
        document.getElementById('template-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('save-btn');
            btn.disabled = true;
            btn.textContent = 'Enregistrement...';

            const formData = new FormData(this);
            const id = formData.get('id');
            const isNew = !id;

            // Upload du fichier SVG si présent
            const svgFile = formData.get('svg_file');
            let uploadedPath = null;

            if (svgFile && svgFile.size > 0) {
                const uploadData = new FormData();
                uploadData.append('file', svgFile);

                const uploadRes = await fetch('/api/templates-manager.php?action=upload', {
                    method: 'POST',
                    body: uploadData
                });
                const uploadResult = await uploadRes.json();

                if (uploadResult.success) {
                    uploadedPath = uploadResult.file.path;
                } else {
                    alert('Erreur upload: ' + (uploadResult.error || 'Impossible de télécharger le fichier'));
                    btn.disabled = false;
                    btn.textContent = isNew ? 'Créer le template' : 'Enregistrer les modifications';
                    return;
                }
            }

            // Préparer les données JSON
            const data = {
                nom: formData.get('nom'),
                description: formData.get('description'),
                sports: formData.getAll('sports[]'),
                familles: formData.getAll('familles[]'),
                category_id: formData.get('category_id') || null,
                tags: formData.get('tags'),
                ordre: parseInt(formData.get('ordre')) || 0,
                active: parseInt(formData.get('active'))
            };

            // Renommage du fichier
            const newFilename = formData.get('new_filename');
            if (newFilename && !isNew) {
                data.new_filename = newFilename;
            }

            if (uploadedPath) {
                data.path = uploadedPath;
                data.filename = uploadedPath.split('/').pop();
            }

            // Sauvegarder le template
            const method = isNew ? 'POST' : 'PUT';
            const url = '/api/templates-manager.php' + (isNew ? '' : '?id=' + id);

            const res = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (result.success) {
                const templateId = isNew ? result.id : id;

                // Sauvegarder les produits associés
                const products = [];
                formData.getAll('products[]').forEach(p => products.push(parseInt(p)));

                await fetch('/api/template-products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ template_id: templateId, products: products })
                });

                window.location.href = '?page=templates&saved=1';
            } else {
                alert('Erreur: ' + (result.error || 'Impossible de sauvegarder'));
                btn.disabled = false;
                btn.textContent = isNew ? 'Créer le template' : 'Enregistrer les modifications';
            }
        });

        function deleteTemplate(id) {
            if (!confirm('Supprimer ce template définitivement ?')) return;
            fetch('/api/templates-manager.php?id=' + id, { method: 'DELETE' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '?page=templates&deleted=1';
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
                    }
                });
        }
        </script>

        <?php endif; ?>
    </div>
</main>
<?php endif; ?>

<script>
// Initialisation Quill pour les éditeurs WYSIWYG (gratuit, open source)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Quill !== 'undefined') {
        const quillInstances = [];
        const quillToolbarOptions = [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['link', 'image'],
            ['clean']
        ];

        // Pour chaque textarea.wysiwyg, créer un éditeur Quill
        document.querySelectorAll('textarea.wysiwyg').forEach(function(textarea, index) {
            // Créer le conteneur Quill
            const container = document.createElement('div');
            container.className = 'quill-editor-container';
            const editorDiv = document.createElement('div');
            editorDiv.id = 'quill-editor-' + index;
            editorDiv.innerHTML = textarea.value;
            container.appendChild(editorDiv);

            // Cacher le textarea et insérer le conteneur
            textarea.style.display = 'none';
            textarea.parentNode.insertBefore(container, textarea);

            // Initialiser Quill
            const quill = new Quill('#quill-editor-' + index, {
                theme: 'snow',
                modules: {
                    toolbar: quillToolbarOptions
                }
            });

            // Stocker la référence
            quillInstances.push({ quill: quill, textarea: textarea });

            // Sync au changement
            quill.on('text-change', function() {
                textarea.value = quill.root.innerHTML;
            });
        });

        // Sync avant soumission du formulaire
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function() {
                quillInstances.forEach(function(instance) {
                    instance.textarea.value = instance.quill.root.innerHTML;
                });
            });
        });
    }
});

// Preview du guide des tailles
function toggleCustomSizes(select) {
    var preview = document.getElementById('size-chart-preview');
    var previewContent = document.getElementById('size-chart-preview-content');
    var customArea = document.getElementById('custom-sizes-area');
    var selected = select.options[select.selectedIndex];

    if (select.value && selected.dataset.content) {
        preview.style.display = 'block';
        previewContent.innerHTML = selected.dataset.content;
        customArea.style.opacity = '0.5';
    } else {
        preview.style.display = 'none';
        previewContent.innerHTML = '';
        customArea.style.opacity = '1';
    }
}

// Initialiser le preview si un guide est déjà sélectionné
document.addEventListener('DOMContentLoaded', function() {
    var sizeSelect = document.querySelector('select[name="size_chart_id"]');
    if (sizeSelect && sizeSelect.value) {
        toggleCustomSizes(sizeSelect);
    }
});
</script>

</body>
</html>
