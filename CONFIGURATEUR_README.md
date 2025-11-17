# 🎯 Configurateur de Devis FLARE CUSTOM

## Vue d'ensemble

Le configurateur de devis est une interface de chat interactive qui guide les utilisateurs à travers un parcours d'achat fluide pour créer leur devis personnalisé d'équipements sportifs.

## 🚀 Fonctionnalités

### Parcours utilisateur
1. **Sélection du sport** - Choix parmi tous les sports disponibles (Football, Rugby, Basketball, etc.)
2. **Famille de produit** - Choix du type de produit (Maillot, Short, Polo, etc.)
3. **Genre** - Sélection Homme/Femme
4. **Produit spécifique** - Cartes produits avec photos, descriptions et prix
5. **Quantité** - Input avec calcul de prix dégressif en temps réel
6. **Personnalisation** - Options de design et détails personnalisés
7. **Contact** - Formulaire de coordonnées
8. **Récapitulatif** - Vue complète avant envoi
9. **Confirmation** - Envoi automatique d'emails

### Caractéristiques techniques
- ✅ Interface de chat moderne et responsive
- ✅ Parsing dynamique du CSV de prix
- ✅ Calcul de prix en temps réel selon quantité
- ✅ Indicateur de progression (steps)
- ✅ Résumé du panier en temps réel
- ✅ Envoi d'emails HTML (client + admin)
- ✅ Animations fluides et UX optimale
- ✅ 100% Vanilla JavaScript (pas de framework)
- ✅ Compatible tous navigateurs modernes

## 📁 Architecture des fichiers

```
flare/
├── assets/
│   ├── css/
│   │   └── configurateur-chat.css      # Styles du chat et interface
│   ├── js/
│   │   ├── csv-parser.js                # Parser CSV pour charger les produits
│   │   └── configurateur-chat.js        # Moteur de conversation
│   └── data/
│       └── PRICING-FLARE-2025.csv       # Base de données produits (existant)
├── api/
│   └── send-quote.php                   # API d'envoi d'emails
└── pages/
    └── info/
        └── configurateur-devis.html     # Page principale
```

## 🔧 Installation et Configuration

### 1. Fichiers déjà en place
Tous les fichiers suivants ont été créés et sont prêts à l'emploi :
- ✅ `/assets/js/csv-parser.js`
- ✅ `/assets/js/configurateur-chat.js`
- ✅ `/assets/css/configurateur-chat.css`
- ✅ `/api/send-quote.php`
- ✅ `/pages/info/configurateur-devis.html`

### 2. Configuration Email (Important !)

Éditez le fichier `/api/send-quote.php` et modifiez si nécessaire :

```php
define('ADMIN_EMAIL', 'contact@flare-custom.com');  // Email de réception des devis
define('SITE_NAME', 'FLARE CUSTOM');
define('SITE_URL', 'https://flare-custom.com');
```

### 3. Permissions serveur

Assurez-vous que le serveur peut envoyer des emails :

```bash
# Test d'envoi d'email PHP
php -r "mail('votre-email@test.com', 'Test', 'Message de test');"
```

Si les emails ne fonctionnent pas, contactez votre hébergeur (O2Switch) pour :
- Vérifier que la fonction `mail()` PHP est activée
- Configurer un SMTP si nécessaire
- Vérifier les restrictions d'envoi d'emails

### 4. Intégration au menu (optionnel)

Pour ajouter le configurateur au menu principal, éditez `/pages/components/header.html` :

```html
<li>
    <a href="/pages/info/configurateur-devis.html">
        Configurateur de Devis
    </a>
</li>
```

## 🎨 Personnalisation

### Modifier les couleurs

Éditez `/assets/css/configurateur-chat.css` :

```css
:root {
    --chat-primary: #FF6B00;         /* Couleur principale */
    --chat-primary-dark: #E56000;    /* Couleur principale foncée */
    --chat-secondary: #1a1a1a;       /* Couleur secondaire */
    /* ... */
}
```

### Modifier les messages du bot

Éditez `/assets/js/configurateur-chat.js` et modifiez les méthodes :
- `showWelcomeMessage()` - Message de bienvenue
- `showSportSelection()` - Question sur le sport
- `showFamilySelection()` - Question sur la famille
- etc.

