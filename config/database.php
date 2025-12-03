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
define('DB_PASS', 'TON_MOT_DE_PASSE_ICI');                  // ⚠️ REMPLACE ICI PAR TON VRAI MOT DE PASSE !
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
