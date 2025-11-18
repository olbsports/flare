# 📦 Système de Pages Produits Dynamiques - FLARE CUSTOM

## 🎯 Vue d'ensemble

Ce système permet d'afficher automatiquement tous les produits du catalogue FLARE CUSTOM avec des pages produits individuelles optimisées, générées dynamiquement à partir du fichier CSV.

## 📁 Structure des fichiers

```
flare/
├── assets/
│   ├── data/
│   │   └── PRICING-FLARE-2025.csv         # Base de données produits
│   └── js/
│       ├── csv-parser.js                  # Parser CSV (déjà existant)
│       ├── product-page-loader.js         # 🆕 Charge les données produit
│       └── product-cards-linker.js        # 🆕 Rend les cartes cliquables
├── pages/
│   ├── produit.html                       # 🆕 Template de page produit
│   └── products/
│       ├── equipement-football-personnalise-sublimation.html (mis à jour)
│       ├── equipement-rugby-personnalise-sublimation.html (mis à jour)
│       ├── equipement-basketball-personnalise-sublimation.html (mis à jour)
│       └── ... (toutes les pages de famille mises à jour)
```

## 🔄 Comment ça fonctionne

### 1. Pages de Famille de Produits (ex: Football, Rugby, etc.)

Les pages de famille affichent des cartes produits en dur (HTML statique). Le script `product-cards-linker.js` :

- ✅ Détecte automatiquement toutes les cartes produits (`.product-card`)
- ✅ Extrait la référence FLARE depuis l'URL de l'image (ex: `FLARE-FTBMAIH-316`)
- ✅ Rend chaque carte cliquable vers `/pages/produit.html?ref=FLARE-FTBMAIH-316`
- ✅ Ajoute des effets hover et un indicateur visuel

**Exemple d'URL générée automatiquement :**
```
https://flare-custom.com/pages/produit.html?ref=FLARE-FTBMAIH-316
```

### 2. Page Produit Individuelle (`/pages/produit.html`)

Cette page unique affiche TOUS les produits du catalogue. Elle est dynamique et se base sur le paramètre `?ref=` dans l'URL.

**Le script `product-page-loader.js` :**

1. 📥 Lit le paramètre `?ref=` de l'URL
2. 📥 Charge le fichier CSV `/assets/data/PRICING-FLARE-2025.csv`
3. 🔍 Trouve le produit correspondant à la référence
4. 🎨 Remplit dynamiquement tous les champs :
   - Titre, description, prix
   - Galerie photos (5 photos max)
   - Specs techniques (grammage, tissu, genre, etc.)
   - Paliers de prix dégressifs
   - Meta tags SEO (title, description, Open Graph, Schema.org)
   - Fil d'Ariane (breadcrumb)

## 🆕 Fichiers ajoutés/modifiés

### ✨ Nouveaux fichiers créés

1. **`/pages/produit.html`**
   - Template de page produit optimisée
   - Design moderne et responsive
   - SEO-friendly avec meta tags dynamiques
   - Schema.org JSON-LD pour rich snippets Google

2. **`/assets/js/product-page-loader.js`**
   - Charge les données du produit depuis le CSV
   - Remplit dynamiquement la page
   - Gère les erreurs (produit introuvable, CSV non chargé)

3. **`/assets/js/product-cards-linker.js`**
   - Rend les cartes produits cliquables automatiquement
   - Ne nécessite aucune modification du HTML existant
   - Fonctionne avec n'importe quelle page de famille

### 🔧 Fichiers modifiés

Toutes les pages de famille de produits ont été mises à jour avec l'ajout du script `product-cards-linker.js` :

- ✅ `equipement-football-personnalise-sublimation.html`
- ✅ `equipement-rugby-personnalise-sublimation.html`
- ✅ `equipement-basketball-personnalise-sublimation.html`
- ✅ `equipement-handball-personnalise-sublimation.html`
- ✅ `equipement-volleyball-personnalise-sublimation.html`
- ✅ `equipement-running-course-pied-personnalise.html`
- ✅ `equipement-cyclisme-velo-personnalise-sublimation.html`
- ✅ `equipement-triathlon-personnalise-sublimation.html`
- ✅ `equipement-petanque-personnalise-club.html`
- ✅ `sportswear-vetements-sport-personnalises.html`
- ✅ `merchandising-accessoires-club-personnalises.html`

## 📊 Structure du CSV

Le fichier `PRICING-FLARE-2025.csv` contient toutes les données produits :

```csv
SPORT;FAMILLE_PRODUIT;CODE;DESCRIPTION;QTY_1;QTY_5;...;REFERENCE_FLARE;DESCRIPTION_SEO;PHOTO_1;PHOTO_2;...;URL
```

**Champs importants utilisés :**
- `REFERENCE_FLARE` : Identifiant unique (ex: `FLARE-FTBMAIH-316`)
- `TITRE_VENDEUR` : Nom du produit
- `DESCRIPTION_SEO` : Description longue
- `PHOTO_1` à `PHOTO_5` : URLs des photos
- `QTY_1`, `QTY_5`, ..., `QTY_500` : Paliers de prix
- `SPORT`, `FAMILLE_PRODUIT`, `GRAMMAGE`, `TISSU`, `GENRE`, etc.

## 🎨 Fonctionnalités de la page produit

