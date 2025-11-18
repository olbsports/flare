const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🎨 Application du design universel heroes à TOUTES les pages...\n');

const universalCSS = fs.readFileSync(path.join(__dirname, 'universal-hero-design.css'), 'utf-8');

let updatedCount = 0;

// 1. PAGES INFO - Remplacer tout le CSS hero
console.log('📄 Pages INFO...');
const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Trouver la section CSS hero (entre <style> et </style>)
    const styleMatch = content.match(/<style>([\s\S]*?)<\/style>/);

    if (styleMatch) {
        const oldCSS = styleMatch[1];

        // Extraire le CSS non-hero (après "Masquer anciens éléments" ou autre section)
        const afterHeroPattern = /\/\* (Masquer anciens éléments|=== CONTACT SECTION ===|Responsive)/;
        const afterHeroIndex = oldCSS.search(afterHeroPattern);

        let nonHeroCSS = '';
        if (afterHeroIndex !== -1) {
            nonHeroCSS = oldCSS.substring(afterHeroIndex);
        }

        // Nouveau CSS = CSS universel + CSS non-hero de la page
        const newCSS = universalCSS + '\n\n' + nonHeroCSS;

        content = content.replace(styleMatch[0], '<style>' + newCSS + '\n    </style>');

        fs.writeFileSync(fullPath, content, 'utf-8');
        console.log(`  ✓ ${pagePath}`);
        updatedCount++;
    }
});

// 2. STYLE-SPORT.CSS - Remplacer le CSS hero
console.log('\n📦 assets/css/style-sport.css...');
const styleSportPath = path.join(__dirname, 'assets/css/style-sport.css');
let styleSportContent = fs.readFileSync(styleSportPath, 'utf-8');

// Trouver le début du hero CSS
const heroStartPattern = /\/\* =+ HERO SPORT - DESIGN IDENTIQUE PAGE D'ACCUEIL =+ \*\/|\/\* =+ NOUVEAU DESIGN HERO-SPORT-FEATURES/;
const heroStart = styleSportContent.search(heroStartPattern);

if (heroStart !== -1) {
    // Trouver la fin (avant .products-section ou autre)
    const heroEndPattern = /\n\.products-section/;
    let searchFrom = heroStart + 100;
    const heroEnd = styleSportContent.substring(searchFrom).search(heroEndPattern);

    if (heroEnd !== -1) {
        const actualHeroEnd = searchFrom + heroEnd;
        const before = styleSportContent.substring(0, heroStart);
        const after = styleSportContent.substring(actualHeroEnd);

        styleSportContent = before + universalCSS + '\n\n' + after;
        fs.writeFileSync(styleSportPath, styleSportContent, 'utf-8');
        console.log('  ✓ CSS universel appliqué');
        updatedCount++;
    }
}

console.log(`\n✅ ${updatedCount} fichiers mis à jour avec le design universel`);
console.log('🎨 Design simple, propre, responsive mobile optimisé !');
