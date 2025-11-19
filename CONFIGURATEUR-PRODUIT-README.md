# 🎯 Configurateur Produit - FLARE CUSTOM

Configurateur de devis ultra-complet pour les pages produits, avec toutes les options de personnalisation.

## 📁 Fichiers créés

- `/assets/js/configurateur-produit.js` - Moteur JavaScript (990 lignes)
- `/assets/css/configurateur-produit.css` - Styles FLARE CUSTOM (1000+ lignes)
- `/api/send-quote-product.php` - API d'envoi des devis

## 🚀 Installation sur une page produit

### 1. Inclure les fichiers dans le `<head>` :

```html
<!-- CSS -->
<link rel="stylesheet" href="/assets/css/configurateur-produit.css">

<!-- JavaScript -->
<script src="/assets/js/configurateur-produit.js" defer></script>
```

### 2. Ajouter le bouton d'ouverture :

```html
<button class="btn-ouvrir-configurateur" onclick="ouvrirConfigurateurProduit()">
    🎨 Configurer mon devis
</button>
```

### 3. Initialiser avec les données produit :

```html
<script>
function ouvrirConfigurateurProduit() {
    // Données du produit (à adapter dynamiquement)
    const productData = {
        reference: 'MAILLOT-FOOT-001',
        nom: 'Maillot Football Personnalisé',
        sport: 'Football',
        famille: 'Maillot',
        photo: '/assets/images/products/maillot-foot-001.jpg',
        tissu: 'Polyester technique respirant',
        grammage: '140g/m²',
        prixBase: 22.50  // Prix de base pour le calcul dégressif
    };

    // Ouvrir le configurateur
    initConfigurateurProduit(productData);
}
</script>
```

## ⭐ Fonctionnalités

### Étape 1 : Type de design
- ✅ Design par FLARE (notre équipe)
- ✅ Fichiers client (fournis par le client)
- ✅ Template prédéfini (4 templates disponibles)

### Étape 2 : Options produit
- Col : Rond, V, Polo, Montant, Sans col
- Manches : Courtes, Longues, Sans manches, 3/4
- Poches : Avec / Sans
- Fermeture : Zip complet, Zip partiel, Boutons, Sans

### Étape 3 : Genre
- Homme (coupe masculine)
- Femme (coupe féminine)
- Mixte (coupe unisexe)

### Étape 4 : Tailles et quantités
- 8 tailles disponibles : XS, S, M, L, XL, XXL, 3XL, 4XL
- Quantité par taille
- Total automatique
- **Presets rapides** :
  - Équipe 15 joueurs (S:2, M:5, L:5, XL:3)
  - Club 25 personnes (répartition équilibrée)
  - Événement 50 personnes (grandes quantités)

### Étape 5 : Personnalisation
- **Couleurs** : Principale, secondaire, tertiaire (picker + hex)
- **Logos** : Description + upload après devis
- **Numéros** : Oui/Non + style de numérotation
- **Noms** : Oui/Non + style des noms
- **Remarques** : Champ libre

### Étape 6 : Contact et validation
- Formulaire complet
- Récapitulatif final
- Prix estimé HT en temps réel
- Envoi du devis

## 💰 Calcul des prix dégressifs

Le configurateur calcule automatiquement les prix selon la grille :

| Quantité | Réduction |
|----------|-----------|
| 500+     | -35%      |
| 250-499  | -30%      |
| 100-249  | -25%      |
| 50-99    | -20%      |
| 20-49    | -15%      |
| 10-19    | -10%      |
| 5-9      | -5%       |
| 1-4      | Prix base |

## 📧 Emails envoyés

### Email client
- Confirmation de la demande
- Récapitulatif complet
- Répartition des tailles
- Détails de personnalisation
- Prix estimé
- Prochaines étapes

### Email admin
- Informations client
- Détails produit complets
- Options sélectionnées
- Personnalisation détaillée
- Action requise : réponse sous 24h

## 🎨 Design System

**Couleurs FLARE CUSTOM** :
- Primary: `#FF4B26`
- Dark: `#E63910`
- Secondary: `#1a1a1a`

**Polices** :
- Titres : Bebas Neue
- Contenu : Inter

**Transitions** : `0.3s ease`
**Border-radius** : `8px` (boutons), `16px` (cards), `20px` (modal)

## 📱 Responsive

Le configurateur est 100% responsive :

- **Desktop** : Layout 2 colonnes (contenu + sidebar)
- **Tablette** (< 1200px) : Sidebar réduit
- **Mobile** (< 968px) : Layout 1 colonne, sidebar en bas
- **Mobile** (< 768px) : Grids adaptés, boutons empilés

