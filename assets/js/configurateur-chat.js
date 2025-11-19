/**
 * Configurateur de devis en chat - FLARE CUSTOM
 * Moteur de conversation pour guide l'utilisateur dans sa configuration
 */

class ConfigurateurChat {
    constructor() {
        this.csvParser = new CSVParser();
        this.currentStep = 'welcome';
        this.configuration = {
            sport: null,
            famille: null,
            produit: null,
            genre: null,
            quantite: null,
            prix: null,
            personnalisation: {
                design: false,
                designDescription: '',
                couleurs: '',
                logos: '',
                textes: '',
                remarques: ''
            },
            contact: {
                prenom: '',
                nom: '',
                email: '',
                telephone: '',
                club: '',
                accepteNewsletter: false
            }
        };
        this.messages = [];
        this.data = null;

        // Étapes du parcours
        this.steps = [
            'welcome',
            'sport',
            'famille',
            'genre',
            'produit',
            'quantite',
            'personnalisation',
            'contact',
            'recapitulatif',
            'confirmation'
        ];
    }

    /**
     * Initialise le configurateur
     */
    async init() {
        try {
            // Charger les données CSV
            this.data = await this.csvParser.loadCSV('/assets/data/PRICING-FLARE-2025.csv');
            console.log('Données chargées:', this.data);

            // Afficher le message de bienvenue
            this.showWelcomeMessage();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation:', error);
            this.addBotMessage('Désolé, une erreur est survenue lors du chargement des données. Veuillez rafraîchir la page.');
        }
    }

    /**
     * Affiche le message de bienvenue
     */
    showWelcomeMessage() {
        const welcomeText = `
            Bienvenue sur le configurateur de devis FLARE CUSTOM ! 🎯

            Je suis votre assistant virtuel et je vais vous guider pour créer votre devis personnalisé en quelques étapes simples.

            Nous allons configurer ensemble :
            • Le sport et le type de produit
            • La quantité souhaitée
            • Vos options de personnalisation

            Vous recevrez ensuite un devis détaillé par email. Prêt à commencer ?
        `;

        this.addBotMessage(welcomeText, () => {
            this.showSportSelection();
        });
    }

    /**
     * Affiche la sélection du sport
     */
    showSportSelection() {
        this.currentStep = 'sport';
        this.updateStepsIndicator();

        // Vérifier qu'il y a des sports disponibles
        if (!this.data.sports || this.data.sports.length === 0) {
            this.addBotMessage('Désolé, aucun sport n\'est disponible pour le moment. Veuillez nous contacter directement au +359885813134.');
            return;
        }

        const question = 'Parfait ! Pour quel sport souhaitez-vous des équipements ?';

        const options = this.data.sports.map(sport => ({
            id: sport,
            title: this.formatSportName(sport),
            description: this.getSportDescription(sport)
        }));

        this.addBotMessage(question);
        this.showOptions(options, (selectedSport) => {
            this.configuration.sport = selectedSport.id;
            this.addUserMessage(selectedSport.title);
            this.showFamilySelection();
        });
    }

    /**
     * Affiche la sélection de la famille de produits
     */
    showFamilySelection() {
        this.currentStep = 'famille';
        this.updateStepsIndicator();

        const families = this.csvParser.getFamiliesBySport(this.configuration.sport);

        // Vérifier qu'il y a des familles disponibles
        if (!families || families.length === 0) {
            this.addBotMessage(`Désolé, aucun produit n'est disponible pour le ${this.formatSportName(this.configuration.sport)} pour le moment. Veuillez nous contacter directement au +359885813134.`);
            return;
        }

        const question = `Excellent choix ! Quel type de produit recherchez-vous en ${this.formatSportName(this.configuration.sport)} ?`;

        const options = families.map(famille => ({
            id: famille,
            title: famille,
            description: this.getFamilyDescription(famille)
        }));

        this.addBotMessage(question);
        this.showOptions(options, (selectedFamily) => {
            this.configuration.famille = selectedFamily.id;
            this.addUserMessage(selectedFamily.title);
            this.showGenreSelection();
        });
    }

