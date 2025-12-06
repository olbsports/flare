<?php
/**
 * IMPORT SPORTS HTML → DATABASE
 * Script pour importer automatiquement les pages sports HTML existantes
 * vers la table sport_pages de la base de données
 *
 * Usage: php import-sports.php
 */

require_once __DIR__ . '/../config/database.php';

// Configuration
$sportsDir = __DIR__ . '/../pages/_archive/sports/';
$dryRun = isset($argv[1]) && $argv[1] === '--dry-run';

// Sports à importer avec leurs patterns de fichiers
$sportsConfig = [
    'football' => [
        'file' => 'equipement-football-personnalise-sublimation.html',
        'sport_name' => 'Football',
        'sport_icon' => '⚽',
        'slug' => 'football'
    ],
    'rugby' => [
        'file' => 'equipement-rugby-personnalise-sublimation.html',
        'sport_name' => 'Rugby',
        'sport_icon' => '🏉',
        'slug' => 'rugby'
    ],
    'basketball' => [
        'file' => 'equipement-basketball-personnalise-sublimation.html',
        'sport_name' => 'Basketball',
        'sport_icon' => '🏀',
        'slug' => 'basketball'
    ],
    'cyclisme' => [
        'file' => 'equipement-cyclisme-velo-personnalise-sublimation.html',
        'sport_name' => 'Cyclisme',
        'sport_icon' => '🚴',
        'slug' => 'cyclisme'
    ],
    'running' => [
        'file' => 'equipement-running-course-pied-personnalise.html',
        'sport_name' => 'Running',
        'sport_icon' => '🏃',
        'slug' => 'running'
    ],
    'handball' => [
        'file' => 'equipement-handball-personnalise-sublimation.html',
        'sport_name' => 'Handball',
        'sport_icon' => '🤾',
        'slug' => 'handball'
    ],
    'volleyball' => [
        'file' => 'equipement-volleyball-personnalise-sublimation.html',
        'sport_name' => 'Volleyball',
        'sport_icon' => '🏐',
        'slug' => 'volleyball'
    ],
    'triathlon' => [
        'file' => 'equipement-triathlon-personnalise-sublimation.html',
        'sport_name' => 'Triathlon',
        'sport_icon' => '🏊',
        'slug' => 'triathlon'
    ],
    'petanque' => [
        'file' => 'equipement-petanque-personnalise-club.html',
        'sport_name' => 'Pétanque',
        'sport_icon' => '⚾',
        'slug' => 'petanque'
    ]
];

echo "=== IMPORT SPORTS HTML → DATABASE ===\n";
echo $dryRun ? "Mode: DRY RUN (aucune modification)\n\n" : "Mode: IMPORT RÉEL\n\n";

