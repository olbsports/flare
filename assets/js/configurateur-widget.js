/**
 * Widget de configurateur flottant - FLARE CUSTOM
 * Version compacte et optimisée
 */

class FlareConfigurateurWidget {
    constructor() {
        this.csvParser = null;
        this.data = null;
        this.config = {
            sport: null,
            famille: null,
            produit: null,
            genre: null,
            quantite: null,
            prix: null,
            perso: {},
            contact: {}
        };
        this.currentStep = 'welcome';
        this.isOpen = false;
        this.container = null;
        this.messagesContainer = null;
        this.dataLoaded = false;
        this.dataLoading = false;
        this.loadPromise = null; // Promise pour attendre le chargement
    }

    /**
     * Initialise le widget
     */
    async init() {
        console.log('🚀 Initialisation du widget...');
        this.createWidget();
        this.attachEvents();

        // Charger le CSV
        this.loadData();
    }

    /**
     * Charge les données CSV
     */
    async loadData() {
        // Si déjà chargé, retourner immédiatement
        if (this.dataLoaded) {
            console.log('✅ Données déjà chargées');
            return;
        }

        // Si en cours de chargement, attendre la promesse existante
        if (this.loadPromise) {
            console.log('⏳ Chargement déjà en cours, attente...');
            return this.loadPromise;
        }

        // Démarrer le chargement
        this.dataLoading = true;
        console.log('📊 Démarrage du chargement des données...');

        this.loadPromise = (async () => {
            try {
                this.csvParser = new CSVParser();
                this.data = await this.csvParser.loadCSV('/assets/data/PRICING-FLARE-2025.csv');
                this.dataLoaded = true;
                console.log('✅ Données chargées:', this.data);
                console.log('✅ Nombre de produits:', this.data.products ? this.data.products.length : 0);
                console.log('✅ Sports disponibles:', this.data.sports);
                console.log('✅ Type de sports:', typeof this.data.sports, Array.isArray(this.data.sports));
            } catch (error) {
                console.error('❌ Erreur chargement CSV:', error);
                console.error('❌ Stack:', error.stack);
                this.dataLoaded = false;
                throw error; // Propager l'erreur
            } finally {
                this.dataLoading = false;
                this.loadPromise = null; // Réinitialiser la promesse
            }
        })();

        return this.loadPromise;
    }

    /**
     * Crée le HTML du widget
     */
    createWidget() {
        const widget = document.createElement('div');
        widget.id = 'flare-configurateur-widget';
        widget.innerHTML = `
            <!-- Bulle de chat -->
            <div class="flare-chat-bubble">
                <div class="flare-chat-bubble-icon">💬</div>
                <div class="flare-chat-bubble-badge" style="display: none;">1</div>
            </div>

            <!-- Fenêtre de chat -->
            <div class="flare-chat-window">
                <!-- En-tête -->
                <div class="flare-chat-header">
                    <div class="flare-chat-header-content">
                        <h3>🎯 Devis Express</h3>
                        <p>Configurez en 2 minutes</p>
                    </div>
                    <button class="flare-chat-close">✕</button>
                </div>

                <!-- Messages -->
                <div class="flare-chat-messages" id="flare-messages"></div>

                <!-- Footer -->
                <div class="flare-chat-footer">
                    Propulsé par <a href="https://flare-custom.com" target="_blank">FLARE CUSTOM</a>
                </div>
            </div>
        `;

        document.body.appendChild(widget);
        this.container = widget;
        this.messagesContainer = document.getElementById('flare-messages');
    }

    /**
     * Attache les événements
     */
    attachEvents() {
        // Ouvrir/fermer au clic sur la bulle
        const bubble = this.container.querySelector('.flare-chat-bubble');
        bubble.addEventListener('click', () => this.toggle());

        // Fermer avec le bouton X
        const closeBtn = this.container.querySelector('.flare-chat-close');
        closeBtn.addEventListener('click', () => this.close());
    }

