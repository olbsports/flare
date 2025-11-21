<?php
/**
 * TEMPLATE DE PAGE DYNAMIQUE - FLARE CUSTOM
 *
 * Cette page charge son contenu depuis la base de données
 * Utilisez-la comme modèle pour créer vos pages dynamiques
 *
 * Usage:
 * 1. Copiez ce fichier et renommez-le (ex: produit.php, about.php)
 * 2. Changez le $blockKey pour correspondre à votre contenu
 * 3. Le contenu sera chargé automatiquement depuis la BDD
 */

require_once __DIR__ . '/config/database.php';

// ========================================
// CONFIGURATION
// ========================================

// Clé du block de contenu à charger
// Exemples: 'page_home', 'product_page_maillot', 'page_about'
$blockKey = $_GET['page'] ?? 'page_home';

// ========================================
// CHARGEMENT DU CONTENU
// ========================================

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT *
        FROM content_blocks
        WHERE block_key = ? AND active = 1
    ");
    $stmt->execute([$blockKey]);

    $block = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$block) {
        http_response_code(404);
        die("Page introuvable");
    }

    // Décode le contenu JSON
    $content = json_decode($block['contenu'], true);

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur de chargement: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($content['title'] ?? $block['titre']); ?> - FLARE CUSTOM</title>
    <meta name="description" content="<?php echo htmlspecialchars($content['meta_description'] ?? ''); ?>">

    <!-- Votre CSS ici -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        h1 {
            font-size: 42px;
            font-weight: 700;
            color: #FF4B26;
            margin-bottom: 20px;
        }

        .content {
            margin-top: 30px;
        }

        .content p {
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.8;
        }

        .content ul, .content ol {
            margin: 20px 0;
            padding-left: 40px;
        }

        .content li {
            margin-bottom: 10px;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .images-grid img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #FF4B26;
            color: white;
            font-weight: 600;
        }

        .debug {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 40px;
            font-family: monospace;
            font-size: 12px;
        }

        .debug h3 {
            margin-bottom: 10px;
            color: #FF4B26;
        }
    </style>
</head>
<body>
    <!-- Votre header ici -->
    <header>
        <!-- Navigation, logo, etc. -->
    </header>

    <main class="container">
        <!-- Titre principal -->
        <h1><?php echo htmlspecialchars($content['h1'] ?? $block['titre']); ?></h1>

        <!-- Contenu principal -->
        <div class="content">
            <?php if (!empty($content['paragraphs'])): ?>
                <?php foreach ($content['paragraphs'] as $paragraph): ?>
                    <p><?php echo nl2br(htmlspecialchars($paragraph)); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($content['lists'])): ?>
                <?php foreach ($content['lists'] as $list): ?>
                    <ul>
                        <?php foreach ($list as $item): ?>
                            <li><?php echo htmlspecialchars($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Images -->
        <?php if (!empty($content['images'])): ?>
            <div class="images-grid">
                <?php foreach ($content['images'] as $image): ?>
                    <img src="<?php echo htmlspecialchars($image['src']); ?>"
                         alt="<?php echo htmlspecialchars($image['alt'] ?? ''); ?>"
                         title="<?php echo htmlspecialchars($image['title'] ?? ''); ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Tableaux -->
        <?php if (!empty($content['tables'])): ?>
            <?php foreach ($content['tables'] as $table): ?>
                <table>
                    <?php foreach ($table as $rowIndex => $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <?php if ($rowIndex === 0): ?>
                                    <th><?php echo htmlspecialchars($cell); ?></th>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- DEBUG (à retirer en production) -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="debug">
                <h3>🐛 Mode Debug</h3>
                <pre><?php print_r($content); ?></pre>
            </div>
        <?php endif; ?>
    </main>

    <!-- Votre footer ici -->
    <footer>
        <!-- Copyright, liens, etc. -->
    </footer>

    <!-- Vos scripts ici -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
