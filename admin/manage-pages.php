<?php
/**
 * FLARE CUSTOM - Gestionnaire de Pages Unifié
 * Permet d'éditer toutes les pages (info, catégories, blog) individuellement
 * Préserve le design original de chaque page
 */

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_user'])) {
    header('Location: admin.php?page=login');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Type de page à gérer
$pageType = $_GET['type'] ?? 'info';
$editSlug = $_GET['edit'] ?? null;

// Dossiers des pages
$dirs = [
    'info' => __DIR__ . '/../pages/info/',
    'category' => __DIR__ . '/../pages/products/',
    'blog' => __DIR__ . '/../pages/blog/'
];

// Charger les fichiers HTML
$htmlFiles = [];
if (isset($dirs[$pageType])) {
    $files = glob($dirs[$pageType] . '*.html');
    foreach ($files as $file) {
        $slug = basename($file, '.html');
        $htmlFiles[$slug] = $file;
    }
}

// Traiter les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $slug = $_POST['slug'] ?? '';

    if ($action === 'save_page' && $slug) {
        $title = $_POST['title'] ?? '';
        $metaTitle = $_POST['meta_title'] ?? '';
        $metaDescription = $_POST['meta_description'] ?? '';
        $type = $_POST['page_type'] ?? 'info';

        // Données spécifiques selon le type
        $extraData = [];
        if ($type === 'category') {
            $extraData = [
                'hero_eyebrow' => $_POST['hero_eyebrow'] ?? '',
                'hero_title' => $_POST['hero_title'] ?? '',
                'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
                'section_title' => $_POST['section_title'] ?? '',
                'section_description' => $_POST['section_description'] ?? '',
                'filter_sport' => $_POST['filter_sport'] ?? '',
                'filter_famille' => $_POST['filter_famille'] ?? ''
            ];

            // Produits sélectionnés
            $selectedProducts = $_POST['selected_products'] ?? [];
            $filters = [
                'sport' => $extraData['filter_sport'],
                'famille' => $extraData['filter_famille'],
                'included_ids' => !empty($selectedProducts) ? array_map('intval', $selectedProducts) : []
            ];
        } else {
            $extraData = [
                'hero_title' => $_POST['hero_title'] ?? '',
                'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
                'section_content' => $_POST['section_content'] ?? ''
            ];
            $filters = [];
        }

        // Vérifier si la page existe en BDD
        $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing = $stmt->fetch();

        $contentJson = json_encode($extraData, JSON_UNESCAPED_UNICODE);
        $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE pages SET title = ?, meta_title = ?, meta_description = ?, type = ?, excerpt = ?, product_filters = ? WHERE slug = ?");
            $stmt->execute([$title, $metaTitle, $metaDescription, $type, $contentJson, $filtersJson, $slug]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description, excerpt, product_filters) VALUES (?, ?, ?, 'published', ?, ?, ?, ?)");
            $stmt->execute([$slug, $title, $type, $metaTitle, $metaDescription, $contentJson, $filtersJson]);
        }

        $message = "Page '$title' sauvegardée avec succès !";
    }

    if ($action === 'import_all_pages') {
        $imported = 0;
        $targetType = $_POST['import_type'] ?? 'info';
        $targetDir = $dirs[$targetType] ?? null;

        if ($targetDir) {
            $files = glob($targetDir . '*.html');
            foreach ($files as $file) {
                $slug = basename($file, '.html');

                // Vérifier si déjà en BDD
                $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
                $stmt->execute([$slug]);
                if (!$stmt->fetch()) {
                    // Extraire les infos du HTML
                    $html = file_get_contents($file);
                    preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
                    preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $descMatch);

                    $title = $titleMatch[1] ?? ucwords(str_replace('-', ' ', $slug));
                    $metaDesc = $descMatch[1] ?? '';

                    $dbType = $targetType === 'category' ? 'category' : 'info';

                    $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description) VALUES (?, ?, ?, 'published', ?, ?)");
                    $stmt->execute([$slug, $title, $dbType, $title, $metaDesc]);
                    $imported++;
                }
            }
        }
        $message = "$imported pages importées !";
    }
}

// Charger les données pour l'édition
$editPage = null;
$editData = [];
$editFilters = [];

