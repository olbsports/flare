<?php
/**
 * FLARE CUSTOM - Page Builder API
 * API pour le constructeur de pages type Elementor
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/PageBuilder.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$builder = new PageBuilder();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    $action = $_GET['action'] ?? 'blocks';

    switch ($method) {
        case 'GET':
            if ($action === 'blocks') {
                // Récupérer les blocs d'une page
                if (!isset($_GET['page_id'])) {
                    $response = ['success' => false, 'error' => 'page_id requis'];
                    http_response_code(400);
                    break;
                }

                $blocks = $builder->getPageBlocks($_GET['page_id']);

                $response = [
                    'success' => true,
                    'blocks' => $blocks
                ];
            } elseif ($action === 'templates') {
                // Récupérer les templates
                $templates = $builder->getTemplates([
                    'template_type' => $_GET['type'] ?? '',
                    'category' => $_GET['category'] ?? ''
                ]);

                $response = [
                    'success' => true,
                    'templates' => $templates
                ];
            } elseif ($action === 'render') {
                // Rendu d'une page
                if (!isset($_GET['page_id'])) {
                    $response = ['success' => false, 'error' => 'page_id requis'];
                    http_response_code(400);
                    break;
                }

                $html = $builder->renderPage($_GET['page_id']);

                $response = [
                    'success' => true,
                    'html' => $html
                ];
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if ($action === 'block') {
                // Créer un bloc
                if (empty($data['page_id']) || empty($data['block_type'])) {
                    $response = ['success' => false, 'error' => 'page_id et block_type requis'];
                    http_response_code(400);
                    break;
                }

                $id = $builder->createBlock($data);

                $response = [
                    'success' => true,
                    'message' => 'Bloc créé',
                    'id' => $id
                ];
                http_response_code(201);
            } elseif ($action === 'template') {
                // Créer un template
                if (empty($data['name']) || empty($data['blocks'])) {
                    $response = ['success' => false, 'error' => 'name et blocks requis'];
                    http_response_code(400);
                    break;
                }

                $id = $builder->createTemplate($data);

                $response = [
                    'success' => true,
                    'message' => 'Template créé',
                    'id' => $id
                ];
                http_response_code(201);
            } elseif ($action === 'apply_template') {
                // Appliquer un template
                if (empty($data['template_id']) || empty($data['page_id'])) {
                    $response = ['success' => false, 'error' => 'template_id et page_id requis'];
                    http_response_code(400);
                    break;
                }

                $builder->applyTemplateToPage($data['template_id'], $data['page_id']);

                $response = [
                    'success' => true,
                    'message' => 'Template appliqué'
                ];
            } elseif ($action === 'save_as_template') {
                // Sauvegarder une page comme template
                if (empty($data['page_id']) || empty($data['name'])) {
                    $response = ['success' => false, 'error' => 'page_id et name requis'];
                    http_response_code(400);
                    break;
                }

                $id = $builder->savePageAsTemplate($data['page_id'], $data['name'], $data);

                $response = [
                    'success' => true,
                    'message' => 'Template créé depuis la page',
                    'id' => $id
                ];
                http_response_code(201);
            } elseif ($action === 'reorder') {
                // Réorganiser les blocs
                if (empty($data['page_id']) || empty($data['block_ids'])) {
                    $response = ['success' => false, 'error' => 'page_id et block_ids requis'];
                    http_response_code(400);
                    break;
                }

                $builder->reorderBlocks($data['page_id'], $data['block_ids']);

                $response = [
                    'success' => true,
                    'message' => 'Blocs réorganisés'
                ];
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);

            if ($action === 'block') {
                // Mettre à jour un bloc
                if (!isset($_GET['id']) && !isset($data['id'])) {
                    $response = ['success' => false, 'error' => 'ID requis'];
                    http_response_code(400);
                    break;
                }

                $id = $_GET['id'] ?? $data['id'];
                unset($data['id']);

                $builder->updateBlock($id, $data);

                $response = [
                    'success' => true,
                    'message' => 'Bloc mis à jour'
                ];
            }
            break;

        case 'DELETE':
            if ($action === 'block') {
                if (!isset($_GET['id'])) {
                    $response = ['success' => false, 'error' => 'ID requis'];
                    http_response_code(400);
                    break;
                }

                $builder->deleteBlock($_GET['id']);

                $response = [
                    'success' => true,
                    'message' => 'Bloc supprimé'
                ];
            }
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
