<?php
/**
 * FLARE CUSTOM - Blog Model
 * Gestion des articles de blog
 */

require_once __DIR__ . '/../config/database.php';

class Blog {
    private $pdo;

    public function __construct() {
        $this->pdo = getConnection();
    }

    /**
     * Récupérer tous les articles
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[] = 'category = :category';
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR excerpt LIKE :search OR content LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM blog_posts WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get articles
        $sql = "SELECT * FROM blog_posts
                WHERE $whereClause
                ORDER BY created_at DESC
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
     * Récupérer un article par ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un article par slug
     */
    public function getBySlug($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM blog_posts WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un article
     */
    public function create($data) {
        // Générer le slug si non fourni
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $sql = "INSERT INTO blog_posts
                (title, slug, excerpt, content, featured_image, featured_image_alt,
                 category, tags, meta_title, meta_description, meta_keywords,
                 author_id, author_name, status, published_at, reading_time)
                VALUES
                (:title, :slug, :excerpt, :content, :featured_image, :featured_image_alt,
                 :category, :tags, :meta_title, :meta_description, :meta_keywords,
                 :author_id, :author_name, :status, :published_at, :reading_time)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':excerpt' => $data['excerpt'] ?? null,
            ':content' => $data['content'] ?? null,
            ':featured_image' => $data['featured_image'] ?? null,
            ':featured_image_alt' => $data['featured_image_alt'] ?? null,
            ':category' => $data['category'] ?? null,
            ':tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            ':meta_title' => $data['meta_title'] ?? $data['title'],
            ':meta_description' => $data['meta_description'] ?? $data['excerpt'],
            ':meta_keywords' => $data['meta_keywords'] ?? null,
            ':author_id' => $data['author_id'] ?? null,
            ':author_name' => $data['author_name'] ?? 'Admin',
            ':status' => $data['status'] ?? 'draft',
            ':published_at' => ($data['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null,
            ':reading_time' => $data['reading_time'] ?? $this->calculateReadingTime($data['content'] ?? '')
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un article
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'title', 'slug', 'excerpt', 'content', 'featured_image', 'featured_image_alt',
            'category', 'tags', 'meta_title', 'meta_description', 'meta_keywords',
            'author_name', 'status', 'reading_time'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                if ($field === 'tags' && is_array($data[$field])) {
                    $params[":$field"] = json_encode($data[$field]);
                } else {
                    $params[":$field"] = $data[$field];
                }
            }
        }

        // Gérer published_at
        if (isset($data['status']) && $data['status'] === 'published') {
            $fields[] = "published_at = COALESCE(published_at, NOW())";
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE blog_posts SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprimer un article
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM blog_posts WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Compter les articles
     */
    public function count($status = null) {
        $sql = "SELECT COUNT(*) as total FROM blog_posts";
        $params = [];

        if ($status) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Récupérer les catégories de blog
     */
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM blog_categories WHERE active = 1 ORDER BY ordre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Incrémenter le compteur de vues
     */
    public function incrementViews($id) {
        $stmt = $this->pdo->prepare("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
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
     * Calculer le temps de lecture
     */
    private function calculateReadingTime($content) {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // 200 mots par minute
        return max(1, $readingTime);
    }

    /**
     * Articles récents
     */
    public function getRecent($limit = 5) {
        $stmt = $this->pdo->prepare(
            "SELECT id, title, slug, excerpt, featured_image, category, published_at
             FROM blog_posts
             WHERE status = 'published'
             ORDER BY published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Articles par catégorie
     */
    public function getByCategory($category, $limit = 10) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM blog_posts
             WHERE category = :category AND status = 'published'
             ORDER BY published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
