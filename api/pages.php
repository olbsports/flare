<?php
/**
 * FLARE CUSTOM - API Pages
 * CRUD pour les pages dynamiques
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Page.php';

$pageModel = new Page();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer une page par ID
                $page = $pageModel->getById($_GET['id']);
                if ($page) {
                    echo json_encode(['success' => true, 'data' => $page]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Page non trouvée']);
                }
            } elseif (isset($_GET['slug'])) {
                // Récupérer une page par slug
                $page = $pageModel->getBySlug($_GET['slug']);
                if ($page) {
                    echo json_encode(['success' => true, 'data' => $page]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Page non trouvée']);
                }
            } elseif (isset($_GET['action']) && $_GET['action'] === 'published') {
                // Pages publiées
                $type = $_GET['type'] ?? null;
                $pages = $pageModel->getPublished($type);
                echo json_encode(['success' => true, 'data' => $pages]);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'stats') {
                // Statistiques
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total' => $pageModel->count(),
                        'published' => $pageModel->count('published'),
                        'draft' => $pageModel->count('draft'),
                        'archived' => $pageModel->count('archived')
                    ]
                ]);
            } else {
                // Liste des pages avec filtres
                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['type'])) $filters['type'] = $_GET['type'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];

                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

                $result = $pageModel->getAll($filters, $page, $limit);
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            // Action spéciale: dupliquer
            if (isset($input['action']) && $input['action'] === 'duplicate') {
                if (empty($input['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'ID requis']);
                    exit;
                }
                $newId = $pageModel->duplicate($input['id']);
                $page = $pageModel->getById($newId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Page dupliquée',
                    'data' => $page
                ]);
                exit;
            }

            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Le titre est requis']);
                exit;
            }

            $id = $pageModel->create($input);
            $page = $pageModel->getById($id);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Page créée avec succès',
                'data' => $page
            ]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID requis']);
                exit;
            }

            $id = $input['id'];
            unset($input['id']);

            // Actions spéciales
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'publish':
                        $pageModel->publish($id);
                        break;
                    case 'archive':
                        $pageModel->archive($id);
                        break;
                }
            } else {
                $pageModel->update($id, $input);
            }

            $page = $pageModel->getById($id);
            echo json_encode([
                'success' => true,
                'message' => 'Page mise à jour',
                'data' => $page
            ]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID requis']);
                exit;
            }

            $pageModel->delete($id);
            echo json_encode(['success' => true, 'message' => 'Page supprimée']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
