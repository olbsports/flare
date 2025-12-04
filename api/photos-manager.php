<?php
/**
 * Photos Manager API
 * Gestion des photos dans /photos/ et sous-dossiers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$basePhotosPath = __DIR__ . '/../photos';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
$maxFileSize = 10 * 1024 * 1024; // 10 Mo

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// UPLOAD - Uploader une photo
// ============================================
if ($action === 'upload' && $method === 'POST') {
    if (!isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'error' => 'Aucun fichier envoyé']);
        exit;
    }

    $file = $_FILES['file'];
    $folder = $_POST['folder'] ?? 'produits';

    // Sécuriser le nom du dossier
    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
    if (empty($folder)) $folder = 'produits';

    // Vérifier l'extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        echo json_encode(['success' => false, 'error' => 'Extension non autorisée: ' . $ext]);
        exit;
    }

    // Vérifier la taille
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 Mo)']);
        exit;
    }

    // Créer le dossier si nécessaire
    $targetDir = $basePhotosPath . '/' . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Générer un nom de fichier unique si nécessaire
    $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $filename = $originalName;
    $counter = 1;
    while (file_exists($targetDir . '/' . $filename)) {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $filename = $name . '-' . $counter . '.' . $ext;
        $counter++;
    }

    // Déplacer le fichier
    $targetPath = $targetDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'message' => 'Photo uploadée',
            'file' => [
                'name' => $filename,
                'url' => '/photos/' . $folder . '/' . $filename,
                'size' => $file['size']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors du déplacement du fichier']);
    }
    exit;
}

// ============================================
// DELETE - Supprimer une photo
// ============================================
if ($action === 'delete' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = $input['url'] ?? '';

    if (empty($url)) {
        echo json_encode(['success' => false, 'error' => 'URL manquante']);
        exit;
    }

    // Convertir l'URL en chemin de fichier
    // URL format: /photos/dossier/fichier.jpg
    if (!preg_match('#^/photos/([a-zA-Z0-9_-]+/)?[a-zA-Z0-9._-]+\.(jpg|jpeg|png|gif|webp|svg)$#i', $url)) {
        echo json_encode(['success' => false, 'error' => 'URL invalide']);
        exit;
    }

    $relativePath = substr($url, 8); // Enlever "/photos/"
    $filePath = $basePhotosPath . '/' . $relativePath;

    // Vérifier que le fichier est bien dans /photos/
    $realPath = realpath($filePath);
    $realBasePath = realpath($basePhotosPath);
    if (!$realPath || strpos($realPath, $realBasePath) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Fichier non trouvé ou chemin invalide']);
        exit;
    }

    if (file_exists($filePath) && unlink($filePath)) {
        echo json_encode(['success' => true, 'message' => 'Photo supprimée']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le fichier']);
    }
    exit;
}

// ============================================
// LIST - Lister les photos d'un dossier
// ============================================
if ($action === 'list' && $method === 'GET') {
    $folder = $_GET['folder'] ?? '';
    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);

    $scanPath = $folder ? $basePhotosPath . '/' . $folder : $basePhotosPath;

    if (!is_dir($scanPath)) {
        echo json_encode(['success' => false, 'error' => 'Dossier non trouvé']);
        exit;
    }

    $photos = [];
    $files = glob($scanPath . '/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE);
    foreach ($files as $file) {
        $photos[] = [
            'name' => basename($file),
            'url' => '/photos/' . ($folder ? $folder . '/' : '') . basename($file),
            'size' => filesize($file),
            'modified' => filemtime($file)
        ];
    }

    echo json_encode([
        'success' => true,
        'folder' => $folder ?: 'root',
        'photos' => $photos
    ]);
    exit;
}

// Default response
echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
