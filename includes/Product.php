<?php
/**
 * FLARE CUSTOM - Product Class
 * Gestion des produits
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    private $table = 'products';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les produits
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE active = 1";
        $params = [];

        // Filtres
        if (!empty($filters['sport'])) {
            $sql .= " AND sport = :sport";
            $params[':sport'] = $filters['sport'];
        }

        if (!empty($filters['famille'])) {
            $sql .= " AND famille = :famille";
            $params[':famille'] = $filters['famille'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (nom LIKE :search OR description LIKE :search OR reference LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
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
     * Récupère un produit par référence
     */
    public function getByReference($reference) {
        $sql = "SELECT * FROM {$this->table} WHERE reference = :reference AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reference', $reference);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Récupère un produit par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Crée un nouveau produit
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (
            reference, nom, sport, famille, description, description_seo,
            tissu, grammage,
            prix_1, prix_5, prix_10, prix_20, prix_50, prix_100, prix_250, prix_500,
            photo_1, photo_2, photo_3, photo_4, photo_5,
            meta_title, meta_description, slug, url,
            genre, finition, etiquettes, active
        ) VALUES (
            :reference, :nom, :sport, :famille, :description, :description_seo,
            :tissu, :grammage,
            :prix_1, :prix_5, :prix_10, :prix_20, :prix_50, :prix_100, :prix_250, :prix_500,
            :photo_1, :photo_2, :photo_3, :photo_4, :photo_5,
            :meta_title, :meta_description, :slug, :url,
            :genre, :finition, :etiquettes, :active
        )";

        $stmt = $this->db->prepare($sql);

        // Bind des valeurs
        $stmt->bindValue(':reference', $data['reference']);
        $stmt->bindValue(':nom', $data['nom']);
        $stmt->bindValue(':sport', $data['sport'] ?? null);
        $stmt->bindValue(':famille', $data['famille'] ?? null);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':description_seo', $data['description_seo'] ?? null);
        $stmt->bindValue(':tissu', $data['tissu'] ?? null);
        $stmt->bindValue(':grammage', $data['grammage'] ?? null);

        // Prix
        $stmt->bindValue(':prix_1', $data['prix_1'] ?? null);
        $stmt->bindValue(':prix_5', $data['prix_5'] ?? null);
        $stmt->bindValue(':prix_10', $data['prix_10'] ?? null);
        $stmt->bindValue(':prix_20', $data['prix_20'] ?? null);
        $stmt->bindValue(':prix_50', $data['prix_50'] ?? null);
        $stmt->bindValue(':prix_100', $data['prix_100'] ?? null);
        $stmt->bindValue(':prix_250', $data['prix_250'] ?? null);
        $stmt->bindValue(':prix_500', $data['prix_500'] ?? null);

        // Photos
        $stmt->bindValue(':photo_1', $data['photo_1'] ?? null);
        $stmt->bindValue(':photo_2', $data['photo_2'] ?? null);
        $stmt->bindValue(':photo_3', $data['photo_3'] ?? null);
        $stmt->bindValue(':photo_4', $data['photo_4'] ?? null);
        $stmt->bindValue(':photo_5', $data['photo_5'] ?? null);

        // SEO
        $stmt->bindValue(':meta_title', $data['meta_title'] ?? null);
        $stmt->bindValue(':meta_description', $data['meta_description'] ?? null);
        $stmt->bindValue(':slug', $data['slug'] ?? $this->generateSlug($data['nom']));
        $stmt->bindValue(':url', $data['url'] ?? null);

        // Autres
        $stmt->bindValue(':genre', $data['genre'] ?? null);
        $stmt->bindValue(':finition', $data['finition'] ?? null);
        $stmt->bindValue(':etiquettes', $data['etiquettes'] ?? null);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un produit
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Supprime un produit (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compte le nombre de produits
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE active = 1";
        $params = [];

        if (!empty($filters['sport'])) {
            $sql .= " AND sport = :sport";
            $params[':sport'] = $filters['sport'];
        }

        if (!empty($filters['famille'])) {
            $sql .= " AND famille = :famille";
            $params[':famille'] = $filters['famille'];
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
     * Génère un slug unique
     */
    private function generateSlug($text) {
        $text = strtolower(trim($text));
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
     * Import depuis CSV
     */
    public function importFromCSV($csvPath) {
        if (!file_exists($csvPath)) {
            return ['success' => false, 'error' => 'Fichier CSV non trouvé'];
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle, 0, ';');

        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $data = array_combine($headers, $row);

            try {
                // Mapper les données du CSV
                $product = [
                    'reference' => $data['REFERENCE_FLARE'] ?? '',
                    'nom' => $data['TITRE_VENDEUR'] ?? '',
                    'sport' => $data['SPORT'] ?? null,
                    'famille' => $data['FAMILLE_PRODUIT'] ?? null,
                    'description' => $data['DESCRIPTION'] ?? null,
                    'tissu' => $data['TISSU'] ?? null,
                    'grammage' => $data['GRAMMAGE'] ?? null,
                    'prix_1' => $data['QTY_1'] ?? null,
                    'prix_5' => $data['QTY_5'] ?? null,
                    'prix_10' => $data['QTY_10'] ?? null,
                    'prix_20' => $data['QTY_20'] ?? null,
                    'prix_50' => $data['QTY_50'] ?? null,
                    'prix_100' => $data['QTY_100'] ?? null,
                    'prix_250' => $data['QTY_250'] ?? null,
                    'prix_500' => $data['QTY_500'] ?? null,
                    'photo_1' => $data['PHOTO_1'] ?? null,
                    'genre' => $data['GENRE'] ?? null,
                    'finition' => $data['FINITION'] ?? null,
                    'etiquettes' => $data['ETIQUETTES'] ?? null,
                ];

                // Vérifier si le produit existe déjà
                $existing = $this->getByReference($product['reference']);

                if ($existing) {
                    $this->update($existing['id'], $product);
                } else {
                    $this->create($product);
                }

                $imported++;
            } catch (Exception $e) {
                $errors[] = "Erreur ligne " . ($imported + 1) . ": " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}