try {
    $pdo = getConnection();

    // Vérifier/créer la table sport_pages
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

    $imported = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($sportsConfig as $key => $config) {
        $filePath = $sportsDir . $config['file'];
        echo "Traitement: {$config['sport_name']} ({$config['file']})...\n";

        if (!file_exists($filePath)) {
            echo "  ⚠ Fichier non trouvé: {$filePath}\n";
            $errors++;
            continue;
        }

        // Vérifier si déjà importé
        $stmt = $pdo->prepare("SELECT id FROM sport_pages WHERE slug = ?");
        $stmt->execute([$config['slug']]);
        if ($stmt->fetch()) {
            echo "  ⏭ Déjà importé (slug: {$config['slug']})\n";
            $skipped++;
            continue;
        }

        // Lire et parser le HTML
        $html = file_get_contents($filePath);
        $data = parseHtmlSportPage($html, $config);

        if ($dryRun) {
            echo "  📋 Données extraites:\n";
            echo "     - Title: " . ($data['title'] ?? 'N/A') . "\n";
            echo "     - Meta Title: " . mb_substr($data['meta_title'] ?? 'N/A', 0, 50) . "...\n";
            echo "     - Hero Title: " . ($data['hero_title'] ?? 'N/A') . "\n";
            echo "     - Trust Bar: " . count($data['trust_bar'] ?? []) . " items\n";
            echo "     - FAQ: " . count($data['faq_items'] ?? []) . " questions\n";
            echo "     - SEO Sections: " . count($data['seo_sections'] ?? []) . " sections\n";
        } else {
            // Insérer en base
            $stmt = $pdo->prepare("INSERT INTO sport_pages (
                slug, title, sport_name, sport_icon, meta_title, meta_description,
                hero_title, hero_subtitle, hero_eyebrow, hero_image,
                products_title, products_subtitle, products_eyebrow, products_description,
                cta_title, cta_subtitle, cta_button_text, cta_button_link,
                why_title, faq_title,
                trust_bar, cta_features, why_items, faq_items, seo_sections,
                active, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

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
                $data['hero_image'] ?? '',
                $data['products_title'],
                $data['products_subtitle'] ?? '',
                $data['products_eyebrow'],
                $data['products_description'],
                $data['cta_title'],
                $data['cta_subtitle'] ?? '',
                $data['cta_button_text'],
                $data['cta_button_link'],
                $data['why_title'],
                $data['faq_title'],
                json_encode($data['trust_bar'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($data['cta_features'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($data['why_items'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($data['faq_items'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($data['seo_sections'] ?? [], JSON_UNESCAPED_UNICODE),
                $imported
            ]);

            echo "  ✅ Importé avec succès (ID: " . $pdo->lastInsertId() . ")\n";
        }

        $imported++;
    }

    echo "\n=== RÉSUMÉ ===\n";
    echo "Importés: $imported\n";
    echo "Ignorés: $skipped\n";
    echo "Erreurs: $errors\n";

    if ($dryRun) {
        echo "\n💡 Pour importer réellement, exécutez sans --dry-run\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Parse une page HTML sport et extrait les données structurées
 */
function parseHtmlSportPage($html, $config) {
    $data = [
        'slug' => $config['slug'],
        'sport_name' => $config['sport_name'],
        'sport_icon' => $config['sport_icon'],
    ];

    // Meta title
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        $data['meta_title'] = trim(html_entity_decode($m[1]));
        $data['title'] = explode('|', $data['meta_title'])[0];
        $data['title'] = trim($data['title']);
    } else {
        $data['title'] = 'Équipements ' . $config['sport_name'];
        $data['meta_title'] = $data['title'] . ' | FLARE CUSTOM';
    }

    // Meta description
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/', $html, $m)) {
        $data['meta_description'] = trim(html_entity_decode($m[1]));
    }

    // Hero section
    if (preg_match('/<span class=["\']hero-sport-eyebrow["\']>([^<]+)<\/span>/i', $html, $m)) {
        $data['hero_eyebrow'] = trim(html_entity_decode($m[1]));
    } else {
        $data['hero_eyebrow'] = $config['sport_icon'] . ' ' . $config['sport_name'];
    }

    if (preg_match('/<h1 class=["\']hero-sport-title["\']>([^<]+)<\/h1>/i', $html, $m)) {
        $data['hero_title'] = trim(html_entity_decode($m[1]));
    } else {
        $data['hero_title'] = 'Équipements ' . $config['sport_name'];
    }

    if (preg_match('/<p class=["\']hero-sport-subtitle["\']>([^<]+)<\/p>/i', $html, $m)) {
        $data['hero_subtitle'] = trim(html_entity_decode($m[1]));
    } else {
        $data['hero_subtitle'] = 'Personnalisés Sublimation';
    }

    // Trust bar
    $data['trust_bar'] = [];
    if (preg_match_all('/<div class=["\']trust-item["\']>\s*<strong>([^<]+)<\/strong>\s*<span>([^<]+)<\/span>/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['trust_bar'][] = [
                'value' => trim($m[1]),
                'label' => trim($m[2])
            ];
        }
    }

    // Default trust bar if not found
    if (empty($data['trust_bar'])) {
        $data['trust_bar'] = [
            ['value' => '500+', 'label' => 'Clubs équipés'],
            ['value' => '4.9/5', 'label' => 'Satisfaction client'],
            ['value' => '48h', 'label' => 'Devis sous 48h'],
            ['value' => '100%', 'label' => 'Sublimation française']
        ];
    }

    // Products section
    if (preg_match('/<div class=["\']section-eyebrow["\']>([^<]+)<\/div>/i', $html, $m)) {
        $data['products_eyebrow'] = trim(html_entity_decode($m[1]));
    } else {
        $data['products_eyebrow'] = 'Catalogue ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<h2 class=["\']section-title["\']>Nos équipements[^<]*<\/h2>/i', $html, $m)) {
        $data['products_title'] = 'Nos équipements ' . strtolower($config['sport_name']);
    } else {
        $data['products_title'] = 'Nos équipements ' . strtolower($config['sport_name']);
    }

    if (preg_match('/<p class=["\']section-description["\']>\s*([^<]+(?:<br[^>]*>[^<]+)*)\s*<\/p>/i', $html, $m)) {
        $data['products_description'] = trim(strip_tags(str_replace('<br>', ' ', $m[1])));
    } else {
        $data['products_description'] = "Découvrez notre gamme complète d'équipements {$config['sport_name']} personnalisés par sublimation.";
    }

    // CTA section
    if (preg_match('/<h2 class=["\']cta-title["\']>([^<]+(?:<br[^>]*>[^<]+)*)<\/h2>/i', $html, $m)) {
        $data['cta_title'] = trim(strip_tags($m[1]));
    } else {
        $data['cta_title'] = "Équipez votre club de " . strtolower($config['sport_name']);
    }

    $data['cta_button_text'] = 'Demander un devis';
    $data['cta_button_link'] = '/pages/info/devis.html';

    $data['cta_features'] = [
        'Devis gratuit sous 24h',
        'Design professionnel inclus',
        'Prix dégressifs garantis',
        'Livraison France métropolitaine'
    ];

    // Why us section
    $data['why_title'] = 'Pourquoi choisir Flare Custom';
    $data['why_items'] = [];

    if (preg_match_all('/<div class=["\']why-card["\']>.*?<div class=["\']why-card-icon["\']>([^<]+)<\/div>.*?<h3>([^<]+)<\/h3>.*?<p>([^<]+)<\/p>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['why_items'][] = [
                'icon' => trim($m[1]),
                'title' => trim($m[2]),
                'description' => trim($m[3])
            ];
        }
    }

    // Default why items
    if (empty($data['why_items'])) {
        $data['why_items'] = [
            ['icon' => '🎨', 'title' => 'Design sur-mesure', 'description' => 'Créez votre design unique avec notre équipe de graphistes professionnels.'],
            ['icon' => '🏭', 'title' => 'Fabrication française', 'description' => 'Production 100% européenne, sublimation de qualité supérieure.'],
            ['icon' => '💰', 'title' => 'Prix compétitifs', 'description' => 'Tarifs dégressifs selon quantités, devis gratuit sous 24h.'],
            ['icon' => '🚚', 'title' => 'Livraison rapide', 'description' => 'Expédition sous 2-3 semaines, suivi en temps réel.']
        ];
    }

    // FAQ section
    $data['faq_title'] = 'FAQ ' . $config['sport_name'];
    $data['faq_items'] = [];

    if (preg_match_all('/<button class=["\']faq-question["\'][^>]*>.*?<span>([^<]+)<\/span>.*?<\/button>.*?<div class=["\']faq-answer["\']>.*?<p>([^<]+)<\/p>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['faq_items'][] = [
                'question' => trim(html_entity_decode($m[1])),
                'answer' => trim(html_entity_decode($m[2]))
            ];
        }
    }

    // Default FAQ if not found
    if (empty($data['faq_items'])) {
        $sportLower = strtolower($config['sport_name']);
        $data['faq_items'] = [
            ['question' => "Quels sont les délais de livraison pour les équipements $sportLower ?", 'answer' => "Comptez environ 2 à 3 semaines après validation de votre BAT (bon à tirer). Pour les commandes urgentes, contactez-nous pour étudier les possibilités."],
            ['question' => "Peut-on personnaliser les couleurs et le design ?", 'answer' => "Absolument ! La sublimation permet une personnalisation totale : couleurs illimitées, logos, noms, numéros. Notre équipe graphique vous accompagne dans la création."],
            ['question' => "Y a-t-il une quantité minimum de commande ?", 'answer' => "Oui, la quantité minimum est généralement de 10 pièces par modèle. Pour les petites quantités, contactez-nous pour une solution adaptée."],
            ['question' => "Comment fonctionne le processus de commande ?", 'answer' => "1) Demandez un devis gratuit, 2) Validez le BAT avec votre design, 3) Confirmez la commande, 4) Recevez vos équipements sous 2-3 semaines."]
        ];
    }

    // SEO sections
    $data['seo_sections'] = [];

    if (preg_match_all('/<section class=["\']seo-footer-section["\']>.*?<h2 class=["\']section-title["\']>([^<]+)<\/h2>.*?<div class=["\']seo-text["\']>(.*?)<\/div>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $data['seo_sections'][] = [
                'title' => trim(html_entity_decode($m[1])),
                'content' => trim($m[2])
            ];
        }
    }

    return $data;
}
