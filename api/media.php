<?php
/**
 * FLARE CUSTOM - Media API
 * API REST pour la gestion de la bibliothèque médias
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Media.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$mediaModel = new Media();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de médias
            if (isset($_GET['id'])) {
                // Récupérer un média par ID
                $media = $mediaModel->getById($_GET['id']);
                if ($media) {
                    $response = [
                        'success' => true,
                        'media' => $media
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Média non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['filename'])) {
                // Récupérer un média par filename
                $media = $mediaModel->getByFilename($_GET['filename']);
                if ($media) {
                    $response = [
                        'success' => true,
                        'media' => $media
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Média non trouvé'
                    ];
                    http_response_code(404);
                }
            } else {
                // Récupérer tous les médias avec filtres
                $filters = [
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 30,
                    'type' => $_GET['type'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];

                $media = $mediaModel->getAll($filters);
                $total = $mediaModel->count($filters);

                $response = [
                    'success' => true,
                    'media' => $media,
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
            // Upload un nouveau média
            if (!isset($_FILES['file'])) {
                $response = [
                    'success' => false,
                    'error' => 'Aucun fichier fourni'
                ];
                http_response_code(400);
                break;
            }

            $file = $_FILES['file'];

            // Récupérer les métadonnées optionnelles
            $data = [
                'alt_text' => $_POST['alt_text'] ?? null,
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'uploaded_by' => $_POST['uploaded_by'] ?? null
            ];

            $media = $mediaModel->upload($file, $data);

            $response = [
                'success' => true,
                'message' => 'Média uploadé avec succès',
                'media' => $media
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour un média (métadonnées uniquement)
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du média requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            $media = $mediaModel->getById($id);
            if (!$media) {
                $response = [
                    'success' => false,
                    'error' => 'Média non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $mediaModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Média mis à jour avec succès',
                'media' => $mediaModel->getById($id)
            ];
            break;

        case 'DELETE':
            // Supprimer un média
            if (!isset($_GET['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du média requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'];
            $media = $mediaModel->getById($id);

            if (!$media) {
                $response = [
                    'success' => false,
                    'error' => 'Média non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $mediaModel->delete($id);

            $response = [
                'success' => true,
                'message' => 'Média supprimé avec succès'
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
