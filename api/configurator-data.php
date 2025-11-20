<?php
/**
 * FLARE CUSTOM - Configurator Data API
 * API qui alimente le configurateur JS des fiches produits
 * Remplace le chargement depuis le CSV
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';
require_once __DIR__ . '/../includes/ProductConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$productModel = new Product();
$configModel = new ProductConfig();
$response = ['success' => false];

try {
    $action = $_GET['action'] ?? 'product';

    switch ($action) {
        case 'product':
            // Récupérer toutes les données d'un produit pour le configurateur
            if (empty($_GET['reference']) && empty($_GET['id'])) {
                $response = ['success' => false, 'error' => 'reference ou id requis'];
                http_response_code(400);
                break;
            }

            // Récupérer le produit
            $product = null;
            if (!empty($_GET['reference'])) {
                $product = $productModel->getByReference($_GET['reference']);
            } else {
                $product = $productModel->getById($_GET['id']);
            }

            if (!$product) {
                $response = ['success' => false, 'error' => 'Produit non trouvé'];
                http_response_code(404);
                break;
            }

            // Récupérer la config du configurateur
            $config = $configModel->getByProductId($product['id']);

            // Si pas de config, générer une par défaut
            if (!$config) {
                $configModel->generateDefault($product['id']);
                $config = $configModel->getByProductId($product['id']);
            }

            // Construire les données pour le configurateur JS
            $configuratorData = [
                'produit' => [
                    'id' => $product['id'],
                    'reference' => $product['reference'],
                    'nom' => $product['nom'],
                    'sport' => $product['sport'],
                    'famille' => $product['famille'],
                    'photo' => $product['photo_1'],
                    'tissu' => $product['tissu'],
                    'grammage' => $product['grammage']
                ],
                'prix' => [
                    'qty_1' => $product['prix_1'],
                    'qty_5' => $product['prix_5'],
                    'qty_10' => $product['prix_10'],
                    'qty_20' => $product['prix_20'],
                    'qty_50' => $product['prix_50'],
                    'qty_100' => $product['prix_100'],
                    'qty_250' => $product['prix_250'],
                    'qty_500' => $product['prix_500']
                ],
                'config' => [
                    'allow_colors' => $config['allow_colors'] ?? true,
                    'colors' => $config['colors'] ?? ['#FFFFFF', '#000000', '#FF0000', '#0000FF'],
                    'allow_logos' => $config['allow_logos'] ?? true,
                    'max_logos' => $config['max_logos'] ?? 3,
                    'logo_positions' => $config['logo_positions'] ?? [],
                    'allow_text' => $config['allow_text'] ?? true,
                    'text_positions' => $config['text_positions'] ?? [],
                    'allow_numbers' => $config['allow_numbers'] ?? true,
                    'number_positions' => $config['number_positions'] ?? [],
                    'available_sizes' => $config['available_sizes'] ?? ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
                    'custom_options' => $config['custom_options'] ?? [],
                    'customization_zones' => $config['customization_zones'] ?? [],
                    'price_rules' => $config['price_rules'] ?? [
                        'logo_extra' => 5.00,
                        'text_extra' => 2.00,
                        'number_extra' => 3.00
                    ],
                    'min_quantity' => $config['min_quantity'] ?? 1,
                    'max_quantity' => $config['max_quantity'] ?? 1000,
                    'lead_time_days' => $config['lead_time_days'] ?? 21
                ]
            ];

            $response = [
                'success' => true,
                'data' => $configuratorData
            ];
            break;

        case 'all-pricing':
            // Retourne tous les prix (pour remplacer le CSV)
            $products = $productModel->getAll(['limit' => 10000]);

            $pricingData = [];
            foreach ($products as $product) {
                $pricingData[$product['reference']] = [
                    'qty_1' => $product['prix_1'],
                    'qty_5' => $product['prix_5'],
                    'qty_10' => $product['prix_10'],
                    'qty_20' => $product['prix_20'],
                    'qty_50' => $product['prix_50'],
                    'qty_100' => $product['prix_100'],
                    'qty_250' => $product['prix_250'],
                    'qty_500' => $product['prix_500']
                ];
            }

            $response = [
                'success' => true,
                'pricing' => $pricingData
            ];
            break;

        case 'calculate':
            // Calcule un prix avec options
            $productId = $_GET['product_id'] ?? null;
            $quantity = $_GET['quantity'] ?? 1;
            $options = isset($_GET['options']) ? json_decode($_GET['options'], true) : [];

            if (!$productId) {
                $response = ['success' => false, 'error' => 'product_id requis'];
                http_response_code(400);
                break;
            }

            $price = $configModel->calculatePrice($productId, $quantity, $options);

            $response = [
                'success' => true,
                'price' => $price
            ];
            break;

        default:
            $response = ['success' => false, 'error' => 'Action inconnue'];
            http_response_code(400);
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
