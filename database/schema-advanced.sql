-- ============================================
-- FLARE CUSTOM - Extensions avancées
-- Configurateur de devis + Page Builder
-- ============================================

-- ============================================
-- TABLE: product_configurations
-- Configuration du configurateur par produit
-- ============================================
CREATE TABLE IF NOT EXISTS product_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Options de personnalisation
    allow_colors BOOLEAN DEFAULT TRUE,
    colors JSON,  -- ["#FF0000", "#00FF00", "#0000FF"]

    allow_logos BOOLEAN DEFAULT TRUE,
    max_logos INT DEFAULT 3,
    logo_positions JSON,  -- [{"name": "Poitrine", "x": 50, "y": 30}, ...]

    allow_text BOOLEAN DEFAULT TRUE,
    text_positions JSON,  -- [{"name": "Dos", "maxChars": 20}, ...]

    allow_numbers BOOLEAN DEFAULT TRUE,
    number_positions JSON,

    -- Tailles disponibles
    available_sizes JSON,  -- ["XS", "S", "M", "L", "XL", "XXL", "3XL"]
    size_chart JSON,  -- {measurements: {...}, guide: "..."}

    -- Options spécifiques
    custom_options JSON,  -- [{name: "Col", values: ["V", "Rond"]}, ...]

    -- Templates de design
    design_templates JSON,  -- IDs des templates disponibles
    default_template_id INT,

    -- Zones de personnalisation (pour le configurateur visuel)
    customization_zones JSON,  -- [{zone: "front", sublimation: true}, ...]

    -- Règles de prix
    price_rules JSON,  -- {logoExtra: 5, textExtra: 2, ...}

    -- Contraintes
    min_quantity INT DEFAULT 1,
    max_quantity INT DEFAULT 1000,
    lead_time_days INT DEFAULT 21,

    -- Configuration du formulaire de devis
    form_fields JSON,  -- Champs personnalisés pour ce produit

    -- Métadonnées
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: page_blocks
-- Blocs de contenu pour le page builder
-- ============================================
CREATE TABLE IF NOT EXISTS page_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,

    -- Type de bloc
    block_type VARCHAR(50) NOT NULL,  -- hero, text, image, gallery, form, products, categories, testimonials, faq, etc.

    -- Contenu du bloc (JSON flexible)
    content JSON NOT NULL,

    -- Style et apparence
    styles JSON,  -- {backgroundColor, padding, margin, ...}
    custom_css TEXT,

    -- Position et affichage
    position INT DEFAULT 0,
    visible BOOLEAN DEFAULT TRUE,

    -- Responsive
    mobile_content JSON,  -- Contenu spécifique mobile
    mobile_styles JSON,

    -- Conditions d'affichage
    display_conditions JSON,  -- {showIf: "user_logged_in", ...}

    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page (page_id),
    INDEX idx_position (position),
    INDEX idx_type (block_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: page_templates
-- Templates de pages réutilisables
-- ============================================
CREATE TABLE IF NOT EXISTS page_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Type de template
    template_type ENUM('landing', 'product', 'category', 'blog', 'custom') DEFAULT 'custom',

    -- Structure complète du template
    blocks JSON NOT NULL,  -- Array de blocs avec leur config

    -- Métadonnées
    thumbnail VARCHAR(500),
    category VARCHAR(100),
    tags VARCHAR(255),

    -- Stats d'utilisation
    usage_count INT DEFAULT 0,

    -- Statut
    active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (template_type),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: design_assets
-- Assets pour le configurateur (logos, patterns, etc.)
-- ============================================
CREATE TABLE IF NOT EXISTS design_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    asset_type ENUM('logo', 'pattern', 'icon', 'clipart', 'font') NOT NULL,

    -- Fichier
    file_path VARCHAR(500) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),

    -- Pour les logos/cliparts
    svg_content TEXT,  -- Si SVG, stocker le contenu

    -- Pour les patterns
    pattern_data JSON,  -- Configuration du pattern

    -- Catégorisation
    category VARCHAR(100),
    tags VARCHAR(255),

    -- Permissions
    is_public BOOLEAN DEFAULT TRUE,
    uploaded_by INT,

    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (asset_type),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: quote_designs
