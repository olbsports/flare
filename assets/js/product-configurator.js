/**
 * PRODUCT CONFIGURATOR - Composant dynamique de devis
 * Ce configurateur est injecté dans toutes les pages produits
 * Il utilise les données de prix (priceTiers) définies dans chaque page
 */

(function() {
    console.log('🎨 Product Configurator - Chargement...');

    // Attendre que le DOM soit prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConfigurator);
    } else {
        initConfigurator();
    }

    function initConfigurator() {
        const container = document.getElementById('configurator-container');

        if (!container) {
            console.warn('⚠️ #configurator-container non trouvé');
            return;
        }

        // Injecter le HTML du configurateur
        container.innerHTML = `
            <section id="configurator" class="configurator-section">
                <div class="configurator-container">
                    <div class="configurator-header">
                        <h2>DEMANDEZ VOTRE DEVIS GRATUIT</h2>
                        <p>Réponse garantie sous 24h · Sans engagement · Design 3D photoréaliste inclus</p>
                    </div>

                    <div class="contact-form-simple" style="max-width: 800px; margin: 0 auto; background: #fff; padding: 60px; border-radius: 0;">
                        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                            <div class="form-section" style="margin-bottom: 0;">
                                <label style="display: block; font-weight: 700; font-size: 16px; margin-bottom: 12px;">Nom / Club / Entreprise *</label>
                                <input type="text" id="quoteName" required placeholder="FC Marseille" style="width: 100%; padding: 16px; border: 2px solid #e0e0e0; font-size: 15px; font-family: 'Inter', sans-serif;">
                            </div>

                            <div class="form-section" style="margin-bottom: 0;">
                                <label style="display: block; font-weight: 700; font-size: 16px; margin-bottom: 12px;">Email *</label>
                                <input type="email" id="quoteEmail" required placeholder="contact@club.fr" style="width: 100%; padding: 16px; border: 2px solid #e0e0e0; font-size: 15px; font-family: 'Inter', sans-serif;">
                            </div>

                            <div class="form-section" style="margin-bottom: 0;">
                                <label style="display: block; font-weight: 700; font-size: 16px; margin-bottom: 12px;">Téléphone *</label>
                                <input type="tel" id="quotePhone" required placeholder="+33 6 12 34 56 78" style="width: 100%; padding: 16px; border: 2px solid #e0e0e0; font-size: 15px; font-family: 'Inter', sans-serif;">
                            </div>

                            <div class="form-section" style="margin-bottom: 0;">
                                <label style="display: block; font-weight: 700; font-size: 16px; margin-bottom: 12px;">Quantité estimée *</label>
                                <select id="quoteQuantity" required style="width: 100%; padding: 16px; border: 2px solid #e0e0e0; font-size: 15px; font-family: 'Inter', sans-serif;">
                                    <option value="">Sélectionner...</option>
                                    <option value="1-5">1 à 5 pièces</option>
                                    <option value="5-10">5 à 10 pièces</option>
                                    <option value="10-20">10 à 20 pièces</option>
                                    <option value="20-50">20 à 50 pièces</option>
                                    <option value="50-100">50 à 100 pièces</option>
                                    <option value="100+">100+ pièces</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-section" style="margin-bottom: 32px;">
                            <label style="display: block; font-weight: 700; font-size: 16px; margin-bottom: 12px;">Votre projet (optionnel)</label>
                            <textarea id="quoteMessage" placeholder="Décrivez brièvement votre projet : design souhaité, couleurs, logos, date de livraison, etc." style="width: 100%; min-height: 120px; padding: 16px; border: 2px solid #e0e0e0; font-size: 15px; font-family: 'Inter', sans-serif; resize: vertical;"></textarea>
                        </div>

                        <div style="background: #fff5f3; border-left: 4px solid #FF4B26; padding: 20px; margin-bottom: 32px;">
                            <p style="font-size: 14px; line-height: 1.6; color: #444; margin: 0;">
                                <strong>📧 Notre équipe vous répondra sous 24h maximum</strong> avec un devis détaillé personnalisé et des maquettes 3D photoréalistes de votre produit. Totalement gratuit et sans aucun engagement.
                            </p>
                        </div>

                        <button id="submitQuote" class="btn-submit" style="width: 100%; padding: 24px; background: #FF4B26; color: #fff; border: none; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-family: 'Inter', sans-serif;">
                            ENVOYER MA DEMANDE DE DEVIS 🚀
                        </button>

                        <div id="successMessage" style="display: none; text-align: center; padding: 60px 20px;">
                            <div style="width: 80px; height: 80px; background: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 48px; color: #fff;">✓</div>
                            <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 40px; letter-spacing: 2px; margin-bottom: 16px; color: #22c55e;">DEMANDE ENVOYÉE !</h3>
                            <p style="font-size: 16px; line-height: 1.8; color: #444;">Merci pour votre confiance ! Notre équipe vous répondra sous <strong>24 heures maximum</strong>.</p>
                            <p style="font-size: 16px; line-height: 1.8; color: #444;">Vérifiez votre boîte email (et vos spams au cas où).</p>
                        </div>
                    </div>
                </div>
            </section>
        `;

        // Ajouter les styles inline pour le configurateur
        addConfiguratorStyles();

        // Initialiser les événements
        setupEventListeners();

        console.log('✅ Configurateur de devis chargé');
    }

    function addConfiguratorStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .configurator-section {
                background: linear-gradient(135deg, #1A1D2E 0%, #2C3E50 100%);
                padding: 80px 5%;
                margin: 60px 0;
            }

            .configurator-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .configurator-header {
                text-align: center;
                color: #fff;
                margin-bottom: 60px;
            }

            .configurator-header h2 {
                font-family: 'Bebas Neue', sans-serif;
                font-size: 56px;
                letter-spacing: 3px;
                margin-bottom: 16px;
            }

            .configurator-header p {
                font-size: 20px;
                opacity: 0.9;
            }

            .btn-submit:hover {
                background: #E63910;
                transform: scale(1.02);
            }

            @media (max-width: 768px) {
                .configurator-header h2 {
                    font-size: 36px;
                }

                .configurator-header p {
                    font-size: 16px;
                }

                .contact-form-simple {
                    padding: 40px 24px !important;
                }

                .form-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function setupEventListeners() {
        const submitBtn = document.getElementById('submitQuote');
        if (!submitBtn) return;

        submitBtn.addEventListener('click', handleQuoteSubmit);

        // Bouton "Configurer mon devis" scroll vers le configurateur
        window.scrollToConfigurator = function() {
            const configurator = document.getElementById('configurator');
            if (configurator) {
                configurator.scrollIntoView({ behavior: 'smooth' });
            }
        };
    }

    function handleQuoteSubmit(e) {
        e.preventDefault();

        const name = document.getElementById('quoteName').value.trim();
        const email = document.getElementById('quoteEmail').value.trim();
        const phone = document.getElementById('quotePhone').value.trim();
        const quantity = document.getElementById('quoteQuantity').value;
        const message = document.getElementById('quoteMessage').value.trim();

        // Validation
        if (!name || !email || !phone || !quantity) {
            alert('⚠️ Veuillez remplir tous les champs obligatoires (*)');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('⚠️ Veuillez entrer une adresse email valide');
            return;
        }

        // Récupérer la référence produit depuis l'URL ou le titre
        const productTitle = document.querySelector('.product-info h1')?.textContent || 'Produit';
        const productRef = document.querySelector('.specs-table td:nth-child(2)')?.textContent || 'N/A';

        // Préparer les données
        const quoteData = {
            product: {
                title: productTitle,
                reference: productRef,
                url: window.location.href
            },
            customer: {
                name: name,
                email: email,
                phone: phone
            },
            quantity: quantity,
            message: message,
            timestamp: new Date().toISOString()
        };

        console.log('📧 Demande de devis:', quoteData);

        // TODO: Envoyer à un backend ou service d'email
        // Pour l'instant, on simule l'envoi

        // Afficher le message de succès
        document.querySelector('.contact-form-simple > .form-grid').style.display = 'none';
        document.querySelector('.contact-form-simple > .form-section').style.display = 'none';
        document.querySelector('.contact-form-simple > div[style*="background"]').style.display = 'none';
        document.getElementById('submitQuote').style.display = 'none';
        document.getElementById('successMessage').style.display = 'block';

        // Scroll vers le message de succès
        setTimeout(() => {
            document.getElementById('successMessage').scrollIntoView({ behavior: 'smooth' });
        }, 300);
    }
})();
