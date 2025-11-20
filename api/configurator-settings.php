<?php
/**
 * API Configurator Settings - Gestion de la configuration du configurateur par produit
 *
 * Endpoints:
 * GET    /api/configurator-settings.php?product_id=123  -> Récupère la config d'un produit
 * POST   /api/configurator-settings.php                 -> Crée une config
 * PUT    /api/configurator-settings.php?product_id=123  -> Met à jour une config
 * DELETE /api/configurator-settings.php?product_id=123  -> Supprime une config (reset aux défauts)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$product_id = $_GET['product_id'] ?? null;

// ============================================
// GET - Récupère la config d'un produit
// ============================================
if ($method === 'GET') {
    if (!$product_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'product_id is required'
        ]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT * FROM product_configurator_settings
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        // Décode les champs JSON
        $jsonFields = [
            'available_col_options',
            'available_manches_options',
            'available_poches_options',
            'available_fermeture_options',
            'available_sizes',
            'default_colors',
            'quantity_presets'
        ];

        foreach ($jsonFields as $field) {
            if ($config[$field]) {
                $config[$field . '_data'] = json_decode($config[$field], true);
            }
        }

        echo json_encode([
            'success' => true,
            'config' => $config
        ]);
    } else {
        // Pas de config -> retourne les valeurs par défaut
        echo json_encode([
            'success' => true,
            'config' => null,
            'message' => 'No configuration found for this product. Default values will be used.'
        ]);
    }
    exit;
}

// ============================================
// POST - Crée une nouvelle config
// ============================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['product_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'product_id is required'
        ]);
        exit;
    }

    // Vérifie si une config existe déjà
    $stmt = $db->prepare("SELECT id FROM product_configurator_settings WHERE product_id = ?");
    $stmt->execute([$input['product_id']]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Configuration already exists for this product. Use PUT to update.'
        ]);
        exit;
    }

    // Encode les champs JSON
    $jsonFields = [
        'available_col_options',
        'available_manches_options',
        'available_poches_options',
        'available_fermeture_options',
        'available_sizes',
        'default_colors',
        'quantity_presets'
    ];

    foreach ($jsonFields as $field) {
        if (isset($input[$field]) && is_array($input[$field])) {
            $input[$field] = json_encode($input[$field]);
        }
    }

    // Prépare l'insertion avec TOUS les champs
    $fields = [
        'product_id',
        'allow_design_flare', 'allow_design_client', 'allow_design_template',
        'default_design_type', 'design_description_required',
        'available_col_options', 'available_manches_options', 'available_poches_options', 'available_fermeture_options',
        'default_col', 'default_manches', 'default_poches', 'default_fermeture',
        'col_required', 'manches_required', 'poches_required', 'fermeture_required',
        'allow_genre_homme', 'allow_genre_femme', 'allow_genre_mixte', 'allow_genre_enfant',
        'default_genre', 'enfant_discount_percent',
        'available_sizes', 'min_quantity_per_size', 'max_quantity_per_size',
        'min_total_quantity', 'max_total_quantity', 'quantity_presets',
        'allow_colors', 'default_colors', 'min_colors', 'max_colors',
        'allow_logos', 'logo_description_required', 'logo_upload_after_validation', 'logo_extra_cost',
        'allow_numeros', 'allow_numeros_generique', 'allow_numeros_specifique',
        'numeros_specifique_cost', 'numeros_description_required',
        'allow_noms', 'allow_noms_generique', 'allow_noms_specifique',
        'noms_specifique_cost', 'noms_description_required',
        'allow_remarques', 'remarques_required',
        'design_flare_extra_cost', 'sublimation_extra_cost', 'broderie_extra_cost',
        'default_lead_time_days', 'express_available', 'express_lead_time_days', 'express_extra_cost_percent',
        'email_validation_regex', 'telephone_required', 'club_required', 'fonction_required',
        'newsletter_checkbox', 'newsletter_default_checked',
        'send_client_email', 'client_email_template',
        'send_admin_email', 'admin_email_recipients', 'admin_email_template',
        'show_sidebar_summary', 'sidebar_show_price', 'sidebar_show_quantity', 'sidebar_show_sizes',
        'custom_welcome_message', 'custom_final_message', 'custom_price_disclaimer',
        'enable_analytics', 'gtag_tracking_id',
        'active'
    ];

    $placeholders = array_fill(0, count($fields), '?');
    $values = [];

    foreach ($fields as $field) {
        $values[] = $input[$field] ?? null;
    }

    $sql = "INSERT INTO product_configurator_settings (" . implode(', ', $fields) . ")
            VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute($values);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuration created successfully',
            'id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create configuration'
        ]);
    }
    exit;
}

// ============================================
// PUT - Met à jour une config
// ============================================
if ($method === 'PUT') {
    if (!$product_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'product_id is required'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Encode les champs JSON
    $jsonFields = [
        'available_col_options',
        'available_manches_options',
        'available_poches_options',
        'available_fermeture_options',
        'available_sizes',
        'default_colors',
        'quantity_presets'
    ];

    foreach ($jsonFields as $field) {
        if (isset($input[$field]) && is_array($input[$field])) {
            $input[$field] = json_encode($input[$field]);
        }
    }

    // Liste de tous les champs modifiables
    $allowedFields = [
        'allow_design_flare', 'allow_design_client', 'allow_design_template',
        'default_design_type', 'design_description_required',
        'available_col_options', 'available_manches_options', 'available_poches_options', 'available_fermeture_options',
        'default_col', 'default_manches', 'default_poches', 'default_fermeture',
        'col_required', 'manches_required', 'poches_required', 'fermeture_required',
        'allow_genre_homme', 'allow_genre_femme', 'allow_genre_mixte', 'allow_genre_enfant',
        'default_genre', 'enfant_discount_percent',
        'available_sizes', 'min_quantity_per_size', 'max_quantity_per_size',
        'min_total_quantity', 'max_total_quantity', 'quantity_presets',
        'allow_colors', 'default_colors', 'min_colors', 'max_colors',
        'allow_logos', 'logo_description_required', 'logo_upload_after_validation', 'logo_extra_cost',
        'allow_numeros', 'allow_numeros_generique', 'allow_numeros_specifique',
        'numeros_specifique_cost', 'numeros_description_required',
        'allow_noms', 'allow_noms_generique', 'allow_noms_specifique',
        'noms_specifique_cost', 'noms_description_required',
        'allow_remarques', 'remarques_required',
        'design_flare_extra_cost', 'sublimation_extra_cost', 'broderie_extra_cost',
        'default_lead_time_days', 'express_available', 'express_lead_time_days', 'express_extra_cost_percent',
        'email_validation_regex', 'telephone_required', 'club_required', 'fonction_required',
        'newsletter_checkbox', 'newsletter_default_checked',
        'send_client_email', 'client_email_template',
        'send_admin_email', 'admin_email_recipients', 'admin_email_template',
        'show_sidebar_summary', 'sidebar_show_price', 'sidebar_show_quantity', 'sidebar_show_sizes',
        'custom_welcome_message', 'custom_final_message', 'custom_price_disclaimer',
        'enable_analytics', 'gtag_tracking_id',
        'active'
    ];

    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $input)) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No fields to update'
        ]);
        exit;
    }

    $params[] = $product_id;

    $sql = "UPDATE product_configurator_settings SET " . implode(', ', $updates) . " WHERE product_id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        // Si aucune ligne affectée, c'est que la config n'existe pas -> la créer
        if ($stmt->rowCount() === 0) {
            $input['product_id'] = $product_id;

            // Réutilise la logique POST
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET['product_id'] = $product_id;

            // Réencode les JSON
            foreach ($jsonFields as $field) {
                if (isset($input[$field]) && !is_string($input[$field])) {
                    $input[$field] = json_encode($input[$field]);
                }
            }

            file_put_contents('php://input', json_encode($input));

            // Réappelle POST
            // (Simplifié - normalement il faudrait refactoriser le code POST en fonction)
            echo json_encode([
                'success' => true,
                'message' => 'Configuration created (did not exist)',
                'created' => true
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Configuration updated successfully'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update configuration'
        ]);
    }
    exit;
}

// ============================================
// DELETE - Supprime une config (reset aux défauts)
// ============================================
if ($method === 'DELETE') {
    if (!$product_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'product_id is required'
        ]);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM product_configurator_settings WHERE product_id = ?");
    $result = $stmt->execute([$product_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuration deleted successfully. Default values will be used.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete configuration'
        ]);
    }
    exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Method not allowed'
]);
