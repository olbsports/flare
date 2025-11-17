# 💬 Widget Configurateur de Devis - FLARE CUSTOM

## Vue d'ensemble

Widget de configurateur flottant qui s'affiche en bas à droite de votre site, sous forme de bulle de chat. Interface ultra-intuitive et efficace pour obtenir des devis en 2 minutes.

## ✨ Fonctionnalités

### Interface
- 💬 **Bulle flottante** en bas à droite (60x60px)
- ⚡ **Animation de pulsation** pour attirer l'attention
- 🎯 **Ouverture smooth** avec fenêtre de chat (400x600px)
- 📱 **100% Responsive** - S'adapte sur mobile/tablet/desktop
- 🎨 **Design moderne** - Cohérent avec votre charte graphique

### Parcours optimisé
1. **Sport** - Sélection rapide avec emojis (⚽🏀🏉)
2. **Famille** - Type de produit (Maillot, Short, etc.)
3. **Genre** - Homme/Femme
4. **Produit** - Cartes compactes avec photos
5. **Quantité** - Input avec prix dégressif en temps réel
6. **Contact** - Formulaire simplifié + personnalisation
7. **Envoi** - Email automatique client + admin

### Avantages vs version page complète
- ✅ **Plus rapide** - Accessible depuis n'importe quelle page
- ✅ **Plus intuitif** - Format chat familier pour les utilisateurs
- ✅ **Plus efficace** - Parcours optimisé en 2 minutes
- ✅ **Plus accessible** - Toujours visible en bas à droite
- ✅ **Meilleur taux de conversion** - UX optimale

## 🚀 Installation (Super simple !)

### Méthode 1 : Intégration sur toutes les pages

Ajoutez ces lignes dans le `<head>` de votre header commun (`/pages/components/header.html`) :

```html
<!-- Widget Configurateur de Devis -->
<link rel="stylesheet" href="/assets/css/configurateur-widget.css">
<script src="/assets/js/csv-parser.js" defer></script>
<script src="/assets/js/configurateur-widget.js" defer></script>
```

**C'est tout !** Le widget apparaîtra automatiquement sur toutes les pages.

### Méthode 2 : Intégration sur une page spécifique

Dans le `<head>` de votre page :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ma Page</title>

    <!-- Vos autres CSS -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <!-- Widget Configurateur -->
    <link rel="stylesheet" href="/assets/css/configurateur-widget.css">
    <script src="/assets/js/csv-parser.js" defer></script>
    <script src="/assets/js/configurateur-widget.js" defer></script>
</head>
<body>
    <!-- Votre contenu -->

    <!-- Le widget s'affiche automatiquement ! -->
</body>
</html>
```

## 📁 Fichiers du widget

```
flare/
├── assets/
│   ├── css/
│   │   └── configurateur-widget.css    # Styles du widget (13 KB)
│   ├── js/
│   │   ├── csv-parser.js               # Parser CSV (partagé) (8.4 KB)
│   │   └── configurateur-widget.js     # Moteur du widget (21 KB)
│   └── data/
│       └── PRICING-FLARE-2025.csv      # Données produits (existant)
├── api/
│   └── send-quote.php                  # API email (partagé) (23 KB)
└── pages/
    └── info/
        └── demo-widget.html            # Page de démonstration
```

**Total : 42.4 KB de code** (CSS + JS, avant compression)

## 🎨 Personnalisation

### Modifier les couleurs

Éditez `/assets/css/configurateur-widget.css` (lignes 7-11) :

```css
:root {
    --widget-primary: #FF6B00;         /* Couleur de la bulle */
    --widget-primary-dark: #E56000;    /* Dégradé */
    --widget-white: #ffffff;
    --widget-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}
```

### Modifier le texte de bienvenue

Éditez `/assets/js/configurateur-widget.js` (ligne ~80) :

```javascript
showWelcome() {
    this.addBotMessage('Bonjour ! 👋 Je suis votre assistant FLARE CUSTOM...');
    // ...
}
```

### Modifier la position

Par défaut : bas à droite (20px du bord)

Pour changer, éditez `/assets/css/configurateur-widget.css` (lignes 15-18) :

```css
#flare-configurateur-widget {
    position: fixed;
    bottom: 20px;    /* Distance du bas */
    right: 20px;     /* Distance de la droite */
    /* Pour mettre à gauche : left: 20px; */
}
```

### Modifier la taille de la bulle

Éditez `/assets/css/configurateur-widget.css` (lignes 22-24) :

```css
.flare-chat-bubble {
    width: 60px;     /* Largeur de la bulle */
    height: 60px;    /* Hauteur de la bulle */
}
```

### Modifier la taille de la fenêtre

Éditez `/assets/css/configurateur-widget.css` (lignes 86-89) :

```css
.flare-chat-window {
    width: 400px;    /* Largeur de la fenêtre */
    height: 600px;   /* Hauteur de la fenêtre */
}
```

## 🧪 Test du widget

### En local
1. Ouvrir : `http://localhost/pages/info/demo-widget.html`
2. Cliquer sur la bulle orange en bas à droite
3. Tester le parcours complet

