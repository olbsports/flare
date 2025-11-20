<?php
/**
 * FLARE CUSTOM - Page Model
 * Gestion des pages dynamiques
 */

class Page {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Récupérer toutes les pages avec filtres
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM pages WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR slug LIKE :search OR content LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY updated_at DESC";

        if (!empty($filters['limit'])) {
            $offset = ((int)($filters['page'] ?? 1) - 1) * (int)$filters['limit'];
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if (!empty($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les pages
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) FROM pages WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR slug LIKE :search OR content LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupérer une page par ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer une page par slug
     */
    public function getBySlug($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une page
     */
    public function create($data) {
        $sql = "INSERT INTO pages (
            title, slug, content, type, template,
            meta_title, meta_description, meta_keywords,
            status, published_at, author_id
        ) VALUES (
            :title, :slug, :content, :type, :template,
            :meta_title, :meta_description, :meta_keywords,
            :status, :published_at, :author_id
        )";

        $stmt = $this->pdo->prepare($sql);

        // Générer un slug si non fourni
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content' => $data['content'] ?? '',
            ':type' => $data['type'] ?? 'page',
            ':template' => $data['template'] ?? 'default',
            ':meta_title' => $data['meta_title'] ?? $data['title'],
            ':meta_description' => $data['meta_description'] ?? '',
            ':meta_keywords' => $data['meta_keywords'] ?? '',
            ':status' => $data['status'] ?? 'draft',
            ':published_at' => $data['published_at'] ?? null,
            ':author_id' => $data['author_id'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour une page
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['title', 'slug', 'content', 'type', 'template',
                   'meta_title', 'meta_description', 'meta_keywords',
                   'status', 'published_at'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprimer une page
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Générer un slug unique
     */
    private function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        // Vérifier l'unicité
        $base_slug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifier si un slug existe
     */
    private function slugExists($slug) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Statistiques
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'published' => 0,
            'draft' => 0,
            'by_type' => []
        ];

        // Total
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pages");
        $stats['total'] = (int)$stmt->fetchColumn();

        // Par statut
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count
            FROM pages
            GROUP BY status
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = (int)$row['count'];
        }

        // Par type
        $stmt = $this->pdo->query("
            SELECT type, COUNT(*) as count
            FROM pages
            GROUP BY type
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_type'][$row['type']] = (int)$row['count'];
        }

        return $stats;
    }
}
