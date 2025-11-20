<?php
/**
 * FLARE CUSTOM - Media Class
 * Gestion de la bibliothèque médias
 */

require_once __DIR__ . '/../config/database.php';

class Media {
    private $db;
    private $table = 'media';
    private $uploadDir = __DIR__ . '/../assets/uploads/';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Créer le dossier uploads s'il n'existe pas
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Récupère tous les médias
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Filtres
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (filename LIKE :search OR title LIKE :search OR alt_text LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 30;
        $offset = ($page - 1) * $limit;

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

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
     * Récupère un média par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Récupère un média par filename
     */
    public function getByFilename($filename) {
        $sql = "SELECT * FROM {$this->table} WHERE filename = :filename LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':filename', $filename);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Upload un nouveau média
     */
    public function upload($file, $data = []) {
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }

        // Vérifier la taille (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Le fichier est trop volumineux (max 10MB)');
        }

        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . strtolower($extension);
        $filepath = $this->uploadDir . $filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Erreur lors de l\'enregistrement du fichier');
        }

        // Déterminer le type
        $mimeType = mime_content_type($filepath);
        $type = $this->getMediaType($mimeType);

        // Obtenir les dimensions si c'est une image
        $width = null;
        $height = null;

        if ($type === 'image' && function_exists('getimagesize')) {
            $imageInfo = getimagesize($filepath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }

        // Créer l'URL
        $url = '/assets/uploads/' . $filename;

        // Insérer dans la BDD
        $sql = "INSERT INTO {$this->table} (
            filename, original_filename, path, url, type, mime_type,
            size, width, height, alt_text, title, description, uploaded_by
        ) VALUES (
            :filename, :original_filename, :path, :url, :type, :mime_type,
            :size, :width, :height, :alt_text, :title, :description, :uploaded_by
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':filename', $filename);
        $stmt->bindValue(':original_filename', $file['name']);
        $stmt->bindValue(':path', $filepath);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':mime_type', $mimeType);
        $stmt->bindValue(':size', $file['size'], PDO::PARAM_INT);
        $stmt->bindValue(':width', $width, PDO::PARAM_INT);
        $stmt->bindValue(':height', $height, PDO::PARAM_INT);
        $stmt->bindValue(':alt_text', $data['alt_text'] ?? null);
        $stmt->bindValue(':title', $data['title'] ?? null);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':uploaded_by', $data['uploaded_by'] ?? null, PDO::PARAM_INT);

        $stmt->execute();
        $id = $this->db->lastInsertId();

        return $this->getById($id);
    }

    /**
     * Met à jour un média (métadonnées uniquement)
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['alt_text', 'title', 'description'];

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
     * Supprime un média
     */
    public function delete($id) {
        $media = $this->getById($id);

        if (!$media) {
            return false;
        }

        // Supprimer le fichier physique
        if (file_exists($media['path'])) {
            unlink($media['path']);
        }

        // Supprimer de la BDD
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compte le nombre de médias
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
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
     * Détermine le type de média en fonction du MIME type
     */
    private function getMediaType($mimeType) {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.ms-excel'])) {
            return 'document';
        }

        return 'other';
    }

    /**
     * Crée des miniatures pour une image
     */
    public function createThumbnail($id, $width = 300, $height = 300) {
        $media = $this->getById($id);

        if (!$media || $media['type'] !== 'image') {
            return false;
        }

        $sourcePath = $media['path'];
        $thumbFilename = 'thumb_' . $media['filename'];
        $thumbPath = $this->uploadDir . $thumbFilename;

        // Utiliser GD pour créer la miniature
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];

        // Créer l'image source
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        // Calculer les nouvelles dimensions
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $ratio = min($width / $srcWidth, $height / $srcHeight);
        $newWidth = (int)($srcWidth * $ratio);
        $newHeight = (int)($srcHeight * $ratio);

        // Créer la miniature
        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG
        if ($mimeType === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        // Sauvegarder la miniature
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumb, $thumbPath, 90);
                break;
            case 'image/png':
                imagepng($thumb, $thumbPath);
                break;
            case 'image/gif':
                imagegif($thumb, $thumbPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return '/assets/uploads/' . $thumbFilename;
    }
}
