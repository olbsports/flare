<?php
/**
 * API Template-Products Association - FLARE CUSTOM
 * Gère les associations entre templates et produits
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Créer la table si elle n'existe pas
    $db->exec("
        CREATE TABLE IF NOT EXISTS template_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_template_product (template_id, product_id),
            INDEX idx_template (template_id),
            INDEX idx_product (product_id)
        )
    ");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les produits d'un template
if ($method === 'GET') {
    $templateId = $_GET['template_id'] ?? null;

    if (!$templateId) {
        echo json_encode(['success' => false, 'error' => 'template_id required']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT p.id, p.reference, p.nom, p.meta_title, p.sport, p.famille, p.photo_1
        FROM products p
        INNER JOIN template_products tp ON p.id = tp.product_id
        WHERE tp.template_id = ?
        ORDER BY p.sport, p.famille, p.nom
    ");
    $stmt->execute([$templateId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'template_id' => $templateId,
        'count' => count($products),
        'products' => $products
    ]);
    exit;
}

// POST - Définir les produits d'un template
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $templateId = $input['template_id'] ?? null;
    $products = $input['products'] ?? [];

    if (!$templateId) {
        echo json_encode(['success' => false, 'error' => 'template_id required']);
        exit;
    }

    $db->beginTransaction();

    try {
        // Supprimer les associations existantes
        $stmt = $db->prepare("DELETE FROM template_products WHERE template_id = ?");
        $stmt->execute([$templateId]);

        // Ajouter les nouvelles associations
        if (!empty($products)) {
            $stmt = $db->prepare("INSERT INTO template_products (template_id, product_id) VALUES (?, ?)");
            foreach ($products as $productId) {
                $stmt->execute([$templateId, (int)$productId]);
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Products associated successfully',
            'template_id' => $templateId,
            'products_count' => count($products)
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Failed to save: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE - Supprimer une association spécifique
if ($method === 'DELETE') {
    $templateId = $_GET['template_id'] ?? null;
    $productId = $_GET['product_id'] ?? null;

    if (!$templateId || !$productId) {
        echo json_encode(['success' => false, 'error' => 'template_id and product_id required']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM template_products WHERE template_id = ? AND product_id = ?");
    $result = $stmt->execute([$templateId, $productId]);

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Association removed' : 'Failed to remove'
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
