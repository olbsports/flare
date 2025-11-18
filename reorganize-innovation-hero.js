const fs = require('fs');
const path = require('path');

console.log('🔧 Réorganisation du hero innovation...\n');

const filePath = path.join(__dirname, 'pages/info/innovation-technologie-sublimation-textile-sportif.html');
let content = fs.readFileSync(filePath, 'utf-8');

// Pattern pour hero-innovation-v2
const innovationPattern = /<section class="hero-innovation-v2">\s*<div class="hero-innovation-bg"><\/div>\s*<div class="hero-innovation-content-v2">\s*<span class="hero-eyebrow-v2">([^<]+)<\/span>\s*<h1 class="hero-title-v2">([^<]+)<br><span class="highlight">([^<]+)<\/span><\/h1>\s*<p class="hero-subtitle-v2">([^<]+)<\/p>\s*<div class="hero-stats">([\s\S]*?)<\/div>\s*<\/div>\s*<\/section>/;

if (content.match(innovationPattern)) {
    const match = content.match(innovationPattern);
    const eyebrow = match[1];
    const titlePart1 = match[2];
    const titlePart2 = match[3];
    const subtitle = match[4];
    const stats = match[5];

    const newHeroHTML = `<section class="hero-innovation-v2">
        <div class="hero-innovation-content-v2">
            <div class="hero-innovation-left">
                <span class="hero-sport-eyebrow">${eyebrow}</span>
                <h1 class="hero-sport-title">
                    <span class="hero-sport-main">${titlePart1}</span>
                    <span class="hero-sport-sub">${titlePart2}</span>
                </h1>
                <p class="hero-sport-subtitle">${subtitle}</p>

                <div class="hero-sport-cta">
                    <a href="/pages/info/contact.html" class="btn-hero-primary">
                        Demander un devis
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="#science-intro" class="btn-hero-secondary">Découvrir la technologie</a>
                </div>
            </div>

            <div class="hero-innovation-right">
                <div class="hero-sport-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>150+ Lavages garantis</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M7 21C6.46957 21 5.96086 20.7893 5.58579 20.4142C5.21071 20.0391 5 19.5304 5 19V5C5 4.46957 5.21071 3.96086 5.58579 3.58579C5.96086 3.21071 6.46957 3 7 3H14L19 8V19C19 19.5304 18.7893 20.0391 18.4142 20.4142C18.0391 20.7893 17.5304 21 17 21H7Z"/>
                            <path d="M14 3V8H19" stroke="white" stroke-width="2"/>
                        </svg>
                        <span>16.7M Couleurs possibles</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                        </svg>
                        <span>0mm Surépaisseur</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>100% Fabrication Europe</span>
                    </div>
                </div>
            </div>
        </div>
    </section>`;

    content = content.replace(innovationPattern, newHeroHTML);
    fs.writeFileSync(filePath, content, 'utf-8');
    console.log('  ✓ Réorganisé hero-innovation-v2 en 2 colonnes');
    console.log('✅ Page innovation réorganisée avec les mêmes codes couleurs !');
} else {
    console.log('❌ Pattern non trouvé pour hero-innovation-v2');
}