### SEO Optimisé
- ✅ Meta title dynamique avec prix et nom produit
- ✅ Meta description riche (extrait de `DESCRIPTION_SEO`)
- ✅ URL canonique avec référence produit
- ✅ Open Graph pour partage réseaux sociaux
- ✅ Schema.org JSON-LD pour rich snippets Google (étoiles, prix dans résultats)

### Interface utilisateur
- ✅ Galerie photos avec thumbnails cliquables
- ✅ Prix dégressifs affichés en tableau clair
- ✅ Badge d'économie (ex: "Économisez jusqu'à 19,44€ par pièce")
- ✅ Specs techniques en grille
- ✅ Breadcrumb (fil d'Ariane) avec liens vers sport/famille
- ✅ Boutons CTA (Email + WhatsApp)
- ✅ Description produit formatée
- ✅ Features FLARE (sublimation, fabrication Europe, etc.)

### Performance
- ✅ Loading state pendant chargement CSV
- ✅ Gestion d'erreurs (produit introuvable, CSV non chargé)
- ✅ Images optimisées webp
- ✅ Responsive mobile-first

## 🔗 Exemple de flux utilisateur

1. **Utilisateur visite** : `/pages/products/equipement-football-personnalise-sublimation.html`
2. **Voit** : Grille de cartes produits (maillots, shorts, kits)
3. **Clique sur une carte** : Le script `product-cards-linker.js` détecte le clic
4. **Redirigé vers** : `/pages/produit.html?ref=FLARE-FTBMAIH-316`
5. **Page produit** : Se charge et affiche toutes les infos du produit depuis le CSV
6. **Utilisateur peut** :
   - Voir 5 photos du produit
   - Consulter les prix dégressifs (1 à 500+ pièces)
   - Voir les specs techniques (grammage, tissu, etc.)
   - Demander un devis par email ou WhatsApp

## 🚀 Avantages du système

### Pour le développement
- ✅ **1 seule page produit** pour tous les produits (au lieu de 1698 pages !)
- ✅ **Mise à jour centralisée** : modifier le CSV met à jour tous les produits
- ✅ **Pas de code dupliqué** : template unique
- ✅ **Maintenance facile** : corriger un bug = corriger 1 fichier

### Pour le SEO
- ✅ **URLs uniques** pour chaque produit (`?ref=FLARE-XXX-YYY`)
- ✅ **Meta tags optimisés** automatiquement
- ✅ **Schema.org** pour rich snippets Google
- ✅ **Breadcrumb** pour navigation et SEO
- ✅ **Canonical URLs** pour éviter contenu dupliqué

### Pour l'utilisateur
- ✅ **Navigation fluide** : clic sur carte = page produit instantanée
- ✅ **Informations complètes** : photos, prix, specs, description
- ✅ **Design moderne** : responsive, élégant, professionnel
- ✅ **CTA clairs** : email + WhatsApp pour devis

## 🔧 Maintenance

### Ajouter un nouveau produit
1. Ajouter une ligne dans `PRICING-FLARE-2025.csv` avec tous les champs
2. S'assurer que les photos sont uploadées sur `https://flare-custom.com/photos/produits/`
3. Ajouter la carte produit en HTML dans la page de famille concernée
4. ✅ Le système génère automatiquement la page produit !

### Modifier un produit existant
1. Modifier la ligne correspondante dans le CSV
2. ✅ Le changement est immédiat sur la page produit !

### Ajouter une nouvelle page de famille
1. Créer la page HTML avec les cartes produits
2. Ajouter le script `product-cards-linker.js` avant la balise `</body>` :
```html
<script src="../../assets/js/product-cards-linker.js" defer></script>
```
3. ✅ Toutes les cartes deviennent automatiquement cliquables !

## 🐛 Dépannage

### Les cartes ne sont pas cliquables
- Vérifier que le script `product-cards-linker.js` est bien inclus dans la page
- Vérifier la console JavaScript pour les erreurs
- S'assurer que les images ont bien le format `FLARE-XXX-YYY-N.webp`

### Page produit vide ou erreur "Produit non trouvé"
- Vérifier que la référence dans l'URL existe dans le CSV
- Vérifier que le CSV est accessible à `/assets/data/PRICING-FLARE-2025.csv`
- Vérifier la console pour voir les logs de chargement

### Photos ne s'affichent pas
- Vérifier que les URLs des photos dans le CSV sont correctes
- Vérifier que les photos existent sur `https://flare-custom.com/photos/produits/`
- S'assurer du format : `FLARE-XXX-YYY-1.webp` à `FLARE-XXX-YYY-5.webp`

## 📝 Notes importantes

- ⚠️ La colonne `URL` du CSV n'est PAS utilisée (on génère dynamiquement avec `?ref=`)
- ⚠️ Les cartes produits dans les pages de famille restent en HTML dur (pas générées dynamiquement)
- ⚠️ Le script `product-cards-linker.js` doit être chargé avec `defer` pour attendre le DOM

## 🎉 Résultat final

- 🚀 **+1600 produits** accessibles avec **1 seule page template**
- ⚡ **Chargement rapide** grâce au système de cache du CSV parser
- 🎨 **Design professionnel** pour toutes les pages produits
- 🔍 **SEO optimisé** pour meilleur référencement Google
- 💼 **Maintenance simplifiée** : 1 fichier CSV à gérer

---

**Créé par Claude Code - Optimisation e-commerce FLARE CUSTOM**
*Date : Novembre 2025*
