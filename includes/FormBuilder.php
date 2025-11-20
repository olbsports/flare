<?php
/**
 * FLARE CUSTOM - FormBuilder Class
 * Création et gestion de formulaires personnalisés
 */

require_once __DIR__ . '/../config/database.php';

class FormBuilder {
    private $db;
    private $formsTable = 'form_builders';
    private $submissionsTable = 'form_submissions';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les formulaires
     */
    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->formsTable} WHERE active = 1";
        $params = [];

        if (!empty($filters['form_type'])) {
            $sql .= " AND form_type = :form_type";
            $params[':form_type'] = $filters['form_type'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $forms = $stmt->fetchAll();

        foreach ($forms as &$form) {
            $form = $this->decodeJsonFields($form);
        }

        return $forms;
    }

    /**
     * Récupère un formulaire
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->formsTable} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $form = $stmt->fetch();

        if ($form) {
            $form = $this->decodeJsonFields($form);
        }

        return $form;
    }

    /**
     * Crée un formulaire
     */
    public function create($data) {
        $fields = isset($data['fields']) && is_array($data['fields'])
            ? json_encode($data['fields'])
            : $data['fields'];

        $settings = isset($data['settings']) && is_array($data['settings'])
            ? json_encode($data['settings'])
            : null;

        $validationRules = isset($data['validation_rules']) && is_array($data['validation_rules'])
            ? json_encode($data['validation_rules'])
            : null;

        $actions = isset($data['actions']) && is_array($data['actions'])
            ? json_encode($data['actions'])
            : null;

        $sql = "INSERT INTO {$this->formsTable} (
            name, description, form_type, fields, settings, validation_rules, actions, active
        ) VALUES (
            :name, :description, :form_type, :fields, :settings, :validation_rules, :actions, :active
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':form_type', $data['form_type'] ?? 'custom');
        $stmt->bindValue(':fields', $fields);
        $stmt->bindValue(':settings', $settings);
        $stmt->bindValue(':validation_rules', $validationRules);
        $stmt->bindValue(':actions', $actions);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Soumet un formulaire
     */
    public function submit($formId, $data, $meta = []) {
        $form = $this->getById($formId);

        if (!$form) {
            throw new Exception('Formulaire introuvable');
        }

        // Valider les données
        if (!$this->validate($form, $data)) {
            throw new Exception('Données invalides');
        }

        // Enregistrer la soumission
        $submissionData = is_array($data) ? json_encode($data) : $data;

        $sql = "INSERT INTO {$this->submissionsTable} (
            form_id, data, ip_address, user_agent, referrer, status
        ) VALUES (
            :form_id, :data, :ip_address, :user_agent, :referrer, 'pending'
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':form_id', $formId, PDO::PARAM_INT);
        $stmt->bindValue(':data', $submissionData);
        $stmt->bindValue(':ip_address', $meta['ip'] ?? null);
        $stmt->bindValue(':user_agent', $meta['user_agent'] ?? null);
        $stmt->bindValue(':referrer', $meta['referrer'] ?? null);

        $stmt->execute();
        $submissionId = $this->db->lastInsertId();

        // Incrémenter le compteur
        $sql = "UPDATE {$this->formsTable} SET submission_count = submission_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $formId]);

        // Exécuter les actions
        $this->executeActions($form, $data, $submissionId);

        return $submissionId;
    }

    /**
     * Valide les données du formulaire
     */
    private function validate($form, $data) {
        $validationRules = $form['validation_rules'] ?? [];

        foreach ($form['fields'] as $field) {
            $name = $field['name'];
            $value = $data[$name] ?? null;

            // Champ requis
            if (!empty($field['required']) && empty($value)) {
                return false;
            }

            // Type de validation
            if (isset($validationRules[$name])) {
                $rule = $validationRules[$name];

                if ($rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }

                if ($rule['type'] === 'number' && !is_numeric($value)) {
                    return false;
                }

                if ($rule['type'] === 'min' && strlen($value) < $rule['value']) {
                    return false;
                }

                if ($rule['type'] === 'max' && strlen($value) > $rule['value']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Exécute les actions post-soumission
     */
    private function executeActions($form, $data, $submissionId) {
        $actions = $form['actions'] ?? [];

        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'email':
                    // TODO: Envoyer un email
                    break;

                case 'webhook':
                    // TODO: Appeler un webhook
                    break;

                case 'redirect':
                    // Le redirect sera géré côté frontend
                    break;
            }
        }
    }

    /**
     * Décode les champs JSON
     */
    private function decodeJsonFields($form) {
        $jsonFields = ['fields', 'settings', 'validation_rules', 'actions'];

        foreach ($jsonFields as $field) {
            if (isset($form[$field]) && $form[$field]) {
                $form[$field] = json_decode($form[$field], true);
            }
        }

        return $form;
    }
}
