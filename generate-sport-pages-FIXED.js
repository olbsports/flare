const fs = require('fs');

// Fonctions de parsing CSV correct (gère quotes et multi-lignes)
function parseCSV(content) {
    const lines = [];
    let currentLine = '';
    let inQuotes = false;

    for (let i = 0; i < content.length; i++) {
        const char = content[i];
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === '\n' && !inQuotes) {
            if (currentLine.trim()) lines.push(currentLine);
            currentLine = '';
            continue;
        }
        currentLine += char;
    }
    if (currentLine.trim()) lines.push(currentLine);
    return lines;
}

function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;

    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === ';' && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }
    result.push(current.trim());
    return result;
}

// Lire et parser le CSV
const csvPath = './assets/data/PRICING-FLARE-2025.csv';
const csvContent = fs.readFileSync(csvPath, 'utf-8');
const lines = parseCSV(csvContent);
const headers = parseCSVLine(lines[0]);

// Parser le CSV
const products = [];
for (let i = 1; i < lines.length; i++) {
    const values = parseCSVLine(lines[i]);
    const product = {};
    headers.forEach((header, index) => {
        product[header] = values[index] || '';
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
        seoDescription: 'Merchandising club personnalisé : écharpes, serviettes, fanions, drapeaux, plaids. Accessoires supporters personnalisés pour clubs sportifs. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>accessoires merchandising club personnalisés</strong> renforcent identité visuelle et sentiment appartenance supporters. Notre gamme complète de <strong>29 produits merchandising</strong> transforme fans en ambassadeurs fidèles arborant fièrement couleurs équipe.</p>

<p>Les <strong>écharpes club personnalisées</strong> constituent l'accessoire iconique supporters défilant dans tribunes agitant couleurs équipe. Tissage jacquard haute définition reproduit logos écussons slogans club avec détails précis. Les <strong>écharpes reversibles</strong> double face optimisent visibilité affichage.</p>

<p>Les <strong>serviettes sport personnalisées</strong> servent vestiaires clubs piscines installations sportives. Tissus éponge absorbants haute qualité avec logos clubs sublimés résistant lavages industriels fréquents. Les <strong>fanions club personnalisés</strong> décorent salles réunions bureaux dirigeants boutiques officielles supporters créant ambiance sportive motivante.</p>

<p>Les <strong>drapeaux club géants</strong> animent tribunes gradins créant spectacle visuel impressionnant. Les <strong>plaids personnalisés</strong> confortables douillets accompagnent supporters matchs extérieurs températures fraîches. Les <strong>dossards compétition personnalisés</strong> équipent événements courses trails manifestations sportives numérotation lisible identification participants.</p>

<p>Les <strong>accessoires supporters personnalisés</strong> génèrent revenus complémentaires clubs via boutiques officielles ventes merchandising matchs événements. Fidélisation supporters création communauté engagée autour valeurs club. Visibilité accrue sponsors partenaires logos intégrés accessoires portés quotidiennement supporters.</p>

<p>Tarifs accessibles <strong>merchandising pas cher</strong> qualité professionnelle : prix dégressifs volumes commandes, fabrication européenne certifiée, personnalisation illimitée sans frais supplémentaires, délais production rapides, service création graphique design professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">29 produits merchandising • Écharpes fanions serviettes • Drapeaux plaids dossards • Personnalisation illimitée • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'SPORTSWEAR': {
        title: 'Sportswear Personnalisé Tous Sports',
        slug: 'sportswear-vetements-club-tous-sports',
        eyebrow: `${productsBySport['SPORTSWEAR']?.length || 0} vêtements personnalisables`,
        subtitle: `${productsBySport['SPORTSWEAR']?.length || 0} vêtements sportswear. Polos, sweats, vestes, gilets, pantalons. Tenues lifestyle club, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sportswear Personnalisé Club | Vêtements Lifestyle - FLARE CUSTOM',
        seoDescription: 'Sportswear personnalisé : polos, sweats, vestes, pantalons club. Vêtements lifestyle personnalisés pour clubs sportifs. Fabrication européenne, personnalisation illimitée, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>vêtements sportswear personnalisés</strong> permettent aux clubs sportifs développer identité visuelle au-delà terrains compétitions. Notre collection complète de <strong>70 produits sportswear</strong> habille dirigeants staff joueurs supporters usage quotidien représentation club.</p>

<p>Les <strong>polos sportswear club</strong> offrent élégance décontractée réunions officielles événements corporate représentations publiques. Tissus jersey piqué texturés respirants col classique ou bord côte logos clubs brodés sublimés haute définition. Les <strong>sweats sportswear personnalisés</strong> combinent confort urbain moderne et fierté appartenance club molleton doux capuche optionnelle.</p>

<p>Les <strong>vestes sportswear club</strong> protègent élégamment intempéries déplacements matchs extérieurs cérémonies officielles. Versions légères coupe-vent matelassées softshell imperméables selon saisons besoins. Les <strong>gilets sportswear sans manches</strong> apportent couche thermique supplémentaire légère compactable transitions météo.</p>

<p>Les <strong>pantalons sportswear personnalisés</strong> complètent tenues lifestyle club survêtements coordonnés ensembles training. Tissus confortables stretch ceintures élastiques ajustables poches pratiques. Les <strong>sweats capuche zippés</strong> style urbain moderne couleurs clubs logos sponsors intégrés subtilement.</p>

<p>Le <strong>sportswear club personnalisé</strong> renforce cohésion équipe sentiment appartenance hors terrains. Dirigeants entraîneurs staff arborent couleurs club réunions négociations événements officiels. Joueurs portent tenues lifestyle déplacements conférences presse apparitions publiques. Supporters acquièrent vêtements qualité boutiques officielles générant revenus merchandising.</p>

<p>Tarifs accessibles <strong>sportswear pas cher</strong> qualité premium : prix dégressifs volumes commandes clubs, fabrication européenne certifiée éco-responsable, matières recyclées, personnalisation illimitée, délais rapides, service design création graphique professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">70 vêtements sportswear • Polos sweats vestes gilets • Pantalons tenues complètes • Style lifestyle moderne • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
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
    ].filter(p => p && p.trim() && p.startsWith('http'));

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
        `<button class="slider-dot ${index === 0 ? 'active' : ''}" data-slide="${index}" aria-label="Voir photo ${index + 1}"></button>`
    ).join('');

    const finitionsHTML = finitions.map(f =>
        `<span class="product-finition-badge">${f}</span>`
    ).join('');

    return `<div class="product-card">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            ${slidesHTML}
                        </div>
                        <button class="slider-nav prev" aria-label="Photo précédente">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="slider-nav next" aria-label="Photo suivante">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
                        <div class="product-slider-dots">${dotsHTML}</div>
                        <div class="product-badges"></div>
                    </div>
                    <div class="product-info">
                        <div class="product-family">${product.FAMILLE_PRODUIT}</div>
                        <h3 class="product-name">${product.TITRE_VENDEUR}</h3>
                        <div class="product-specs">
                            <span class="product-spec">${product.SPORT}</span><span class="product-spec">${product.GENRE || 'Unisexe'}</span>
                        </div>
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
                </div>
`;
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

    // Remplacer TOUS les "maillot/maillots" par le terme approprié
    const sportTerms = {
        'MERCHANDISING': { singular: 'accessoire', plural: 'accessoires', singularCap: 'Accessoire', pluralCap: 'Accessoires' },
        'SPORTSWEAR': { singular: 'vêtement', plural: 'vêtements', singularCap: 'Vêtement', pluralCap: 'Vêtements' }
    };

    const terms = sportTerms[sport];
    if (terms) {
        // Remplacer toutes les occurrences de "maillot" par le terme approprié
        template = template.replace(/\bmaillots personnalisés\b/gi, `${terms.plural} personnalisés`);
        template = template.replace(/\bMaillots personnalisés\b/g, `${terms.pluralCap} personnalisés`);
        template = template.replace(/\bNos maillots\b/g, `Nos ${terms.plural}`);
        template = template.replace(/\bdes maillots\b/gi, `des ${terms.plural}`);
        template = template.replace(/\bvos maillots\b/gi, `vos ${terms.plural}`);
        template = template.replace(/\bles maillots\b/gi, `les ${terms.plural}`);
        template = template.replace(/\bde maillots\b/gi, `de ${terms.plural}`);
        template = template.replace(/\bmaillot\b/gi, terms.singular);
        template = template.replace(/\bMaillot\b/g, terms.singularCap);
        template = template.replace(/\bmaillots\b/gi, terms.plural);
        template = template.replace(/\bMaillots\b/g, terms.pluralCap);
    }

    // Remplacer le compteur de produits
    template = template.replace(/108 produits/g, `${sportProducts.length} produits`);

    // Remplacer toute la grille de produits
    const gridStart = template.indexOf('<div class="products-grid" id="productsGrid">');
    let searchPos = gridStart;
    let depth = 0;
    let gridEnd = -1;

    while (searchPos < template.length) {
        if (template.substring(searchPos, searchPos + 5) === '<div ') {
            depth++;
        } else if (template.substring(searchPos, searchPos + 6) === '</div>') {
            depth--;
            if (depth === 0) {
                gridEnd = searchPos + 6;
                break;
            }
        }
        searchPos++;
    }

    if (gridStart !== -1 && gridEnd !== -1) {
        const before = template.substring(0, gridStart);
        const after = template.substring(gridEnd);
        template = before + `<div class="products-grid" id="productsGrid">\n${productsCardsHTML}\n            </div>` + after;
    }

    // Remplacer TOUTE la section SEO longtail
    const seoStart = template.indexOf('<section class="seo-longtail-mega">');
    const seoEnd = template.indexOf('</section>', seoStart) + 10;

    if (seoStart !== -1 && seoEnd > seoStart) {
        const before = template.substring(0, seoStart);
        const after = template.substring(seoEnd);
        template = before + `<section class="seo-mega">
        <div class="container" style="max-width: 900px; margin: 0 auto;">
            <div class="seo-hero">
                <div class="seo-hero-badge">${sport}</div>
                <h2 class="seo-hero-title">${config.title}</h2>
                <div class="seo-hero-intro">
                    ${config.seoContent}
                </div>
            </div>
        </div>
    </section>` + after;
    }

    return template;
}

// Générer les pages
console.log('🔧 Génération des pages Merchandising et Sportswear avec VRAI contenu SEO...\n');

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

console.log('\n✨ FINALEMENT TERMINÉ Merchandising & Sportswear avec le BON contenu SEO !');
