<?php
/**
 * API Templates Manager - CRUD complet pour les templates SVG
 *
 * Endpoints:
 * GET    /api/templates-manager.php                  -> Liste tous les templates
 * GET    /api/templates-manager.php?id=123           -> Récupère un template
 * POST   /api/templates-manager.php                  -> Crée un nouveau template
 * PUT    /api/templates-manager.php?id=123           -> Met à jour un template
 * DELETE /api/templates-manager.php?id=123           -> Supprime un template
 * POST   /api/templates-manager.php?action=upload    -> Upload un fichier SVG
 * POST   /api/templates-manager.php?action=reorder   -> Réordonne les templates
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

// ============================================
// GET - Liste ou récupère un template
// ============================================
if ($method === 'GET') {

    // GET un template spécifique
    if ($id) {
        $stmt = $db->prepare("
            SELECT
                t.*,
                tc.nom as category_name,
                tc.slug as category_slug
            FROM templates t
            LEFT JOIN template_categories tc ON t.category_id = tc.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($template) {
            // Décode les champs JSON
            if ($template['tags']) {
                $template['tags_array'] = explode(',', $template['tags']);
            }

            echo json_encode([
                'success' => true,
                'template' => $template
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Template not found'
            ]);
        }
        exit;
    }

    // GET tous les templates (avec filtres optionnels)
    $category = $_GET['category'] ?? null;
    $sport = $_GET['sport'] ?? null;
    $famille = $_GET['famille'] ?? null;
    $active = isset($_GET['active']) ? (int)$_GET['active'] : null;
    $search = $_GET['search'] ?? null;

    $sql = "
        SELECT
            t.*,
            tc.nom as category_name,
            tc.slug as category_slug
        FROM templates t
        LEFT JOIN template_categories tc ON t.category_id = tc.id
        WHERE 1=1
    ";

    $params = [];

    if ($category) {
        $sql .= " AND (t.category_id = ? OR tc.slug = ?)";
        $params[] = $category;
        $params[] = $category;
    }

    if ($sport) {
        $sql .= " AND t.sport = ?";
        $params[] = $sport;
    }

    if ($famille) {
        $sql .= " AND t.famille = ?";
        $params[] = $famille;
    }

    if ($active !== null) {
        $sql .= " AND t.active = ?";
        $params[] = $active;
    }

    if ($search) {
        $sql .= " AND (t.nom LIKE ? OR t.description LIKE ? OR t.tags LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY t.ordre ASC, t.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traite les templates
    foreach ($templates as &$template) {
        if ($template['tags']) {
            $template['tags_array'] = explode(',', $template['tags']);
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($templates),
        'templates' => $templates
    ]);
    exit;
}

// ============================================
// POST - Crée un nouveau template ou actions spéciales
// ============================================
if ($method === 'POST') {

    // ACTION: Upload d'un fichier SVG
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
        $uploadDir = __DIR__ . '/../templates/';

        // Crée le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Vérifie le type de fichier
        $allowedTypes = ['image/svg+xml', 'text/plain', 'application/octet-stream'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($fileExt !== 'svg') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Only SVG files are allowed'
            ]);
            exit;
        }

        // Génère un nom de fichier unique
        $filename = 'template-' . uniqid() . '.svg';
        $filepath = $uploadDir . $filename;

        // Déplace le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Lit le contenu SVG
            $svgContent = file_get_contents($filepath);

            // Extrait les dimensions du SVG
            $width = null;
            $height = null;
            if (preg_match('/width=["\'](\d+)["\']/', $svgContent, $matches)) {
                $width = (int)$matches[1];
            }
            if (preg_match('/height=["\'](\d+)["\']/', $svgContent, $matches)) {
                $height = (int)$matches[1];
            }

            echo json_encode([
                'success' => true,
                'file' => [
                    'filename' => $filename,
                    'path' => '/templates/' . $filename,
                    'url' => '/templates/' . $filename,
                    'size' => filesize($filepath),
                    'width' => $width,
                    'height' => $height,
                    'svg_content' => $svgContent
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

    // ACTION: Réordonne les templates
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

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE templates SET ordre = ? WHERE id = ?");

            foreach ($input['order'] as $index => $templateId) {
                $stmt->execute([$index, $templateId]);
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Templates reordered successfully'
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to reorder templates: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // POST - Crée un nouveau template
    $input = json_decode(file_get_contents('php://input'), true);

    $requiredFields = ['filename', 'path'];
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

    $stmt = $db->prepare("
        INSERT INTO templates (
            filename, nom, description, path, preview_url, type,
            tags, category_id, sport, famille, svg_content,
            width, height, colors_count, editable, ordre, active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $input['filename'],
        $input['nom'] ?? null,
        $input['description'] ?? null,
        $input['path'],
        $input['preview_url'] ?? null,
        $input['type'] ?? 'svg',
        $input['tags'] ?? null,
        $input['category_id'] ?? null,
        $input['sport'] ?? null,
        $input['famille'] ?? null,
        $input['svg_content'] ?? null,
        $input['width'] ?? null,
        $input['height'] ?? null,
        $input['colors_count'] ?? 1,
        $input['editable'] ?? true,
        $input['ordre'] ?? 0,
        $input['active'] ?? true
    ]);

    if ($result) {
        $newId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Template created successfully',
            'id' => $newId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create template'
        ]);
    }
    exit;
}

// ============================================
// PUT - Met à jour un template
// ============================================
if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Template ID is required'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Construit la requête UPDATE dynamiquement
    $allowedFields = [
        'filename', 'nom', 'description', 'path', 'preview_url', 'type',
        'tags', 'category_id', 'sport', 'famille', 'svg_content',
        'width', 'height', 'colors_count', 'editable', 'ordre', 'active'
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

    $sql = "UPDATE templates SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Template updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update template'
        ]);
    }
    exit;
}

// ============================================
// DELETE - Supprime un template
// ============================================
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Template ID is required'
        ]);
        exit;
    }

    // Récupère le template pour supprimer le fichier
    $stmt = $db->prepare("SELECT filename, path FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Template not found'
        ]);
        exit;
    }

    // Supprime de la BDD
    $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        // Supprime le fichier physique
        $filepath = __DIR__ . '/../' . ltrim($template['path'], '/');
        if (file_exists($filepath)) {
            @unlink($filepath);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete template'
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
