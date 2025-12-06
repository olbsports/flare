<?php
/**
 * IMPORT FAMILLE PAGES HTML → DATABASE
 * Extrait le contenu des pages famille (maillots, shorts, etc.)
 */

require_once __DIR__ . '/../config/database.php';

session_start();

$familleDir = __DIR__ . '/../pages/products/';

// Actions
$resetDone = false;
$resetCount = 0;
if (isset($_POST['reset']) && $_POST['reset'] === '1') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM famille_pages");
        $resetCount = $stmt->fetchColumn();
        $pdo->exec("DELETE FROM famille_pages");
        $pdo->exec("ALTER TABLE famille_pages AUTO_INCREMENT = 1");
        $resetDone = true;
    } catch (Exception $e) {
        // Table n'existe pas encore
    }
}

// Configuration des familles de produits
$famillesConfig = [
    ['file' => 'maillots-sport-personnalises.html', 'slug' => 'maillots', 'famille_name' => 'Maillots', 'famille_icon' => '👕'],
    ['file' => 'shorts-sport-personnalises.html', 'slug' => 'shorts', 'famille_name' => 'Shorts', 'famille_icon' => '🩳'],
    ['file' => 'survetements-personnalises.html', 'slug' => 'survetements', 'famille_name' => 'Survêtements', 'famille_icon' => '🧥'],
    ['file' => 'sweats-capuche-sport-personnalises.html', 'slug' => 'sweats', 'famille_name' => 'Sweats', 'famille_icon' => '🧷'],
    ['file' => 'debardeurs-sport-personnalises.html', 'slug' => 'debardeurs', 'famille_name' => 'Débardeurs', 'famille_icon' => '🎽'],
    ['file' => 'polos-sport-personnalises.html', 'slug' => 'polos', 'famille_name' => 'Polos', 'famille_icon' => '👔'],
    ['file' => 'pantalons-sport-personnalises.html', 'slug' => 'pantalons', 'famille_name' => 'Pantalons', 'famille_icon' => '👖'],
    ['file' => 'gilets-sport-personnalises.html', 'slug' => 'gilets', 'famille_name' => 'Gilets', 'famille_icon' => '🦺'],
    ['file' => 'coupe-vent-sport-personnalises.html', 'slug' => 'coupe-vent', 'famille_name' => 'Coupe-vent', 'famille_icon' => '🌬️'],
    ['file' => 'cuissards-cyclisme-personnalises.html', 'slug' => 'cuissards', 'famille_name' => 'Cuissards', 'famille_icon' => '🚴'],
    ['file' => 'combinaisons-triathlon-personnalisees.html', 'slug' => 'combinaisons', 'famille_name' => 'Combinaisons', 'famille_icon' => '🏊'],
    ['file' => 'corsaires-sport-personnalises.html', 'slug' => 'corsaires', 'famille_name' => 'Corsaires', 'famille_icon' => '🏃'],
];

/**
 * Extraction du contenu HTML pour famille
 */
