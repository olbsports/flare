<?php
/**
 * FLARE CUSTOM - Éditeur de Contenu
 * Interface pour modifier le contenu des pages stocké dans la BDD
 */

require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

// Récupère tous les content blocks
$stmt = $db->query("
    SELECT block_key, titre, block_type, active, updated_at
    FROM content_blocks
    ORDER BY block_key ASC
");
$blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si on édite un block spécifique
$currentBlock = null;
$currentContent = null;

if (isset($_GET['block'])) {
    $stmt = $db->prepare("SELECT * FROM content_blocks WHERE block_key = ?");
    $stmt->execute([$_GET['block']]);
    $currentBlock = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($currentBlock) {
        $currentContent = json_decode($currentBlock['contenu'], true);
    }
}

// Sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $blockKey = $_POST['block_key'];
        $titre = $_POST['titre'];

        // Reconstruit le JSON du contenu
        $content = [
            'title' => $_POST['content_title'] ?? '',
            'h1' => $_POST['content_h1'] ?? '',
            'meta_description' => $_POST['content_meta_description'] ?? '',
            'full_text' => $_POST['content_full_text'] ?? '',
            'paragraphs' => isset($_POST['content_paragraphs']) ? explode("\n\n", $_POST['content_paragraphs']) : [],
            'lists' => json_decode($_POST['content_lists'] ?? '[]', true),
            'images' => json_decode($_POST['content_images'] ?? '[]', true),
            'tables' => json_decode($_POST['content_tables'] ?? '[]', true),
            'links' => json_decode($_POST['content_links'] ?? '[]', true),
        ];

        $contentJson = json_encode($content, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare("
            UPDATE content_blocks
            SET titre = ?, contenu = ?, updated_at = NOW()
            WHERE block_key = ?
        ");

        $stmt->execute([$titre, $contentJson, $blockKey]);

        header("Location: content-editor.php?block=" . urlencode($blockKey) . "&saved=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur de Contenu - FLARE CUSTOM Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: #fff;
            border-right: 1px solid #e5e5e7;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: white;
        }

        .sidebar-header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.9;
        }

        .back-link {
            display: block;
            padding: 15px 20px;
            background: #f9f9f9;
            border-bottom: 1px solid #e5e5e7;
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        .back-link:hover {
            background: #f0f0f0;
        }

        .search-box {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e5e7;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .blocks-list {
            padding: 10px 0;
        }

        .block-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: block;
            color: #333;
        }

        .block-item:hover {
            background: #f9f9f9;
        }

        .block-item.active {
            background: #fff5f2;
            border-left: 3px solid #FF4B26;
        }

        .block-item-key {
            font-size: 11px;
            color: #999;
            margin-bottom: 3px;
        }

        .block-item-title {
            font-size: 14px;
            font-weight: 500;
        }

        .block-item-date {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .editor-header h2 {
            font-size: 28px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #FF4B26;
            color: white;
        }

        .btn-primary:hover {
            background: #E63910;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .editor-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #FF4B26;
            padding-bottom: 10px;
            border-bottom: 2px solid #FF4B26;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group textarea.large {
            min-height: 250px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF4B26;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #004085;
        }

        .json-editor {
            background: #1d1d1f;
            color: #0f0;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }

        .char-count {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>📝 Éditeur de Contenu</h1>
                <p>Modifiez le contenu de vos pages</p>
            </div>

            <a href="index.php" class="back-link">← Retour au Dashboard</a>

            <div class="search-box">
                <input type="text" id="searchBlocks" placeholder="🔍 Rechercher...">
            </div>

            <div class="blocks-list" id="blocksList">
                <?php foreach ($blocks as $block): ?>
                    <a href="content-editor.php?block=<?php echo urlencode($block['block_key']); ?>"
                       class="block-item <?php echo ($currentBlock && $currentBlock['block_key'] === $block['block_key']) ? 'active' : ''; ?>">
                        <div class="block-item-key"><?php echo htmlspecialchars($block['block_key']); ?></div>
                        <div class="block-item-title"><?php echo htmlspecialchars($block['titre']); ?></div>
                        <div class="block-item-date">
                            Modifié: <?php echo date('d/m/Y H:i', strtotime($block['updated_at'])); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (!$currentBlock): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3>Sélectionnez une page à éditer</h3>
                    <p>Choisissez une page dans la liste de gauche pour modifier son contenu</p>
                </div>
            <?php else: ?>
                <div class="editor-header">
                    <h2>Édition : <?php echo htmlspecialchars($currentBlock['titre']); ?></h2>
                    <div>
                        <a href="publish-pages.php" class="btn btn-success">🚀 Publier les Modifications</a>
                    </div>
                </div>

                <?php if (isset($_GET['saved'])): ?>
                    <div class="success-message">
                        ✅ Modifications enregistrées avec succès ! Cliquez sur "Publier" pour mettre à jour le site.
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>📌 Important :</strong> Les modifications sont sauvegardées dans la base de données.
                    Pour qu'elles apparaissent sur votre site, cliquez sur le bouton "Publier les Modifications" pour régénérer les fichiers HTML.
                </div>

                <form method="POST" class="editor-form">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="block_key" value="<?php echo htmlspecialchars($currentBlock['block_key']); ?>">

                    <!-- Meta Données -->
                    <div class="form-section">
                        <h3>📋 Informations de la Page</h3>

                        <div class="form-group">
                            <label>Titre de la page (admin)</label>
                            <input type="text" name="titre" value="<?php echo htmlspecialchars($currentBlock['titre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Title (balise &lt;title&gt;)</label>
                            <input type="text" name="content_title" value="<?php echo htmlspecialchars($currentContent['title'] ?? ''); ?>">
                            <div class="char-count"><?php echo strlen($currentContent['title'] ?? ''); ?> caractères</div>
                        </div>

                        <div class="form-group">
                            <label>H1 Principal</label>
                            <input type="text" name="content_h1" value="<?php echo htmlspecialchars($currentContent['h1'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="content_meta_description"><?php echo htmlspecialchars($currentContent['meta_description'] ?? ''); ?></textarea>
                            <div class="char-count"><?php echo strlen($currentContent['meta_description'] ?? ''); ?> / 160 caractères recommandés</div>
                        </div>
                    </div>

                    <!-- Contenu Principal -->
                    <div class="form-section">
                        <h3>📝 Contenu Textuel</h3>

                        <div class="form-group">
                            <label>Paragraphes (séparez chaque paragraphe par une ligne vide)</label>
                            <textarea name="content_paragraphs" class="large"><?php
                                if (!empty($currentContent['paragraphs'])) {
                                    echo htmlspecialchars(implode("\n\n", $currentContent['paragraphs']));
                                }
                            ?></textarea>
                        </div>
                    </div>

                    <!-- Listes -->
                    <?php if (!empty($currentContent['lists'])): ?>
                        <div class="form-section">
                            <h3>📋 Listes</h3>
                            <div class="form-group">
                                <label>Listes (format JSON)</label>
                                <textarea name="content_lists" class="large"><?php echo htmlspecialchars(json_encode($currentContent['lists'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Images -->
                    <?php if (!empty($currentContent['images'])): ?>
                        <div class="form-section">
                            <h3>🖼️ Images</h3>
                            <div class="form-group">
                                <label>Images (format JSON)</label>
                                <textarea name="content_images" class="large"><?php echo htmlspecialchars(json_encode($currentContent['images'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tableaux -->
                    <?php if (!empty($currentContent['tables'])): ?>
                        <div class="form-section">
                            <h3>📊 Tableaux</h3>
                            <div class="form-group">
                                <label>Tableaux (format JSON)</label>
                                <textarea name="content_tables" class="large"><?php echo htmlspecialchars(json_encode($currentContent['tables'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Aperçu JSON complet -->
                    <div class="form-section">
                        <h3>🔍 Aperçu du contenu complet (lecture seule)</h3>
                        <div class="json-editor">
                            <pre><?php echo htmlspecialchars(json_encode($currentContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                        <a href="content-editor.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Recherche de blocks
        document.getElementById('searchBlocks').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.block-item');

            items.forEach(item => {
                const key = item.querySelector('.block-item-key').textContent.toLowerCase();
                const title = item.querySelector('.block-item-title').textContent.toLowerCase();

                if (key.includes(search) || title.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Auto-save warning
        let hasChanges = false;
        const formInputs = document.querySelectorAll('input, textarea');

        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                hasChanges = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Reset hasChanges après sauvegarde
        document.querySelector('form')?.addEventListener('submit', () => {
            hasChanges = false;
        });
    </script>
</body>
</html>
