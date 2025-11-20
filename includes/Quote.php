<?php
/**
 * FLARE CUSTOM - Quote Class
 * Gestion des devis
 */

require_once __DIR__ . '/../config/database.php';

class Quote {
    private $db;
    private $table = 'quotes';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les devis
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Filtres
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['client_email'])) {
            $sql .= " AND client_email = :client_email";
            $params[':client_email'] = $filters['client_email'];
        }

        if (!empty($filters['product_reference'])) {
            $sql .= " AND product_reference = :product_reference";
            $params[':product_reference'] = $filters['product_reference'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (reference LIKE :search OR client_nom LIKE :search OR client_email LIKE :search OR product_nom LIKE :search)";
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
        $quotes = $stmt->fetchAll();

        // Décoder les données JSON
        foreach ($quotes as &$quote) {
            $quote = $this->decodeJsonFields($quote);
        }

        return $quotes;
    }

    /**
     * Récupère un devis par ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $quote = $stmt->fetch();

        if ($quote) {
            $quote = $this->decodeJsonFields($quote);
        }

        return $quote;
    }

    /**
     * Récupère un devis par référence
     */
    public function getByReference($reference) {
        $sql = "SELECT * FROM {$this->table} WHERE reference = :reference LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reference', $reference);
        $stmt->execute();
        $quote = $stmt->fetch();

        if ($quote) {
            $quote = $this->decodeJsonFields($quote);
        }

        return $quote;
    }

    /**
     * Crée un nouveau devis
     */
    public function create($data) {
        // Générer une référence unique
        $reference = $data['reference'] ?? $this->generateReference();

        // Encoder les données JSON
        $options = isset($data['options']) && is_array($data['options'])
            ? json_encode($data['options'])
            : (is_string($data['options']) ? $data['options'] : null);

        $tailles = isset($data['tailles']) && is_array($data['tailles'])
            ? json_encode($data['tailles'])
            : (is_string($data['tailles']) ? $data['tailles'] : null);

        $personnalisation = isset($data['personnalisation']) && is_array($data['personnalisation'])
            ? json_encode($data['personnalisation'])
            : (is_string($data['personnalisation']) ? $data['personnalisation'] : null);

        $sql = "INSERT INTO {$this->table} (
            reference,
            client_prenom, client_nom, client_email, client_telephone, client_club, client_fonction,
            product_reference, product_nom, sport, famille,
            design_type, design_template_id, design_description,
            options, genre, tailles, personnalisation,
            total_pieces, prix_unitaire, prix_total,
            status, notes
        ) VALUES (
            :reference,
            :client_prenom, :client_nom, :client_email, :client_telephone, :client_club, :client_fonction,
            :product_reference, :product_nom, :sport, :famille,
            :design_type, :design_template_id, :design_description,
            :options, :genre, :tailles, :personnalisation,
            :total_pieces, :prix_unitaire, :prix_total,
            :status, :notes
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':reference', $reference);
        $stmt->bindValue(':client_prenom', $data['client_prenom'] ?? null);
        $stmt->bindValue(':client_nom', $data['client_nom']);
        $stmt->bindValue(':client_email', $data['client_email']);
        $stmt->bindValue(':client_telephone', $data['client_telephone'] ?? null);
        $stmt->bindValue(':client_club', $data['client_club'] ?? null);
        $stmt->bindValue(':client_fonction', $data['client_fonction'] ?? null);
        $stmt->bindValue(':product_reference', $data['product_reference']);
        $stmt->bindValue(':product_nom', $data['product_nom'] ?? null);
        $stmt->bindValue(':sport', $data['sport'] ?? null);
        $stmt->bindValue(':famille', $data['famille'] ?? null);
        $stmt->bindValue(':design_type', $data['design_type'] ?? null);
        $stmt->bindValue(':design_template_id', $data['design_template_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':design_description', $data['design_description'] ?? null);
        $stmt->bindValue(':options', $options);
        $stmt->bindValue(':genre', $data['genre'] ?? null);
        $stmt->bindValue(':tailles', $tailles);
        $stmt->bindValue(':personnalisation', $personnalisation);
        $stmt->bindValue(':total_pieces', $data['total_pieces'], PDO::PARAM_INT);
        $stmt->bindValue(':prix_unitaire', $data['prix_unitaire'] ?? null);
        $stmt->bindValue(':prix_total', $data['prix_total'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'pending');
        $stmt->bindValue(':notes', $data['notes'] ?? null);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un devis
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'status', 'notes', 'prix_unitaire', 'prix_total',
            'design_type', 'design_template_id', 'design_description'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        // Gérer les champs JSON
        if (isset($data['options'])) {
            $fields[] = "options = :options";
            $params[':options'] = is_array($data['options']) ? json_encode($data['options']) : $data['options'];
        }

        if (isset($data['tailles'])) {
            $fields[] = "tailles = :tailles";
            $params[':tailles'] = is_array($data['tailles']) ? json_encode($data['tailles']) : $data['tailles'];
        }

        if (isset($data['personnalisation'])) {
            $fields[] = "personnalisation = :personnalisation";
            $params[':personnalisation'] = is_array($data['personnalisation']) ? json_encode($data['personnalisation']) : $data['personnalisation'];
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
     * Supprime un devis
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compte le nombre de devis
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
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
     * Génère une référence unique pour un devis
     */
    private function generateReference() {
        $reference = 'DEV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Vérifier l'unicité
        $counter = 1;
        $baseReference = $reference;

        while ($this->getByReference($reference)) {
            $reference = $baseReference . '-' . $counter;
            $counter++;
        }

        return $reference;
    }

    /**
     * Décode les champs JSON
     */
    private function decodeJsonFields($quote) {
        if (isset($quote['options'])) {
            $quote['options'] = $quote['options'] ? json_decode($quote['options'], true) : null;
        }

        if (isset($quote['tailles'])) {
            $quote['tailles'] = $quote['tailles'] ? json_decode($quote['tailles'], true) : null;
        }

        if (isset($quote['personnalisation'])) {
            $quote['personnalisation'] = $quote['personnalisation'] ? json_decode($quote['personnalisation'], true) : null;
        }

        return $quote;
    }

    /**
     * Change le statut d'un devis
     */
    public function updateStatus($id, $status) {
        $allowedStatuses = ['pending', 'sent', 'accepted', 'rejected', 'completed'];

        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Statut invalide');
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Récupère les statistiques des devis
     */
    public function getStats() {
        $sql = "SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            SUM(prix_total) as total_revenue,
            AVG(prix_total) as average_revenue
        FROM {$this->table}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}
