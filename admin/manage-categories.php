<?php
/**
 * FLARE CUSTOM - Gestion complète des pages catégories
 * Permet d'éditer les textes, SEO, et sélectionner les produits pour chaque catégorie
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Vérifier la connexion admin
if (!isset($_SESSION['admin_user'])) {
    header('Location: admin.php?page=login');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Charger toutes les pages catégories depuis les fichiers HTML
$categoriesDir = __DIR__ . '/../pages/products/';
$categoryFiles = glob($categoriesDir . '*.html');

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_category') {
        $slug = $_POST['slug'] ?? '';
        $title = $_POST['title'] ?? '';
        $metaTitle = $_POST['meta_title'] ?? '';
        $metaDescription = $_POST['meta_description'] ?? '';
        $heroEyebrow = $_POST['hero_eyebrow'] ?? '';
        $heroTitle = $_POST['hero_title'] ?? '';
        $heroSubtitle = $_POST['hero_subtitle'] ?? '';
        $sectionTitle = $_POST['section_title'] ?? '';
        $sectionDescription = $_POST['section_description'] ?? '';

        // Charger les filtres produits
        $filters = [
            'sport' => $_POST['filter_sport'] ?? '',
            'famille' => $_POST['filter_famille'] ?? '',
            'included_ids' => [],
            'excluded_ids' => []
        ];

        $selectedProducts = $_POST['selected_products'] ?? [];
        if (!empty($selectedProducts)) {
            $filters['included_ids'] = array_map('intval', $selectedProducts);
        }

        // Sauvegarder ou mettre à jour dans la BDD
        $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing = $stmt->fetch();

        $categoryData = json_encode([
            'hero_eyebrow' => $heroEyebrow,
            'hero_title' => $heroTitle,
            'hero_subtitle' => $heroSubtitle,
            'section_title' => $sectionTitle,
            'section_description' => $sectionDescription
        ], JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE pages SET title = ?, meta_title = ?, meta_description = ?, product_filters = ?, excerpt = ? WHERE slug = ?");
            $stmt->execute([$title, $metaTitle, $metaDescription, json_encode($filters), $categoryData, $slug]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description, product_filters, excerpt) VALUES (?, ?, 'category', 'published', ?, ?, ?, ?)");
            $stmt->execute([$slug, $title, $metaTitle, $metaDescription, json_encode($filters), $categoryData]);
        }

        $message = "Page catégorie '$title' sauvegardée !";
    }

    if ($action === 'create_sport_pages') {
        // Créer automatiquement des pages pour chaque sport
        $sports = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);

        $created = 0;
        foreach ($sports as $sport) {
            $slug = 'equipement-' . slugify($sport) . '-personnalise-sublimation';
            $title = "Équipement $sport Personnalisé";

            // Vérifier si existe déjà
            $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                $filters = json_encode(['sport' => $sport]);
                $metaTitle = "$title | Sublimation sur mesure - FLARE CUSTOM";
                $metaDesc = "Équipements $sport personnalisés en sublimation. Maillots, shorts, survêtements aux couleurs de votre club. Devis gratuit sous 48h.";

                $categoryData = json_encode([
                    'hero_eyebrow' => getEmojiForSport($sport) . ' ' . $sport,
                    'hero_title' => "Équipements $sport",
                    'hero_subtitle' => 'Personnalisés Sublimation',
                    'section_title' => "Nos équipements $sport",
                    'section_description' => "Découvrez notre gamme complète d'équipements $sport personnalisés. Tissus techniques, personnalisation illimitée en sublimation."
                ], JSON_UNESCAPED_UNICODE);

                $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description, product_filters, excerpt) VALUES (?, ?, 'category', 'published', ?, ?, ?, ?)");
                $stmt->execute([$slug, $title, $metaTitle, $metaDesc, $filters, $categoryData]);
                $created++;
            }
        }
        $message = "$created nouvelles pages sport créées !";
    }

    if ($action === 'create_famille_pages') {
        // Créer des pages pour chaque famille de produits
        $familles = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);

        $created = 0;
        foreach ($familles as $famille) {
            $slug = slugify($famille) . 's-sport-personnalises';
            $title = "$famille" . "s Sport Personnalisés";

            $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                $filters = json_encode(['famille' => $famille]);
                $metaTitle = "$title | Sublimation sur mesure - FLARE CUSTOM";
                $metaDesc = "$famille" . "s sportifs personnalisés en sublimation totale. Design sur mesure, tissus techniques respirants. Devis gratuit.";

                $categoryData = json_encode([
                    'hero_eyebrow' => '👕 ' . $famille,
                    'hero_title' => $famille . "s Sport",
                    'hero_subtitle' => 'Personnalisés Sublimation',
                    'section_title' => "Nos $famille" . "s",
                    'section_description' => "Large choix de $famille" . "s sportifs personnalisables. Sublimation totale, couleurs illimitées, tissus techniques."
                ], JSON_UNESCAPED_UNICODE);

                $stmt = $pdo->prepare("INSERT INTO pages (slug, title, type, status, meta_title, meta_description, product_filters, excerpt) VALUES (?, ?, 'category', 'published', ?, ?, ?, ?)");
                $stmt->execute([$slug, $title, $metaTitle, $metaDesc, $filters, $categoryData]);
                $created++;
            }
        }
        $message = "$created nouvelles pages famille créées !";
    }
}

// Charger les données pour l'édition
$editSlug = $_GET['edit'] ?? null;
$editPage = null;
$editFilters = [];
$editData = [];

if ($editSlug) {
    // Charger depuis la BDD
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$editSlug]);
    $editPage = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editPage) {
        $editFilters = json_decode($editPage['product_filters'] ?? '{}', true) ?: [];
        $editData = json_decode($editPage['excerpt'] ?? '{}', true) ?: [];
    }

    // Charger depuis le fichier HTML si pas en BDD
    $htmlFile = $categoriesDir . $editSlug . '.html';
    if (file_exists($htmlFile) && !$editPage) {
        $html = file_get_contents($htmlFile);

        // Extraire les infos du HTML
        preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch);
        preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $h1Match);

        $editPage = [
            'slug' => $editSlug,
            'title' => $titleMatch[1] ?? ucwords(str_replace('-', ' ', $editSlug)),
            'meta_title' => $titleMatch[1] ?? '',
            'meta_description' => ''
        ];
    }
}

// Récupérer les sports et familles pour les filtres
$sports = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport IS NOT NULL AND sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
$familles = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille IS NOT NULL AND famille != '' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les pages catégories en BDD
$categoryPages = $pdo->query("SELECT * FROM pages WHERE type = 'category' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Fonctions utilitaires
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function getEmojiForSport($sport) {
    $emojis = [
        'Football' => '⚽',
        'Rugby' => '🏉',
        'Basketball' => '🏀',
        'Handball' => '🤾',
        'Volleyball' => '🏐',
        'Running' => '🏃',
        'Cyclisme' => '🚴',
        'Triathlon' => '🏊',
        'Pétanque' => '⚫'
    ];
    return $emojis[$sport] ?? '🎽';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Catégories - FLARE CUSTOM Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --success: #10b981;
            --warning: #f59e0b;
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
            margin-bottom: 32px; flex-wrap: wrap; gap: 16px;
        }
        .header h1 { font-size: 28px; color: var(--gray-900); }
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
        .btn-sm { padding: 6px 12px; font-size: 13px; }

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
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-header h2 { font-size: 18px; color: var(--gray-900); }
        .card-body { padding: 24px; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--gray-200);
        }
        .table th { font-weight: 600; color: var(--gray-700); font-size: 13px; }
        .table tr:hover { background: var(--gray-50); }

        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #d1fae5; color: #065f46; }

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

        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }

        .products-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px; max-height: 400px; overflow-y: auto; padding: 8px;
            background: var(--gray-50); border-radius: 8px;
        }

        .product-mini {
            background: #fff; border: 2px solid var(--gray-200); border-radius: 8px;
            padding: 8px; cursor: pointer; transition: all 0.2s; text-align: center;
        }
        .product-mini:hover { border-color: var(--primary); }
        .product-mini.selected { border-color: var(--success); background: #ecfdf5; }
        .product-mini img {
            width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 6px;
        }
        .product-mini .name { font-size: 11px; font-weight: 500; color: var(--gray-700); line-height: 1.2; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 14px; color: var(--gray-500); margin-top: 4px; }

        .back-link { color: var(--gray-500); text-decoration: none; margin-bottom: 20px; display: inline-block; }
        .back-link:hover { color: var(--primary); }

        .toolbar { display: flex; gap: 12px; margin-bottom: 16px; align-items: center; }
        .search-box { padding: 8px 14px; border: 1px solid var(--gray-300); border-radius: 8px; width: 250px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestion des Pages Catégories</h1>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-secondary">← Retour Admin</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="create_sport_pages">
                    <button type="submit" class="btn btn-success">Créer pages Sports</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="create_famille_pages">
                    <button type="submit" class="btn btn-success">Créer pages Familles</button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($categoryFiles) ?></div>
                <div class="stat-label">Fichiers HTML catégories</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($categoryPages) ?></div>
                <div class="stat-label">Pages en base de données</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($sports) ?></div>
                <div class="stat-label">Sports disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($familles) ?></div>
                <div class="stat-label">Familles produits</div>
            </div>
        </div>

        <?php if ($editSlug && $editPage): ?>
        <!-- EDITION D'UNE PAGE -->
        <a href="manage-categories.php" class="back-link">← Retour à la liste</a>

        <div class="card">
            <div class="card-header">
                <h2>Éditer: <?= htmlspecialchars($editPage['title'] ?? $editSlug) ?></h2>
                <a href="/categorie/<?= htmlspecialchars($editSlug) ?>" target="_blank" class="btn btn-secondary btn-sm">Voir la page</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_category">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($editSlug) ?>">

                    <h3 style="margin-bottom: 20px; color: var(--gray-700);">Informations générales</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre de la page</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editPage['title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>URL (slug)</label>
                            <input type="text" class="form-control" value="/categorie/<?= htmlspecialchars($editSlug) ?>" disabled>
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: var(--gray-700);">SEO</h3>
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($editPage['meta_title'] ?? '') ?>" placeholder="Titre pour Google (50-60 caractères)">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3" placeholder="Description pour Google (150-160 caractères)"><?= htmlspecialchars($editPage['meta_description'] ?? '') ?></textarea>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: var(--gray-700);">Contenu Hero</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Eyebrow (petit texte au-dessus)</label>
                            <input type="text" name="hero_eyebrow" class="form-control" value="<?= htmlspecialchars($editData['hero_eyebrow'] ?? '') ?>" placeholder="Ex: ⚽ Football">
                        </div>
                        <div class="form-group">
                            <label>Titre Hero</label>
                            <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($editData['hero_title'] ?? '') ?>" placeholder="Ex: Équipements Football">
                        </div>
                        <div class="form-group">
                            <label>Sous-titre Hero</label>
                            <input type="text" name="hero_subtitle" class="form-control" value="<?= htmlspecialchars($editData['hero_subtitle'] ?? '') ?>" placeholder="Ex: Personnalisés Sublimation">
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: var(--gray-700);">Section Produits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Titre section</label>
                            <input type="text" name="section_title" class="form-control" value="<?= htmlspecialchars($editData['section_title'] ?? '') ?>" placeholder="Ex: Nos équipements football">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description section</label>
                        <textarea name="section_description" class="form-control" rows="2" placeholder="Description affichée sous le titre"><?= htmlspecialchars($editData['section_description'] ?? '') ?></textarea>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: var(--gray-700);">Filtres Produits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Filtrer par Sport</label>
                            <select name="filter_sport" class="form-control">
                                <option value="">-- Tous les sports --</option>
                                <?php foreach ($sports as $sport): ?>
                                <option value="<?= htmlspecialchars($sport) ?>" <?= ($editFilters['sport'] ?? '') === $sport ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sport) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Filtrer par Famille</label>
                            <select name="filter_famille" class="form-control">
                                <option value="">-- Toutes les familles --</option>
                                <?php foreach ($familles as $famille): ?>
                                <option value="<?= htmlspecialchars($famille) ?>" <?= ($editFilters['famille'] ?? '') === $famille ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($famille) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: var(--gray-700);">Sélection manuelle des produits (optionnel)</h3>
                    <p style="color: var(--gray-500); margin-bottom: 16px; font-size: 14px;">
                        Si vous sélectionnez des produits ci-dessous, seuls ceux-ci seront affichés. Sinon, les filtres ci-dessus seront utilisés.
                    </p>

                    <?php
                    // Charger les produits pour la sélection
                    $allProducts = $pdo->query("SELECT id, reference, nom, photo_1, sport, famille FROM products WHERE active = 1 ORDER BY sport, famille, nom LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
                    $includedIds = $editFilters['included_ids'] ?? [];
                    ?>

                    <div class="toolbar">
                        <input type="text" class="search-box" placeholder="Rechercher un produit..." id="searchProducts">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="selectAll()">Tout sélectionner</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="deselectAll()">Tout désélectionner</button>
                        <span style="margin-left: auto; color: var(--gray-500);">
                            <span id="selectedCount"><?= count($includedIds) ?></span> produit(s) sélectionné(s)
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
                            <div class="name"><?= htmlspecialchars(mb_substr($product['nom'], 0, 40)) ?></div>
                            <input type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>"
                                   style="display:none;" <?= $isSelected ? 'checked' : '' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 30px; text-align: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 14px 40px; font-size: 16px;">
                            Sauvegarder les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- LISTE DES PAGES -->
        <div class="card">
            <div class="card-header">
                <h2>Pages Catégories en base de données</h2>
            </div>
            <div class="card-body">
                <?php if (empty($categoryPages)): ?>
                <p style="color: var(--gray-500); text-align: center; padding: 40px;">
                    Aucune page catégorie configurée. Utilisez les boutons ci-dessus pour créer automatiquement des pages.
                </p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Slug</th>
                            <th>Filtres</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryPages as $page):
                            $filters = json_decode($page['product_filters'] ?? '{}', true);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($page['title']) ?></strong></td>
                            <td><a href="/categorie/<?= htmlspecialchars($page['slug']) ?>" target="_blank" style="color: var(--primary);">/categorie/<?= htmlspecialchars($page['slug']) ?></a></td>
                            <td>
                                <?php if (!empty($filters['sport'])): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($filters['sport']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($filters['famille'])): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($filters['famille']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($filters['included_ids'])): ?>
                                <span class="badge badge-success"><?= count($filters['included_ids']) ?> produits</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?= urlencode($page['slug']) ?>" class="btn btn-secondary btn-sm">Modifier</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Liste des fichiers HTML existants -->
        <div class="card">
            <div class="card-header">
                <h2>Fichiers HTML disponibles (pages/products/)</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>URL</th>
                            <th>En BDD</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryFiles as $file):
                            $filename = basename($file, '.html');
                            // Vérifier si en BDD
                            $inDb = false;
                            foreach ($categoryPages as $p) {
                                if ($p['slug'] === $filename) {
                                    $inDb = true;
                                    break;
                                }
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars(basename($file)) ?></td>
                            <td><a href="/categorie/<?= htmlspecialchars($filename) ?>" target="_blank" style="color: var(--primary);">/categorie/<?= htmlspecialchars($filename) ?></a></td>
                            <td>
                                <?php if ($inDb): ?>
                                <span class="badge badge-success">Configuré</span>
                                <?php else: ?>
                                <span class="badge" style="background: var(--gray-200); color: var(--gray-600);">Non configuré</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?= urlencode($filename) ?>" class="btn btn-secondary btn-sm">Configurer</a>
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
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = card.classList.contains('selected');
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
        const count = document.querySelectorAll('.product-mini.selected').length;
        document.getElementById('selectedCount').textContent = count;
    }

    document.getElementById('searchProducts')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.product-mini').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(search) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