### Ajouter des étapes

1. Ajoutez l'étape dans le tableau `this.steps`
2. Créez une méthode `showVotreEtape()`
3. Appelez-la depuis l'étape précédente

## 📧 Format des emails

### Email client
- ✅ Design HTML responsive
- ✅ Récapitulatif complet de la commande
- ✅ Prix détaillés (unitaire et total HT)
- ✅ Informations de contact FLARE CUSTOM
- ✅ Instructions sur les prochaines étapes

### Email admin
- ✅ Notification instantanée
- ✅ Coordonnées complètes du client
- ✅ Détails du produit et personnalisation
- ✅ Liens cliquables (email, téléphone)
- ✅ Action requise mise en évidence

## 🧪 Test du configurateur

### En local
1. Ouvrir : `http://localhost/pages/info/configurateur-devis.html`
2. Suivre le parcours complet
3. Vérifier les emails dans les logs ou inbox

### En production
1. Ouvrir : `https://flare-custom.com/pages/info/configurateur-devis.html`
2. Tester avec de vraies données
3. Vérifier la réception des emails

### Points de test
- [ ] Chargement du CSV sans erreur
- [ ] Affichage des sports
- [ ] Navigation entre les étapes
- [ ] Calcul du prix dégressif
- [ ] Affichage des images produits
- [ ] Validation du formulaire de contact
- [ ] Envoi des emails (client + admin)
- [ ] Responsive design (mobile/tablet/desktop)

## 📊 Tracking Analytics (optionnel)

Le configurateur inclut des événements Google Analytics :
- `page_view` - Vue de la page
- `configurateur_step` - Chaque étape du parcours
- `quote_submission` - Soumission du devis

Pour activer, ajoutez Google Analytics au site.

## 🐛 Dépannage

### Les produits ne s'affichent pas
- Vérifiez que le CSV est accessible : `/assets/data/PRICING-FLARE-2025.csv`
- Ouvrez la console du navigateur (F12) pour voir les erreurs
- Vérifiez que le parsing CSV ne rencontre pas d'erreurs

### Les emails ne sont pas envoyés
- Vérifiez les logs PHP : `/var/log/apache2/error.log`
- Testez la fonction mail() PHP
- Contactez O2Switch pour la configuration SMTP
- Vérifiez que `send-quote.php` est accessible

### Le chat ne répond pas
- Ouvrez la console (F12) et cherchez les erreurs JavaScript
- Vérifiez que tous les fichiers JS sont chargés
- Vérifiez la compatibilité du navigateur

### Images produits manquantes
- Vérifiez que les URLs dans le CSV sont valides
- Ajoutez une image placeholder : `/assets/images/placeholder.jpg`
- Vérifiez les permissions des dossiers d'images

## 🔒 Sécurité

- ✅ Validation des données côté serveur
- ✅ Protection XSS avec `htmlspecialchars()`
- ✅ Validation des emails
- ✅ Headers CORS configurés
- ✅ Pas d'injection SQL (pas de BDD)

### Recommandations supplémentaires
1. Ajouter un CAPTCHA pour éviter le spam
2. Limiter le taux de soumission (rate limiting)
3. Valider les tailles de fichiers si upload de logos

## 📱 Responsive Design

Le configurateur est entièrement responsive :
- **Desktop** : Chat + résumé côte à côte
- **Tablet** : Layout adaptatif
- **Mobile** : Stack vertical, optimisé tactile

## 🎯 Prochaines améliorations possibles

1. **Sauvegarde de session** - Reprendre la config après rafraîchissement
2. **Export PDF** - Télécharger le devis en PDF
3. **Galerie photos** - Slider d'images produits
4. **Visualisateur 3D** - Prévisualiser la personnalisation
5. **Partage social** - Partager sa config
6. **Multi-produits** - Ajouter plusieurs produits au panier
7. **Connexion compte** - Historique des devis
8. **Live chat** - Support en direct

## 📞 Support

Pour toute question ou problème :
- **Email** : contact@flare-custom.com
- **WhatsApp** : +359 885 813 134

## 📝 Licence

© 2025 FLARE CUSTOM - Tous droits réservés

---

**Version** : 1.0.0
**Date** : Novembre 2025
**Auteur** : FLARE CUSTOM Development Team
