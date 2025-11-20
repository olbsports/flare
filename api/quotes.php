<?php
/**
 * FLARE CUSTOM - Quotes API
 * API REST pour la gestion des devis
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Quote.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$quoteModel = new Quote();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    switch ($method) {
        case 'POST':
            // Créer un nouveau devis
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                $data = $_POST;
            }

            // Validation des champs obligatoires
            $required = ['client_email', 'client_nom', 'product_reference', 'total_pieces'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response = [
                        'success' => false,
                        'error' => "Le champ $field est obligatoire"
                    ];
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }
            }

            $quoteId = $quoteModel->create($data);
            $quote = $quoteModel->getById($quoteId);

            // TODO: Envoyer un email de confirmation au client
            // TODO: Envoyer une notification à l'admin

            $response = [
                'success' => true,
                'message' => 'Devis créé avec succès',
                'quote_id' => $quoteId,
                'reference' => $quote['reference'],
                'quote' => $quote
            ];
            http_response_code(201);
            break;

        case 'GET':
            // Récupérer des devis
            if (isset($_GET['id'])) {
                // Récupérer un devis par ID
                $quote = $quoteModel->getById($_GET['id']);

                if ($quote) {
                    $response = [
                        'success' => true,
                        'quote' => $quote
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Devis non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['reference'])) {
                // Récupérer un devis par référence
                $quote = $quoteModel->getByReference($_GET['reference']);

                if ($quote) {
                    $response = [
                        'success' => true,
                        'quote' => $quote
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Devis non trouvé'
                    ];
                    http_response_code(404);
                }
            } elseif (isset($_GET['stats'])) {
                // Récupérer les statistiques
                $stats = $quoteModel->getStats();

                $response = [
                    'success' => true,
                    'stats' => $stats
                ];
            } else {
                // Récupérer tous les devis avec filtres
                $filters = [
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 20,
                    'status' => $_GET['status'] ?? '',
                    'client_email' => $_GET['client_email'] ?? '',
                    'product_reference' => $_GET['product_reference'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];

                $quotes = $quoteModel->getAll($filters);
                $total = $quoteModel->count($filters);

                $response = [
                    'success' => true,
                    'quotes' => $quotes,
                    'pagination' => [
                        'page' => (int)$filters['page'],
                        'limit' => (int)$filters['limit'],
                        'total' => $total,
                        'pages' => ceil($total / $filters['limit'])
                    ]
                ];
            }
            break;

        case 'PUT':
            // Mettre à jour un devis
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($_GET['id']) && !isset($data['id'])) {
                $response = [
                    'success' => false,
                    'error' => 'ID du devis requis'
                ];
                http_response_code(400);
                break;
            }

            $id = $_GET['id'] ?? $data['id'];
            unset($data['id']);

            // Vérifier que le devis existe
            $quote = $quoteModel->getById($id);
            if (!$quote) {
                $response = [
                    'success' => false,
                    'error' => 'Devis non trouvé'
                ];
                http_response_code(404);
                break;
            }

            $quoteModel->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'Devis mis à jour avec succès',
                'quote' => $quoteModel->getById($id)
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