## 🔧 Personnalisation avancée

### Changer les tailles disponibles :

```javascript
// Dans configurateur-produit.js, ligne 72
this.taillesDisponibles = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];

// Modifier pour enfants par exemple :
this.taillesDisponibles = ['2 ans', '4 ans', '6 ans', '8 ans', '10 ans', '12 ans', '14 ans'];
```

### Ajouter des templates :

```javascript
// Dans renderTemplateSelector(), ligne 161
const templates = [
    { id: 'classic', name: 'Classic', preview: '/assets/images/templates/classic.jpg' },
    { id: 'modern', name: 'Modern', preview: '/assets/images/templates/modern.jpg' },
    { id: 'sport', name: 'Sport', preview: '/assets/images/templates/sport.jpg' },
    { id: 'elegant', name: 'Élégant', preview: '/assets/images/templates/elegant.jpg' },
    // Ajouter vos templates ici
    { id: 'custom', name: 'Mon Template', preview: '/assets/images/templates/custom.jpg' }
];
```

### Modifier les options produit :

```javascript
// Dans renderStep2Options(), ligne 187
// Personnalisez les options selon le type de produit
const colOptions = ['Col rond', 'Col V', 'Col polo', 'Col montant', 'Sans col'];
const manchesOptions = ['Manches courtes', 'Manches longues', 'Sans manches', 'Manches 3/4'];
```

## 🧪 Test

Pour tester le configurateur :

1. Créer une page HTML de test
2. Inclure les CSS et JS
3. Ajouter un bouton avec `onclick="initConfigurateurProduit(productData)"`
4. Ouvrir dans le navigateur
5. Vérifier chaque étape
6. Tester la validation
7. Vérifier l'envoi (attention : emails réels !)

## 📊 Analytics

Le configurateur track automatiquement :

- `configurateur_ouvert` - Ouverture du configurateur
- `design_type_selected` - Choix du type de design
- `genre_selected` - Sélection du genre
- `preset_applied` - Utilisation d'un preset
- `step_completed` - Complétion de chaque étape
- `devis_submitted` - Soumission du devis final

Événements envoyés via `gtag()` si Google Analytics est configuré.

## 🔒 Validation

**Validations côté client** :
- Type de design obligatoire
- Template obligatoire si "Template" sélectionné
- Options col et manches obligatoires
- Genre obligatoire
- Au moins 1 taille avec quantité > 0
- Email valide
- Champs contact obligatoires

**Validations côté serveur** (PHP) :
- Format email
- Champs obligatoires
- Données produit
- Au moins 1 taille

## 💡 Exemples d'intégration

### Intégration WordPress :

```php
<?php
// Dans single-product.php
$product_data = array(
    'reference' => get_field('reference'),
    'nom' => get_the_title(),
    'sport' => get_field('sport'),
    'famille' => get_field('famille'),
    'photo' => get_the_post_thumbnail_url(),
    'tissu' => get_field('tissu'),
    'grammage' => get_field('grammage'),
    'prixBase' => get_field('prix_base')
);
?>

<script>
const productData = <?php echo json_encode($product_data); ?>;

function ouvrirConfigurateurProduit() {
    initConfigurateurProduit(productData);
}
</script>

<button class="btn-config" onclick="ouvrirConfigurateurProduit()">
    Configurer mon devis
</button>
```

### Intégration React :

```jsx
import React from 'react';

const ProductPage = ({ product }) => {
    const handleOpenConfig = () => {
        const productData = {
            reference: product.reference,
            nom: product.name,
            sport: product.sport,
            famille: product.family,
            photo: product.image,
            tissu: product.fabric,
            grammage: product.weight,
            prixBase: product.basePrice
        };

        window.initConfigurateurProduit(productData);
    };

    return (
        <button onClick={handleOpenConfig}>
            Configurer mon devis
        </button>
    );
};
```

## 🎯 Roadmap / Améliorations futures

- [ ] Upload de fichiers logos dans le configurateur
- [ ] Aperçu 3D du produit personnalisé
- [ ] Export PDF du devis
- [ ] Sauvegarde de configuration (reprise plus tard)
- [ ] Partage de configuration par URL
- [ ] Mode "commande rapide" (sans toutes les étapes)
- [ ] Intégration paiement en ligne
- [ ] Multi-produits dans un seul devis

## 📞 Support

Pour toute question sur l'intégration du configurateur :
- Email : contact@flare-custom.com
- Téléphone : +359 885 813 134

---

**Version** : 1.0.0
**Dernière mise à jour** : 2025
**Design System** : FLARE CUSTOM (#FF4B26 / #E63910)
