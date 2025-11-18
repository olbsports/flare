const fs = require('fs');
const path = require('path');

// Nouveau CSS pour le hero de la page d'accueil
const newHomepageHeroCSS = `/* ========================================
   HERO ACCUEIL DYNAMIQUE - DESIGN MODERNE 2024
   ======================================== */

.hero-adidas {
    position: relative;
    min-height: 90vh;
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    align-items: center;
    padding: 140px 5% 100px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
    gap: 80px;
    max-width: 100%;
    overflow: hidden;
}

/* Formes décoratives dynamiques */
.hero-adidas::before {
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

.hero-adidas::after {
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

/* === GAUCHE - TEXTE === */
.hero-adidas-content {
    animation: fadeInLeft 1s ease;
    position: relative;
    z-index: 1;
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-40px); }
    to { opacity: 1; transform: translateX(0); }
}

.hero-adidas-label {
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

.hero-adidas-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(64px, 8vw, 120px);
    font-weight: 700;
    line-height: 0.95;
    letter-spacing: 2px;
    margin-bottom: 28px;
    color: #1a1a1a;
}

.hero-adidas-title .red {
    background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: inline-block;
}

.hero-adidas-subtitle {
    font-size: clamp(16px, 2vw, 20px);
    line-height: 1.7;
    color: #495057;
    margin-bottom: 48px;
    max-width: 500px;
    font-weight: 400;
}

.hero-adidas-cta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-adidas-black {
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
}

.btn-adidas-black::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-adidas-black:hover::before {
    left: 100%;
}

.btn-adidas-black:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}

.btn-adidas-white {
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
}

.btn-adidas-white::before {
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

.btn-adidas-white:hover::before {
    width: 300px;
    height: 300px;
}

.btn-adidas-white:hover {
    color: #fff;
}

/* === DROITE - VISUEL === */
.hero-adidas-visual {
    position: relative;
    display: grid;
    gap: 24px;
    z-index: 1;
    animation: fadeInRight 1s ease 0.3s backwards;
}

@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(40px); }
    to { opacity: 1; transform: translateX(0); }
}

.hero-adidas-image {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    transition: all 0.4s ease;
}

.hero-adidas-image:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 30px 80px rgba(0,0,0,0.2);
}

.hero-adidas-image img {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.6s ease;
}

.hero-adidas-image:hover img {
    transform: scale(1.05);
}

.badge-adidas {
    position: absolute;
    top: 24px;
    right: 24px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    color: #1a1a1a;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.hero-adidas-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.stat-adidas {
    background: #fff;
    padding: 24px 20px;
    border-radius: 16px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.stat-adidas::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #FF4B26 0%, #E63910 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-adidas:hover::before {
    transform: scaleX(1);
}

.stat-adidas:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    border-color: #FF4B26;
}

.stat-adidas-number {
    font-size: 40px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-adidas-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #495057;
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
    .hero-adidas {
        grid-template-columns: 1fr;
        padding: 120px 5% 80px;
        gap: 60px;
        min-height: auto;
    }

    .hero-adidas-content {
        text-align: center;
    }

    .hero-adidas-label {
        margin: 0 auto 32px;
    }

    .hero-adidas-subtitle {
        margin: 0 auto 48px;
    }

    .hero-adidas-cta {
        justify-content: center;
    }

    .hero-adidas-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-adidas {
        padding: 100px 5% 60px;
    }

    .hero-adidas-title {
        font-size: clamp(48px, 12vw, 64px);
    }

    .btn-adidas-black,
    .btn-adidas-white {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
}`;

console.log('🚀 Mise à jour du hero de la page d\'accueil...\n');

// 1. Modifier assets/css/style.css
const stylePath = path.join(__dirname, 'assets/css/style.css');
let styleContent = fs.readFileSync(stylePath, 'utf-8');

// Remplacer tout le CSS du hero-adidas
const heroRegex = /\/\* ={40,}[\s\S]*?HERO ADIDAS[\s\S]*?={40,} \*\/[\s\S]*?(@media \(max-width: 1024px\) \{[\s\S]*?\.hero-adidas \{[\s\S]*?\}[\s\S]*?\})/;

if (heroRegex.test(styleContent)) {
    styleContent = styleContent.replace(heroRegex, newHomepageHeroCSS + '\n\n@media (max-width: 1024px) {');
} else {
    // Si pas trouvé, chercher juste le début
    const simpleRegex = /\/\* ={40,}[\s\S]*?HERO ADIDAS[\s\S]*?={40,} \*\/[\s\S]*?(\/\* ={40,}|$)/;
    styleContent = styleContent.replace(simpleRegex, newHomepageHeroCSS + '\n\n$1');
}

fs.writeFileSync(stylePath, styleContent, 'utf-8');
console.log('✅ assets/css/style.css - Hero accueil modernisé\n');

console.log('🎉 Terminé !');
console.log('✨ Nouveau hero accueil : Gradient clair, formes animées, boutons modernes, stats dynamiques !');
