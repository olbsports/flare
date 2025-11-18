const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🧹 Simplification HTML heroes - Suppression left/right/features...\n');

let updatedCount = 0;

const allPages = [
    ...glob.sync('pages/info/*.html', { cwd: __dirname }),
    ...glob.sync('pages/products/*.html', { cwd: __dirname })
];

allPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');
    let modified = false;

    // Pattern 1: Hero avec left/right et CTA dans left
    // <div class="hero-xxx-content">
    //   <div class="hero-xxx-left">
    //     <span>eyebrow</span>
    //     <h1>title</h1>
    //     <p>subtitle</p>
    //     <div class="hero-xxx-cta">...</div>
    //   </div>
    //   <div class="hero-xxx-right">
    //     <div class="hero-xxx-features">...</div>
    //   </div>
    // </div>

    const pattern1 = /<div class="hero-(\w+)-content">\s*<div class="hero-\1-left">\s*(<span class="hero-\1-eyebrow">.*?<\/span>)\s*(<h1 class="hero-\1-title">[\s\S]*?<\/h1>)\s*(<p class="hero-\1-subtitle">[\s\S]*?<\/p>)\s*(?:<div class="hero-\1-cta">[\s\S]*?<\/div>)?\s*<\/div>\s*<div class="hero-\1-right">[\s\S]*?<\/div>\s*<\/div>/g;

    if (content.match(pattern1)) {
        content = content.replace(pattern1, (match, heroType, eyebrow, title, subtitle) => {
            return `<div class="hero-${heroType}-content">
            ${eyebrow}
            ${title}
            ${subtitle}
        </div>`;
        });
        modified = true;
        console.log(`  ✓ Simplifié left/right: ${pagePath}`);
    }

    // Pattern 2: Hero simple avec juste left (sans right)
    const pattern2 = /<div class="hero-(\w+)-content">\s*<div class="hero-\1-left">\s*(<span class="hero-\1-eyebrow">.*?<\/span>)\s*(<h1 class="hero-\1-title">[\s\S]*?<\/h1>)\s*(<p class="hero-\1-subtitle">[\s\S]*?<\/p>)\s*<\/div>\s*<\/div>/g;

    if (content.match(pattern2)) {
        content = content.replace(pattern2, (match, heroType, eyebrow, title, subtitle) => {
            return `<div class="hero-${heroType}-content">
            ${eyebrow}
            ${title}
            ${subtitle}
        </div>`;
        });
        modified = true;
        console.log(`  ✓ Simplifié left seul: ${pagePath}`);
    }

    // Pattern 3: Hero avec CTA mais sans left/right (juste nettoyer les CTA)
    const pattern3 = /<div class="hero-(\w+)-content">\s*(<span class="hero-\1-eyebrow">.*?<\/span>)\s*(<h1 class="hero-\1-title">[\s\S]*?<\/h1>)\s*(<p class="hero-\1-subtitle">[\s\S]*?<\/p>)\s*<div class="hero-\1-cta">[\s\S]*?<\/div>\s*<\/div>/g;

    if (content.match(pattern3)) {
        content = content.replace(pattern3, (match, heroType, eyebrow, title, subtitle) => {
            return `<div class="hero-${heroType}-content">
            ${eyebrow}
            ${title}
            ${subtitle}
        </div>`;
        });
        modified = true;
        console.log(`  ✓ Supprimé CTA inline: ${pagePath}`);
    }

    // Supprimer divs hero-xxx-features seules
    const featuresPattern = /<div class="hero-\w+-features">[\s\S]*?<\/div>/g;
    if (content.match(featuresPattern)) {
        content = content.replace(featuresPattern, '');
        modified = true;
        console.log(`  ✓ Supprimé features orphelines: ${pagePath}`);
    }

    if (modified) {
        fs.writeFileSync(fullPath, content, 'utf-8');
        updatedCount++;
    }
});

console.log(`\n✅ ${updatedCount} pages HTML simplifiées`);
console.log('🧹 Structures left/right/features/cta supprimées !');
