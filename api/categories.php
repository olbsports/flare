<?php
/**
 * FLARE CUSTOM - Categories API
 * API REST pour la gestion des catégories
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Category.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$categoryModel = new Category();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de catégories
            if (isset($_GET['id'])) {
                // Récupérer une catégorie par ID
                $category = $categoryModel->getById($_GET['id']);
                if ($category) {
                    $response = [
                        'success' => true,
                        'category' => $category
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Catégorie non trouvée'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['slug'])) {
                // Récupérer une catégorie par slug
                $category = $categoryModel->getBySlug($_GET['slug']);
                if ($category) {
                    $response = [
                        'success' => true,
                        'category' => $category
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Catégorie non trouvée'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['tree'])) {
                // Récupérer l'arbre des catégories
                $type = $_GET['type'] ?? null;
                $tree = $categoryModel->getTree($type);

                $response = [
                    'success' => true,
                    'categories' => $tree
                ];
            } elseif (isset($_GET['products'])) {
                // Récupérer les produits d'une catégorie
                if (!isset($_GET['id'])) {
                    $response = [
                        'success' => false,
                        'error' => 'ID de catégorie requis'
                    ];
                    http_response_code(400);
                    break;
                }

                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;

                $products = $categoryModel->getProducts($_GET['id'], $page, $limit);

                $response = [
                    'success' => true,
                    'products' => $products
                ];
            } else {
                // Récupérer toutes les catégories avec filtres
                $filters = [
                    'type' => $_GET['type'] ?? '',
                    'parent_id' => $_GET['parent_id'] ?? '',
                    'root' => isset($_GET['root']) ? (bool)$_GET['root'] : false
                ];

                $categories = $categoryModel->getAll($filters);
                $total = $categoryModel->count($filters);

                $response = [
                    'success' => true,
                    'categories' => $categories,
                    'total' => $total
                ];
            }
            break;

        case 'POST':
            // Créer une nouvelle catégorie
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $data = $_POST;
            }

            if (empty($data['nom']) || empty($data['type'])) {
                $response = [
                    'success' => false,
                    'error' => 'Nom et type sont obligatoires'
                ];
                http_response_code(400);
                break;
            }

            $id = $categoryModel->create($data);

            $response = [
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'id' => $id,
                'category' => $categoryModel->getById($id)
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour une catégorie
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID de la catégorie requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $category = $categoryModel->getById($id);
            if (!$category) {
                $response = [
                    'success' => false,
                    'error' => 'Catégorie non trouvée'
                ];
                http_response_code(404);
                break;
            }

            $categoryModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'category' => $categoryModel->getById($id)
            ];
            break;

        case 'DELETE':
            // Supprimer une catégorie (soft delete)
            if (!isset($_GET['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID de la catégorie requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'];
            $category = $categoryModel->getById($id);

            if (!$category) {
                $response = [
                    'success' => false,
                    'error' => 'Catégorie non trouvée'
                ];
                http_response_code(404);
                break;
            }

            $categoryModel->delete($id);

            $response = [
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
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
