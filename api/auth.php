<?php
/**
 * FLARE CUSTOM - Auth API
 * API REST pour l'authentification et la gestion des utilisateurs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

try {
    // Déterminer l'action
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'POST':
            if ($action === 'login') {
                // Connexion
                $data = json_decode(file_get_contents('php://input'), true);

                if (!$data) {
                    $data = $_POST;
                }

                if (empty($data['username']) || empty($data['password'])) {
                    $response = [
                        'success' => false,
                        'error' => 'Username et password sont obligatoires'
                    ];
                    http_response_code(400);
                    break;
                }

                $result = $auth->login($data['username'], $data['password']);

                if ($result['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'Connexion réussie',
                        'user' => $result['user']
                    ];
                } else {
                    $response = $result;
                    http_response_code(401);
                }
            } elseif ($action === 'logout') {
                // Déconnexion
                $response = $auth->logout();
            } elseif ($action === 'register') {
                // Créer un utilisateur (nécessite admin)
                $auth->requireAuth('admin');

                $data = json_decode(file_get_contents('php://input'), true);

                if (!$data) {
                    $data = $_POST;
                }

                if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                    $response = [
                        'success' => false,
                        'error' => 'Username, email et password sont obligatoires'
                    ];
                    http_response_code(400);
                    break;
                }

                $id = $auth->createUser($data);

                $response = [
                    'success' => true,
                    'message' => 'Utilisateur créé avec succès',
                    'user_id' => $id
                ];
                http_response_code(201);
            } elseif ($action === 'change-password') {
                // Changer son mot de passe
                $auth->requireAuth();

                $data = json_decode(file_get_contents('php://input'), true);

                if (empty($data['current_password']) || empty($data['new_password'])) {
                    $response = [
                        'success' => false,
                        'error' => 'Mot de passe actuel et nouveau mot de passe requis'
                    ];
                    http_response_code(400);
                    break;
                }

                $auth->changePassword($data['current_password'], $data['new_password']);

                $response = [
                    'success' => true,
                    'message' => 'Mot de passe modifié avec succès'
                ];
            } elseif ($action === 'reset-password') {
                // Demander une réinitialisation de mot de passe
                $data = json_decode(file_get_contents('php://input'), true);

                if (empty($data['email'])) {
                    $response = [
                        'success' => false,
                        'error' => 'Email requis'
                    ];
                    http_response_code(400);
                    break;
                }

                $token = $auth->generatePasswordResetToken($data['email']);

                // TODO: Envoyer l'email avec le token

                $response = [
                    'success' => true,
                    'message' => 'Email de réinitialisation envoyé'
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Action non reconnue'
                ];
                http_response_code(400);
            }
            break;

        case 'GET':
            if ($action === 'me') {
                // Récupérer l'utilisateur connecté
                $auth->requireAuth();

                $user = $auth->getCurrentUser();

                $response = [
                    'success' => true,
                    'user' => $user
                ];
            } elseif ($action === 'check') {
                // Vérifier si l'utilisateur est connecté
                $response = [
                    'success' => true,
                    'logged_in' => $auth->isLoggedIn(),
                    'user' => $auth->isLoggedIn() ? $auth->getCurrentUser() : null
                ];
            } elseif ($action === 'users') {
                // Lister tous les utilisateurs (admin uniquement)
                $auth->requireAuth('admin');

                $users = $auth->getAllUsers();

                $response = [
                    'success' => true,
                    'users' => $users
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Action non reconnue'
                ];
                http_response_code(400);
            }
            break;

        case 'PUT':
            if ($action === 'update-user') {
                // Mettre à jour un utilisateur
                $auth->requireAuth('admin');

                $data = json_decode(file_get_contents('php://input'), true);

                if (!isset($_GET['id']) && !isset($data['id'])) {
                    $response = [
                        'success' => false,
                        'error' => 'ID utilisateur requis'
                    ];
                    http_response_code(400);
                    break;
                }

                $id = $_GET['id'] ?? $data['id'];
                unset($data['id']);

                $auth->updateUser($id, $data);

                $response = [
                    'success' => true,
                    'message' => 'Utilisateur mis à jour avec succès'
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Action non reconnue'
                ];
                http_response_code(400);
            }
            break;

        case 'DELETE':
            if ($action === 'delete-user') {
                // Supprimer un utilisateur
                $auth->requireAuth('admin');

                if (!isset($_GET['id'])) {
                    $response = [
                        'success' => false,
                        'error' => 'ID utilisateur requis'
                    ];
                    http_response_code(400);
                    break;
                }

                $id = $_GET['id'];

                // Empêcher de se supprimer soi-même
                if ($id == $_SESSION['user_id']) {
                    $response = [
                        'success' => false,
                        'error' => 'Vous ne pouvez pas vous supprimer vous-même'
                    ];
                    http_response_code(400);
                    break;
                }

                $auth->deleteUser($id);

                $response = [
                    'success' => true,
                    'message' => 'Utilisateur supprimé avec succès'
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Action non reconnue'
                ];
                http_response_code(400);
            }
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
