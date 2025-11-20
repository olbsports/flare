<?php
/**
 * FLARE CUSTOM - ProductConfig Class
 * Gestion des configurations du configurateur par produit
 */

require_once __DIR__ . '/../config/database.php';

class ProductConfig {
    private $db;
    private $table = 'product_configurations';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère la configuration d'un produit
     */
    public function getByProductId($productId) {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = :product_id AND active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $config = $stmt->fetch();

        if ($config) {
            $config = $this->decodeJsonFields($config);
        }

        return $config;
    }

    /**
     * Crée une configuration pour un produit
     */
    public function create($data) {
        // Encoder les champs JSON
        $colors = isset($data['colors']) && is_array($data['colors'])
            ? json_encode($data['colors'])
            : null;

        $logoPositions = isset($data['logo_positions']) && is_array($data['logo_positions'])
            ? json_encode($data['logo_positions'])
            : null;

        $textPositions = isset($data['text_positions']) && is_array($data['text_positions'])
            ? json_encode($data['text_positions'])
            : null;

        $numberPositions = isset($data['number_positions']) && is_array($data['number_positions'])
            ? json_encode($data['number_positions'])
            : null;

        $availableSizes = isset($data['available_sizes']) && is_array($data['available_sizes'])
            ? json_encode($data['available_sizes'])
            : json_encode(['S', 'M', 'L', 'XL', 'XXL']);

        $sizeChart = isset($data['size_chart']) && is_array($data['size_chart'])
            ? json_encode($data['size_chart'])
            : null;

        $customOptions = isset($data['custom_options']) && is_array($data['custom_options'])
            ? json_encode($data['custom_options'])
            : null;

        $designTemplates = isset($data['design_templates']) && is_array($data['design_templates'])
            ? json_encode($data['design_templates'])
            : null;

        $customizationZones = isset($data['customization_zones']) && is_array($data['customization_zones'])
            ? json_encode($data['customization_zones'])
            : null;

        $priceRules = isset($data['price_rules']) && is_array($data['price_rules'])
            ? json_encode($data['price_rules'])
            : null;

        $formFields = isset($data['form_fields']) && is_array($data['form_fields'])
            ? json_encode($data['form_fields'])
            : null;

        $sql = "INSERT INTO {$this->table} (
            product_id,
            allow_colors, colors,
            allow_logos, max_logos, logo_positions,
            allow_text, text_positions,
            allow_numbers, number_positions,
            available_sizes, size_chart,
            custom_options,
            design_templates, default_template_id,
            customization_zones,
            price_rules,
            min_quantity, max_quantity, lead_time_days,
            form_fields,
            active
        ) VALUES (
            :product_id,
            :allow_colors, :colors,
            :allow_logos, :max_logos, :logo_positions,
            :allow_text, :text_positions,
            :allow_numbers, :number_positions,
            :available_sizes, :size_chart,
            :custom_options,
            :design_templates, :default_template_id,
            :customization_zones,
            :price_rules,
            :min_quantity, :max_quantity, :lead_time_days,
            :form_fields,
            :active
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue(':allow_colors', $data['allow_colors'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':colors', $colors);
        $stmt->bindValue(':allow_logos', $data['allow_logos'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':max_logos', $data['max_logos'] ?? 3, PDO::PARAM_INT);
        $stmt->bindValue(':logo_positions', $logoPositions);
        $stmt->bindValue(':allow_text', $data['allow_text'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':text_positions', $textPositions);
        $stmt->bindValue(':allow_numbers', $data['allow_numbers'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':number_positions', $numberPositions);
        $stmt->bindValue(':available_sizes', $availableSizes);
        $stmt->bindValue(':size_chart', $sizeChart);
        $stmt->bindValue(':custom_options', $customOptions);
        $stmt->bindValue(':design_templates', $designTemplates);
        $stmt->bindValue(':default_template_id', $data['default_template_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':customization_zones', $customizationZones);
        $stmt->bindValue(':price_rules', $priceRules);
        $stmt->bindValue(':min_quantity', $data['min_quantity'] ?? 1, PDO::PARAM_INT);
        $stmt->bindValue(':max_quantity', $data['max_quantity'] ?? 1000, PDO::PARAM_INT);
        $stmt->bindValue(':lead_time_days', $data['lead_time_days'] ?? 21, PDO::PARAM_INT);
        $stmt->bindValue(':form_fields', $formFields);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour une configuration
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'allow_colors', 'allow_logos', 'max_logos', 'allow_text',
            'allow_numbers', 'default_template_id', 'min_quantity',
            'max_quantity', 'lead_time_days', 'active'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        // Gérer les champs JSON
        $jsonFields = [
            'colors', 'logo_positions', 'text_positions', 'number_positions',
            'available_sizes', 'size_chart', 'custom_options', 'design_templates',
            'customization_zones', 'price_rules', 'form_fields'
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
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
     * Supprime une configuration
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Récupère toutes les configurations
     */
    public function getAll($filters = []) {
        $sql = "SELECT pc.*, p.nom as product_name, p.reference as product_reference
                FROM {$this->table} pc
                LEFT JOIN products p ON pc.product_id = p.id
                WHERE pc.active = 1";
        $params = [];

        if (!empty($filters['product_id'])) {
            $sql .= " AND pc.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }

        $sql .= " ORDER BY pc.created_at DESC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $configs = $stmt->fetchAll();

        foreach ($configs as &$config) {
            $config = $this->decodeJsonFields($config);
        }

        return $configs;
    }

    /**
     * Génère une configuration par défaut pour un produit
     */
    public function generateDefault($productId) {
        $defaultConfig = [
            'product_id' => $productId,
            'allow_colors' => true,
            'colors' => ['#FFFFFF', '#000000', '#FF0000', '#0000FF', '#FFFF00', '#00FF00'],
            'allow_logos' => true,
            'max_logos' => 3,
            'logo_positions' => [
                ['name' => 'Poitrine gauche', 'x' => 20, 'y' => 30, 'maxWidth' => 10, 'maxHeight' => 10],
                ['name' => 'Poitrine centre', 'x' => 50, 'y' => 30, 'maxWidth' => 15, 'maxHeight' => 15],
                ['name' => 'Dos centre', 'x' => 50, 'y' => 40, 'maxWidth' => 30, 'maxHeight' => 30]
            ],
            'allow_text' => true,
            'text_positions' => [
                ['name' => 'Nom dos', 'x' => 50, 'y' => 70, 'maxChars' => 20],
                ['name' => 'Nom poitrine', 'x' => 50, 'y' => 50, 'maxChars' => 15]
            ],
            'allow_numbers' => true,
            'number_positions' => [
                ['name' => 'Numéro dos', 'x' => 50, 'y' => 50, 'size' => 'large'],
                ['name' => 'Numéro short', 'x' => 80, 'y' => 30, 'size' => 'small']
            ],
            'available_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
            'custom_options' => [
                ['name' => 'Type de col', 'values' => ['Col V', 'Col rond', 'Col polo']],
                ['name' => 'Longueur des manches', 'values' => ['Manches courtes', 'Manches longues']]
            ],
            'customization_zones' => [
                ['zone' => 'front', 'sublimation' => true, 'colors' => true],
                ['zone' => 'back', 'sublimation' => true, 'colors' => true],
                ['zone' => 'sleeves', 'sublimation' => false, 'colors' => true]
            ],
            'price_rules' => [
                'logo_extra' => 5.00,
                'text_extra' => 2.00,
                'number_extra' => 3.00,
                'sublimation_extra' => 10.00
            ],
            'min_quantity' => 10,
            'max_quantity' => 1000,
            'lead_time_days' => 21
        ];

        return $this->create($defaultConfig);
    }

    /**
     * Décode les champs JSON
     */
    private function decodeJsonFields($config) {
        $jsonFields = [
            'colors', 'logo_positions', 'text_positions', 'number_positions',
            'available_sizes', 'size_chart', 'custom_options', 'design_templates',
            'customization_zones', 'price_rules', 'form_fields'
        ];

        foreach ($jsonFields as $field) {
            if (isset($config[$field]) && $config[$field]) {
                $config[$field] = json_decode($config[$field], true);
            }
        }

        return $config;
    }

    /**
     * Calcule le prix total avec les options
     */
    public function calculatePrice($productId, $quantity, $options = []) {
        $config = $this->getByProductId($productId);

        if (!$config) {
            return null;
        }

        // Récupérer le produit
        require_once __DIR__ . '/Product.php';
        $productModel = new Product();
        $product = $productModel->getById($config['product_id']);

        if (!$product) {
            return null;
        }

        // Prix de base selon la quantité
        $basePrice = $this->getBasePriceForQuantity($product, $quantity);

        // Calculer les extras
        $extras = 0;
        $priceRules = $config['price_rules'] ?? [];

        if (!empty($options['logos'])) {
            $extras += count($options['logos']) * ($priceRules['logo_extra'] ?? 0);
        }

        if (!empty($options['texts'])) {
            $extras += count($options['texts']) * ($priceRules['text_extra'] ?? 0);
        }

        if (!empty($options['numbers'])) {
            $extras += count($options['numbers']) * ($priceRules['number_extra'] ?? 0);
        }

        if (!empty($options['sublimation'])) {
            $extras += $priceRules['sublimation_extra'] ?? 0;
        }

        $unitPrice = $basePrice + $extras;
        $totalPrice = $unitPrice * $quantity;

        return [
            'base_price' => $basePrice,
            'extras' => $extras,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $totalPrice
        ];
    }

    /**
     * Récupère le prix de base selon la quantité
     */
    private function getBasePriceForQuantity($product, $quantity) {
        if ($quantity >= 500 && $product['prix_500']) return $product['prix_500'];
        if ($quantity >= 250 && $product['prix_250']) return $product['prix_250'];
        if ($quantity >= 100 && $product['prix_100']) return $product['prix_100'];
        if ($quantity >= 50 && $product['prix_50']) return $product['prix_50'];
        if ($quantity >= 20 && $product['prix_20']) return $product['prix_20'];
        if ($quantity >= 10 && $product['prix_10']) return $product['prix_10'];
        if ($quantity >= 5 && $product['prix_5']) return $product['prix_5'];

        return $product['prix_1'] ?? 0;
    }
}
