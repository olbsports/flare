<?php
/**
 * FLARE CUSTOM - Product Config API
 * API pour la configuration du configurateur de produits
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$configModel = new ProductConfig();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['product_id'])) {
                // Récupérer la config d'un produit
                $config = $configModel->getByProductId($_GET['product_id']);

                if ($config) {
                    $response = [
                        'success' => true,
                        'config' => $config
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Configuration non trouvée'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['calculate_price'])) {
                // Calculer un prix
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
            } elseif (isset($_GET['generate_default'])) {
                // Générer une config par défaut
                $productId = $_GET['product_id'] ?? null;

                if (!$productId) {
                    $response = ['success' => false, 'error' => 'product_id requis'];
                    http_response_code(400);
                    break;
                }

                $id = $configModel->generateDefault($productId);

                $response = [
                    'success' => true,
                    'message' => 'Configuration par défaut créée',
                    'id' => $id,
                    'config' => $configModel->getByProductId($productId)
                ];
            } else {
                // Toutes les configs
                $configs = $configModel->getAll();

                $response = [
                    'success' => true,
                    'configs' => $configs
                ];
            }
            break;

        case 'POST':
            // Créer une config
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || empty($data['product_id'])) {
                $response = ['success' => false, 'error' => 'product_id requis'];
                http_response_code(400);
                break;
            }

            $id = $configModel->create($data);

            $response = [
                'success' => true,
                'message' => 'Configuration créée',
                'id' => $id,
                'config' => $configModel->getByProductId($data['product_id'])
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour une config
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = ['success' => false, 'error' => 'ID requis'];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $configModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Configuration mise à jour'
            ];
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                $response = ['success' => false, 'error' => 'ID requis'];
                http_response_code(400);
                break;
            }

            $configModel->delete($_GET['id']);

            $response = [
                'success' => true,
                'message' => 'Configuration supprimée'
            ];
            break;

        default:
            $response = ['success' => false, 'error' => 'Méthode non supportée'];
            http_response_code(405);
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
