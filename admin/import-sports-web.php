<?php
/**
 * IMPORT SPORTS HTML → DATABASE (VERSION WEB)
 * Accessible via navigateur pour exécution sur serveur O2Switch
 */

require_once __DIR__ . '/../config/database.php';

// Sécurité basique
session_start();

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

// Fonctions d'extraction
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
    } else {
        $data['cta_subtitle'] = '';
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

// Traitement de l'import
$results = [];
$doImport = isset($_POST['import']) && $_POST['import'] === '1';

try {
    $pdo = getConnection();

    // Créer/vérifier la table
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
            'meta_title' => mb_substr($data['meta_title'], 0, 60) . '...',
            'hero' => $data['hero_eyebrow'] . ' | ' . $data['hero_title'],
            'trust_bar' => count($data['trust_bar']),
            'faq' => count($data['faq_items']),
            'seo_sections' => count($data['seo_sections']),
            'why_items' => count($data['why_items'])
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
            $result['message'] = 'Prêt pour import';
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
    <title>Import Sports HTML → BDD | FLARE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .subtitle {
            color: rgba(255,255,255,0.6);
            margin-bottom: 2rem;
        }
        .card {
            background: rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }
        .sport-grid {
            display: grid;
            gap: 1rem;
        }
        .sport-item {
            background: rgba(255,255,255,0.05);
            border-radius: 0.75rem;
            padding: 1rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
        }
        .sport-icon {
            font-size: 2rem;
            background: rgba(255,255,255,0.1);
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sport-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        .sport-info .file {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            font-family: monospace;
        }
        .sport-extracted {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .badge {
            background: rgba(255,255,255,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-align: center;
            min-width: 120px;
        }
        .status.preview { background: #3b82f6; }
        .status.inserted { background: #10b981; }
        .status.updated { background: #f59e0b; }
        .status.error { background: #ef4444; }
        .btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.75rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(16,185,129,0.3);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }
        .summary {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .value {
            font-size: 2rem;
            font-weight: 700;
        }
        .summary-item .label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
        }
        .success-msg {
            background: rgba(16,185,129,0.2);
            border: 1px solid #10b981;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .error-box {
            background: rgba(239,68,68,0.2);
            border: 1px solid #ef4444;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏆 Import Sports HTML → BDD</h1>
        <p class="subtitle">Extrait le contenu complet des pages sports HTML pour l'insérer en base de données</p>

        <?php if (!$dbConnected): ?>
            <div class="error-box">
                <h3>❌ Erreur de connexion BDD</h3>
                <p><?= htmlspecialchars($dbError ?? 'Connexion impossible') ?></p>
            </div>
        <?php else: ?>

            <?php if ($doImport): ?>
                <div class="success-msg">
                    ✅ Import terminé ! Les pages sports ont été importées dans la base de données.
                </div>
            <?php endif; ?>

            <div class="summary">
                <div class="summary-item">
                    <div class="value"><?= count($results) ?></div>
                    <div class="label">Sports détectés</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= count(array_filter($results, fn($r) => $r['status'] === 'inserted')) ?></div>
                    <div class="label">Nouveaux</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?= count(array_filter($results, fn($r) => $r['status'] === 'updated')) ?></div>
                    <div class="label">Mis à jour</div>
                </div>
            </div>

            <div class="card">
                <div class="sport-grid">
                    <?php foreach ($results as $result): ?>
                        <div class="sport-item">
                            <div class="sport-icon"><?= $result['icon'] ?></div>
                            <div class="sport-info">
                                <h3><?= htmlspecialchars($result['sport']) ?></h3>
                                <div class="file"><?= htmlspecialchars($result['file']) ?></div>
                                <?php if (isset($result['extracted'])): ?>
                                    <div class="sport-extracted">
                                        <span class="badge">Trust: <?= $result['extracted']['trust_bar'] ?></span>
                                        <span class="badge">FAQ: <?= $result['extracted']['faq'] ?></span>
                                        <span class="badge">SEO: <?= $result['extracted']['seo_sections'] ?></span>
                                        <span class="badge">Why: <?= $result['extracted']['why_items'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="status <?= $result['status'] ?>">
                                <?= $result['message'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="actions">
                <?php if (!$doImport): ?>
                    <form method="POST">
                        <input type="hidden" name="import" value="1">
                        <button type="submit" class="btn">🚀 Lancer l'import</button>
                    </form>
                <?php else: ?>
                    <a href="admin.php?page=sport_pages" class="btn">📋 Voir les pages sports</a>
                    <a href="import-sports-web.php" class="btn btn-secondary">🔄 Relancer</a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <div style="text-align: center; margin-top: 3rem; color: rgba(255,255,255,0.4);">
            <a href="admin.php">← Retour à l'admin</a>
        </div>
    </div>
</body>
</html>
