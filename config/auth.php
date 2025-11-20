<?php
/**
 * Système d'authentification sécurisé
 * Gère les sessions, login, logout, vérification des permissions
 */

// Démarre la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private static $instance = null;
    private $db;

    private function __construct() {
        require_once __DIR__ . '/database.php';
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function check() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']);
    }

    /**
     * Retourne l'utilisateur connecté
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }

        $instance = self::getInstance();
        $stmt = $instance->db->prepare("
            SELECT id, username, email, role, created_at, last_login
            FROM users
            WHERE id = ? AND active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tente de connecter un utilisateur
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, role, active
                FROM users
                WHERE (username = ? OR email = ?) AND active = 1
            ");
            $stmt->execute([$username, $username]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'error' => 'Utilisateur introuvable ou inactif'];
            }

            // Vérifie le mot de passe
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Mot de passe incorrect'];
            }

            // Régénère l'ID de session (sécurité)
            session_regenerate_id(true);

            // Enregistre l'utilisateur en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Met à jour last_login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de la connexion: ' . $e->getMessage()];
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout() {
        // Détruit toutes les variables de session
        $_SESSION = array();

        // Détruit le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        // Détruit la session
        session_destroy();

        return true;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($role) {
        if (!self::check()) {
            return false;
        }

        return $_SESSION['role'] === $role;
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }

    /**
     * Vérifie si l'utilisateur est éditeur ou admin
     */
    public static function canEdit() {
        if (!self::check()) {
            return false;
        }

        return in_array($_SESSION['role'], ['admin', 'editor']);
    }

    /**
     * Redirige vers login si pas connecté
     */
    public static function requireAuth($redirectTo = '/admin/login.php') {
        if (!self::check()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /**
     * Redirige vers login si pas admin
     */
    public static function requireAdmin($redirectTo = '/admin/login.php') {
        if (!self::isAdmin()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser($username, $email, $password, $role = 'editor') {
        try {
            // Vérifie si le username existe déjà
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Cet utilisateur ou email existe déjà'];
            }

            // Hash le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insère l'utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, role, active)
                VALUES (?, ?, ?, ?, 1)
            ");

            $stmt->execute([$username, $email, $hashedPassword, $role]);

            return [
                'success' => true,
                'user_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de la création: ' . $e->getMessage()];
        }
    }

    /**
     * Change le mot de passe d'un utilisateur
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Récupère le mot de passe actuel
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'error' => 'Utilisateur introuvable'];
            }

            // Vérifie l'ancien mot de passe
            if (!password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'error' => 'Ancien mot de passe incorrect'];
            }

            // Hash le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Met à jour
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors du changement: ' . $e->getMessage()];
        }
    }
}
