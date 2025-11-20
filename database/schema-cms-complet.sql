-- ============================================
-- EXTENSION SCHÉMA POUR CMS COMPLET
-- Tables pour gérer TOUT le contenu des pages produits
-- ============================================

-- Table pour le contenu complet des pages produits
CREATE TABLE IF NOT EXISTS product_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Textes principaux
    titre_principal VARCHAR(500),
    sous_titre VARCHAR(500),
    description_courte TEXT,
    description_longue LONGTEXT,

    -- Sections de contenu
    caracteristiques LONGTEXT,  -- JSON des caractéristiques
    avantages LONGTEXT,          -- JSON des avantages/bénéfices
    composition TEXT,
    entretien TEXT,

    -- Guide des tailles
    guide_tailles LONGTEXT,      -- JSON du tableau des tailles
    conseils_taille TEXT,

    -- SEO et metadata
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    meta_keywords VARCHAR(500),
    url_slug VARCHAR(255) UNIQUE,

    -- Médias
    video_url VARCHAR(500),
    galerie_images LONGTEXT,     -- JSON des images supplémentaires

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_slug (url_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les produits recommandés/à découvrir
CREATE TABLE IF NOT EXISTS product_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    related_product_id INT NOT NULL,
    relation_type ENUM('similar', 'recommended', 'frequently_bought', 'alternative') DEFAULT 'similar',
    ordre INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_type (relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les avis clients
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Info client
    client_nom VARCHAR(255) NOT NULL,
    client_email VARCHAR(255),

    -- Avis
    note INT NOT NULL CHECK (note >= 1 AND note <= 5),
    titre VARCHAR(255),
    commentaire TEXT,

    -- Validation
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reponse_admin TEXT,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_note (note)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les FAQ produits
CREATE TABLE IF NOT EXISTS product_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL,  -- NULL = FAQ globale

    question VARCHAR(500) NOT NULL,
    reponse TEXT NOT NULL,

    ordre INT DEFAULT 0,
    visible BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_visible (visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les blocs de contenu personnalisés
CREATE TABLE IF NOT EXISTS content_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identification
    block_key VARCHAR(100) UNIQUE NOT NULL,  -- Ex: 'home_hero', 'about_us', etc.
    block_type ENUM('text', 'html', 'image', 'video', 'slider', 'grid', 'faq', 'testimonial') DEFAULT 'text',

    -- Contenu
    titre VARCHAR(500),
    contenu LONGTEXT,  -- Peut être HTML, JSON, ou texte selon le type

    -- Médias
    image_url VARCHAR(500),
    video_url VARCHAR(500),

    -- Positionnement
    position VARCHAR(100),  -- 'header', 'footer', 'sidebar', etc.
    ordre INT DEFAULT 0,

    -- Statut
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (block_key),
    INDEX idx_position (position),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les guides de tailles globaux
CREATE TABLE IF NOT EXISTS size_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Catégorisation
    categorie VARCHAR(100) NOT NULL,  -- 'maillot', 'short', 'veste', etc.
    sport VARCHAR(100),
    genre ENUM('homme', 'femme', 'mixte', 'enfant') DEFAULT 'mixte',

    -- Contenu
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    tableau LONGTEXT NOT NULL,  -- JSON du tableau des tailles
    conseils TEXT,

    -- Image
    image_url VARCHAR(500),

    -- Statut
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_categorie (categorie),
    INDEX idx_sport (sport),
    INDEX idx_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les collections/lookbooks
CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,

    -- Médias
    image_principale VARCHAR(500),
    images_galerie LONGTEXT,  -- JSON

    -- SEO
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),

    -- Dates
    date_debut DATE,
    date_fin DATE,

    -- Statut
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison collections <-> produits
CREATE TABLE IF NOT EXISTS collection_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    product_id INT NOT NULL,
    ordre INT DEFAULT 0,

    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_collection (collection_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les promotions
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,

    -- Type de remise
    type_remise ENUM('percentage', 'fixed_amount', 'free_shipping') NOT NULL,
    valeur DECIMAL(10,2) NOT NULL,

    -- Conditions
    montant_minimum DECIMAL(10,2) DEFAULT 0,
    quantite_minimum INT DEFAULT 1,

    -- Applicabilité
    applicable_a ENUM('all', 'category', 'product', 'collection') DEFAULT 'all',
    applicable_ids LONGTEXT,  -- JSON des IDs concernés

    -- Limites
    limite_utilisation INT NULL,  -- NULL = illimité
    limite_par_client INT DEFAULT 1,

    -- Dates
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,

    -- Statut
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les bannières/slides
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,

    titre VARCHAR(255),
    sous_titre VARCHAR(255),
    texte_bouton VARCHAR(100),
    lien_bouton VARCHAR(500),

    -- Médias
    image_desktop VARCHAR(500),
    image_mobile VARCHAR(500),
    video_url VARCHAR(500),

    -- Positionnement
    position ENUM('home_slider', 'category_header', 'product_sidebar', 'footer') DEFAULT 'home_slider',
    ordre INT DEFAULT 0,

    -- Dates de validité
    date_debut DATETIME,
    date_fin DATETIME,

    -- Statut
    active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_position (position),
    INDEX idx_active (active),
    INDEX idx_dates (date_debut, date_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
