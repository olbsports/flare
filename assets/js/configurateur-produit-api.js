/**
 * CONFIGURATEUR PRODUIT - VERSION API
 * Version adaptée qui charge depuis l'API backend au lieu du CSV
 *
 * À UTILISER APRÈS L'IMPORT DES PRODUITS EN BDD
 *
 * Remplace configurateur-produit.js dans vos pages
 */

// Adapter la méthode de chargement des prix
class ConfigurateurProduitAPI extends ConfigurateurProduit {
    /**
     * Charge les données depuis l'API au lieu du CSV
     */
    static async loadPricingCSV() {
        if (ConfigurateurProduit.pricingData) {
            return ConfigurateurProduit.pricingData;
        }

        if (ConfigurateurProduit.pricingPromise) {
            return ConfigurateurProduit.pricingPromise;
        }

        ConfigurateurProduit.pricingPromise = fetch('/api/configurator-data.php?action=all-pricing')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    ConfigurateurProduit.pricingData = data.pricing;
                    return ConfigurateurProduit.pricingData;
                }
                console.error('Erreur chargement pricing depuis API');
                return {};
            })
            .catch(error => {
                console.error('Erreur chargement pricing:', error);
                return {};
            });

        return ConfigurateurProduit.pricingPromise;
    }

    /**
     * Charge les données complètes d'un produit depuis l'API
     */
    static async loadProductData(productReference) {
        return fetch(`/api/configurator-data.php?action=product&reference=${productReference}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data.data;
                }
                throw new Error('Produit non trouvé');
            });
    }

    /**
     * Calcule le prix avec options via l'API
     */
    static async calculatePrice(productId, quantity, options) {
        const params = new URLSearchParams({
            action: 'calculate',
            product_id: productId,
            quantity: quantity,
            options: JSON.stringify(options)
        });

        return fetch(`/api/configurator-data.php?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data.price;
                }
                throw new Error('Erreur calcul prix');
            });
    }
}

/**
 * Fonction d'initialisation pour les pages produits
 * Remplace l'ancienne méthode
 */
async function initConfigurateur(productReference) {
    try {
        // Charger les données du produit depuis l'API
        const productData = await ConfigurateurProduitAPI.loadProductData(productReference);

        // Créer le configurateur avec les données de l'API
        const config = new ConfigurateurProduitAPI(productData.produit);

        // Appliquer la configuration personnalisée
        if (productData.config) {
            config.taillesDisponibles = productData.config.available_sizes || config.taillesDisponibles;
            // ... autres configs
        }

        // Attacher au bouton
        const btnDevis = document.getElementById('btn-devis-gratuit');
        if (btnDevis) {
            btnDevis.addEventListener('click', () => config.open());
        }

        console.log('✅ Configurateur initialisé depuis l'API');
        return config;

    } catch (error) {
        console.error('❌ Erreur initialisation configurateur:', error);
        // Fallback sur l'ancien système si erreur
        return null;
    }
}

// Auto-initialisation si une référence produit est détectée
document.addEventListener('DOMContentLoaded', () => {
    // Essayer de détecter la référence produit depuis le schema.org
    const schemaScript = document.querySelector('script[type="application/ld+json"]');
    if (schemaScript) {
        try {
            const schema = JSON.parse(schemaScript.textContent);
            if (schema.mpn) {
                initConfigurateur(schema.mpn);
            }
        } catch (e) {
            console.warn('Impossible de détecter la référence produit automatiquement');
        }
    }
});
