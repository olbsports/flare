const fs = require('fs');

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
    if (product.SPORT && product.FAMILLE_PRODUIT && product.FAMILLE_PRODUIT.length > 0 && !product.FAMILLE_PRODUIT.startsWith('-') && !product.FAMILLE_PRODUIT.startsWith('http')) {
        products.push(product);
    }
}

// Grouper par sport
const productsBySport = {};
products.forEach(product => {
    const sport = product.SPORT;
    if (!productsBySport[sport]) {
        productsBySport[sport] = [];
    }
    productsBySport[sport].push(product);
});

// Configuration des sports à générer
const sportConfig = {
    'MERCHANDISING': {
        title: 'Merchandising & Accessoires Club Personnalisés',
        slug: 'merchandising-accessoires-club-tous-sports',
        eyebrow: `${productsBySport['MERCHANDISING']?.length || 0} accessoires personnalisables`,
        subtitle: `${productsBySport['MERCHANDISING']?.length || 0} accessoires merchandising. Écharpes, serviettes, fanions, drapeaux, plaids, dossards. Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Merchandising Club Personnalisé | Accessoires Supporters - FLARE CUSTOM',
        seoDescription: 'Merchandising club personnalisé : écharpes, serviettes, fanions, drapeaux, plaids. Accessoires supporters personnalisés pour clubs sportifs. Fabrication européenne, prix dégressifs. Devis gratuit 24h.'
    },
    'SPORTSWEAR': {
        title: 'Sportswear Personnalisé Tous Sports',
        slug: 'sportswear-vetements-club-tous-sports',
        eyebrow: `${productsBySport['SPORTSWEAR']?.length || 0} vêtements personnalisables`,
        subtitle: `${productsBySport['SPORTSWEAR']?.length || 0} vêtements sportswear. Polos, sweats, vestes, gilets, pantalons. Tenues lifestyle club, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sportswear Personnalisé Club | Vêtements Lifestyle - FLARE CUSTOM',
        seoDescription: 'Sportswear personnalisé : polos, sweats, vestes, pantalons club. Vêtements lifestyle personnalisés pour clubs sportifs. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.'
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
    const prixEnfant = (prixQty500 * 0.9).toFixed(2);

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
                        <div class="product-sport">${product.FAMILLE_PRODUIT}</div>
                        <h3 class="product-name">${product.TITRE_VENDEUR}</h3>
                        <div class="product-genre">${product.GENRE || 'Unisexe'}</div>
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
function generateSportPage(sport, config) {
    const sportProducts = productsBySport[sport] || [];
    if (sportProducts.length === 0) {
        console.log(`Pas de produits pour ${sport}`);
        return null;
    }

    const productsCardsHTML = sportProducts.map(p => generateProductCard(p)).join('\n');

    // Lire le template de base
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
    template = template.replace(/108 produits/g, `${sportProducts.length} produits`);

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
console.log('Génération des pages Merchandising et Sportswear...\n');

['MERCHANDISING', 'SPORTSWEAR'].forEach(sport => {
    const config = sportConfig[sport];
    const sportProducts = productsBySport[sport] || [];

    console.log(`${sport}: ${sportProducts.length} produits`);

    if (sportProducts.length > 0) {
        const pageContent = generateSportPage(sport, config);
        if (pageContent) {
            const outputPath = `./pages/products/${config.slug}.html`;
            fs.writeFileSync(outputPath, pageContent);
            console.log(`  ✅ Créé: ${outputPath}`);
        }
    } else {
        console.log(`  ⏭️  Ignoré (pas de produits)`);
    }
});

console.log('\n✨ Génération terminée!');
