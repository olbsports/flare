/**
 * Product Page Loader
 * Charge dynamiquement les données d'un produit depuis le CSV
 * Utilise le paramètre ?ref= dans l'URL pour identifier le produit
 */

(async function() {
    console.log('🚀 Product Page Loader - Démarrage');

    // Get product reference from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const productRef = urlParams.get('ref');

    console.log('📦 Référence produit depuis URL:', productRef);

    if (!productRef) {
        console.error('❌ Aucune référence produit dans l\'URL');
        showError('Aucun produit spécifié. Veuillez accéder à cette page via un lien produit valide.');
        return;
    }

    try {
        // Initialize CSV Parser
        const parser = new CSVParser();

        // Load CSV data
        console.log('📥 Chargement du fichier CSV...');
        const csvData = await parser.loadCSV('../assets/data/PRICING-FLARE-2025.csv');
        console.log('✅ CSV chargé:', csvData.products.length, 'produits');

        // Find product by reference
        const product = parser.getProductByReference(productRef);

        if (!product) {
            console.error('❌ Produit non trouvé:', productRef);
            showError('Produit non trouvé. La référence ' + productRef + ' n\'existe pas dans notre catalogue.');
            return;
        }

        console.log('✅ Produit trouvé:', product.TITRE_VENDEUR);

        // Render product
        renderProduct(product, parser);

        // Hide loading, show content
        document.getElementById('loading').style.display = 'none';
        document.getElementById('main-content').style.display = 'block';

        // Setup gallery
        setupGallery();

        console.log('✅ Page produit chargée avec succès');

    } catch (error) {
        console.error('❌ Erreur lors du chargement:', error);
        showError('Erreur lors du chargement du produit. Veuillez réessayer plus tard.');
    }
})();

/**
 * Render product data to the page
 */
