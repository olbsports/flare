<?php
/**
 * FLARE CUSTOM - Products API
 * API REST pour la gestion des produits
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$productModel = new Product();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de produits
            if (isset($_GET['id'])) {
                // Récupérer un produit par ID
                $product = $productModel->getById($_GET['id']);
                if ($product) {
                    $response = [
                        'success' => true,
                        'product' => $product
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Produit non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['reference'])) {
                // Récupérer un produit par référence
                $product = $productModel->getByReference($_GET['reference']);
                if ($product) {
                    $response = [
                        'success' => true,
                        'product' => $product
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Produit non trouvé'
                    ];
                    http_response_code(404);
                }
            } else {
                // Récupérer tous les produits avec filtres
                $filters = [
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 20,
                    'search' => $_GET['search'] ?? '',
                    'sport' => $_GET['sport'] ?? '',
                    'famille' => $_GET['famille'] ?? ''
                ];

                $products = $productModel->getAll($filters);
                $total = $productModel->count($filters);

                $response = [
                    'success' => true,
                    'products' => $products,
                    'pagination' => [
                        'page' => (int)$filters['page'],
                        'limit' => (int)$filters['limit'],
                        'total' => $total,
                        'pages' => ceil($total / $filters['limit'])
                    ]
                ];
            }
            break;

        case 'POST':
            // Créer un nouveau produit
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                // Essayer avec les données POST classiques
                $data = $_POST;
            }

            if (empty($data['reference']) || empty($data['nom'])) {
                $response = [
                    'success' => false,
                    'error' => 'Référence et nom sont obligatoires'
                ];
                http_response_code(400);
                break;
            }

            // Vérifier si la référence existe déjà
            $existing = $productModel->getByReference($data['reference']);
            if ($existing) {
                $response = [
                    'success' => false,
                    'error' => 'Un produit avec cette référence existe déjà'
                ];
                http_response_code(409);
                break;
            }

            $id = $productModel->create($data);

            $response = [
                'success' => true,
                'message' => 'Produit créé avec succès',
                'id' => $id,
                'product' => $productModel->getById($id)
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour un produit
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du produit requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $product = $productModel->getById($id);
            if (!$product) {
                $response = [
                    'success' => false,
                    'error' => 'Produit non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $productModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'product' => $productModel->getById($id)
            ];
            break;

        case 'DELETE':
            // Supprimer un produit (soft delete)
            if (!isset($_GET['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du produit requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'];
            $product = $productModel->getById($id);

            if (!$product) {
                $response = [
                    'success' => false,
                    'error' => 'Produit non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $productModel->delete($id);

            $response = [
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ];
            break;

        default:
            $response = [
                'success' => false,
                'error' => 'Méthode non supportée'
            ];
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
