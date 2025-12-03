<?php
/**
 * CONFIGURATION BASE DE DONNÉES - O2SWITCH
 *
 * ⚠️ INSTRUCTIONS :
 * 1. Remplace 'TON_MOT_DE_PASSE_ICI' par ton VRAI mot de passe MySQL (ligne 11)
 * 2. Enregistre ce fichier
 * 3. C'est tout !
 */

// ⚠️ METS TES IDENTIFIANTS ICI (en dur, pas de getenv()) :
define('DB_HOST', 'localhost');                              // o2switch utilise 'localhost'
define('DB_NAME', 'sc1ispy2055_flare_custom');              // Ton nom de BDD
define('DB_USER', 'sc1ispy2055_flare_adm');                 // ⚠️ CORRIGÉ : L'user c'est _adm !
define('DB_PASS', '[DF%&c@xahF4');                           // Mot de passe configuré
define('DB_CHARSET', 'utf8mb4');

// Activer les erreurs pour déboguer
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Classe Database - Singleton pour connexion PDO
 */
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            // Afficher l'erreur complète pour déboguer
            die("<h1>ERREUR CONNEXION BDD</h1>" .
                "<p><strong>Message :</strong> " . $e->getMessage() . "</p>" .
                "<hr>" .
                "<h2>Vérifications :</h2>" .
                "<ol>" .
                "<li>As-tu bien remplacé 'TON_MOT_DE_PASSE_ICI' par ton vrai mot de passe dans config/database.php ligne 11 ?</li>" .
                "<li>La base <strong>" . DB_NAME . "</strong> existe-t-elle dans cPanel > MySQL Databases ?</li>" .
                "<li>L'utilisateur <strong>" . DB_USER . "</strong> a-t-il les droits sur cette base ?</li>" .
                "</ol>");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function __clone() {}

    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Fonction helper pour obtenir la connexion PDO
 * Compatible avec les anciens fichiers
 */
function getConnection() {
    return Database::getInstance()->getConnection();
}

/**
 * Récupère un paramètre depuis la table settings
 */
function getSetting($key, $default = '') {
    static $settings = null;
    if ($settings === null) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Récupère tous les paramètres
 */
function getAllSettings() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère un produit par sa référence
 */
function getProductByReference($reference) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE reference = ? AND active = 1");
        $stmt->execute([$reference]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Récupère un produit par son ID
 */
function getProductById($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Récupère les produits par sport
 */
function getProductsBySport($sport, $limit = 100) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE sport = ? AND active = 1 ORDER BY nom LIMIT ?");
        $stmt->execute([$sport, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère les produits par famille
 */
function getProductsByFamille($famille, $limit = 100) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE famille = ? AND active = 1 ORDER BY nom LIMIT ?");
        $stmt->execute([$famille, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Recherche de produits
 */
function searchProducts($query, $limit = 50) {
    try {
        $pdo = getConnection();
        $search = '%' . $query . '%';
        $stmt = $pdo->prepare("SELECT * FROM products WHERE active = 1 AND (nom LIKE ? OR reference LIKE ? OR description LIKE ?) ORDER BY nom LIMIT ?");
        $stmt->execute([$search, $search, $search, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère toutes les catégories
 */
function getCategories($type = null) {
    try {
        $pdo = getConnection();
        if ($type) {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? AND active = 1 ORDER BY ordre, nom");
            $stmt->execute([$type]);
        } else {
            $stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY type, ordre, nom");
        }
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère tous les sports
 */
function getSports() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport != '' AND active = 1 ORDER BY sport");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère toutes les familles
 */
function getFamilles() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille != '' AND active = 1 ORDER BY famille");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère une page par son slug
 */
function getPageBySlug($slug) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Récupère les articles de blog
 */
function getBlogPosts($limit = 10, $category = null) {
    try {
        $pdo = getConnection();
        if ($category) {
            $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status = 'published' AND category = ? ORDER BY published_at DESC LIMIT ?");
            $stmt->execute([$category, $limit]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT ?");
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère un article de blog par son slug
 */
function getBlogPostBySlug($slug) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Crée un nouveau devis
 */
function createQuote($data) {
    try {
        $pdo = getConnection();
        $prefix = getSetting('quote_prefix', 'DEV-');
        $reference = $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $stmt = $pdo->prepare("INSERT INTO quotes (reference, client_nom, client_prenom, client_email, client_telephone, client_club, product_reference, product_nom, sport, total_pieces, prix_unitaire, prix_total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([
            $reference,
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? '',
            $data['club'] ?? '',
            $data['product_reference'] ?? '',
            $data['product_nom'] ?? '',
            $data['sport'] ?? '',
            $data['total_pieces'] ?? 0,
            $data['prix_unitaire'] ?? 0,
            $data['prix_total'] ?? 0
        ]);

        return $reference;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Vérifie si le mode maintenance est activé
 */
function isMaintenanceMode() {
    return getSetting('maintenance_mode', '0') === '1';
}

/**
 * Récupère le prix d'un produit selon la quantité
 */
function getProductPrice($product, $quantity) {
    $priceColumns = [
        500 => 'prix_500',
        250 => 'prix_250',
        100 => 'prix_100',
        50 => 'prix_50',
        20 => 'prix_20',
        10 => 'prix_10',
        5 => 'prix_5',
        1 => 'prix_1'
    ];

    foreach ($priceColumns as $qty => $column) {
        if ($quantity >= $qty && !empty($product[$column]) && $product[$column] > 0) {
            return floatval($product[$column]);
        }
    }

    return floatval($product['prix_1'] ?? 0);
}

/**
 * Récupère la configuration du configurateur par défaut
 */
function getDefaultConfiguratorConfig() {
    return [
        'design_options' => [
            'flare' => getSetting('configurator_design_flare', '1') === '1',
            'client' => getSetting('configurator_design_client', '1') === '1',
            'template' => getSetting('configurator_design_template', '1') === '1'
        ],
        'personalization' => [
            'nom' => getSetting('configurator_perso_nom', '1') === '1',
            'numero' => getSetting('configurator_perso_numero', '1') === '1',
            'logo' => getSetting('configurator_perso_logo', '1') === '1',
            'sponsor' => getSetting('configurator_perso_sponsor', '1') === '1'
        ],
        'sizes' => explode(',', getSetting('configurator_sizes', 'XS,S,M,L,XL,XXL,3XL')),
        'sizes_kids' => explode(',', getSetting('configurator_sizes_kids', '6ans,8ans,10ans,12ans,14ans')),
        'collars' => explode(',', getSetting('configurator_collars', 'col_v,col_rond,col_polo')),
        'min_quantity' => intval(getSetting('min_order_quantity', '1')),
        'delivery_time' => getSetting('default_delivery_time', '3-4 semaines')
    ];
}
