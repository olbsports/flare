<?php
/**
 * FLARE CUSTOM - Template Class
 * Gestion des templates de design (SVG, PNG, etc.)
 */

require_once __DIR__ . '/../config/database.php';

class Template {
    private $db;
    private $table = 'templates';
    private $templateDir = __DIR__ . '/../assets/templates/';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Créer le dossier templates s'il n'existe pas
        if (!file_exists($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }
    }

    /**
     * Récupère tous les templates
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE active = 1";
        $params = [];

        // Filtres
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['tags'])) {
            $sql .= " AND tags LIKE :tags";
            $params[':tags'] = '%' . $filters['tags'] . '%';
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (nom LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 30;
        $offset = ($page - 1) * $limit;

        $sql .= " ORDER BY ordre ASC, created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère un template par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Récupère un template par filename
     */
    public function getByFilename($filename) {
        $sql = "SELECT * FROM {$this->table} WHERE filename = :filename AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':filename', $filename);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Crée un nouveau template
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (
            filename, nom, description, path, preview_url, type, tags, ordre, active
        ) VALUES (
            :filename, :nom, :description, :path, :preview_url, :type, :tags, :ordre, :active
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':filename', $data['filename']);
        $stmt->bindValue(':nom', $data['nom'] ?? null);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':path', $data['path']);
        $stmt->bindValue(':preview_url', $data['preview_url'] ?? null);
        $stmt->bindValue(':type', $data['type'] ?? 'svg');
        $stmt->bindValue(':tags', $data['tags'] ?? null);
        $stmt->bindValue(':ordre', $data['ordre'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Upload un nouveau template
     */
    public function upload($file, $data = []) {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['svg', 'png', 'jpg', 'jpeg'];

        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Format de fichier non supporté');
        }

        // Vérifier la taille (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Le fichier est trop volumineux (max 5MB)');
        }

        // Générer un nom de fichier unique
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->templateDir . $filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Erreur lors de l\'enregistrement du fichier');
        }

        // Créer l'entrée en BDD
        $templateData = [
            'filename' => $filename,
            'nom' => $data['nom'] ?? pathinfo($file['name'], PATHINFO_FILENAME),
            'description' => $data['description'] ?? null,
            'path' => $filepath,
            'preview_url' => '/assets/templates/' . $filename,
            'type' => $extension === 'svg' ? 'svg' : ($extension === 'png' ? 'png' : 'jpg'),
            'tags' => $data['tags'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
            'active' => $data['active'] ?? 1
        ];

        $id = $this->create($templateData);

        return $this->getById($id);
    }

    /**
     * Met à jour un template
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['nom', 'description', 'preview_url', 'tags', 'ordre', 'active'];

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
     * Supprime un template (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Supprime définitivement un template
     */
    public function hardDelete($id) {
        $template = $this->getById($id);

        if (!$template) {
            return false;
        }

        // Supprimer le fichier physique
        if (file_exists($template['path'])) {
            unlink($template['path']);
        }

        // Supprimer de la BDD
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compte le nombre de templates
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
     * Lit le contenu d'un template SVG
     */
    public function getSvgContent($id) {
        $template = $this->getById($id);

        if (!$template || $template['type'] !== 'svg') {
            return null;
        }

        if (!file_exists($template['path'])) {
            return null;
        }

        return file_get_contents($template['path']);
    }

    /**
     * Scan le dossier templates et importe les nouveaux
     */
    public function scanAndImport() {
        $imported = 0;
        $skipped = 0;

        $files = glob($this->templateDir . '*.{svg,png,jpg,jpeg}', GLOB_BRACE);

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            // Vérifier si déjà en BDD
            if ($this->getByFilename($filename)) {
                $skipped++;
                continue;
            }

            // Créer l'entrée
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $this->create([
                'filename' => $filename,
                'nom' => pathinfo($filename, PATHINFO_FILENAME),
                'path' => $filepath,
                'preview_url' => '/assets/templates/' . $filename,
                'type' => $extension === 'svg' ? 'svg' : ($extension === 'png' ? 'png' : 'jpg'),
                'active' => 1
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped
        ];
    }
}