    /**
     * Affiche la sélection du genre
     */
    showGenreSelection() {
        this.currentStep = 'genre';
        this.updateStepsIndicator();

        const genres = this.csvParser.getGenresBySportAndFamily(
            this.configuration.sport,
            this.configuration.famille
        );

        // Vérifier qu'il y a des genres disponibles
        if (!genres || genres.length === 0) {
            this.addBotMessage(`Désolé, aucun modèle n'est disponible pour cette famille de produits. Veuillez nous contacter directement au +359885813134.`);
            return;
        }

        const question = `Super ! Souhaitez-vous un modèle Homme ou Femme ?`;

        const options = genres.map(genre => ({
            id: genre,
            title: genre,
            description: `Coupe ${genre.toLowerCase()}`
        }));

        this.addBotMessage(question);
        this.showOptions(options, (selectedGenre) => {
            this.configuration.genre = selectedGenre.id;
            this.addUserMessage(selectedGenre.title);
            this.showProductSelection();
        });
    }

    /**
     * Affiche la sélection du produit spécifique
     */
    showProductSelection() {
        this.currentStep = 'produit';
        this.updateStepsIndicator();

        const products = this.csvParser.getProductsBySportFamilyGenre(
            this.configuration.sport,
            this.configuration.famille,
            this.configuration.genre
        );

        // Vérifier qu'il y a des produits disponibles
        if (!products || products.length === 0) {
            this.addBotMessage(`Désolé, aucun produit ne correspond à votre sélection pour le moment. Veuillez nous contacter directement au +359885813134 pour une offre personnalisée.`);
            return;
        }

        const question = `Voici nos modèles de ${this.configuration.famille} ${this.configuration.genre} disponibles :`;

        this.addBotMessage(question);
        this.showProductCards(products, (selectedProduct) => {
            this.configuration.produit = selectedProduct;
            this.addUserMessage(selectedProduct.TITRE_VENDEUR);
            this.showQuantityInput();
        });
    }

    /**
     * Affiche l'input de quantité
     */
    showQuantityInput() {
        this.currentStep = 'quantite';
        this.updateStepsIndicator();

        const priceTiers = this.csvParser.getPriceTiers(this.configuration.produit);

        let tiersText = '📊 Nos tarifs dégressifs :\n\n';
        priceTiers.forEach(tier => {
            tiersText += `• ${tier.label} : ${tier.price.toFixed(2)}€/pièce\n`;
        });

        const question = `${tiersText}\n\nCombien de pièces souhaitez-vous commander ?`;

        this.addBotMessage(question);
        this.showQuantitySelector((quantity) => {
            this.configuration.quantite = quantity;
            const pricing = this.csvParser.calculatePrice(this.configuration.produit, quantity);
            this.configuration.prix = pricing;

            this.addUserMessage(`${quantity} pièces`);
            this.updateCartSummary();
            this.showPersonnalisationOptions();
        });
    }

    /**
     * Affiche les options de personnalisation
     */
    showPersonnalisationOptions() {
        this.currentStep = 'personnalisation';
        this.updateStepsIndicator();

        const question = `Souhaitez-vous un service de design pour votre personnalisation ?\n\nNotre équipe peut créer votre design sur-mesure (logos, motifs, numéros, noms...) ou vous pouvez nous fournir vos propres fichiers.`;

        const options = [
            {
                id: 'design-oui',
                title: 'Oui, j\'ai besoin d\'aide pour le design',
                description: 'Notre équipe vous accompagne'
            },
            {
                id: 'design-non',
                title: 'Non, je fournirai mes fichiers',
                description: 'Vous avez déjà vos visuels'
            }
        ];

        this.addBotMessage(question);
        this.showOptions(options, (selected) => {
            this.configuration.personnalisation.design = selected.id === 'design-oui';
            this.addUserMessage(selected.title);
            this.showPersonnalisationDetails();
        });
    }

