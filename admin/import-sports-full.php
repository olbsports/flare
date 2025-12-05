<?php
/**
 * IMPORT COMPLET SPORTS HTML → DATABASE
 * Extrait TOUT le contenu des pages sports HTML vers la BDD
 *
 * Usage: php import-sports-full.php [--dry-run]
 */

require_once __DIR__ . '/../config/database.php';

$dryRun = isset($argv[1]) && $argv[1] === '--dry-run';
$sportsDir = __DIR__ . '/../pages/products/';

// Configuration des sports à importer
$sportsConfig = [
    [
        'file' => 'equipement-football-personnalise-sublimation.html',
        'slug' => 'football',
        'sport_name' => 'Football',
        'sport_icon' => '⚽'
    ],
    [
        'file' => 'equipement-rugby-personnalise-sublimation.html',
        'slug' => 'rugby',
        'sport_name' => 'Rugby',
        'sport_icon' => '🏉'
    ],
    [
        'file' => 'equipement-basketball-personnalise-sublimation.html',
        'slug' => 'basketball',
        'sport_name' => 'Basketball',
        'sport_icon' => '🏀'
    ],
    [
        'file' => 'equipement-cyclisme-velo-personnalise-sublimation.html',
        'slug' => 'cyclisme',
        'sport_name' => 'Cyclisme',
        'sport_icon' => '🚴'
    ],
    [
        'file' => 'equipement-running-course-pied-personnalise.html',
        'slug' => 'running',
        'sport_name' => 'Running',
        'sport_icon' => '🏃'
    ],
    [
        'file' => 'equipement-handball-personnalise-sublimation.html',
        'slug' => 'handball',
        'sport_name' => 'Handball',
        'sport_icon' => '🤾'
    ],
    [
        'file' => 'equipement-volleyball-personnalise-sublimation.html',
        'slug' => 'volleyball',
        'sport_name' => 'Volleyball',
        'sport_icon' => '🏐'
    ],
    [
        'file' => 'equipement-triathlon-personnalise-sublimation.html',
        'slug' => 'triathlon',
        'sport_name' => 'Triathlon',
        'sport_icon' => '🏊'
    ],
    [
        'file' => 'equipement-petanque-personnalise-club.html',
        'slug' => 'petanque',
        'sport_name' => 'Pétanque',
        'sport_icon' => '⚾'
    ]
];

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     IMPORT COMPLET SPORTS HTML → DATABASE                    ║\n";
echo "║     " . ($dryRun ? "MODE: DRY RUN (aucune modification)" : "MODE: IMPORT RÉEL") . "                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = getConnection();

    // Créer/vérifier la table
    createSportPagesTable($pdo);

    $imported = 0;
    $updated = 0;
    $errors = 0;

    foreach ($sportsConfig as $config) {
        $filePath = $sportsDir . $config['file'];
        echo "\n┌─ {$config['sport_icon']} {$config['sport_name']} ({$config['file']})\n";

        if (!file_exists($filePath)) {
            echo "│  ⚠️  Fichier non trouvé!\n";
            $errors++;
            continue;
        }

        // Lire le HTML
        $html = file_get_contents($filePath);
        echo "│  📄 Fichier lu: " . number_format(strlen($html)) . " caractères\n";

        // Extraire TOUT le contenu
        $data = extractAllContent($html, $config);

        // Afficher ce qui a été extrait
        echo "│  📋 Contenu extrait:\n";
        echo "│     • Meta Title: " . mb_substr($data['meta_title'], 0, 50) . "...\n";
        echo "│     • Hero: {$data['hero_eyebrow']} | {$data['hero_title']}\n";
        echo "│     • Trust Bar: " . count($data['trust_bar']) . " items\n";
        echo "│     • Products: {$data['products_eyebrow']}\n";
        echo "│     • FAQ: " . count($data['faq_items']) . " questions\n";
        echo "│     • SEO Sections: " . count($data['seo_sections']) . " sections\n";

        if (!$dryRun) {
            // Vérifier si existe
            $stmt = $pdo->prepare("SELECT id FROM sport_pages WHERE slug = ?");
            $stmt->execute([$config['slug']]);
            $existing = $stmt->fetch();

            if ($existing) {
                // UPDATE
                updateSportPage($pdo, $existing['id'], $data);
                echo "│  ✅ MIS À JOUR (ID: {$existing['id']})\n";
                $updated++;
            } else {
                // INSERT
                $newId = insertSportPage($pdo, $data);
                echo "│  ✅ INSÉRÉ (ID: $newId)\n";
                $imported++;
            }
        } else {
            echo "│  📝 [DRY RUN] Serait importé/mis à jour\n";
            $imported++;
        }

        echo "└────────────────────────────────────────────────────────────\n";
    }

    echo "\n╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  RÉSUMÉ                                                      ║\n";
    echo "╠══════════════════════════════════════════════════════════════╣\n";
    echo "║  Nouveaux imports: $imported                                      ║\n";
    echo "║  Mis à jour: $updated                                             ║\n";
    echo "║  Erreurs: $errors                                                 ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";

    if ($dryRun) {
        echo "\n💡 Pour importer réellement: php import-sports-full.php\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================================
// FONCTIONS
// ============================================================================

function createSportPagesTable($pdo) {
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
}

function extractAllContent($html, $config) {
    $data = [
        'slug' => $config['slug'],
        'sport_name' => $config['sport_name'],
        'sport_icon' => $config['sport_icon'],
    ];

    // ========== META ==========
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        $data['meta_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        $data['title'] = explode('|', $data['meta_title'])[0];
        $data['title'] = trim($data['title']);
    } else {
        $data['title'] = 'Équipements ' . $config['sport_name'];
        $data['meta_title'] = $data['title'] . ' | FLARE CUSTOM';
    }

    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/', $html, $m)) {
        $data['meta_description'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['meta_description'] = "Équipements {$config['sport_name']} personnalisés sublimation. Devis gratuit sous 24h.";
    }

    // ========== HERO SECTION ==========
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
    if (preg_match_all('/<div class=["\']trust-item["\']>\s*<strong>([^<]+)<\/strong>\s*<span>([^<]+)<\/span>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['trust_bar'][] = [
                'value' => trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')),
                'label' => trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'))
            ];
        }
    }

    // ========== PRODUCTS SECTION ==========
    if (preg_match('/<section class=["\']products-section["\'][^>]*>.*?<div class=["\']section-eyebrow["\']>([^<]+)<\/div>/is', $html, $m)) {
        $data['products_eyebrow'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['products_eyebrow'] = 'Catalogue ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<section class=["\']products-section["\'][^>]*>.*?<h2 class=["\']section-title["\']>([^<]+)<\/h2>/is', $html, $m)) {
        $data['products_title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } else {
        $data['products_title'] = 'Nos équipements ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<section class=["\']products-section["\'][^>]*>.*?<p class=["\']section-description["\']>\s*(.*?)\s*<\/p>/is', $html, $m)) {
        $desc = preg_replace('/<br\s*\/?>/i', ' ', $m[1]);
        $data['products_description'] = trim(strip_tags(html_entity_decode($desc, ENT_QUOTES, 'UTF-8')));
    } else {
        $data['products_description'] = "Découvrez notre gamme complète d'équipements {$config['sport_name']} personnalisés.";
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
    }

    $data['cta_button_text'] = 'Demander un devis ' . strtolower($config['sport_name']);
    $data['cta_button_link'] = '/pages/info/contact.html';
    $data['cta_whatsapp'] = '+33612345678';

    // ========== WHY US SECTION ==========
    $data['why_title'] = 'Pourquoi choisir Flare Custom';
    $data['why_subtitle'] = 'La référence européenne en équipements sportifs personnalisés';
    $data['why_items'] = [];

    // Pattern pour extraire les why-us cards
    if (preg_match_all('/<div class=["\']why-us-card-redesign["\']>.*?<h3>([^<]+)<\/h3>.*?<p>([^<]+)<\/p>/is', $html, $matches, PREG_SET_ORDER)) {
        $icons = ['⭐', '✅', '⚡', 'ℹ️', '💰', '🎨'];
        foreach ($matches as $i => $m) {
            $data['why_items'][] = [
                'icon' => $icons[$i] ?? '✓',
                'title' => trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')),
                'description' => trim(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'))
            ];
        }
    }

    // ========== FAQ SECTION ==========
    $data['faq_title'] = 'FAQ ' . $config['sport_name'];
    $data['faq_items'] = [];

    if (preg_match_all('/<div class=["\']faq-item["\']>\s*<div class=["\']faq-question["\']>([^<]+)<\/div>\s*<div class=["\']faq-answer["\']>\s*<p>(.*?)<\/p>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['faq_items'][] = [
                'question' => trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')),
                'answer' => trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'))
            ];
        }
    }

    // ========== SEO SECTIONS ==========
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

            // Content blocks
            if (preg_match_all('/<div class=["\']seo-content-block["\']>(.*?)<\/div>\s*(?=<div class=["\']seo-content-block|<div class=["\']seo-keywords|<\/div>\s*<\/div>)/is', $seoHtml, $blockMatches)) {
                foreach ($blockMatches[1] as $blockHtml) {
                    $block = ['title' => '', 'content' => ''];

                    if (preg_match('/<h3>([^<]+)<\/h3>/i', $blockHtml, $m)) {
                        $block['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
                    }

                    // Get paragraphs and lists
                    $content = '';
                    if (preg_match_all('/<p>(.*?)<\/p>/is', $blockHtml, $pMatches)) {
                        foreach ($pMatches[1] as $p) {
                            $content .= '<p>' . trim($p) . '</p>';
                        }
                    }
                    if (preg_match('/<ul>(.*?)<\/ul>/is', $blockHtml, $m)) {
                        $content .= '<ul>' . $m[1] . '</ul>';
                    }

                    $block['content'] = $content;
                    $section['blocks'][] = $block;
                }
            }

            // Keywords
            if (preg_match('/<div class=["\']seo-keywords["\']>.*?<h4>([^<]+)<\/h4>.*?<p>([^<]+)<\/p>/is', $seoHtml, $m)) {
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

function insertSportPage($pdo, $data) {
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

    return $pdo->lastInsertId();
}

function updateSportPage($pdo, $id, $data) {
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
        $id
    ]);
}
