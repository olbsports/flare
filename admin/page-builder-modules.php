<?php
/**
 * FLARE CUSTOM - Page Builder Modules
 * Définitions des modules pour l'éditeur visuel style Elementor
 */

// ============ DÉFINITIONS DES MODULES ============

$PAGE_BUILDER_MODULES = [

    // ========== HERO SECTIONS ==========
    'hero_sport' => [
        'name' => 'Hero Sport',
        'icon' => 'M3 4v16h18V4H3zm16 14H5V6h14v12z',
        'category' => 'hero',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Sous-titre', 'default' => '⚽ Sport'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Équipements Sport'],
            'subtitle' => ['type' => 'text', 'label' => 'Sous-titre', 'default' => 'Personnalisés Sublimation'],
        ],
        'template' => '
<section class="hero-sport">
    <div class="hero-sport-content">
        <span class="hero-sport-eyebrow">{{eyebrow}}</span>
        <h1 class="hero-sport-title">{{title}}</h1>
        <p class="hero-sport-subtitle">{{subtitle}}</p>
    </div>
</section>'
    ],

    'hero_contact' => [
        'name' => 'Hero Contact',
        'icon' => 'M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z',
        'category' => 'hero',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Sous-titre', 'default' => 'Contactez-nous'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Besoin d\'un devis ?'],
            'subtitle' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Notre équipe est à votre disposition'],
        ],
        'template' => '
<section class="hero-contact">
    <div class="hero-contact-content">
        <span class="hero-contact-eyebrow">{{eyebrow}}</span>
        <h1 class="hero-contact-title">{{title}}</h1>
        <p class="hero-contact-subtitle">{{subtitle}}</p>
    </div>
</section>'
    ],

    'hero_page' => [
        'name' => 'Hero Page',
        'icon' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z',
        'category' => 'hero',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Sous-titre', 'default' => 'FLARE CUSTOM'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Titre de la page'],
            'subtitle' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Description de la page'],
        ],
        'template' => '
<section class="hero-page">
    <div class="hero-page-content">
        <span class="hero-page-eyebrow">{{eyebrow}}</span>
        <h1 class="hero-page-title">{{title}}</h1>
        <p class="hero-page-subtitle">{{subtitle}}</p>
    </div>
</section>'
    ],

    // ========== CONTENT SECTIONS ==========
    'trust_bar' => [
        'name' => 'Barre de Confiance',
        'icon' => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z',
        'category' => 'content',
        'fields' => [
            'stat1_value' => ['type' => 'text', 'label' => 'Stat 1 - Valeur', 'default' => '500+'],
            'stat1_label' => ['type' => 'text', 'label' => 'Stat 1 - Label', 'default' => 'Clubs équipés'],
            'stat2_value' => ['type' => 'text', 'label' => 'Stat 2 - Valeur', 'default' => '4.9/5'],
            'stat2_label' => ['type' => 'text', 'label' => 'Stat 2 - Label', 'default' => 'Satisfaction client'],
            'stat3_value' => ['type' => 'text', 'label' => 'Stat 3 - Valeur', 'default' => '48h'],
            'stat3_label' => ['type' => 'text', 'label' => 'Stat 3 - Label', 'default' => 'Devis rapide'],
            'stat4_value' => ['type' => 'text', 'label' => 'Stat 4 - Valeur', 'default' => '100%'],
            'stat4_label' => ['type' => 'text', 'label' => 'Stat 4 - Label', 'default' => 'Sublimation'],
        ],
        'template' => '
<section class="trust-bar">
    <div class="container">
        <div class="trust-items">
            <div class="trust-item">
                <strong>{{stat1_value}}</strong>
                <span>{{stat1_label}}</span>
            </div>
            <div class="trust-item">
                <strong>{{stat2_value}}</strong>
                <span>{{stat2_label}}</span>
            </div>
            <div class="trust-item">
                <strong>{{stat3_value}}</strong>
                <span>{{stat3_label}}</span>
            </div>
            <div class="trust-item">
                <strong>{{stat4_value}}</strong>
                <span>{{stat4_label}}</span>
            </div>
        </div>
    </div>
</section>'
    ],

    'section_header' => [
        'name' => 'En-tête de Section',
        'icon' => 'M5 4v3h5.5v12h3V7H19V4H5z',
        'category' => 'content',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Surtitre', 'default' => 'Découvrez'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Nos produits'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Une description de la section'],
        ],
        'template' => '