    /**
     * Affiche le formulaire de détails de personnalisation
     */
    showPersonnalisationDetails() {
        const question = `Parfait ! Pouvez-vous nous donner quelques détails sur votre personnalisation ?\n\n(Couleurs souhaitées, logos à intégrer, textes/numéros, etc.)`;

        this.addBotMessage(question);
        this.showPersonnalisationForm(() => {
            this.addUserMessage('Détails de personnalisation envoyés ✓');
            this.showContactForm();
        });
    }

    /**
     * Affiche le formulaire de contact
     */
    showContactForm() {
        this.currentStep = 'contact';
        this.updateStepsIndicator();

        const question = `Excellent ! Pour finaliser votre demande de devis, merci de renseigner vos coordonnées :`;

        this.addBotMessage(question);
        this.showContactFormFields(() => {
            this.addUserMessage('Coordonnées envoyées ✓');
            this.showRecapitulatif();
        });
    }

    /**
     * Affiche le récapitulatif final
     */
    showRecapitulatif() {
        this.currentStep = 'recapitulatif';
        this.updateStepsIndicator();

        const recap = this.generateRecapitulatif();
        this.addBotMessage('Voici le récapitulatif de votre demande de devis :');
        this.showRecapitulatifSummary(recap, () => {
            this.submitQuote();
        });
    }