    /**
     * Ouvre/ferme le widget
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * Ouvre le widget
     */
    async open() {
        const window = this.container.querySelector('.flare-chat-window');
        const bubble = this.container.querySelector('.flare-chat-bubble');

        window.classList.add('open');
        bubble.classList.add('active');
        this.isOpen = true;

        // Premier message si pas encore fait
        if (this.currentStep === 'welcome') {
            // Attendre que les données soient chargées
            if (!this.dataLoaded) {
                this.addBotMessage('⏳ Chargement des données...');
                await this.loadData();
            }

            if (this.dataLoaded) {
                setTimeout(() => this.showWelcome(), 300);
            } else {
                this.addBotMessage('❌ Erreur de chargement. Veuillez rafraîchir la page ou contactez-nous:\n📧 contact@flare-custom.com\n📱 +359885813134');
            }
        }

        // Cacher le badge
        const badge = this.container.querySelector('.flare-chat-bubble-badge');
        badge.style.display = 'none';
    }

    /**
     * Ferme le widget
     */
    close() {
        const window = this.container.querySelector('.flare-chat-window');
        const bubble = this.container.querySelector('.flare-chat-bubble');

        window.classList.remove('open');
        bubble.classList.remove('active');
        this.isOpen = false;
    }

    /**
     * Affiche le message de bienvenue
     */
    showWelcome() {
        this.addBotMessage('Bonjour ! 👋 Bienvenue chez FLARE CUSTOM.\n\n🎯 Obtenez votre devis personnalisé en 2 minutes !\n✅ 100% gratuit et sans engagement\n✅ Réponse sous 24h');

        setTimeout(() => {
            this.addBotMessage('Pour commencer, pour quel sport souhaitez-vous des équipements ?');
            this.showSportOptions();
        }, 1200);
    }

    /**
     * Affiche les options de sport
     */
    showSportOptions() {
        console.log('🏀 showSportOptions appelé, data:', this.data);

        if (!this.data || !this.data.sports) {
            console.error('❌ Pas de données disponibles');
            this.addBotMessage('❌ Erreur: données non disponibles. Veuillez rafraîchir la page.');
            return;
        }

        console.log('📋 Sports disponibles:', this.data.sports);

        if (!this.data.sports || this.data.sports.length === 0) {
            console.error('❌ Aucun sport disponible dans les données');
            this.addBotMessage('❌ Erreur: aucun sport disponible. Contactez-nous:\n📧 contact@flare-custom.com');
            return;
        }

        const options = this.data.sports.map(sport => ({
            id: sport,
            title: this.formatSportName(sport),
            desc: this.getSportEmoji(sport)
        }));

        console.log('✅ Options créées:', options);

        this.showOptions(options, (selected) => {
            console.log('✅ Sport sélectionné:', selected);
            this.config.sport = selected.id;
            this.addUserMessage(selected.title);
            this.showFamilyOptions();
        });
    }

    /**
     * Affiche les options de famille
     */
    showFamilyOptions() {
        let families = this.csvParser.getFamiliesBySport(this.config.sport);

        // Ajouter les familles SPORTSWEAR dans tous les sports (sauf si c'est déjà SPORTSWEAR)
        if (this.config.sport !== 'SPORTSWEAR') {
            const sportsWearFamilies = this.csvParser.getFamiliesBySport('SPORTSWEAR');
            // Fusionner en évitant les doublons
            const allFamilies = new Set([...families, ...sportsWearFamilies]);
            families = Array.from(allFamilies).sort();
        }

        this.addBotMessage('Super ! Quel type de produit ?');

        const options = families.map(famille => ({
            id: famille,
            title: famille,
            desc: this.getFamilyEmoji(famille)
        }));

        this.showOptions(options, (selected) => {
            this.config.famille = selected.id;
            this.addUserMessage(selected.title);
            this.showGenreOptions();
        });
    }