-- Designs sauvegardés pour les devis
-- ============================================
CREATE TABLE IF NOT EXISTS quote_designs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,

    -- Configuration complète du design
    design_data JSON NOT NULL,  -- Tout le state du configurateur

    -- Rendu
    preview_front VARCHAR(500),
    preview_back VARCHAR(500),
    preview_sides JSON,

    -- Fichiers de production
    production_files JSON,  -- URLs des fichiers pour la prod

    -- Version
    version INT DEFAULT 1,
    is_final BOOLEAN DEFAULT FALSE,

    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    INDEX idx_quote (quote_id),
    INDEX idx_final (is_final)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: form_builders
-- Formulaires personnalisés
-- ============================================
CREATE TABLE IF NOT EXISTS form_builders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    -- Type de formulaire
    form_type ENUM('quote', 'contact', 'custom') DEFAULT 'custom',

    -- Champs du formulaire
    fields JSON NOT NULL,

    -- Configuration
    settings JSON,  -- {submitText, successMessage, redirectUrl, ...}

    -- Validation
    validation_rules JSON,

    -- Actions après soumission
    actions JSON,  -- [{type: "email", to: "admin@example.com"}, ...]

    -- Stats
    submission_count INT DEFAULT 0,

    -- Statut
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (form_type),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: form_submissions
-- Soumissions de formulaires
-- ============================================
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,

    -- Données soumises
    data JSON NOT NULL,

    -- Informations utilisateur
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),

    -- Statut
    status ENUM('pending', 'processed', 'spam') DEFAULT 'pending',

    -- Métadonnées
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,

    FOREIGN KEY (form_id) REFERENCES form_builders(id) ON DELETE CASCADE,
    INDEX idx_form (form_id),
    INDEX idx_status (status),
    INDEX idx_date (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données initiales pour le configurateur
-- ============================================

-- Template de page produit par défaut
INSERT INTO page_templates (name, description, template_type, blocks, is_default) VALUES
('Template Produit Standard', 'Template par défaut pour les pages produits', 'product',
'[
  {
    "type": "hero",
    "content": {
      "title": "{{product.name}}",
      "subtitle": "{{product.description_seo}}",
      "backgroundImage": "{{product.photo_1}}"
    }
  },
  {
    "type": "product_gallery",
    "content": {
      "images": ["{{product.photo_1}}", "{{product.photo_2}}", "{{product.photo_3}}"]
    }
  },
  {
    "type": "product_info",
    "content": {
      "description": "{{product.description}}",
      "features": ["{{product.tissu}}", "{{product.grammage}}"]
    }
  },
  {
    "type": "pricing_table",
    "content": {
      "prices": "{{product.prices}}"
    }
  },
  {
    "type": "configurator",
    "content": {
      "productId": "{{product.id}}"
    }
  }
]',
TRUE);

-- Configuration par défaut pour les produits
INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES
('configurator_default_colors', '["#FFFFFF", "#000000", "#FF0000", "#0000FF", "#FFFF00", "#00FF00"]', 'json', 'configurator', 'Couleurs par défaut du configurateur'),
('configurator_logo_max_size', '5', 'number', 'configurator', 'Taille maximale des logos (MB)'),
('configurator_text_max_chars', '20', 'number', 'configurator', 'Nombre maximum de caractères pour les textes'),
('configurator_enable_preview_3d', 'true', 'boolean', 'configurator', 'Activer l''aperçu 3D'),
('page_builder_enable', 'true', 'boolean', 'page_builder', 'Activer le page builder'),
('page_builder_allowed_blocks', '["hero", "text", "image", "gallery", "products", "categories", "form", "testimonials", "faq", "cta"]', 'json', 'page_builder', 'Blocs autorisés dans le page builder');
