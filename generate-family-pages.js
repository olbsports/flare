const fs = require('fs');
const path = require('path');

// Lire et parser le CSV
const csvPath = './assets/data/PRICING-FLARE-2025.csv';
const csvContent = fs.readFileSync(csvPath, 'utf-8');
const lines = csvContent.split('\n');
const headers = lines[0].split(';');

// Parser le CSV
const products = [];
for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    const values = lines[i].split(';');
    const product = {};
    headers.forEach((header, index) => {
        product[header.trim()] = values[index] ? values[index].trim() : '';
    });
    if (product.FAMILLE_PRODUIT && product.FAMILLE_PRODUIT.length > 0 && !product.FAMILLE_PRODUIT.startsWith('-') && !product.FAMILLE_PRODUIT.startsWith('http')) {
        products.push(product);
    }
}

// Grouper par famille de produit
const productsByFamily = {};
products.forEach(product => {
    const family = product.FAMILLE_PRODUIT;
    if (!productsByFamily[family]) {
        productsByFamily[family] = [];
    }
    productsByFamily[family].push(product);
});

// Familles à générer (celles avec au moins 8 produits)
const familiesToGenerate = [
    { family: 'Polo', minCount: 8 },
    { family: 'Sweat', minCount: 8 },
    { family: 'Sweat à Capuche', minCount: 8 },
    { family: 'T-Shirt', minCount: 8 },
    { family: 'Débardeur', minCount: 8 },
    { family: 'Veste', minCount: 8 },
    { family: 'Pantalon', minCount: 8 },
    { family: 'Gilet', minCount: 8 },
    { family: 'Coupe-Vent', minCount: 8 }
];

