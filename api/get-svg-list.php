<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$svgDir = __DIR__ . '/svg/';

// Vérifier que le dossier existe
if (!is_dir($svgDir)) {
    echo json_encode([
        'success' => false,
        'error' => 'Le dossier /svg/ n\'existe pas',
        'files' => []
    ]);
    exit;
}

// Scanner le dossier
$files = scandir($svgDir);
$svgFiles = [];

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
        $svgFiles[] = $file;
    }
}

sort($svgFiles);

echo json_encode([
    'success' => true,
    'count' => count($svgFiles),
    'files' => $svgFiles
]);
?>
