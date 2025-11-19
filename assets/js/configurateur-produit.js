/**
 * Configurateur de devis produit - FLARE CUSTOM
 * Widget injectable dans les pages produits
 * Version ultra-complète avec toutes les options
 */

class ConfigurateurProduit {
    // Cache statique pour les prix du CSV
    static pricingData = null;
    static pricingPromise = null;

    constructor(productData) {
        this.product = productData;
        this.currentStep = 1;
        this.totalSteps = 6;

        this.configuration = {
            produit: {
                reference: productData.reference || '',
                nom: productData.nom || '',
                sport: productData.sport || '',
                famille: productData.famille || '',
                photo: productData.photo || '',
                tissu: productData.tissu || '',
                grammage: productData.grammage || '',
                prixBase: parseFloat(productData.prixBase) || 0
            },
            design: {
                type: null, // 'flare', 'client', 'template'
                templateId: null,
                description: ''
            },
            options: {
                col: null,
                manches: null,
                poches: false,
                fermeture: null,
                extras: []
            },
            genre: null, // 'homme', 'femme', 'mixte'
            tailles: {
                // Structure: { 'S': 10, 'M': 15, 'L': 12, ... }
            },
            personnalisation: {
                couleurPrincipale: '',
                couleurSecondaire: '',
                couleurTertiaire: '',
                logos: [],
                numeros: false,
                numerosType: 'generique', // 'generique' ou 'specifique'
                numerosStyle: '',
                noms: false,
                nomsType: 'generique', // 'generique' ou 'specifique'
                nomsStyle: '',
                zones: [], // Zones de personnalisation
                remarques: ''
            },
            contact: {
                prenom: '',
                nom: '',
                email: '',
                telephone: '',
                club: '',
                fonction: '',
                accepteNewsletter: false
            }
        };

        this.taillesDisponibles = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];
        this.isOpen = false;

