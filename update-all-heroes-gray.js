const fs = require('fs');
const path = require('path');
const glob = require('glob');

// CSS UNIVERSEL POUR TOUS LES HERO HEADERS - FOND GRIS + CARDS CARRÉES
const universalHeroCSS = `        /* ===== HERO 2 COLONNES - FOND GRIS + CARDS CARRÉES ===== */
        .hero-sport,
        .hero-faq,
        .hero-livraison,
        .hero-contact,
        .hero-budget,
        .hero-retours,
        .hero-revendeur,
        .hero-page,
        .hero-innovation-v2 {
            background: #e8e8e8 !important;
            margin-top: 80px;
            padding: 2.5rem 5% 2rem;
            min-height: auto !important;
            position: relative !important;
        }

        .hero-sport-content,
        .hero-faq-content,
        .hero-livraison-content,
        .hero-contact-content,
        .hero-budget-content,
        .hero-retours-content,
        .hero-revendeur-content,
        .hero-page-content,
        .hero-innovation-content-v2 {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-sport-left,
        .hero-faq-left,
        .hero-livraison-left,
        .hero-contact-left,
        .hero-budget-left,
        .hero-retours-left,
        .hero-revendeur-left,
        .hero-page-left,
        .hero-innovation-left {
            text-align: left;
        }

        .hero-sport-right,
        .hero-faq-right,
        .hero-livraison-right,
        .hero-contact-right,
        .hero-budget-right,
        .hero-retours-right,
        .hero-revendeur-right,
        .hero-page-right,
        .hero-innovation-right {
            background: #fff;
            padding: 2.5rem;
            border-radius: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero-sport-eyebrow,
        .hero-faq-eyebrow,
        .hero-livraison-eyebrow,
        .hero-contact-eyebrow,
        .hero-budget-eyebrow,
        .hero-retours-eyebrow,
        .hero-revendeur-eyebrow,
        .hero-page-eyebrow,
        .hero-eyebrow {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #FF4B26;
            padding: 10px 24px;
            border: 2px solid #FF4B26;
            margin-bottom: 24px;
            background: transparent;
            border-radius: 0;
        }

        .hero-sport-title,
        .hero-faq-title,
        .hero-livraison-title,
        .hero-contact-title,
        .hero-budget-title,
        .hero-retours-title,
        .hero-revendeur-title,
        .hero-page-title,
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(42px, 6vw, 64px);
            line-height: 1.05;
            letter-spacing: 2px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .hero-sport-subtitle,
        .hero-faq-subtitle,
        .hero-livraison-subtitle,
        .hero-contact-subtitle,
        .hero-budget-subtitle,
        .hero-budget-desc,
        .hero-retours-subtitle,
        .hero-revendeur-subtitle,
        .hero-page-subtitle,
        .hero-subtitle {
            font-size: clamp(15px, 2vw, 18px);
            line-height: 1.7;
            color: #666;
            margin-bottom: 32px;
        }

        .hero-sport-cta,
        .hero-faq-cta,
        .hero-livraison-cta,
        .hero-contact-cta,
        .hero-budget-cta,
        .hero-retours-cta,
        .hero-revendeur-cta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .btn-hero-primary,
        .btn-primary {
            padding: 16px 36px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            background: #FF4B26;
            color: #fff;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-hero-primary:hover,
        .btn-primary:hover {
            background: #E63910;
            transform: translateY(-2px);
        }

        .btn-hero-secondary,
        .btn-secondary {
            padding: 16px 36px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            background: transparent;
            color: #1a1a1a;
            border: 2px solid #1a1a1a;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .btn-hero-secondary:hover,
        .btn-secondary:hover {
            background: #1a1a1a;
            color: #fff;
        }

        .hero-sport-features,
        .hero-faq-features,
        .hero-livraison-features,
        .hero-contact-features,
        .hero-budget-features,
        .hero-retours-features,
        .hero-revendeur-features,
        .hero-contact-badges {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .feature-badge,
        .contact-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            background: #f8f8f8;
            border-radius: 0;
            border-left: 3px solid #FF4B26;
            transition: all 0.3s ease;
        }

        .feature-badge:hover,
        .contact-badge:hover {
            background: #f0f0f0;
            border-left-color: #000;
        }

        .feature-badge svg,
        .contact-badge svg {
            width: 24px;
            height: 24px;
            color: #FF4B26;
            fill: #FF4B26;
            flex-shrink: 0;
        }

        .feature-badge span,
        .contact-badge span {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* Masquer les anciens éléments */
        .hero-sport-background,
        .hero-sport-overlay,
        .hero-contact-background,
        .hero-faq-background,
        .hero-livraison-background,
        .hero-budget-overlay,
        .hero-budget-image,
        .breadcrumb {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-sport-content,
            .hero-faq-content,
            .hero-livraison-content,
            .hero-contact-content,
            .hero-budget-content,
            .hero-retours-content,
            .hero-revendeur-content,
            .hero-page-content,
            .hero-innovation-content-v2 {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .hero-sport-left,
            .hero-faq-left,
            .hero-livraison-left,
            .hero-contact-left,
            .hero-budget-left,
            .hero-retours-left,
            .hero-revendeur-left {
                text-align: center;
            }
            .hero-sport-features,
            .hero-contact-badges {
                grid-template-columns: 1fr;
            }
        }`;

