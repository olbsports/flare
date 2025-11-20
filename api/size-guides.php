<?php
/**
 * API Size Guides - Gestion des guides des tailles et associations aux produits
 *
 * Endpoints:
 * GET    /api/size-guides.php                           -> Liste tous les guides
 * GET    /api/size-guides.php?id=123                    -> Récupère un guide
 * GET    /api/size-guides.php?product_id=123            -> Guides associés à un produit
 * POST   /api/size-guides.php                           -> Crée un nouveau guide
 * PUT    /api/size-guides.php?id=123                    -> Met à jour un guide
 * DELETE /api/size-guides.php?id=123                    -> Supprime un guide
 * POST   /api/size-guides.php?action=associate          -> Associe un guide à un produit
 * DELETE /api/size-guides.php?action=disassociate&id=X  -> Retire une association
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$product_id = $_GET['product_id'] ?? null;

// ============================================
// GET - Liste ou récupère des guides
// ============================================
if ($method === 'GET') {

    // GET un guide spécifique
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM size_guides WHERE id = ?");
        $stmt->execute([$id]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guide) {
            // Décode le tableau JSON
            if ($guide['tableau']) {
                $guide['tableau_data'] = json_decode($guide['tableau'], true);
            }

            echo json_encode([
                'success' => true,
                'guide' => $guide
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Size guide not found'
            ]);
        }
        exit;
    }

    // GET les guides associés à un produit
    if ($product_id) {
        $stmt = $db->prepare("
            SELECT
                sg.*,
                psg.id as association_id,
                psg.ordre as association_ordre,
                psg.display_type,
                psg.visible
            FROM size_guides sg
            INNER JOIN product_size_guides psg ON sg.id = psg.size_guide_id
            WHERE psg.product_id = ? AND psg.visible = TRUE
            ORDER BY psg.ordre ASC
        ");
        $stmt->execute([$product_id]);
        $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Décode les tableaux JSON
        foreach ($guides as &$guide) {
            if ($guide['tableau']) {
                $guide['tableau_data'] = json_decode($guide['tableau'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'count' => count($guides),
            'guides' => $guides
        ]);
        exit;
    }

    // GET tous les guides (avec filtres optionnels)
    $categorie = $_GET['categorie'] ?? null;
    $sport = $_GET['sport'] ?? null;
    $genre = $_GET['genre'] ?? null;
    $active = isset($_GET['active']) ? (int)$_GET['active'] : null;
    $search = $_GET['search'] ?? null;

    $sql = "SELECT * FROM size_guides WHERE 1=1";
    $params = [];

    if ($categorie) {
        $sql .= " AND categorie = ?";
        $params[] = $categorie;
    }

    if ($sport) {
        $sql .= " AND sport = ?";
        $params[] = $sport;
    }

    if ($genre) {
        $sql .= " AND genre = ?";
        $params[] = $genre;
    }

    if ($active !== null) {
        $sql .= " AND active = ?";
        $params[] = $active;
    }

    if ($search) {
        $sql .= " AND (titre LIKE ? OR description LIKE ? OR conseils LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY sport ASC, categorie ASC, genre ASC, created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Décode les tableaux JSON
    foreach ($guides as &$guide) {
        if ($guide['tableau']) {
            $guide['tableau_data'] = json_decode($guide['tableau'], true);
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($guides),
        'guides' => $guides
    ]);
    exit;
}

// ============================================
// POST - Crée un nouveau guide ou actions spéciales
// ============================================
if ($method === 'POST') {

    // ACTION: Associe un guide à un produit
    if ($action === 'associate') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['product_id']) || !isset($input['guide_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'product_id and guide_id are required'
            ]);
            exit;
        }

        // Vérifie si l'association existe déjà
        $stmt = $db->prepare("
            SELECT id FROM product_size_guides
            WHERE product_id = ? AND size_guide_id = ?
        ");
        $stmt->execute([$input['product_id'], $input['guide_id']]);

        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'This guide is already associated with this product'
            ]);
            exit;
        }

        // Récupère le prochain ordre
        $stmt = $db->prepare("SELECT COALESCE(MAX(ordre), -1) + 1 as next_ordre FROM product_size_guides WHERE product_id = ?");
        $stmt->execute([$input['product_id']]);
        $nextOrdre = $stmt->fetch(PDO::FETCH_ASSOC)['next_ordre'];

        // Crée l'association
        $stmt = $db->prepare("
            INSERT INTO product_size_guides (product_id, size_guide_id, ordre, display_type, visible)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $input['product_id'],
            $input['guide_id'],
            $input['ordre'] ?? $nextOrdre,
            $input['display_type'] ?? 'tab',
            $input['visible'] ?? true
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Guide associated successfully',
                'association_id' => $db->lastInsertId()
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to associate guide'
            ]);
        }
        exit;
    }

    // POST - Crée un nouveau guide
    $input = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['categorie', 'titre', 'tableau'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit;
        }
    }

    // Encode le tableau en JSON si c'est un array
    $tableauJson = is_array($input['tableau']) ? json_encode($input['tableau']) : $input['tableau'];

    $stmt = $db->prepare("
        INSERT INTO size_guides (
            categorie, sport, genre, titre, description,
            tableau, conseils, image_url, active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $input['categorie'],
        $input['sport'] ?? null,
        $input['genre'] ?? 'mixte',
        $input['titre'],
        $input['description'] ?? null,
        $tableauJson,
        $input['conseils'] ?? null,
        $input['image_url'] ?? null,
        $input['active'] ?? true
    ]);

    if ($result) {
        $newId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Size guide created successfully',
            'id' => $newId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create size guide'
        ]);
    }
    exit;
}

// ============================================
// PUT - Met à jour un guide
// ============================================
if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Size guide ID is required'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Construit la requête UPDATE dynamiquement
    $allowedFields = [
        'categorie', 'sport', 'genre', 'titre', 'description',
        'tableau', 'conseils', 'image_url', 'active'
    ];

    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            // Encode le tableau en JSON si nécessaire
            if ($field === 'tableau' && is_array($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = json_encode($input[$field]);
            } else {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No fields to update'
        ]);
        exit;
    }

    $params[] = $id;

    $sql = "UPDATE size_guides SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Size guide updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update size guide'
        ]);
    }
    exit;
}

// ============================================
// DELETE - Supprime un guide ou une association
// ============================================
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID is required'
        ]);
        exit;
    }

    // ACTION: Retire une association produit ↔ guide
    if ($action === 'disassociate') {
        $stmt = $db->prepare("DELETE FROM product_size_guides WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Guide disassociated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to disassociate guide'
            ]);
        }
        exit;
    }

    // DELETE - Supprime un guide (et toutes ses associations en cascade)
    $stmt = $db->prepare("DELETE FROM size_guides WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Size guide deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete size guide'
        ]);
    }
    exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Method not allowed'
]);