<div class="section-header">
    <div class="section-eyebrow">{{eyebrow}}</div>
    <h2 class="section-title">{{title}}</h2>
    <p class="section-description">{{description}}</p>
</div>'
    ],

    'text_block' => [
        'name' => 'Bloc de Texte',
        'icon' => 'M3 3v18h18V3H3zm16 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z',
        'category' => 'content',
        'fields' => [
            'content' => ['type' => 'richtext', 'label' => 'Contenu', 'default' => '<p>Votre texte ici...</p>'],
        ],
        'template' => '
<div class="text-block">
    <div class="container">
        {{content}}
    </div>
</div>'
    ],

    'products_grid' => [
        'name' => 'Grille Produits',
        'icon' => 'M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z',
        'category' => 'products',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Surtitre', 'default' => 'Catalogue'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Nos équipements'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Découvrez notre sélection de produits'],
            'show_filters' => ['type' => 'checkbox', 'label' => 'Afficher les filtres', 'default' => true],
        ],
        'template' => '
<section class="products-section" id="products">
    <div class="container">
        <div class="section-header">
            <div class="section-eyebrow">{{eyebrow}}</div>
            <h2 class="section-title">{{title}}</h2>
            <p class="section-description">{{description}}</p>
        </div>
        {{#show_filters}}
        <div class="products-filters">
            <!-- Filtres générés automatiquement -->
        </div>
        {{/show_filters}}
        <div class="products-grid" id="products-grid">
            <!-- Produits injectés dynamiquement -->
        </div>
    </div>
</section>'
    ],

    // ========== WHY US / FEATURES ==========
    'why_us' => [
        'name' => 'Pourquoi Nous',
        'icon' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
        'category' => 'features',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Surtitre', 'default' => 'Nos engagements'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Pourquoi choisir Flare Custom'],
            'description' => ['type' => 'text', 'label' => 'Description', 'default' => 'La référence européenne en équipements sportifs personnalisés'],
            'card1_title' => ['type' => 'text', 'label' => 'Carte 1 - Titre', 'default' => 'Design 100% personnalisé'],
            'card1_text' => ['type' => 'textarea', 'label' => 'Carte 1 - Texte', 'default' => 'Aucune limite de couleurs, motifs ou logos.'],
            'card2_title' => ['type' => 'text', 'label' => 'Carte 2 - Titre', 'default' => 'Fabrication européenne'],
            'card2_text' => ['type' => 'textarea', 'label' => 'Carte 2 - Texte', 'default' => 'Production dans nos ateliers partenaires certifiés.'],
            'card3_title' => ['type' => 'text', 'label' => 'Carte 3 - Titre', 'default' => 'Livraison rapide'],
            'card3_text' => ['type' => 'textarea', 'label' => 'Carte 3 - Texte', 'default' => 'Délai standard 3-4 semaines, option express disponible.'],
        ],
        'template' => '
<section class="why-us-section" id="why-us">
    <div class="container">
        <div class="section-header">
            <div class="section-eyebrow">{{eyebrow}}</div>
            <h2 class="section-title">{{title}}</h2>
            <p class="section-desc">{{description}}</p>
        </div>
        <div class="why-us-grid-redesign">
            <div class="why-us-card-redesign">
                <div class="why-us-number">01</div>
                <div class="why-us-icon-redesign">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/></svg>
                </div>
                <h3>{{card1_title}}</h3>
                <p>{{card1_text}}</p>
            </div>
            <div class="why-us-card-redesign">
                <div class="why-us-number">02</div>
                <div class="why-us-icon-redesign">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <h3>{{card2_title}}</h3>
                <p>{{card2_text}}</p>
            </div>
            <div class="why-us-card-redesign">
                <div class="why-us-number">03</div>
                <div class="why-us-icon-redesign">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M13 10V3L4 14H11V21L20 10H13Z" fill="currentColor"/></svg>
                </div>
                <h3>{{card3_title}}</h3>
                <p>{{card3_text}}</p>
            </div>
        </div>
    </div>
