-- ============================================
-- FLARE CUSTOM - Database Schema
-- Système de gestion complet pour produits, pages, contenus
-- ============================================
-- NOTE: La base de données doit être créée et sélectionnée avant d'exécuter ce script
-- ============================================

-- ============================================
-- TABLE: users (Utilisateurs admin)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: products (Produits)
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(255) NOT NULL,
    sport VARCHAR(100),
    famille VARCHAR(100),
    description TEXT,
    description_seo TEXT,
    tissu VARCHAR(100),
    grammage VARCHAR(50),

    -- Prix
    prix_1 DECIMAL(10,2),
    prix_5 DECIMAL(10,2),
    prix_10 DECIMAL(10,2),
    prix_20 DECIMAL(10,2),
    prix_50 DECIMAL(10,2),
    prix_100 DECIMAL(10,2),
    prix_250 DECIMAL(10,2),
    prix_500 DECIMAL(10,2),

    -- Médias
    photo_1 VARCHAR(500),
    photo_2 VARCHAR(500),
    photo_3 VARCHAR(500),
    photo_4 VARCHAR(500),
    photo_5 VARCHAR(500),

    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    slug VARCHAR(255) UNIQUE,
    url VARCHAR(500),

    -- Caractéristiques
    genre ENUM('Homme', 'Femme', 'Mixte', 'Enfant'),
    finition TEXT,
    etiquettes VARCHAR(255),

    -- Statut
    active BOOLEAN DEFAULT TRUE,
    stock_status ENUM('in_stock', 'out_of_stock', 'preorder') DEFAULT 'in_stock',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reference (reference),
    INDEX idx_sport (sport),
    INDEX idx_famille (famille),
    INDEX idx_slug (slug),
    INDEX idx_active (active),
    FULLTEXT idx_search (nom, description, description_seo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: categories (Catégories)
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('sport', 'famille') NOT NULL,
    description TEXT,
    image VARCHAR(500),
    parent_id INT NULL,
    ordre INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_type (type),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: templates (Templates de design)
-- ============================================
CREATE TABLE IF NOT EXISTS templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) UNIQUE NOT NULL,
    nom VARCHAR(100),
    description TEXT,
    path VARCHAR(500) NOT NULL,
    preview_url VARCHAR(500),
    type ENUM('svg', 'png', 'jpg') DEFAULT 'svg',
    tags VARCHAR(255),
    ordre INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_filename (filename),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: pages (Pages dynamiques)
-- ============================================
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    type ENUM('page', 'category', 'product') DEFAULT 'page',
    template VARCHAR(100) DEFAULT 'default',

    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),

    -- Statut
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,

    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_type (type),
    INDEX idx_status (status),
    FULLTEXT idx_content (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: settings (Paramètres du site)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'text', 'number', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: media (Bibliothèque médias)
-- ============================================
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    path VARCHAR(500) NOT NULL,
    url VARCHAR(500) NOT NULL,
    type ENUM('image', 'video', 'document', 'other') DEFAULT 'image',
    mime_type VARCHAR(100),
    size INT,
    width INT,
    height INT,
    alt_text VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: quotes (Devis)
-- ============================================
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) UNIQUE NOT NULL,

    -- Client
    client_prenom VARCHAR(100),
    client_nom VARCHAR(100),
    client_email VARCHAR(150),
    client_telephone VARCHAR(20),
    client_club VARCHAR(150),
    client_fonction VARCHAR(100),

    -- Produit
    product_reference VARCHAR(50),
    product_nom VARCHAR(255),
    sport VARCHAR(100),
    famille VARCHAR(100),

    -- Configuration
    design_type ENUM('flare', 'client', 'template'),
    design_template_id INT,
    design_description TEXT,

    options JSON,
    genre VARCHAR(50),
    tailles JSON,
    personnalisation JSON,

    -- Prix
    total_pieces INT,
    prix_unitaire DECIMAL(10,2),
    prix_total DECIMAL(10,2),

    -- Statut
    status ENUM('pending', 'sent', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reference (reference),
    INDEX idx_email (client_email),
    INDEX idx_status (status),
    INDEX idx_product (product_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données initiales
-- ============================================

-- Utilisateur admin par défaut (mot de passe: admin123 - À CHANGER!)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@flare-custom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Paramètres du site
INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES
('site_name', 'FLARE CUSTOM', 'string', 'general', 'Nom du site'),
('site_url', 'https://flare-custom.com', 'string', 'general', 'URL du site'),
('contact_email', 'contact@flare-custom.com', 'string', 'general', 'Email de contact'),
('contact_phone', '+33 1 23 45 67 89', 'string', 'general', 'Téléphone de contact'),
('default_currency', 'EUR', 'string', 'pricing', 'Devise par défaut'),
('tax_rate', '20', 'number', 'pricing', 'Taux de TVA (%)'),
('products_per_page', '20', 'number', 'catalog', 'Produits par page');

-- Catégories Sports
INSERT INTO categories (nom, slug, type, description, ordre) VALUES
('Football', 'football', 'sport', 'Équipements de football personnalisés', 1),
('Basketball', 'basketball', 'sport', 'Équipements de basketball personnalisés', 2),
('Rugby', 'rugby', 'sport', 'Équipements de rugby personnalisés', 3),
('Handball', 'handball', 'sport', 'Équipements de handball personnalisés', 4),
('Volleyball', 'volleyball', 'sport', 'Équipements de volleyball personnalisés', 5),
('Running', 'running', 'sport', 'Équipements de running personnalisés', 6),
('Cyclisme', 'cyclisme', 'sport', 'Équipements de cyclisme personnalisés', 7),
('Sportswear', 'sportswear', 'sport', 'Vêtements de sport personnalisés', 8);

-- Catégories Familles
INSERT INTO categories (nom, slug, type, description, ordre) VALUES
('Maillot', 'maillot', 'famille', 'Maillots personnalisables', 1),
('Short', 'short', 'famille', 'Shorts personnalisables', 2),
('Polo', 'polo', 'famille', 'Polos personnalisables', 3),
('Veste', 'veste', 'famille', 'Vestes personnalisables', 4),
('Pantalon', 'pantalon', 'famille', 'Pantalons personnalisables', 5),
('Débardeur', 'debardeur', 'famille', 'Débardeurs personnalisables', 6);