    /**
     * Affiche les options de genre
     */
    showGenreOptions() {
        // Déterminer le sport réel à utiliser (SPORTSWEAR si la famille n'existe pas dans le sport actuel)
        let sportToUse = this.config.sport;
        const familiesInCurrentSport = this.csvParser.getFamiliesBySport(this.config.sport);

        if (!familiesInCurrentSport.includes(this.config.famille)) {
            // Cette famille vient du sport SPORTSWEAR
            sportToUse = 'SPORTSWEAR';
            this.config.sportForProducts = sportToUse;
        } else {
            this.config.sportForProducts = this.config.sport;
        }

        const genres = this.csvParser.getGenresBySportAndFamily(sportToUse, this.config.famille);

        this.addBotMessage('Pour qui est-ce ?');

        // Créer les options avec Homme, Femme et Enfant
        const options = [];

        if (genres.includes('Homme')) {
            options.push({
                id: 'Homme',
                title: 'Homme',
                desc: '👨'
            });
        }

        if (genres.includes('Femme')) {
            options.push({
                id: 'Femme',
                title: 'Femme',
                desc: '👩'
            });
        }

        // Ajouter Enfant si Homme existe (on affichera les produits Homme pour les enfants)
        if (genres.includes('Homme')) {
            options.push({
                id: 'Enfant',
                title: 'Enfant',
                desc: '👶'
            });
        }

        this.showOptions(options, (selected) => {
            this.config.genreSelected = selected.id;

            // Si Enfant, on cherche les produits Homme avec -10%
            if (selected.id === 'Enfant') {
                this.config.genre = 'Homme';
                this.config.isEnfant = true;
            } else {
                this.config.genre = selected.id;
                this.config.isEnfant = false;
            }

            this.addUserMessage(selected.title);
            this.showFilterOptions();
        });
    }

    /**
     * Affiche les options de filtrage (manches, col, etc.)
     */
    showFilterOptions() {
        // Utiliser le sport déterminé pour les produits (peut être SPORTSWEAR)
        const sportToUse = this.config.sportForProducts || this.config.sport;

        // Récupérer tous les produits disponibles
        let products = this.csvParser.getProductsBySportFamilyGenre(
            sportToUse,
            this.config.famille,
            this.config.genre
        );

        if (products.length === 0) {
            this.showProducts();
            return;
        }

        // Extraire les variations disponibles (manches, col, etc.)
        const variations = this.extractProductVariations(products);

        // Poser les questions de filtrage si nécessaire
        if (variations.manches && variations.manches.length > 1) {
            this.addBotMessage('Quel type de manches préférez-vous ?');

            const options = variations.manches.map(manche => ({
                id: manche,
                title: manche,
                desc: manche.includes('Courtes') ? '👕' : '🧥'
            }));

            this.showOptions(options, (selected) => {
                this.config.manchesFilter = selected.id;
                this.addUserMessage(selected.title);

                // Continuer avec les autres filtres ou afficher les produits
                this.continueFilteringOrShowProducts(variations);
            });
        } else if (variations.col && variations.col.length > 1) {
            this.addBotMessage('Quel type de col souhaitez-vous ?');

            const options = variations.col.map(col => ({
                id: col,
                title: col,
                desc: '👔'
            }));

            this.showOptions(options, (selected) => {
                this.config.colFilter = selected.id;
                this.addUserMessage(selected.title);
                this.showProducts();
            });
        } else {
            // Pas de filtrage nécessaire, afficher directement les produits
            this.showProducts();
        }
    }

    /**
     * Continue le filtrage ou affiche les produits
     */
    continueFilteringOrShowProducts(variations) {
        if (variations.col && variations.col.length > 1) {
            this.addBotMessage('Quel type de col souhaitez-vous ?');

            const options = variations.col.map(col => ({
                id: col,
                title: col,
                desc: '👔'
            }));

            this.showOptions(options, (selected) => {
                this.config.colFilter = selected.id;
                this.addUserMessage(selected.title);
                this.showProducts();
            });
        } else {
            this.showProducts();
        }
    }

