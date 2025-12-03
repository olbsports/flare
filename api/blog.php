<?php
/**
 * FLARE CUSTOM - API Blog
 * CRUD pour les articles de blog
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
require_once __DIR__ . '/../includes/Blog.php';

$blog = new Blog();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer un article par ID
                $article = $blog->getById($_GET['id']);
                if ($article) {
                    echo json_encode(['success' => true, 'data' => $article]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                }
            } elseif (isset($_GET['slug'])) {
                // Récupérer un article par slug
                $article = $blog->getBySlug($_GET['slug']);
                if ($article) {
                    // Incrémenter les vues
                    if (!isset($_GET['no_view'])) {
                        $blog->incrementViews($article['id']);
                    }
                    echo json_encode(['success' => true, 'data' => $article]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                }
            } elseif (isset($_GET['action']) && $_GET['action'] === 'categories') {
                // Récupérer les catégories
                $categories = $blog->getCategories();
                echo json_encode(['success' => true, 'data' => $categories]);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'recent') {
                // Articles récents
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
                $articles = $blog->getRecent($limit);
                echo json_encode(['success' => true, 'data' => $articles]);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'stats') {
                // Statistiques
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total' => $blog->count(),
                        'published' => $blog->count('published'),
                        'draft' => $blog->count('draft'),
                        'archived' => $blog->count('archived')
                    ]
                ]);
            } else {
                // Liste des articles avec filtres
                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['category'])) $filters['category'] = $_GET['category'];
                if (isset($_GET['search'])) $filters['search'] = $_GET['search'];

                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

                $result = $blog->getAll($filters, $page, $limit);
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Le titre est requis']);
                exit;
            }

            $id = $blog->create($input);
            $article = $blog->getById($id);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => $article
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

            $blog->update($id, $input);
            $article = $blog->getById($id);

            echo json_encode([
                'success' => true,
                'message' => 'Article mis à jour',
                'data' => $article
            ]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID requis']);
                exit;
            }

            $blog->delete($id);
            echo json_encode(['success' => true, 'message' => 'Article supprimé']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
