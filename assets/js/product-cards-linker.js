/**
 * Product Cards Linker
 * Ajoute automatiquement des liens cliquables aux cartes produits
 * basés sur la référence FLARE trouvée dans les images
 */

(function() {
    console.log('🔗 Product Cards Linker - Démarrage');

    // Attendre que le DOM soit complètement chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductLinks);
    } else {
        initProductLinks();
    }

    function initProductLinks() {
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

                // Extraire la référence FLARE depuis l'URL de l'image
                // Format: https://flare-custom.com/photos/produits/FLARE-SPWPOLH-000-1.webp
                const imageUrl = firstImage.src;
                const match = imageUrl.match(/FLARE-[A-Z0-9]+-[0-9]+/i);

                if (!match) {
                    console.warn(`⚠️ Carte ${index}: Impossible d'extraire la référence depuis ${imageUrl}`);
                    return;
                }

                // Extraire seulement la référence sans le numéro de photo
                // FLARE-SPWPOLH-000-1 -> FLARE-SPWPOLH-000
                const fullRef = match[0];
                const productRef = fullRef.substring(0, fullRef.lastIndexOf('-'));

                console.log(`✅ Carte ${index}: Référence extraite: ${productRef}`);

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

                // Ajouter un indicateur visuel qu'on peut cliquer
                const productInfo = card.querySelector('.product-info');
                if (productInfo) {
                    const clickIndicator = document.createElement('div');
                    clickIndicator.style.cssText = `
                        font-size: 12px;
                        color: #FF4B26;
                        font-weight: 600;
                        margin-top: 12px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    `;
                    clickIndicator.textContent = '👉 Cliquez pour voir les détails';
                    productInfo.appendChild(clickIndicator);
                }

            } catch (error) {
                console.error(`❌ Erreur lors du traitement de la carte ${index}:`, error);
            }
        });

        console.log('✅ Liens produits initialisés');
    }
})();