    /**
     * Extrait les variations disponibles dans les produits
     */
    extractProductVariations(products) {
        const variations = {
            manches: new Set(),
            col: new Set()
        };

        products.forEach(product => {
            const titre = product.TITRE_VENDEUR || '';

            // Détecter le type de manches
            if (titre.includes('Manches Courtes') || titre.includes('MC ')) {
                variations.manches.add('Manches Courtes');
            } else if (titre.includes('Manches Longues') || titre.includes('ML ')) {
                variations.manches.add('Manches Longues');
            } else if (titre.includes('Sans Manche') || titre.includes('Débardeur')) {
                variations.manches.add('Sans Manches');
            }

            // Détecter le type de col
            if (titre.includes('Col Bord Côte') || titre.includes('col bord côte')) {
                variations.col.add('Col Bord Côte');
            } else if (titre.includes('Col Tissu') || titre.includes('col tissu')) {
                variations.col.add('Col Tissu');
            } else if (titre.includes('Col Rond')) {
                variations.col.add('Col Rond');
            } else if (titre.includes('Col V')) {
                variations.col.add('Col V');
            }
        });

        return {
            manches: Array.from(variations.manches),
            col: Array.from(variations.col)
        };
    }

    /**
     * Affiche les produits
     */
    showProducts() {
        // Utiliser le sport déterminé pour les produits (peut être SPORTSWEAR)
        const sportToUse = this.config.sportForProducts || this.config.sport;

        let products = this.csvParser.getProductsBySportFamilyGenre(
            sportToUse,
            this.config.famille,
            this.config.genre
        );

        // Appliquer les filtres sélectionnés
        if (this.config.manchesFilter) {
            products = products.filter(p => {
                const titre = p.TITRE_VENDEUR || '';
                if (this.config.manchesFilter === 'Manches Courtes') {
                    return titre.includes('Manches Courtes') || titre.includes('MC ');
                } else if (this.config.manchesFilter === 'Manches Longues') {
                    return titre.includes('Manches Longues') || titre.includes('ML ');
                } else if (this.config.manchesFilter === 'Sans Manches') {
                    return titre.includes('Sans Manche') || titre.includes('Débardeur');
                }
                return true;
            });
        }

        if (this.config.colFilter) {
            products = products.filter(p => {
                const titre = p.TITRE_VENDEUR || '';
                return titre.includes(this.config.colFilter) || titre.toLowerCase().includes(this.config.colFilter.toLowerCase());
            });
        }

        const messageIntro = this.config.isEnfant
            ? 'Parfait ! Voici nos modèles disponibles pour enfants (tailles adaptées avec -10% sur les prix) :'
            : 'Parfait ! Voici nos modèles disponibles pour vous :';

        this.addBotMessage(messageIntro);

        products.forEach(product => {
            this.addProductCard(product, (selected) => {
                this.config.produit = selected;
                this.addUserMessage(selected.TITRE_VENDEUR);
                this.showQuantityInput();
            });
        });
    }

