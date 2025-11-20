<?php
/**
 * FLARE CUSTOM - Database Configuration
 * Configuration de connexion à la base de données
 */

// Configuration selon l'environnement
$env = getenv('APP_ENV') ?: 'production';

if ($env === 'development') {
    // Environnement de développement
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'flare_custom');
    define('DB_USER', 'flare_user');
    define('DB_PASS', 'flare_password');
    define('DB_CHARSET', 'utf8mb4');

    // Activer les erreurs en dev
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Environnement de production
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'flare_custom');
    define('DB_USER', getenv('DB_USER') ?: 'flare_user');
    define('DB_PASS', getenv('DB_PASS') ?: 'flare_password');
    define('DB_CHARSET', 'utf8mb4');

    // Désactiver les erreurs en production
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Classe Database - Singleton pour la connexion PDO
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
            error_log("Database connection error: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'error' => 'Database connection failed'
            ]));
        }
    }

    /**
     * Retourne l'instance unique de Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Empêche le clonage
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
