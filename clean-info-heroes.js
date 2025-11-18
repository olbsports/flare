const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🔧 Nettoyage heroes pages info - Suppression features et fix polices...\n');

const cleanerHeroCSS = `
/* ===== DESIGN CENTRÉ - PAGES INFO (SANS OVERRIDE) ===== */
.hero-sport-content,
.hero-faq-content,
.hero-livraison-content,
.hero-contact-content,
.hero-budget-content,
.hero-retours-content,
.hero-revendeur-content,
.hero-page-content {
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    z-index: 1;
}

/* Masquer left/right divisions */
.hero-sport-left,
.hero-faq-left,
.hero-livraison-left,
.hero-contact-left,
.hero-budget-left,
.hero-retours-left,
.hero-revendeur-left,
.hero-page-left,
.hero-sport-right,
.hero-faq-right,
.hero-livraison-right,
.hero-contact-right,
.hero-budget-right,
.hero-retours-right,
.hero-revendeur-right,
.hero-page-right {
    all: initial;
    display: contents;
}

.hero-sport-eyebrow,
.hero-faq-eyebrow,
.hero-livraison-eyebrow,
.hero-contact-eyebrow,
.hero-budget-eyebrow,
.hero-retours-eyebrow,
.hero-revendeur-eyebrow,
.hero-page-eyebrow {
    display: inline-block;
    background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 3px;
    font-size: 14px;
    margin-bottom: 24px;
    font-family: 'Inter', sans-serif !important;
}

.hero-sport-title,
.hero-faq-title,
.hero-livraison-title,
.hero-contact-title,
.hero-budget-title,
.hero-retours-title,
.hero-revendeur-title,
.hero-page-title {
    font-family: 'Bebas Neue', sans-serif !important;
    font-size: 72px !important;
    font-weight: 700 !important;
    letter-spacing: 2px;
    line-height: 1.1;
    margin-bottom: 28px;
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
.hero-page-subtitle {
    font-size: 20px;
    line-height: 1.7;
    color: #495057;
    max-width: 700px;
    margin: 0 auto 48px;
    font-weight: 500;
    font-family: 'Inter', sans-serif !important;
}

/* CTA Buttons centrés */
.hero-sport-cta,
.hero-faq-cta,
.hero-livraison-cta,
.hero-contact-cta,
.hero-budget-cta,
.hero-retours-cta,
.hero-revendeur-cta,
.hero-page-cta {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.btn-hero-primary,
.btn-cta-main,
.btn-cta-white {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 36px;
    background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    border-radius: 50px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 4px 20px rgba(255,75,38,0.3);
}

.btn-hero-primary:hover,
.btn-cta-main:hover,
.btn-cta-white:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(255,75,38,0.4);
}

.btn-hero-secondary,
.btn-cta-secondary,
.btn-cta-outline {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 36px;
    background: #fff;
    color: #1a1a1a;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    border: 2px solid #1a1a1a;
    border-radius: 50px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.btn-hero-secondary:hover,
.btn-cta-secondary:hover,
.btn-cta-outline:hover {
    background: #1a1a1a;
    color: #fff;
    transform: translateY(-4px);
}

/* Masquer toutes les sections features */
.hero-sport-features,
.hero-faq-features,
.hero-livraison-features,
.hero-contact-features,
.hero-contact-badges,
.hero-budget-features,
.hero-retours-features,
.hero-revendeur-features,
.hero-page-features {
    display: none !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-sport-title,
    .hero-faq-title,
    .hero-livraison-title,
    .hero-contact-title,
    .hero-budget-title {
        font-size: 56px !important;
    }
}

@media (max-width: 768px) {
    .hero-sport-title,
    .hero-faq-title,
    .hero-livraison-title,
    .hero-contact-title {
        font-size: 42px !important;
    }

    .btn-hero-primary,
    .btn-hero-secondary {
        width: 100%;
        justify-content: center;
    }

    .hero-sport-cta,
    .hero-faq-cta {
        flex-direction: column;
    }
}
`;

let updatedCount = 0;

const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');
    let modified = false;

    // Supprimer les divs hero-xxx-right avec features
    const rightDivPattern = /<div class="hero-(sport|faq|livraison|contact|budget|retours|revendeur|page)-right">\s*<div class="hero-\1-features">[\s\S]*?<\/div>\s*<\/div>/g;
    if (content.match(rightDivPattern)) {
        content = content.replace(rightDivPattern, '');
        modified = true;
        console.log(`  ✓ Supprimé hero-right features: ${pagePath}`);
    }

    // Supprimer les divs hero-contact-badges
    const badgesPattern = /<div class="hero-contact-right">\s*<div class="hero-contact-badges">[\s\S]*?<\/div>\s*<\/div>/g;
    if (content.match(badgesPattern)) {
        content = content.replace(badgesPattern, '');
        modified = true;
        console.log(`  ✓ Supprimé hero-contact-badges: ${pagePath}`);
    }

    // Remplacer tout le CSS hero par le nouveau (plus simple)
    const heroStartPattern = /\/\* =====+ NOUVEAU DESIGN CENTRÉ - PAGES INFO =====+ \*\//;
    const heroStart = content.search(heroStartPattern);

    if (heroStart !== -1) {
        // Trouver la fin de la section hero
        const nextSectionPattern = /\/\* (Masquer anciens éléments|Responsive) \*\//;
        let searchFrom = heroStart + 100;
        const nextSection = content.substring(searchFrom).search(nextSectionPattern);

        if (nextSection !== -1) {
            const actualNextSection = searchFrom + nextSection;
            const before = content.substring(0, heroStart);
            const after = content.substring(actualNextSection);
            content = before + cleanerHeroCSS + '\n' + after;
            modified = true;
            console.log(`  ✓ CSS simplifié: ${pagePath}`);
        }
    }

    if (modified) {
        fs.writeFileSync(fullPath, content, 'utf-8');
        updatedCount++;
    }
});

console.log(`\n✅ ${updatedCount} pages nettoyées`);
console.log('🎨 Features supprimées, polices fixées, CSS simplifié !');
