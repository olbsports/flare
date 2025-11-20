<?php
/**
 * FLARE CUSTOM - Settings Class
 * Gestion des paramètres du site
 */

require_once __DIR__ . '/../config/database.php';

class Settings {
    private $db;
    private $table = 'settings';
    private static $cache = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les paramètres
     */
    public function getAll($category = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($category) {
            $sql .= " WHERE category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY category ASC, setting_key ASC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $settings = $stmt->fetchAll();

        // Convertir les valeurs selon leur type
        foreach ($settings as &$setting) {
            $setting['setting_value'] = $this->convertValue($setting['setting_value'], $setting['setting_type']);
        }

        return $settings;
    }

    /**
     * Récupère un paramètre par sa clé
     */
    public function get($key, $default = null) {
        // Vérifier le cache
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $sql = "SELECT * FROM {$this->table} WHERE setting_key = :key LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':key', $key);
        $stmt->execute();

        $setting = $stmt->fetch();

        if (!$setting) {
            return $default;
        }

        $value = $this->convertValue($setting['setting_value'], $setting['setting_type']);

        // Mettre en cache
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Définit un paramètre
     */
    public function set($key, $value, $type = 'string', $category = 'general', $description = null) {
        // Vérifier si le paramètre existe
        $existing = $this->get($key);

        // Convertir la valeur en string pour le stockage
        $stringValue = $this->convertToString($value, $type);

        if ($existing !== null) {
            // Mettre à jour
            $sql = "UPDATE {$this->table} SET setting_value = :value, setting_type = :type WHERE setting_key = :key";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':value', $stringValue);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':key', $key);
        } else {
            // Créer
            $sql = "INSERT INTO {$this->table} (setting_key, setting_value, setting_type, category, description)
                    VALUES (:key, :value, :type, :category, :description)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':key', $key);
            $stmt->bindValue(':value', $stringValue);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':category', $category);
            $stmt->bindValue(':description', $description);
        }

        $result = $stmt->execute();

        // Mettre à jour le cache
        if ($result) {
            self::$cache[$key] = $value;
        }

        return $result;
    }

    /**
     * Supprime un paramètre
     */
    public function delete($key) {
        $sql = "DELETE FROM {$this->table} WHERE setting_key = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':key', $key);
        $result = $stmt->execute();

        // Supprimer du cache
        if ($result && isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
        }

        return $result;
    }

    /**
     * Récupère tous les paramètres d'une catégorie
     */
    public function getByCategory($category) {
        return $this->getAll($category);
    }

    /**
     * Récupère toutes les catégories
     */
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM {$this->table} ORDER BY category ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $categories;
    }

    /**
     * Met à jour plusieurs paramètres en masse
     */
    public function updateBulk($settings) {
        $updated = 0;
        $errors = [];

        foreach ($settings as $key => $data) {
            try {
                $value = $data['value'] ?? $data;
                $type = $data['type'] ?? 'string';
                $category = $data['category'] ?? 'general';
                $description = $data['description'] ?? null;

                if ($this->set($key, $value, $type, $category, $description)) {
                    $updated++;
                }
            } catch (Exception $e) {
                $errors[] = "Erreur pour $key: " . $e->getMessage();
            }
        }

        return [
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    /**
     * Vide le cache
     */
    public function clearCache() {
        self::$cache = [];
    }

    /**
     * Convertit une valeur selon son type
     */
    private function convertValue($value, $type) {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'number':
                return is_numeric($value) ? (float)$value : null;

            case 'json':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;

            case 'text':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Convertit une valeur en string pour le stockage
     */
    private function convertToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';

            case 'json':
                return is_string($value) ? $value : json_encode($value);

            case 'number':
            case 'text':
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * Exporte tous les paramètres
     */
    public function export() {
        $settings = $this->getAll();
        $export = [];

        foreach ($settings as $setting) {
            $export[$setting['setting_key']] = [
                'value' => $setting['setting_value'],
                'type' => $setting['setting_type'],
                'category' => $setting['category'],
                'description' => $setting['description']
            ];
        }

        return $export;
    }

    /**
     * Importe des paramètres
     */
    public function import($settings) {
        return $this->updateBulk($settings);
    }
}
