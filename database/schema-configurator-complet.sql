-- ============================================
-- EXTENSION SCHÉMA POUR CONFIGURATEUR COMPLET
-- Tables pour gérer TOUS les paramètres du configurateur par produit
-- ============================================

-- ============================================
-- TABLE: product_configurator_settings
-- Configuration complète du configurateur par produit
-- ============================================
CREATE TABLE IF NOT EXISTS product_configurator_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- =========== ÉTAPE 1: Type de Design ===========
    allow_design_flare BOOLEAN DEFAULT TRUE,
    allow_design_client BOOLEAN DEFAULT TRUE,
    allow_design_template BOOLEAN DEFAULT TRUE,
    default_design_type ENUM('flare', 'client', 'template') DEFAULT 'flare',
    design_description_required BOOLEAN DEFAULT FALSE,

    -- =========== ÉTAPE 2: Options Produit ===========
    -- Options disponibles (JSON array)
    available_col_options JSON,           -- Ex: ["Rond", "V", "Polo", "Capuche"]
    available_manches_options JSON,       -- Ex: ["Courtes", "Longues", "Sans manches"]
    available_poches_options JSON,        -- Ex: ["Oui", "Non"]
    available_fermeture_options JSON,     -- Ex: ["Élastique", "Zip", "Boutons"]

    -- Options par défaut
    default_col VARCHAR(50),
    default_manches VARCHAR(50),
    default_poches VARCHAR(50),
    default_fermeture VARCHAR(50),

    -- Options obligatoires
    col_required BOOLEAN DEFAULT TRUE,
    manches_required BOOLEAN DEFAULT TRUE,
    poches_required BOOLEAN DEFAULT FALSE,
    fermeture_required BOOLEAN DEFAULT FALSE,

    -- =========== ÉTAPE 3: Genre ===========
    allow_genre_homme BOOLEAN DEFAULT TRUE,
    allow_genre_femme BOOLEAN DEFAULT TRUE,
    allow_genre_mixte BOOLEAN DEFAULT TRUE,
    allow_genre_enfant BOOLEAN DEFAULT TRUE,
    default_genre ENUM('Homme', 'Femme', 'Mixte', 'Enfant') DEFAULT 'Mixte',
    enfant_discount_percent DECIMAL(5,2) DEFAULT 10.00,  -- Remise enfant (%)

    -- =========== ÉTAPE 4: Tailles et Quantités ===========
    available_sizes JSON,                 -- Ex: ["XS", "S", "M", "L", "XL", "XXL", "3XL", "4XL"]
    min_quantity_per_size INT DEFAULT 0,  -- Quantité minimum par taille
    max_quantity_per_size INT DEFAULT 999, -- Quantité maximum par taille
    min_total_quantity INT DEFAULT 1,     -- Quantité totale minimum
    max_total_quantity INT DEFAULT 9999,  -- Quantité totale maximum

    -- Presets de quantités (JSON)
    quantity_presets JSON,                -- Ex: [{"name": "Équipe 15", "sizes": {"S": 2, "M": 5, ...}}, ...]

    -- =========== ÉTAPE 5: Personnalisation ===========

    -- Couleurs
    allow_colors BOOLEAN DEFAULT TRUE,
    default_colors JSON,                  -- Ex: ["#FF4B26", "#1a1a1a", "#ffffff"]
    min_colors INT DEFAULT 1,
    max_colors INT DEFAULT 5,

    -- Logos
    allow_logos BOOLEAN DEFAULT TRUE,
    logo_description_required BOOLEAN DEFAULT TRUE,
    logo_upload_after_validation BOOLEAN DEFAULT TRUE,
    logo_extra_cost DECIMAL(10,2) DEFAULT 0.00,  -- Coût supplémentaire par logo

    -- Numéros
    allow_numeros BOOLEAN DEFAULT TRUE,
    allow_numeros_generique BOOLEAN DEFAULT TRUE,   -- 1-20 gratuit
    allow_numeros_specifique BOOLEAN DEFAULT TRUE,  -- Personnalisés payants
    numeros_specifique_cost DECIMAL(10,2) DEFAULT 2.00,  -- Coût par pièce
    numeros_description_required BOOLEAN DEFAULT TRUE,

    -- Noms
    allow_noms BOOLEAN DEFAULT TRUE,
    allow_noms_generique BOOLEAN DEFAULT TRUE,      -- JOUEUR 1-20 gratuit
    allow_noms_specifique BOOLEAN DEFAULT TRUE,     -- Personnalisés payants
    noms_specifique_cost DECIMAL(10,2) DEFAULT 2.00,     -- Coût par pièce
    noms_description_required BOOLEAN DEFAULT TRUE,

    -- Remarques générales
    allow_remarques BOOLEAN DEFAULT TRUE,
    remarques_required BOOLEAN DEFAULT FALSE,

    -- =========== PRIX & CALCULS ===========

    -- Design
    design_flare_extra_cost DECIMAL(10,2) DEFAULT 50.00,  -- Forfait design FLARE

    -- Frais supplémentaires
    sublimation_extra_cost DECIMAL(10,2) DEFAULT 0.00,
    broderie_extra_cost DECIMAL(10,2) DEFAULT 0.00,

    -- Délais
    default_lead_time_days INT DEFAULT 21,  -- 3 semaines par défaut
    express_available BOOLEAN DEFAULT FALSE,
    express_lead_time_days INT DEFAULT 10,
    express_extra_cost_percent DECIMAL(5,2) DEFAULT 30.00,  -- +30% pour express

    -- =========== VALIDATIONS & RÈGLES ===========

    -- Validation email
    email_validation_regex VARCHAR(500) DEFAULT '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',

    -- Champs obligatoires contact
    telephone_required BOOLEAN DEFAULT TRUE,
    club_required BOOLEAN DEFAULT FALSE,
    fonction_required BOOLEAN DEFAULT FALSE,

    -- Newsletter
    newsletter_checkbox BOOLEAN DEFAULT TRUE,
    newsletter_default_checked BOOLEAN DEFAULT FALSE,

    -- =========== EMAILS ===========

    -- Email client
    send_client_email BOOLEAN DEFAULT TRUE,
    client_email_template TEXT,           -- Template HTML personnalisé (optionnel)

    -- Email admin
    send_admin_email BOOLEAN DEFAULT TRUE,
    admin_email_recipients VARCHAR(500) DEFAULT 'contact@flare-custom.com',  -- Séparés par virgule
    admin_email_template TEXT,            -- Template HTML personnalisé (optionnel)

    -- =========== AFFICHAGE & UX ===========

    -- Sidebar récapitulatif
    show_sidebar_summary BOOLEAN DEFAULT TRUE,
    sidebar_show_price BOOLEAN DEFAULT TRUE,
    sidebar_show_quantity BOOLEAN DEFAULT TRUE,
    sidebar_show_sizes BOOLEAN DEFAULT TRUE,

    -- Messages personnalisés
    custom_welcome_message TEXT,
    custom_final_message TEXT,
    custom_price_disclaimer TEXT DEFAULT 'Ce prix est une estimation. Un devis détaillé et personnalisé vous sera envoyé par email sous 24h.',

    -- =========== ANALYTICS ===========

    enable_analytics BOOLEAN DEFAULT TRUE,
    gtag_tracking_id VARCHAR(50),

    -- =========== STATUT ===========

    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_config (product_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_photos
-- Galerie de photos dynamique (pas de limite à 5)
-- ============================================
CREATE TABLE IF NOT EXISTS product_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Photo
    url VARCHAR(500) NOT NULL,
    filename VARCHAR(255),
    alt_text VARCHAR(255),
    title VARCHAR(255),

    -- Type
    type ENUM('main', 'gallery', 'thumbnail', 'hover', 'zoom') DEFAULT 'gallery',

    -- Ordre d'affichage
    ordre INT DEFAULT 0,

    -- Métadonnées
    width INT,
    height INT,
    size_bytes INT,
    mime_type VARCHAR(100),

    -- Statut
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_type (type),
    INDEX idx_ordre (ordre),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: product_size_guides
-- Association produits ↔ guides des tailles (plusieurs guides par produit)
-- ============================================
CREATE TABLE IF NOT EXISTS product_size_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_guide_id INT NOT NULL,

    -- Ordre d'affichage si plusieurs guides
    ordre INT DEFAULT 0,

    -- Affichage
    display_type ENUM('tab', 'modal', 'inline', 'link') DEFAULT 'tab',
    visible BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_guide_id) REFERENCES size_guides(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_guide (product_id, size_guide_id),
    INDEX idx_product (product_id),
    INDEX idx_guide (size_guide_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: template_categories
-- Catégories pour organiser les templates
-- ============================================
CREATE TABLE IF NOT EXISTS template_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    ordre INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES template_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AJOUT DE COLONNES À LA TABLE templates
-- Pour mieux gérer les templates
-- ============================================
ALTER TABLE templates
    ADD COLUMN IF NOT EXISTS category_id INT NULL AFTER tags,
    ADD COLUMN IF NOT EXISTS sport VARCHAR(100) AFTER category_id,
    ADD COLUMN IF NOT EXISTS famille VARCHAR(100) AFTER sport,
    ADD COLUMN IF NOT EXISTS svg_content LONGTEXT AFTER preview_url,
    ADD COLUMN IF NOT EXISTS width INT AFTER svg_content,
    ADD COLUMN IF NOT EXISTS height INT AFTER width,
    ADD COLUMN IF NOT EXISTS colors_count INT DEFAULT 1 AFTER height,
    ADD COLUMN IF NOT EXISTS editable BOOLEAN DEFAULT TRUE AFTER colors_count,
    ADD FOREIGN KEY IF NOT EXISTS (category_id) REFERENCES template_categories(id) ON DELETE SET NULL;

-- ============================================
-- Données initiales pour les catégories de templates
-- ============================================
INSERT INTO template_categories (nom, slug, description, ordre) VALUES
('Football', 'football', 'Templates pour le football', 1),
('Basketball', 'basketball', 'Templates pour le basketball', 2),
('Rugby', 'rugby', 'Templates pour le rugby', 3),
('Running', 'running', 'Templates pour le running', 4),
('Cyclisme', 'cyclisme', 'Templates pour le cyclisme', 5),
('Abstrait', 'abstrait', 'Designs abstraits et géométriques', 6),
('Minimaliste', 'minimaliste', 'Designs minimalistes', 7),
('Tribal', 'tribal', 'Motifs tribaux et ethniques', 8)
ON DUPLICATE KEY UPDATE nom=nom;  -- Ne fait rien si ça existe déjà

-- ============================================
-- Configuration par défaut pour les produits existants
-- ============================================
-- Crée une config par défaut pour chaque produit sans config
INSERT INTO product_configurator_settings (
    product_id,
    available_col_options,
    available_manches_options,
    available_poches_options,
    available_fermeture_options,
    available_sizes,
    default_colors,
    quantity_presets
)
SELECT
    id,
    '["Rond", "V", "Polo"]',
    '["Courtes", "Longues", "Sans manches"]',
    '["Oui", "Non"]',
    '["Élastique", "Zip", "Boutons"]',
    '["XS", "S", "M", "L", "XL", "XXL", "3XL", "4XL"]',
    '["#FF4B26", "#1a1a1a", "#ffffff"]',
    '[
        {"name": "Équipe 15 joueurs", "sizes": {"S": 2, "M": 5, "L": 5, "XL": 3}},
        {"name": "Club 25 personnes", "sizes": {"XS": 2, "S": 4, "M": 8, "L": 7, "XL": 3, "XXL": 1}},
        {"name": "Événement 50 personnes", "sizes": {"S": 8, "M": 15, "L": 15, "XL": 8, "XXL": 4}}
    ]'
FROM products
WHERE id NOT IN (SELECT product_id FROM product_configurator_settings);

-- ============================================
-- Migration des photos existantes vers product_photos
-- ============================================
-- Insère les 5 photos existantes (photo_1 à photo_5) dans la nouvelle table
INSERT INTO product_photos (product_id, url, type, ordre, active)
SELECT id, photo_1, 'main', 1, TRUE FROM products WHERE photo_1 IS NOT NULL AND photo_1 != ''
UNION ALL
SELECT id, photo_2, 'gallery', 2, TRUE FROM products WHERE photo_2 IS NOT NULL AND photo_2 != ''
UNION ALL
SELECT id, photo_3, 'gallery', 3, TRUE FROM products WHERE photo_3 IS NOT NULL AND photo_3 != ''
UNION ALL
SELECT id, photo_4, 'gallery', 4, TRUE FROM products WHERE photo_4 IS NOT NULL AND photo_4 != ''
UNION ALL
SELECT id, photo_5, 'gallery', 5, TRUE FROM products WHERE photo_5 IS NOT NULL AND photo_5 != '';

-- ============================================
-- VUES UTILES
-- ============================================

-- Vue pour obtenir toutes les infos d'un produit + config en une seule requête
CREATE OR REPLACE VIEW product_full_config AS
SELECT
    p.*,
    c.allow_design_flare,
    c.allow_design_client,
    c.allow_design_template,
    c.default_design_type,
    c.available_col_options,
    c.available_manches_options,
    c.available_poches_options,
    c.available_fermeture_options,
    c.available_sizes,
    c.allow_genre_homme,
    c.allow_genre_femme,
    c.allow_genre_mixte,
    c.allow_genre_enfant,
    c.default_genre,
    c.enfant_discount_percent,
    c.min_total_quantity,
    c.max_total_quantity,
    c.allow_colors,
    c.default_colors,
    c.allow_logos,
    c.logo_extra_cost,
    c.allow_numeros,
    c.numeros_specifique_cost,
    c.allow_noms,
    c.noms_specifique_cost,
    c.design_flare_extra_cost,
    c.default_lead_time_days
FROM products p
LEFT JOIN product_configurator_settings c ON p.id = c.product_id;

-- Vue pour compter les photos par produit
CREATE OR REPLACE VIEW product_photos_count AS
SELECT
    product_id,
    COUNT(*) as total_photos,
    SUM(CASE WHEN type = 'main' THEN 1 ELSE 0 END) as main_photos,
    SUM(CASE WHEN type = 'gallery' THEN 1 ELSE 0 END) as gallery_photos
FROM product_photos
WHERE active = TRUE
GROUP BY product_id;

-- Vue pour les guides associés aux produits
CREATE OR REPLACE VIEW product_guides_summary AS
SELECT
    p.id as product_id,
    p.reference,
    p.nom,
    sg.id as guide_id,
    sg.titre as guide_titre,
    sg.categorie as guide_categorie,
    sg.sport as guide_sport,
    sg.genre as guide_genre,
    psg.ordre,
    psg.display_type
FROM products p
INNER JOIN product_size_guides psg ON p.id = psg.product_id
INNER JOIN size_guides sg ON psg.size_guide_id = sg.id
WHERE psg.visible = TRUE
ORDER BY p.id, psg.ordre;
