<?php
/**
 * FLARE CUSTOM - Auth Class
 * Gestion de l'authentification et des permissions
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Démarrer la session si elle n'est pas déjà active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authentifie un utilisateur
     */
    public function login($username, $password) {
        // Récupérer l'utilisateur
        $sql = "SELECT * FROM {$this->table} WHERE (username = :username OR email = :username) AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'Identifiants invalides'
            ];
        }

        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'error' => 'Identifiants invalides'
            ];
        }

        // Mettre à jour la date de dernière connexion
        $updateSql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $updateStmt->execute();

        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Retourner les infos utilisateur (sans le mot de passe)
        unset($user['password']);

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout() {
        // Détruire la session
        session_unset();
        session_destroy();

        return [
            'success' => true,
            'message' => 'Déconnexion réussie'
        ];
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Récupère l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {
            unset($user['password']);
        }

        return $user;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return $_SESSION['role'] === $role;
    }

    /**
     * Vérifie si l'utilisateur a au moins un rôle spécifique
     */
    public function hasMinRole($minRole) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $roles = ['viewer', 'editor', 'admin'];
        $currentRoleIndex = array_search($_SESSION['role'], $roles);
        $minRoleIndex = array_search($minRole, $roles);

        return $currentRoleIndex >= $minRoleIndex;
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Vérifie si l'utilisateur peut éditer
     */
    public function canEdit() {
        return $this->hasMinRole('editor');
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser($data) {
        // Vérifier que l'email n'existe pas
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            throw new Exception('Un utilisateur avec cet email existe déjà');
        }

        // Vérifier que le username n'existe pas
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $data['username']);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            throw new Exception('Ce nom d\'utilisateur est déjà pris');
        }

        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insérer l'utilisateur
        $sql = "INSERT INTO {$this->table} (username, email, password, role, active)
                VALUES (:username, :email, :password, :role, :active)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password', $hashedPassword);
        $stmt->bindValue(':role', $data['role'] ?? 'editor');
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['username', 'email', 'role', 'active'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        // Gérer le mot de passe séparément
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser($id) {
        // Soft delete
        $sql = "UPDATE {$this->table} SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function getAllUsers() {
        $sql = "SELECT id, username, email, role, created_at, updated_at, last_login, active
                FROM {$this->table}
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Change le mot de passe de l'utilisateur connecté
     */
    public function changePassword($currentPassword, $newPassword) {
        if (!$this->isLoggedIn()) {
            throw new Exception('Vous devez être connecté');
        }

        $user = $this->getCurrentUser();

        // Récupérer le mot de passe actuel
        $sql = "SELECT password FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch();

        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $userData['password'])) {
            throw new Exception('Mot de passe actuel incorrect');
        }

        // Mettre à jour le mot de passe
        return $this->updateUser($user['id'], ['password' => $newPassword]);
    }

    /**
     * Génère un token de réinitialisation de mot de passe
     */
    public function generatePasswordResetToken($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('Aucun utilisateur trouvé avec cet email');
        }

        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // TODO: Stocker le token et la date d'expiration dans une table dédiée
        // Pour le moment, on retourne juste le token

        return [
            'token' => $token,
            'expires' => $expires,
            'user_id' => $user['id']
        ];
    }

    /**
     * Middleware pour protéger les routes
     */
    public function requireAuth($minRole = null) {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentification requise'
            ]);
            exit;
        }

        if ($minRole && !$this->hasMinRole($minRole)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Permissions insuffisantes'
            ]);
            exit;
        }

        return true;
    }
}
