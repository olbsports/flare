<?php
/**
 * FLARE CUSTOM - CSV Import
 * Import de produits depuis CSV
 */

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Product.php';

$productModel = new Product();
$success = '';
$error = '';
$importResults = null;

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $file = $_FILES['csv_file'];

        // Vérification du fichier
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors de l'upload du fichier");
        }

        // Vérification de l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new Exception("Le fichier doit être au format CSV");
        }

        // Import
        $importResults = $productModel->importFromCSV($file['tmp_name']);

        if ($importResults['success']) {
            $success = "Import réussi : " . $importResults['imported'] . " produits importés/mis à jour";
        } else {
            $error = $importResults['error'];
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV - FLARE CUSTOM Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: #1d1d1f;
            color: #fff;
            padding: 20px 0;
            overflow-y: auto;
        }

        .logo {
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 28px;
            letter-spacing: 2px;
            color: #FF4B26;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #FF4B26;
        }

        .nav-icon {
            width: 20px;
            margin-right: 12px;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 40px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h2 {
            font-size: 32px;
            font-weight: 700;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
        }

        .btn-secondary {
            background: #e5e5e7;
            color: #1d1d1f;
        }

        /* Cards */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .card h3 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #FF4B26;
        }

        /* Upload area */
        .upload-area {
            border: 3px dashed #e5e5e7;
            border-radius: 12px;
            padding: 48px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #FF4B26;
            background: #fff5f3;
        }

        .upload-area.dragover {
            border-color: #FF4B26;
            background: #fff5f3;
        }

        .upload-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .upload-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .upload-hint {
            color: #86868b;
            font-size: 14px;
        }

        #csv_file {
            display: none;
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
        }

        /* Info box */
        .info-box {
            background: #f5f5f7;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .info-box h4 {
            margin-bottom: 12px;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin: 8px 0;
        }

        /* Results */
        .results {
            margin-top: 24px;
        }

        .result-stat {
            display: inline-block;
            padding: 16px 24px;
            background: #f5f5f7;
            border-radius: 8px;
            margin-right: 16px;
        }

        .result-stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #FF4B26;
        }

        .result-stat-label {
            font-size: 14px;
            color: #86868b;
            margin-top: 4px;
        }

        .errors-list {
            margin-top: 16px;
            padding: 16px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e5e7;
        }

        .errors-list h4 {
            margin-bottom: 12px;
            color: #c62828;
        }

        .errors-list ul {
            margin-left: 20px;
        }

        .errors-list li {
            margin: 8px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">
            <h1>FLARE CUSTOM</h1>
            <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 4px;">Administration</p>
        </div>

        <a href="index.php" class="nav-item">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>
        <a href="products.php" class="nav-item">
            <span class="nav-icon">📦</span>
            Produits
        </a>
        <a href="categories.php" class="nav-item">
            <span class="nav-icon">📁</span>
            Catégories
        </a>
        <a href="templates.php" class="nav-item">
            <span class="nav-icon">🎨</span>
            Templates
        </a>
        <a href="quotes.php" class="nav-item">
            <span class="nav-icon">💰</span>
            Devis
        </a>
        <a href="pages.php" class="nav-item">
            <span class="nav-icon">📄</span>
            Pages
        </a>
        <a href="media.php" class="nav-item">
            <span class="nav-icon">🖼️</span>
            Médias
        </a>
        <a href="settings.php" class="nav-item">
            <span class="nav-icon">⚙️</span>
            Paramètres
        </a>
        <a href="logout.php" class="nav-item" style="margin-top: 30px; opacity: 0.7;">
            <span class="nav-icon">🚪</span>
            Déconnexion
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h2>Import CSV</h2>
            <a href="products.php" class="btn btn-secondary">← Retour aux produits</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✓ <?php echo htmlspecialchars($success); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>✕ <?php echo htmlspecialchars($error); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="info-box">
            <h4>Format du fichier CSV</h4>
            <p>Le fichier CSV doit contenir les colonnes suivantes (séparées par des points-virgules) :</p>
            <ul>
                <li><strong>REFERENCE_FLARE</strong> - Référence unique du produit (obligatoire)</li>
                <li><strong>TITRE_VENDEUR</strong> - Nom du produit (obligatoire)</li>
                <li><strong>SPORT</strong> - Sport (Football, Basketball, etc.)</li>
                <li><strong>FAMILLE_PRODUIT</strong> - Famille (Maillot, Short, etc.)</li>
                <li><strong>DESCRIPTION</strong> - Description du produit</li>
                <li><strong>TISSU</strong> - Type de tissu</li>
                <li><strong>GRAMMAGE</strong> - Grammage</li>
                <li><strong>QTY_1, QTY_5, QTY_10, QTY_20, QTY_50, QTY_100, QTY_250, QTY_500</strong> - Prix dégressifs</li>
                <li><strong>PHOTO_1</strong> - URL de la photo principale</li>
                <li><strong>GENRE</strong> - Genre (Homme, Femme, Mixte, Enfant)</li>
                <li><strong>FINITION, ETIQUETTES</strong> - Caractéristiques supplémentaires</li>
            </ul>
            <p style="margin-top: 16px;"><strong>Note :</strong> Si un produit avec la même référence existe déjà, il sera mis à jour.</p>
        </div>

        <!-- Upload Form -->
        <div class="card">
            <h3>Importer un fichier CSV</h3>
            <form method="POST" enctype="multipart/form-data" id="upload-form">
                <label for="csv_file" class="upload-area" id="upload-area">
                    <div class="upload-icon">📤</div>
                    <div class="upload-text">Cliquez pour sélectionner un fichier CSV</div>
                    <div class="upload-hint">ou glissez-déposez le fichier ici</div>
                    <div id="file-name" style="margin-top: 16px; color: #FF4B26; font-weight: 600;"></div>
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>

                <div style="text-align: center; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                        📥 Importer les produits
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if ($importResults): ?>
            <div class="card">
                <h3>Résultats de l'import</h3>
                <div class="results">
                    <div class="result-stat">
                        <div class="result-stat-value"><?php echo $importResults['imported']; ?></div>
                        <div class="result-stat-label">Produits importés/mis à jour</div>
                    </div>

                    <?php if (!empty($importResults['errors'])): ?>
                        <div class="errors-list">
                            <h4>⚠️ Erreurs rencontrées (<?php echo count($importResults['errors']); ?>)</h4>
                            <ul>
                                <?php foreach ($importResults['errors'] as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const fileInput = document.getElementById('csv_file');
        const uploadArea = document.getElementById('upload-area');
        const fileName = document.getElementById('file-name');
        const submitBtn = document.getElementById('submit-btn');

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = '📄 ' + this.files[0].name;
                submitBtn.disabled = false;
            }
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = '📄 ' + files[0].name;
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
