const fs = require('fs');
const path = require('path');

// Lire le fichier actuel
const filePath = path.join(__dirname, 'assets/css/style-sport.css');
let content = fs.readFileSync(filePath, 'utf-8');

// CSS moderne de la page d'accueil
const newHeroCSS = `/* ========================================
   HERO SPORT - DESIGN IDENTIQUE PAGE D'ACCUEIL
   ======================================== */

.hero-sport,
.hero-faq,
.hero-livraison,
.hero-contact,
.hero-budget,
.hero-retours,
.hero-revendeur,
.hero-page {
    position: relative;
    min-height: 70vh;
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    align-items: center;
    padding: 140px 5% 100px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
    gap: 80px;
    max-width: 100%;
    overflow: hidden;
    margin-top: 0 !important;
}

/* Formes décoratives animées */
.hero-sport::before,
.hero-faq::before,
.hero-livraison::before,
.hero-contact::before {
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
.hero-contact::after {
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

.hero-sport-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    position: relative;
    z-index: 1;
    max-width: 100%;
    width: auto;
    margin: 0;
    color: #1a1a1a;
}

.hero-sport-left {
    animation: fadeInLeft 1s ease;
    position: relative;
    z-index: 1;
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-40px); }
    to { opacity: 1; transform: translateX(0); }
}

.hero-sport-right {
    animation: fadeInRight 1s ease 0.3s backwards;
    position: relative;
    z-index: 1;
}

@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(40px); }
    to { opacity: 1; transform: translateX(0); }
}

.hero-sport-eyebrow {
    display: inline-block;
    padding: 10px 24px;
    background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 32px;
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(255,75,38,0.3);
}

.hero-sport-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(64px, 8vw, 120px);
    font-weight: 700;
    line-height: 0.95;
    letter-spacing: 2px;
    margin-bottom: 28px;
    color: #1a1a1a;
}

.hero-sport-main {
    display: block;
}

.hero-sport-sub {
    display: block;
    color: #FF4B26;
}

.hero-sport-subtitle,
.hero-sport-desc {
    font-size: clamp(16px, 2vw, 20px);
    line-height: 1.7;
    color: #495057;
    margin-bottom: 48px;
    max-width: 600px;
    font-weight: 400;
}

.hero-sport-cta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-hero-primary {
    padding: 18px 48px;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 50px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-hero-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-hero-primary:hover::before {
    left: 100%;
}

.btn-hero-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}

.btn-hero-secondary {
    padding: 18px 48px;
    background: transparent;
    color: #1a1a1a;
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 50px;
    border: 2px solid #1a1a1a;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
}

.btn-hero-secondary::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: #1a1a1a;
    transform: translate(-50%, -50%);
    transition: width 0.4s, height 0.4s;
    z-index: -1;
}

.btn-hero-secondary:hover::before {
    width: 300px;
    height: 300px;
}

.btn-hero-secondary:hover {
    color: #fff;
}

.hero-sport-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    background: #fff;
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    transition: all 0.4s ease;
}

.hero-sport-features:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 80px rgba(0,0,0,0.15);
}

.feature-badge {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #FF4B26 0%, #E63910 100%);
    transition: width 0.3s ease;
}

.feature-badge:hover::before {
    width: 100%;
    opacity: 0.05;
}

.feature-badge:hover {
    background: #fff;
    transform: translateX(5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.feature-badge svg {
    width: 40px;
    height: 40px;
    color: #FF4B26;
    fill: #FF4B26;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.feature-badge:hover svg {
    transform: scale(1.1) rotate(5deg);
}

/* Masquer anciens éléments */
.hero-sport-background,
.hero-sport-overlay,
.breadcrumb {
    display: none !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-sport {
        grid-template-columns: 1fr;
        padding: 120px 5% 80px;
        gap: 60px;
        min-height: auto;
    }

    .hero-sport-content {
        grid-template-columns: 1fr;
    }

    .hero-sport-left {
        text-align: center;
    }

    .hero-sport-subtitle {
        margin: 0 auto 48px;
    }

    .hero-sport-cta {
        justify-content: center;
    }

    .hero-sport-features {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-sport {
        padding: 100px 5% 60px;
    }

    .btn-hero-primary,
    .btn-hero-secondary {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
}

`;

// Supprimer tout le CSS hero existant (jusqu'à la première section non-hero)
const heroSectionEnd = content.search(/\/\* ={40,}\s*\n\s*PRODUCTS SECTION|\/\* Products|\.products-section/);

let beforeHero = content.substring(0, content.indexOf('/* === HERO SPORT ==='));
let afterHero = heroSectionEnd > -1 ? content.substring(heroSectionEnd) : '';

// Si pas de section après trouvée, essayer autrement
if (!afterHero) {
    const productsSectionMatch = content.match(/\/\*[\s\S]*?(PRODUCTS|Products Grid|\.products-)/);
    if (productsSectionMatch) {
        const idx = productsSectionMatch.index;
        beforeHero = content.substring(0, content.indexOf('/* === HERO SPORT ==='));
        afterHero = content.substring(idx);
    }
}

// Reconstruire le fichier
const newContent = beforeHero + newHeroCSS + '\n\n' + afterHero;

fs.writeFileSync(filePath, newContent, 'utf-8');

console.log('✅ assets/css/style-sport.css - Hero modernisé avec design page d\'accueil');
console.log('✨ Les 6 pages restantes utiliseront maintenant ce CSS !');
