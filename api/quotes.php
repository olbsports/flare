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

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = Database::getInstance()->getConnection();
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

            // Générer une référence unique pour le devis
            $reference = 'DEV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Encoder les données JSON
            $options = isset($data['options']) ? json_encode($data['options']) : null;
            $tailles = isset($data['tailles']) ? json_encode($data['tailles']) : null;
            $personnalisation = isset($data['personnalisation']) ? json_encode($data['personnalisation']) : null;

            // Insertion dans la base de données
            $sql = "INSERT INTO quotes (
                reference,
                client_prenom, client_nom, client_email, client_telephone, client_club, client_fonction,
                product_reference, product_nom, sport, famille,
                design_type, design_template_id, design_description,
                options, genre, tailles, personnalisation,
                total_pieces, prix_unitaire, prix_total,
                status, notes
            ) VALUES (
                :reference,
                :client_prenom, :client_nom, :client_email, :client_telephone, :client_club, :client_fonction,
                :product_reference, :product_nom, :sport, :famille,
                :design_type, :design_template_id, :design_description,
                :options, :genre, :tailles, :personnalisation,
                :total_pieces, :prix_unitaire, :prix_total,
                'pending', :notes
            )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':reference' => $reference,
                ':client_prenom' => $data['client_prenom'] ?? null,
                ':client_nom' => $data['client_nom'],
                ':client_email' => $data['client_email'],
                ':client_telephone' => $data['client_telephone'] ?? null,
                ':client_club' => $data['client_club'] ?? null,
                ':client_fonction' => $data['client_fonction'] ?? null,
                ':product_reference' => $data['product_reference'],
                ':product_nom' => $data['product_nom'] ?? null,
                ':sport' => $data['sport'] ?? null,
                ':famille' => $data['famille'] ?? null,
                ':design_type' => $data['design_type'] ?? null,
                ':design_template_id' => $data['design_template_id'] ?? null,
                ':design_description' => $data['design_description'] ?? null,
                ':options' => $options,
                ':genre' => $data['genre'] ?? null,
                ':tailles' => $tailles,
                ':personnalisation' => $personnalisation,
                ':total_pieces' => $data['total_pieces'],
                ':prix_unitaire' => $data['prix_unitaire'] ?? null,
                ':prix_total' => $data['prix_total'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);

            $quoteId = $db->lastInsertId();

            // TODO: Envoyer un email de confirmation au client
            // TODO: Envoyer une notification à l'admin

            $response = [
                'success' => true,
                'message' => 'Devis créé avec succès',
                'quote_id' => $quoteId,
                'reference' => $reference
            ];
            http_response_code(201);
            break;

        case 'GET':
            // Récupérer des devis
            if (isset($_GET['id'])) {
                // Récupérer un devis par ID
                $stmt = $db->prepare("SELECT * FROM quotes WHERE id = :id");
                $stmt->execute([':id' => $_GET['id']]);
                $quote = $stmt->fetch();

                if ($quote) {
                    // Décoder les données JSON
                    $quote['options'] = $quote['options'] ? json_decode($quote['options'], true) : null;
                    $quote['tailles'] = $quote['tailles'] ? json_decode($quote['tailles'], true) : null;
                    $quote['personnalisation'] = $quote['personnalisation'] ? json_decode($quote['personnalisation'], true) : null;

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
                $stmt = $db->prepare("SELECT * FROM quotes WHERE reference = :reference");
                $stmt->execute([':reference' => $_GET['reference']]);
                $quote = $stmt->fetch();

                if ($quote) {
                    $quote['options'] = $quote['options'] ? json_decode($quote['options'], true) : null;
                    $quote['tailles'] = $quote['tailles'] ? json_decode($quote['tailles'], true) : null;
                    $quote['personnalisation'] = $quote['personnalisation'] ? json_decode($quote['personnalisation'], true) : null;

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
            } else {
                // Récupérer tous les devis avec filtres
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $offset = ($page - 1) * $limit;
                $status = $_GET['status'] ?? '';

                $sql = "SELECT * FROM quotes";
                $params = [];

                if ($status) {
                    $sql .= " WHERE status = :status";
                    $params[':status'] = $status;
                }

                $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

                $stmt = $db->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                $stmt->execute();

                $quotes = $stmt->fetchAll();

                // Décoder les JSON pour chaque devis
                foreach ($quotes as &$quote) {
                    $quote['options'] = $quote['options'] ? json_decode($quote['options'], true) : null;
                    $quote['tailles'] = $quote['tailles'] ? json_decode($quote['tailles'], true) : null;
                    $quote['personnalisation'] = $quote['personnalisation'] ? json_decode($quote['personnalisation'], true) : null;
                }

                // Compter le total
                $countSql = "SELECT COUNT(*) as total FROM quotes";
                if ($status) {
                    $countSql .= " WHERE status = :status";
                }
                $countStmt = $db->prepare($countSql);
                if ($status) {
                    $countStmt->bindValue(':status', $status);
                }
                $countStmt->execute();
                $total = $countStmt->fetch()['total'];

                $response = [
                    'success' => true,
                    'quotes' => $quotes,
                    'pagination' => [
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ];
            }
            break;

        case 'PUT':
            // Mettre à jour un devis (changement de statut, notes, etc.)
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

            // Vérifier que le devis existe
            $stmt = $db->prepare("SELECT id FROM quotes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                $response = [
                    'success' => false,
                    'error' => 'Devis non trouvé'
                ];
                http_response_code(404);
                break;
            }

            // Construire la requête de mise à jour
            $updateFields = [];
            $params = [':id' => $id];

            $allowedFields = ['status', 'notes', 'prix_unitaire', 'prix_total'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                $response = [
                    'success' => false,
                    'error' => 'Aucun champ à mettre à jour'
                ];
                http_response_code(400);
                break;
            }

            $sql = "UPDATE quotes SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $response = [
                'success' => true,
                'message' => 'Devis mis à jour avec succès'
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
