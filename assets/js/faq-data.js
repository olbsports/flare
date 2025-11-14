// FAQ DATA - VERSION COMPLÈTE
const faqData = [
    // ========== COMMANDE ==========
    {
        category: 'commande',
        question: 'Y a-t-il un minimum de commande ?',
        answer: '<p><strong>Non, aucun minimum !</strong> FLARE CUSTOM accepte les commandes dès <strong>1 pièce</strong>.</p><p>Nous proposons des <strong>tarifs dégressifs</strong> selon le volume commandé. Plus vous commandez, plus vous économisez !</p>'
    },
    {
        category: 'commande',
        question: 'Quel est le délai de fabrication ?',
        answer: '<p><strong>Délai standard : 3 à 4 semaines</strong> après validation du design et réception de l\'acompte.</p><p><strong>Service express disponible : 10-15 jours</strong> (supplément de +25% sur le prix total).</p><p>Les délais commencent après validation finale de votre maquette.</p>'
    },
    {
        category: 'commande',
        question: 'Comment passer commande ?',
        answer: '<p><strong>Processus en 5 étapes simples :</strong></p><ul><li><strong>1.</strong> Demande de devis via formulaire ou email</li><li><strong>2.</strong> Réception du devis sous 24h maximum</li><li><strong>3.</strong> Création du design en 48h</li><li><strong>4.</strong> Validation finale + paiement de 50% d\'acompte</li><li><strong>5.</strong> Production 3-4 semaines + livraison</li></ul>'
    },
    {
        category: 'commande',
        question: 'Puis-je modifier ma commande après validation ?',
        answer: '<p><strong>Avant le lancement de la production :</strong> modifications possibles gratuitement.</p><p><strong>Après le début de production :</strong> les modifications ne sont plus possibles car les équipements sont déjà en cours de fabrication avec vos designs personnalisés.</p><p>C\'est pourquoi nous accordons une grande importance à la validation finale de votre maquette.</p>'
    },
    {
        category: 'commande',
        question: 'Proposez-vous des commandes récurrentes pour les clubs ?',
        answer: '<p><strong>Oui !</strong> Nous accompagnons de nombreux clubs avec des commandes régulières :</p><ul><li>Conservation de vos designs et gabarits</li><li>Process simplifié pour les nouvelles commandes</li><li>Tarifs préférentiels pour les partenariats long terme</li><li>Gestionnaire de compte dédié</li></ul>'
    },
    {
        category: 'commande',
        question: 'Comment obtenir un devis ?',
        answer: '<p><strong>2 moyens rapides :</strong></p><ul><li><strong>Email :</strong> contact@flare-custom.com</li><li><strong>Formulaire</strong> sur notre site web</li></ul><p>Réponse garantie sous <strong>24h maximum</strong>, souvent bien plus rapide !</p>'
    },

    // ========== PERSONNALISATION ==========
    {
        category: 'personnalisation',
        question: 'Comment fonctionne la personnalisation ?',
        answer: '<p><strong>Technologie de sublimation haute définition :</strong></p><ul><li><strong>Couleurs illimitées</strong> sans surcoût</li><li><strong>Designs complexes</strong> parfaitement reproduits</li><li><strong>Durabilité maximale</strong> - ne se décolle jamais</li><li><strong>Confort optimal</strong> - pas de sur-épaisseur</li></ul><p>Envoyez-nous vos logos, couleurs Pantone et textes. Design professionnel en 48h.</p>'
    },
    {
        category: 'personnalisation',
        question: 'Puis-je envoyer mon propre design ?',
        answer: '<p><strong>Absolument !</strong> Formats acceptés :</p><ul><li><strong>Vectoriel :</strong> AI, EPS, SVG, PDF (idéal)</li><li><strong>Haute résolution :</strong> PNG, JPG à 300 DPI minimum</li><li><strong>Professionnels :</strong> PSD, INDD</li></ul><p>Notre équipe effectue une <strong>vérification technique gratuite</strong> et vous conseille si besoin d\'optimisations.</p>'
    },
    {
        category: 'personnalisation',
        question: 'Le service design est-il payant ?',
        answer: '<p><strong>Forfait design : 50€</strong> comprenant :</p><ul><li>Création professionnelle en 48h</li><li>Jusqu\'à 4 allers-retours de modifications</li><li>3 propositions de design différentes</li><li>Conseils expert de notre équipe</li></ul><p>Service optionnel mais fortement recommandé pour un rendu professionnel optimal.</p>'
    },
    {
        category: 'personnalisation',
        question: 'Puis-je personnaliser chaque équipement avec des noms différents ?',
        answer: '<p><strong>Personnalisation disponible :</strong></p><ul><li><strong>Numéros classiques (1-99) :</strong> inclus sans supplément</li><li><strong>Noms personnalisés :</strong> +2€ par pièce</li><li><strong>Numéros personnalisés (ex: 101, 00, etc) :</strong> +2€ par pièce</li></ul><p>Tailles variées selon les joueurs sans frais supplémentaires. Envoyez-nous votre liste avec détails.</p>'
    },
    {
        category: 'personnalisation',
        question: 'Combien de logos puis-je ajouter ?',
        answer: '<p><strong>Aucune limitation !</strong> Vous pouvez intégrer autant de logos que souhaité :</p><ul><li>Logo du club</li><li>Logos sponsors (multiples)</li><li>Logos partenaires</li><li>Emblèmes et badges</li></ul><p>Tout est inclus dans le prix, sans frais supplémentaires par logo.</p>'
    },
    {
        category: 'personnalisation',
        question: 'Proposez-vous des modèles prédéfinis ?',
        answer: '<p><strong>Oui !</strong> Nous avons une bibliothèque de plus de <strong>200 modèles</strong> pour tous les sports :</p><ul><li>Football, rugby, basketball, handball</li><li>Cyclisme, running, triathlon</li><li>Volleyball, pétanque</li></ul><p>Vous pouvez partir d\'un modèle et le personnaliser entièrement avec vos couleurs et logos.</p>'
    },

    // ========== TARIFS ==========
    {
        category: 'tarifs',
        question: 'Quels sont vos tarifs ?',
        answer: '<p><strong>Exemples de prix (tarifs dégressifs selon quantité) :</strong></p><ul><li><strong>Maillot football/rugby :</strong> à partir de 16€</li><li><strong>Short :</strong> à partir de 12€</li><li><strong>Débardeur basket :</strong> à partir de 14€</li><li><strong>Maillot cyclisme :</strong> à partir de 20€</li><li><strong>Kit complet :</strong> à partir de 28€</li></ul><p>Demandez un devis personnalisé pour votre projet spécifique !</p>'
    },
    {
        category: 'tarifs',
        question: 'Les tarifs varient-ils selon le volume ?',
        answer: '<p><strong>Oui, tarifs dégressifs !</strong> Plus vous commandez, plus le prix unitaire diminue :</p><ul><li>Les remises s\'appliquent automatiquement</li><li>Économies significatives sur volumes importants</li><li>Devis personnalisé selon vos besoins</li></ul><p>Contactez-nous pour obtenir le meilleur tarif selon votre quantité.</p>'
    },
    {
        category: 'tarifs',
        question: 'Y a-t-il des frais supplémentaires cachés ?',
        answer: '<p><strong>Non, tout est inclus dans le prix !</strong></p><ul><li>Personnalisation illimitée (logos, couleurs)</li><li>Numéros et noms individuels</li><li>Tous les logos souhaités</li><li>Tissu technique premium</li><li>Sublimation haute qualité</li></ul><p><strong>Frais en supplément uniquement :</strong> service design (50€) et service express (+25%).</p>'
    },
    {
        category: 'tarifs',
        question: 'Quels modes de paiement acceptez-vous ?',
        answer: '<p><strong>Paiements acceptés :</strong></p><ul><li><strong>Virement bancaire SEPA</strong> (recommandé pour grosses commandes)</li><li><strong>PayPal</strong></li><li><strong>Carte bancaire</strong> (Visa, Mastercard, American Express)</li></ul><p><strong>Modalités :</strong> 50% d\'acompte à la commande, solde avant expédition.</p>'
    },
    {
        category: 'tarifs',
        question: 'Proposez-vous des factures avec TVA ?',
        answer: '<p><strong>Oui !</strong> Factures officielles pour toutes les commandes :</p><ul><li>TVA européenne selon pays de livraison</li><li>Numéro de TVA : BG208208044</li><li>OLB SPORTS OOD (Bulgarie)</li><li>Facture envoyée par email</li></ul><p>Parfait pour les clubs, associations et entreprises.</p>'
    },

    // ========== LIVRAISON ==========
    {
        category: 'livraison',
        question: 'Quels sont les délais de livraison ?',
        answer: '<p><strong>Livraison standard Europe :</strong></p><ul><li><strong>France :</strong> 5-7 jours ouvrés</li><li><strong>Belgique, Luxembourg, Allemagne :</strong> 7-10 jours</li><li><strong>Reste de l\'Europe :</strong> 10-15 jours</li><li><strong>Suisse, Monaco :</strong> 10-15 jours</li></ul><p><strong>Livraison express disponible : 24-48h</strong> après expédition (supplément)</p><p>Numéro de suivi fourni systématiquement.</p>'
    },
    {
        category: 'livraison',
        question: 'Quels sont les frais de livraison ?',
        answer: '<p><strong>Les frais de livraison varient selon :</strong></p><ul><li>Pays de destination</li><li>Volume de la commande</li><li>Service standard ou express</li></ul><p>Les frais vous seront communiqués dans votre devis personnalisé. Contactez-nous pour obtenir le détail des frais de livraison pour votre commande.</p>'
    },
    {
        category: 'livraison',
        question: 'Livrez-vous partout en Europe ?',
        answer: '<p><strong>Oui, livraison dans toute l\'Europe :</strong></p><ul><li>Union Européenne (27 pays)</li><li>Suisse</li><li>Monaco</li><li>Royaume-Uni</li><li>Norvège, Islande</li></ul><p>Nous travaillons avec des transporteurs fiables pour garantir la sécurité de vos équipements.</p>'
    },
    {
        category: 'livraison',
        question: 'Puis-je suivre ma livraison ?',
        answer: '<p><strong>Oui, suivi complet !</strong></p><ul><li>Numéro de tracking envoyé par email dès expédition</li><li>Suivi en temps réel sur le site du transporteur</li><li>Notifications automatiques des étapes</li><li>Support disponible en cas de question</li></ul>'
    },
    {
        category: 'livraison',
        question: 'Que se passe-t-il si ma commande est perdue ou endommagée ?',
        answer: '<p><strong>Protection complète :</strong></p><ul><li>Toutes nos expéditions sont assurées</li><li>En cas de perte : refabrication immédiate gratuite</li><li>En cas de dommage transport : remplacement à nos frais</li></ul><p>Contactez-nous immédiatement si problème à la réception.</p>'
    },

    // ========== QUALITÉ ==========
    {
        category: 'qualite',
        question: 'Quelle est la qualité des tissus ?',
        answer: '<p><strong>Tissus techniques premium 130-200g/m² :</strong></p><ul><li><strong>100% polyester</strong> haute performance respirant</li><li><strong>Évacuation rapide</strong> de la transpiration</li><li><strong>Anti-bactérien</strong> traitement intégré</li><li><strong>Protection UV 50+</strong></li><li><strong>Élasticité 4-way</strong> pour liberté de mouvement</li></ul><p>Fabrication selon normes <strong>ISO 9001</strong>. Qualité européenne garantie.</p>'
    },
    {
        category: 'qualite',
        question: 'Puis-je commander un échantillon avant ?',
        answer: '<p><strong>Deux options d\'échantillons :</strong></p><ul><li><strong>Échantillons de tissus :</strong> 15€ (envoi d\'échantillons de nos tissus techniques)</li><li><strong>Prototype personnalisé complet :</strong> 50€ + frais de livraison</li></ul><p><strong>Le prototype est remboursé intégralement si vous commandez 50 pièces ou plus !</strong></p>'
    },
    {
        category: 'qualite',
        question: 'Quelle garantie proposez-vous ?',
        answer: '<p><strong>Garantie 1 an sur tous nos produits :</strong></p><ul><li>Défauts de fabrication</li><li>Problèmes de sublimation (décoloration, craquelures)</li><li>Qualité du tissu (déchirures anormales)</li></ul><p><strong>Refabrication gratuite</strong> si défaut avéré sous garantie.</p>'
    },
    {
        category: 'qualite',
        question: 'Les couleurs restent-elles vives après les lavages ?',
        answer: '<p><strong>Oui, couleurs permanentes !</strong></p><p>La sublimation intègre l\'encre directement dans les fibres du tissu :</p><ul><li>Aucune décoloration même après 100+ lavages</li><li>Pas de craquelures ou décollement</li><li>Résistance maximale au chlore et au soleil</li></ul><p>Lavage en machine jusqu\'à 40°C sans problème.</p>'
    },
    {
        category: 'qualite',
        question: 'Vos produits sont-ils fabriqués en Europe ?',
        answer: '<p><strong>Oui, fabrication 100% européenne !</strong></p><ul><li>Production en Europe de l\'Est (Bulgarie)</li><li>Contrôle qualité européen strict</li><li>Normes environnementales respectées</li><li>Circuits courts = délais optimisés</li></ul><p>Nous sommes fiers de soutenir l\'industrie européenne.</p>'
    },
    {
        category: 'qualite',
        question: 'Proposez-vous un guide des tailles ?',
        answer: '<p><strong>Oui, guide des tailles détaillé disponible !</strong></p><ul><li>Tailles disponibles : <strong>XS à 4XL</strong></li><li>Tailles enfant disponibles sur demande</li><li>Coupes anatomiques homme et femme</li><li>Mesures détaillées pour chaque taille</li></ul><p>Notre équipe peut vous conseiller sur les tailles selon morphologies de votre équipe.</p>'
    },

    // ========== RETOURS & SAV ==========
    {
        category: 'qualite',
        question: 'Puis-je retourner ou échanger ma commande ?',
        answer: '<p><strong>Produits personnalisés = pas d\'échange ni retour</strong></p><p>Chaque équipement étant fabriqué sur-mesure selon vos spécifications, nous ne pouvons accepter les retours.</p><p><strong>MAIS :</strong> En cas d\'erreur de notre part (taille, design, fabrication), nous refabriquons gratuitement et intégralement votre commande.</p>'
    },
    {
        category: 'qualite',
        question: 'Que faire si je reçois un produit défectueux ?',
        answer: '<p><strong>Contactez-nous immédiatement :</strong></p><ul><li><strong>Email :</strong> contact@flare-custom.com</li><li><strong>Formulaire de contact</strong> sur notre site</li></ul><p><strong>Procédure :</strong></p><ul><li>Photos du défaut</li><li>Analyse sous 24-48h</li><li>Refabrication gratuite si défaut confirmé</li><li>Ré-expédition rapide à nos frais</li></ul>'
    },

    // ========== BAT & VALIDATION ==========
    {
        category: 'validation',
        question: 'Qu\'est-ce qu\'un BAT (Bon à Tirer) ?',
        answer: '<p>Le <strong>BAT est la maquette finale</strong> que nous vous envoyons avant production :</p><ul><li>Visualisation exacte du rendu final</li><li>Tous les détails : logos, couleurs, textes, numéros</li><li>Vues avant/arrière et détails</li><li>Simulation réaliste sur mannequin</li></ul><p><strong>Une fois validé, aucune modification n\'est possible.</strong> Prenez le temps de vérifier tous les détails !</p>'
    },
    {
        category: 'validation',
        question: 'Combien de temps pour recevoir le BAT ?',
        answer: '<p><strong>Délai de création du BAT : 48h maximum</strong> après réception de tous vos éléments (logos, textes, numéros).</p><p>Le BAT inclut :</p><ul><li>Mise en page professionnelle de votre design</li><li>Intégration de tous vos logos</li><li>Simulation couleurs exactes</li><li>Vérification technique de faisabilité</li></ul><p>Nous pouvons faire jusqu\'à <strong>3 versions différentes</strong> incluses dans le forfait design.</p>'
    },
    {
        category: 'validation',
        question: 'Puis-je demander des modifications sur le BAT ?',
        answer: '<p><strong>Oui, jusqu\'à 4 allers-retours de modifications inclus !</strong></p><p>Vous pouvez modifier :</p><ul><li>Couleurs et nuances</li><li>Position des logos</li><li>Taille des éléments</li><li>Textes et numéros</li><li>Design global</li></ul><p>Chaque modification prend <strong>24-48h</strong>. Une fois le BAT validé et signé, la production démarre et plus aucune modification n\'est possible.</p>'
    },
    {
        category: 'validation',
        question: 'Comment valider mon BAT ?',
        answer: '<p><strong>Validation en 3 étapes simples :</strong></p><ul><li><strong>1.</strong> Nous vous envoyons le BAT par email en PDF haute résolution</li><li><strong>2.</strong> Vous vérifiez TOUS les détails attentivement</li><li><strong>3.</strong> Vous nous renvoyez votre validation écrite par email</li></ul><p><strong>Important :</strong> La validation du BAT engage la production. Vérifiez orthographe, numéros, couleurs et positionnement des logos.</p>'
    },

    // ========== SUBLIMATION & TECHNIQUE ==========
    {
        category: 'technique',
        question: 'Qu\'est-ce que la sublimation exactement ?',
        answer: '<p><strong>La sublimation est un procédé d\'impression par transfert thermique</strong> qui intègre l\'encre directement dans les fibres du tissu :</p><ul><li><strong>Technique :</strong> L\'encre passe de l\'état solide à gazeux sous haute température (200°C)</li><li><strong>Résultat :</strong> Les motifs font partie intégrante du tissu</li><li><strong>Avantages :</strong> Couleurs éclatantes, durabilité extrême, aucune sur-épaisseur</li><li><strong>Différence vs flocage :</strong> Ne se décolle jamais, ne craquelle jamais</li></ul>'
    },
    {
        category: 'technique',
        question: 'Quelle est la différence entre sublimation et sérigraphie ?',
        answer: '<p><strong>Comparatif des techniques :</strong></p><p><strong>SUBLIMATION (notre technique) :</strong></p><ul><li>Couleurs illimitées sans surcoût</li><li>Designs complexes et dégradés possibles</li><li>Durabilité maximale (fait partie du tissu)</li><li>Confort optimal (aucune sur-épaisseur)</li><li>Idéal pour équipements sportifs personnalisés</li></ul><p><strong>SÉRIGRAPHIE :</strong></p><ul><li>Limitée en nombre de couleurs</li><li>Sur-épaisseur du motif</li><li>Moins durable (peut craquer)</li><li>Moins adaptée aux textiles techniques</li></ul>'
    },
    {
        category: 'technique',
        question: 'Quelles couleurs Pantone pouvez-vous reproduire ?',
        answer: '<p><strong>Reproduction de toutes les couleurs Pantone !</strong></p><ul><li>Bibliothèque Pantone complète disponible</li><li>Matching précis à 95-98%</li><li>Calibration colorimétrique professionnelle</li><li>Épreuves de couleur sur demande</li></ul><p><strong>Important :</strong> Légers écarts possibles entre écran et tissu réel (variations d\'affichage). Nous vous conseillons pour des couleurs optimales.</p>'
    },
    {
        category: 'technique',
        question: 'Les motifs sont-ils identiques sur toutes les tailles ?',
        answer: '<p><strong>Oui, proportions adaptées automatiquement !</strong></p><ul><li>Designs ajustés selon chaque taille (XS à 4XL)</li><li>Logos proportionnels à la taille du vêtement</li><li>Numéros et noms positionnés idéalement</li><li>Rendu homogène sur toute l\'équipe</li></ul><p>Notre système CAO adapte intelligemment le design à chaque gabarit.</p>'
    },
    {
        category: 'technique',
        question: 'Puis-je avoir des finitions spéciales (paillettes, relief, etc.) ?',
        answer: '<p><strong>La sublimation permet uniquement des impressions à plat</strong> (pas de relief ni paillettes).</p><p><strong>Ce que nous pouvons faire :</strong></p><ul><li>Effets visuels de brillance/mat par le design</li><li>Simulations de textures et d\'ombres</li><li>Dégradés et effets 3D visuels</li><li>Effets métalliques simulés</li></ul><p><strong>Avantage :</strong> Confort maximal sans sur-épaisseur gênante pendant l\'effort.</p>'
    },
    {
        category: 'technique',
        question: 'Le tissu reste-t-il respirant après la sublimation ?',
        answer: '<p><strong>Oui, totalement respirant !</strong></p><p>La sublimation <strong>n\'obstrue pas les pores du tissu</strong> :</p><ul><li>Évacuation de la transpiration intacte</li><li>Propriétés techniques du tissu préservées</li><li>Légèreté identique</li><li>Élasticité 4-way conservée</li></ul><p>Contrairement au flocage qui ajoute une couche imperméable, la sublimation intègre l\'encre dans les fibres.</p>'
    },

    // ========== QUESTIONS TECHNIQUES SPÉCIFIQUES ==========
    {
        category: 'technique',
        question: 'Acceptez-vous les fichiers basse résolution ?',
        answer: '<p><strong>Oui, mais avec vectorisation obligatoire :</strong></p><ul><li>Logos basse résolution : vectorisation manuelle par notre équipe</li><li>Coût supplémentaire : 15€ par logo à vectoriser</li><li>Délai : +24h au délai de création du BAT</li></ul><p><strong>Pour éviter ces frais :</strong> Fournissez vos logos en vectoriel (AI, EPS, SVG, PDF) ou haute résolution (300 DPI minimum).</p>'
    },
    {
        category: 'technique',
        question: 'Proposez-vous des échantillons de couleurs avant production ?',
        answer: '<p><strong>Oui, nous pouvons créer des échantillons couleur :</strong></p><ul><li><strong>Échantillon tissu imprimé :</strong> 25€ (A4)</li><li><strong>Nuancier de vos couleurs exactes</strong> sur le tissu final</li><li>Délai : 5-7 jours ouvrés</li><li>Remboursé si commande de 50+ pièces</li></ul><p>Idéal pour valider les couleurs critiques (sponsors, identité de marque).</p>'
    }
];