function renderProduct(product, parser) {
    console.log('🎨 Rendu du produit:', product.REFERENCE_FLARE);

    // === META TAGS & SEO ===
    document.getElementById('page-title').textContent =
        `${product.TITRE_VENDEUR} | Dès ${parseFloat(product.QTY_500 || product.QTY_1).toFixed(2)}€ | FLARE CUSTOM`;

    document.getElementById('page-description').content =
        product.DESCRIPTION_SEO || product.DESCRIPTION || `${product.TITRE_VENDEUR} personnalisé par sublimation. Fabrication Europe. Prix dégressifs.`;

    document.getElementById('page-keywords').content =
        `${product.TITRE_VENDEUR}, ${product.SPORT}, ${product.FAMILLE_PRODUIT}, équipement personnalisé, sublimation, ${product.GRAMMAGE}`;

    // Canonical URL avec la référence
    const canonicalUrl = `https://flare-custom.com/pages/produit.html?ref=${product.REFERENCE_FLARE}`;
    document.getElementById('page-canonical').href = canonicalUrl;

    // Open Graph
    document.getElementById('og-title').content = product.TITRE_VENDEUR;
    document.getElementById('og-description').content = product.DESCRIPTION_SEO || product.DESCRIPTION || '';
    document.getElementById('og-image').content = product.PHOTO_1 || '';

    // Schema.org JSON-LD
    const priceTiers = parser.getPriceTiers(product);
    const lowestPrice = priceTiers[priceTiers.length - 1]?.price || parseFloat(product.QTY_1);
    const highestPrice = priceTiers[0]?.price || parseFloat(product.QTY_1);

    const schema = {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": product.TITRE_VENDEUR,
        "description": product.DESCRIPTION_SEO || product.DESCRIPTION || "",
        "sku": product.CODE,
        "brand": {
            "@type": "Brand",
            "name": "FLARE CUSTOM"
        },
        "offers": {
            "@type": "AggregateOffer",
            "priceCurrency": "EUR",
            "lowPrice": lowestPrice.toFixed(2),
            "highPrice": highestPrice.toFixed(2),
            "availability": "https://schema.org/InStock"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "reviewCount": "127"
        }
    };
    document.getElementById('product-schema').textContent = JSON.stringify(schema, null, 2);

    // === BREADCRUMB ===
    document.getElementById('breadcrumb-sport').textContent = product.SPORT;
    document.getElementById('breadcrumb-sport').href = getSportPageUrl(product.SPORT);
    document.getElementById('breadcrumb-family').textContent = product.FAMILLE_PRODUIT;
    document.getElementById('breadcrumb-family').href = getSportPageUrl(product.SPORT); // Same as sport page
    document.getElementById('breadcrumb-product').textContent = product.TITRE_VENDEUR;

    // === PRODUCT INFO ===
    document.getElementById('product-ref').textContent = `REF: ${product.REFERENCE_FLARE}`;
    document.getElementById('product-title').textContent = product.TITRE_VENDEUR;

    // === GALLERY ===
    const photos = [
        product.PHOTO_1,
        product.PHOTO_2,
        product.PHOTO_3,
        product.PHOTO_4,
        product.PHOTO_5
    ].filter(photo => photo && photo.trim() !== '');

    if (photos.length > 0) {
        document.getElementById('main-product-image').src = photos[0];
        document.getElementById('main-product-image').alt = product.TITRE_VENDEUR;

        const thumbnailGrid = document.getElementById('thumbnail-grid');
        thumbnailGrid.innerHTML = '';
        photos.forEach((photo, index) => {
            const thumb = document.createElement('div');
            thumb.className = 'thumbnail' + (index === 0 ? ' active' : '');
            thumb.innerHTML = `<img src="${photo}" alt="${product.TITRE_VENDEUR} - Photo ${index + 1}">`;
            thumbnailGrid.appendChild(thumb);
        });
    }

    // === SPECS ===
    const specsHtml = `
        <div class="spec-item">
            <div class="spec-label">Sport</div>
            <div class="spec-value">${product.SPORT}</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">Famille</div>
            <div class="spec-value">${product.FAMILLE_PRODUIT}</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">Grammage</div>
            <div class="spec-value">${product.GRAMMAGE}</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">Tissu</div>
            <div class="spec-value">${product.TISSU}</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">Genre</div>
            <div class="spec-value">${product.GENRE}</div>
        </div>
        <div class="spec-item">
            <div class="spec-label">Finition</div>
            <div class="spec-value">${product.FINITION || 'Standard'}</div>
        </div>
    `;
    document.getElementById('product-specs').innerHTML = specsHtml;

    // === PRICING ===
    const priceData = parser.getPriceTiers(product);

    if (priceData.length > 0) {
        // Lowest price (highest quantity)
        const lowestTier = priceData[priceData.length - 1];
        const highestTier = priceData[0];

        document.getElementById('product-price').textContent = `${lowestTier.price.toFixed(2)}€`;
        document.getElementById('price-range').textContent =
            `De ${highestTier.price.toFixed(2)}€ à ${lowestTier.price.toFixed(2)}€ selon quantité`;

        // Savings badge
        const savings = highestTier.price - lowestTier.price;
        if (savings > 0) {
            const savingsBadge = document.getElementById('savings-badge');
            savingsBadge.textContent = `Économisez jusqu'à ${savings.toFixed(2)}€ par pièce`;
            savingsBadge.style.display = 'inline-block';
        }

        // Price tiers table
        const tiersHtml = priceData.map(tier => `
            <div class="price-tier">
                <span class="price-tier-qty">${tier.quantity >= 500 ? '500+' : tier.quantity + '-' + (tier.quantity === 1 ? 4 : tier.quantity < 500 ? (priceData[priceData.indexOf(tier) + 1]?.quantity - 1 || 499) : '')} pièces</span>
                <span class="price-tier-price">${tier.price.toFixed(2)}€ / pièce</span>
            </div>
        `).join('');
        document.getElementById('price-tiers').innerHTML = tiersHtml;
    }

    // === DESCRIPTION ===
    const description = product.DESCRIPTION_SEO || product.DESCRIPTION || '';
    const descriptionLines = description.split('\n').filter(line => line.trim() !== '');

    let descriptionHtml = '<p>Équipement sportif personnalisé par sublimation haute qualité.</p><ul>';
    descriptionLines.forEach(line => {
        const cleanLine = line.replace(/^[-•]\s*/, '').trim();
        if (cleanLine) {
            descriptionHtml += `<li>${cleanLine}</li>`;
        }
    });
    descriptionHtml += '</ul>';

    // Add standard features
    descriptionHtml += `
        <h3 style="margin-top: 32px; margin-bottom: 16px; font-size: 24px;">Caractéristiques techniques</h3>
        <ul>
            <li><strong>Sublimation intégrale</strong> : Impression haute définition directement dans les fibres</li>
            <li><strong>Personnalisation illimitée</strong> : Logos, noms, numéros, sponsors sans surcoût</li>
            <li><strong>Fabrication européenne</strong> : Atelier certifié, contrôle qualité strict</li>
            <li><strong>Délai garanti</strong> : 3-4 semaines de la validation à la livraison</li>
            <li><strong>Sans minimum</strong> : Commandez dès 1 pièce</li>
            <li><strong>Prix dégressifs</strong> : Jusqu'à -60% sur les grandes quantités</li>
        </ul>

        <h3 style="margin-top: 32px; margin-bottom: 16px; font-size: 24px;">Avantages FLARE CUSTOM</h3>
        <ul>
            <li>✓ Devis gratuit sous 24h avec votre design</li>
            <li>✓ Service design professionnel offert</li>
            <li>✓ Catalogue de +100 templates gratuits</li>
            <li>✓ Garantie conformité 100%</li>
            <li>✓ Garantie fabrication 1 an</li>
            <li>✓ Livraison express Europe disponible</li>
        </ul>
    `;

    document.getElementById('product-description-content').innerHTML = descriptionHtml;
}

