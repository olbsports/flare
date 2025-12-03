-- ============================================
-- FLARE CUSTOM - Schema Blog
-- Table pour les articles de blog
-- ============================================

-- ============================================
-- TABLE: blog_posts (Articles de blog)
-- ============================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,

    -- Image mise en avant
    featured_image VARCHAR(500),
    featured_image_alt VARCHAR(255),

    -- Catégorisation
    category VARCHAR(100),
    tags JSON,

    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(500),

    -- Auteur
    author_id INT,
    author_name VARCHAR(100),

    -- Statut
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,

    -- Stats
    views_count INT DEFAULT 0,
    reading_time INT DEFAULT 5, -- minutes

    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_published (published_at),
    FULLTEXT idx_search (title, excerpt, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: blog_categories (Catégories blog)
-- ============================================
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(500),
    color VARCHAR(20) DEFAULT '#FF4B26',
    ordre INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données initiales - Catégories blog
-- ============================================
INSERT INTO blog_categories (name, slug, description, color, ordre) VALUES
('Conseils', 'conseils', 'Conseils et astuces pour vos équipements sportifs', '#FF4B26', 1),
('Actualités', 'actualites', 'Actualités FLARE CUSTOM et du monde sportif', '#0066CC', 2),
('Tutoriels', 'tutoriels', 'Guides et tutoriels pour personnaliser vos tenues', '#00AA44', 3),
('Témoignages', 'temoignages', 'Retours d''expérience de nos clients', '#9933CC', 4),
('Nouveautés', 'nouveautes', 'Dernières nouveautés produits et services', '#FF9900', 5);