    /**
     * Affiche l'input de quantité
     */
    showQuantityInput() {
        this.addBotMessage('Excellent choix ! 👍\n\nDe combien de pièces avez-vous besoin ?\n\n💡 Plus la quantité est élevée, plus le prix unitaire est avantageux !');

        const inputHtml = `
            <div class="flare-input-group">
                <input type="number" class="flare-input" id="qty-input" placeholder="Ex: 20 pièces" min="1" value="15">
                <button class="flare-btn" id="qty-btn">Continuer →</button>
            </div>
            <div style="text-align: center; margin-top: 8px; font-size: 12px; color: #666;">
                💰 Tarifs dégressifs : plus vous commandez, plus c'est avantageux !
            </div>
        `;

        this.addHTML(inputHtml);

        setTimeout(() => {
            const input = document.getElementById('qty-input');
            const btn = document.getElementById('qty-btn');

            input.focus();

            const validate = () => {
                const qty = parseInt(input.value);
                if (qty && qty > 0) {
                    this.config.quantite = qty;
                    let prix = this.csvParser.calculatePrice(this.config.produit, qty);

                    // Appliquer -10% pour les enfants
                    if (this.config.isEnfant) {
                        prix = {
                            unitPrice: prix.unitPrice * 0.9,
                            totalPrice: prix.totalPrice * 0.9,
                            tier: prix.tier
                        };
                    }

                    this.config.prix = prix;

                    btn.disabled = true;
                    input.disabled = true;

                    this.addUserMessage(`${qty} pièces`);
                    this.showComplementsOrContactForm();
                }
            };

            btn.addEventListener('click', validate);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') validate();
            });
        }, 100);
    }

    /**
     * Propose des compléments de produits ou passe directement au formulaire
     */
    showComplementsOrContactForm() {
        // Définir les compléments logiques par famille de produit
        const complements = this.getProductComplements(this.config.famille);

        if (complements.length === 0 || this.config.hasShownComplements) {
            // Pas de compléments ou déjà proposés, passer au formulaire
            this.showContactForm();
            return;
        }

        // Marquer qu'on a déjà proposé les compléments (éviter la boucle)
        this.config.hasShownComplements = true;

        this.addBotMessage('Excellent ! 🎯\n\nSouhaitez-vous ajouter un produit complémentaire à votre devis ?');

        // Ajouter l'option "Non merci, continuer"
        const options = [
            {
                id: '__skip__',
                title: 'Non merci, continuer',
                desc: '✅'
            }
        ];

        // Ajouter les compléments disponibles
        complements.forEach(comp => {
            options.push({
                id: comp,
                title: comp,
                desc: this.getFamilyEmoji(comp)
            });
        });

        this.showOptions(options, (selected) => {
            if (selected.id === '__skip__') {
                this.addUserMessage('Non merci');
                this.showContactForm();
            } else {
                this.addUserMessage(`Oui, ajouter ${selected.title}`);
                // Revenir à la sélection de genre pour cette nouvelle famille
                this.config.famille = selected.id;
                this.showGenreOptions();
            }
        });
    }

    /**
     * Retourne les familles de produits complémentaires
     */
    getProductComplements(famille) {
        const complementsMap = {
            'Maillot': ['Short', 'Chaussettes', 'Sweat'],
            'Short': ['Maillot', 'Chaussettes', 'Polo'],
            'Polo': ['Pantalon', 'Short', 'Sweat'],
            'Sweat': ['Pantalon', 'Polo', 'Sweat à Capuche'],
            'Sweat à Capuche': ['Pantalon', 'Sweat', 'Polo'],
            'Pantalon': ['Polo', 'Sweat', 'Veste'],
            'Veste': ['Pantalon', 'Polo', 'Sweat'],
            'Coupe-Vent': ['Pantalon', 'Sweat', 'Polo'],
            'Débardeur': ['Short', 'Cuissard', 'Brassière'],
            'Cuissard': ['Maillot', 'Débardeur', 'Gilet'],
            'Chaussettes': ['Maillot', 'Short'],
            'Corsaire': ['Maillot', 'Débardeur', 'Brassière'],
            'Legging': ['Sweat', 'Débardeur', 'Brassière'],
            'Brassière': ['Legging', 'Short', 'Corsaire']
        };

        const sportToUse = this.config.sportForProducts || this.config.sport;
        const availableInSport = this.csvParser.getFamiliesBySport(sportToUse);
        const suggested = complementsMap[famille] || [];

        // Retourner uniquement les compléments disponibles dans le sport actuel
        return suggested.filter(comp => availableInSport.includes(comp));
    }

    /**
     * Affiche le formulaire de contact
     */
    showContactForm() {
        // Calculer l'estimation (on arrondit pour donner une fourchette)
        const prixUnitaireMin = Math.floor(this.config.prix.unitPrice * 0.9 * 100) / 100;
        const prixUnitaireMax = Math.ceil(this.config.prix.unitPrice * 1.1 * 100) / 100;
        const estimationMin = Math.floor(this.config.prix.totalPrice * 0.9 / 50) * 50;
        const estimationMax = Math.ceil(this.config.prix.totalPrice * 1.1 / 50) * 50;

        this.addBotMessage(`Parfait ! Voici un récapitulatif de votre demande :\n\n📦 ${this.config.produit.TITRE_VENDEUR}\n👤 ${this.config.genreSelected}\n🏷️ ${this.config.quantite} pièces\n\n💰 Prix unitaire : ${prixUnitaireMin}€ - ${prixUnitaireMax}€ HT/pièce\n💰 Estimation totale : ${estimationMin}€ - ${estimationMax}€ HT\n\n✨ Nous vous enverrons un devis détaillé et personnalisé sous 24h !`);

        const formHtml = `
            <div style="background: linear-gradient(135deg, rgba(255, 107, 0, 0.05) 0%, rgba(255, 107, 0, 0.1) 100%); padding: 16px; border-radius: 12px; margin-bottom: 16px;">
                <div style="font-size: 13px; color: #666; margin-bottom: 8px;">
                    ✅ Devis gratuit et sans engagement<br>
                    ✅ Réponse sous 24h<br>
                    ✅ Accompagnement personnalisé
                </div>
            </div>

            <div class="flare-form-group">
                <label class="flare-form-label">Prénom *</label>
                <input type="text" class="flare-input" id="prenom" placeholder="Votre prénom" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Nom *</label>
                <input type="text" class="flare-input" id="nom" placeholder="Votre nom" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Email *</label>
                <input type="email" class="flare-input" id="email" placeholder="votre@email.com" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Téléphone *</label>
                <input type="tel" class="flare-input" id="tel" placeholder="+33 6 12 34 56 78" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Club / Entreprise</label>
                <input type="text" class="flare-input" id="club" placeholder="Nom de votre club ou entreprise">
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Vos souhaits de personnalisation</label>
                <textarea class="flare-textarea" id="perso" placeholder="Couleurs souhaitées, logos, numéros, textes...&#10;&#10;Ex: Bleu et blanc, logo du club sur le devant, numéros dans le dos"></textarea>
            </div>
            <button class="flare-btn" id="submit-btn">🚀 Recevoir mon devis gratuit</button>
            <div style="text-align: center; margin-top: 12px; font-size: 11px; color: #999;">
                🔒 Vos données sont sécurisées et ne seront jamais partagées
            </div>
        `;

        this.addHTML(formHtml);

        setTimeout(() => {
            const btn = document.getElementById('submit-btn');
            btn.addEventListener('click', () => this.submitQuote());
        }, 100);
    }

    /**
     * Soumet le devis
     */
    async submitQuote() {
        // Récupérer les valeurs
        this.config.contact = {
            prenom: document.getElementById('prenom').value.trim(),
            nom: document.getElementById('nom').value.trim(),
            email: document.getElementById('email').value.trim(),
            telephone: document.getElementById('tel').value.trim(),
            club: document.getElementById('club').value.trim()
        };

        this.config.perso.remarques = document.getElementById('perso').value.trim();

        // Validation
        if (!this.config.contact.prenom || !this.config.contact.nom ||
            !this.config.contact.email || !this.config.contact.telephone) {
            alert('Merci de remplir tous les champs obligatoires');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(this.config.contact.email)) {
            alert('Email invalide');
            return;
        }

        // Désactiver le bouton
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Envoi en cours...';

        this.addBotMessage('⏳ Envoi de votre demande...');

        try {
            const recap = this.generateRecap();

            const formData = new FormData();
            formData.append('configuration', JSON.stringify(this.config));
            formData.append('recapitulatif', JSON.stringify(recap));

            const response = await fetch('/api/send-quote.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess();
            } else {
                this.addBotMessage(`❌ Erreur: ${result.error}\n\nContactez-nous: contact@flare-custom.com`);
                btn.disabled = false;
                btn.textContent = '📧 Recevoir mon devis';
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.addBotMessage('❌ Erreur réseau. Contactez-nous:\n📧 contact@flare-custom.com\n📱 +359885813134');
            btn.disabled = false;
            btn.textContent = '📧 Recevoir mon devis';
        }
    }

    /**
     * Affiche le succès
     */
    showSuccess() {
        const successHtml = `
            <div class="flare-success">
                <div class="flare-success-icon">🎉</div>
                <h4>Demande envoyée avec succès !</h4>
                <p>Vous allez recevoir votre devis personnalisé à :<br><strong>${this.config.contact.email}</strong></p>
                <p style="margin-top: 12px; font-size: 12px;">
                    ✅ Notre équipe vous recontactera sous 24h<br>
                    ✅ Devis détaillé avec prix et options<br>
                    ✅ Accompagnement personnalisé gratuit
                </p>
                <p style="margin-top: 16px; font-size: 11px; color: rgba(255,255,255,0.8);">
                    📧 Pensez à vérifier vos spams si vous ne recevez rien<br>
                    📱 Besoin urgent ? WhatsApp : +359 885 813 134
                </p>
            </div>
        `;

        this.addHTML(successHtml);

        // Afficher badge sur la bulle
        setTimeout(() => {
            const badge = this.container.querySelector('.flare-chat-bubble-badge');
            badge.textContent = '✓';
            badge.style.background = '#4CAF50';
            badge.style.display = 'flex';
        }, 1000);
    }

    /**
     * Génère le récapitulatif
     */
    generateRecap() {
        return {
            produit: {
                nom: this.config.produit.TITRE_VENDEUR,
                reference: this.config.produit.REFERENCE_FLARE,
                sport: this.formatSportName(this.config.sport),
                famille: this.config.famille,
                genre: this.config.genre,
                tissu: this.config.produit.TISSU,
                grammage: this.config.produit.GRAMMAGE,
                photo: this.config.produit.PHOTO_1
            },
            quantite: this.config.quantite,
            prix: {
                unitaire: this.config.prix.unitPrice,
                total: this.config.prix.totalPrice
            },
            personnalisation: {
                design: false,
                couleurs: '',
                logos: '',
                textes: '',
                remarques: this.config.perso.remarques || ''
            },
            contact: this.config.contact,
            date: new Date().toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        };
    }

    // ========== MÉTHODES D'AFFICHAGE ==========

    /**
     * Ajoute un message du bot
     */
    addBotMessage(text) {
        const msg = document.createElement('div');
        msg.className = 'flare-message bot';
        msg.innerHTML = `
            <div class="flare-message-avatar">🤖</div>
            <div class="flare-message-bubble">${text.replace(/\n/g, '<br>')}</div>
        `;
        this.messagesContainer.appendChild(msg);
        this.scrollToBottom();
    }

    /**
     * Ajoute un message de l'utilisateur
     */
    addUserMessage(text) {
        const msg = document.createElement('div');
        msg.className = 'flare-message user';
        msg.innerHTML = `
            <div class="flare-message-avatar">👤</div>
            <div class="flare-message-bubble">${text}</div>
        `;
        this.messagesContainer.appendChild(msg);
        this.scrollToBottom();
    }

    /**
     * Ajoute du HTML brut
     */
    addHTML(html) {
        const wrapper = document.createElement('div');
        wrapper.style.width = '100%';
        wrapper.style.marginTop = '12px';
        wrapper.innerHTML = html;
        this.messagesContainer.appendChild(wrapper);
        this.scrollToBottom();
    }

    /**
     * Affiche des options
     */
    showOptions(options, callback) {
        console.log('🔘 showOptions appelé avec', options.length, 'options');

        const wrapper = document.createElement('div');
        wrapper.style.width = '100%';
        wrapper.style.marginTop = '12px';
        wrapper.style.pointerEvents = 'auto'; // S'assurer que les interactions sont activées

        const container = document.createElement('div');
        container.className = 'flare-options';

        options.forEach((option, index) => {
            const btn = document.createElement('button');
            btn.className = 'flare-option-btn';
            btn.type = 'button'; // Spécifier explicitement le type
            btn.innerHTML = `
                <div class="flare-option-title">${option.desc} ${option.title}</div>
            `;

            console.log(`✅ Bouton ${index} créé:`, option.title);

            // Gestionnaire de clic avec bind explicite
            const clickHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('👆 Clic sur:', option.title);

                // Désactiver immédiatement le bouton pour éviter les doubles clics
                btn.disabled = true;
                btn.classList.add('selected');

                const allBtns = container.querySelectorAll('.flare-option-btn');
                allBtns.forEach(b => {
                    if (b !== btn) {
                        b.disabled = true;
                        b.style.opacity = '0.5';
                    }
                });

                setTimeout(() => {
                    wrapper.style.opacity = '0.6';
                    wrapper.style.pointerEvents = 'none';
                    callback(option);
                }, 300);
            };

            btn.addEventListener('click', clickHandler);
            // Ajouter aussi un gestionnaire pour le touch sur mobile
            btn.addEventListener('touchend', (e) => {
                e.preventDefault();
                clickHandler(e);
            });

            container.appendChild(btn);
        });

        wrapper.appendChild(container);
        this.messagesContainer.appendChild(wrapper);
        console.log('✅ Options ajoutées au DOM, wrapper pointer-events:', wrapper.style.pointerEvents);

        // Forcer un reflow pour s'assurer que les styles sont appliqués
        wrapper.offsetHeight;

        this.scrollToBottom();
    }

    /**
     * Affiche une carte produit
     */
    addProductCard(product, callback) {
        const wrapper = document.createElement('div');
        wrapper.style.width = '100%';
        wrapper.style.marginTop = '8px';
        wrapper.style.pointerEvents = 'auto'; // S'assurer que les interactions sont activées

        const card = document.createElement('div');
        card.className = 'flare-product-card';
        card.style.cursor = 'pointer';
        card.innerHTML = `
            <img src="${product.PHOTO_1 || '/assets/images/placeholder.jpg'}"
                 alt="${product.TITRE_VENDEUR}"
                 class="flare-product-img"
                 onerror="this.src='/assets/images/placeholder.jpg'">
            <div class="flare-product-info">
                <div class="flare-product-name">${product.TITRE_VENDEUR}</div>
                <div class="flare-product-details">
                    <div style="margin-bottom: 4px;">
                        <strong>📏 Tissu:</strong> ${product.TISSU}
                    </div>
                    <div>
                        <strong>⚖️ Grammage:</strong> ${product.GRAMMAGE}
                    </div>
                </div>
            </div>
        `;

        const clickHandler = (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('👆 Clic sur produit:', product.TITRE_VENDEUR);

            card.classList.add('selected');
            card.style.pointerEvents = 'none';

            const allCards = this.messagesContainer.querySelectorAll('.flare-product-card');
            allCards.forEach(c => {
                if (c !== card) {
                    c.style.opacity = '0.5';
                    c.style.pointerEvents = 'none';
                }
            });

            setTimeout(() => {
                callback(product);
            }, 300);
        };

        card.addEventListener('click', clickHandler);
        card.addEventListener('touchend', (e) => {
            e.preventDefault();
            clickHandler(e);
        });

        wrapper.appendChild(card);
        this.messagesContainer.appendChild(wrapper);

        // Forcer un reflow
        wrapper.offsetHeight;

        this.scrollToBottom();
    }

    /**
     * Scroll vers le bas
     */
    scrollToBottom() {
        setTimeout(() => {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }, 100);
    }

    // ========== MÉTHODES UTILITAIRES ==========

    formatSportName(sport) {
        // Remplacer les "_" par des espaces et capitaliser la première lettre uniquement
        return sport
            .toLowerCase()
            .replace(/_/g, ' ')
            .replace(/^\w/, c => c.toUpperCase());
    }

    getSportEmoji(sport) {
        const emojis = {
            'SPORTSWEAR': '👕',
            'FOOTBALL': '⚽',
            'RUGBY': '🏉',
            'BASKETBALL': '🏀',
            'VOLLEYBALL': '🏐',
            'HANDBALL': '🤾',
            'CYCLISME': '🚴',
            'RUNNING': '🏃'
        };
        return emojis[sport] || '🎯';
    }

    getFamilyEmoji(famille) {
        const emojis = {
            'Maillot': '👕',
            'Short': '🩳',
            'Polo': '👔',
            'Veste': '🧥',
            'Sweat': '🧶',
            'Pantalon': '👖'
        };
        return emojis[famille] || '👕';
    }
}

// Auto-initialisation
let flareWidget;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        flareWidget = new FlareConfigurateurWidget();
        flareWidget.init();
    });
} else {
    flareWidget = new FlareConfigurateurWidget();
    flareWidget.init();
}
