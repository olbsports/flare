/**
 * CSV Parser pour le configurateur de devis FLARE CUSTOM
 * Parse le fichier PRICING-FLARE-2025.csv et structure les données
 */

class CSVParser {
    constructor() {
        this.products = [];
        this.sports = new Set();
        this.families = new Map(); // Map<sport, Set<famille>>
        this.genres = new Set();
    }

    /**
     * Charge et parse le fichier CSV
     * @param {string} csvPath - Chemin vers le fichier CSV
     * @returns {Promise<Object>} - Données structurées
     */
    async loadCSV(csvPath) {
        try {
            const response = await fetch(csvPath);
            const csvText = await response.text();
            return this.parseCSV(csvText);
        } catch (error) {
            console.error('Erreur lors du chargement du CSV:', error);
            throw error;
        }
    }

    /**
     * Parse le texte CSV
     * @param {string} csvText - Contenu du CSV
     * @returns {Object} - Données structurées
     */
    parseCSV(csvText) {
        const lines = csvText.split('\n');
        const headers = lines[0].split(';');

        // Parse chaque ligne de produit
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            const values = this.parseCSVLine(line);
            if (values.length < headers.length) continue;

            const product = {};
            headers.forEach((header, index) => {
                product[header.trim()] = values[index] ? values[index].trim() : '';
            });

            // Ne garder que les produits avec prix
            if (product.QTY_1 && parseFloat(product.QTY_1) > 0) {
                this.products.push(product);

                // Collecter les sports
                if (product.SPORT) {
                    this.sports.add(product.SPORT);

                    // Collecter les familles par sport
                    if (!this.families.has(product.SPORT)) {
                        this.families.set(product.SPORT, new Set());
                    }
                    if (product.FAMILLE_PRODUIT) {
                        this.families.get(product.SPORT).add(product.FAMILLE_PRODUIT);
                    }
                }

                // Collecter les genres
                if (product.GENRE) {
                    this.genres.add(product.GENRE);
                }
            }
        }

        return {
            products: this.products,
            sports: Array.from(this.sports).sort(),
            families: this.families,
            genres: Array.from(this.genres).sort()
        };
    }

    /**
     * Parse une ligne CSV en gérant les descriptions multi-lignes
     * @param {string} line - Ligne CSV
     * @returns {Array} - Valeurs parsées
     */
    parseCSVLine(line) {
        const values = [];
        let currentValue = '';
        let insideQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            const nextChar = line[i + 1];

            if (char === '"') {
                if (insideQuotes && nextChar === '"') {
                    // Double quote = échappement
                    currentValue += '"';
                    i++;
                } else {
                    insideQuotes = !insideQuotes;
                }
            } else if (char === ';' && !insideQuotes) {
                values.push(currentValue);
                currentValue = '';
            } else {
                currentValue += char;
            }
        }

        // Ajouter la dernière valeur
        values.push(currentValue);

        return values;
    }

    /**
     * Filtre les produits par sport
     * @param {string} sport - Sport à filtrer
     * @returns {Array} - Produits filtrés
     */
    getProductsBySport(sport) {
        return this.products.filter(p => p.SPORT === sport);
    }

    /**
     * Filtre les produits par sport et famille
     * @param {string} sport - Sport à filtrer
     * @param {string} famille - Famille de produit
     * @returns {Array} - Produits filtrés
     */
    getProductsBySportAndFamily(sport, famille) {
        return this.products.filter(p =>
            p.SPORT === sport && p.FAMILLE_PRODUIT === famille
        );
    }

    /**
     * Filtre les produits par sport, famille et genre
     * @param {string} sport - Sport à filtrer
     * @param {string} famille - Famille de produit
     * @param {string} genre - Genre (Homme/Femme)
     * @returns {Array} - Produits filtrés
     */
    getProductsBySportFamilyGenre(sport, famille, genre) {
        return this.products.filter(p =>
            p.SPORT === sport &&
            p.FAMILLE_PRODUIT === famille &&
            p.GENRE === genre
        );
    }

    /**
     * Récupère un produit par sa référence FLARE
     * @param {string} reference - Référence FLARE
     * @returns {Object|null} - Produit ou null
     */
    getProductByReference(reference) {
        return this.products.find(p => p.REFERENCE_FLARE === reference) || null;
    }

    /**
     * Calcule le prix pour une quantité donnée
     * @param {Object} product - Produit
     * @param {number} quantity - Quantité
     * @returns {Object} - Prix unitaire et total
     */
    calculatePrice(product, quantity) {
        let unitPrice = 0;

        // Déterminer le prix selon la quantité
        if (quantity >= 500 && product.QTY_500) {
            unitPrice = parseFloat(product.QTY_500);
        } else if (quantity >= 250 && product.QTY_250) {
            unitPrice = parseFloat(product.QTY_250);
        } else if (quantity >= 100 && product.QTY_100) {
            unitPrice = parseFloat(product.QTY_100);
        } else if (quantity >= 50 && product.QTY_50) {
            unitPrice = parseFloat(product.QTY_50);
        } else if (quantity >= 20 && product.QTY_20) {
            unitPrice = parseFloat(product.QTY_20);
        } else if (quantity >= 10 && product.QTY_10) {
            unitPrice = parseFloat(product.QTY_10);
        } else if (quantity >= 5 && product.QTY_5) {
            unitPrice = parseFloat(product.QTY_5);
        } else if (product.QTY_1) {
            unitPrice = parseFloat(product.QTY_1);
        }

        return {
            unitPrice: unitPrice,
            totalPrice: unitPrice * quantity,
            quantity: quantity
        };
    }

    /**
     * Récupère les paliers de prix pour un produit
     * @param {Object} product - Produit
     * @returns {Array} - Paliers de prix
     */
    getPriceTiers(product) {
        const tiers = [];
        const quantities = [1, 5, 10, 20, 50, 100, 250, 500];

        quantities.forEach(qty => {
            const key = `QTY_${qty}`;
            if (product[key] && parseFloat(product[key]) > 0) {
                tiers.push({
                    quantity: qty,
                    price: parseFloat(product[key]),
                    label: qty >= 500 ? `${qty}+ pièces` :
                           qty >= 100 ? `${qty}-${quantities[quantities.indexOf(qty) + 1] - 1} pièces` :
                           qty >= 10 ? `${qty}-${quantities[quantities.indexOf(qty) + 1] - 1} pièces` :
                           qty === 5 ? `5-9 pièces` :
                           `1-4 pièces`
                });
            }
        });

        return tiers;
    }

    /**
     * Récupère les familles de produits pour un sport
     * @param {string} sport - Sport
     * @returns {Array} - Familles de produits
     */
    getFamiliesBySport(sport) {
        const families = this.families.get(sport);
        return families ? Array.from(families).sort() : [];
    }

    /**
     * Récupère les genres disponibles pour un sport et une famille
     * @param {string} sport - Sport
     * @param {string} famille - Famille de produit
     * @returns {Array} - Genres disponibles
     */
    getGenresBySportAndFamily(sport, famille) {
        const products = this.getProductsBySportAndFamily(sport, famille);
        const genres = new Set();
        products.forEach(p => {
            if (p.GENRE) genres.add(p.GENRE);
        });
        return Array.from(genres).sort();
    }
}

// Export pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CSVParser;
}