</section>'
    ],

    // ========== CTA ==========
    'cta' => [
        'name' => 'Call to Action',
        'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
        'category' => 'cta',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Équipez votre club'],
            'text' => ['type' => 'text', 'label' => 'Texte', 'default' => 'Devis gratuit sous 24h • Designer dédié • Prix dégressifs'],
            'btn1_text' => ['type' => 'text', 'label' => 'Bouton 1 - Texte', 'default' => 'Demander un devis'],
            'btn1_url' => ['type' => 'text', 'label' => 'Bouton 1 - URL', 'default' => '/info/contact'],
            'btn2_text' => ['type' => 'text', 'label' => 'Bouton 2 - Texte', 'default' => '+33 1 23 45 67 89'],
            'btn2_url' => ['type' => 'text', 'label' => 'Bouton 2 - URL', 'default' => 'tel:+33123456789'],
        ],
        'template' => '
<section class="cta-section" id="contact">
    <div class="cta-container">
        <div class="cta-content">
            <h2 class="cta-title">{{title}}</h2>
            <p class="cta-text">{{text}}</p>
            <div class="cta-buttons">
                <a href="{{btn1_url}}" class="btn-cta-primary">
                    {{btn1_text}}
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </a>
                <a href="{{btn2_url}}" class="btn-cta-secondary">{{btn2_text}}</a>
            </div>
        </div>
    </div>
</section>'
    ],

    // ========== FAQ ==========
    'faq' => [
        'name' => 'FAQ',
        'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z',
        'category' => 'content',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Surtitre', 'default' => 'Questions fréquentes'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'FAQ'],
            'description' => ['type' => 'text', 'label' => 'Description', 'default' => 'Toutes les réponses à vos questions'],
            'questions' => ['type' => 'repeater', 'label' => 'Questions', 'fields' => [
                'question' => ['type' => 'text', 'label' => 'Question'],
                'answer' => ['type' => 'textarea', 'label' => 'Réponse'],
            ], 'default' => [
                ['question' => 'Comment passer commande ?', 'answer' => 'Contactez-nous pour recevoir un devis personnalisé.'],
                ['question' => 'Quels sont les délais ?', 'answer' => 'Comptez 3 à 4 semaines pour une commande standard.'],
            ]],
        ],
        'template' => '
<section class="faq-sport-section">
    <div class="container">
        <div class="section-header">
            <div class="section-eyebrow">{{eyebrow}}</div>
            <h2 class="section-title">{{title}}</h2>
            <p class="section-description">{{description}}</p>
        </div>
        <div class="faq-accordion">
            {{#questions}}
            <div class="faq-item">
                <button class="faq-question" onclick="this.parentElement.classList.toggle(\'active\')">
                    <span>{{question}}</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M19 9L12 16L5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <div class="faq-answer">
                    <p>{{answer}}</p>
                </div>
            </div>
            {{/questions}}
        </div>
    </div>
</section>'
    ],

    // ========== CONTACT FORM ==========
    'contact_form' => [
        'name' => 'Formulaire Contact',
        'icon' => 'M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z',
        'category' => 'forms',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Demande de devis gratuit'],
            'description' => ['type' => 'text', 'label' => 'Description', 'default' => 'Remplissez le formulaire et recevez votre devis sous 24h.'],
        ],
        'template' => '
<section class="contact-main-section">
    <div class="contact-container">
        <div class="contact-form-wrapper">
            <div class="form-header">
                <h2>{{title}}</h2>
                <p>{{description}}</p>
            </div>
            <form class="contact-form" action="/api/contact" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">Prénom <span class="required">*</span></label>
                        <input type="text" id="firstname" name="firstname" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Nom <span class="required">*</span></label>
                        <input type="text" id="lastname" name="lastname" class="form-input" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea id="message" name="message" class="form-textarea" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Envoyer ma demande</button>
            </form>
        </div>
    </div>
</section>'
    ],

    // ========== SEO FOOTER ==========
    'seo_footer' => [
        'name' => 'Bloc SEO',
        'icon' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z',
        'category' => 'seo',
        'fields' => [
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'En savoir plus'],
            'content' => ['type' => 'richtext', 'label' => 'Contenu SEO', 'default' => '<p>Contenu optimisé pour le référencement...</p>'],
        ],
        'template' => '
<section class="seo-footer-section">
    <div class="container">
        <h2 class="seo-title">{{title}}</h2>
        <div class="seo-content">
            {{content}}
        </div>
    </div>
