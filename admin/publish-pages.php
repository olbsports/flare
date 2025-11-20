<?php
/**
 * FLARE CUSTOM - Publication des Pages
 * Régénère les fichiers HTML depuis le contenu de la BDD
 */

require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../config/database.php';

set_time_limit(300);

$db = Database::getInstance()->getConnection();

$publishLog = [];
$stats = [
    'total' => 0,
    'success' => 0,
    'errors' => 0,
    'skipped' => 0
];

// Si on lance la publication
$isPublishing = isset($_POST['publish']);

if ($isPublishing) {
    // Récupère tous les content blocks actifs
    $stmt = $db->query("
        SELECT block_key, titre, contenu
        FROM content_blocks
        WHERE active = 1
        ORDER BY block_key ASC
    ");
    $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($blocks as $block) {
        $stats['total']++;

        $blockKey = $block['block_key'];
        $content = json_decode($block['contenu'], true);

        // Détermine le fichier source
        $sourceFile = null;

        if (isset($content['source_file']) && file_exists($content['source_file'])) {
            $sourceFile = $content['source_file'];
        } else {
            // Essaye de deviner le fichier source
            if (strpos($blockKey, 'product_page_') === 0) {
                $filename = str_replace('product_page_', '', $blockKey);
                $sourceFile = __DIR__ . '/../pages/products/' . $filename . '.html';
            } elseif (strpos($blockKey, 'page_') === 0) {
                $filename = str_replace('page_', '', $blockKey);
                $sourceFile = __DIR__ . '/../pages/' . $filename . '.html';

                if (!file_exists($sourceFile)) {
                    $sourceFile = __DIR__ . '/../' . $filename . '.html';
                }
            }
        }

        if (!$sourceFile || !file_exists($sourceFile)) {
            $publishLog[] = "⚠️ Fichier source non trouvé pour: $blockKey";
            $stats['skipped']++;
            continue;
        }

        // Régénère le fichier HTML
        try {
            $result = regenerateHTMLFile($sourceFile, $content);

            if ($result['success']) {
                $publishLog[] = "✅ " . basename($sourceFile) . " - Publié avec succès";
                $stats['success']++;
            } else {
                $publishLog[] = "❌ " . basename($sourceFile) . " - Erreur: " . $result['error'];
                $stats['errors']++;
            }

        } catch (Exception $e) {
            $publishLog[] = "❌ " . basename($sourceFile) . " - Exception: " . $e->getMessage();
            $stats['errors']++;
        }
    }
}

/**
 * Régénère un fichier HTML depuis le contenu BDD
 */
function regenerateHTMLFile($filePath, $content) {
    try {
        // Lit le fichier HTML original
        $html = file_get_contents($filePath);

        if (!$html) {
            return ['success' => false, 'error' => 'Impossible de lire le fichier'];
        }

        // Parse le HTML
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8';

        // Supprime les erreurs HTML mal formé
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Remplace le <title>
        if (!empty($content['title'])) {
            $titleNode = $xpath->query('//title')->item(0);
            if ($titleNode) {
                $titleNode->nodeValue = $content['title'];
            }
        }

        // Remplace la meta description
        if (!empty($content['meta_description'])) {
            $metaDesc = $xpath->query('//meta[@name="description"]')->item(0);
            if ($metaDesc) {
                $metaDesc->setAttribute('content', $content['meta_description']);
            }
        }

        // Remplace le H1 (premier trouvé)
        if (!empty($content['h1'])) {
            $h1Node = $xpath->query('//h1')->item(0);
            if ($h1Node) {
                $h1Node->nodeValue = $content['h1'];
            }
        }

        // Sauvegarde le HTML modifié
        $newHTML = $dom->saveHTML();

        // Crée un backup avant de sauvegarder
        $backupFile = $filePath . '.backup_' . date('YmdHis');
        copy($filePath, $backupFile);

        // Écrit le nouveau fichier
        if (file_put_contents($filePath, $newHTML) === false) {
            return ['success' => false, 'error' => 'Impossible d\'écrire le fichier'];
        }

        return ['success' => true];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publication des Pages - FLARE CUSTOM Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f7;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .content {
            padding: 40px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #FF4B26;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .warning-box h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .warning-box ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        .warning-box li {
            margin-bottom: 5px;
        }

        .btn {
            display: inline-block;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-publish {
            background: #28a745;
            color: white;
        }

        .btn-publish:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            margin-left: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-value.success { color: #28a745; }
        .stat-value.error { color: #dc3545; }
        .stat-value.skipped { color: #ffc107; }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .log-container {
            background: #1d1d1f;
            color: #0f0;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 30px;
        }

        .log-container div {
            margin-bottom: 5px;
            line-height: 1.6;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #c3e6cb;
        }

        .success-message h3 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Publication des Pages</h1>
            <p>Régénérez vos fichiers HTML depuis le contenu de la base de données</p>
        </div>

        <div class="content">
            <a href="content-editor.php" class="back-link">← Retour à l'éditeur</a>

            <?php if (!$isPublishing): ?>
                <div class="warning-box">
                    <h3>⚠️ Important - Lisez avant de publier</h3>
                    <p><strong>Cette action va :</strong></p>
                    <ul>
                        <li>Régénérer tous les fichiers HTML depuis le contenu de la base de données</li>
                        <li>Créer une sauvegarde (.backup) de chaque fichier avant modification</li>
                        <li>Remplacer les titres, meta descriptions, et H1 par le nouveau contenu</li>
                        <li>Les changements seront visibles immédiatement sur votre site</li>
                    </ul>
                    <p style="margin-top: 15px;"><strong>Note:</strong> La structure HTML, le CSS et le JavaScript resteront intacts.
                    Seul le contenu textuel sera mis à jour.</p>
                </div>

                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir publier les modifications ? Cette action va régénérer tous les fichiers HTML.');">
                    <button type="submit" name="publish" class="btn btn-publish">
                        🚀 Publier les Modifications
                    </button>
                    <a href="content-editor.php" class="btn btn-cancel">Annuler</a>
                </form>

            <?php else: ?>
                <div class="success-message">
                    <h3>✅ Publication terminée !</h3>
                    <p>Les fichiers HTML ont été régénérés avec le nouveau contenu.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value success"><?php echo $stats['success']; ?></div>
                        <div class="stat-label">Succès</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value error"><?php echo $stats['errors']; ?></div>
                        <div class="stat-label">Erreurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value skipped"><?php echo $stats['skipped']; ?></div>
                        <div class="stat-label">Ignorés</div>
                    </div>
                </div>

                <?php if (!empty($publishLog)): ?>
                    <h3 style="margin-top: 30px; margin-bottom: 15px;">📋 Journal de publication</h3>
                    <div class="log-container">
                        <?php foreach ($publishLog as $log): ?>
                            <div><?php echo htmlspecialchars($log); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 30px;">
                    <a href="content-editor.php" class="btn btn-publish">← Retour à l'éditeur</a>
                    <a href="index.php" class="btn btn-cancel">Retour au Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