if ($editSlug) {
    // Charger depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$editSlug]);
    $editPage = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editPage) {
        $editData = json_decode($editPage['excerpt'] ?? '{}', true) ?: [];
        $editFilters = json_decode($editPage['product_filters'] ?? '{}', true) ?: [];
    }

    // Si pas en BDD, extraire du fichier HTML
    $htmlFile = ($dirs[$pageType] ?? '') . $editSlug . '.html';
    if (!$editPage && file_exists($htmlFile)) {
        $html = file_get_contents($htmlFile);
        preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
        preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $descMatch);

        // Extraire le contenu hero selon le type de page
        preg_match('/<span[^>]*class="[^"]*hero[^"]*eyebrow[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $eyebrowMatch);
        preg_match('/<h1[^>]*class="[^"]*hero[^"]*title[^"]*"[^>]*>([^<]+)<\/h1>/i', $html, $heroTitleMatch);
        preg_match('/<p[^>]*class="[^"]*hero[^"]*subtitle[^"]*"[^>]*>([^<]+)<\/p>/i', $html, $heroSubtitleMatch);

        $editPage = [
            'slug' => $editSlug,
            'title' => $titleMatch[1] ?? ucwords(str_replace('-', ' ', $editSlug)),
            'meta_title' => $titleMatch[1] ?? '',
            'meta_description' => $descMatch[1] ?? '',
            'type' => $pageType === 'category' ? 'category' : 'info'
        ];

        $editData = [
            'hero_eyebrow' => $eyebrowMatch[1] ?? '',
            'hero_title' => $heroTitleMatch[1] ?? '',
            'hero_subtitle' => $heroSubtitleMatch[1] ?? ''
        ];
    }
}

// Charger toutes les pages en BDD pour comparaison
$dbPages = [];
$stmt = $pdo->query("SELECT slug, title, type, meta_title FROM pages");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbPages[$row['slug']] = $row;
}

// Récupérer sports et familles pour les filtres
$sports = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
$familles = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);

