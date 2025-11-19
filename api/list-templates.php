<?php
/**
 * FLARE CUSTOM - Templates List Generator
 * Scanne le dossier /templates/ et retourne la liste des fichiers SVG
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Dossier contenant les templates
$templatesFolder = dirname(__DIR__) . '/templates/';

// Vérifier si le dossier existe
if (!is_dir($templatesFolder)) {
    echo json_encode([
        'success' => false,
        'error' => 'Le dossier /templates/ n\'existe pas',
        'files' => []
    ]);
    exit;
}

// Scanner le dossier
$files = scandir($templatesFolder);
$templates = [];

foreach ($files as $file) {
    // Vérifier que c'est un fichier SVG
    if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
        $templates[] = [
            'id' => pathinfo($file, PATHINFO_FILENAME),
            'filename' => $file,
            'path' => '/templates/' . $file
        ];
    }
}

// Trier par ordre alphabétique
usort($templates, function($a, $b) {
    return strcmp($a['filename'], $b['filename']);
});

// Retourner la liste
echo json_encode([
    'success' => true,
    'count' => count($templates),
    'templates' => $templates
], JSON_UNESCAPED_UNICODE);
?>
