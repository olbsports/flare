<?php
/**
 * FLARE CUSTOM - Administration Professionnelle
 * Interface style WordPress/Shopify
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';

$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;
$tab = $_GET['tab'] ?? 'general';

// Auth check
if ($page !== 'login' && !isset($_SESSION['admin_user'])) {
    $page = 'login';
}

// DB Connection
$pdo = null;
$dbError = null;
try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// LOGIN
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_user'] = $user;
            header('Location: admin.php');
            exit;
        }
        $loginError = "Identifiants incorrects";
    }
}

// LOGOUT
if ($page === 'logout') {
    session_destroy();
    header('Location: admin.php?page=login');
    exit;
}

// ACTIONS
$toast = '';
if ($action && $pdo) {
    try {
        switch ($action) {
            case 'save_product':
                $fields = ['nom', 'sport', 'famille', 'description', 'description_seo', 'tissu', 'grammage',
                    'prix_1', 'prix_5', 'prix_10', 'prix_20', 'prix_50', 'prix_100', 'prix_250', 'prix_500',
                    'photo_1', 'photo_2', 'photo_3', 'photo_4', 'photo_5', 'genre', 'finition',
                    'meta_title', 'meta_description', 'tab_description', 'tab_specifications',
                    'tab_sizes', 'tab_templates', 'tab_faq', 'configurator_config'];
                $set = implode('=?, ', $fields) . '=?';
                $values = array_map(fn($f) => $_POST[$f] ?? null, $fields);
                $values[] = $id;
                $pdo->prepare("UPDATE products SET $set WHERE id=?")->execute($values);
                $toast = 'Produit enregistré';
                break;

            case 'save_category':
                if ($id) {
                    $pdo->prepare("UPDATE categories SET nom=?, slug=?, type=?, description=?, image=? WHERE id=?")
                        ->execute([$_POST['nom'], $_POST['slug'], $_POST['type'], $_POST['description'], $_POST['image'], $id]);
                } else {
                    $pdo->prepare("INSERT INTO categories (nom, slug, type, description, image, active) VALUES (?,?,?,?,?,1)")
                        ->execute([$_POST['nom'], $_POST['slug'], $_POST['type'], $_POST['description'], $_POST['image']]);
                }
                $toast = 'Catégorie enregistrée';
                break;

            case 'save_page':
                if ($id) {
                    $pdo->prepare("UPDATE pages SET title=?, slug=?, content=?, excerpt=?, meta_title=?, meta_description=?, status=? WHERE id=?")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status'], $id]);
                } else {
                    $pdo->prepare("INSERT INTO pages (title, slug, content, excerpt, meta_title, meta_description, status, type) VALUES (?,?,?,?,?,?,?,'page')")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status']]);
                }
                $toast = 'Page enregistrée';
                break;

            case 'save_blog':
                if ($id) {
                    $pdo->prepare("UPDATE blog_posts SET title=?, slug=?, content=?, excerpt=?, featured_image=?, category=?, meta_title=?, meta_description=?, status=? WHERE id=?")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['featured_image'], $_POST['category'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status'], $id]);
                } else {
                    $pdo->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, category, meta_title, meta_description, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())")
                        ->execute([$_POST['title'], $_POST['slug'], $_POST['content'], $_POST['excerpt'], $_POST['featured_image'], $_POST['category'], $_POST['meta_title'], $_POST['meta_description'], $_POST['status']]);
                }
                $toast = 'Article enregistré';
                break;

            case 'update_quote':
                $pdo->prepare("UPDATE quotes SET status=?, notes=? WHERE id=?")->execute([$_POST['status'], $_POST['notes'], $id]);
                $toast = 'Devis mis à jour';
                break;

            case 'delete':
                $table = $_POST['table'] ?? '';
                if (in_array($table, ['products', 'categories', 'pages', 'blog_posts'])) {
                    $pdo->prepare("UPDATE $table SET active=0 WHERE id=?")->execute([$id]);
                    $toast = 'Élément supprimé';
                }
                break;

            case 'save_settings':
                $settings = [
                    'site_name' => $_POST['site_name'] ?? 'FLARE CUSTOM',
                    'site_tagline' => $_POST['site_tagline'] ?? '',
                    'site_email' => $_POST['site_email'] ?? '',
                    'site_phone' => $_POST['site_phone'] ?? '',
                    'site_address' => $_POST['site_address'] ?? '',
                    'site_logo' => $_POST['site_logo'] ?? '',
                    'site_favicon' => $_POST['site_favicon'] ?? '',
                    'social_facebook' => $_POST['social_facebook'] ?? '',
                    'social_instagram' => $_POST['social_instagram'] ?? '',
                    'social_twitter' => $_POST['social_twitter'] ?? '',
                    'social_linkedin' => $_POST['social_linkedin'] ?? '',
                    'social_youtube' => $_POST['social_youtube'] ?? '',
                    'smtp_host' => $_POST['smtp_host'] ?? '',
                    'smtp_port' => $_POST['smtp_port'] ?? '587',
                    'smtp_user' => $_POST['smtp_user'] ?? '',
                    'smtp_pass' => $_POST['smtp_pass'] ?? '',
                    'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
                    'smtp_from_name' => $_POST['smtp_from_name'] ?? '',
                    'payment_mode' => $_POST['payment_mode'] ?? 'quote',
                    'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                    'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                    'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                    'paypal_secret' => $_POST['paypal_secret'] ?? '',
                    'shipping_france' => $_POST['shipping_france'] ?? '0',
                    'shipping_europe' => $_POST['shipping_europe'] ?? '0',
                    'shipping_world' => $_POST['shipping_world'] ?? '0',
                    'shipping_free_above' => $_POST['shipping_free_above'] ?? '0',
                    'default_delivery_time' => $_POST['default_delivery_time'] ?? '3-4 semaines',
                    'min_order_quantity' => $_POST['min_order_quantity'] ?? '1',
                    'tva_rate' => $_POST['tva_rate'] ?? '20',
                    'quote_validity_days' => $_POST['quote_validity_days'] ?? '30',
                    'quote_prefix' => $_POST['quote_prefix'] ?? 'DEV-',
                    'notification_email' => $_POST['notification_email'] ?? '',
                    'google_analytics' => $_POST['google_analytics'] ?? '',
                    'google_tag_manager' => $_POST['google_tag_manager'] ?? '',
                    'meta_pixel' => $_POST['meta_pixel'] ?? '',
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                    'maintenance_message' => $_POST['maintenance_message'] ?? '',
                    'configurator_design_flare' => isset($_POST['configurator_design_flare']) ? '1' : '0',
                    'configurator_design_client' => isset($_POST['configurator_design_client']) ? '1' : '0',
                    'configurator_design_template' => isset($_POST['configurator_design_template']) ? '1' : '0',
                    'configurator_perso_nom' => isset($_POST['configurator_perso_nom']) ? '1' : '0',
                    'configurator_perso_numero' => isset($_POST['configurator_perso_numero']) ? '1' : '0',
                    'configurator_perso_logo' => isset($_POST['configurator_perso_logo']) ? '1' : '0',
                    'configurator_perso_sponsor' => isset($_POST['configurator_perso_sponsor']) ? '1' : '0',
                    'configurator_sizes' => $_POST['configurator_sizes'] ?? 'XS,S,M,L,XL,XXL,3XL',
                    'configurator_sizes_kids' => $_POST['configurator_sizes_kids'] ?? '6ans,8ans,10ans,12ans,14ans',
                    'configurator_collars' => $_POST['configurator_collars'] ?? 'col_v,col_rond,col_polo',
                ];
                foreach ($settings as $key => $value) {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$key, $value]);
                }
                $toast = 'Paramètres enregistrés';
                break;

            case 'change_password':
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if ($new !== $confirm) {
                    $toast = 'Erreur: Les mots de passe ne correspondent pas';
                } elseif (strlen($new) < 6) {
                    $toast = 'Erreur: Le mot de passe doit faire au moins 6 caractères';
                } else {
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['admin_user']['id']]);
                    $user = $stmt->fetch();
                    if ($user && password_verify($current, $user['password'])) {
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_user']['id']]);
                        $toast = 'Mot de passe modifié avec succès';
                    } else {
                        $toast = 'Erreur: Mot de passe actuel incorrect';
                    }
                }
                break;

            case 'import_csv':
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    $imported = 0;
                    $updated = 0;
                    $errors = 0;
                    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                    $headers = fgetcsv($handle, 0, ';');
                    $headers = array_map(fn($h) => strtoupper(trim($h)), $headers);

                    while (($row = fgetcsv($handle, 0, ';')) !== false) {
                        if (count($row) < 5) continue;
                        $data = array_combine($headers, array_pad($row, count($headers), ''));

                        $reference = $data['REFERENCE_FLARE'] ?? $data['REFERENCE'] ?? '';
                        if (empty($reference)) continue;

                        try {
                            // Check if exists
                            $stmt = $pdo->prepare("SELECT id FROM products WHERE reference = ?");
                            $stmt->execute([$reference]);
                            $exists = $stmt->fetch();

                            $productData = [
                                'reference' => $reference,
                                'nom' => $data['TITRE_VENDEUR'] ?? $data['NOM'] ?? $reference,
                                'sport' => $data['SPORT'] ?? '',
                                'famille' => $data['FAMILLE_PRODUIT'] ?? $data['FAMILLE'] ?? '',
                                'description' => $data['DESCRIPTION'] ?? '',
                                'description_seo' => $data['DESCRIPTION_SEO'] ?? '',
                                'tissu' => $data['TISSU'] ?? '',
                                'grammage' => $data['GRAMMAGE'] ?? '',
                                'genre' => $data['GENRE'] ?? 'Mixte',
                                'finition' => $data['FINITION'] ?? '',
                                'prix_1' => floatval(str_replace(',', '.', $data['QTY_1'] ?? $data['PRIX_1'] ?? 0)),
                                'prix_5' => floatval(str_replace(',', '.', $data['QTY_5'] ?? $data['PRIX_5'] ?? 0)),
                                'prix_10' => floatval(str_replace(',', '.', $data['QTY_10'] ?? $data['PRIX_10'] ?? 0)),
                                'prix_20' => floatval(str_replace(',', '.', $data['QTY_20'] ?? $data['PRIX_20'] ?? 0)),
                                'prix_50' => floatval(str_replace(',', '.', $data['QTY_50'] ?? $data['PRIX_50'] ?? 0)),
                                'prix_100' => floatval(str_replace(',', '.', $data['QTY_100'] ?? $data['PRIX_100'] ?? 0)),
                                'prix_250' => floatval(str_replace(',', '.', $data['QTY_250'] ?? $data['PRIX_250'] ?? 0)),
                                'prix_500' => floatval(str_replace(',', '.', $data['QTY_500'] ?? $data['PRIX_500'] ?? 0)),
                                'photo_1' => $data['PHOTO_1'] ?? '',
                                'photo_2' => $data['PHOTO_2'] ?? '',
                                'photo_3' => $data['PHOTO_3'] ?? '',
                                'photo_4' => $data['PHOTO_4'] ?? '',
                                'photo_5' => $data['PHOTO_5'] ?? '',
                                'url' => $data['URL'] ?? '',
                            ];

                            if ($exists) {
                                $sql = "UPDATE products SET nom=?, sport=?, famille=?, description=?, description_seo=?, tissu=?, grammage=?, genre=?, finition=?, prix_1=?, prix_5=?, prix_10=?, prix_20=?, prix_50=?, prix_100=?, prix_250=?, prix_500=?, photo_1=?, photo_2=?, photo_3=?, photo_4=?, photo_5=?, url=?, updated_at=NOW() WHERE reference=?";
                                $pdo->prepare($sql)->execute([
                                    $productData['nom'], $productData['sport'], $productData['famille'], $productData['description'], $productData['description_seo'],
                                    $productData['tissu'], $productData['grammage'], $productData['genre'], $productData['finition'],
                                    $productData['prix_1'], $productData['prix_5'], $productData['prix_10'], $productData['prix_20'],
                                    $productData['prix_50'], $productData['prix_100'], $productData['prix_250'], $productData['prix_500'],
                                    $productData['photo_1'], $productData['photo_2'], $productData['photo_3'], $productData['photo_4'], $productData['photo_5'],
                                    $productData['url'], $reference
                                ]);
                                $updated++;
                            } else {
                                $sql = "INSERT INTO products (reference, nom, sport, famille, description, description_seo, tissu, grammage, genre, finition, prix_1, prix_5, prix_10, prix_20, prix_50, prix_100, prix_250, prix_500, photo_1, photo_2, photo_3, photo_4, photo_5, url, active, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW())";
                                $pdo->prepare($sql)->execute([
                                    $productData['reference'], $productData['nom'], $productData['sport'], $productData['famille'], $productData['description'], $productData['description_seo'],
                                    $productData['tissu'], $productData['grammage'], $productData['genre'], $productData['finition'],
                                    $productData['prix_1'], $productData['prix_5'], $productData['prix_10'], $productData['prix_20'],
                                    $productData['prix_50'], $productData['prix_100'], $productData['prix_250'], $productData['prix_500'],
                                    $productData['photo_1'], $productData['photo_2'], $productData['photo_3'], $productData['photo_4'], $productData['photo_5'],
                                    $productData['url']
                                ]);
                                $imported++;
                            }
                        } catch (Exception $e) {
                            $errors++;
                        }
                    }
                    fclose($handle);
                    $toast = "Import terminé: $imported nouveaux, $updated mis à jour, $errors erreurs";
                } else {
                    $toast = 'Erreur: Fichier CSV non reçu';
                }
                break;
        }
    } catch (Exception $e) {
        $toast = 'Erreur: ' . $e->getMessage();
    }
}

// FETCH DATA
$data = [];
if ($pdo && $page !== 'login') {
    try {
        switch ($page) {
            case 'dashboard':
                $data['products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE active=1")->fetchColumn();
                $data['categories'] = $pdo->query("SELECT COUNT(*) FROM categories WHERE active=1")->fetchColumn();
                $data['pages'] = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
                $data['blog'] = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();
                $data['quotes_pending'] = $pdo->query("SELECT COUNT(*) FROM quotes WHERE status='pending'")->fetchColumn();
                $data['quotes_total'] = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();
                $data['recent_quotes'] = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC LIMIT 5")->fetchAll();
                $data['recent_products'] = $pdo->query("SELECT * FROM products WHERE active=1 ORDER BY updated_at DESC LIMIT 5")->fetchAll();
                break;

            case 'products':
                $where = "WHERE active=1";
                $params = [];
                if (!empty($_GET['search'])) {
                    $where .= " AND (nom LIKE ? OR reference LIKE ?)";
                    $params[] = '%'.$_GET['search'].'%';
                    $params[] = '%'.$_GET['search'].'%';
                }
                if (!empty($_GET['sport'])) {
                    $where .= " AND sport=?";
                    $params[] = $_GET['sport'];
                }
                $stmt = $pdo->prepare("SELECT * FROM products $where ORDER BY updated_at DESC LIMIT 100");
                $stmt->execute($params);
                $data['items'] = $stmt->fetchAll();
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport!='' AND active=1 ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                break;

            case 'product':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                $data['sports'] = $pdo->query("SELECT DISTINCT sport FROM products WHERE sport!='' ORDER BY sport")->fetchAll(PDO::FETCH_COLUMN);
                $data['familles'] = $pdo->query("SELECT DISTINCT famille FROM products WHERE famille!='' ORDER BY famille")->fetchAll(PDO::FETCH_COLUMN);
                break;

            case 'categories':
                $data['items'] = $pdo->query("SELECT * FROM categories WHERE active=1 ORDER BY type, ordre, nom")->fetchAll();
                break;

            case 'category':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'pages':
                $data['items'] = $pdo->query("SELECT * FROM pages ORDER BY title")->fetchAll();
                break;

            case 'page':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'blog':
                $data['items'] = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
                break;

            case 'blog_edit':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'quotes':
                $where = "1=1";
                if (!empty($_GET['status'])) $where = "status='".$_GET['status']."'";
                $data['items'] = $pdo->query("SELECT * FROM quotes WHERE $where ORDER BY created_at DESC")->fetchAll();
                break;

            case 'quote':
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id=?");
                    $stmt->execute([$id]);
                    $data['item'] = $stmt->fetch();
                }
                break;

            case 'settings':
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
                $data['settings'] = [];
                while ($row = $stmt->fetch()) {
                    $data['settings'][$row['setting_key']] = $row['setting_value'];
                }
                break;

            case 'import':
                $data['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $data['last_import'] = $pdo->query("SELECT MAX(created_at) FROM products")->fetchColumn();
                break;
        }
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

$user = $_SESSION['admin_user'] ?? null;
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
            --primary-hover: #E6401F;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: #2a2a3c;
            --body-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #1e1e2d;
            --text-muted: #7e8299;
            --border: #e4e6ef;
            --success: #50cd89;
            --warning: #ffc700;
            --danger: #f1416c;
            --info: #7239ea;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--body-bg); color: var(--text-dark); font-size: 13px; }

        /* SIDEBAR */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 265px;
            background: var(--sidebar-bg); z-index: 100; display: flex; flex-direction: column;
        }
        .sidebar-header {
            padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-logo {
            color: #fff; font-size: 22px; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-logo span { color: var(--primary); }
        .sidebar-menu { padding: 15px 0; flex: 1; overflow-y: auto; }
        .menu-section { padding: 10px 25px 5px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .menu-item {
            display: flex; align-items: center; gap: 12px; padding: 11px 25px;
            color: #9d9da6; text-decoration: none; transition: all 0.2s;
        }
        .menu-item:hover { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active { background: var(--sidebar-hover); color: #fff; }
        .menu-item.active::before {
            content: ''; position: absolute; left: 0; width: 3px; height: 100%;
            background: var(--primary);
        }
        .menu-item { position: relative; }
        .menu-icon { width: 20px; height: 20px; opacity: 0.7; }
        .menu-badge {
            margin-left: auto; background: var(--primary); color: #fff;
            padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;
        }
        .sidebar-footer {
            padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.07);
        }
        .user-box {
            display: flex; align-items: center; gap: 12px; color: #fff;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 8px; background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .user-info { flex: 1; }
        .user-name { font-weight: 600; font-size: 14px; }
        .user-role { color: var(--text-muted); font-size: 12px; }

        /* MAIN */
        .main { margin-left: 265px; min-height: 100vh; }
        .topbar {
            background: var(--card-bg); padding: 15px 30px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }
        .breadcrumb { display: flex; align-items: center; gap: 8px; color: var(--text-muted); }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--primary); }
        .topbar-actions { display: flex; gap: 10px; }

        .content { padding: 30px; }

        /* CARDS */
        .card {
            background: var(--card-bg); border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.03); margin-bottom: 25px;
        }
        .card-header {
            padding: 20px 25px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-title { font-size: 16px; font-weight: 600; }
        .card-body { padding: 25px; }
        .card-footer { padding: 15px 25px; border-top: 1px solid var(--border); background: #fafbfc; border-radius: 0 0 12px 12px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card {
            background: var(--card-bg); border-radius: 12px; padding: 25px;
            box-shadow: 0 0 20px rgba(0,0,0,0.03);
        }
        .stat-card.primary { background: linear-gradient(135deg, var(--primary) 0%, #ff6b4a 100%); color: #fff; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 24px; }
        .stat-card:not(.primary) .stat-icon { background: rgba(255,75,38,0.1); }
        .stat-value { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { color: var(--text-muted); font-size: 13px; }
        .stat-card.primary .stat-label { color: rgba(255,255,255,0.8); }

        /* TABLE */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 15px; background: #fafbfc; font-weight: 600; color: var(--text-muted); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover td { background: #fafbfc; }
        .table-img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; background: var(--body-bg); }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px;
            border-radius: 8px; font-weight: 500; font-size: 13px; cursor: pointer;
            border: none; text-decoration: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-light { background: #f4f6f9; color: var(--text-dark); }
        .btn-light:hover { background: #e9ecef; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-icon { padding: 8px; }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-dark); }
        .form-control {
            width: 100%; padding: 10px 15px; border: 1px solid var(--border);
            border-radius: 8px; font-size: 13px; transition: all 0.2s;
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255,75,38,0.1); }
        textarea.form-control { min-height: 150px; resize: vertical; }
        select.form-control { cursor: pointer; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .form-hint { font-size: 12px; color: var(--text-muted); margin-top: 5px; }

        /* TABS */
        .tabs-nav {
            display: flex; gap: 5px; border-bottom: 1px solid var(--border);
            padding: 0 25px; background: #fafbfc; border-radius: 12px 12px 0 0;
        }
        .tab-btn {
            padding: 15px 20px; background: none; border: none; cursor: pointer;
            font-weight: 500; color: var(--text-muted); position: relative;
            font-size: 13px; transition: all 0.2s;
        }
        .tab-btn:hover { color: var(--text-dark); }
        .tab-btn.active { color: var(--primary); }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
            height: 2px; background: var(--primary);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* BADGES */
        .badge {
            display: inline-flex; align-items: center; padding: 5px 10px;
            border-radius: 6px; font-size: 11px; font-weight: 600;
        }
        .badge-success { background: rgba(80,205,137,0.1); color: var(--success); }
        .badge-warning { background: rgba(255,199,0,0.1); color: #b58b00; }
        .badge-danger { background: rgba(241,65,108,0.1); color: var(--danger); }
        .badge-info { background: rgba(114,57,234,0.1); color: var(--info); }
        .badge-primary { background: rgba(255,75,38,0.1); color: var(--primary); }

        /* TOAST */
        .toast {
            position: fixed; top: 20px; right: 20px; background: var(--success); color: #fff;
            padding: 15px 25px; border-radius: 8px; font-weight: 500; z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } }

        /* LOGIN */
        .login-page {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sidebar-bg) 0%, #2d2d42 100%);
        }
        .login-box {
            background: var(--card-bg); padding: 40px; border-radius: 16px; width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .login-logo h1 { font-size: 28px; }
        .login-logo span { color: var(--primary); }

        /* ALERTS */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-danger { background: rgba(241,65,108,0.1); color: var(--danger); border: 1px solid rgba(241,65,108,0.2); }
        .alert-success { background: rgba(80,205,137,0.1); color: var(--success); border: 1px solid rgba(80,205,137,0.2); }

        /* EDITOR */
        .editor-toolbar {
            display: flex; gap: 5px; padding: 10px; background: #fafbfc;
            border: 1px solid var(--border); border-bottom: none; border-radius: 8px 8px 0 0;
        }
        .editor-toolbar button {
            padding: 8px 12px; background: none; border: 1px solid var(--border);
            border-radius: 4px; cursor: pointer; font-size: 12px;
        }
        .editor-toolbar button:hover { background: #fff; }

        /* FILTERS */
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }

        /* RESPONSIVE */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php if ($page === 'login'): ?>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <h1>FLARE <span>CUSTOM</span></h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Administration</p>
        </div>
        <?php if (isset($loginError)): ?>
            <div class="alert alert-danger"><?= $loginError ?></div>
        <?php endif; ?>
        <?php if ($dbError): ?>
            <div class="alert alert-danger">Erreur BDD: <?= htmlspecialchars($dbError) ?><br><a href="import-content.php">Lancer l'import</a></div>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Se connecter</button>
        </form>
        <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 12px;">
            Identifiants par défaut: admin / admin123
        </p>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<?php if ($toast): ?>
<div class="toast"><?= htmlspecialchars($toast) ?></div>
<script>setTimeout(() => document.querySelector('.toast').remove(), 3000);</script>
<?php endif; ?>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="admin.php" class="sidebar-logo">FLARE <span>CUSTOM</span></a>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-section">Principal</div>
        <a href="?page=dashboard" class="menu-item <?= $page === 'dashboard' ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Tableau de bord
        </a>

        <div class="menu-section">Catalogue</div>
        <a href="?page=products" class="menu-item <?= in_array($page, ['products', 'product']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Produits
        </a>
        <a href="?page=categories" class="menu-item <?= in_array($page, ['categories', 'category']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Catégories
        </a>

        <div class="menu-section">Contenu</div>
        <a href="?page=pages" class="menu-item <?= in_array($page, ['pages', 'page']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Pages
        </a>
        <a href="?page=blog" class="menu-item <?= in_array($page, ['blog', 'blog_edit']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            Blog
        </a>

        <div class="menu-section">Ventes</div>
        <a href="?page=quotes" class="menu-item <?= in_array($page, ['quotes', 'quote']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Devis
            <?php if (($data['quotes_pending'] ?? 0) > 0): ?>
            <span class="menu-badge"><?= $data['quotes_pending'] ?? 0 ?></span>
            <?php endif; ?>
        </a>

        <div class="menu-section">Outils</div>
        <a href="?page=import" class="menu-item <?= $page === 'import' ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import CSV
        </a>
        <a href="?page=settings" class="menu-item <?= in_array($page, ['settings', 'settings_password']) ? 'active' : '' ?>">
            <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Paramètres
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-avatar"><?= strtoupper(substr($user['username'] ?? 'A', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['username'] ?? 'Admin') ?></div>
                <div class="user-role"><?= ucfirst($user['role'] ?? 'admin') ?></div>
            </div>
            <a href="?page=logout" style="color: var(--text-muted);" title="Déconnexion">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="topbar">
        <div class="breadcrumb">
            <a href="admin.php">Admin</a>
            <span>/</span>
            <span><?= ucfirst($page) ?></span>
        </div>
    </div>

    <div class="content">
        <?php if ($dbError): ?>
        <div class="alert alert-danger">Erreur BDD: <?= htmlspecialchars($dbError) ?> — <a href="import-content.php">Lancer l'import</a></div>
        <?php endif; ?>

        <?php // ============ DASHBOARD ============ ?>
        <?php if ($page === 'dashboard'): ?>
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= number_format($data['products'] ?? 0) ?></div>
                <div class="stat-label">Produits actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?= number_format($data['quotes_pending'] ?? 0) ?></div>
                <div class="stat-label">Devis en attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?= number_format($data['categories'] ?? 0) ?></div>
                <div class="stat-label">Catégories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-value"><?= number_format($data['pages'] ?? 0) ?></div>
                <div class="stat-label">Pages publiées</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Derniers devis</span>
                    <a href="?page=quotes" class="btn btn-sm btn-light">Voir tout</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Référence</th><th>Client</th><th>Statut</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['recent_quotes'] ?? [] as $q): ?>
                            <tr>
                                <td><a href="?page=quote&id=<?= $q['id'] ?>"><?= htmlspecialchars($q['reference']) ?></a></td>
                                <td><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></td>
                                <td><span class="badge badge-<?= $q['status'] === 'pending' ? 'warning' : ($q['status'] === 'accepted' ? 'success' : 'info') ?>"><?= $q['status'] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Produits récemment modifiés</span>
                    <a href="?page=products" class="btn btn-sm btn-light">Voir tout</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Produit</th><th>Sport</th><th>Modifié</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['recent_products'] ?? [] as $p): ?>
                            <tr>
                                <td>
                                    <a href="?page=product&id=<?= $p['id'] ?>" style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($p['photo_1'] ?: '/photos/placeholder.webp') ?>" class="table-img">
                                        <?= htmlspecialchars(mb_substr($p['nom'], 0, 40)) ?>
                                    </a>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($p['sport']) ?></span></td>
                                <td><?= date('d/m H:i', strtotime($p['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php // ============ PRODUCTS LIST ============ ?>
        <?php elseif ($page === 'products'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Produits (<?= count($data['items'] ?? []) ?>)</span>
            </div>
            <div class="card-body">
                <form class="filters" method="GET">
                    <input type="hidden" name="page" value="products">
                    <input type="text" name="search" class="form-control" style="width: 250px;" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <select name="sport" class="form-control" style="width: 180px;">
                        <option value="">Tous les sports</option>
                        <?php foreach ($data['sports'] ?? [] as $s): ?>
                        <option value="<?= $s ?>" <?= ($_GET['sport'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-light">Filtrer</button>
                </form>

                <div class="table-container">
                    <table>
                        <thead><tr><th style="width:60px"></th><th>Référence</th><th>Nom</th><th>Sport</th><th>Prix</th><th style="width:100px">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['items'] ?? [] as $p): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($p['photo_1'] ?: '/photos/placeholder.webp') ?>" class="table-img"></td>
                                <td><strong><?= htmlspecialchars($p['reference']) ?></strong></td>
                                <td><a href="?page=product&id=<?= $p['id'] ?>"><?= htmlspecialchars(mb_substr($p['nom'], 0, 50)) ?></a></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($p['sport']) ?></span></td>
                                <td><?= $p['prix_500'] ? number_format($p['prix_500'], 2).'€' : '-' ?></td>
                                <td>
                                    <a href="?page=product&id=<?= $p['id'] ?>" class="btn btn-sm btn-light">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php // ============ PRODUCT EDIT ============ ?>
        <?php elseif ($page === 'product' && $id): ?>
        <?php $p = $data['item'] ?? []; ?>
        <form method="POST" action="?page=product&id=<?= $id ?>">
            <input type="hidden" name="action" value="save_product">

            <div class="card">
                <div class="tabs-nav">
                    <button type="button" class="tab-btn <?= $tab === 'general' ? 'active' : '' ?>" onclick="switchTab('general')">Général</button>
                    <button type="button" class="tab-btn <?= $tab === 'prices' ? 'active' : '' ?>" onclick="switchTab('prices')">Prix</button>
                    <button type="button" class="tab-btn <?= $tab === 'photos' ? 'active' : '' ?>" onclick="switchTab('photos')">Photos</button>
                    <button type="button" class="tab-btn <?= $tab === 'tabs' ? 'active' : '' ?>" onclick="switchTab('tabs')">Contenu onglets</button>
                    <button type="button" class="tab-btn <?= $tab === 'configurator' ? 'active' : '' ?>" onclick="switchTab('configurator')">Configurateur</button>
                    <button type="button" class="tab-btn <?= $tab === 'seo' ? 'active' : '' ?>" onclick="switchTab('seo')">SEO</button>
                </div>

                <!-- TAB: GENERAL -->
                <div class="tab-content <?= $tab === 'general' ? 'active' : '' ?>" id="tab-general">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Référence</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($p['reference'] ?? '') ?>" readonly style="background: #f4f6f9;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sport</label>
                                <select name="sport" class="form-control">
                                    <?php foreach ($data['sports'] ?? [] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($p['sport'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Famille</label>
                                <select name="famille" class="form-control">
                                    <?php foreach ($data['familles'] ?? [] as $f): ?>
                                    <option value="<?= $f ?>" <?= ($p['famille'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom du produit</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($p['nom'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tissu</label>
                                <input type="text" name="tissu" class="form-control" value="<?= htmlspecialchars($p['tissu'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Grammage</label>
                                <input type="text" name="grammage" class="form-control" value="<?= htmlspecialchars($p['grammage'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Genre</label>
                                <select name="genre" class="form-control">
                                    <option value="Mixte" <?= ($p['genre'] ?? '') === 'Mixte' ? 'selected' : '' ?>>Mixte</option>
                                    <option value="Homme" <?= ($p['genre'] ?? '') === 'Homme' ? 'selected' : '' ?>>Homme</option>
                                    <option value="Femme" <?= ($p['genre'] ?? '') === 'Femme' ? 'selected' : '' ?>>Femme</option>
                                    <option value="Enfant" <?= ($p['genre'] ?? '') === 'Enfant' ? 'selected' : '' ?>>Enfant</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Finition</label>
                                <input type="text" name="finition" class="form-control" value="<?= htmlspecialchars($p['finition'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description courte</label>
                            <textarea name="description" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description SEO</label>
                            <textarea name="description_seo" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['description_seo'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB: PRICES -->
                <div class="tab-content <?= $tab === 'prices' ? 'active' : '' ?>" id="tab-prices">
                    <div class="card-body">
                        <p style="color: var(--text-muted); margin-bottom: 20px;">Prix unitaire TTC par quantité</p>
                        <div class="form-row">
                            <?php foreach ([1, 5, 10, 20, 50, 100, 250, 500] as $qty): ?>
                            <div class="form-group">
                                <label class="form-label"><?= $qty ?> pièce<?= $qty > 1 ? 's' : '' ?></label>
                                <input type="number" step="0.01" name="prix_<?= $qty ?>" class="form-control" value="<?= $p['prix_'.$qty] ?? '' ?>" placeholder="0.00">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB: PHOTOS -->
                <div class="tab-content <?= $tab === 'photos' ? 'active' : '' ?>" id="tab-photos">
                    <div class="card-body">
                        <div class="form-row">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="form-group">
                                <label class="form-label">Photo <?= $i ?></label>
                                <input type="text" name="photo_<?= $i ?>" class="form-control" value="<?= htmlspecialchars($p['photo_'.$i] ?? '') ?>" placeholder="URL de l'image">
                                <?php if (!empty($p['photo_'.$i])): ?>
                                <img src="<?= htmlspecialchars($p['photo_'.$i]) ?>" style="max-width: 150px; margin-top: 10px; border-radius: 8px;">
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB: TABS CONTENT -->
                <div class="tab-content <?= $tab === 'tabs' ? 'active' : '' ?>" id="tab-tabs">
                    <div class="card-body">
                        <p style="color: var(--text-muted); margin-bottom: 20px;">Contenu des onglets affichés sur la fiche produit. Laissez vide pour utiliser le contenu par défaut.</p>

                        <div class="form-group">
                            <label class="form-label">📝 Onglet Description</label>
                            <textarea name="tab_description" class="form-control" style="min-height: 200px; font-family: monospace;"><?= htmlspecialchars($p['tab_description'] ?? '') ?></textarea>
                            <div class="form-hint">HTML autorisé. Contenu principal de la fiche produit.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📋 Onglet Caractéristiques</label>
                            <textarea name="tab_specifications" class="form-control" style="min-height: 200px; font-family: monospace;"><?= htmlspecialchars($p['tab_specifications'] ?? '') ?></textarea>
                            <div class="form-hint">HTML autorisé. Tableau des spécifications techniques.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📏 Onglet Guide des Tailles</label>
                            <textarea name="tab_sizes" class="form-control" style="min-height: 200px; font-family: monospace;"><?= htmlspecialchars($p['tab_sizes'] ?? '') ?></textarea>
                            <div class="form-hint">HTML autorisé. Tableau des tailles.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">🎨 Onglet Templates</label>
                            <textarea name="tab_templates" class="form-control" style="min-height: 200px; font-family: monospace;"><?= htmlspecialchars($p['tab_templates'] ?? '') ?></textarea>
                            <div class="form-hint">HTML autorisé. Galerie de templates disponibles.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">❓ Onglet FAQ</label>
                            <textarea name="tab_faq" class="form-control" style="min-height: 200px; font-family: monospace;"><?= htmlspecialchars($p['tab_faq'] ?? '') ?></textarea>
                            <div class="form-hint">HTML autorisé. Questions fréquentes sur ce produit.</div>
                        </div>
                    </div>
                </div>

                <!-- TAB: CONFIGURATOR -->
                <div class="tab-content <?= $tab === 'configurator' ? 'active' : '' ?>" id="tab-configurator">
                    <div class="card-body">
                        <p style="color: var(--text-muted); margin-bottom: 20px;">Configuration du configurateur produit (JSON). Définissez les options disponibles pour ce produit.</p>

                        <?php
                        $defaultConfig = [
                            'design_options' => ['flare' => true, 'client' => true, 'template' => true],
                            'personalization' => ['nom' => true, 'numero' => true, 'logo' => true, 'sponsor' => true],
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
                            'sizes_kids' => ['6ans', '8ans', '10ans', '12ans', '14ans'],
                            'colors_available' => true,
                            'collar_options' => ['col_v', 'col_rond', 'col_polo'],
                            'min_quantity' => 1,
                            'delivery_time' => '3-4 semaines'
                        ];
                        $config = json_decode($p['configurator_config'] ?? '', true) ?: $defaultConfig;
                        ?>

                        <div class="form-group">
                            <label class="form-label">Configuration JSON</label>
                            <textarea name="configurator_config" class="form-control" style="min-height: 350px; font-family: monospace;"><?= htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
                            <div class="form-hint">
                                Options disponibles: design_options (flare, client, template), personalization (nom, numero, logo, sponsor), sizes, sizes_kids, colors_available, collar_options, min_quantity, delivery_time
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SEO -->
                <div class="tab-content <?= $tab === 'seo' ? 'active' : '' ?>" id="tab-seo">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($p['meta_title'] ?? '') ?>">
                            <div class="form-hint">Recommandé: 50-60 caractères</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($p['meta_description'] ?? '') ?></textarea>
                            <div class="form-hint">Recommandé: 150-160 caractères</div>
                        </div>
                    </div>
                </div>

                <div class="card-footer" style="display: flex; justify-content: space-between;">
                    <a href="?page=products" class="btn btn-light">← Retour aux produits</a>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
                </div>
            </div>
        </form>

        <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelector(`[onclick="switchTab('${tabId}')"]`).classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
        </script>

        <?php // ============ CATEGORIES ============ ?>
        <?php elseif ($page === 'categories'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Catégories</span>
                <a href="?page=category" class="btn btn-primary">+ Nouvelle catégorie</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Nom</th><th>Type</th><th>Slug</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                            <td><span class="badge badge-<?= $c['type'] === 'sport' ? 'info' : 'success' ?>"><?= $c['type'] ?></span></td>
                            <td><?= htmlspecialchars($c['slug']) ?></td>
                            <td><a href="?page=category&id=<?= $c['id'] ?>" class="btn btn-sm btn-light">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ CATEGORY EDIT ============ ?>
        <?php elseif ($page === 'category'): ?>
        <?php $c = $data['item'] ?? []; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $id ? 'Modifier' : 'Nouvelle' ?> catégorie</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_category">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($c['nom'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($c['slug'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control">
                                <option value="sport" <?= ($c['type'] ?? '') === 'sport' ? 'selected' : '' ?>>Sport</option>
                                <option value="famille" <?= ($c['type'] ?? '') === 'famille' ? 'selected' : '' ?>>Famille produit</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image (URL)</label>
                        <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($c['image'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($c['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?page=categories" class="btn btn-light">← Retour</a>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>

        <?php // ============ PAGES ============ ?>
        <?php elseif ($page === 'pages'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Pages</span>
                <a href="?page=page" class="btn btn-primary">+ Nouvelle page</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Titre</th><th>Slug</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $pg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pg['title']) ?></strong></td>
                            <td><?= htmlspecialchars($pg['slug']) ?></td>
                            <td><span class="badge badge-<?= $pg['status'] === 'published' ? 'success' : 'warning' ?>"><?= $pg['status'] ?></span></td>
                            <td><a href="?page=page&id=<?= $pg['id'] ?>" class="btn btn-sm btn-light">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ PAGE EDIT ============ ?>
        <?php elseif ($page === 'page'): ?>
        <?php $pg = $data['item'] ?? []; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $id ? 'Modifier' : 'Nouvelle' ?> page</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_page">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($pg['title'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($pg['slug'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="published" <?= ($pg['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
                                <option value="draft" <?= ($pg['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" style="min-height: 80px;"><?= htmlspecialchars($pg['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contenu (HTML)</label>
                        <textarea name="content" class="form-control" style="min-height: 300px; font-family: monospace;"><?= htmlspecialchars($pg['content'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($pg['meta_title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control"><?= htmlspecialchars($pg['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?page=pages" class="btn btn-light">← Retour</a>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>

        <?php // ============ BLOG ============ ?>
        <?php elseif ($page === 'blog'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Articles de blog</span>
                <a href="?page=blog_edit" class="btn btn-primary">+ Nouvel article</a>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $post): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($post['title']) ?></strong></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($post['category'] ?? '-') ?></span></td>
                            <td><span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'warning' ?>"><?= $post['status'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                            <td><a href="?page=blog_edit&id=<?= $post['id'] ?>" class="btn btn-sm btn-light">Modifier</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ BLOG EDIT ============ ?>
        <?php elseif ($page === 'blog_edit'): ?>
        <?php $post = $data['item'] ?? []; ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $id ? 'Modifier' : 'Nouvel' ?> article</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_blog">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Catégorie</label>
                            <select name="category" class="form-control">
                                <option value="conseils" <?= ($post['category'] ?? '') === 'conseils' ? 'selected' : '' ?>>Conseils</option>
                                <option value="tutoriels" <?= ($post['category'] ?? '') === 'tutoriels' ? 'selected' : '' ?>>Tutoriels</option>
                                <option value="nouveautes" <?= ($post['category'] ?? '') === 'nouveautes' ? 'selected' : '' ?>>Nouveautés</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
                                <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Image mise en avant</label>
                            <input type="text" name="featured_image" class="form-control" value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" style="min-height: 80px;"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contenu (HTML)</label>
                        <textarea name="content" class="form-control" style="min-height: 300px; font-family: monospace;"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?page=blog" class="btn btn-light">← Retour</a>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
            </form>
        </div>

        <?php // ============ QUOTES ============ ?>
        <?php elseif ($page === 'quotes'): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Devis</span>
                <div class="filters" style="margin: 0;">
                    <a href="?page=quotes" class="btn btn-sm <?= empty($_GET['status']) ? 'btn-primary' : 'btn-light' ?>">Tous</a>
                    <a href="?page=quotes&status=pending" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'pending' ? 'btn-primary' : 'btn-light' ?>">En attente</a>
                    <a href="?page=quotes&status=sent" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'sent' ? 'btn-primary' : 'btn-light' ?>">Envoyés</a>
                    <a href="?page=quotes&status=accepted" class="btn btn-sm <?= ($_GET['status'] ?? '') === 'accepted' ? 'btn-primary' : 'btn-light' ?>">Acceptés</a>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Réf</th><th>Client</th><th>Produit</th><th>Qté</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($data['items'] ?? [] as $q): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($q['reference']) ?></strong></td>
                            <td><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($q['product_nom'] ?? '', 0, 30)) ?></td>
                            <td><?= $q['total_pieces'] ?></td>
                            <td><strong><?= number_format($q['prix_total'] ?? 0, 2) ?>€</strong></td>
                            <td>
                                <?php $colors = ['pending' => 'warning', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger']; ?>
                                <span class="badge badge-<?= $colors[$q['status']] ?? 'info' ?>"><?= $q['status'] ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($q['created_at'])) ?></td>
                            <td><a href="?page=quote&id=<?= $q['id'] ?>" class="btn btn-sm btn-light">Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php // ============ QUOTE VIEW ============ ?>
        <?php elseif ($page === 'quote' && $id): ?>
        <?php $q = $data['item'] ?? []; ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
            <div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Devis <?= htmlspecialchars($q['reference']) ?></span>
                        <span class="badge badge-<?= ['pending' => 'warning', 'sent' => 'info', 'accepted' => 'success'][$q['status']] ?? 'info' ?>"><?= $q['status'] ?></span>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Client</h4>
                                <p><strong><?= htmlspecialchars($q['client_prenom'].' '.$q['client_nom']) ?></strong></p>
                                <p><?= htmlspecialchars($q['client_email']) ?></p>
                                <p><?= htmlspecialchars($q['client_telephone']) ?></p>
                                <p><?= htmlspecialchars($q['client_club']) ?></p>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--text-muted); font-size: 12px; text-transform: uppercase;">Produit</h4>
                                <p><strong><?= htmlspecialchars($q['product_nom']) ?></strong></p>
                                <p>Réf: <?= htmlspecialchars($q['product_reference']) ?></p>
                                <p>Sport: <?= htmlspecialchars($q['sport']) ?></p>
                            </div>
                        </div>
                        <hr style="margin: 25px 0; border: none; border-top: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <span style="color: var(--text-muted);">Quantité:</span>
                                <strong style="font-size: 18px; margin-left: 10px;"><?= $q['total_pieces'] ?> pièces</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);">Prix unitaire:</span>
                                <strong style="margin-left: 10px;"><?= number_format($q['prix_unitaire'] ?? 0, 2) ?>€</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);">Total TTC:</span>
                                <strong style="font-size: 24px; color: var(--primary); margin-left: 10px;"><?= number_format($q['prix_total'] ?? 0, 2) ?>€</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Mettre à jour</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_quote">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-control">
                                <option value="pending" <?= $q['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                <option value="sent" <?= $q['status'] === 'sent' ? 'selected' : '' ?>>Envoyé</option>
                                <option value="accepted" <?= $q['status'] === 'accepted' ? 'selected' : '' ?>>Accepté</option>
                                <option value="rejected" <?= $q['status'] === 'rejected' ? 'selected' : '' ?>>Refusé</option>
                                <option value="completed" <?= $q['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes internes</label>
                            <textarea name="notes" class="form-control"><?= htmlspecialchars($q['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
        <a href="?page=quotes" class="btn btn-light" style="margin-top: 20px;">← Retour aux devis</a>

        <?php // ============ SETTINGS ============ ?>
        <?php elseif ($page === 'settings'): ?>
        <?php $s = $data['settings'] ?? []; ?>
        <form method="POST" action="?page=settings" id="settings-form">
            <input type="hidden" name="action" value="save_settings">

            <div class="card">
                <div class="tabs-nav">
                    <button type="button" class="tab-btn active" onclick="switchTab('general')">Général</button>
                    <button type="button" class="tab-btn" onclick="switchTab('social')">Réseaux sociaux</button>
                    <button type="button" class="tab-btn" onclick="switchTab('email')">Email / SMTP</button>
                    <button type="button" class="tab-btn" onclick="switchTab('payment')">Paiement</button>
                    <button type="button" class="tab-btn" onclick="switchTab('shipping')">Livraison</button>
                    <button type="button" class="tab-btn" onclick="switchTab('quotes')">Devis</button>
                    <button type="button" class="tab-btn" onclick="switchTab('configurator')">Configurateur</button>
                    <button type="button" class="tab-btn" onclick="switchTab('tracking')">Tracking</button>
                    <button type="button" class="tab-btn" onclick="switchTab('security')">Sécurité</button>
                </div>

                <!-- TAB: GENERAL -->
                <div class="tab-content active" id="tab-general">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Informations du site</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nom du site</label>
                                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($s['site_name'] ?? 'FLARE CUSTOM') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Slogan</label>
                                <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($s['site_tagline'] ?? '') ?>" placeholder="Votre slogan ici">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email de contact</label>
                                <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($s['site_email'] ?? '') ?>" placeholder="contact@flare-custom.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="site_phone" class="form-control" value="<?= htmlspecialchars($s['site_phone'] ?? '') ?>" placeholder="+33 1 23 45 67 89">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Adresse</label>
                            <textarea name="site_address" class="form-control" style="min-height: 80px;"><?= htmlspecialchars($s['site_address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Logo (URL)</label>
                                <input type="text" name="site_logo" class="form-control" value="<?= htmlspecialchars($s['site_logo'] ?? '') ?>" placeholder="/assets/images/logo.png">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Favicon (URL)</label>
                                <input type="text" name="site_favicon" class="form-control" value="<?= htmlspecialchars($s['site_favicon'] ?? '') ?>" placeholder="/favicon.ico">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SOCIAL -->
                <div class="tab-content" id="tab-social">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Réseaux sociaux</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Facebook</label>
                                <input type="url" name="social_facebook" class="form-control" value="<?= htmlspecialchars($s['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/flarecustom">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Instagram</label>
                                <input type="url" name="social_instagram" class="form-control" value="<?= htmlspecialchars($s['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/flarecustom">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Twitter / X</label>
                                <input type="url" name="social_twitter" class="form-control" value="<?= htmlspecialchars($s['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/flarecustom">
                            </div>
                            <div class="form-group">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" name="social_linkedin" class="form-control" value="<?= htmlspecialchars($s['social_linkedin'] ?? '') ?>" placeholder="https://linkedin.com/company/flarecustom">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">YouTube</label>
                            <input type="url" name="social_youtube" class="form-control" value="<?= htmlspecialchars($s['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@flarecustom">
                        </div>
                    </div>
                </div>

                <!-- TAB: EMAIL -->
                <div class="tab-content" id="tab-email">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Configuration SMTP</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Serveur SMTP</label>
                                <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Port SMTP</label>
                                <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>" placeholder="587">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Utilisateur SMTP</label>
                                <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>" placeholder="user@gmail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mot de passe SMTP</label>
                                <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email expéditeur</label>
                                <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($s['smtp_from_email'] ?? '') ?>" placeholder="noreply@flare-custom.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom expéditeur</label>
                                <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($s['smtp_from_name'] ?? '') ?>" placeholder="FLARE CUSTOM">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email de notification (nouveaux devis)</label>
                            <input type="email" name="notification_email" class="form-control" value="<?= htmlspecialchars($s['notification_email'] ?? '') ?>" placeholder="admin@flare-custom.com">
                        </div>
                    </div>
                </div>

                <!-- TAB: PAYMENT -->
                <div class="tab-content" id="tab-payment">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Mode de paiement</h4>
                        <div class="form-group">
                            <label class="form-label">Mode de fonctionnement</label>
                            <select name="payment_mode" class="form-control" style="max-width: 300px;">
                                <option value="quote" <?= ($s['payment_mode'] ?? '') === 'quote' ? 'selected' : '' ?>>Devis uniquement (pas de paiement en ligne)</option>
                                <option value="stripe" <?= ($s['payment_mode'] ?? '') === 'stripe' ? 'selected' : '' ?>>Paiement Stripe</option>
                                <option value="paypal" <?= ($s['payment_mode'] ?? '') === 'paypal' ? 'selected' : '' ?>>Paiement PayPal</option>
                                <option value="both" <?= ($s['payment_mode'] ?? '') === 'both' ? 'selected' : '' ?>>Stripe + PayPal</option>
                            </select>
                        </div>

                        <h4 style="margin: 30px 0 20px;">Stripe</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Clé publique Stripe</label>
                                <input type="text" name="stripe_public_key" class="form-control" value="<?= htmlspecialchars($s['stripe_public_key'] ?? '') ?>" placeholder="pk_live_...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Clé secrète Stripe</label>
                                <input type="password" name="stripe_secret_key" class="form-control" value="<?= htmlspecialchars($s['stripe_secret_key'] ?? '') ?>" placeholder="sk_live_...">
                            </div>
                        </div>

                        <h4 style="margin: 30px 0 20px;">PayPal</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Client ID PayPal</label>
                                <input type="text" name="paypal_client_id" class="form-control" value="<?= htmlspecialchars($s['paypal_client_id'] ?? '') ?>" placeholder="Client ID...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Secret PayPal</label>
                                <input type="password" name="paypal_secret" class="form-control" value="<?= htmlspecialchars($s['paypal_secret'] ?? '') ?>" placeholder="Secret...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: SHIPPING -->
                <div class="tab-content" id="tab-shipping">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Frais de livraison</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">France métropolitaine (€)</label>
                                <input type="number" step="0.01" name="shipping_france" class="form-control" value="<?= htmlspecialchars($s['shipping_france'] ?? '0') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Europe (€)</label>
                                <input type="number" step="0.01" name="shipping_europe" class="form-control" value="<?= htmlspecialchars($s['shipping_europe'] ?? '0') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">International (€)</label>
                                <input type="number" step="0.01" name="shipping_world" class="form-control" value="<?= htmlspecialchars($s['shipping_world'] ?? '0') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Livraison gratuite au-dessus de (€)</label>
                                <input type="number" step="0.01" name="shipping_free_above" class="form-control" value="<?= htmlspecialchars($s['shipping_free_above'] ?? '0') ?>">
                                <div class="form-hint">Mettre 0 pour désactiver</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Délai de livraison par défaut</label>
                                <input type="text" name="default_delivery_time" class="form-control" value="<?= htmlspecialchars($s['default_delivery_time'] ?? '3-4 semaines') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: QUOTES -->
                <div class="tab-content" id="tab-quotes">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Paramètres des devis</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Préfixe des devis</label>
                                <input type="text" name="quote_prefix" class="form-control" value="<?= htmlspecialchars($s['quote_prefix'] ?? 'DEV-') ?>" placeholder="DEV-">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Validité des devis (jours)</label>
                                <input type="number" name="quote_validity_days" class="form-control" value="<?= htmlspecialchars($s['quote_validity_days'] ?? '30') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Quantité minimum de commande</label>
                                <input type="number" name="min_order_quantity" class="form-control" value="<?= htmlspecialchars($s['min_order_quantity'] ?? '1') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Taux de TVA (%)</label>
                                <input type="number" step="0.01" name="tva_rate" class="form-control" value="<?= htmlspecialchars($s['tva_rate'] ?? '20') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: CONFIGURATOR -->
                <div class="tab-content" id="tab-configurator">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Options du configurateur (par défaut)</h4>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Options de design</h5>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_flare" value="1" <?= ($s['configurator_design_flare'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Design FLARE
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_client" value="1" <?= ($s['configurator_design_client'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Design client
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_design_template" value="1" <?= ($s['configurator_design_template'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Template catalogue
                            </label>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Personnalisation</h5>
                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_nom" value="1" <?= ($s['configurator_perso_nom'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Nom
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_numero" value="1" <?= ($s['configurator_perso_numero'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Numéro
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_logo" value="1" <?= ($s['configurator_perso_logo'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Logo
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="configurator_perso_sponsor" value="1" <?= ($s['configurator_perso_sponsor'] ?? '1') === '1' ? 'checked' : '' ?>>
                                Sponsor
                            </label>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Tailles disponibles</h5>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tailles adultes (séparées par virgule)</label>
                                <input type="text" name="configurator_sizes" class="form-control" value="<?= htmlspecialchars($s['configurator_sizes'] ?? 'XS,S,M,L,XL,XXL,3XL') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tailles enfants (séparées par virgule)</label>
                                <input type="text" name="configurator_sizes_kids" class="form-control" value="<?= htmlspecialchars($s['configurator_sizes_kids'] ?? '6ans,8ans,10ans,12ans,14ans') ?>">
                            </div>
                        </div>

                        <h5 style="margin: 25px 0 15px; color: var(--text-muted);">Options de col</h5>
                        <div class="form-group">
                            <label class="form-label">Types de col (séparés par virgule)</label>
                            <input type="text" name="configurator_collars" class="form-control" value="<?= htmlspecialchars($s['configurator_collars'] ?? 'col_v,col_rond,col_polo') ?>">
                        </div>
                    </div>
                </div>

                <!-- TAB: TRACKING -->
                <div class="tab-content" id="tab-tracking">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Codes de suivi</h4>
                        <div class="form-group">
                            <label class="form-label">Google Analytics (ID)</label>
                            <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars($s['google_analytics'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Google Tag Manager (ID)</label>
                            <input type="text" name="google_tag_manager" class="form-control" value="<?= htmlspecialchars($s['google_tag_manager'] ?? '') ?>" placeholder="GTM-XXXXXXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Pixel (ID)</label>
                            <input type="text" name="meta_pixel" class="form-control" value="<?= htmlspecialchars($s['meta_pixel'] ?? '') ?>" placeholder="1234567890123456">
                        </div>
                    </div>
                </div>

                <!-- TAB: SECURITY -->
                <div class="tab-content" id="tab-security">
                    <div class="card-body">
                        <h4 style="margin-bottom: 20px;">Maintenance</h4>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= ($s['maintenance_mode'] ?? '') === '1' ? 'checked' : '' ?>>
                                <strong>Mode maintenance activé</strong>
                            </label>
                            <div class="form-hint">Le site sera inaccessible aux visiteurs (sauf admin)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Message de maintenance</label>
                            <textarea name="maintenance_message" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($s['maintenance_message'] ?? 'Site en maintenance. Nous revenons bientôt !') ?></textarea>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">

                        <h4 style="margin-bottom: 20px;">Changer le mot de passe admin</h4>
                        </form>
                        <form method="POST" action="?page=settings">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" form="settings-form" class="btn btn-primary">Enregistrer les paramètres</button>
                </div>
            </div>
        </form>

        <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
        </script>

        <?php // ============ IMPORT CSV ============ ?>
        <?php elseif ($page === 'import'): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Import CSV Produits</span>
                </div>
                <form method="POST" action="?page=import" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_csv">
                    <div class="card-body">
                        <div class="alert alert-success" style="background: rgba(80,205,137,0.1); border: 1px solid rgba(80,205,137,0.2); color: var(--success);">
                            <strong>Base de données:</strong> <?= number_format($data['total_products'] ?? 0) ?> produits
                            <?php if ($data['last_import'] ?? null): ?>
                            <br><small>Dernier import: <?= date('d/m/Y H:i', strtotime($data['last_import'])) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fichier CSV</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-hint">Format CSV avec séparateur point-virgule (;)</div>
                        </div>

                        <div style="background: #fafbfc; border-radius: 8px; padding: 15px; margin-top: 20px;">
                            <strong style="font-size: 12px; color: var(--text-muted);">COLONNES SUPPORTÉES:</strong>
                            <p style="font-size: 12px; margin-top: 10px; color: var(--text-dark);">
                                REFERENCE_FLARE, TITRE_VENDEUR, SPORT, FAMILLE_PRODUIT, DESCRIPTION, DESCRIPTION_SEO,
                                TISSU, GRAMMAGE, GENRE, FINITION, QTY_1, QTY_5, QTY_10, QTY_20, QTY_50, QTY_100, QTY_250, QTY_500,
                                PHOTO_1 à PHOTO_5, URL
                            </p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Importer le CSV</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informations</span>
                </div>
                <div class="card-body">
                    <h5 style="margin-bottom: 15px;">Comment ça marche ?</h5>
                    <ul style="color: var(--text-muted); line-height: 2;">
                        <li>Le CSV met à jour les produits existants (par référence)</li>
                        <li>Les nouveaux produits sont créés automatiquement</li>
                        <li>Les produits non présents dans le CSV ne sont pas supprimés</li>
                        <li>Toutes les modifications sont enregistrées en base de données</li>
                    </ul>

                    <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border);">

                    <h5 style="margin-bottom: 15px;">Format attendu</h5>
                    <p style="color: var(--text-muted); font-size: 13px;">
                        Le fichier doit être au format CSV avec un séparateur point-virgule (;).
                        La première ligne doit contenir les noms des colonnes.
                    </p>

                    <div style="background: var(--sidebar-bg); color: #fff; padding: 15px; border-radius: 8px; margin-top: 15px; font-family: monospace; font-size: 11px; overflow-x: auto;">
                        REFERENCE_FLARE;TITRE_VENDEUR;SPORT;QTY_1;QTY_5;...<br>
                        FLARE-MFOOT-001;Maillot Football Pro;Football;45.00;42.00;...
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</main>
<?php endif; ?>

</body>
</html>
