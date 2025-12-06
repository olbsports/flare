<?php
/**
 * IMPORT SPORTS HTML → DATABASE (VERSION WEB)
 * Extrait EXACTEMENT le contenu des pages HTML sports
 */

require_once __DIR__ . '/../config/database.php';

session_start();

$sportsDir = __DIR__ . '/../pages/products/';

// Actions
$resetDone = false;
$resetCount = 0;
if (isset($_POST['reset']) && $_POST['reset'] === '1') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM sport_pages");
        $resetCount = $stmt->fetchColumn();
        $pdo->exec("DELETE FROM sport_pages");
        $pdo->exec("ALTER TABLE sport_pages AUTO_INCREMENT = 1");
        $resetDone = true;
    } catch (Exception $e) {
        // Table n'existe pas encore
    }
}

// Configuration des sports
$sportsConfig = [
    ['file' => 'equipement-football-personnalise-sublimation.html', 'slug' => 'football', 'sport_name' => 'Football', 'sport_icon' => '⚽'],
    ['file' => 'equipement-rugby-personnalise-sublimation.html', 'slug' => 'rugby', 'sport_name' => 'Rugby', 'sport_icon' => '🏉'],
    ['file' => 'equipement-basketball-personnalise-sublimation.html', 'slug' => 'basketball', 'sport_name' => 'Basketball', 'sport_icon' => '🏀'],
    ['file' => 'equipement-cyclisme-velo-personnalise-sublimation.html', 'slug' => 'cyclisme', 'sport_name' => 'Cyclisme', 'sport_icon' => '🚴'],
    ['file' => 'equipement-running-course-pied-personnalise.html', 'slug' => 'running', 'sport_name' => 'Running', 'sport_icon' => '🏃'],
    ['file' => 'equipement-handball-personnalise-sublimation.html', 'slug' => 'handball', 'sport_name' => 'Handball', 'sport_icon' => '🤾'],
    ['file' => 'equipement-volleyball-personnalise-sublimation.html', 'slug' => 'volleyball', 'sport_name' => 'Volleyball', 'sport_icon' => '🏐'],
    ['file' => 'equipement-triathlon-personnalise-sublimation.html', 'slug' => 'triathlon', 'sport_name' => 'Triathlon', 'sport_icon' => '🏊'],
    ['file' => 'equipement-petanque-personnalise-club.html', 'slug' => 'petanque', 'sport_name' => 'Pétanque', 'sport_icon' => '⚾']
];

/**
 * Extraction COMPLETE du contenu HTML
 */