        // Charger les prix du CSV si pas déjà fait
        this.loadPricing();
    }

    /**
     * Charge les données de pricing depuis le CSV
     */
    static async loadPricingCSV() {
        if (ConfigurateurProduit.pricingData) {
            return ConfigurateurProduit.pricingData;
        }

        if (ConfigurateurProduit.pricingPromise) {
            return ConfigurateurProduit.pricingPromise;
        }

        ConfigurateurProduit.pricingPromise = fetch('/assets/data/PRICING-FLARE-2025.csv')
            .then(response => response.text())
            .then(csvText => {
                const lines = csvText.split('\n');
                const headers = lines[0].split(';');
                const data = {};

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;

                    const values = lines[i].split(';');
                    const reference = values[18]; // REFERENCE_FLARE est la colonne 18

                    if (reference) {
                        data[reference] = {
                            qty_1: parseFloat(values[4]) || 0,
                            qty_5: parseFloat(values[5]) || 0,
                            qty_10: parseFloat(values[6]) || 0,
                            qty_20: parseFloat(values[7]) || 0,
                            qty_50: parseFloat(values[8]) || 0,
                            qty_100: parseFloat(values[9]) || 0,
                            qty_250: parseFloat(values[10]) || 0,
                            qty_500: parseFloat(values[11]) || 0
                        };
                    }
                }

                ConfigurateurProduit.pricingData = data;
                return data;
            })
            .catch(error => {
                console.error('Erreur chargement pricing:', error);
                return {};
            });

        return ConfigurateurProduit.pricingPromise;
    }

    /**
     * Initialise le chargement du pricing
     */
    async loadPricing() {
        await ConfigurateurProduit.loadPricingCSV();
    }

    /**
     * Retourne les options disponibles selon la famille du produit
     */
    getAvailableOptions() {
        const famille = this.product.famille || '';
        const options = {
            col: [],
            manches: [],
            poches: false,
            fermeture: []
        };

        switch (famille.toLowerCase()) {
            case 'maillot':
                options.col = ['Col rond', 'Col V', 'Col polo'];
                options.manches = ['Manches courtes', 'Manches longues', 'Sans manches'];
                options.poches = false;
                break;

            case 'short':
                options.col = [];
                options.manches = [];
                options.poches = true;
                break;

            case 'pantalon':
                options.col = [];
                options.manches = [];
                options.poches = true;
                options.fermeture = ['Élastique', 'Zip', 'Boutons'];
                break;

            case 'polo':
                options.col = ['Col polo'];
                options.manches = ['Manches courtes', 'Manches longues'];
                options.poches = false;
                break;

            case 'sweat':
                options.col = ['Col rond', 'Capuche'];
                options.manches = ['Manches longues'];
                options.poches = true;
                options.fermeture = ['Zip', 'Sans zip'];
                break;

            case 'veste':
                options.col = ['Col montant', 'Capuche', 'Col classique'];
                options.manches = ['Manches longues'];
                options.poches = true;
                options.fermeture = ['Zip', 'Boutons'];
                break;

            case 't-shirt':
                options.col = ['Col rond', 'Col V'];
                options.manches = ['Manches courtes', 'Manches longues'];
                options.poches = false;
                break;

            case 'débardeur':
                options.col = ['Col rond', 'Dos nageur'];
                options.manches = [];
                options.poches = false;
                break;

            case 'cuissard':
                options.col = [];
                options.manches = [];
                options.poches = true;
                break;

            case 'combinaison':
                options.col = ['Col rond', 'Col zippé'];
                options.manches = ['Manches courtes', 'Manches longues', 'Sans manches'];
                options.poches = false;
                break;

            case 'gilet':
                options.col = ['Col montant', 'Col rond'];
                options.manches = [];
                options.poches = true;
                options.fermeture = ['Zip', 'Boutons'];
                break;

            default:
                // Options par défaut pour les produits non catégorisés
                options.col = ['Col rond', 'Col V', 'Col polo', 'Col montant'];
                options.manches = ['Manches courtes', 'Manches longues', 'Sans manches'];
                options.poches = true;
                options.fermeture = ['Zip', 'Boutons', 'Élastique'];
                break;
        }

        return options;
    }

    /**
     * Ouvre le configurateur
     */
    open() {
        if (this.isOpen) return;

        this.render();
        this.isOpen = true;
        document.body.style.overflow = 'hidden';

        // Analytics
        this.trackEvent('configurateur_ouvert', {
            produit: this.product.nom,
            reference: this.product.reference
        });
    }

    /**
     * Ferme le configurateur
     */
    close() {
        const modal = document.getElementById('configurateur-produit-modal');
        if (modal) {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.remove();
                this.isOpen = false;
                document.body.style.overflow = '';
            }, 300);
        }
    }

    /**
     * Render le configurateur
     */
    render() {
        // Créer la modale
        const modal = document.createElement('div');
        modal.id = 'configurateur-produit-modal';
        modal.className = 'config-modal';

        modal.innerHTML = `
            <div class="config-overlay" onclick="configurateurProduitInstance.close()"></div>
            <div class="config-container">
                <button class="config-close" onclick="configurateurProduitInstance.close()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>

                <div class="config-header">
                    <div class="config-product-info">
                        <img src="${this.product.photo || '/assets/images/placeholder.jpg'}" alt="${this.product.nom}" class="config-product-thumb">
                        <div>
                            <h2 class="config-product-name">${this.product.nom}</h2>
                            <p class="config-product-ref">Réf: ${this.product.reference}</p>
                        </div>
                    </div>

                    <div class="config-steps-indicator">
                        ${this.renderStepsIndicator()}
                    </div>
                </div>

                <div class="config-body">
                    <div class="config-content" id="config-content">
                        ${this.renderStep()}
                    </div>

                    <div class="config-sidebar">
                        <div class="config-summary">
                            <h3>📋 Récapitulatif</h3>
                            <div id="config-summary-content">
                                ${this.renderSummary()}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="config-footer">
                    <button class="btn-config-secondary" onclick="configurateurProduitInstance.previousStep()" ${this.currentStep === 1 ? 'style="visibility: hidden;"' : ''}>
                        ← Retour
                    </button>

                    <div class="config-step-info">
                        Étape ${this.currentStep}/${this.totalSteps}
                    </div>

                    <button class="btn-config-primary" onclick="configurateurProduitInstance.nextStep()" id="config-next-btn">
                        ${this.currentStep === this.totalSteps ? 'Envoyer le devis' : 'Continuer →'}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Animation d'entrée
        setTimeout(() => modal.classList.add('active'), 10);
    }

    /**
     * Render l'indicateur d'étapes
     */
    renderStepsIndicator() {
        const steps = [
            { num: 1, label: 'Design' },
            { num: 2, label: 'Options' },
            { num: 3, label: 'Genre' },
            { num: 4, label: 'Tailles' },
            { num: 5, label: 'Personnalisation' },
            { num: 6, label: 'Contact' }
        ];

        return steps.map(step => `
            <div class="config-step ${step.num === this.currentStep ? 'active' : ''} ${step.num < this.currentStep ? 'completed' : ''}">
                <div class="config-step-number">${step.num}</div>
                <div class="config-step-label">${step.label}</div>
            </div>
        `).join('');
    }

    /**
     * Render l'étape courante
     */
    renderStep() {
        switch (this.currentStep) {
            case 1: return this.renderStep1Design();
            case 2: return this.renderStep2Options();
            case 3: return this.renderStep3Genre();
            case 4: return this.renderStep4Tailles();
            case 5: return this.renderStep5Personnalisation();
            case 6: return this.renderStep6Contact();
            default: return '';
        }
    }

    /**
     * ÉTAPE 1 : Choix du type de design
     */
    renderStep1Design() {
        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Comment souhaitez-vous créer votre design ?</h3>
                <p class="config-step-description">Choisissez la méthode qui vous convient le mieux pour personnaliser vos équipements</p>

                <div class="config-design-options">
                    <div class="config-design-card ${this.configuration.design.type === 'flare' ? 'selected' : ''}" onclick="configurateurProduitInstance.selectDesignType('flare')">
                        <div class="config-design-icon">🎨</div>
                        <h4>Design par FLARE</h4>
                        <p>Notre équipe de designers crée votre design sur-mesure selon vos indications</p>
                        <div class="config-design-badge">Recommandé</div>
                    </div>

                    <div class="config-design-card ${this.configuration.design.type === 'client' ? 'selected' : ''}" onclick="configurateurProduitInstance.selectDesignType('client')">
                        <div class="config-design-icon">📁</div>
                        <h4>Mes fichiers</h4>
                        <p>Vous fournissez vos fichiers de design prêts à l'impression (AI, PDF, PNG haute résolution)</p>
                    </div>

                    <div class="config-design-card ${this.configuration.design.type === 'template' ? 'selected' : ''}" onclick="configurateurProduitInstance.selectDesignType('template')">
                        <div class="config-design-icon">📐</div>
                        <h4>Template</h4>
                        <p>Choisissez parmi nos templates prédéfinis et personnalisez les couleurs et logos</p>
                    </div>
                </div>

                ${this.configuration.design.type === 'template' ? this.renderTemplateSelector() : ''}

                ${this.configuration.design.type ? `
                    <div class="config-form-group" style="margin-top: 2rem;">
                        <label class="config-label">Décrivez vos besoins</label>
                        <textarea
                            class="config-textarea"
                            placeholder="Décrivez votre projet, vos couleurs souhaitées, vos inspirations..."
                            rows="4"
                            onchange="configurateurProduitInstance.configuration.design.description = this.value"
                        >${this.configuration.design.description}</textarea>
                    </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Render le sélecteur de templates
     */
    renderTemplateSelector() {
        // Liste des templates SVG disponibles dans /assets/templates/
        const templates = [
            { id: 'classic', name: 'Classic', preview: '../../assets/templates/classic.svg' },
            { id: 'modern', name: 'Modern', preview: '../../assets/templates/modern.svg' },
            { id: 'sport', name: 'Sport', preview: '../../assets/templates/sport.svg' },
            { id: 'elegant', name: 'Élégant', preview: '../../assets/templates/elegant.svg' },
            { id: 'geometric', name: 'Géométrique', preview: '../../assets/templates/geometric.svg' },
            { id: 'minimal', name: 'Minimal', preview: '../../assets/templates/minimal.svg' }
        ];

        return `
            <div class="config-templates" style="margin-top: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 16px; font-weight: 600;">Choisissez un template :</h4>
                <div class="config-templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px;">
                    ${templates.map(tpl => `
                        <div class="config-template-card ${this.configuration.design.templateId === tpl.id ? 'selected' : ''}"
                             onclick="configurateurProduitInstance.selectTemplate('${tpl.id}')"
                             style="cursor: pointer; border: 2px solid ${this.configuration.design.templateId === tpl.id ? '#FF4B26' : '#e0e0e0'}; border-radius: 12px; padding: 12px; transition: all 0.3s ease; background: ${this.configuration.design.templateId === tpl.id ? '#fff5f3' : '#fff'};">
                            <div style="aspect-ratio: 3/4; background: #f8f9fa; border-radius: 8px; overflow: hidden; margin-bottom: 8px;">
                                <img src="${tpl.preview}" alt="${tpl.name}"
                                     style="width: 100%; height: 100%; object-fit: contain;"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:12px;\'>Template ${tpl.name}</div>'">
                            </div>
                            <span style="display: block; text-align: center; font-size: 14px; font-weight: ${this.configuration.design.templateId === tpl.id ? '700' : '500'}; color: ${this.configuration.design.templateId === tpl.id ? '#FF4B26' : '#333'};">${tpl.name}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * ÉTAPE 2 : Options produit
     */
    renderStep2Options() {
        const availableOptions = this.getAvailableOptions();

        let optionsHTML = '';

        // Type de col (si disponible)
        if (availableOptions.col && availableOptions.col.length > 0) {
            optionsHTML += `
                <div class="config-option-group">
                    <label class="config-label">Type de col</label>
                    <div class="config-option-buttons">
                        ${availableOptions.col.map(col => `
                            <button class="config-option-btn ${this.configuration.options.col === col ? 'selected' : ''}"
                                    onclick="configurateurProduitInstance.selectOption('col', '${col}')">
                                ${col}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Type de manches (si disponible)
        if (availableOptions.manches && availableOptions.manches.length > 0) {
            optionsHTML += `
                <div class="config-option-group">
                    <label class="config-label">Type de manches</label>
                    <div class="config-option-buttons">
                        ${availableOptions.manches.map(manche => `
                            <button class="config-option-btn ${this.configuration.options.manches === manche ? 'selected' : ''}"
                                    onclick="configurateurProduitInstance.selectOption('manches', '${manche}')">
                                ${manche}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Poches (si disponible)
        if (availableOptions.poches) {
            optionsHTML += `
                <div class="config-option-group">
                    <label class="config-label">Poches</label>
                    <div class="config-option-buttons">
                        <button class="config-option-btn ${this.configuration.options.poches === true ? 'selected' : ''}"
                                onclick="configurateurProduitInstance.selectOption('poches', true)">
                            Avec poches
                        </button>
                        <button class="config-option-btn ${this.configuration.options.poches === false ? 'selected' : ''}"
                                onclick="configurateurProduitInstance.selectOption('poches', false)">
                            Sans poches
                        </button>
                    </div>
                </div>
            `;
        }

        // Fermeture (si disponible)
        if (availableOptions.fermeture && availableOptions.fermeture.length > 0) {
            optionsHTML += `
                <div class="config-option-group">
                    <label class="config-label">Type de fermeture</label>
                    <div class="config-option-buttons">
                        ${availableOptions.fermeture.map(fermeture => `
                            <button class="config-option-btn ${this.configuration.options.fermeture === fermeture ? 'selected' : ''}"
                                    onclick="configurateurProduitInstance.selectOption('fermeture', '${fermeture}')">
                                ${fermeture}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Si aucune option n'est disponible
        if (!optionsHTML) {
            optionsHTML = `
                <div class="config-info-box">
                    <p>✓ Ce produit ne nécessite pas de configuration d'options spécifiques.</p>
                    <p>Passez à l'étape suivante pour continuer votre configuration.</p>
                </div>
            `;
        }

        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Options du produit</h3>
                <p class="config-step-description">Personnalisez les caractéristiques techniques de votre ${this.product.famille}</p>

                <div class="config-options-grid">
                    ${optionsHTML}
                </div>
            </div>
        `;
    }

    /**
     * ÉTAPE 3 : Genre
     */
    renderStep3Genre() {
        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Pour quel genre ?</h3>
                <p class="config-step-description">Sélectionnez le genre pour adapter les coupes et tailles</p>

                <div class="config-genre-options">
                    <div class="config-genre-card ${this.configuration.genre === 'homme' ? 'selected' : ''}"
                         onclick="configurateurProduitInstance.selectGenre('homme')">
                        <div class="config-genre-icon">👔</div>
                        <h4>Homme</h4>
                        <p>Coupe masculine</p>
                    </div>

                    <div class="config-genre-card ${this.configuration.genre === 'femme' ? 'selected' : ''}"
                         onclick="configurateurProduitInstance.selectGenre('femme')">
                        <div class="config-genre-icon">👗</div>
                        <h4>Femme</h4>
                        <p>Coupe féminine</p>
                    </div>

                    <div class="config-genre-card ${this.configuration.genre === 'mixte' ? 'selected' : ''}"
                         onclick="configurateurProduitInstance.selectGenre('mixte')">
                        <div class="config-genre-icon">👕</div>
                        <h4>Mixte</h4>
                        <p>Coupe unisexe</p>
                    </div>

                    <div class="config-genre-card ${this.configuration.genre === 'enfants' ? 'selected' : ''}"
                         onclick="configurateurProduitInstance.selectGenre('enfants')">
                        <div class="config-genre-icon">🧒</div>
                        <h4>Enfants</h4>
                        <p>Coupe enfant</p>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ÉTAPE 4 : Tailles et quantités
     */
    renderStep4Tailles() {
        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Tailles et quantités</h3>
                <p class="config-step-description">Indiquez le nombre de pièces par taille</p>

                <div class="config-tailles-grid">
                    ${this.taillesDisponibles.map(taille => `
                        <div class="config-taille-item">
                            <label class="config-taille-label">${taille}</label>
                            <input
                                type="number"
                                class="config-taille-input"
                                min="0"
                                value="${this.configuration.tailles[taille] || 0}"
                                onchange="configurateurProduitInstance.updateTaille('${taille}', parseInt(this.value) || 0)"
                                placeholder="0"
                            >
                        </div>
                    `).join('')}
                </div>

                <div class="config-tailles-total">
                    <div class="config-total-label">Total de pièces :</div>
                    <div class="config-total-value">${this.getTotalQuantite()} pièces</div>
                </div>

                <div class="config-tailles-presets">
                    <p style="margin-bottom: 0.5rem; font-weight: 600; color: #666;">Ou utilisez un preset :</p>
                    <div class="config-preset-buttons">
                        <button class="btn-config-preset" onclick="configurateurProduitInstance.applyPreset('equipe15')">
                            Équipe 15 joueurs
                        </button>
                        <button class="btn-config-preset" onclick="configurateurProduitInstance.applyPreset('club25')">
                            Club 25 personnes
                        </button>
                        <button class="btn-config-preset" onclick="configurateurProduitInstance.applyPreset('evenement50')">
                            Événement 50 personnes
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ÉTAPE 5 : Personnalisation
     */
    renderStep5Personnalisation() {
        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Personnalisation</h3>
                <p class="config-step-description">Détaillez vos besoins de personnalisation</p>

                <div class="config-perso-sections">
                    <!-- Couleurs -->
                    <div class="config-perso-section">
                        <h4 class="config-perso-subtitle">🎨 Couleurs</h4>
                        <div class="config-couleurs-grid">
                            <div class="config-form-group">
                                <label class="config-label">Couleur principale</label>
                                <input type="color" class="config-color-input" value="${this.configuration.personnalisation.couleurPrincipale || '#FF4B26'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurPrincipale = this.value; configurateurProduitInstance.updateSummary()">
                                <input type="text" class="config-text-input" value="${this.configuration.personnalisation.couleurPrincipale || '#FF4B26'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurPrincipale = this.value; configurateurProduitInstance.updateSummary()"
                                       placeholder="#FF4B26">
                            </div>
                            <div class="config-form-group">
                                <label class="config-label">Couleur secondaire</label>
                                <input type="color" class="config-color-input" value="${this.configuration.personnalisation.couleurSecondaire || '#1a1a1a'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurSecondaire = this.value; configurateurProduitInstance.updateSummary()">
                                <input type="text" class="config-text-input" value="${this.configuration.personnalisation.couleurSecondaire || '#1a1a1a'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurSecondaire = this.value; configurateurProduitInstance.updateSummary()"
                                       placeholder="#1a1a1a">
                            </div>
                            <div class="config-form-group">
                                <label class="config-label">Couleur tertiaire (optionnel)</label>
                                <input type="color" class="config-color-input" value="${this.configuration.personnalisation.couleurTertiaire || '#ffffff'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurTertiaire = this.value; configurateurProduitInstance.updateSummary()">
                                <input type="text" class="config-text-input" value="${this.configuration.personnalisation.couleurTertiaire || '#ffffff'}"
                                       onchange="configurateurProduitInstance.configuration.personnalisation.couleurTertiaire = this.value; configurateurProduitInstance.updateSummary()"
                                       placeholder="#ffffff">
                            </div>
                        </div>
                    </div>

                    <!-- Logos -->
                    <div class="config-perso-section">
                        <h4 class="config-perso-subtitle">📌 Logos et visuels</h4>
                        <div class="config-form-group">
                            <label class="config-label">Décrivez vos logos</label>
                            <textarea
                                class="config-textarea"
                                rows="3"
                                placeholder="Ex: Logo du club sur le cœur, sponsors sur le dos, etc."
                                onchange="configurateurProduitInstance.configuration.personnalisation.logos.push(this.value); configurateurProduitInstance.updateSummary()"
                            ></textarea>
                            <p class="config-hint">Vous pourrez nous envoyer vos fichiers logos après validation du devis</p>
                        </div>
                    </div>

                    <!-- Numéros et noms -->
                    <div class="config-perso-section">
                        <h4 class="config-perso-subtitle">🔢 Numéros et noms</h4>

                        <!-- Numéros -->
                        <div class="config-checkbox-group">
                            <label class="config-checkbox">
                                <input type="checkbox" ${this.configuration.personnalisation.numeros ? 'checked' : ''}
                                       onchange="configurateurProduitInstance.toggleNumeros(this.checked)">
                                <span>Numéros sur les équipements</span>
                            </label>

                            ${this.configuration.personnalisation.numeros ? `
                                <div class="config-form-group" style="margin-left: 2rem; margin-top: 0.5rem;">
                                    <label class="config-label">Type de numérotation</label>
                                    <div class="config-option-buttons" style="gap: 8px; margin-bottom: 1rem;">
                                        <button class="config-option-btn ${this.configuration.personnalisation.numerosType === 'generique' ? 'selected' : ''}"
                                                onclick="configurateurProduitInstance.setNumerosType('generique')">
                                            Générique (1-20) - Gratuit
                                        </button>
                                        <button class="config-option-btn ${this.configuration.personnalisation.numerosType === 'specifique' ? 'selected' : ''}"
                                                onclick="configurateurProduitInstance.setNumerosType('specifique')">
                                            Spécifique - +2€/pièce
                                        </button>
                                    </div>
                                    <input type="text" class="config-text-input"
                                           placeholder="Ex: Numéros 1-20, style moderne, couleur blanche, taille 25cm"
                                           value="${this.configuration.personnalisation.numerosStyle}"
                                           onchange="configurateurProduitInstance.configuration.personnalisation.numerosStyle = this.value">
                                </div>
                            ` : ''}
                        </div>

                        <!-- Noms -->
                        <div class="config-checkbox-group">
                            <label class="config-checkbox">
                                <input type="checkbox" ${this.configuration.personnalisation.noms ? 'checked' : ''}
                                       onchange="configurateurProduitInstance.toggleNoms(this.checked)">
                                <span>Noms des joueurs</span>
                            </label>

                            ${this.configuration.personnalisation.noms ? `
                                <div class="config-form-group" style="margin-left: 2rem; margin-top: 0.5rem;">
                                    <label class="config-label">Type de noms</label>
                                    <div class="config-option-buttons" style="gap: 8px; margin-bottom: 1rem;">
                                        <button class="config-option-btn ${this.configuration.personnalisation.nomsType === 'generique' ? 'selected' : ''}"
                                                onclick="configurateurProduitInstance.setNomsType('generique')">
                                            Générique (JOUEUR 1-20) - Gratuit
                                        </button>
                                        <button class="config-option-btn ${this.configuration.personnalisation.nomsType === 'specifique' ? 'selected' : ''}"
                                                onclick="configurateurProduitInstance.setNomsType('specifique')">
                                            Spécifique - +2€/pièce
                                        </button>
                                    </div>
                                    <input type="text" class="config-text-input"
                                           placeholder="Ex: Noms en majuscules sur le dos, police moderne, taille 8cm"
                                           value="${this.configuration.personnalisation.nomsStyle}"
                                           onchange="configurateurProduitInstance.configuration.personnalisation.nomsStyle = this.value">
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Remarques -->
                    <div class="config-perso-section">
                        <h4 class="config-perso-subtitle">💬 Remarques supplémentaires</h4>
                        <div class="config-form-group">
                            <textarea
                                class="config-textarea"
                                rows="4"
                                placeholder="Toute information complémentaire sur votre projet..."
                                onchange="configurateurProduitInstance.configuration.personnalisation.remarques = this.value"
                            >${this.configuration.personnalisation.remarques}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ÉTAPE 6 : Contact et validation
     */
    renderStep6Contact() {
        const totalPieces = this.getTotalQuantite();
        const prixEstime = this.calculatePrice();

        return `
            <div class="config-step-content">
                <h3 class="config-step-title">Vos coordonnées</h3>
                <p class="config-step-description">Pour recevoir votre devis personnalisé</p>

                <div class="config-contact-form">
                    <div class="config-form-row">
                        <div class="config-form-group">
                            <label class="config-label">Prénom *</label>
                            <input type="text" class="config-text-input" required
                                   value="${this.configuration.contact.prenom}"
                                   onchange="configurateurProduitInstance.configuration.contact.prenom = this.value">
                        </div>
                        <div class="config-form-group">
                            <label class="config-label">Nom *</label>
                            <input type="text" class="config-text-input" required
                                   value="${this.configuration.contact.nom}"
                                   onchange="configurateurProduitInstance.configuration.contact.nom = this.value">
                        </div>
                    </div>

                    <div class="config-form-row">
                        <div class="config-form-group">
                            <label class="config-label">Email *</label>
                            <input type="email" class="config-text-input" required
                                   value="${this.configuration.contact.email}"
                                   onchange="configurateurProduitInstance.configuration.contact.email = this.value">
                        </div>
                        <div class="config-form-group">
                            <label class="config-label">Téléphone *</label>
                            <input type="tel" class="config-text-input" required
                                   value="${this.configuration.contact.telephone}"
                                   onchange="configurateurProduitInstance.configuration.contact.telephone = this.value">
                        </div>
                    </div>

                    <div class="config-form-row">
                        <div class="config-form-group">
                            <label class="config-label">Club / Entreprise</label>
                            <input type="text" class="config-text-input"
                                   value="${this.configuration.contact.club}"
                                   onchange="configurateurProduitInstance.configuration.contact.club = this.value">
                        </div>
                        <div class="config-form-group">
                            <label class="config-label">Fonction</label>
                            <input type="text" class="config-text-input"
                                   value="${this.configuration.contact.fonction}"
                                   onchange="configurateurProduitInstance.configuration.contact.fonction = this.value">
                        </div>
                    </div>

                    <div class="config-checkbox-group">
                        <label class="config-checkbox">
                            <input type="checkbox" ${this.configuration.contact.accepteNewsletter ? 'checked' : ''}
                                   onchange="configurateurProduitInstance.configuration.contact.accepteNewsletter = this.checked">
                            <span>J'accepte de recevoir les actualités et offres FLARE CUSTOM</span>
                        </label>
                    </div>
                </div>

                <div class="config-final-summary">
                    <h4 style="margin-bottom: 1.5rem; color: #1a1a1a;">Récapitulatif final</h4>

                    <div class="config-final-item">
                        <span>Produit</span>
                        <strong>${this.product.nom}</strong>
                    </div>
                    <div class="config-final-item">
                        <span>Quantité totale</span>
                        <strong>${totalPieces} pièces</strong>
                    </div>
                    <div class="config-final-item">
                        <span>Type de design</span>
                        <strong>${this.getDesignTypeLabel()}</strong>
                    </div>
                    ${this.configuration.genre ? `
                        <div class="config-final-item">
                            <span>Genre</span>
                            <strong>${this.configuration.genre.charAt(0).toUpperCase() + this.configuration.genre.slice(1)}</strong>
                        </div>
                    ` : ''}

                    <div class="config-final-total">
                        <span>Prix estimé HT</span>
                        <strong>${prixEstime.toFixed(2)} €</strong>
                    </div>

                    <p class="config-final-note">
                        Ce prix est une estimation. Un devis détaillé et personnalisé vous sera envoyé par email sous 24h.
                    </p>
                </div>
            </div>
        `;
    }

    /**
     * Render le récapitulatif (sidebar)
     */
    renderSummary() {
        const totalPieces = this.getTotalQuantite();
        const prixEstime = this.calculatePrice();

        let html = '<div class="config-summary-items">';

        // Design
        if (this.configuration.design.type) {
            html += `
                <div class="config-summary-item">
                    <span class="config-summary-label">Design</span>
                    <span class="config-summary-value">${this.getDesignTypeLabel()}</span>
                </div>
            `;
        }

        // Genre
        if (this.configuration.genre) {
            html += `
                <div class="config-summary-item">
                    <span class="config-summary-label">Genre</span>
                    <span class="config-summary-value">${this.configuration.genre.charAt(0).toUpperCase() + this.configuration.genre.slice(1)}</span>
                </div>
            `;
        }

        // Tailles
        if (totalPieces > 0) {
            html += `
                <div class="config-summary-item">
                    <span class="config-summary-label">Quantité</span>
                    <span class="config-summary-value">${totalPieces} pièces</span>
                </div>
            `;

            const taillesDetails = Object.entries(this.configuration.tailles)
                .filter(([, qty]) => qty > 0)
                .map(([size, qty]) => `${size}: ${qty}`)
                .join(', ');

            if (taillesDetails) {
                html += `
                    <div class="config-summary-detail">
                        ${taillesDetails}
                    </div>
                `;
            }
        }

        // Prix
        if (totalPieces > 0) {
            html += `
                <div class="config-summary-total">
                    <span>Prix estimé HT</span>
                    <strong>${prixEstime.toFixed(2)} €</strong>
                </div>
                <div class="config-summary-unit">
                    ${(prixEstime / totalPieces).toFixed(2)} € / pièce
                </div>
            `;
        }

        html += '</div>';

        if (totalPieces === 0) {
            html = '<p class="config-summary-empty">Configurez votre produit pour voir le résumé</p>';
        }

        return html;
    }

    /**
     * Met à jour le récapitulatif
     */
    updateSummary() {
        const summaryContent = document.getElementById('config-summary-content');
        if (summaryContent) {
            summaryContent.innerHTML = this.renderSummary();
        }
    }

    /**
     * Calcule le prix estimé
     */
    calculatePrice() {
        const totalPieces = this.getTotalQuantite();
        if (totalPieces === 0) return 0;

        // Récupérer les prix depuis le CSV
        const pricing = ConfigurateurProduit.pricingData;
        let prixUnitaire = this.product.prixBase; // Fallback

        // Si on a les données du CSV, utiliser les vrais prix
        if (pricing && pricing[this.product.reference]) {
            const productPricing = pricing[this.product.reference];

            // Déterminer le prix unitaire selon la quantité
            if (totalPieces >= 500) {
                prixUnitaire = productPricing.qty_500;
            } else if (totalPieces >= 250) {
                prixUnitaire = productPricing.qty_250;
            } else if (totalPieces >= 100) {
                prixUnitaire = productPricing.qty_100;
            } else if (totalPieces >= 50) {
                prixUnitaire = productPricing.qty_50;
            } else if (totalPieces >= 20) {
                prixUnitaire = productPricing.qty_20;
            } else if (totalPieces >= 10) {
                prixUnitaire = productPricing.qty_10;
            } else if (totalPieces >= 5) {
                prixUnitaire = productPricing.qty_5;
            } else {
                prixUnitaire = productPricing.qty_1;
            }
        }

        // Prix de base pour toutes les pièces
        let prixTotal = prixUnitaire * totalPieces;

        // +2€/pièce pour les numéros SEULEMENT si spécifique
        if (this.configuration.personnalisation.numeros && this.configuration.personnalisation.numerosType === 'specifique') {
            prixTotal += totalPieces * 2;
        }

        // +2€/pièce pour les noms SEULEMENT si spécifique
        if (this.configuration.personnalisation.noms && this.configuration.personnalisation.nomsType === 'specifique') {
            prixTotal += totalPieces * 2;
        }

        // Forfait design FLARE : +50€
        if (this.configuration.design.type === 'flare') {
            prixTotal += 50;
        }

        return prixTotal;
    }

    /**
     * Active/désactive les numéros
     */
    toggleNumeros(checked) {
        this.configuration.personnalisation.numeros = checked;
        if (checked && !this.configuration.personnalisation.numerosType) {
            this.configuration.personnalisation.numerosType = 'generique';
        }
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Active/désactive les noms
     */
    toggleNoms(checked) {
        this.configuration.personnalisation.noms = checked;
        if (checked && !this.configuration.personnalisation.nomsType) {
            this.configuration.personnalisation.nomsType = 'generique';
        }
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Définit le type de numérotation
     */
    setNumerosType(type) {
        this.configuration.personnalisation.numerosType = type;
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Définit le type de noms
     */
    setNomsType(type) {
        this.configuration.personnalisation.nomsType = type;
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Sélectionne un type de design
     */
    selectDesignType(type) {
        this.configuration.design.type = type;
        this.updateStep();
        this.updateSummary();
        this.trackEvent('design_type_selected', { type });

        // Auto-scroll vers le contenu qui vient d'apparaître
        setTimeout(() => {
            const contentArea = document.querySelector('.config-template-selector, .config-form-group');
            if (contentArea) {
                contentArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }, 100);
    }

    /**
     * Sélectionne un template
     */
    selectTemplate(templateId) {
        this.configuration.design.templateId = templateId;
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Sélectionne une option
     */
    selectOption(optionType, value) {
        this.configuration.options[optionType] = value;
        this.updateStep();
        this.updateSummary();
    }

    /**
     * Sélectionne un genre
     */
    selectGenre(genre) {
        this.configuration.genre = genre;
        this.updateStep();
        this.updateSummary();
        this.trackEvent('genre_selected', { genre });
    }

    /**
     * Met à jour une taille
     */
    updateTaille(taille, quantite) {
        if (quantite > 0) {
            this.configuration.tailles[taille] = quantite;
        } else {
            delete this.configuration.tailles[taille];
        }
        this.updateSummary();

        // Update total display
        const totalEl = document.querySelector('.config-total-value');
        if (totalEl) {
            totalEl.textContent = `${this.getTotalQuantite()} pièces`;
        }
    }

    /**
     * Applique un preset de tailles
     */
    applyPreset(presetType) {
        const presets = {
            'equipe15': { 'S': 2, 'M': 5, 'L': 5, 'XL': 3 },
            'club25': { 'XS': 2, 'S': 4, 'M': 8, 'L': 7, 'XL': 3, 'XXL': 1 },
            'evenement50': { 'S': 8, 'M': 15, 'L': 15, 'XL': 8, 'XXL': 4 }
        };

        const preset = presets[presetType];
        if (preset) {
            this.configuration.tailles = { ...preset };
            this.updateStep();
            this.updateSummary();
            this.trackEvent('preset_applied', { presetType });
        }
    }

    /**
     * Obtient le total de quantités
     */
    getTotalQuantite() {
        return Object.values(this.configuration.tailles).reduce((sum, qty) => sum + qty, 0);
    }

    /**
     * Obtient le label du type de design
     */
    getDesignTypeLabel() {
        const labels = {
            'flare': 'Design par FLARE',
            'client': 'Fichiers client',
            'template': 'Template'
        };
        return labels[this.configuration.design.type] || 'Non défini';
    }

    /**
     * Étape suivante
     */
    nextStep() {
        // Validation de l'étape courante
        if (!this.validateCurrentStep()) {
            return;
        }

        if (this.currentStep === this.totalSteps) {
            // Dernière étape = envoi
            this.submitDevis();
        } else {
            this.currentStep++;
            this.updateStep();
            this.trackEvent('step_completed', { step: this.currentStep - 1 });
        }
    }

    /**
     * Étape précédente
     */
    previousStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStep();
        }
    }

    /**
     * Valide l'étape courante
     */
    validateCurrentStep() {
        switch (this.currentStep) {
            case 1:
                if (!this.configuration.design.type) {
                    alert('Veuillez sélectionner un type de design');
                    return false;
                }
                if (this.configuration.design.type === 'template' && !this.configuration.design.templateId) {
                    alert('Veuillez sélectionner un template');
                    return false;
                }
                break;

            case 2:
                // Validation dynamique selon les options disponibles
                const availableOptions = this.getAvailableOptions();

                // Vérifier col seulement si disponible
                if (availableOptions.col && availableOptions.col.length > 0 && !this.configuration.options.col) {
                    alert('Veuillez sélectionner un type de col');
                    return false;
                }

                // Vérifier manches seulement si disponible
                if (availableOptions.manches && availableOptions.manches.length > 0 && !this.configuration.options.manches) {
                    alert('Veuillez sélectionner un type de manches');
                    return false;
                }

                // Vérifier fermeture seulement si disponible
                if (availableOptions.fermeture && availableOptions.fermeture.length > 0 && !this.configuration.options.fermeture) {
                    alert('Veuillez sélectionner un type de fermeture');
                    return false;
                }
                break;

            case 3:
                if (!this.configuration.genre) {
                    alert('Veuillez sélectionner un genre');
                    return false;
                }
                break;

            case 4:
                if (this.getTotalQuantite() === 0) {
                    alert('Veuillez saisir au moins une quantité');
                    return false;
                }
                break;

            case 6:
                const { prenom, nom, email, telephone } = this.configuration.contact;
                if (!prenom || !nom || !email || !telephone) {
                    alert('Veuillez remplir tous les champs obligatoires');
                    return false;
                }
                if (!this.validateEmail(email)) {
                    alert('Veuillez saisir un email valide');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Valide un email
     */
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Met à jour l'affichage de l'étape
     */
    updateStep() {
        const content = document.getElementById('config-content');
        if (content) {
            content.innerHTML = this.renderStep();
        }

        // Update steps indicator
        const modal = document.getElementById('configurateur-produit-modal');
        if (modal) {
            const stepsIndicator = modal.querySelector('.config-steps-indicator');
            if (stepsIndicator) {
                stepsIndicator.innerHTML = this.renderStepsIndicator();
            }

            // Update buttons
            const prevBtn = modal.querySelector('.btn-config-secondary');
            const nextBtn = modal.querySelector('.btn-config-primary');
            const stepInfo = modal.querySelector('.config-step-info');

            if (prevBtn) {
                prevBtn.style.visibility = this.currentStep === 1 ? 'hidden' : 'visible';
            }

            if (nextBtn) {
                nextBtn.textContent = this.currentStep === this.totalSteps ? 'Envoyer le devis' : 'Continuer →';
            }

            if (stepInfo) {
                stepInfo.textContent = `Étape ${this.currentStep}/${this.totalSteps}`;
            }
        }

        // Scroll to top
        if (content) {
            content.scrollTop = 0;
        }
    }

    /**
     * Soumet le devis
     */
    async submitDevis() {
        const nextBtn = document.getElementById('config-next-btn');
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.textContent = 'Envoi en cours...';
        }

        try {
            const formData = new FormData();
            formData.append('configuration', JSON.stringify(this.configuration));

            const response = await fetch('/api/send-quote-product.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess();
                this.trackEvent('devis_submitted', {
                    produit: this.product.nom,
                    quantite: this.getTotalQuantite(),
                    prix: this.calculatePrice()
                });
            } else {
                throw new Error(result.error || 'Erreur lors de l\'envoi');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue. Veuillez réessayer ou nous contacter au +359885813134');

            if (nextBtn) {
                nextBtn.disabled = false;
                nextBtn.textContent = 'Envoyer le devis';
            }
        }
    }

    /**
     * Affiche le message de succès
     */
    showSuccess() {
        const content = document.getElementById('config-content');
        if (content) {
            content.innerHTML = `
                <div class="config-success">
                    <div class="config-success-icon">✅</div>
                    <h3>Demande envoyée avec succès !</h3>
                    <p>Votre demande de devis a été transmise à notre équipe.</p>
                    <p>Vous allez recevoir un email de confirmation à l'adresse : <strong>${this.configuration.contact.email}</strong></p>
                    <p>Notre équipe vous recontactera sous 24h avec un devis détaillé et personnalisé.</p>
                    <div class="config-success-actions">
                        <button class="btn-config-primary" onclick="configurateurProduitInstance.close()">
                            Fermer
                        </button>
                    </div>
                </div>
            `;
        }

        // Hide footer
        const footer = document.querySelector('.config-footer');
        if (footer) {
            footer.style.display = 'none';
        }
    }

    /**
     * Track analytics event
     */
    trackEvent(eventName, data = {}) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                ...data,
                produit_reference: this.product.reference,
                produit_nom: this.product.nom
            });
        }
        console.log('Event:', eventName, data);
    }
}

// Instance globale
let configurateurProduitInstance = null;

/**
 * Fonction d'initialisation pour injection dans les pages produits
 */
function initConfigurateurProduit(productData) {
    configurateurProduitInstance = new ConfigurateurProduit(productData);
    configurateurProduitInstance.open();
}
