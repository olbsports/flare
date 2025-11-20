<?php
/**
 * FLARE CUSTOM - Settings API
 * API REST pour la gestion des paramètres du site
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Settings.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$settingsModel = new Settings();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'GET':
            // Récupération de paramètres
            if (isset($_GET['key'])) {
                // Récupérer un paramètre spécifique
                $value = $settingsModel->get($_GET['key']);

                if ($value !== null) {
                    $response = [
                        'success' => true,
                        'key' => $_GET['key'],
                        'value' => $value
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Paramètre non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['category'])) {
                // Récupérer tous les paramètres d'une catégorie
                $settings = $settingsModel->getByCategory($_GET['category']);

                $response = [
                    'success' => true,
                    'category' => $_GET['category'],
                    'settings' => $settings
                ];
            } elseif (isset($_GET['categories'])) {
                // Récupérer la liste des catégories
                $categories = $settingsModel->getCategories();

                $response = [
                    'success' => true,
                    'categories' => $categories
                ];
            } elseif (isset($_GET['export'])) {
                // Exporter tous les paramètres
                $export = $settingsModel->export();

                $response = [
                    'success' => true,
                    'settings' => $export
                ];
            } else {
                // Récupérer tous les paramètres
                $settings = $settingsModel->getAll();

                // Organiser par catégorie
                $grouped = [];
                foreach ($settings as $setting) {
                    $category = $setting['category'] ?? 'general';
                    if (!isset($grouped[$category])) {
                        $grouped[$category] = [];
                    }
                    $grouped[$category][] = $setting;
                }

                $response = [
                    'success' => true,
                    'settings' => $grouped
                ];
            }
            break;

        case 'POST':
            // Créer ou mettre à jour un paramètre
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $data = $_POST;
            }

            if (empty($data['key'])) {
                $response = [
                    'success' => false,
                    'error' => 'La clé du paramètre est obligatoire'
                ];
                http_response_code(400);
                break;
            }

            $key = $data['key'];
            $value = $data['value'] ?? '';
            $type = $data['type'] ?? 'string';
            $category = $data['category'] ?? 'general';
            $description = $data['description'] ?? null;

            $settingsModel->set($key, $value, $type, $category, $description);

            $response = [
                'success' => true,
                'message' => 'Paramètre enregistré avec succès',
                'key' => $key,
                'value' => $settingsModel->get($key)
            ];
            http_response_code(201);
            break;

        case 'PUT':
            // Mettre à jour en masse
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !is_array($data)) {
                $response = [
                    'success' => false,
                    'error' => 'Données invalides'
                ];
                http_response_code(400);
                break;
            }

            // Vérifier si c'est un import
            if (isset($_GET['import'])) {
                $result = $settingsModel->import($data);
            } else {
                $result = $settingsModel->updateBulk($data);
            }

            $response = [
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'updated' => $result['updated'],
                'errors' => $result['errors']
            ];
            break;

        case 'DELETE':
            // Supprimer un paramètre
            if (!isset($_GET['key'])) {
                $response = [
                    'success' => false,
                    'error' => 'Clé du paramètre requise'
                ];
                http_response_code(400);
                break;
            }

            $key = $_GET['key'];
            $settingsModel->delete($key);

            $response = [
                'success' => true,
                'message' => 'Paramètre supprimé avec succès'
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
