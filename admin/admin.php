<?php
/**
 * FLARE CUSTOM - ADMIN TOUT-EN-UN
 * Un seul fichier pour tout gérer
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

// Page actuelle
$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// Vérifier login (sauf pour login)
if ($page !== 'login' && !isset($_SESSION['admin_user'])) {
    $page = 'login';
}

// Connexion BDD
$pdo = null;
$dbError = null;
try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// Traitement LOGIN
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_user'] = $user;
            header('Location: admin.php?page=dashboard');
            exit;
        } else {
            $loginError = "Identifiants incorrects";
        }
    }
}

// Traitement LOGOUT
if ($page === 'logout') {
    session_destroy();
    header('Location: admin.php?page=login');
    exit;
}

// Traitement des actions
$message = '';
$messageType = '';

if ($action && $pdo) {
    switch ($action) {
        // === PRODUITS ===
        case 'save_product':
            $data = $_POST;
            if ($id) {
                $sql = "UPDATE products SET nom=?, sport=?, famille=?, description=?, description_seo=?,
                        tissu=?, grammage=?, prix_1=?, prix_5=?, prix_10=?, prix_20=?, prix_50=?, prix_100=?,
                        photo_1=?, genre=?, finition=?, meta_title=?, meta_description=?,
                        tabs_config=?, configurator_config=?
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $data['nom'], $data['sport'], $data['famille'], $data['description'], $data['description_seo'],
                    $data['tissu'], $data['grammage'], $data['prix_1'], $data['prix_5'], $data['prix_10'],
                    $data['prix_20'], $data['prix_50'], $data['prix_100'], $data['photo_1'], $data['genre'],
                    $data['finition'], $data['meta_title'], $data['meta_description'],
                    $data['tabs_config'] ?? '{}', $data['configurator_config'] ?? '{}', $id
                ]);
                $message = "Produit mis à jour";
            }
            $messageType = 'success';
            break;

        case 'delete_product':
            $pdo->prepare("UPDATE products SET active=0 WHERE id=?")->execute([$id]);
            $message = "Produit supprimé";
            $messageType = 'success';
            break;

        // === CATEGORIES ===
        case 'save_category':
            $data = $_POST;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categories SET nom=?, slug=?, type=?, description=?, image=? WHERE id=?");
                $stmt->execute([$data['nom'], $data['slug'], $data['type'], $data['description'], $data['image'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (nom, slug, type, description, image, active) VALUES (?,?,?,?,?,1)");
                $stmt->execute([$data['nom'], $data['slug'], $data['type'], $data['description'], $data['image']]);
            }
            $message = "Catégorie sauvegardée";
            $messageType = 'success';
            break;

        case 'delete_category':
            $pdo->prepare("UPDATE categories SET active=0 WHERE id=?")->execute([$id]);
            $message = "Catégorie supprimée";
            $messageType = 'success';
            break;

        // === PAGES ===
        case 'save_page':
            $data = $_POST;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE pages SET title=?, slug=?, content=?, meta_title=?, meta_description=?, status=? WHERE id=?");
                $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['meta_title'], $data['meta_description'], $data['status'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, status, type) VALUES (?,?,?,?,?,?,'page')");
                $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['meta_title'], $data['meta_description'], $data['status']]);
            }
            $message = "Page sauvegardée";
            $messageType = 'success';
            break;

        // === BLOG ===
        case 'save_blog':
            $data = $_POST;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, slug=?, content=?, excerpt=?, category=?, meta_title=?, meta_description=?, status=? WHERE id=?");
                $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['excerpt'], $data['category'], $data['meta_title'], $data['meta_description'], $data['status'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, category, meta_title, meta_description, status, published_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
                $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['excerpt'], $data['category'], $data['meta_title'], $data['meta_description'], $data['status']]);
            }
            $message = "Article sauvegardé";
            $messageType = 'success';
            break;

        // === DEVIS ===
        case 'update_quote_status':
            $stmt = $pdo->prepare("UPDATE quotes SET status=?, notes=? WHERE id=?");
            $stmt->execute([$_POST['status'], $_POST['notes'], $id]);
            $message = "Devis mis à jour";
            $messageType = 'success';
            break;
    }
}

// Récupérer les données selon la page
$data = [];
if ($pdo) {
    try {
        switch ($page) {
            case 'dashboard':
                $data['stats'] = [
                    'products' => $pdo->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn(),
                    'categories' => $pdo->query("SELECT COUNT(*) FROM categories WHERE active=1")->fetchColumn(),
                    'pages' => $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
                    'blog' => $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn(),
                    'quotes' => $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn(),
                ];
                break;

            case 'products':
                $search = $_GET['search'] ?? '';
                $sport = $_GET['sport'] ?? '';
                $sql = "SELECT * FROM products WHERE active=1";
                $params = [];
                if ($search) {
                    $sql .= " AND (nom LIKE ? OR reference LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                if ($sport) {
                    $sql .= " AND sport = ?";
                    $params[] = $sport;
                }
                $sql .= " ORDER BY id DESC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data['products'] = $stmt->fetchAll();
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport != '' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                break;

            case 'product_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
                    $stmt->execute([$id]);
                    $data['product'] = $stmt->fetch();
                }
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport != ''")->fetchAll(PDO::FETCH_COLUMN);
                $data['familles'] = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille != ''")->fetchAll(PDO::FETCH_COLUMN);
                break;

            case 'categories':
                $data['categories'] = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY type, ordre")->fetchAll();
                break;

            case 'category_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
                    $stmt->execute([$id]);
                    $data['category'] = $stmt->fetch();
                }
                break;

            case 'pages':
                $data['pages'] = $pdo->query("SELECT * FROM pages ORDER BY id DESC")->fetchAll();
                break;

            case 'page_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id=?");
                    $stmt->execute([$id]);
                    $data['page'] = $stmt->fetch();
                }
                break;

            case 'blog':
                $data['posts'] = $pdo->query("SELECT * FROM blog_posts ORDER BY id DESC")->fetchAll();
                break;

            case 'blog_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
                    $stmt->execute([$id]);
                    $data['post'] = $stmt->fetch();
                }
                break;

            case 'quotes':
                $status = $_GET['status'] ?? '';
                $sql = "SELECT * FROM quotes";
                if ($status) {
                    $sql .= " WHERE status = '$status'";
                }
                $sql .= " ORDER BY created_at DESC LIMIT 100";
                $data['quotes'] = $pdo->query($sql)->fetchAll();
                break;

            case 'quote_view':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id=?");
                    $stmt->execute([$id]);
                    $data['quote'] = $stmt->fetch();
                }
                break;
        }
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - FLARE CUSTOM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B26;
            --primary-dark: #E63910;
            --dark: #1a1a1c;
            --gray-100: #f5f5f7;
            --gray-200: #e5e5e7;
            --gray-500: #86868b;
            --success: #34c759;
            --warning: #ff9500;
            --danger: #ff3b30;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); min-height: 100vh; }

        /* Login */
        .login-container {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--dark) 0%, #2d2d30 100%);
        }
        .login-box {
            background: #fff; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-box h1 { color: var(--primary); font-size: 28px; margin-bottom: 8px; }
        .login-box p { color: var(--gray-500); margin-bottom: 24px; }

        /* Layout */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 260px;
            background: var(--dark); color: #fff; padding: 24px 0; overflow-y: auto;
        }
        .sidebar .logo { padding: 0 24px 24px; font-size: 22px; font-weight: 700; color: var(--primary); }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 24px;
            color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-item.active { border-left: 3px solid var(--primary); }

        .main { margin-left: 260px; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 24px; }

        /* Components */
        .card { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size: 18px; margin-bottom: 16px; }

        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
            border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer;
            border: none; font-size: 14px; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--gray-200); color: var(--dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: var(--dark); }
        .form-control {
            width: 100%; padding: 10px 14px; border: 2px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { min-height: 120px; resize: vertical; }
        select.form-control { cursor: pointer; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        .table th { background: var(--gray-100); font-weight: 600; font-size: 13px; }
        .table tr:hover { background: var(--gray-100); }

        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-success { background: rgba(52,199,89,0.15); color: var(--success); }
        .badge-warning { background: rgba(255,149,0,0.15); color: var(--warning); }
        .badge-danger { background: rgba(255,59,48,0.15); color: var(--danger); }
        .badge-info { background: rgba(0,122,255,0.15); color: #007AFF; }

        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: rgba(52,199,89,0.15); color: var(--success); }
        .alert-danger { background: rgba(255,59,48,0.15); color: var(--danger); }
        .alert-warning { background: rgba(255,149,0,0.15); color: var(--warning); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 13px; color: var(--gray-500); margin-top: 4px; }

        .tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 2px solid var(--gray-200); }
        .tab { padding: 12px 20px; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { border-bottom-color: var(--primary); color: var(--primary); font-weight: 600; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

        .product-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; background: var(--gray-100); }

        .filters { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters .form-control { width: auto; min-width: 200px; }

        /* Configurateur */
        .config-section { border: 2px solid var(--gray-200); border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .config-section h3 { font-size: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .config-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--gray-100); }
        .config-item:last-child { border-bottom: none; }
        .config-item input[type="checkbox"] { width: 18px; height: 18px; }

        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; }
            .main { margin-left: 0; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php if ($page === 'login'): ?>
<!-- LOGIN -->
<div class="login-container">
    <div class="login-box">
        <h1>FLARE CUSTOM</h1>
        <p>Panneau d'administration</p>

        <?php if (isset($loginError)): ?>
        <div class="alert alert-danger"><?php echo $loginError; ?></div>
        <?php endif; ?>

        <?php if ($dbError): ?>
        <div class="alert alert-danger">Erreur BDD: <?php echo htmlspecialchars($dbError); ?></div>
        <p style="margin-top:12px"><a href="import-content.php" class="btn btn-primary">Lancer l'import</a></p>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label>Utilisateur</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Connexion</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ADMIN LAYOUT -->
<nav class="sidebar">
    <div class="logo">FLARE ADMIN</div>
    <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
    <a href="?page=products" class="nav-item <?php echo in_array($page, ['products', 'product_edit']) ? 'active' : ''; ?>">📦 Produits</a>
    <a href="?page=categories" class="nav-item <?php echo in_array($page, ['categories', 'category_edit']) ? 'active' : ''; ?>">📁 Catégories</a>
    <a href="?page=pages" class="nav-item <?php echo in_array($page, ['pages', 'page_edit']) ? 'active' : ''; ?>">📄 Pages</a>
    <a href="?page=blog" class="nav-item <?php echo in_array($page, ['blog', 'blog_edit']) ? 'active' : ''; ?>">📝 Blog</a>
    <a href="?page=quotes" class="nav-item <?php echo in_array($page, ['quotes', 'quote_view']) ? 'active' : ''; ?>">💰 Devis</a>
    <a href="import-content.php" class="nav-item">📥 Import</a>
    <a href="?page=logout" class="nav-item">🚪 Déconnexion</a>
</nav>

<main class="main">
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($dbError): ?>
    <div class="alert alert-danger">Erreur BDD: <?php echo htmlspecialchars($dbError); ?> <a href="import-content.php">Lancer l'import</a></div>
    <?php endif; ?>

    <?php if ($page === 'dashboard'): ?>
    <!-- DASHBOARD -->
    <div class="header"><h1>📊 Dashboard</h1></div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?php echo $data['stats']['products'] ?? 0; ?></div><div class="stat-label">Produits</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $data['stats']['categories'] ?? 0; ?></div><div class="stat-label">Catégories</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $data['stats']['pages'] ?? 0; ?></div><div class="stat-label">Pages</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $data['stats']['blog'] ?? 0; ?></div><div class="stat-label">Articles</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $data['stats']['quotes'] ?? 0; ?></div><div class="stat-label">Devis</div></div>
    </div>

    <div class="card">
        <h2>Accès rapide</h2>
        <div class="grid-3">
            <a href="?page=products" class="btn btn-secondary">📦 Gérer les produits</a>
            <a href="?page=quotes" class="btn btn-secondary">💰 Voir les devis</a>
            <a href="import-content.php" class="btn btn-primary">📥 Importer du contenu</a>
        </div>
    </div>

    <?php elseif ($page === 'products'): ?>
    <!-- LISTE PRODUITS -->
    <div class="header">
        <h1>📦 Produits</h1>
    </div>

    <form class="filters" method="GET">
        <input type="hidden" name="page" value="products">
        <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        <select name="sport" class="form-control">
            <option value="">Tous les sports</option>
            <?php foreach ($data['sports'] ?? [] as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($s === ($_GET['sport'] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrer</button>
    </form>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th>Sport</th>
                    <th>Prix</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['products'] ?? [] as $p): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($p['photo_1'] ?: '/photos/placeholder.webp'); ?>" class="product-thumb"></td>
                    <td><strong><?php echo htmlspecialchars($p['reference']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($p['nom'], 0, 50)); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($p['sport']); ?></span></td>
                    <td><?php echo $p['prix_1'] ? number_format($p['prix_1'], 2) . '€' : '-'; ?></td>
                    <td>
                        <a href="?page=product_edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($page === 'product_edit'): ?>
    <!-- EDIT PRODUIT -->
    <?php $p = $data['product'] ?? []; ?>
    <div class="header">
        <h1>📦 <?php echo $id ? 'Modifier' : 'Nouveau'; ?> Produit</h1>
        <a href="?page=products" class="btn btn-secondary">← Retour</a>
    </div>

    <form method="POST" action="?page=product_edit&id=<?php echo $id; ?>">
        <input type="hidden" name="action" value="save_product">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="grid-2">
            <div class="card">
                <h2>Informations générales</h2>
                <div class="form-group">
                    <label>Référence</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['reference'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Nom du produit</label>
                    <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($p['nom'] ?? ''); ?>" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Sport</label>
                        <select name="sport" class="form-control">
                            <?php foreach ($data['sports'] ?? [] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($p['sport'] ?? '') === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Famille</label>
                        <select name="famille" class="form-control">
                            <?php foreach ($data['familles'] ?? [] as $f): ?>
                            <option value="<?php echo $f; ?>" <?php echo ($p['famille'] ?? '') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?php echo htmlspecialchars($p['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Description SEO</label>
                    <textarea name="description_seo" class="form-control"><?php echo htmlspecialchars($p['description_seo'] ?? ''); ?></textarea>
                </div>
            </div>

            <div>
                <div class="card">
                    <h2>Caractéristiques</h2>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Tissu</label>
                            <input type="text" name="tissu" class="form-control" value="<?php echo htmlspecialchars($p['tissu'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Grammage</label>
                            <input type="text" name="grammage" class="form-control" value="<?php echo htmlspecialchars($p['grammage'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Genre</label>
                            <select name="genre" class="form-control">
                                <option value="Mixte" <?php echo ($p['genre'] ?? '') === 'Mixte' ? 'selected' : ''; ?>>Mixte</option>
                                <option value="Homme" <?php echo ($p['genre'] ?? '') === 'Homme' ? 'selected' : ''; ?>>Homme</option>
                                <option value="Femme" <?php echo ($p['genre'] ?? '') === 'Femme' ? 'selected' : ''; ?>>Femme</option>
                                <option value="Enfant" <?php echo ($p['genre'] ?? '') === 'Enfant' ? 'selected' : ''; ?>>Enfant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Finition</label>
                            <input type="text" name="finition" class="form-control" value="<?php echo htmlspecialchars($p['finition'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Prix (€)</h2>
                    <div class="grid-3">
                        <div class="form-group"><label>1 pièce</label><input type="number" step="0.01" name="prix_1" class="form-control" value="<?php echo $p['prix_1'] ?? ''; ?>"></div>
                        <div class="form-group"><label>5 pièces</label><input type="number" step="0.01" name="prix_5" class="form-control" value="<?php echo $p['prix_5'] ?? ''; ?>"></div>
                        <div class="form-group"><label>10 pièces</label><input type="number" step="0.01" name="prix_10" class="form-control" value="<?php echo $p['prix_10'] ?? ''; ?>"></div>
                        <div class="form-group"><label>20 pièces</label><input type="number" step="0.01" name="prix_20" class="form-control" value="<?php echo $p['prix_20'] ?? ''; ?>"></div>
                        <div class="form-group"><label>50 pièces</label><input type="number" step="0.01" name="prix_50" class="form-control" value="<?php echo $p['prix_50'] ?? ''; ?>"></div>
                        <div class="form-group"><label>100 pièces</label><input type="number" step="0.01" name="prix_100" class="form-control" value="<?php echo $p['prix_100'] ?? ''; ?>"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>📸 Image principale</h2>
            <div class="form-group">
                <label>URL de l'image</label>
                <input type="text" name="photo_1" class="form-control" value="<?php echo htmlspecialchars($p['photo_1'] ?? ''); ?>">
            </div>
            <?php if (!empty($p['photo_1'])): ?>
            <img src="<?php echo htmlspecialchars($p['photo_1']); ?>" style="max-width:200px; border-radius:8px;">
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>🔧 Configuration Onglets Page Produit</h2>
            <p style="color:var(--gray-500);margin-bottom:16px;">Configurez les onglets qui s'affichent sur la fiche produit</p>
            <?php
            $tabsConfig = json_decode($p['tabs_config'] ?? '{}', true) ?: [];
            $defaultTabs = [
                'description' => ['label' => 'Description', 'enabled' => true],
                'specifications' => ['label' => 'Caractéristiques', 'enabled' => true],
                'sizes' => ['label' => 'Guide des tailles', 'enabled' => true],
                'delivery' => ['label' => 'Livraison', 'enabled' => true],
                'customization' => ['label' => 'Personnalisation', 'enabled' => true],
            ];
            $tabs = array_merge($defaultTabs, $tabsConfig);
            ?>
            <div class="config-section">
                <?php foreach ($tabs as $key => $tab): ?>
                <div class="config-item">
                    <input type="checkbox" name="tabs[<?php echo $key; ?>][enabled]" value="1" <?php echo ($tab['enabled'] ?? true) ? 'checked' : ''; ?>>
                    <input type="text" name="tabs[<?php echo $key; ?>][label]" class="form-control" value="<?php echo htmlspecialchars($tab['label'] ?? ucfirst($key)); ?>" style="width:200px;">
                    <span style="color:var(--gray-500)"><?php echo $key; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="tabs_config" id="tabs_config" value='<?php echo htmlspecialchars(json_encode($tabs)); ?>'>
        </div>

        <div class="card">
            <h2>⚙️ Configuration Configurateur Produit</h2>
            <p style="color:var(--gray-500);margin-bottom:16px;">Définissez les options disponibles dans le configurateur pour ce produit</p>
            <?php
            $configConfig = json_decode($p['configurator_config'] ?? '{}', true) ?: [];
            ?>
            <div class="grid-2">
                <div class="config-section">
                    <h3>🎨 Design</h3>
                    <div class="config-item">
                        <input type="checkbox" name="config[design_flare]" value="1" <?php echo ($configConfig['design_flare'] ?? true) ? 'checked' : ''; ?>>
                        <span>Design par FLARE</span>
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[design_client]" value="1" <?php echo ($configConfig['design_client'] ?? true) ? 'checked' : ''; ?>>
                        <span>Design du client</span>
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[design_template]" value="1" <?php echo ($configConfig['design_template'] ?? true) ? 'checked' : ''; ?>>
                        <span>Templates prédéfinis</span>
                    </div>
                </div>

                <div class="config-section">
                    <h3>✨ Options de personnalisation</h3>
                    <div class="config-item">
                        <input type="checkbox" name="config[option_nom]" value="1" <?php echo ($configConfig['option_nom'] ?? true) ? 'checked' : ''; ?>>
                        <span>Nom joueur</span>
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[option_numero]" value="1" <?php echo ($configConfig['option_numero'] ?? true) ? 'checked' : ''; ?>>
                        <span>Numéro</span>
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[option_logo]" value="1" <?php echo ($configConfig['option_logo'] ?? true) ? 'checked' : ''; ?>>
                        <span>Logo club/sponsor</span>
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[option_col]" value="1" <?php echo ($configConfig['option_col'] ?? false) ? 'checked' : ''; ?>>
                        <span>Type de col</span>
                    </div>
                </div>

                <div class="config-section">
                    <h3>📏 Tailles disponibles</h3>
                    <div class="form-group">
                        <label>Tailles (séparées par virgule)</label>
                        <input type="text" name="config[sizes]" class="form-control" value="<?php echo htmlspecialchars($configConfig['sizes'] ?? 'XS,S,M,L,XL,XXL,3XL'); ?>">
                    </div>
                    <div class="config-item">
                        <input type="checkbox" name="config[sizes_enfant]" value="1" <?php echo ($configConfig['sizes_enfant'] ?? false) ? 'checked' : ''; ?>>
                        <span>Inclure tailles enfant</span>
                    </div>
                </div>

                <div class="config-section">
                    <h3>💰 Options de prix</h3>
                    <div class="form-group">
                        <label>Quantité minimum</label>
                        <input type="number" name="config[qty_min]" class="form-control" value="<?php echo $configConfig['qty_min'] ?? 1; ?>">
                    </div>
                    <div class="form-group">
                        <label>Supplément personnalisation (€)</label>
                        <input type="number" step="0.01" name="config[supplement]" class="form-control" value="<?php echo $configConfig['supplement'] ?? 0; ?>">
                    </div>
                </div>
            </div>
            <input type="hidden" name="configurator_config" id="configurator_config" value='<?php echo htmlspecialchars(json_encode($configConfig)); ?>'>
        </div>

        <div class="card">
            <h2>🔍 SEO</h2>
            <div class="form-group">
                <label>Meta Title</label>
                <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($p['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Meta Description</label>
                <textarea name="meta_description" class="form-control"><?php echo htmlspecialchars($p['meta_description'] ?? ''); ?></textarea>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="?page=products" class="btn btn-secondary">Annuler</a>
        </div>
    </form>

    <script>
    // Sérialiser les configs avant submit
    document.querySelector('form').addEventListener('submit', function(e) {
        // Tabs config
        const tabs = {};
        document.querySelectorAll('[name^="tabs["]').forEach(input => {
            const match = input.name.match(/tabs\[(\w+)\]\[(\w+)\]/);
            if (match) {
                if (!tabs[match[1]]) tabs[match[1]] = {};
                tabs[match[1]][match[2]] = input.type === 'checkbox' ? input.checked : input.value;
            }
        });
        document.getElementById('tabs_config').value = JSON.stringify(tabs);

        // Configurator config
        const config = {};
        document.querySelectorAll('[name^="config["]').forEach(input => {
            const key = input.name.match(/config\[(\w+)\]/)[1];
            config[key] = input.type === 'checkbox' ? input.checked : input.value;
        });
        document.getElementById('configurator_config').value = JSON.stringify(config);
    });
    </script>

    <?php elseif ($page === 'categories'): ?>
    <!-- CATEGORIES -->
    <div class="header">
        <h1>📁 Catégories</h1>
        <a href="?page=category_edit" class="btn btn-primary">+ Nouvelle catégorie</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Nom</th><th>Type</th><th>Slug</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($data['categories'] ?? [] as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['nom']); ?></strong></td>
                    <td><span class="badge badge-<?php echo $c['type'] === 'sport' ? 'info' : 'success'; ?>"><?php echo $c['type']; ?></span></td>
                    <td><?php echo htmlspecialchars($c['slug']); ?></td>
                    <td>
                        <a href="?page=category_edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a>
                        <a href="?page=categories&action=delete_category&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer?')">×</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($page === 'category_edit'): ?>
    <!-- EDIT CATEGORIE -->
    <?php $c = $data['category'] ?? []; ?>
    <div class="header">
        <h1>📁 <?php echo $id ? 'Modifier' : 'Nouvelle'; ?> Catégorie</h1>
        <a href="?page=categories" class="btn btn-secondary">← Retour</a>
    </div>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($c['nom'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($c['slug'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="sport" <?php echo ($c['type'] ?? '') === 'sport' ? 'selected' : ''; ?>>Sport</option>
                        <option value="famille" <?php echo ($c['type'] ?? '') === 'famille' ? 'selected' : ''; ?>>Famille produit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image (URL)</label>
                    <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($c['image'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($c['description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </form>
    </div>

    <?php elseif ($page === 'pages'): ?>
    <!-- PAGES -->
    <div class="header">
        <h1>📄 Pages</h1>
        <a href="?page=page_edit" class="btn btn-primary">+ Nouvelle page</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Titre</th><th>Slug</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($data['pages'] ?? [] as $pg): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($pg['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($pg['slug']); ?></td>
                    <td><span class="badge badge-<?php echo $pg['status'] === 'published' ? 'success' : 'warning'; ?>"><?php echo $pg['status']; ?></span></td>
                    <td><a href="?page=page_edit&id=<?php echo $pg['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($page === 'page_edit'): ?>
    <!-- EDIT PAGE -->
    <?php $pg = $data['page'] ?? []; ?>
    <div class="header">
        <h1>📄 <?php echo $id ? 'Modifier' : 'Nouvelle'; ?> Page</h1>
        <a href="?page=pages" class="btn btn-secondary">← Retour</a>
    </div>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="save_page">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($pg['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($pg['slug'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Contenu (HTML)</label>
                <textarea name="content" class="form-control" style="min-height:300px;font-family:monospace;"><?php echo htmlspecialchars($pg['content'] ?? ''); ?></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($pg['meta_title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        <option value="published" <?php echo ($pg['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publié</option>
                        <option value="draft" <?php echo ($pg['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Meta Description</label>
                <textarea name="meta_description" class="form-control"><?php echo htmlspecialchars($pg['meta_description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </form>
    </div>

    <?php elseif ($page === 'blog'): ?>
    <!-- BLOG -->
    <div class="header">
        <h1>📝 Blog</h1>
        <a href="?page=blog_edit" class="btn btn-primary">+ Nouvel article</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($data['posts'] ?? [] as $post): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($post['category'] ?? '-'); ?></span></td>
                    <td><span class="badge badge-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>"><?php echo $post['status']; ?></span></td>
                    <td><a href="?page=blog_edit&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($page === 'blog_edit'): ?>
    <!-- EDIT BLOG -->
    <?php $post = $data['post'] ?? []; ?>
    <div class="header">
        <h1>📝 <?php echo $id ? 'Modifier' : 'Nouvel'; ?> Article</h1>
        <a href="?page=blog" class="btn btn-secondary">← Retour</a>
    </div>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="save_blog">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Extrait</label>
                <textarea name="excerpt" class="form-control" style="min-height:80px;"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Contenu (HTML)</label>
                <textarea name="content" class="form-control" style="min-height:300px;font-family:monospace;"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
            </div>
            <div class="grid-3">
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" class="form-control">
                        <option value="conseils" <?php echo ($post['category'] ?? '') === 'conseils' ? 'selected' : ''; ?>>Conseils</option>
                        <option value="tutoriels" <?php echo ($post['category'] ?? '') === 'tutoriels' ? 'selected' : ''; ?>>Tutoriels</option>
                        <option value="nouveautes" <?php echo ($post['category'] ?? '') === 'nouveautes' ? 'selected' : ''; ?>>Nouveautés</option>
                        <option value="sports" <?php echo ($post['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Publié</option>
                        <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Meta Description</label>
                <textarea name="meta_description" class="form-control"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
        </form>
    </div>

    <?php elseif ($page === 'quotes'): ?>
    <!-- DEVIS -->
    <div class="header">
        <h1>💰 Devis</h1>
    </div>
    <div class="filters">
        <a href="?page=quotes" class="btn btn-<?php echo empty($_GET['status']) ? 'primary' : 'secondary'; ?>">Tous</a>
        <a href="?page=quotes&status=pending" class="btn btn-<?php echo ($_GET['status'] ?? '') === 'pending' ? 'primary' : 'secondary'; ?>">En attente</a>
        <a href="?page=quotes&status=sent" class="btn btn-<?php echo ($_GET['status'] ?? '') === 'sent' ? 'primary' : 'secondary'; ?>">Envoyés</a>
        <a href="?page=quotes&status=accepted" class="btn btn-<?php echo ($_GET['status'] ?? '') === 'accepted' ? 'primary' : 'secondary'; ?>">Acceptés</a>
    </div>
    <div class="card">
        <table class="table">
            <thead><tr><th>Réf</th><th>Client</th><th>Produit</th><th>Qté</th><th>Total</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($data['quotes'] ?? [] as $q): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($q['reference']); ?></strong></td>
                    <td><?php echo htmlspecialchars($q['client_prenom'] . ' ' . $q['client_nom']); ?></td>
                    <td><?php echo htmlspecialchars(substr($q['product_nom'] ?? '', 0, 30)); ?></td>
                    <td><?php echo $q['total_pieces']; ?></td>
                    <td><strong><?php echo number_format($q['prix_total'] ?? 0, 2); ?>€</strong></td>
                    <td>
                        <?php
                        $statusColors = ['pending' => 'warning', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger', 'completed' => 'success'];
                        ?>
                        <span class="badge badge-<?php echo $statusColors[$q['status']] ?? 'info'; ?>"><?php echo $q['status']; ?></span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($q['created_at'])); ?></td>
                    <td><a href="?page=quote_view&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-secondary">Voir</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($page === 'quote_view'): ?>
    <!-- VIEW DEVIS -->
    <?php $q = $data['quote'] ?? []; ?>
    <div class="header">
        <h1>💰 Devis <?php echo htmlspecialchars($q['reference'] ?? ''); ?></h1>
        <a href="?page=quotes" class="btn btn-secondary">← Retour</a>
    </div>
    <div class="grid-2">
        <div class="card">
            <h2>👤 Client</h2>
            <p><strong>Nom:</strong> <?php echo htmlspecialchars($q['client_prenom'] . ' ' . $q['client_nom']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($q['client_email']); ?></p>
            <p><strong>Tél:</strong> <?php echo htmlspecialchars($q['client_telephone']); ?></p>
            <p><strong>Club:</strong> <?php echo htmlspecialchars($q['client_club']); ?></p>
        </div>
        <div class="card">
            <h2>📦 Produit</h2>
            <p><strong>Réf:</strong> <?php echo htmlspecialchars($q['product_reference']); ?></p>
            <p><strong>Nom:</strong> <?php echo htmlspecialchars($q['product_nom']); ?></p>
            <p><strong>Sport:</strong> <?php echo htmlspecialchars($q['sport']); ?></p>
            <p><strong>Quantité:</strong> <?php echo $q['total_pieces']; ?> pièces</p>
            <p><strong>Prix unitaire:</strong> <?php echo number_format($q['prix_unitaire'] ?? 0, 2); ?>€</p>
            <p><strong>Total:</strong> <span style="font-size:20px;color:var(--primary);font-weight:700;"><?php echo number_format($q['prix_total'] ?? 0, 2); ?>€</span></p>
        </div>
    </div>
    <div class="card">
        <h2>📋 Mettre à jour le statut</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_quote_status">
            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        <option value="pending" <?php echo $q['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="sent" <?php echo $q['status'] === 'sent' ? 'selected' : ''; ?>>Envoyé</option>
                        <option value="accepted" <?php echo $q['status'] === 'accepted' ? 'selected' : ''; ?>>Accepté</option>
                        <option value="rejected" <?php echo $q['status'] === 'rejected' ? 'selected' : ''; ?>>Refusé</option>
                        <option value="completed" <?php echo $q['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes internes</label>
                    <textarea name="notes" class="form-control"><?php echo htmlspecialchars($q['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Mettre à jour</button>
        </form>
    </div>

    <?php endif; ?>

</main>
<?php endif; ?>

</body>
</html>
