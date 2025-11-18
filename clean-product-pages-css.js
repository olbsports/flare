const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🧹 Nettoyage CSS éclaté pages produits...\n');

const universalCSS = fs.readFileSync(path.join(__dirname, 'universal-hero-design.css'), 'utf-8');

let cleanedCount = 0;

const productPages = glob.sync('pages/products/*.html', { cwd: __dirname });

productPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Chercher le <style> tag
    const styleMatch = content.match(/<style>([\s\S]*?)<\/style>/);

    if (!styleMatch) {
        return;
    }

    const oldCSS = styleMatch[1];

    // Garder UNIQUEMENT les sections critiques qui ne sont PAS dans universal-hero-design.css
    // c'est-à-dire: Responsive spécifique à la page, Masquer anciens éléments
    const afterHeroPattern = /(\/\* (Masquer anciens éléments|Responsive)[\s\S]*)/;
    const afterHeroMatch = oldCSS.match(afterHeroPattern);

    let nonHeroCSS = '';
    if (afterHeroMatch) {
        nonHeroCSS = afterHeroMatch[1];
    }

    // Nouveau CSS = UNIQUEMENT universal + section "Masquer anciens éléments" si elle existe
    const newCSS = universalCSS + (nonHeroCSS ? '\n\n' + nonHeroCSS : '');

    // Remplacer tout le contenu <style>
    const newContent = content.replace(styleMatch[0], '<style>' + newCSS + '\n    </style>');

    fs.writeFileSync(fullPath, newContent, 'utf-8');

    const oldLines = styleMatch[0].split('\n').length;
    const newLines = ('<style>' + newCSS + '\n    </style>').split('\n').length;
    const savedLines = oldLines - newLines;

    console.log(`  ✓ ${pagePath}: ${oldLines} → ${newLines} lignes (${savedLines} lignes économisées)`);
    cleanedCount++;
});

console.log(`\n✅ ${cleanedCount} pages produits nettoyées`);
console.log('🧹 CSS minimal et optimisé !');