    /**
     * Soumet le devis
     */
    async submitQuote() {
        this.addBotMessage('Envoi de votre demande en cours...');
        this.showTypingIndicator();

        try {
            const formData = new FormData();
            formData.append('configuration', JSON.stringify(this.configuration));
            formData.append('recapitulatif', JSON.stringify(this.generateRecapitulatif()));

            const response = await fetch('/api/send-quote.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            this.hideTypingIndicator();

            if (result.success) {
                this.showConfirmation();
            } else {
                this.addBotMessage(`Désolé, une erreur est survenue : ${result.error}. Veuillez réessayer ou nous contacter directement.`);
            }
        } catch (error) {
            console.error('Erreur lors de l\'envoi:', error);
            this.hideTypingIndicator();
            this.addBotMessage('Désolé, une erreur est survenue lors de l\'envoi. Veuillez réessayer ou nous contacter directement au +359885813134.');
        }
    }

    /**
     * Affiche la confirmation finale
     */
    showConfirmation() {
        this.currentStep = 'confirmation';
        this.updateStepsIndicator();

        const message = `
            ✅ Votre demande de devis a été envoyée avec succès !

            Vous allez recevoir un email de confirmation avec le récapitulatif détaillé à l'adresse : ${this.configuration.contact.email}

            Notre équipe va étudier votre demande et vous recontactera sous 24h avec un devis personnalisé.

            Si vous avez des questions, n'hésitez pas à nous contacter :
            📧 contact@flare-custom.com
            📱 +359885813134

            Merci de votre confiance ! 🎯
        `;

        this.addBotMessage(message);
        this.showSuccessAnimation();
    }

    /**
     * Génère le récapitulatif
     */
    generateRecapitulatif() {
        return {
            produit: {
                nom: this.configuration.produit.TITRE_VENDEUR,
                reference: this.configuration.produit.REFERENCE_FLARE,
                sport: this.formatSportName(this.configuration.sport),
                famille: this.configuration.famille,
                genre: this.configuration.genre,
                tissu: this.configuration.produit.TISSU,
                grammage: this.configuration.produit.GRAMMAGE,
                photo: this.configuration.produit.PHOTO_1
            },
            quantite: this.configuration.quantite,
            prix: {
                unitaire: this.configuration.prix.unitPrice,
                total: this.configuration.prix.totalPrice
            },
            personnalisation: this.configuration.personnalisation,
            contact: this.configuration.contact,
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
    addBotMessage(text, callback) {
        const message = {
            type: 'bot',
            text: text,
            timestamp: new Date()
        };
        this.messages.push(message);
        this.renderMessage(message);

        if (callback) {
            setTimeout(callback, 500);
        }
    }

    /**
     * Ajoute un message de l'utilisateur
     */
    addUserMessage(text) {
        const message = {
            type: 'user',
            text: text,
            timestamp: new Date()
        };
        this.messages.push(message);
        this.renderMessage(message);
    }

    /**
     * Affiche un message dans le chat
     */
    renderMessage(message) {
        const messagesContainer = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${message.type}`;

        const avatar = document.createElement('div');
        avatar.className = `message-avatar ${message.type}`;
        avatar.textContent = message.type === 'bot' ? '🤖' : '👤';

        const content = document.createElement('div');
        content.className = 'message-content';

        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';

        const text = document.createElement('p');
        text.className = 'message-text';
        text.textContent = message.text;
        text.style.whiteSpace = 'pre-line';

        bubble.appendChild(text);
        content.appendChild(bubble);
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);

        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    /**
     * Affiche des options de sélection
     */
    showOptions(options, callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'message-options fade-in';

        options.forEach(option => {
            const card = document.createElement('div');
            card.className = 'option-card';
            card.onclick = () => {
                // Marquer comme sélectionné
                optionsContainer.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');

                // Désactiver les autres options
                setTimeout(() => {
                    optionsContainer.style.pointerEvents = 'none';
                    optionsContainer.style.opacity = '0.6';
                    callback(option);
                }, 300);
            };

            const title = document.createElement('div');
            title.className = 'option-title';
            title.textContent = option.title;

            const description = document.createElement('div');
            description.className = 'option-description';
            description.textContent = option.description;

            card.appendChild(title);
            card.appendChild(description);
            optionsContainer.appendChild(card);
        });

        messagesContainer.appendChild(optionsContainer);
        this.scrollToBottom();
    }

    /**
     * Affiche des cartes de produits
     */
    showProductCards(products, callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'message-options fade-in';

        products.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.onclick = () => {
                optionsContainer.querySelectorAll('.product-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');

                setTimeout(() => {
                    optionsContainer.style.pointerEvents = 'none';
                    optionsContainer.style.opacity = '0.6';
                    callback(product);
                }, 300);
            };

            const img = document.createElement('img');
            img.className = 'product-image';
            img.src = product.PHOTO_1 || '/assets/images/placeholder.jpg';
            img.alt = product.TITRE_VENDEUR;
            img.onerror = () => {
                img.src = '/assets/images/placeholder.jpg';
            };

            const info = document.createElement('div');
            info.className = 'product-info';

            const name = document.createElement('div');
            name.className = 'product-name';
            name.textContent = product.TITRE_VENDEUR;

            const details = document.createElement('div');
            details.className = 'product-details';
            details.textContent = `${product.TISSU} • ${product.GRAMMAGE}`;

            const price = document.createElement('div');
            price.className = 'product-price';
            price.textContent = `À partir de ${parseFloat(product.QTY_1).toFixed(2)}€`;

            info.appendChild(name);
            info.appendChild(details);
            info.appendChild(price);

            card.appendChild(img);
            card.appendChild(info);
            optionsContainer.appendChild(card);
        });

        messagesContainer.appendChild(optionsContainer);
        this.scrollToBottom();
    }

    /**
     * Affiche le sélecteur de quantité
     */
    showQuantitySelector(callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const container = document.createElement('div');
        container.className = 'quantity-input-wrapper fade-in';

        const input = document.createElement('input');
        input.type = 'number';
        input.className = 'quantity-input';
        input.placeholder = 'Entrez la quantité (minimum 1)';
        input.min = '1';
        input.value = '10';

        const button = document.createElement('button');
        button.className = 'validate-button';
        button.textContent = 'Valider la quantité';
        button.onclick = () => {
            const qty = parseInt(input.value);
            if (qty && qty > 0) {
                container.style.pointerEvents = 'none';
                container.style.opacity = '0.6';
                callback(qty);
            } else {
                input.style.borderColor = 'red';
                setTimeout(() => {
                    input.style.borderColor = '';
                }, 1000);
            }
        };

        // Validation à l'entrée
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                button.click();
            }
        });

        container.appendChild(input);
        container.appendChild(button);
        messagesContainer.appendChild(container);
        this.scrollToBottom();

        // Focus sur l'input
        setTimeout(() => input.focus(), 100);
    }

    /**
     * Affiche le formulaire de personnalisation
     */
    showPersonnalisationForm(callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const form = document.createElement('div');
        form.className = 'contact-form fade-in';

        const fields = [
            { name: 'couleurs', label: 'Couleurs souhaitées', type: 'text', placeholder: 'Ex: Rouge et blanc' },
            { name: 'logos', label: 'Logos à intégrer', type: 'text', placeholder: 'Ex: Logo du club + sponsors' },
            { name: 'textes', label: 'Textes/Numéros', type: 'text', placeholder: 'Ex: Noms + numéros joueurs' },
            { name: 'remarques', label: 'Remarques supplémentaires', type: 'textarea', placeholder: 'Autres détails importants...' }
        ];

        fields.forEach(field => {
            const group = document.createElement('div');
            group.className = 'form-group';

            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = field.label;

            let input;
            if (field.type === 'textarea') {
                input = document.createElement('textarea');
                input.className = 'form-textarea';
            } else {
                input = document.createElement('input');
                input.type = field.type;
                input.className = 'form-input';
            }
            input.placeholder = field.placeholder;
            input.dataset.field = field.name;

            group.appendChild(label);
            group.appendChild(input);
            form.appendChild(group);
        });

        const button = document.createElement('button');
        button.className = 'validate-button';
        button.textContent = 'Continuer';
        button.onclick = () => {
            // Sauvegarder les valeurs
            fields.forEach(field => {
                const input = form.querySelector(`[data-field="${field.name}"]`);
                this.configuration.personnalisation[field.name] = input.value;
            });

            form.style.pointerEvents = 'none';
            form.style.opacity = '0.6';
            callback();
        };

        form.appendChild(button);
        messagesContainer.appendChild(form);
        this.scrollToBottom();
    }

    /**
     * Affiche le formulaire de contact
     */
    showContactFormFields(callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const form = document.createElement('div');
        form.className = 'contact-form fade-in';

        const fields = [
            { name: 'prenom', label: 'Prénom *', type: 'text', required: true },
            { name: 'nom', label: 'Nom *', type: 'text', required: true },
            { name: 'email', label: 'Email *', type: 'email', required: true },
            { name: 'telephone', label: 'Téléphone *', type: 'tel', required: true },
            { name: 'club', label: 'Club / Entreprise', type: 'text', required: false }
        ];

        fields.forEach(field => {
            const group = document.createElement('div');
            group.className = 'form-group';

            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = field.label;

            const input = document.createElement('input');
            input.type = field.type;
            input.className = 'form-input';
            input.required = field.required;
            input.dataset.field = field.name;

            group.appendChild(label);
            group.appendChild(input);
            form.appendChild(group);
        });

        // Checkbox newsletter
        const checkboxGroup = document.createElement('div');
        checkboxGroup.className = 'form-checkbox';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = 'newsletter-checkbox';

        const checkboxLabel = document.createElement('label');
        checkboxLabel.htmlFor = 'newsletter-checkbox';
        checkboxLabel.textContent = 'J\'accepte de recevoir les actualités et offres de FLARE CUSTOM';

        checkboxGroup.appendChild(checkbox);
        checkboxGroup.appendChild(checkboxLabel);
        form.appendChild(checkboxGroup);

        const button = document.createElement('button');
        button.className = 'validate-button';
        button.textContent = 'Voir le récapitulatif';
        button.onclick = () => {
            // Validation
            let isValid = true;
            fields.forEach(field => {
                const input = form.querySelector(`[data-field="${field.name}"]`);
                if (field.required && !input.value.trim()) {
                    input.style.borderColor = 'red';
                    isValid = false;
                } else {
                    input.style.borderColor = '';
                    this.configuration.contact[field.name] = input.value.trim();
                }
            });

            if (!isValid) {
                return;
            }

            this.configuration.contact.accepteNewsletter = checkbox.checked;

            form.style.pointerEvents = 'none';
            form.style.opacity = '0.6';
            callback();
        };

        form.appendChild(button);
        messagesContainer.appendChild(form);
        this.scrollToBottom();
    }

    /**
     * Affiche le récapitulatif final
     */
    showRecapitulatifSummary(recap, callback) {
        const messagesContainer = document.getElementById('chat-messages');
        const summary = document.createElement('div');
        summary.className = 'final-summary fade-in';

        let html = `
            <div class="summary-section">
                <h4 class="summary-title">📦 Produit</h4>
                <ul class="summary-list">
                    <li><strong>Référence:</strong> ${recap.produit.reference}</li>
                    <li><strong>Produit:</strong> ${recap.produit.nom}</li>
                    <li><strong>Sport:</strong> ${recap.produit.sport}</li>
                    <li><strong>Genre:</strong> ${recap.produit.genre}</li>
                    <li><strong>Tissu:</strong> ${recap.produit.tissu} - ${recap.produit.grammage}</li>
                </ul>
            </div>

            <div class="summary-section">
                <h4 class="summary-title">🔢 Quantité et Prix</h4>
                <ul class="summary-list">
                    <li><strong>Quantité:</strong> ${recap.quantite} pièces</li>
                    <li><strong>Prix unitaire:</strong> ${recap.prix.unitaire.toFixed(2)}€</li>
                    <li><strong>Prix total HT:</strong> ${recap.prix.total.toFixed(2)}€</li>
                </ul>
            </div>

            <div class="summary-section">
                <h4 class="summary-title">🎨 Personnalisation</h4>
                <ul class="summary-list">
                    <li><strong>Service design:</strong> ${recap.personnalisation.design ? 'Oui' : 'Non'}</li>
                    ${recap.personnalisation.couleurs ? `<li><strong>Couleurs:</strong> ${recap.personnalisation.couleurs}</li>` : ''}
                    ${recap.personnalisation.logos ? `<li><strong>Logos:</strong> ${recap.personnalisation.logos}</li>` : ''}
                    ${recap.personnalisation.textes ? `<li><strong>Textes:</strong> ${recap.personnalisation.textes}</li>` : ''}
                    ${recap.personnalisation.remarques ? `<li><strong>Remarques:</strong> ${recap.personnalisation.remarques}</li>` : ''}
                </ul>
            </div>

            <div class="summary-section">
                <h4 class="summary-title">👤 Contact</h4>
                <ul class="summary-list">
                    <li><strong>Nom:</strong> ${recap.contact.prenom} ${recap.contact.nom}</li>
                    <li><strong>Email:</strong> ${recap.contact.email}</li>
                    <li><strong>Téléphone:</strong> ${recap.contact.telephone}</li>
                    ${recap.contact.club ? `<li><strong>Club:</strong> ${recap.contact.club}</li>` : ''}
                </ul>
            </div>
        `;

        summary.innerHTML = html;

        const button = document.createElement('button');
        button.className = 'validate-button';
        button.textContent = '✅ Envoyer ma demande de devis';
        button.onclick = () => {
            summary.style.pointerEvents = 'none';
            summary.style.opacity = '0.6';
            callback();
        };

        summary.appendChild(button);
        messagesContainer.appendChild(summary);
        this.scrollToBottom();
    }

    /**
     * Affiche l'indicateur de frappe
     */
    showTypingIndicator() {
        const messagesContainer = document.getElementById('chat-messages');
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.id = 'typing-indicator';

        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('div');
            dot.className = 'typing-dot';
            indicator.appendChild(dot);
        }

        messagesContainer.appendChild(indicator);
        this.scrollToBottom();
    }

    /**
     * Cache l'indicateur de frappe
     */
    hideTypingIndicator() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Affiche l'animation de succès
     */
    showSuccessAnimation() {
        const messagesContainer = document.getElementById('chat-messages');
        const success = document.createElement('div');
        success.className = 'success-message fade-in';
        success.innerHTML = `
            <div class="success-icon">✅</div>
            <h3>Demande envoyée avec succès !</h3>
        `;
        messagesContainer.appendChild(success);
        this.scrollToBottom();
    }

    /**
     * Met à jour le résumé du panier
     */
    updateCartSummary() {
        const cartElement = document.getElementById('cart-summary-content');
        if (!cartElement) return;

        let html = '';

        if (this.configuration.sport) {
            html += `
                <div class="cart-item">
                    <div class="cart-item-label">Sport</div>
                    <div class="cart-item-value">${this.formatSportName(this.configuration.sport)}</div>
                </div>
            `;
        }

        if (this.configuration.famille) {
            html += `
                <div class="cart-item">
                    <div class="cart-item-label">Produit</div>
                    <div class="cart-item-value">${this.configuration.famille} ${this.configuration.genre || ''}</div>
                </div>
            `;
        }

        if (this.configuration.quantite) {
            html += `
                <div class="cart-item">
                    <div class="cart-item-label">Quantité</div>
                    <div class="cart-item-value">${this.configuration.quantite} pièces</div>
                </div>
            `;
        }

        if (this.configuration.prix) {
            html += `
                <div class="cart-total">
                    <div class="cart-total-label">Prix total estimé (HT)</div>
                    <div class="cart-total-amount">${this.configuration.prix.totalPrice.toFixed(2)}€</div>
                    <div class="cart-unit-price">${this.configuration.prix.unitPrice.toFixed(2)}€ / pièce</div>
                </div>
            `;
        }

        cartElement.innerHTML = html;
    }

    /**
     * Met à jour l'indicateur d'étapes
     */
    updateStepsIndicator() {
        const indicator = document.getElementById('steps-indicator');
        if (!indicator) return;

        const currentIndex = this.steps.indexOf(this.currentStep);
        const dots = indicator.querySelectorAll('.step-dot');

        dots.forEach((dot, index) => {
            dot.classList.remove('active', 'completed');
            if (index < currentIndex) {
                dot.classList.add('completed');
            } else if (index === currentIndex) {
                dot.classList.add('active');
            }
        });
    }

    /**
     * Fait défiler vers le bas
     */
    scrollToBottom() {
        const messagesContainer = document.getElementById('chat-messages');
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }

    // ========== MÉTHODES UTILITAIRES ==========

    formatSportName(sport) {
        const sportNames = {
            'SPORTSWEAR': 'Sportswear',
            'FOOTBALL': 'Football',
            'RUGBY': 'Rugby',
            'BASKETBALL': 'Basketball',
            'VOLLEYBALL': 'Volleyball',
            'HANDBALL': 'Handball',
            'CYCLISME': 'Cyclisme',
            'RUNNING': 'Running'
        };
        return sportNames[sport] || sport;
    }

    getSportDescription(sport) {
        const descriptions = {
            'SPORTSWEAR': 'Vêtements multisports personnalisables',
            'FOOTBALL': 'Équipements de football sur-mesure',
            'RUGBY': 'Tenues de rugby personnalisées',
            'BASKETBALL': 'Maillots et shorts de basket',
            'VOLLEYBALL': 'Équipements de volley personnalisés',
            'HANDBALL': 'Tenues de handball sur-mesure',
            'CYCLISME': 'Vêtements de cyclisme techniques',
            'RUNNING': 'Équipements de course à pied'
        };
        return descriptions[sport] || 'Équipements personnalisés';
    }

    getFamilyDescription(famille) {
        const descriptions = {
            'Maillot': 'Maillots techniques personnalisables',
            'Short': 'Shorts de sport sur-mesure',
            'Polo': 'Polos élégants et confortables',
            'Veste': 'Vestes techniques et stylées',
            'Sweat': 'Sweats confortables personnalisés',
            'Pantalon': 'Pantalons de sport techniques'
        };
        return descriptions[famille] || 'Produits de qualité';
    }
}

// Initialisation au chargement de la page
let configurateur;
document.addEventListener('DOMContentLoaded', () => {
    configurateur = new ConfigurateurChat();
    configurateur.init();
});
