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
        if (this.dataLoading || this.dataLoaded) return;

        this.dataLoading = true;
        console.log('📊 Chargement des données...');

        try {
            this.csvParser = new CSVParser();
            this.data = await this.csvParser.loadCSV('/assets/data/PRICING-FLARE-2025.csv');
            this.dataLoaded = true;
            console.log('✅ Données chargées:', this.data.products.length, 'produits');
        } catch (error) {
            console.error('❌ Erreur chargement CSV:', error);
            this.dataLoaded = false;
        }
        this.dataLoading = false;
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
        this.addBotMessage('Bonjour ! 👋 Je suis votre assistant FLARE CUSTOM.\n\nJe vais vous aider à obtenir un devis personnalisé en quelques clics. C\'est parti !');

        setTimeout(() => {
            this.addBotMessage('Pour quel sport souhaitez-vous des équipements ?');
            this.showSportOptions();
        }, 1000);
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
        const families = this.csvParser.getFamiliesBySport(this.config.sport);

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
        const genres = this.csvParser.getGenresBySportAndFamily(this.config.sport, this.config.famille);

        this.addBotMessage('Homme ou Femme ?');

        const options = genres.map(genre => ({
            id: genre,
            title: genre,
            desc: genre === 'Homme' ? '👨' : '👩'
        }));

        this.showOptions(options, (selected) => {
            this.config.genre = selected.id;
            this.addUserMessage(selected.title);
            this.showProducts();
        });
    }

    /**
     * Affiche les produits
     */
    showProducts() {
        const products = this.csvParser.getProductsBySportFamilyGenre(
            this.config.sport,
            this.config.famille,
            this.config.genre
        );

        this.addBotMessage('Voici nos modèles disponibles :');

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
        const tiers = this.csvParser.getPriceTiers(this.config.produit);

        let tiersText = '📊 Tarifs dégressifs:\n\n';
        tiers.slice(0, 4).forEach(tier => {
            tiersText += `• ${tier.label}: ${tier.price.toFixed(2)}€\n`;
        });

        this.addBotMessage(tiersText + '\nCombien de pièces ?');

        const inputHtml = `
            <div class="flare-input-group">
                <input type="number" class="flare-input" id="qty-input" placeholder="Ex: 20" min="1" value="10">
                <button class="flare-btn" id="qty-btn">Valider</button>
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
                    this.config.prix = this.csvParser.calculatePrice(this.config.produit, qty);

                    btn.disabled = true;
                    input.disabled = true;

                    this.addUserMessage(`${qty} pièces`);
                    this.showContactForm();
                }
            };

            btn.addEventListener('click', validate);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') validate();
            });
        }, 100);
    }

    /**
     * Affiche le formulaire de contact
     */
    showContactForm() {
        this.addBotMessage(`Prix estimé: ${this.config.prix.totalPrice.toFixed(2)}€ HT\n(${this.config.prix.unitPrice.toFixed(2)}€/pièce)\n\nVos coordonnées pour recevoir le devis :`);

        const formHtml = `
            <div class="flare-form-group">
                <label class="flare-form-label">Prénom *</label>
                <input type="text" class="flare-input" id="prenom" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Nom *</label>
                <input type="text" class="flare-input" id="nom" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Email *</label>
                <input type="email" class="flare-input" id="email" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Téléphone *</label>
                <input type="tel" class="flare-input" id="tel" required>
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Club / Entreprise</label>
                <input type="text" class="flare-input" id="club">
            </div>
            <div class="flare-form-group">
                <label class="flare-form-label">Personnalisation (couleurs, logos...)</label>
                <textarea class="flare-textarea" id="perso" placeholder="Ex: Bleu et blanc, logo du club..."></textarea>
            </div>
            <button class="flare-btn" id="submit-btn">📧 Recevoir mon devis</button>
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
                <div class="flare-success-icon">✅</div>
                <h4>Devis envoyé !</h4>
                <p>Vous allez recevoir un email à:<br><strong>${this.config.contact.email}</strong></p>
                <p style="margin-top: 12px;">Notre équipe vous recontactera sous 24h</p>
            </div>
        `;

        this.addHTML(successHtml);

        // Afficher badge sur la bulle
        setTimeout(() => {
            const badge = this.container.querySelector('.flare-chat-bubble-badge');
            badge.textContent = '✓';
            badge.style.background = '#4CAF50';
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

        const container = document.createElement('div');
        container.className = 'flare-options';

        options.forEach((option, index) => {
            const btn = document.createElement('button');
            btn.className = 'flare-option-btn';
            btn.innerHTML = `
                <div class="flare-option-title">${option.desc} ${option.title}</div>
            `;

            console.log(`✅ Bouton ${index} créé:`, option.title);

            btn.addEventListener('click', () => {
                console.log('👆 Clic sur:', option.title);
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
            });

            container.appendChild(btn);
        });

        wrapper.appendChild(container);
        this.messagesContainer.appendChild(wrapper);
        console.log('✅ Options ajoutées au DOM');
        this.scrollToBottom();
    }

    /**
     * Affiche une carte produit
     */
    addProductCard(product, callback) {
        const wrapper = document.createElement('div');
        wrapper.style.width = '100%';
        wrapper.style.marginTop = '8px';

        const card = document.createElement('div');
        card.className = 'flare-product-card';
        card.innerHTML = `
            <img src="${product.PHOTO_1 || '/assets/images/placeholder.jpg'}"
                 alt="${product.TITRE_VENDEUR}"
                 class="flare-product-img"
                 onerror="this.src='/assets/images/placeholder.jpg'">
            <div class="flare-product-info">
                <div class="flare-product-name">${product.TITRE_VENDEUR}</div>
                <div class="flare-product-details">${product.TISSU} • ${product.GRAMMAGE}</div>
                <div class="flare-product-price">À partir de ${parseFloat(product.QTY_1).toFixed(2)}€</div>
            </div>
        `;

        card.addEventListener('click', () => {
            card.classList.add('selected');
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
        });

        wrapper.appendChild(card);
        this.messagesContainer.appendChild(wrapper);
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
        const names = {
            'SPORTSWEAR': 'Sportswear',
            'FOOTBALL': 'Football',
            'RUGBY': 'Rugby',
            'BASKETBALL': 'Basketball',
            'VOLLEYBALL': 'Volleyball',
            'HANDBALL': 'Handball',
            'CYCLISME': 'Cyclisme',
            'RUNNING': 'Running'
        };
        return names[sport] || sport;
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
