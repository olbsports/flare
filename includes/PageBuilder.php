<?php
/**
 * FLARE CUSTOM - PageBuilder Class
 * Page Builder type Elementor pour créer des pages facilement
 */

require_once __DIR__ . '/../config/database.php';

class PageBuilder {
    private $db;
    private $blocksTable = 'page_blocks';
    private $templatesTable = 'page_templates';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ============================================
    // GESTION DES BLOCS
    // ============================================

    /**
     * Récupère tous les blocs d'une page
     */
    public function getPageBlocks($pageId) {
        $sql = "SELECT * FROM {$this->blocksTable}
                WHERE page_id = :page_id AND visible = 1
                ORDER BY position ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':page_id', $pageId, PDO::PARAM_INT);
        $stmt->execute();
        $blocks = $stmt->fetchAll();

        foreach ($blocks as &$block) {
            $block = $this->decodeBlockJson($block);
        }

        return $blocks;
    }

    /**
     * Crée un nouveau bloc
     */
    public function createBlock($data) {
        $content = isset($data['content']) && is_array($data['content'])
            ? json_encode($data['content'])
            : $data['content'];

        $styles = isset($data['styles']) && is_array($data['styles'])
            ? json_encode($data['styles'])
            : null;

        $mobileContent = isset($data['mobile_content']) && is_array($data['mobile_content'])
            ? json_encode($data['mobile_content'])
            : null;

        $mobileStyles = isset($data['mobile_styles']) && is_array($data['mobile_styles'])
            ? json_encode($data['mobile_styles'])
            : null;

        $displayConditions = isset($data['display_conditions']) && is_array($data['display_conditions'])
            ? json_encode($data['display_conditions'])
            : null;

        $sql = "INSERT INTO {$this->blocksTable} (
            page_id, block_type, content, styles, custom_css,
            position, visible, mobile_content, mobile_styles, display_conditions
        ) VALUES (
            :page_id, :block_type, :content, :styles, :custom_css,
            :position, :visible, :mobile_content, :mobile_styles, :display_conditions
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':page_id', $data['page_id'], PDO::PARAM_INT);
        $stmt->bindValue(':block_type', $data['block_type']);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':styles', $styles);
        $stmt->bindValue(':custom_css', $data['custom_css'] ?? null);
        $stmt->bindValue(':position', $data['position'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':visible', $data['visible'] ?? 1, PDO::PARAM_BOOL);
        $stmt->bindValue(':mobile_content', $mobileContent);
        $stmt->bindValue(':mobile_styles', $mobileStyles);
        $stmt->bindValue(':display_conditions', $displayConditions);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un bloc
     */
    public function updateBlock($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['block_type', 'position', 'visible', 'custom_css'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        // Gérer les champs JSON
        $jsonFields = ['content', 'styles', 'mobile_content', 'mobile_styles', 'display_conditions'];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->blocksTable} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Supprime un bloc
     */
    public function deleteBlock($id) {
        $sql = "DELETE FROM {$this->blocksTable} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Réordonne les blocs d'une page
     */
    public function reorderBlocks($pageId, $blockIds) {
        $this->db->beginTransaction();

        try {
            $position = 0;
            foreach ($blockIds as $blockId) {
                $sql = "UPDATE {$this->blocksTable} SET position = :position WHERE id = :id AND page_id = :page_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':position' => $position,
                    ':id' => $blockId,
                    ':page_id' => $pageId
                ]);
                $position++;
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // ============================================
    // GESTION DES TEMPLATES
    // ============================================

    /**
     * Récupère tous les templates
     */
    public function getTemplates($filters = []) {
        $sql = "SELECT * FROM {$this->templatesTable} WHERE active = 1";
        $params = [];

        if (!empty($filters['template_type'])) {
            $sql .= " AND template_type = :template_type";
            $params[':template_type'] = $filters['template_type'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }

        $sql .= " ORDER BY is_default DESC, usage_count DESC, created_at DESC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $templates = $stmt->fetchAll();

        foreach ($templates as &$template) {
            if ($template['blocks']) {
                $template['blocks'] = json_decode($template['blocks'], true);
            }
        }

        return $templates;
    }

    /**
     * Récupère un template par ID
     */
    public function getTemplate($id) {
        $sql = "SELECT * FROM {$this->templatesTable} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $template = $stmt->fetch();

        if ($template && $template['blocks']) {
            $template['blocks'] = json_decode($template['blocks'], true);
        }

        return $template;
    }

    /**
     * Crée un nouveau template
     */
    public function createTemplate($data) {
        $blocks = isset($data['blocks']) && is_array($data['blocks'])
            ? json_encode($data['blocks'])
            : $data['blocks'];

        $sql = "INSERT INTO {$this->templatesTable} (
            name, description, template_type, blocks,
            thumbnail, category, tags, active, is_default
        ) VALUES (
            :name, :description, :template_type, :blocks,
            :thumbnail, :category, :tags, :active, :is_default
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':template_type', $data['template_type'] ?? 'custom');
        $stmt->bindValue(':blocks', $blocks);
        $stmt->bindValue(':thumbnail', $data['thumbnail'] ?? null);
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':tags', $data['tags'] ?? null);
        $stmt->bindValue(':active', $data['active'] ?? 1, PDO::PARAM_BOOL);
        $stmt->bindValue(':is_default', $data['is_default'] ?? 0, PDO::PARAM_BOOL);

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Applique un template à une page
     */
    public function applyTemplateToPage($templateId, $pageId) {
        $template = $this->getTemplate($templateId);

        if (!$template || !$template['blocks']) {
            return false;
        }

        // Supprimer les blocs existants
        $sql = "DELETE FROM {$this->blocksTable} WHERE page_id = :page_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':page_id' => $pageId]);

        // Créer les nouveaux blocs depuis le template
        foreach ($template['blocks'] as $index => $blockData) {
            $blockData['page_id'] = $pageId;
            $blockData['position'] = $index;
            $this->createBlock($blockData);
        }

        // Incrémenter le compteur d'utilisation
        $sql = "UPDATE {$this->templatesTable} SET usage_count = usage_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $templateId]);

        return true;
    }

    /**
     * Sauvegarde une page comme template
     */
    public function savePageAsTemplate($pageId, $templateName, $templateData = []) {
        $blocks = $this->getPageBlocks($pageId);

        // Nettoyer les blocs pour le template (enlever les IDs, etc.)
        $cleanBlocks = [];
        foreach ($blocks as $block) {
            $cleanBlocks[] = [
                'block_type' => $block['block_type'],
                'content' => $block['content'],
                'styles' => $block['styles'],
                'custom_css' => $block['custom_css'] ?? null,
                'mobile_content' => $block['mobile_content'] ?? null,
                'mobile_styles' => $block['mobile_styles'] ?? null
            ];
        }

        $templateData['name'] = $templateName;
        $templateData['blocks'] = $cleanBlocks;

        return $this->createTemplate($templateData);
    }

    // ============================================
    // RENDU DES BLOCS
    // ============================================

    /**
     * Génère le HTML d'un bloc
     */
    public function renderBlock($block) {
        $type = $block['block_type'];
        $content = $block['content'];
        $styles = $block['styles'] ?? [];

        $html = "<div class='page-block block-{$type}' data-block-id='{$block['id']}'";

        // Ajouter les styles inline
        if (!empty($styles)) {
            $html .= " style='";
            foreach ($styles as $prop => $value) {
                $html .= "{$prop}: {$value}; ";
            }
            $html .= "'";
        }

        $html .= ">";

        // Contenu selon le type
        switch ($type) {
            case 'hero':
                $html .= $this->renderHero($content);
                break;
            case 'text':
                $html .= $this->renderText($content);
                break;
            case 'image':
                $html .= $this->renderImage($content);
                break;
            case 'gallery':
                $html .= $this->renderGallery($content);
                break;
            case 'products':
                $html .= $this->renderProducts($content);
                break;
            case 'categories':
                $html .= $this->renderCategories($content);
                break;
            default:
                $html .= "<div class='block-content'>" . json_encode($content) . "</div>";
        }

        // Custom CSS
        if (!empty($block['custom_css'])) {
            $html .= "<style>" . $block['custom_css'] . "</style>";
        }

        $html .= "</div>";

        return $html;
    }

    /**
     * Génère le HTML d'une page complète
     */
    public function renderPage($pageId) {
        $blocks = $this->getPageBlocks($pageId);

        $html = "<div class='page-builder-content'>";

        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        $html .= "</div>";

        return $html;
    }

    // ============================================
    // RENDUS SPÉCIFIQUES PAR TYPE
    // ============================================

    private function renderHero($content) {
        $title = $content['title'] ?? '';
        $subtitle = $content['subtitle'] ?? '';
        $backgroundImage = $content['backgroundImage'] ?? '';
        $ctaText = $content['ctaText'] ?? '';
        $ctaLink = $content['ctaLink'] ?? '';

        $html = "<div class='hero-block'>";

        if ($backgroundImage) {
            $html .= "<div class='hero-background' style='background-image: url({$backgroundImage})'></div>";
        }

        $html .= "<div class='hero-content'>";

        if ($title) {
            $html .= "<h1 class='hero-title'>{$title}</h1>";
        }

        if ($subtitle) {
            $html .= "<p class='hero-subtitle'>{$subtitle}</p>";
        }

        if ($ctaText && $ctaLink) {
            $html .= "<a href='{$ctaLink}' class='hero-cta'>{$ctaText}</a>";
        }

        $html .= "</div></div>";

        return $html;
    }

    private function renderText($content) {
        return "<div class='text-block'>" . ($content['text'] ?? '') . "</div>";
    }

    private function renderImage($content) {
        $src = $content['src'] ?? '';
        $alt = $content['alt'] ?? '';
        $caption = $content['caption'] ?? '';

        $html = "<figure class='image-block'>";
        $html .= "<img src='{$src}' alt='{$alt}' />";

        if ($caption) {
            $html .= "<figcaption>{$caption}</figcaption>";
        }

        $html .= "</figure>";

        return $html;
    }

    private function renderGallery($content) {
        $images = $content['images'] ?? [];

        $html = "<div class='gallery-block'>";

        foreach ($images as $image) {
            $html .= "<div class='gallery-item'>";
            $html .= "<img src='{$image['src']}' alt='{$image['alt']}' />";
            $html .= "</div>";
        }

        $html .= "</div>";

        return $html;
    }

    private function renderProducts($content) {
        $productIds = $content['productIds'] ?? [];
        $limit = $content['limit'] ?? 12;

        // Récupérer les produits
        require_once __DIR__ . '/Product.php';
        $productModel = new Product();

        // TODO: Implémenter la récupération par IDs
        $html = "<div class='products-block'>";
        $html .= "<!-- Liste de produits -->";
        $html .= "</div>";

        return $html;
    }

    private function renderCategories($content) {
        $html = "<div class='categories-block'>";
        $html .= "<!-- Liste de catégories -->";
        $html .= "</div>";

        return $html;
    }

    // ============================================
    // UTILITAIRES
    // ============================================

    private function decodeBlockJson($block) {
        $jsonFields = ['content', 'styles', 'mobile_content', 'mobile_styles', 'display_conditions'];

        foreach ($jsonFields as $field) {
            if (isset($block[$field]) && $block[$field]) {
                $block[$field] = json_decode($block[$field], true);
            }
        }

        return $block;
    }
}
