const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🔧 Nettoyage final des @media dupliqués...\n');

let fixedCount = 0;

const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    let modified = false;

    // Pattern 1: Deux @media (max-width: 768px) imbriqués
    const pattern1 = /(@media \(max-width: 768px\) \{)\s*@media \(max-width: 768px\) \{/g;
    if (content.match(pattern1)) {
        content = content.replace(pattern1, '$1');
        modified = true;
    }

    // Pattern 2: Trois @media (max-width: ...) imbriqués au début
    const pattern2 = /@media \(max-width: 1024px\) \{\s*@media \(max-width: 1024px\) \{\s*@media \(max-width: (1024px|768px)\) \{/g;
    if (content.match(pattern2)) {
        // Remplacer par un seul @media qui correspond au dernier (le plus petit)
        content = content.replace(pattern2, '@media (max-width: $1) {');
        modified = true;
    }

    if (modified) {
        fs.writeFileSync(fullPath, content, 'utf-8');
        console.log(`  ✓ Nettoyé: ${pagePath}`);
        fixedCount++;
    }
});

console.log(`\n✅ ${fixedCount} pages nettoyées`);
console.log('✨ @media dupliqués supprimés !');
