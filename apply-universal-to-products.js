const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🎨 Application design universel aux pages produits...\n');

const universalCSS = fs.readFileSync(path.join(__dirname, 'universal-hero-design.css'), 'utf-8');

let updatedCount = 0;

const productPages = glob.sync('pages/products/*.html', { cwd: __dirname });

productPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Chercher le <style> tag
    const styleMatch = content.match(/<style>([\s\S]*?)<\/style>/);

    if (!styleMatch) {
        console.log(`  ⚠ Pas de <style>: ${pagePath}`);
        return;
    }

    const oldCSS = styleMatch[1];

    // Extraire le CSS qui n'est pas lié au hero (après "Masquer anciens éléments" ou "=== TRUST BAR ===")
    const afterHeroPattern = /(\/\* (Masquer anciens éléments|=== TRUST BAR ===|=== CTA REDESIGN ===|Responsive)[\s\S]*)/;
    const afterHeroMatch = oldCSS.match(afterHeroPattern);

    let nonHeroCSS = '';
    if (afterHeroMatch) {
        nonHeroCSS = afterHeroMatch[1];
    }

    // Nouveau CSS = universal + non-hero
    const newCSS = universalCSS + '\n\n' + nonHeroCSS;

    // Remplacer tout le contenu <style>
    const newContent = content.replace(styleMatch[0], '<style>' + newCSS + '\n    </style>');

    fs.writeFileSync(fullPath, newContent, 'utf-8');
    console.log(`  ✓ Design universel appliqué: ${pagePath}`);
    updatedCount++;
});

console.log(`\n✅ ${updatedCount} pages produits avec design universel`);
console.log('🎨 Tous les heroes sont maintenant identiques !');
