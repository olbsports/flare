/**
 * Product Cards Linker
 * Ajoute automatiquement des liens cliquables aux cartes produits
 * en chargeant les références FLARE depuis le CSV
 */

(async function() {
    console.log('🔗 Product Cards Linker - Démarrage');

    // Attendre que le DOM soit complètement chargé
    if (document.readyState === 'loading') {
        await new Promise(resolve => document.addEventListener('DOMContentLoaded', resolve));
    }

    // Charger le CSV pour obtenir le mapping photo -> référence FLARE
    let csvProducts = [];
    try {
        console.log('📥 Chargement du CSV...');
        const parser = new CSVParser();
        const csvData = await parser.loadCSV('../../assets/data/PRICING-FLARE-2025.csv');
        csvProducts = csvData.products;
        console.log(`✅ CSV chargé: ${csvProducts.length} produits`);
    } catch (error) {
        console.error('❌ Erreur chargement CSV:', error);
        // Continuer sans CSV (mode dégradé)
    }

    initProductLinks(csvProducts);

    function initProductLinks(products) {
        console.log('🔗 Initialisation des liens produits...');

        const productCards = document.querySelectorAll('.product-card');
        console.log(`📦 ${productCards.length} cartes produits trouvées`);

        productCards.forEach((card, index) => {
            try {
                // Trouver la première image dans le slider
                const firstImage = card.querySelector('.product-slide img, .product-image');

                if (!firstImage || !firstImage.src) {
                    console.warn(`⚠️ Carte ${index}: Aucune image trouvée`);
                    return;
                }

                const imageUrl = firstImage.src;
                let productRef = null;

                // Méthode 1: Chercher dans le CSV par correspondance d'image
                if (products.length > 0) {
                    const matchingProduct = products.find(p => {
                        // Vérifier si l'une des 5 photos correspond
                        return [p.PHOTO_1, p.PHOTO_2, p.PHOTO_3, p.PHOTO_4, p.PHOTO_5].some(photo =>
                            photo && imageUrl.includes(photo.split('/').pop().split('-').slice(0, -1).join('-'))
                        );
                    });

                    if (matchingProduct) {
                        productRef = matchingProduct.REFERENCE_FLARE;
                        console.log(`✅ Carte ${index}: Référence trouvée dans CSV: ${productRef}`);
                    }
                }

                // Méthode 2 (fallback): Extraction depuis l'URL de l'image
                if (!productRef) {
                    // Format: https://flare-custom.com/photos/produits/FLARE-FTBMAIH-316-1.webp
                    // On veut extraire: FLARE-FTBMAIH-316 (sans le -1 qui est le numéro de photo)
                    const match = imageUrl.match(/FLARE-[A-Z]+-[0-9]+-[0-9]+\.webp/i);
                    if (match) {
                        // Extraire FLARE-XXX-YYY depuis FLARE-XXX-YYY-N.webp
                        const parts = match[0].replace('.webp', '').split('-');
                        // FLARE-FTBMAIH-316-1 -> prendre tout sauf le dernier (numéro de photo)
                        parts.pop();
                        productRef = parts.join('-');
                        console.log(`⚠️ Carte ${index}: Référence extraite (fallback): ${productRef}`);
                    }
                }

                if (!productRef) {
                    console.warn(`⚠️ Carte ${index}: Impossible de déterminer la référence depuis ${imageUrl}`);
                    return;
                }

                // Créer le lien vers la page produit
                const productPageUrl = `../produit.html?ref=${productRef}`;

                // Rendre toute la carte cliquable
                card.style.cursor = 'pointer';
                card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';

                // Ajouter effet hover
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                    this.style.boxShadow = '0 8px 24px rgba(0,0,0,0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });

                // Ajouter le lien au clic
                card.addEventListener('click', function(e) {
                    // Ne pas déclencher si on clique sur un bouton ou un input
                    if (e.target.closest('button, input, select, textarea, a')) {
                        return;
                    }

                    window.location.href = productPageUrl;
                });

            } catch (error) {
                console.error(`❌ Erreur lors du traitement de la carte ${index}:`, error);
            }
        });

        console.log('✅ Liens produits initialisés');
    }
})();