function extractAllContent($html, $config) {
    $data = [
        'slug' => $config['slug'],
        'sport_name' => $config['sport_name'],
        'sport_icon' => $config['sport_icon'],
    ];

    // ========== META ==========
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        $data['meta_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        $data['title'] = trim(explode('|', $data['meta_title'])[0]);
    } else {
        $data['title'] = 'Équipements ' . $config['sport_name'];
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
        $data['hero_eyebrow'] = $config['sport_icon'] . ' ' . $config['sport_name'];
    }

    if (preg_match('/<h1 class=["\']hero-sport-title["\']>([^<]+)<\/h1>/i', $html, $m)) {
        $data['hero_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['hero_title'] = 'Équipements ' . $config['sport_name'];
    }

    if (preg_match('/<p class=["\']hero-sport-subtitle["\']>([^<]+)<\/p>/i', $html, $m)) {
        $data['hero_subtitle'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['hero_subtitle'] = 'Personnalisés Sublimation';
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
        $data['products_eyebrow'] = 'Catalogue ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<section[^>]*class=["\'][^"\']*products-section[^"\']*["\'][^>]*>.*?<h2 class=["\']section-title["\']>([^<]+)<\/h2>/is', $html, $m)) {
        $data['products_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['products_title'] = 'Nos équipements ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<section[^>]*class=["\'][^"\']*products-section[^"\']*["\'][^>]*>.*?<p class=["\']section-description["\']>(.*?)<\/p>/is', $html, $m)) {
        $desc = preg_replace('/<br\s*\/?>/i', ' ', $m[1]);
        $data['products_description'] = trim(strip_tags(html_entity_decode($desc, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['products_description'] = '';
    }

    // ========== WHY US - Extraire EXACTEMENT le contenu ==========
    $data['why_title'] = 'Pourquoi choisir Flare Custom';
    $data['why_subtitle'] = '';
    $data['why_items'] = [];

    // Trouver la section why-us
    if (preg_match('/<section[^>]*class=["\'][^"\']*why-us-section[^"\']*["\'][^>]*>(.*?)<\/section>/is', $html, $whySection)) {
        $whyHtml = $whySection[1];

        // Titre
        if (preg_match('/<h2 class=["\']section-title["\']>([^<]+)<\/h2>/i', $whyHtml, $m)) {
            $data['why_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        // Sous-titre
        if (preg_match('/<p class=["\']section-desc["\']>([^<]+)<\/p>/i', $whyHtml, $m)) {
            $data['why_subtitle'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        // Extraire la grille why-us
        if (preg_match('/<div class=["\']why-us-grid-redesign["\']>(.*?)<\/div>\s*<\/div>\s*<\/section>/is', $whyHtml, $gridMatch)) {
            $gridHtml = $gridMatch[1];

            // Trouver chaque carte - utiliser split sur le début de chaque carte
            $cardParts = preg_split('/<div class=["\']why-us-card-redesign["\']>/is', $gridHtml);

            foreach ($cardParts as $cardHtml) {
                if (empty(trim($cardHtml))) continue;

                $item = ['icon' => '', 'title' => '', 'description' => ''];

                // Extraire le SVG complet
                if (preg_match('/<svg[^>]*>.*?<\/svg>/is', $cardHtml, $svg)) {
                    $item['icon'] = trim($svg[0]);
                }

                // Titre h3
                if (preg_match('/<h3>([^<]+)<\/h3>/i', $cardHtml, $m)) {
                    $item['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
                }

                // Description - prendre le dernier <p> qui contient le texte
                if (preg_match_all('/<p>([^<]+)<\/p>/i', $cardHtml, $pMatches)) {
                    // Prendre le dernier paragraphe (celui avec la description)
                    $lastP = end($pMatches[1]);
                    $item['description'] = trim(html_entity_decode($lastP, ENT_QUOTES, 'UTF-8'));
                }

                if (!empty($item['title'])) {
                    $data['why_items'][] = $item;
                }
            }
        }
    }

    // ========== CTA SECTION ==========
    if (preg_match('/<h2 class=["\']cta-title["\']>(.*?)<\/h2>/is', $html, $m)) {
        $title = preg_replace('/<br\s*\/?>/i', "\n", $m[1]);
        $data['cta_title'] = trim(strip_tags(html_entity_decode($title, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['cta_title'] = "Équipez votre club\nde " . strtolower($config['sport_name']);
    }

    if (preg_match('/<p class=["\']cta-text["\']>(.*?)<\/p>/is', $html, $m)) {
        $data['cta_subtitle'] = trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
    } else {
        $data['cta_subtitle'] = '';
    }

    // Extraire le bouton CTA principal
    if (preg_match('/<a[^>]*class=["\']btn-cta-primary["\'][^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $m)) {
        $data['cta_button_link'] = trim($m[1]);
        $btnText = preg_replace('/<svg.*?<\/svg>/is', '', $m[2]);
        $data['cta_button_text'] = trim(strip_tags(html_entity_decode($btnText, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['cta_button_text'] = 'Demander un devis ' . strtolower($config['sport_name']);
        $data['cta_button_link'] = '/pages/info/contact.html';
    }

    // Extraire le numéro WhatsApp
    if (preg_match('/<a[^>]*class=["\']btn-cta-secondary["\'][^>]*href=["\']https:\/\/wa\.me\/(\d+)["\'][^>]*>([^<]+)<\/a>/is', $html, $m)) {
        $data['cta_whatsapp'] = trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['cta_whatsapp'] = '+33 1 23 45 67 89';
    }

    // ========== FAQ - Extraire EXACTEMENT chaque Q/R ==========
    $data['faq_title'] = 'FAQ ' . $config['sport_name'];
    $data['faq_items'] = [];

    if (preg_match('/<section[^>]*class=["\'][^"\']*faq-sport-section[^"\']*["\'][^>]*>(.*?)<\/section>/is', $html, $faqSection)) {
        $faqHtml = $faqSection[1];

        // Titre FAQ
        if (preg_match('/<h2 class=["\']section-title["\']>([^<]+)<\/h2>/i', $faqHtml, $m)) {
            $data['faq_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }

        // Chaque FAQ item
        if (preg_match_all('/<div class=["\']faq-item["\']>\s*<div class=["\']faq-question["\']>([^<]+)<\/div>\s*<div class=["\']faq-answer["\']>\s*<p>(.*?)<\/p>\s*<\/div>\s*<\/div>/is', $faqHtml, $faqs, PREG_SET_ORDER)) {
            foreach ($faqs as $faq) {
                $data['faq_items'][] = [
                    'question' => trim(html_entity_decode($faq[1], ENT_QUOTES, 'UTF-8')),
                    'answer' => trim(html_entity_decode(strip_tags($faq[2]), ENT_QUOTES, 'UTF-8'))
                ];
            }
        }
    }

    // ========== SEO SECTIONS - Extraire EXACTEMENT le HTML ==========
    $data['seo_sections'] = [];

    // Trouver toutes les sections seo-footer-section
    if (preg_match_all('/<section class=["\']seo-footer-section["\']>(.*?)<\/section>/is', $html, $seoMatches)) {
        foreach ($seoMatches[1] as $seoHtml) {
            $section = [
                'eyebrow' => '',
                'title' => '',
                'blocks' => [],
                'keywords_title' => '',
                'keywords' => ''
            ];

            // Eyebrow
            if (preg_match('/<div class=["\']section-eyebrow["\']>([^<]+)<\/div>/i', $seoHtml, $m)) {
                $section['eyebrow'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            // Title
            if (preg_match('/<h2 class=["\']section-title["\']>([^<]+)<\/h2>/i', $seoHtml, $m)) {
                $section['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            // Content blocks - utiliser split pour capturer TOUS les blocs
            if (preg_match('/<div class=["\']seo-content-grid["\']>(.*?)<\/div>\s*<div class=["\']seo-keywords/is', $seoHtml, $gridMatch)) {
                $gridHtml = $gridMatch[1];

                // Séparer les blocs avec split
                $blockParts = preg_split('/<div class=["\']seo-content-block["\']>/is', $gridHtml);

                foreach ($blockParts as $blockHtml) {
                    if (empty(trim($blockHtml))) continue;

                    $block = ['title' => '', 'content' => ''];

                    // Titre du bloc
                    if (preg_match('/<h3>([^<]+)<\/h3>/i', $blockHtml, $m)) {
                        $block['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
                    }

                    // Tout le contenu après le h3 (paragraphes, listes) - nettoyer les divs de fermeture
                    $content = preg_replace('/<h3>[^<]+<\/h3>/i', '', $blockHtml);
                    $content = preg_replace('/<\/div>\s*$/is', '', $content); // Enlever div fermant en fin
                    $block['content'] = trim($content);

                    if (!empty($block['title']) || !empty($block['content'])) {
                        $section['blocks'][] = $block;
                    }
                }
            }

            // Keywords
            if (preg_match('/<div class=["\']seo-keywords["\']>\s*<h4>([^<]+)<\/h4>\s*<p>([^<]+)<\/p>/is', $seoHtml, $m)) {
                $section['keywords_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
                $section['keywords'] = trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
            }

            if (!empty($section['title']) || !empty($section['blocks'])) {
                $data['seo_sections'][] = $section;
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS sport_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        sport_name VARCHAR(100),
        sport_icon VARCHAR(50),
        meta_title VARCHAR(255),
        meta_description TEXT,
        hero_title VARCHAR(255),
        hero_subtitle TEXT,
        hero_eyebrow VARCHAR(100),
        hero_image VARCHAR(500),
        hero_cta_text VARCHAR(100),
        hero_cta_link VARCHAR(255),
        trust_bar JSON,
        products_title VARCHAR(255),
        products_subtitle TEXT,
        products_eyebrow VARCHAR(100),
        products_description TEXT,
        show_filters BOOLEAN DEFAULT TRUE,
        filter_famille BOOLEAN DEFAULT TRUE,
        filter_genre BOOLEAN DEFAULT TRUE,
        filter_sport BOOLEAN DEFAULT FALSE,
        filter_sort BOOLEAN DEFAULT TRUE,
        products_source ENUM('manual', 'sport', 'famille') DEFAULT 'manual',
        products_sport_filter VARCHAR(100),
        products_famille_filter VARCHAR(100),
        cta_title VARCHAR(255),
        cta_subtitle TEXT,
        cta_features JSON,
        cta_button_text VARCHAR(100),
        cta_button_link VARCHAR(255),
        cta_whatsapp VARCHAR(50),
        why_title VARCHAR(255),
        why_subtitle TEXT,
        why_items JSON,
        faq_title VARCHAR(255),
        faq_items JSON,
        seo_sections JSON,
        active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration: ajouter les colonnes si elles n'existent pas
    try {
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS filter_famille BOOLEAN DEFAULT TRUE");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS filter_genre BOOLEAN DEFAULT TRUE");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS filter_sport BOOLEAN DEFAULT FALSE");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS filter_sort BOOLEAN DEFAULT TRUE");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS products_source ENUM('manual', 'sport', 'famille') DEFAULT 'manual'");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS products_sport_filter VARCHAR(100)");
        $pdo->exec("ALTER TABLE sport_pages ADD COLUMN IF NOT EXISTS products_famille_filter VARCHAR(100)");
    } catch (Exception $e) {
        // Colonnes existent déjà ou autre erreur non bloquante
    }

    // Compter les entrées existantes
    $stmt = $pdo->query("SELECT COUNT(*) FROM sport_pages");
    $existingCount = $stmt->fetchColumn();

    foreach ($sportsConfig as $config) {
        $result = [
            'sport' => $config['sport_name'],
            'icon' => $config['sport_icon'],
            'file' => $config['file'],
            'status' => 'pending',
            'message' => ''
        ];

        $filePath = $sportsDir . $config['file'];

        if (!file_exists($filePath)) {
            $result['status'] = 'error';
            $result['message'] = 'Fichier non trouvé';
            $results[] = $result;
            continue;
        }

        $html = file_get_contents($filePath);
        $data = extractAllContent($html, $config);

        $result['extracted'] = [
            'meta_title' => mb_substr($data['meta_title'] ?? '', 0, 50) . '...',
            'hero_title' => $data['hero_title'] ?? '',
            'trust_bar' => count($data['trust_bar'] ?? []),
            'why_items' => count($data['why_items'] ?? []),
            'faq_items' => count($data['faq_items'] ?? []),
            'seo_sections' => count($data['seo_sections'] ?? [])
        ];

        if ($doImport) {
            // Vérifier si existe
            $stmt = $pdo->prepare("SELECT id FROM sport_pages WHERE slug = ?");
            $stmt->execute([$config['slug']]);
            $existing = $stmt->fetch();

            if ($existing) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE sport_pages SET
                    title = ?, sport_name = ?, sport_icon = ?, meta_title = ?, meta_description = ?,
                    hero_title = ?, hero_subtitle = ?, hero_eyebrow = ?,
                    trust_bar = ?,
                    products_title = ?, products_eyebrow = ?, products_description = ?,
                    cta_title = ?, cta_subtitle = ?, cta_button_text = ?, cta_button_link = ?, cta_whatsapp = ?,
                    why_title = ?, why_subtitle = ?, why_items = ?,
                    faq_title = ?, faq_items = ?,
                    seo_sections = ?
                    WHERE id = ?");

                $stmt->execute([
                    $data['title'],
                    $data['sport_name'],
                    $data['sport_icon'],
                    $data['meta_title'],
                    $data['meta_description'],
                    $data['hero_title'],
                    $data['hero_subtitle'],
                    $data['hero_eyebrow'],
                    json_encode($data['trust_bar'], JSON_UNESCAPED_UNICODE),
                    $data['products_title'],
                    $data['products_eyebrow'],
                    $data['products_description'],
                    $data['cta_title'],
                    $data['cta_subtitle'] ?? '',
                    $data['cta_button_text'],
                    $data['cta_button_link'],
                    $data['cta_whatsapp'],
                    $data['why_title'],
                    $data['why_subtitle'],
                    json_encode($data['why_items'], JSON_UNESCAPED_UNICODE),
                    $data['faq_title'],
                    json_encode($data['faq_items'], JSON_UNESCAPED_UNICODE),
                    json_encode($data['seo_sections'], JSON_UNESCAPED_UNICODE),
                    $existing['id']
                ]);

                $result['status'] = 'updated';
                $result['message'] = 'Mis à jour (ID: ' . $existing['id'] . ')';
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO sport_pages (
                    slug, title, sport_name, sport_icon, meta_title, meta_description,
                    hero_title, hero_subtitle, hero_eyebrow,
                    trust_bar,
                    products_title, products_eyebrow, products_description,
                    cta_title, cta_subtitle, cta_button_text, cta_button_link, cta_whatsapp,
                    why_title, why_subtitle, why_items,
                    faq_title, faq_items,
                    seo_sections,
                    active, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)");

                $stmt->execute([
                    $data['slug'],
                    $data['title'],
                    $data['sport_name'],
                    $data['sport_icon'],
                    $data['meta_title'],
                    $data['meta_description'],
                    $data['hero_title'],
                    $data['hero_subtitle'],
                    $data['hero_eyebrow'],
                    json_encode($data['trust_bar'], JSON_UNESCAPED_UNICODE),
                    $data['products_title'],
                    $data['products_eyebrow'],
                    $data['products_description'],
                    $data['cta_title'],
                    $data['cta_subtitle'] ?? '',
                    $data['cta_button_text'],
                    $data['cta_button_link'],
                    $data['cta_whatsapp'],
                    $data['why_title'],
                    $data['why_subtitle'],
                    json_encode($data['why_items'], JSON_UNESCAPED_UNICODE),
                    $data['faq_title'],
                    json_encode($data['faq_items'], JSON_UNESCAPED_UNICODE),
                    json_encode($data['seo_sections'], JSON_UNESCAPED_UNICODE)
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
    <title>Import Sports HTML → BDD</title>
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
        .sport-item { display: grid; grid-template-columns: 50px 1fr 200px 120px; gap: 1rem; align-items: center; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.5rem; margin-bottom: 0.5rem; }
        .sport-icon { font-size: 1.5rem; text-align: center; }
        .sport-name { font-weight: 600; }
        .sport-file { font-size: 0.75rem; color: #64748b; font-family: monospace; }
        .sport-stats { display: flex; gap: 0.5rem; flex-wrap: wrap; }
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
        <h1>Import Sports HTML → BDD</h1>
        <p class="subtitle">Extrait le contenu EXACT des pages HTML vers la base de données</p>

        <?php if (!$dbConnected): ?>
            <div class="alert alert-error">
                <strong>Erreur BDD:</strong> <?= htmlspecialchars($dbError ?? 'Connexion impossible') ?>
            </div>
        <?php else: ?>

            <?php if ($resetDone): ?>
                <div class="alert alert-warning">
                    <?= $resetCount ?> entrée(s) supprimée(s). La table sport_pages est maintenant vide.
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
                    <div class="summary-label">Sports</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= count(array_filter($results, fn($r) => $r['status'] === 'inserted')) ?></div>
                    <div class="summary-label">Nouveaux</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= count(array_filter($results, fn($r) => $r['status'] === 'updated')) ?></div>
                    <div class="summary-label">Mis à jour</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?= $existingCount ?? 0 ?></div>
                    <div class="summary-label">En BDD</div>
                </div>
            </div>

            <div class="card">
                <?php foreach ($results as $result): ?>
                    <div class="sport-item">
                        <div class="sport-icon"><?= $result['icon'] ?></div>
                        <div>
                            <div class="sport-name"><?= htmlspecialchars($result['sport']) ?></div>
                            <div class="sport-file"><?= htmlspecialchars($result['file']) ?></div>
                        </div>
                        <div class="sport-stats">
                            <?php if (isset($result['extracted'])): ?>
                                <span class="stat">Trust: <?= $result['extracted']['trust_bar'] ?></span>
                                <span class="stat">Why: <?= $result['extracted']['why_items'] ?></span>
                                <span class="stat">FAQ: <?= $result['extracted']['faq_items'] ?></span>
                                <span class="stat">SEO: <?= $result['extracted']['seo_sections'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="status status-<?= $result['status'] ?>">
                            <?= $result['message'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <?php if (!$doImport): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="reset" value="1">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer toutes les pages sports existantes ?')">
                            🗑️ Vider la table
                        </button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="import" value="1">
                        <button type="submit" class="btn btn-primary">
                            🚀 Importer le contenu HTML
                        </button>
                    </form>
                <?php else: ?>
                    <a href="admin.php?page=sport_pages" class="btn btn-primary">📋 Voir les pages sports</a>
                    <a href="import-sports-web.php" class="btn btn-secondary">🔄 Relancer</a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="admin.php">← Retour à l'admin</a>
        </div>
    </div>
</body>
</html>