// URLs selon le type
$urlPrefixes = [
    'info' => '/info/',
    'category' => '/categorie/',
    'blog' => '/blog/'
];
$urlPrefix = $urlPrefixes[$pageType] ?? '/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pages - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1a1a2e;
            --sidebar-bg: #1e1e2d;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-50); min-height: 100vh; }

        .container { max-width: 1400px; margin: 0 auto; padding: 24px; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
        }
        .header h1 { font-size: 24px; color: var(--gray-900); }
        .header-actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn {
            padding: 10px 20px; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-info { background: var(--info); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .tabs {
            display: flex; gap: 4px; margin-bottom: 24px;
            background: #fff; padding: 6px; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .tab {
            padding: 12px 24px; border-radius: 8px; text-decoration: none;
            color: var(--gray-600); font-weight: 600; transition: all 0.2s;
        }
        .tab:hover { background: var(--gray-100); }
        .tab.active { background: var(--primary); color: #fff; }

        .alert {
            padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 500;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        .card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;
        }
        .card-header {
            padding: 20px 24px; border-bottom: 1px solid var(--gray-200);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
        }
        .card-header h2 { font-size: 18px; color: var(--gray-900); }
        .card-body { padding: 24px; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--gray-200);
        }
        .table th { font-weight: 600; color: var(--gray-700); font-size: 13px; background: var(--gray-50); }
        .table tr:hover { background: var(--gray-50); }

        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-gray { background: var(--gray-200); color: var(--gray-600); }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 14px; font-weight: 600;
            color: var(--gray-700); margin-bottom: 6px;
        }
        .form-control {
            width: 100%; padding: 10px 14px;
            border: 1px solid var(--gray-300); border-radius: 8px;
            font-size: 14px; transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { min-height: 100px; resize: vertical; }

        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

        .section-title {
            font-size: 16px; font-weight: 700; color: var(--gray-800);
            margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);
        }

        .products-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px; max-height: 350px; overflow-y: auto; padding: 8px;
            background: var(--gray-50); border-radius: 8px;
        }

        .product-mini {
            background: #fff; border: 2px solid var(--gray-200); border-radius: 8px;
            padding: 6px; cursor: pointer; transition: all 0.2s; text-align: center;
        }
        .product-mini:hover { border-color: var(--primary); }
        .product-mini.selected { border-color: var(--success); background: #ecfdf5; }
        .product-mini img {
            width: 100%; height: 70px; object-fit: cover; border-radius: 4px; margin-bottom: 4px;
        }
        .product-mini .name { font-size: 10px; font-weight: 500; color: var(--gray-700); line-height: 1.2; }

        .back-link { color: var(--gray-500); text-decoration: none; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 6px; }
        .back-link:hover { color: var(--primary); }

        .toolbar { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; flex-wrap: wrap; }
        .search-box { padding: 8px 12px; border: 1px solid var(--gray-300); border-radius: 6px; width: 200px; font-size: 13px; }

        .preview-link { color: var(--primary); text-decoration: none; font-size: 13px; }
        .preview-link:hover { text-decoration: underline; }

        .count-badge {
            background: var(--primary); color: #fff;
            padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des Pages</h1>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-secondary">← Retour Admin</a>
            </div>
        </div>

        <!-- Onglets -->
        <div class="tabs">
            <a href="?type=info" class="tab <?= $pageType === 'info' ? 'active' : '' ?>">
                Pages Info <span class="count-badge"><?= count(glob($dirs['info'] . '*.html')) ?></span>
            </a>
            <a href="?type=category" class="tab <?= $pageType === 'category' ? 'active' : '' ?>">
                Catégories <span class="count-badge"><?= count(glob($dirs['category'] . '*.html')) ?></span>
            </a>
            <a href="?type=blog" class="tab <?= $pageType === 'blog' ? 'active' : '' ?>">
                Blog <span class="count-badge"><?= count(glob($dirs['blog'] . '*.html')) ?></span>
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($editSlug && $editPage): ?>
        <!-- ============ MODE ÉDITION ============ -->
        <a href="?type=<?= $pageType ?>" class="back-link">← Retour à la liste</a>

        <div class="card">
            <div class="card-header">
                <h2>Éditer : <?= htmlspecialchars($editPage['title'] ?? $editSlug) ?></h2>
                <a href="<?= $urlPrefix . htmlspecialchars($editSlug) ?>" target="_blank" class="btn btn-info btn-sm">
                    Voir la page
                </a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_page">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($editSlug) ?>">
                    <input type="hidden" name="page_type" value="<?= htmlspecialchars($pageType === 'category' ? 'category' : 'info') ?>">

                    <!-- Informations générales -->
                    <h3 class="section-title">Informations générales</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre de la page</label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= htmlspecialchars($editPage['title'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>URL</label>
                            <input type="text" class="form-control"
                                   value="<?= $urlPrefix . htmlspecialchars($editSlug) ?>" disabled
                                   style="background: var(--gray-100);">
                        </div>
                    </div>

                    <!-- SEO -->
                    <h3 class="section-title">SEO - Référencement</h3>
                    <div class="form-group">
                        <label>Meta Title (titre Google)</label>
                        <input type="text" name="meta_title" class="form-control"
                               value="<?= htmlspecialchars($editPage['meta_title'] ?? '') ?>"
                               placeholder="50-60 caractères recommandés">
                        <small style="color: var(--gray-500);">Apparaît dans les résultats Google</small>
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3"
                                  placeholder="150-160 caractères recommandés"><?= htmlspecialchars($editPage['meta_description'] ?? '') ?></textarea>
                        <small style="color: var(--gray-500);">Description affichée sous le titre dans Google</small>
                    </div>

                    <!-- Contenu Hero -->
                    <h3 class="section-title">Contenu Hero (En-tête de page)</h3>
                    <?php if ($pageType === 'category'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Eyebrow (petit texte au-dessus)</label>
                            <input type="text" name="hero_eyebrow" class="form-control"
                                   value="<?= htmlspecialchars($editData['hero_eyebrow'] ?? '') ?>"
                                   placeholder="Ex: ⚽ Football">
                        </div>
                        <div class="form-group">
                            <label>Titre principal</label>
                            <input type="text" name="hero_title" class="form-control"
                                   value="<?= htmlspecialchars($editData['hero_title'] ?? '') ?>"
                                   placeholder="Ex: Équipements Football">
                        </div>
                        <div class="form-group">
                            <label>Sous-titre</label>
                            <input type="text" name="hero_subtitle" class="form-control"
                                   value="<?= htmlspecialchars($editData['hero_subtitle'] ?? '') ?>"
                                   placeholder="Ex: Personnalisés Sublimation">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre principal</label>
                            <input type="text" name="hero_title" class="form-control"
                                   value="<?= htmlspecialchars($editData['hero_title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Sous-titre</label>
                            <input type="text" name="hero_subtitle" class="form-control"
                                   value="<?= htmlspecialchars($editData['hero_subtitle'] ?? '') ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($pageType === 'category'): ?>
                    <!-- Section Produits (catégories seulement) -->
                    <h3 class="section-title">Section Produits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre de section</label>
                            <input type="text" name="section_title" class="form-control"
                                   value="<?= htmlspecialchars($editData['section_title'] ?? '') ?>"
                                   placeholder="Ex: Nos équipements football">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description de section</label>
                        <textarea name="section_description" class="form-control" rows="2"
                                  placeholder="Description sous le titre"><?= htmlspecialchars($editData['section_description'] ?? '') ?></textarea>
                    </div>

                    <!-- Filtres Produits -->
                    <h3 class="section-title">Filtres Produits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Filtrer par Sport</label>
                            <select name="filter_sport" class="form-control">
                                <option value="">-- Tous --</option>
                                <?php foreach ($sports as $sport): ?>
                                <option value="<?= htmlspecialchars($sport) ?>"
                                        <?= ($editFilters['sport'] ?? '') === $sport ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sport) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Filtrer par Famille</label>
                            <select name="filter_famille" class="form-control">
                                <option value="">-- Toutes --</option>
                                <?php foreach ($familles as $famille): ?>
                                <option value="<?= htmlspecialchars($famille) ?>"
                                        <?= ($editFilters['famille'] ?? '') === $famille ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($famille) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Sélection manuelle -->
                    <h3 class="section-title">Sélection manuelle (optionnel)</h3>
                    <p style="color: var(--gray-500); margin-bottom: 12px; font-size: 13px;">
                        Sélectionnez des produits spécifiques. Si vide, les filtres ci-dessus seront utilisés.
                    </p>

                    <?php
                    $allProducts = $pdo->query("SELECT id, reference, nom, photo_1, sport FROM products WHERE active = 1 ORDER BY sport, nom LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
                    $includedIds = $editFilters['included_ids'] ?? [];
                    ?>

                    <div class="toolbar">
                        <input type="text" class="search-box" placeholder="Rechercher..." id="searchProducts">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()">Tout sélectionner</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">Tout désélectionner</button>
                        <span style="margin-left: auto; color: var(--gray-500); font-size: 13px;">
                            <span id="selectedCount"><?= count($includedIds) ?></span> sélectionné(s)
                        </span>
                    </div>

                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($allProducts as $product):
                            $isSelected = in_array($product['id'], $includedIds);
                        ?>
                        <div class="product-mini <?= $isSelected ? 'selected' : '' ?>"
                             data-id="<?= $product['id'] ?>"
                             data-name="<?= htmlspecialchars(strtolower($product['nom'])) ?>"
                             onclick="toggleProduct(this)">
                            <img src="<?= htmlspecialchars($product['photo_1'] ?: '/assets/images/placeholder.jpg') ?>" alt="" loading="lazy">
                            <div class="name"><?= htmlspecialchars(mb_substr($product['nom'], 0, 30)) ?></div>
                            <input type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>"
                                   style="display:none;" <?= $isSelected ? 'checked' : '' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <!-- Contenu (pages info) -->
                    <h3 class="section-title">Contenu principal</h3>
                    <div class="form-group">
                        <label>Notes / Contenu additionnel</label>
                        <textarea name="section_content" class="form-control" rows="4"
                                  placeholder="Notes ou contenu additionnel..."><?= htmlspecialchars($editData['section_content'] ?? '') ?></textarea>
                        <small style="color: var(--gray-500);">Le design original de la page est préservé. Ces notes sont pour référence.</small>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200); text-align: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 14px 40px;">
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ============ MODE LISTE ============ -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <?php
                    $typeNames = ['info' => 'Pages Info', 'category' => 'Pages Catégories', 'blog' => 'Articles Blog'];
                    echo $typeNames[$pageType] ?? 'Pages';
                    ?>
                </h2>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="import_all_pages">
                    <input type="hidden" name="import_type" value="<?= htmlspecialchars($pageType) ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        Importer toutes en BDD
                    </button>
                </form>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>URL</th>
                            <th>Meta Title</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($htmlFiles as $slug => $file):
                            $inDb = isset($dbPages[$slug]);
                            $dbData = $dbPages[$slug] ?? [];
                            $displayTitle = $dbData['title'] ?? ucwords(str_replace('-', ' ', $slug));
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($displayTitle) ?></strong>
                                <br><small style="color: var(--gray-500);"><?= htmlspecialchars(basename($file)) ?></small>
                            </td>
                            <td>
                                <a href="<?= $urlPrefix . htmlspecialchars($slug) ?>" target="_blank" class="preview-link">
                                    <?= $urlPrefix . htmlspecialchars($slug) ?>
                                </a>
                            </td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($dbData['meta_title'] ?? '-') ?>
                            </td>
                            <td>
                                <?php if ($inDb): ?>
                                <span class="badge badge-success">Configuré</span>
                                <?php else: ?>
                                <span class="badge badge-gray">Non configuré</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?type=<?= $pageType ?>&edit=<?= urlencode($slug) ?>" class="btn btn-secondary btn-sm">
                                    Modifier
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleProduct(card) {
        card.classList.toggle('selected');
        card.querySelector('input[type="checkbox"]').checked = card.classList.contains('selected');
        updateCount();
    }

    function selectAll() {
        document.querySelectorAll('.product-mini:not([style*="display: none"])').forEach(card => {
            card.classList.add('selected');
            card.querySelector('input[type="checkbox"]').checked = true;
        });
        updateCount();
    }

    function deselectAll() {
        document.querySelectorAll('.product-mini').forEach(card => {
            card.classList.remove('selected');
            card.querySelector('input[type="checkbox"]').checked = false;
        });
        updateCount();
    }

    function updateCount() {
        const el = document.getElementById('selectedCount');
        if (el) el.textContent = document.querySelectorAll('.product-mini.selected').length;
    }

    document.getElementById('searchProducts')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.product-mini').forEach(card => {
            card.style.display = card.dataset.name.includes(search) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
