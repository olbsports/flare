<?php
/**
 * FLARE CUSTOM - Templates API
 * API REST pour la gestion des templates de design
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Template.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$templateModel = new Template();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de templates
            if (isset($_GET['id'])) {
                // Récupérer un template par ID
                $template = $templateModel->getById($_GET['id']);
                if ($template) {
                    // Si demandé, inclure le contenu SVG
                    if (isset($_GET['include_content']) && $template['type'] === 'svg') {
                        $template['content'] = $templateModel->getSvgContent($_GET['id']);
                    }

                    $response = [
                        'success' => true,
                        'template' => $template
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Template non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['filename'])) {
                // Récupérer un template par filename
                $template = $templateModel->getByFilename($_GET['filename']);
                if ($template) {
                    $response = [
                        'success' => true,
                        'template' => $template
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Template non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['scan'])) {
                // Scanner et importer les templates du dossier
                $result = $templateModel->scanAndImport();

                $response = [
                    'success' => true,
                    'message' => 'Scan terminé',
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped']
                ];
            } else {
                // Récupérer tous les templates avec filtres
                $filters = [
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 30,
                    'type' => $_GET['type'] ?? '',
                    'tags' => $_GET['tags'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];

                $templates = $templateModel->getAll($filters);
                $total = $templateModel->count($filters);

                $response = [
                    'success' => true,
                    'templates' => $templates,
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
            // Upload un nouveau template
            if (isset($_FILES['file'])) {
                // Upload depuis un fichier
                $file = $_FILES['file'];

                $data = [
                    'nom' => $_POST['nom'] ?? null,
                    'description' => $_POST['description'] ?? null,
                    'tags' => $_POST['tags'] ?? null,
                    'ordre' => $_POST['ordre'] ?? 0,
                    'active' => $_POST['active'] ?? 1
                ];

                $template = $templateModel->upload($file, $data);

                $response = [
                    'success' => true,
                    'message' => 'Template uploadé avec succès',
                    'template' => $template
                ];
                http_response_code(201);
            } else {
                // Créer depuis des données JSON
                $data = json_decode(file_get_contents('php://input'), true);

                if (!$data) {
                    $data = $_POST;
                }

                if (empty($data['filename']) || empty($data['path'])) {
                    $response = [
                        'success' => false,
                        'error' => 'Filename et path sont obligatoires'
                    ];
                    http_response_code(400);
                    break;
                }

                $id = $templateModel->create($data);

                $response = [
                    'success' => true,
                    'message' => 'Template créé avec succès',
                    'id' => $id,
                    'template' => $templateModel->getById($id)
                ];
                http_response_code(201);
            }
            break;

        case 'PUT':
            // Mettre à jour un template
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du template requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $template = $templateModel->getById($id);
            if (!$template) {
                $response = [
                    'success' => false,
                    'error' => 'Template non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $templateModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Template mis à jour avec succès',
                'template' => $templateModel->getById($id)
            ];
            break;

        case 'DELETE':
            // Supprimer un template
            if (!isset($_GET['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du template requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'];
            $template = $templateModel->getById($id);

            if (!$template) {
                $response = [
                    'success' => false,
                    'error' => 'Template non trouvé'
                ];
                http_response_code(404);
                break;
            }

            // Soft delete par défaut, hard delete si demandé
            if (isset($_GET['hard']) && $_GET['hard'] === '1') {
                $templateModel->hardDelete($id);
                $message = 'Template supprimé définitivement avec succès';
            } else {
                $templateModel->delete($id);
                $message = 'Template désactivé avec succès';
            }

            $response = [
                'success' => true,
                'message' => $message
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
