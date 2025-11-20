<?php
/**
 * API Product Photos - Gestion de la galerie de photos par produit
 *
 * Endpoints:
 * GET    /api/product-photos.php?product_id=123        -> Liste photos d'un produit
 * GET    /api/product-photos.php?id=456                -> Récupère une photo
 * POST   /api/product-photos.php                       -> Ajoute une photo
 * PUT    /api/product-photos.php?id=456                -> Met à jour une photo
 * DELETE /api/product-photos.php?id=456                -> Supprime une photo
 * POST   /api/product-photos.php?action=upload         -> Upload un fichier image
 * POST   /api/product-photos.php?action=reorder        -> Réordonne les photos
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
// GET - Liste ou récupère des photos
// ============================================
if ($method === 'GET') {

    // GET une photo spécifique
    if ($id) {
        $stmt = $db->prepare("
            SELECT *
            FROM product_photos
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($photo) {
            echo json_encode([
                'success' => true,
                'photo' => $photo
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Photo not found'
            ]);
        }
        exit;
    }

    // GET toutes les photos d'un produit
    if ($product_id) {
        $type = $_GET['type'] ?? null;
        $active = isset($_GET['active']) ? (int)$_GET['active'] : null;

        $sql = "
            SELECT *
            FROM product_photos
            WHERE product_id = ?
        ";

        $params = [$product_id];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($active !== null) {
            $sql .= " AND active = ?";
            $params[] = $active;
        }

        $sql .= " ORDER BY ordre ASC, created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'count' => count($photos),
            'photos' => $photos
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'product_id or id is required'
    ]);
    exit;
}

// ============================================
// POST - Ajoute une photo ou actions spéciales
// ============================================
if ($method === 'POST') {

    // ACTION: Upload d'un fichier image
    if ($action === 'upload') {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No file uploaded'
            ]);
            exit;
        }

        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../photos/produits/';

        // Crée le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Vérifie le type de fichier
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Only image files are allowed (JPEG, PNG, WebP, GIF)'
            ]);
            exit;
        }

        // Récupère les dimensions
        $imageInfo = getimagesize($file['tmp_name']);
        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;

        // Génère un nom de fichier unique
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'product-' . uniqid() . '.' . $fileExt;
        $filepath = $uploadDir . $filename;

        // Déplace le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode([
                'success' => true,
                'file' => [
                    'filename' => $filename,
                    'url' => '/photos/produits/' . $filename,
                    'size' => filesize($filepath),
                    'width' => $width,
                    'height' => $height,
                    'mime_type' => $mimeType
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to upload file'
            ]);
        }
        exit;
    }

    // ACTION: Réordonne les photos d'un produit
    if ($action === 'reorder') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['order']) || !is_array($input['order'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid order data'
            ]);
            exit;
        }

        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'product_id is required'
            ]);
            exit;
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE product_photos SET ordre = ? WHERE id = ? AND product_id = ?");

            foreach ($input['order'] as $index => $photoId) {
                $stmt->execute([$index, $photoId, $input['product_id']]);
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Photos reordered successfully'
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to reorder photos: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // POST - Ajoute une nouvelle photo
    $input = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['product_id', 'url'];
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

    // Récupère le prochain ordre
    $stmt = $db->prepare("SELECT COALESCE(MAX(ordre), -1) + 1 as next_ordre FROM product_photos WHERE product_id = ?");
    $stmt->execute([$input['product_id']]);
    $nextOrdre = $stmt->fetch(PDO::FETCH_ASSOC)['next_ordre'];

    $stmt = $db->prepare("
        INSERT INTO product_photos (
            product_id, url, filename, alt_text, title, type,
            ordre, width, height, size_bytes, mime_type, active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $input['product_id'],
        $input['url'],
        $input['filename'] ?? null,
        $input['alt_text'] ?? null,
        $input['title'] ?? null,
        $input['type'] ?? 'gallery',
        $input['ordre'] ?? $nextOrdre,
        $input['width'] ?? null,
        $input['height'] ?? null,
        $input['size_bytes'] ?? null,
        $input['mime_type'] ?? null,
        $input['active'] ?? true
    ]);

    if ($result) {
        $newId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Photo added successfully',
            'id' => $newId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add photo'
        ]);
    }
    exit;
}

// ============================================
// PUT - Met à jour une photo
// ============================================
if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Photo ID is required'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Construit la requête UPDATE dynamiquement
    $allowedFields = [
        'url', 'filename', 'alt_text', 'title', 'type', 'ordre',
        'width', 'height', 'size_bytes', 'mime_type', 'active'
    ];

    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
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

    $sql = "UPDATE product_photos SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Photo updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update photo'
        ]);
    }
    exit;
}

// ============================================
// DELETE - Supprime une photo
// ============================================
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Photo ID is required'
        ]);
        exit;
    }

    // Récupère la photo pour supprimer le fichier
    $stmt = $db->prepare("SELECT url FROM product_photos WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$photo) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Photo not found'
        ]);
        exit;
    }

    // Supprime de la BDD
    $stmt = $db->prepare("DELETE FROM product_photos WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        // Supprime le fichier physique (optionnel - peut-être gardé pour backup)
        // $filepath = __DIR__ . '/../' . ltrim($photo['url'], '/');
        // if (file_exists($filepath)) {
        //     @unlink($filepath);
        // }

        echo json_encode([
            'success' => true,
            'message' => 'Photo deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete photo'
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
