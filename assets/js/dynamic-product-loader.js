/**
 * Dynamic Product Data Loader
 * Charge les données du produit depuis le CSV et met à jour la page
 */

(async function() {
    console.log('🔍 Dynamic Product Loader - Démarrage');

    // Vérifier si on a un paramètre ?ref= dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const productRef = urlParams.get('ref');

    if (!productRef) {
        console.log('ℹ️ Pas de référence produit - affichage de la page par défaut');
        return; // Afficher la page par défaut
    }

    console.log('📦 Chargement du produit:', productRef);

    try {
        // Charger le CSV
        const parser = new CSVParser();
        const csvData = await parser.loadCSV('../assets/data/PRICING-FLARE-2025.csv');

        // Trouver le produit
        const product = parser.getProductByReference(productRef);

        if (!product) {
            console.error('❌ Produit non trouvé:', productRef);
            alert(`Produit non trouvé : ${productRef}\n\nVous allez être redirigé vers l'accueil.`);
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 2000);
            return;
        }

        console.log('✅ Produit trouvé:', product.TITRE_VENDEUR);

        // Mettre à jour la page avec les données du produit
        updateProductPage(product, parser);

    } catch (error) {
        console.error('❌ Erreur chargement:', error);
    }
})();

function updateProductPage(product, parser) {
    console.log('🎨 Mise à jour de la page produit');

    // === TITRE ===
    const h1 = document.querySelector('.product-info h1');
    if (h1) {
        h1.innerHTML = product.TITRE_VENDEUR.toUpperCase();
    }

    // === META TAGS ===
    document.title = `${product.TITRE_VENDEUR} | Dès ${parseFloat(product.QTY_500 || product.QTY_1).toFixed(2)}€ | FLARE CUSTOM`;

    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc && product.DESCRIPTION_SEO) {
        metaDesc.content = product.DESCRIPTION_SEO;
    }

    // === BREADCRUMB ===
    const breadcrumbProduct = document.querySelector('.breadcrumb strong');
    if (breadcrumbProduct) {
        breadcrumbProduct.textContent = product.TITRE_VENDEUR;
    }

    // Mettre à jour le lien du sport dans le breadcrumb
    const breadcrumbSportLink = document.querySelector('.breadcrumb a[href*="equipement"]');
    if (breadcrumbSportLink && product.SPORT) {
        const sportUrls = {
            'FOOTBALL': 'equipement-football-personnalise-sublimation.html',
            'RUGBY': 'equipement-rugby-personnalise-sublimation.html',
            'BASKETBALL': 'equipement-basketball-personnalise-sublimation.html',
            'HANDBALL': 'equipement-handball-personnalise-sublimation.html',
            'VOLLEYBALL': 'equipement-volleyball-personnalise-sublimation.html',
            'RUNNING': 'equipement-running-course-pied-personnalise.html',
            'CYCLISME': 'equipement-cyclisme-velo-personnalise-sublimation.html',
            'TRIATHLON': 'equipement-triathlon-personnalise-sublimation.html',
            'PETANQUE': 'equipement-petanque-personnalise-club.html',
            'MERCHANDISING': 'merchandising-accessoires-club-personnalises.html',
            'SPORTSWEAR': 'sportswear-vetements-sport-personnalises.html'
        };

        if (sportUrls[product.SPORT]) {
            breadcrumbSportLink.href = sportUrls[product.SPORT];
            breadcrumbSportLink.textContent = product.SPORT.charAt(0) + product.SPORT.slice(1).toLowerCase();
        }
    }

    // === GALERIE PHOTOS ===
    const photos = [product.PHOTO_1, product.PHOTO_2, product.PHOTO_3, product.PHOTO_4, product.PHOTO_5]
        .filter(photo => photo && photo.trim() !== '');

    if (photos.length > 0) {
        // Image principale
        const mainImg = document.querySelector('.main-image img');
        if (mainImg) {
            mainImg.src = photos[0];
            mainImg.alt = product.TITRE_VENDEUR;
        }

        // Thumbnails
        const thumbnailGrid = document.querySelector('.thumbnail-grid');
        if (thumbnailGrid) {
            thumbnailGrid.innerHTML = '';
            photos.forEach((photo, index) => {
                const thumb = document.createElement('div');
                thumb.className = 'thumbnail' + (index === 0 ? ' active' : '');
                thumb.innerHTML = `<img src="${photo}" alt="${product.TITRE_VENDEUR} - Photo ${index + 1}">`;
                thumb.addEventListener('click', function() {
                    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    if (mainImg) mainImg.src = photo;
                });
                thumbnailGrid.appendChild(thumb);
            });
        }
    }

    // === PRIX ===
    const priceTiers = parser.getPriceTiers(product);
    if (priceTiers.length > 0) {
        const lowestPrice = priceTiers[priceTiers.length - 1];
        const highestPrice = priceTiers[0];

        // Prix principal
        const priceEl = document.querySelector('.price-current');
        if (priceEl) {
            priceEl.textContent = lowestPrice.price.toFixed(2).replace('.', ',') + ' €';
        }

        // Range de prix
        const rangeEl = document.querySelector('.price-range');
        if (rangeEl) {
            rangeEl.textContent = `Prix dégressifs de ${highestPrice.price.toFixed(2)} € à ${lowestPrice.price.toFixed(2)} € / pièce TTC`;
        }

        // Badge économies
        const savings = highestPrice.price - lowestPrice.price;
        const savingsPercent = ((savings / highestPrice.price) * 100).toFixed(0);
        const savingsBadge = document.querySelector('.savings-badge');
        if (savingsBadge && savings > 0) {
            savingsBadge.textContent = `ÉCONOMISEZ JUSQU'À ${savingsPercent}% SUR GRANDES QUANTITÉS`;
        }

        // Mettre à jour les prix dans le configurateur
        if (window.priceTiers) {
            window.priceTiers = priceTiers.map(tier => ({
                qty: tier.quantity,
                price: tier.price
            }));
            // Réinitialiser l'affichage des prix
            if (typeof updatePriceDisplay === 'function') {
                updatePriceDisplay();
            }
        }
    }

    // === FEATURES ===
    const features = document.querySelectorAll('.feature-text');
    if (features.length >= 2) {
        // Grammage
        if (features[0]) {
            const strong = features[0].querySelector('strong');
            const span = features[0].querySelector('span');
            if (strong) strong.textContent = product.TISSU || 'Performance Mesh';
            if (span) span.textContent = product.GRAMMAGE || 'Ultra-respirant et léger';
        }
    }

    // === DESCRIPTION ===
    const descTab = document.querySelector('#tab-description');
    if (descTab && product.DESCRIPTION_SEO) {
        const h2 = descTab.querySelector('h2');
        if (h2) {
            h2.textContent = product.TITRE_VENDEUR;
        }

        // Ajouter la description
        const description = product.DESCRIPTION_SEO || product.DESCRIPTION || '';
        if (description) {
            const firstP = descTab.querySelector('p');
            if (firstP) {
                firstP.textContent = description.split('\n')[0];
            }
        }
    }

    // === SPECS TABLE ===
    const specsTable = document.querySelector('.specs-table');
    if (specsTable) {
        const rows = specsTable.querySelectorAll('tr');

        // Référence produit
        if (rows[0]) {
            const td = rows[0].querySelectorAll('td')[1];
            if (td) td.textContent = product.REFERENCE_FLARE || product.CODE;
        }

        // Catégorie
        if (rows[1]) {
            const td = rows[1].querySelectorAll('td')[1];
            if (td) td.textContent = `${product.FAMILLE_PRODUIT} ${product.SPORT}`.toLowerCase();
        }

        // Matière
        if (rows[2]) {
            const td = rows[2].querySelectorAll('td')[1];
            if (td) td.textContent = product.TISSU || '100% Polyester';
        }

        // Grammage
        if (rows[3]) {
            const td = rows[3].querySelectorAll('td')[1];
            if (td) td.textContent = product.GRAMMAGE || 'N/A';
        }
    }

    console.log('✅ Page produit mise à jour avec succès');
}