</section>'
    ],

    // ========== IMAGE + TEXTE ==========
    'image_text' => [
        'name' => 'Image + Texte',
        'icon' => 'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z',
        'category' => 'content',
        'fields' => [
            'image' => ['type' => 'image', 'label' => 'Image', 'default' => '/assets/images/placeholder.jpg'],
            'image_alt' => ['type' => 'text', 'label' => 'Texte alternatif', 'default' => 'Description de l\'image'],
            'title' => ['type' => 'text', 'label' => 'Titre', 'default' => 'Titre'],
            'content' => ['type' => 'richtext', 'label' => 'Contenu', 'default' => '<p>Votre texte ici...</p>'],
            'image_position' => ['type' => 'select', 'label' => 'Position image', 'options' => ['left' => 'Gauche', 'right' => 'Droite'], 'default' => 'left'],
        ],
        'template' => '
<section class="image-text-section {{image_position}}">
    <div class="container">
        <div class="image-text-grid">
            <div class="image-text-image">
                <img src="{{image}}" alt="{{image_alt}}" loading="lazy">
            </div>
            <div class="image-text-content">
                <h2>{{title}}</h2>
                {{content}}
            </div>
        </div>
    </div>
</section>'
    ],

    // ========== SPACER ==========
    'spacer' => [
        'name' => 'Espacement',
        'icon' => 'M8 19h3v4h2v-4h3l-4-4-4 4zm8-14h-3V1h-2v4H8l4 4 4-4zM4 11v2h16v-2H4z',
        'category' => 'layout',
        'fields' => [
            'height' => ['type' => 'number', 'label' => 'Hauteur (px)', 'default' => 60],
        ],
        'template' => '<div class="spacer" style="height: {{height}}px;"></div>'
    ],

    // ========== HTML PERSONNALISÉ ==========
    'custom_html' => [
        'name' => 'HTML Personnalisé',
        'icon' => 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z',
        'category' => 'advanced',
        'fields' => [
            'html' => ['type' => 'code', 'label' => 'Code HTML', 'default' => '<!-- Votre code HTML ici -->'],
        ],
        'template' => '{{html}}'
    ],

];

// ============ FONCTIONS UTILITAIRES ============

/**
 * Rendre un module avec ses données
 */
function renderModule($moduleType, $data, $modules) {
    if (!isset($modules[$moduleType])) {
        return '<!-- Module inconnu: ' . htmlspecialchars($moduleType) . ' -->';
    }

    $module = $modules[$moduleType];
    $template = $module['template'];

    // Remplacer les variables simples
    foreach ($data as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
    }

    // Gérer les répéteurs (ex: FAQ questions)
    if (preg_match_all('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', $template, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = $match[1];
            $itemTemplate = $match[2];
            $items = $data[$key] ?? [];
            $rendered = '';
            foreach ($items as $item) {
                $itemHtml = $itemTemplate;
                foreach ($item as $k => $v) {
                    $itemHtml = str_replace('{{' . $k . '}}', htmlspecialchars($v), $itemHtml);
                }
                $rendered .= $itemHtml;
            }
            $template = str_replace($match[0], $rendered, $template);
        }
    }

    // Gérer les conditions
    $template = preg_replace('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', '', $template);

    return $template;
}

/**
 * Générer le HTML complet d'une page à partir des blocs
 */
function generatePageHtml($blocks, $pageData, $modules) {
    $html = '';
    foreach ($blocks as $block) {
        $html .= renderModule($block['type'], $block['data'] ?? [], $modules);
    }
    return $html;
}

/**
 * Obtenir les catégories de modules
 */
function getModuleCategories() {
    return [
        'hero' => ['name' => 'Hero', 'icon' => 'M3 4v16h18V4H3z'],
        'content' => ['name' => 'Contenu', 'icon' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z'],
        'products' => ['name' => 'Produits', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4'],
        'features' => ['name' => 'Avantages', 'icon' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77'],
        'cta' => ['name' => 'Appel à l\'action', 'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10'],
        'forms' => ['name' => 'Formulaires', 'icon' => 'M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16'],
        'seo' => ['name' => 'SEO', 'icon' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14'],
        'layout' => ['name' => 'Mise en page', 'icon' => 'M8 19h3v4h2v-4h3l-4-4-4 4z'],
        'advanced' => ['name' => 'Avancé', 'icon' => 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6'],
    ];
}