// Configuration des familles de produits
const familyConfig = {
    'Polo': {
        title: 'Polo Sport Personnalisé',
        slug: 'polos-sport-personnalises',
        eyebrow: `${productsByFamily['Polo']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Polo']?.length || 0} modèles tous sports. Tissus techniques haute performance, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Polo Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Polo sport personnalisé en sublimation intégrale. Modèles pour tous sports : football, rugby, basketball, running, cyclisme. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Sweat': {
        title: 'Sweat Sport Personnalisé',
        slug: 'sweats-sport-personnalises',
        eyebrow: `${productsByFamily['Sweat']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Sweat']?.length || 0} modèles tous sports. Tissus techniques confortables, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sweat Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Sweat sport personnalisé en sublimation intégrale. Modèles pour tous sports : football, rugby, basketball, running. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Sweat à Capuche': {
        title: 'Sweat à Capuche Sport Personnalisé',
        slug: 'sweats-capuche-sport-personnalises',
        eyebrow: `${productsByFamily['Sweat à Capuche']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Sweat à Capuche']?.length || 0} modèles tous sports. Capuche ajustable, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sweat à Capuche Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Sweat à capuche sport personnalisé en sublimation intégrale. Modèles pour tous sports : football, rugby, basketball, running. Fabrication européenne, prix dégressifs. Devis gratuit 24h.'
    },
    'T-Shirt': {
        title: 'T-Shirt Sport Personnalisé',
        slug: 'tshirts-sport-personnalises',
        eyebrow: `${productsByFamily['T-Shirt']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['T-Shirt']?.length || 0} modèles tous sports. Tissus légers respirants, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'T-Shirt Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'T-shirt sport personnalisé en sublimation intégrale. Modèles pour tous sports : football, rugby, basketball, running. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Débardeur': {
        title: 'Débardeur Sport Personnalisé',
        slug: 'debardeurs-sport-personnalises',
        eyebrow: `${productsByFamily['Débardeur']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Débardeur']?.length || 0} modèles tous sports. Sans manches pour liberté maximale, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Débardeur Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Débardeur sport personnalisé en sublimation intégrale. Modèles pour tous sports : running, basketball, volleyball. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Veste': {
        title: 'Veste Sport Personnalisée',
        slug: 'vestes-sport-personnalisees',
        eyebrow: `${productsByFamily['Veste']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Veste']?.length || 0} modèles tous sports. Protection optimale, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Veste Sport Personnalisée Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Veste sport personnalisée en sublimation intégrale. Modèles pour tous sports : football, rugby, basketball, running, cyclisme. Fabrication européenne, prix dégressifs. Devis gratuit 24h.'
    },
    'Pantalon': {
        title: 'Pantalon Sport Personnalisé',
        slug: 'pantalons-sport-personnalises',
        eyebrow: `${productsByFamily['Pantalon']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Pantalon']?.length || 0} modèles tous sports. Confort et performance, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Pantalon Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Pantalon sport personnalisé en sublimation intégrale. Modèles pour tous sports : football, running, training. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Gilet': {
        title: 'Gilet Sport Personnalisé',
        slug: 'gilets-sport-personnalises',
        eyebrow: `${productsByFamily['Gilet']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Gilet']?.length || 0} modèles tous sports. Léger et pratique, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Gilet Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Gilet sport personnalisé en sublimation intégrale. Modèles pour tous sports : cyclisme, running. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    },
    'Coupe-Vent': {
        title: 'Coupe-Vent Sport Personnalisé',
        slug: 'coupe-vent-sport-personnalises',
        eyebrow: `${productsByFamily['Coupe-Vent']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Coupe-Vent']?.length || 0} modèles tous sports. Protection contre le vent, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Coupe-Vent Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Coupe-vent sport personnalisé en sublimation intégrale. Modèles pour tous sports : cyclisme, running. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
    }
};

// Fonction pour générer une carte produit
function generateProductCard(product) {
    const photos = [
        product.PHOTO_1,
        product.PHOTO_2,
        product.PHOTO_3,
        product.PHOTO_4,
        product.PHOTO_5
    ].filter(p => p && p.trim());

    const finitions = product.FINITION ? product.FINITION.split(',').map(f => f.trim()) : [];
    const prixQty500 = parseFloat(product.QTY_500) || 0;
    const prixAdulte = prixQty500.toFixed(2);
    const prixEnfant = (prixQty500 * 0.9).toFixed(2); // Prix enfant = 90% du prix adulte

    const slidesHTML = photos.map((photo, index) =>
        `<div class="product-slide ${index === 0 ? 'active' : ''}">
                <img src="${photo}" alt="${product.TITRE_VENDEUR} - Photo ${index + 1}" class="product-image" loading="lazy" width="420" height="560" decoding="async">
            </div>`
    ).join('');

    const dotsHTML = photos.map((_, index) =>
        `<button class="slider-dot ${index === 0 ? 'active' : ''}" data-slide="${index}" aria-label="Photo ${index + 1}"></button>`
    ).join('');

    const finitionsHTML = finitions.map(f =>
        `<span class="product-finition-badge">${f}</span>`
    ).join('');

    return `<div class="product-card">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            ${slidesHTML}
                        </div>
                        ${photos.length > 1 ? `
                        <button class="slider-nav prev" aria-label="Photo précédente">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </button>
                        <button class="slider-nav next" aria-label="Photo suivante">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>
                        <div class="slider-dots">
                            ${dotsHTML}
                        </div>
                        ` : ''}
                    </div>
                    <div class="product-content">
                        <div class="product-sport">${product.SPORT}</div>
                        <h3 class="product-name">${product.TITRE_VENDEUR}</h3>
                        <div class="product-genre">${product.GENRE}</div>
                        <div class="product-finitions">${finitionsHTML}</div>
                        <div class="product-pricing">
                            <div class="product-price-label">À partir de</div>
                            <div class="product-price-adulte">
                                <span class="product-price-type">Adulte</span>
                                <span class="product-price">${prixAdulte}€</span>
                            </div>
                            <div class="product-price-enfant">
                                <span class="product-price-type">Enfant</span>
                                <span class="product-price-small">${prixEnfant}€</span>
                            </div>
                        </div>
                    </div>
                </div>`;
}

// Fonction pour générer la page HTML complète
function generateFamilyPage(family, config) {
    const familyProducts = productsByFamily[family] || [];
    if (familyProducts.length === 0) {
        console.log(`Pas de produits pour ${family}`);
        return null;
    }

    const productsCardsHTML = familyProducts.map(p => generateProductCard(p)).join('\n');

    // Lire le template de base de maillots-sport-personnalises.html et l'adapter
    const templatePath = './pages/products/maillots-sport-personnalises.html';
    let template = fs.readFileSync(templatePath, 'utf-8');

    // Remplacer les infos spécifiques
    template = template.replace(/<title>.*?<\/title>/, `<title>${config.seoTitle}</title>`);
    template = template.replace(/<meta name="description" content=".*?"/, `<meta name="description" content="${config.seoDescription}"`);

    // Remplacer le hero
    template = template.replace(/108 modèles personnalisables tous sports/g, config.eyebrow);
    template = template.replace(/Maillots Sport Sublimation/g, config.title);
    template = template.replace(/108 modèles tous sports\. Tissus techniques haute performance.*?pièces\./g, config.subtitle);

    // Remplacer le compteur de produits
    template = template.replace(/108 produits/g, `${familyProducts.length} produits`);

    // Remplacer toute la grille de produits
    const gridStart = template.indexOf('<div class="products-grid" id="productsGrid">');
    const gridEnd = template.indexOf('</div>', gridStart + template.substring(gridStart).indexOf('</div>')) + 6;

    if (gridStart !== -1 && gridEnd !== -1) {
        const before = template.substring(0, gridStart);
        const after = template.substring(gridEnd);
        template = before + `<div class="products-grid" id="productsGrid">\n${productsCardsHTML}\n            </div>` + after;
    }

    return template;
}

// Générer les pages
console.log('Génération des pages de familles de produits...\n');

Object.keys(familyConfig).forEach(family => {
    const config = familyConfig[family];
    const familyProducts = productsByFamily[family] || [];

    console.log(`${family}: ${familyProducts.length} produits`);

    if (familyProducts.length >= 8) {
        const pageContent = generateFamilyPage(family, config);
        if (pageContent) {
            const outputPath = `./pages/products/${config.slug}.html`;
            fs.writeFileSync(outputPath, pageContent);
            console.log(`  ✅ Créé: ${outputPath}`);
        }
    } else {
        console.log(`  ⏭️  Ignoré (moins de 8 produits)`);
    }
});

console.log('\n✨ Génération terminée!');
