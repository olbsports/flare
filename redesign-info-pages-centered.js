const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🎨 Nouveau design centré pour les pages info...\n');

const newHeroCSS = `
/* ===== NOUVEAU DESIGN CENTRÉ - PAGES INFO ===== */
.hero-sport,
.hero-faq,
.hero-livraison,
.hero-contact,
.hero-budget,
.hero-retours,
.hero-revendeur,
.hero-page,
.hero-innovation-v2 {
    position: relative;
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 160px 5% 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
    overflow: hidden;
    margin-top: 0 !important;
}

/* Formes décoratives animées */
.hero-sport::before,
.hero-faq::before,
.hero-livraison::before,
.hero-contact::before,
.hero-budget::before,
.hero-retours::before,
.hero-revendeur::before,
.hero-page::before {
    content: '';
    position: absolute;
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(255,75,38,0.12) 0%, transparent 70%);
    border-radius: 50%;
    top: -300px;
    right: -200px;
    z-index: 0;
    animation: pulse 8s ease-in-out infinite;
}

.hero-sport::after,
.hero-faq::after,
.hero-livraison::after,
.hero-contact::after,
.hero-budget::after,
.hero-retours::after,
.hero-revendeur::after,
.hero-page::after {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(0,123,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    bottom: -200px;
    left: -150px;
    z-index: 0;
    animation: pulse 10s ease-in-out infinite reverse;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* === CONTENU CENTRÉ === */
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
    position: relative;
    z-index: 1;
    animation: fadeInUp 1s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Masquer left/right si présents */
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
}

.hero-sport-title,
.hero-faq-title,
.hero-livraison-title,
.hero-contact-title,
.hero-budget-title,
.hero-retours-title,
.hero-revendeur-title,
.hero-page-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 72px;
    font-weight: 700;
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
    margin-bottom: 64px;
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

/* Features en grille 2x2 */
.hero-sport-features,
.hero-faq-features,
.hero-livraison-features,
.hero-contact-features,
.hero-contact-badges,
.hero-budget-features,
.hero-retours-features,
.hero-revendeur-features,
.hero-page-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.feature-badge,
.hero-badge,
.contact-badge {
    background: #fff;
    padding: 28px 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    text-align: center;
    border: 2px solid transparent;
}

.feature-badge:hover,
.hero-badge:hover,
.contact-badge:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(255,75,38,0.12);
    border-color: rgba(255,75,38,0.2);
}

.feature-badge svg,
.hero-badge svg,
.contact-badge svg {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    display: block;
    color: #FF4B26;
    fill: #FF4B26;
    transition: transform 0.3s ease;
}

.feature-badge:hover svg,
.hero-badge:hover svg,
.contact-badge:hover svg {
    transform: scale(1.15) rotate(-8deg);
}

.feature-badge span,
.hero-badge span,
.contact-badge span {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.4;
}

/* Masquer anciens éléments */
.hero-sport-background,
.hero-sport-overlay,
.hero-contact-background,
.hero-faq-background,
.hero-livraison-background,
.hero-budget-overlay,
.hero-budget-image,
.hero-innovation-bg,
.breadcrumb {
    display: none !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-sport,
    .hero-faq,
    .hero-livraison,
    .hero-contact,
    .hero-budget {
        padding: 140px 5% 100px;
        min-height: 70vh;
    }

    .hero-sport-title,
    .hero-faq-title,
    .hero-livraison-title,
    .hero-contact-title,
    .hero-budget-title {
        font-size: 56px;
    }

    .hero-sport-features,
    .hero-contact-badges {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-sport,
    .hero-faq {
        padding: 120px 5% 80px;
    }

    .hero-sport-title,
    .hero-faq-title,
    .hero-livraison-title,
    .hero-contact-title {
        font-size: 42px;
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

    // Chercher la section CSS hero
    const heroStartPattern = /\/\* =====+ HERO MODERNE - DESIGN IDENTIQUE PAGE D'ACCUEIL =====+ \*\//;
    const heroStart = content.search(heroStartPattern);

    if (heroStart === -1) {
        return;
    }

    // Trouver la fin de la section hero (avant "Masquer anciens éléments" ou responsive)
    const nextSectionPattern = /\/\* (Masquer anciens éléments|Responsive) \*\//;
    let searchFrom = heroStart + 100;
    const nextSection = content.substring(searchFrom).search(nextSectionPattern);

    if (nextSection === -1) {
        return;
    }

    const actualNextSection = searchFrom + nextSection;

    // Remplacer tout le CSS hero
    const before = content.substring(0, heroStart);
    const after = content.substring(actualNextSection);

    const newContent = before + newHeroCSS + '\n' + after;

    fs.writeFileSync(fullPath, newContent, 'utf-8');
    console.log(`  ✓ Design centré appliqué: ${pagePath}`);
    updatedCount++;
});

console.log(`\n✅ ${updatedCount} pages info avec nouveau design centré`);
console.log('🎨 Design moderne, vertical, centré avec les mêmes couleurs !');
