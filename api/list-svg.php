<?php
/**
 * FLARE CUSTOM - SVG List Generator
 * Scanne automatiquement le dossier /svg/ et retourne la liste des fichiers SVG
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Dossier contenant les SVG
// Pour le test, on utilise le dossier uploads
$svgFolder = __DIR__ . '/svg/';

// Si on est en environnement de test et que le dossier n'existe pas, on essaie uploads
if (!is_dir($svgFolder)) {
    $svgFolder = '/mnt/user-data/uploads/';
}

// Vérifier si le dossier existe
if (!is_dir($svgFolder)) {
    echo json_encode([
        'success' => false,
        'error' => 'Le dossier /svg/ n\'existe pas',
        'files' => []
    ]);
    exit;
}

// Scanner le dossier
$files = scandir($svgFolder);
$svgFiles = [];

foreach ($files as $file) {
    // Vérifier que c'est un fichier SVG
    if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
        $svgFiles[] = $file;
    }
}

// Trier par ordre alphabétique
sort($svgFiles);

// Retourner la liste
echo json_encode([
    'success' => true,
    'count' => count($svgFiles),
    'files' => $svgFiles
], JSON_UNESCAPED_UNICODE);
?>