### En production
1. Ouvrir : `https://flare-custom.com/pages/info/demo-widget.html`
2. Tester avec de vraies données
3. Vérifier la réception des emails

### Points de test
- [ ] Apparition de la bulle en bas à droite
- [ ] Ouverture/fermeture smooth
- [ ] Navigation entre les étapes
- [ ] Affichage des produits avec images
- [ ] Calcul du prix dégressif
- [ ] Validation du formulaire
- [ ] Envoi des emails
- [ ] Responsive mobile

## 📊 Comparaison versions

| Critère | Version Page | Version Widget |
|---------|-------------|----------------|
| Accessibilité | Page dédiée | Toutes les pages ✅ |
| Rapidité | 5 étapes | 3 clics pour commencer ✅ |
| UX | Standard | Chat moderne ✅ |
| Conversion | Moyen | Élevé ✅ |
| Installation | 1 page | 3 lignes de code ✅ |
| Mobile | Responsive | Optimisé mobile ✅ |

## 🎯 Recommandations d'usage

### Où intégrer le widget ?

**Recommandé** :
- ✅ Page d'accueil
- ✅ Pages produits
- ✅ Page "Devis"
- ✅ Blog

**Optionnel** :
- Page contact (vous avez déjà un formulaire)
- Pages légales (moins pertinent)

### Intégration dans le header

Pour l'activer sur toutes les pages, éditez `/pages/components/header.html` et ajoutez avant `</head>` :

```html
<!-- Widget Configurateur -->
<link rel="stylesheet" href="/assets/css/configurateur-widget.css">
<script src="/assets/js/csv-parser.js" defer></script>
<script src="/assets/js/configurateur-widget.js" defer></script>
```

## 🔒 Sécurité

- ✅ Validation côté client (email, téléphone, champs requis)
- ✅ Validation côté serveur (PHP)
- ✅ Protection XSS (htmlspecialchars)
- ✅ Headers CORS configurés
- ✅ Pas d'injection SQL possible

## 📧 Emails envoyés

### Email client
- Confirmation de réception
- Récapitulatif complet (produit, quantité, prix)
- Coordonnées FLARE CUSTOM

### Email admin
- Notification instantanée
- Coordonnées client cliquables
- Détails de la demande
- Action requise (réponse sous 24h)

## 🎨 Animations

- **Pulsation de la bulle** - Attire l'attention
- **Slide up** - Ouverture de la fenêtre
- **Message slide** - Apparition des messages
- **Typing dots** - Indicateur de frappe
- **Hover effects** - Sur tous les boutons

## 📱 Support Mobile

Le widget est entièrement optimisé mobile :
- Bulle réduite à 56x56px sur petit écran
- Fenêtre en plein écran (avec marges)
- Touch-friendly (boutons assez grands)
- Scroll optimisé

## 🐛 Dépannage

### Le widget ne s'affiche pas
- Vérifiez que les 3 fichiers sont bien chargés (F12 > Network)
- Vérifiez qu'il n'y a pas d'erreur JavaScript (F12 > Console)
- Vérifiez que le CSV est accessible

### Les produits ne s'affichent pas
- Vérifiez le chargement du CSV dans la console
- Vérifiez le format du CSV (séparateur `;`)
- Vérifiez les permissions du fichier CSV

### Les emails ne partent pas
- Vérifiez `/api/send-quote.php` est accessible
- Testez la fonction `mail()` PHP
- Contactez O2Switch pour configuration SMTP

### La bulle est cachée par un autre élément
- Augmentez le z-index dans `/assets/css/configurateur-widget.css` :
```css
#flare-configurateur-widget {
    z-index: 9999999; /* Augmentez si besoin */
}
```

## 🚀 Optimisations futures

### Performance
- [ ] Minification CSS/JS
- [ ] Lazy loading des images produits
- [ ] Cache du CSV en localStorage
- [ ] Service Worker pour offline

### Fonctionnalités
- [ ] Multi-produits (panier)
- [ ] Upload de logos
- [ ] Visualisateur 3D
- [ ] Live chat intégré
- [ ] Bot IA pour suggestions

### Analytics
- [ ] Google Analytics events
- [ ] Taux de conversion par étape
- [ ] Heatmap des interactions
- [ ] A/B testing

## 📞 Support

Pour toute question ou personnalisation :
- **Email** : contact@flare-custom.com
- **WhatsApp** : +359 885 813 134

## 📝 Changelog

### Version 1.0.0 (Novembre 2025)
- ✅ Widget bulle flottant
- ✅ Interface chat moderne
- ✅ Parcours optimisé 7 étapes
- ✅ Emails HTML automatiques
- ✅ Responsive mobile/desktop
- ✅ Prix dégressif en temps réel

---

**Version** : 1.0.0
**Date** : Novembre 2025
**Auteur** : FLARE CUSTOM Development Team