function extractFamilleContent($html, $config) {
    $data = [
        'slug' => $config['slug'],
        'famille_name' => $config['famille_name'],
        'famille_icon' => $config['famille_icon'],
    ];

    // ========== META ==========
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        $data['meta_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        $data['title'] = trim(explode('|', $data['meta_title'])[0]);
    } else {
        $data['title'] = $config['famille_name'] . ' Personnalisés';
        $data['meta_title'] = $data['title'] . ' | FLARE CUSTOM';
    }

    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
        $data['meta_description'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['meta_description'] = '';
    }

    // ========== HERO ==========
    if (preg_match('/<span class=["\']hero-sport-eyebrow["\']>([^<]+)<\/span>/i', $html, $m)) {
        $data['hero_eyebrow'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['hero_eyebrow'] = $config['famille_name'] . ' personnalisés';
    }

    if (preg_match('/<h1 class=["\']hero-sport-title["\']>([^<]+)<\/h1>/i', $html, $m)) {
        $data['hero_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['hero_title'] = $config['famille_name'] . ' Sport Sublimation';
    }

    if (preg_match('/<p class=["\']hero-sport-subtitle["\']>([^<]+)<\/p>/i', $html, $m)) {
        $data['hero_subtitle'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['hero_subtitle'] = '';
    }

    // ========== TRUST BAR ==========
    $data['trust_bar'] = [];
    if (preg_match_all('/<div class=["\']trust-item["\']>\s*<strong>([^<]+)<\/strong>\s*<span>([^<]+)<\/span>\s*<\/div>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['trust_bar'][] = [
                'value' => trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')),
                'label' => trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'))
            ];
        }
    }

    // ========== PRODUCTS SECTION ==========
    if (preg_match('/<section[^>]*class=["\'][^"\']*products-section[^"\']*["\'][^>]*>.*?<div class=["\']section-eyebrow["\']>([^<]+)<\/div>/is', $html, $m)) {
        $data['products_eyebrow'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['products_eyebrow'] = 'Catalogue ' . $config['famille_name'];
    }

    if (preg_match('/<section[^>]*class=["\'][^"\']*products-section[^"\']*["\'][^>]*>.*?<h2 class=["\']section-title["\']>([^<]+)<\/h2>/is', $html, $m)) {
        $data['products_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['products_title'] = 'Nos ' . strtolower($config['famille_name']) . ' personnalisés';
    }

    if (preg_match('/<section[^>]*class=["\'][^"\']*products-section[^"\']*["\'][^>]*>.*?<p class=["\']section-description["\']>(.*?)<\/p>/is', $html, $m)) {
        $desc = preg_replace('/<br\s*\/?>/i', ' ', $m[1]);
        $data['products_description'] = trim(strip_tags(html_entity_decode($desc, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['products_description'] = '';
    }

    // ========== CTA SECTION (cta-redesign) ==========
    if (preg_match('/<h2 class=["\']cta-redesign-title["\']>(.*?)<\/h2>/is', $html, $m)) {
        $data['cta_title'] = trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
    } elseif (preg_match('/<h2 class=["\']cta-title["\']>(.*?)<\/h2>/is', $html, $m)) {
        $title = preg_replace('/<br\s*\/?>/i', "\n", $m[1]);
        $data['cta_title'] = trim(strip_tags(html_entity_decode($title, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['cta_title'] = "Créons ensemble vos " . strtolower($config['famille_name']);
    }

    if (preg_match('/<p class=["\']cta-redesign-subtitle["\']>(.*?)<\/p>/is', $html, $m)) {
        $data['cta_subtitle'] = trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
    } elseif (preg_match('/<p class=["\']cta-text["\']>(.*?)<\/p>/is', $html, $m)) {
        $data['cta_subtitle'] = trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
    } else {
        $data['cta_subtitle'] = '';
    }

    // CTA Features
    $data['cta_features'] = [];
    if (preg_match_all('/<div class=["\']cta-feature["\']>.*?<span>([^<]+)<\/span>/is', $html, $features)) {
        foreach ($features[1] as $feature) {
            $data['cta_features'][] = trim(html_entity_decode($feature, ENT_QUOTES, 'UTF-8'));
        }
    }

    // CTA Buttons
    if (preg_match('/<a[^>]*class=["\']btn-cta-main["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/is', $html, $m)) {
        $data['cta_button_link'] = trim($m[1]);
    } else {
        $data['cta_button_link'] = '/pages/info/contact.html';
    }
    $data['cta_button_text'] = 'Demander un Devis';

    if (preg_match('/<a[^>]*class=["\']btn-cta-secondary["\'][^>]*href=["\']https:\/\/wa\.me\/(\d+)["\'][^>]*>/is', $html, $m)) {
        $data['cta_whatsapp'] = '+' . $m[1];
    } else {
        $data['cta_whatsapp'] = '+33 1 23 45 67 89';
    }

    // ========== SEO MEGA SECTION ==========
    $data['seo_hero_badge'] = '';
    $data['seo_hero_title'] = '';
    $data['seo_hero_intro'] = '';
    $data['seo_cards'] = [];
    $data['seo_content_blocks'] = [];
    $data['seo_stats'] = [];

    if (preg_match('/<section class=["\']seo-mega["\']>(.*?)<\/section>/is', $html, $seoSection)) {
        $seoHtml = $seoSection[1];

        // SEO Hero
        if (preg_match('/<div class=["\']seo-hero-badge["\']>([^<]+)<\/div>/i', $seoHtml, $m)) {
            $data['seo_hero_badge'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        if (preg_match('/<h2 class=["\']seo-hero-title["\']>([^<]+)<\/h2>/i', $seoHtml, $m)) {
            $data['seo_hero_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        if (preg_match('/<p class=["\']seo-hero-intro["\']>([^<]+)<\/p>/i', $seoHtml, $m)) {
            $data['seo_hero_intro'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        // SEO Cards
        if (preg_match_all('/<div class=["\']seo-card["\']>.*?<div class=["\']seo-card-icon["\']>([^<]+)<\/div>\s*<h3>([^<]+)<\/h3>\s*<p>([^<]+)<\/p>/is', $seoHtml, $cards, PREG_SET_ORDER)) {
            foreach ($cards as $card) {
                $data['seo_cards'][] = [
                    'icon' => trim($card[1]),
                    'title' => trim(html_entity_decode($card[2], ENT_QUOTES, 'UTF-8')),
                    'content' => trim(html_entity_decode($card[3], ENT_QUOTES, 'UTF-8'))
                ];
            }
        }

        // SEO Stats
        if (preg_match_all('/<div class=["\']seo-stat["\']>\s*<div class=["\']seo-stat-number["\']>([^<]+)<\/div>\s*<div class=["\']seo-stat-label["\']>([^<]+)<\/div>/is', $seoHtml, $stats, PREG_SET_ORDER)) {
            foreach ($stats as $stat) {
                $data['seo_stats'][] = [
                    'number' => trim(html_entity_decode($stat[1], ENT_QUOTES, 'UTF-8')),
                    'label' => trim(html_entity_decode($stat[2], ENT_QUOTES, 'UTF-8'))
                ];
            }
        }

        // SEO Full Content Block
        if (preg_match('/<div class=["\']seo-full-content["\']>(.*?)<\/div>/is', $seoHtml, $fullContent)) {
            $data['seo_full_content'] = trim($fullContent[1]);
        }

        // SEO Alternating Blocks
        $cardParts = preg_split('/<div class=["\']seo-alt-block["\']>/is', $seoHtml);
        foreach ($cardParts as $idx => $blockHtml) {
            if ($idx === 0) continue;

            $block = ['title' => '', 'content' => '', 'list' => []];

            if (preg_match('/<h3>([^<]+)<\/h3>/i', $blockHtml, $m)) {
                $block['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            if (preg_match_all('/<p>([^<]+)<\/p>/is', $blockHtml, $paras)) {
                $block['content'] = implode("\n\n", array_map(fn($p) => trim(html_entity_decode($p, ENT_QUOTES, 'UTF-8')), $paras[1]));
            }

            if (preg_match_all('/<li>([^<]+)<\/li>/is', $blockHtml, $items)) {
                $block['list'] = array_map(fn($i) => trim(html_entity_decode($i, ENT_QUOTES, 'UTF-8')), $items[1]);
            }

            if (!empty($block['title'])) {
                $data['seo_content_blocks'][] = $block;
            }
        }
    }

    return $data;
}

// Traitement
$results = [];
$doImport = isset($_POST['import']) && $_POST['import'] === '1';

try {
    $pdo = getConnection();

    // Créer la table
    $pdo->exec("CREATE TABLE IF NOT EXISTS famille_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        famille_name VARCHAR(100),
        famille_icon VARCHAR(50),
        meta_title VARCHAR(255),
        meta_description TEXT,
        hero_title VARCHAR(255),
        hero_subtitle TEXT,
        hero_eyebrow VARCHAR(100),
        hero_image VARCHAR(500),
        hero_cta_text VARCHAR(100),
        hero_cta_link VARCHAR(255),
        trust_bar JSON,
        intro_text TEXT,
        show_sports_links BOOLEAN DEFAULT TRUE,
        sports_links_eyebrow VARCHAR(100) DEFAULT 'Par sport',
        sports_links_title VARCHAR(255),
        products_title VARCHAR(255),
        products_subtitle TEXT,
        products_eyebrow VARCHAR(100),
        products_description TEXT,
        show_filters BOOLEAN DEFAULT TRUE,
        filter_famille BOOLEAN DEFAULT FALSE,
        filter_genre BOOLEAN DEFAULT TRUE,
        filter_sport BOOLEAN DEFAULT TRUE,
        filter_sort BOOLEAN DEFAULT TRUE,
        products_source ENUM('manual', 'sport', 'famille') DEFAULT 'famille',
        products_sport_filter VARCHAR(100),
        products_famille_filter VARCHAR(100),
        cta_title VARCHAR(255),
        cta_subtitle TEXT,
        cta_features JSON,
        cta_button_text VARCHAR(100),
        cta_button_link VARCHAR(255),
        cta_whatsapp VARCHAR(50),
        seo_hero_badge VARCHAR(100),
        seo_hero_title VARCHAR(500),
        seo_hero_intro TEXT,
        seo_cards JSON,
        seo_stats JSON,
        seo_full_content TEXT,
        seo_content_blocks JSON,
        longtail_eyebrow VARCHAR(100) DEFAULT 'Guide complet',
        longtail_title VARCHAR(255),
        longtail_blocks JSON,
        faq_eyebrow VARCHAR(100) DEFAULT 'Questions fréquentes',
        faq_title VARCHAR(255),
        faq_items JSON,
        final_cta_title VARCHAR(255) DEFAULT 'Prêt à équiper votre équipe ?',
        final_cta_text TEXT,
        final_cta_button_text VARCHAR(100) DEFAULT 'Demander un devis gratuit',
        final_cta_button_link VARCHAR(255) DEFAULT '/pages/info/contact.html',
        active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ajouter les nouvelles colonnes si la table existe déjà (migration)
    $newColumns = [
        'intro_text' => 'TEXT',
        'show_sports_links' => 'BOOLEAN DEFAULT TRUE',
        'sports_links_eyebrow' => "VARCHAR(100) DEFAULT 'Par sport'",
        'sports_links_title' => 'VARCHAR(255)',
        'longtail_eyebrow' => "VARCHAR(100) DEFAULT 'Guide complet'",
        'longtail_title' => 'VARCHAR(255)',
        'longtail_blocks' => 'JSON',
        'faq_eyebrow' => "VARCHAR(100) DEFAULT 'Questions fréquentes'",
        'faq_title' => 'VARCHAR(255)',
        'faq_items' => 'JSON',
        'final_cta_title' => "VARCHAR(255) DEFAULT 'Prêt à équiper votre équipe ?'",
        'final_cta_text' => 'TEXT',
        'final_cta_button_text' => "VARCHAR(100) DEFAULT 'Demander un devis gratuit'",
        'final_cta_button_link' => "VARCHAR(255) DEFAULT '/pages/info/contact.html'"
    ];

    foreach ($newColumns as $colName => $colDef) {
        try {
            $pdo->exec("ALTER TABLE famille_pages ADD COLUMN $colName $colDef");
        } catch (PDOException $e) {
            // Colonne existe déjà, ignorer
        }
    }

    // Compter les entrées existantes
    $stmt = $pdo->query("SELECT COUNT(*) FROM famille_pages");
    $existingCount = $stmt->fetchColumn();

    foreach ($famillesConfig as $config) {
        $result = [
            'famille' => $config['famille_name'],
            'icon' => $config['famille_icon'],
            'file' => $config['file'],
            'status' => 'pending',
            'message' => ''
        ];

        $filePath = $familleDir . $config['file'];

        if (!file_exists($filePath)) {
            $result['status'] = 'error';
            $result['message'] = 'Fichier non trouvé';
            $results[] = $result;
            continue;
        }

        $html = file_get_contents($filePath);
        $data = extractFamilleContent($html, $config);

        $result['extracted'] = [
            'meta_title' => mb_substr($data['meta_title'] ?? '', 0, 50) . '...',
            'hero_title' => $data['hero_title'] ?? '',
            'trust_bar' => count($data['trust_bar'] ?? []),
            'seo_cards' => count($data['seo_cards'] ?? []),
            'seo_blocks' => count($data['seo_content_blocks'] ?? [])
        ];

        if ($doImport) {
            // Vérifier si existe
            $stmt = $pdo->prepare("SELECT id FROM famille_pages WHERE slug = ?");
            $stmt->execute([$config['slug']]);
            $existing = $stmt->fetch();

            if ($existing) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE famille_pages SET
                    title = ?, famille_name = ?, famille_icon = ?, meta_title = ?, meta_description = ?,
                    hero_title = ?, hero_subtitle = ?, hero_eyebrow = ?,
                    trust_bar = ?,
                    products_title = ?, products_eyebrow = ?, products_description = ?,
                    products_famille_filter = ?,
                    cta_title = ?, cta_subtitle = ?, cta_features = ?, cta_button_text = ?, cta_button_link = ?, cta_whatsapp = ?,
                    seo_hero_badge = ?, seo_hero_title = ?, seo_hero_intro = ?,
                    seo_cards = ?, seo_stats = ?, seo_full_content = ?, seo_content_blocks = ?
                    WHERE id = ?");

                $stmt->execute([
                    $data['title'],
                    $data['famille_name'],
                    $data['famille_icon'],
                    $data['meta_title'],
                    $data['meta_description'],
                    $data['hero_title'],
                    $data['hero_subtitle'],
                    $data['hero_eyebrow'],
                    json_encode($data['trust_bar'], JSON_UNESCAPED_UNICODE),
                    $data['products_title'],
                    $data['products_eyebrow'],
                    $data['products_description'],
                    $data['famille_name'], // Filter by famille name
                    $data['cta_title'],
                    $data['cta_subtitle'],
                    json_encode($data['cta_features'], JSON_UNESCAPED_UNICODE),
                    $data['cta_button_text'],
                    $data['cta_button_link'],
                    $data['cta_whatsapp'],
                    $data['seo_hero_badge'],
                    $data['seo_hero_title'],
                    $data['seo_hero_intro'],
                    json_encode($data['seo_cards'], JSON_UNESCAPED_UNICODE),
                    json_encode($data['seo_stats'], JSON_UNESCAPED_UNICODE),
                    $data['seo_full_content'] ?? '',
                    json_encode($data['seo_content_blocks'], JSON_UNESCAPED_UNICODE),
                    $existing['id']
                ]);

                $result['status'] = 'updated';
                $result['message'] = 'Mis à jour (ID: ' . $existing['id'] . ')';
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO famille_pages (
                    slug, title, famille_name, famille_icon, meta_title, meta_description,
                    hero_title, hero_subtitle, hero_eyebrow,
                    trust_bar,
                    products_title, products_eyebrow, products_description,
                    products_source, products_famille_filter,
                    cta_title, cta_subtitle, cta_features, cta_button_text, cta_button_link, cta_whatsapp,
                    seo_hero_badge, seo_hero_title, seo_hero_intro,
                    seo_cards, seo_stats, seo_full_content, seo_content_blocks,
                    active, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'famille', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)");

                $stmt->execute([
                    $data['slug'],
                    $data['title'],
                    $data['famille_name'],
                    $data['famille_icon'],
                    $data['meta_title'],
                    $data['meta_description'],
                    $data['hero_title'],
                    $data['hero_subtitle'],
                    $data['hero_eyebrow'],
                    json_encode($data['trust_bar'], JSON_UNESCAPED_UNICODE),
                    $data['products_title'],
                    $data['products_eyebrow'],
                    $data['products_description'],
                    $data['famille_name'], // Filter by famille name
                    $data['cta_title'],
                    $data['cta_subtitle'],
                    json_encode($data['cta_features'], JSON_UNESCAPED_UNICODE),
                    $data['cta_button_text'],
                    $data['cta_button_link'],
                    $data['cta_whatsapp'],
                    $data['seo_hero_badge'],
                    $data['seo_hero_title'],
                    $data['seo_hero_intro'],
                    json_encode($data['seo_cards'], JSON_UNESCAPED_UNICODE),
                    json_encode($data['seo_stats'], JSON_UNESCAPED_UNICODE),
                    $data['seo_full_content'] ?? '',
                    json_encode($data['seo_content_blocks'], JSON_UNESCAPED_UNICODE)
                ]);

                $newId = $pdo->lastInsertId();
                $result['status'] = 'inserted';
                $result['message'] = 'Inséré (ID: ' . $newId . ')';
            }
        } else {
            $result['status'] = 'preview';
            $result['message'] = 'Prêt';
        }

        $results[] = $result;
    }

    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Famille Pages HTML → BDD</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #0f172a; color: #fff; padding: 2rem; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .subtitle { color: #94a3b8; margin-bottom: 2rem; }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        .alert-success { background: rgba(34,197,94,0.2); border: 1px solid #22c55e; }
        .alert-warning { background: rgba(234,179,8,0.2); border: 1px solid #eab308; }
        .alert-error { background: rgba(239,68,68,0.2); border: 1px solid #ef4444; }
        .card { background: rgba(255,255,255,0.05); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; }
        .famille-item { display: grid; grid-template-columns: 50px 1fr 200px 120px; gap: 1rem; align-items: center; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem; margin-bottom: 0.5rem; }
        .famille-icon { font-size: 1.5rem; text-align: center; }
        .famille-name { font-weight: 600; }
        .famille-file { font-size: 0.75rem; color: #64748b; font-family: monospace; }
        .famille-stats { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .stat { background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; }
        .status { padding: 0.4rem 0.8rem; border-radius: 0.3rem; font-size: 0.8rem; text-align: center; font-weight: 500; }
        .status-preview { background: #3b82f6; }
        .status-inserted { background: #22c55e; }
        .status-updated { background: #f59e0b; }
        .status-error { background: #ef4444; }
        .actions { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; border: none; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .summary { display: flex; gap: 2rem; justify-content: center; margin-bottom: 2rem; }
        .summary-item { text-align: center; }
        .summary-value { font-size: 2rem; font-weight: 700; }
        .summary-label { font-size: 0.8rem; color: #94a3b8; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Famille Pages HTML → BDD</h1>
        <p class="subtitle">Extrait le contenu des pages famille (maillots, shorts, etc.) vers la base de données</p>

        <?php if (!$dbConnected): ?>
            <div class="alert alert-error">
                <strong>Erreur BDD:</strong> <?= htmlspecialchars($dbError ?? 'Connexion impossible') ?>
            </div>
        <?php else: ?>

            <?php if ($resetDone): ?>
                <div class="alert alert-warning">
                    <?= $resetCount ?> entrée(s) supprimée(s). La table famille_pages est maintenant vide.
                </div>
            <?php endif; ?>

            <?php if ($doImport): ?>
                <div class="alert alert-success">
                    Import terminé ! Contenu HTML extrait et importé dans la base de données.
                </div>
            <?php endif; ?>

            <div class="summary">
                <div class="summary-item">
                    <div class="summary-value"><?= count($results) ?></div>
                    <div class="summary-label">Familles</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $existingCount ?? 0 ?></div>
                    <div class="summary-label">En BDD</div>
                </div>
            </div>

            <div class="card">
                <?php foreach ($results as $r): ?>
                <div class="famille-item">
                    <div class="famille-icon"><?= $r['icon'] ?></div>
                    <div>
                        <div class="famille-name"><?= htmlspecialchars($r['famille']) ?></div>
                        <div class="famille-file"><?= htmlspecialchars($r['file']) ?></div>
                    </div>
                    <div class="famille-stats">
                        <?php if (isset($r['extracted'])): ?>
                            <span class="stat">Trust: <?= $r['extracted']['trust_bar'] ?></span>
                            <span class="stat">SEO: <?= $r['extracted']['seo_cards'] ?> cards</span>
                            <span class="stat">Blocks: <?= $r['extracted']['seo_blocks'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status status-<?= $r['status'] ?>">
                        <?= $r['status'] === 'preview' ? '🔍 Prêt' : ($r['status'] === 'inserted' ? '✅ Créé' : ($r['status'] === 'updated' ? '🔄 MAJ' : '❌ Erreur')) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="import" value="1">
                    <button type="submit" class="btn btn-primary">🚀 Importer dans la BDD</button>
                </form>
                <form method="post" style="display: inline;" onsubmit="return confirm('Supprimer toutes les entrées famille_pages ?');">
                    <input type="hidden" name="reset" value="1">
                    <button type="submit" class="btn btn-danger">🗑️ Reset table</button>
                </form>
                <a href="admin.php?page=famille_pages" class="btn btn-secondary">← Retour admin</a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
