const fs = require('fs');
const path = require('path');

// Pages de catégories familles de produits à modifier
const categoryPages = [
    'pages/products/maillots-sport-personnalises.html',
    'pages/products/shorts-sport-personnalises.html',
    'pages/products/polos-sport-personnalises.html',
    'pages/products/sweats-sport-personnalises.html',
    'pages/products/vestes-sport-personnalisees.html',
    'pages/products/pantalons-sport-personnalises.html',
    'pages/products/debardeurs-sport-personnalises.html',
    'pages/products/maillots-football-personnalises.html',
    'pages/products/maillots-rugby-personnalises.html',
    'pages/products/maillots-basket-personnalises.html',
    'pages/products/maillots-cyclisme-personnalises.html',
    'pages/products/maillots-running-personnalises.html',
    'pages/products/shorts-football-personnalises.html',
    'pages/products/shorts-basketball-personnalises.html',
    'pages/products/vestes-clubs-personnalisees.html',
    'pages/products/sweats-capuche-sport-personnalises.html',
    'pages/products/pantalons-entrainement-personnalises.html',
    'pages/products/tshirts-sport-personnalises.html'
];

console.log('🚀 Ajout du script product-cards-linker.js aux pages catégories...\n');

let modifiedCount = 0;

categoryPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);

    if (!fs.existsSync(fullPath)) {
        console.log(`⚠️  Page non trouvée: ${pagePath}`);
        return;
    }

    let content = fs.readFileSync(fullPath, 'utf-8');

    // Vérifier si le script est déjà présent
    if (content.includes('product-cards-linker.js')) {
        console.log(`✓ Déjà présent: ${pagePath}`);
        return;
    }

    // Ajouter le script avant la balise </body>
    const scriptTag = `    <script src="../../assets/js/product-cards-linker.js"></script>\n</body>`;
    content = content.replace('</body>', scriptTag);

    // Sauvegarder
    fs.writeFileSync(fullPath, content, 'utf-8');
    modifiedCount++;
    console.log(`✅ Modifié: ${pagePath}`);
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages modifiées.`);
console.log('Les cartes produits sont maintenant cliquables et redirigent vers les pages produits.');
