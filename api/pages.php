<?php
/**
 * FLARE CUSTOM - Pages API
 * API REST pour la gestion des pages
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Page.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$pageModel = new Page();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de pages
            if (isset($_GET['id'])) {
                // Récupérer une page par ID
                $page = $pageModel->getById($_GET['id']);
                if ($page) {
                    $response = [
                        'success' => true,
                        'page' => $page
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Page non trouvée'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['slug'])) {
                // Récupérer une page par slug
                $page = $pageModel->getBySlug($_GET['slug']);
                if ($page) {
                    $response = [
                        'success' => true,
                        'page' => $page
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Page non trouvée'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['stats'])) {
                // Récupérer les statistiques
                $stats = $pageModel->getStats();
                $response = [
                    'success' => true,
                    'stats' => $stats
                ];
            } else {
                // Récupérer toutes les pages avec filtres
                $filters = [
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 50,
                    'type' => $_GET['type'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];

                $pages = $pageModel->getAll($filters);
                $total = $pageModel->count($filters);

                $response = [
                    'success' => true,
                    'pages' => $pages,
                    'total' => $total,
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
            // Créer une nouvelle page
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $data = $_POST;
            }

            if (empty($data['title'])) {
                $response = [
                    'success' => false,
                    'error' => 'Le titre est obligatoire'
                ];
                http_response_code(400);
                break;
            }

            $id = $pageModel->create($data);

            $response = [
                'success' => true,
                'message' => 'Page créée avec succès',
                'id' => $id,
                'page' => $pageModel->getById($id)
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour une page
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID de la page requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $page = $pageModel->getById($id);
            if (!$page) {
                $response = [
                    'success' => false,
                    'error' => 'Page non trouvée'
                ];
                http_response_code(404);
                break;
            }

            $pageModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Page mise à jour avec succès',
                'page' => $pageModel->getById($id)
            ];
            break;

        case 'DELETE':
            // Supprimer une page
            if (!isset($_GET['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID de la page requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'];
            $page = $pageModel->getById($id);

            if (!$page) {
                $response = [
                    'success' => false,
                    'error' => 'Page non trouvée'
                ];
                http_response_code(404);
                break;
            }

            $pageModel->delete($id);

            $response = [
                'success' => true,
                'message' => 'Page supprimée avec succès'
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