console.log('🚀 Mise à jour de TOUS les hero headers - Fond gris + Cards carrées...\n');

let modifiedCount = 0;

// 1. Modifier toutes les pages products/
const productPages = glob.sync('pages/products/*.html', { cwd: __dirname });

productPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Si la page n'a pas de hero, skip
    if (!content.includes('class="hero-')) {
        return;
    }

    // Remplacer le CSS existant dans <style>
    const styleRegex = /(<style>[\s\S]*?)(<\/style>)/;
    const styleMatch = content.match(styleRegex);

    if (styleMatch) {
        // Supprimer l'ancien CSS hero s'il existe
        let styleContent = styleMatch[1];

        // Supprimer tous les anciens CSS de hero
        styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?(\.hero-sport|\.hero-faq|\.hero-livraison|\.hero-contact)\s*\{[\s\S]*?\}\s*@media/g, '@media');
        styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?\.breadcrumb\s*\{[\s\S]*?\}/g, '');

        // Ajouter le nouveau CSS universel
        const newStyleBlock = styleContent + '\n' + universalHeroCSS + '\n    ' + styleMatch[2];
        content = content.replace(styleRegex, newStyleBlock);
    }

    // Vérifier si la structure 2 colonnes existe déjà
    if (!content.includes('hero-sport-left') && !content.includes('hero-faq-left')) {
        // Transformer la structure HTML si besoin
        const heroSectionRegex = /<section class="hero-sport">([\s\S]*?)<\/section>/;
        const heroMatch = content.match(heroSectionRegex);

        if (heroMatch) {
            const heroContent = heroMatch[1];
            const eyebrowMatch = heroContent.match(/<span class="hero-sport-eyebrow">([\s\S]*?)<\/span>/);
            const titleMatch = heroContent.match(/<h1[^>]*class="hero-sport-title"[^>]*>([\s\S]*?)<\/h1>/);
            const descMatch = heroContent.match(/<p[^>]*class="hero-sport-desc"[^>]*>([\s\S]*?)<\/p>/);
            const ctaMatch = heroContent.match(/(<div class="hero-sport-cta">[\s\S]*?<\/div>)/);
            const featuresMatch = heroContent.match(/(<div class="hero-sport-features">[\s\S]*?<\/div>\s*<\/div>)/);

            let eyebrow = eyebrowMatch ? eyebrowMatch[1].trim() : '';
            let title = '';
            let subtitle = '';

            if (titleMatch) {
                const titleHTML = titleMatch[1];
                const mainInTitle = titleHTML.match(/<span class="hero-sport-main">([\s\S]*?)<\/span>/);
                const subInTitle = titleHTML.match(/<span class="hero-sport-sub">([\s\S]*?)<\/span>/);

                if (mainInTitle) title = mainInTitle[1].trim();
                if (subInTitle) subtitle = subInTitle[1].trim();

                // Si pas de structure span, prendre le texte brut
                if (!title) title = titleHTML.replace(/<[^>]+>/g, '').trim();
            }

            if (!subtitle && descMatch) {
                subtitle = descMatch[1].trim().replace(/<br>/g, ' · ');
            }

            const ctaHTML = ctaMatch ? ctaMatch[1] : '';
            const featuresHTML = featuresMatch ? featuresMatch[1] : '';

            const newHeroHTML = `
        <section class="hero-sport">
        <div class="hero-sport-content">
            <div class="hero-sport-left">
                <span class="hero-sport-eyebrow">${eyebrow}</span>
                <h1 class="hero-sport-title">${title}</h1>
                <p class="hero-sport-subtitle">${subtitle}</p>
                ${ctaHTML}
            </div>
            <div class="hero-sport-right">
                ${featuresHTML}
            </div>
        </div>
    </section>`;

            content = content.replace(heroSectionRegex, newHeroHTML);
        }
    }

    fs.writeFileSync(fullPath, content, 'utf-8');
    console.log(`✅ ${pagePath}`);
    modifiedCount++;
});

// 2. Modifier toutes les pages info/
const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Si la page n'a pas de hero, skip
    if (!content.includes('class="hero-')) {
        return;
    }

    // Injecter le CSS dans <head> ou <style> existant
    if (content.includes('<link rel="stylesheet" href="../../assets/css/faq.css">')) {
        // Créer un style inline après les links
        const headEndRegex = /(<\/head>)/;
        const styleBlock = `    <style>\n${universalHeroCSS}\n    </style>\n</head>`;
        content = content.replace(headEndRegex, styleBlock);
    } else if (content.includes('<style>')) {
        const styleRegex = /(<style>[\s\S]*?)(<\/style>)/;
        const styleMatch = content.match(styleRegex);
        if (styleMatch) {
            let styleContent = styleMatch[1];
            styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?\.hero-\w+\s*\{[\s\S]*?\}/g, '');
            const newStyleBlock = styleContent + '\n' + universalHeroCSS + '\n    ' + styleMatch[2];
            content = content.replace(styleRegex, newStyleBlock);
        }
    }

    fs.writeFileSync(fullPath, content, 'utf-8');
    console.log(`✅ ${pagePath}`);
    modifiedCount++;
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages modifiées.`);
console.log('🎨 Nouveau design universel : fond gris #e8e8e8 + cards carrées avec bordure gauche orange !');