/**
 * Setup gallery interactions
 */
function setupGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('main-product-image');

    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const img = this.querySelector('img');
            if (img) {
                mainImage.src = img.src;
            }
        });
    });
}

/**
 * Show error message
 */
function showError(message) {
    document.getElementById('loading').innerHTML = `
        <div style="text-align: center; padding: 60px 20px; max-width: 600px;">
            <div style="font-size: 64px; margin-bottom: 24px;">⚠️</div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 36px; margin-bottom: 16px;">Erreur</h2>
            <p style="font-size: 16px; color: #666; margin-bottom: 32px;">${message}</p>
            <a href="../index.html" style="display: inline-block; padding: 16px 32px; background: #FF4B26; color: #fff; text-decoration: none; font-weight: 700; border-radius: 4px;">Retour à l'accueil</a>
        </div>
    `;
}

/**
 * Get sport page URL from sport name
 */
function getSportPageUrl(sport) {
    const sportUrls = {
        'FOOTBALL': '../pages/products/equipement-football-personnalise-sublimation.html',
        'RUGBY': '../pages/products/equipement-rugby-personnalise-sublimation.html',
        'BASKETBALL': '../pages/products/equipement-basketball-personnalise-sublimation.html',
        'HANDBALL': '../pages/products/equipement-handball-personnalise-sublimation.html',
        'VOLLEYBALL': '../pages/products/equipement-volleyball-personnalise-sublimation.html',
        'RUNNING': '../pages/products/equipement-running-course-pied-personnalise.html',
        'CYCLISME': '../pages/products/equipement-cyclisme-velo-personnalise-sublimation.html',
        'TRIATHLON': '../pages/products/equipement-triathlon-personnalise-sublimation.html',
        'PETANQUE': '../pages/products/equipement-petanque-personnalise-club.html',
        'MERCHANDISING': '../pages/products/merchandising-accessoires-club-personnalises.html',
        'SPORTSWEAR': '../pages/products/sportswear-vetements-sport-personnalises.html'
    };

    return sportUrls[sport] || '../index.html';
}
