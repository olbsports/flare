<?php
/**
 * FLARE CUSTOM - Page Model
 * Gestion des pages dynamiques
 */

require_once __DIR__ . '/../config/database.php';

class Page {
    private $pdo;

    public function __construct() {
        $this->pdo = getConnection();
    }

    /**
     * Récupérer toutes les pages
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR content LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM pages WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get pages
        $sql = "SELECT p.*, u.username as author_name
                FROM pages p
                LEFT JOIN users u ON p.author_id = u.id
                WHERE $whereClause
                ORDER BY p.updated_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Récupérer une page par ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.username as author_name
             FROM pages p
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer une page par slug
     */
    public function getBySlug($slug) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.username as author_name
             FROM pages p
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.slug = :slug"
        );
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une page
     */
    public function create($data) {
        // Générer le slug si non fourni
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $sql = "INSERT INTO pages
                (title, slug, content, type, template, meta_title, meta_description,
                 meta_keywords, status, author_id, published_at)
                VALUES
                (:title, :slug, :content, :type, :template, :meta_title, :meta_description,
                 :meta_keywords, :status, :author_id, :published_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content' => $data['content'] ?? null,
            ':type' => $data['type'] ?? 'page',
            ':template' => $data['template'] ?? 'default',
            ':meta_title' => $data['meta_title'] ?? $data['title'],
            ':meta_description' => $data['meta_description'] ?? null,
            ':meta_keywords' => $data['meta_keywords'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':author_id' => $data['author_id'] ?? null,
            ':published_at' => ($data['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour une page
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'title', 'slug', 'content', 'type', 'template',
            'meta_title', 'meta_description', 'meta_keywords', 'status'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        // Gérer published_at
        if (isset($data['status']) && $data['status'] === 'published') {
            $fields[] = "published_at = COALESCE(published_at, NOW())";
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
        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Compter les pages
     */
    public function count($status = null, $type = null) {
        $sql = "SELECT COUNT(*) as total FROM pages";
        $where = [];
        $params = [];

        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if ($type) {
            $where[] = "type = :type";
            $params[':type'] = $type;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Générer un slug unique
     */
    private function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Vérifier l'unicité
        $original = $slug;
        $counter = 1;
        while ($this->getBySlug($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Publier une page
     */
    public function publish($id) {
        return $this->update($id, ['status' => 'published']);
    }

    /**
     * Archiver une page
     */
    public function archive($id) {
        return $this->update($id, ['status' => 'archived']);
    }

    /**
     * Dupliquer une page
     */
    public function duplicate($id) {
        $page = $this->getById($id);
        if (!$page) {
            return false;
        }

        unset($page['id']);
        $page['title'] = $page['title'] . ' (copie)';
        $page['slug'] = $this->generateSlug($page['title']);
        $page['status'] = 'draft';
        $page['published_at'] = null;

        return $this->create($page);
    }

    /**
     * Pages publiées
     */
    public function getPublished($type = null) {
        $sql = "SELECT id, title, slug, type, template
                FROM pages
                WHERE status = 'published'";
        $params = [];

        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY title";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
