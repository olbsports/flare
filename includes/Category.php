<?php
/**
 * FLARE CUSTOM - Category Class
 * Gestion des catégories (sports et familles)
 */

require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les catégories
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE active = 1";
        $params = [];

        // Filtres
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['parent_id'])) {
            $sql .= " AND parent_id = :parent_id";
            $params[':parent_id'] = $filters['parent_id'];
        } elseif (isset($filters['root']) && $filters['root']) {
            $sql .= " AND parent_id IS NULL";
        }

        $sql .= " ORDER BY ordre ASC, nom ASC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère une catégorie par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Récupère une catégorie par slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Récupère les catégories par type
     */
    public function getByType($type) {
        return $this->getAll(['type' => $type]);
    }

    /**
     * Récupère les enfants d'une catégorie
     */
    public function getChildren($parentId) {
        return $this->getAll(['parent_id' => $parentId]);
    }

    /**
     * Récupère les catégories racines (sans parent)
     */
    public function getRootCategories($type = null) {
        $filters = ['root' => true];
        if ($type) {
            $filters['type'] = $type;
        }
        return $this->getAll($filters);
    }

    /**
     * Crée une nouvelle catégorie
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (
            nom, slug, type, description, image, parent_id, ordre, active
        ) VALUES (
            :nom, :slug, :type, :description, :image, :parent_id, :ordre, :active
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':nom', $data['nom']);
        $stmt->bindValue(':slug', $data['slug'] ?? $this->generateSlug($data['nom']));
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':image', $data['image'] ?? null);
        $stmt->bindValue(':parent_id', $data['parent_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':ordre', $data['ordre'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour une catégorie
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['nom', 'slug', 'type', 'description', 'image', 'parent_id', 'ordre', 'active'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
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
     * Supprime une catégorie (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compte le nombre de catégories
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE active = 1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Récupère l'arbre des catégories
     */
    public function getTree($type = null) {
        $rootCategories = $this->getRootCategories($type);

        foreach ($rootCategories as &$category) {
            $category['children'] = $this->getChildren($category['id']);
        }

        return $rootCategories;
    }

    /**
     * Génère un slug unique
     */
    private function generateSlug($text) {
        // Normalisation
        $text = strtolower(trim($text));
        $text = preg_replace('/[àáâãäå]/', 'a', $text);
        $text = preg_replace('/[èéêë]/', 'e', $text);
        $text = preg_replace('/[ìíîï]/', 'i', $text);
        $text = preg_replace('/[òóôõö]/', 'o', $text);
        $text = preg_replace('/[ùúûü]/', 'u', $text);
        $text = preg_replace('/[ç]/', 'c', $text);
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        // Vérifier unicité
        $slug = $text;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $text . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifie si un slug existe
     */
    private function slugExists($slug) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Récupère les produits d'une catégorie
     */
    public function getProducts($categoryId, $page = 1, $limit = 20) {
        $category = $this->getById($categoryId);

        if (!$category) {
            return [];
        }

        require_once __DIR__ . '/Product.php';
        $productModel = new Product();

        $filters = [
            'page' => $page,
            'limit' => $limit
        ];

        if ($category['type'] === 'sport') {
            $filters['sport'] = $category['nom'];
        } elseif ($category['type'] === 'famille') {
            $filters['famille'] = $category['nom'];
        }

        return $productModel->getAll($filters);
    }
}
