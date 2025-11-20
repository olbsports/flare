# 🎨 FLARE Admin - Interface d'Administration

Interface d'administration moderne pour gérer votre site FLARE Custom.

## 🚀 Accès

```
https://votre-site.com/admin/
```

## 📋 Fonctionnalités

### ⚡ Configuration du Configurateur Produit (NOUVEAU)

**Accès :** `admin/product-configurator.html`

Cette interface vous permet de configurer **de A à Z** le configurateur de devis pour chaque produit :

#### 🎨 Ce que vous pouvez configurer :

1. **Options générales**
   - ✅ Activer/désactiver les couleurs personnalisées
   - ✅ Activer/désactiver les logos (avec nombre max)
   - ✅ Activer/désactiver les textes
   - ✅ Activer/désactiver les numéros

2. **Couleurs disponibles**
   - ➕ Ajouter autant de couleurs que vous voulez
   - 🎨 Color picker visuel pour chaque couleur
   - ✏️ Édition du code HEX
   - 🗑️ Supprimer des couleurs

3. **Tailles disponibles**
   - ☑️ Cocher les tailles disponibles : XS, S, M, L, XL, XXL, 3XL, 4XL
   - 🔄 Activation/désactivation en un clic

4. **Options personnalisées**
   - ➕ Ajouter des options spécifiques par famille
   - **Exemples :**
     - Col : Rond, V, Polo
     - Manches : Courtes, Longues, Sans manches
     - Poches : Oui, Non
     - Fermeture : Zip, Boutons
   - ✏️ Éditer le nom et les valeurs
   - 🗑️ Supprimer des options

5. **Zones de personnalisation**
   - 📍 Définir où placer les logos/textes/numéros
   - **Exemples de zones :**
     - Poitrine gauche
     - Poitrine droite
     - Dos centre
     - Manche gauche
     - Etc.
   - Pour chaque zone : nom, types autorisés (logo/text/numero)

6. **Règles de prix**
   - 💰 Prix extra par logo (ex: 5€)
   - 💰 Prix extra par texte (ex: 2€)
   - 💰 Prix extra par numéro (ex: 3€)
   - 💰 Prix extra sublimation (ex: 0€)

7. **Quantités et délais**
   - 📊 Quantité minimum (ex: 1)
   - 📊 Quantité maximum (ex: 1000)
   - ⏱️ Délai de livraison en jours (ex: 21)

#### 📖 Comment utiliser :

1. **Rechercher un produit**
   ```
   Tapez la référence ou le nom : FLARE-BSKMAIH-372
   Cliquez sur "Rechercher"
   ```

2. **Le produit s'affiche avec ses infos**
   - Photo
   - Nom
   - Référence
   - Sport et Famille

3. **Modifier la configuration**
   - Tous les champs sont éditables
   - Les changements sont détectés automatiquement
   - Une barre de sauvegarde apparaît en bas

4. **Sauvegarder**
   ```
   Cliquez sur "Enregistrer la configuration"
   ✅ Confirmation de sauvegarde
   ```

5. **Résultat**
   - La configuration est sauvegardée en BDD
   - Le configurateur sur la page produit utilise automatiquement la nouvelle config !

---

### 📦 Gestion des Produits

**Accès :** `admin/products.html`

- Liste complète des ~1697 produits
- Recherche et filtres
- Édition des prix par paliers
- Gestion des photos (5 max par produit)
- Import/Export CSV

---

### 💰 Gestion des Devis

**Accès :** `admin/quotes.html`

- Liste de tous les devis générés
- Filtres par statut : pending, sent, accepted, rejected
- Export PDF/CSV
- Statistiques : total, en attente, acceptés...
- Vue détaillée de chaque devis avec le design

---

### 📄 Gestion des Pages

**Accès :** `admin/pages.html`

- Gestion des pages produits (~500)
- Gestion des pages d'information
- Édition du contenu
- SEO : meta title, description, keywords
- Gestion des URLs

---

### 🏗️ Page Builder

**Accès :** `admin/page-builder.html`

Constructeur de pages type Elementor :

**Blocs disponibles :**
- Hero section
- Features grid
- Testimonials
- Call-to-action
- Image gallery
- Text content
- Video embed
- Contact form

**Fonctionnalités :**
- Drag & drop des blocs
- Édition visuelle
- Templates pré-faits
- Responsive design
- Export HTML

---

### 🏷️ Catégories

**Accès :** `admin/categories.html`

- Gestion des sports (Basketball, Football, Rugby...)
- Gestion des familles (Maillot, Short, Veste...)
- Structure hiérarchique
- Réorganisation drag & drop

---

### 🖼️ Médiathèque

**Accès :** `admin/media.html`

- Upload de fichiers (images, PDF, SVG...)
- Recherche de médias
- Gestion des dossiers
- Optimisation automatique des images
- Génération de miniatures

---

### 🎯 Templates SVG

**Accès :** `admin/templates.html`

- Liste des templates de design
- Upload de nouveaux templates
- Catégorisation (maillot, short, veste...)
- Scan automatique du dossier `assets/templates/`

---

### ⚙️ Paramètres

**Accès :** `admin/settings.html`

- Informations entreprise
- Configuration email
- Prix par défaut
- Délais de livraison
- Paramètres SEO globaux
- Import/Export de toute la configuration

---

## 🔧 Configuration

### Prérequis

1. **Backend installé**
   ```bash
   # Vérifier que les APIs fonctionnent
   curl https://votre-site.com/api/products.php
   ```

2. **Base de données**
   ```bash
   # Schéma installé
   mysql -u USER -p DATABASE < database/schema.sql
   mysql -u USER -p DATABASE < database/schema-advanced.sql
   ```

3. **Données importées**
   ```bash
   php import-all.php
   php generate-product-configs.php
   ```

### Installation

1. **Uploadez le dossier admin/** sur votre serveur

2. **Configurez le chemin des APIs** (si besoin)
   - Les APIs sont attendues à `/api/`
   - Si différent, modifiez les URLs dans les fichiers JS

3. **Protégez l'accès** (recommandé)
   ```apache
   # Dans admin/.htaccess
   AuthType Basic
   AuthName "Admin FLARE"
   AuthUserFile /chemin/vers/.htpasswd
   Require valid-user
   ```

---

## 📊 Workflow recommandé

### Premier setup

1. **Importer les données**
   ```bash
   cd /chemin/vers/flare
   php import-all.php
   ```

2. **Générer les configs produits**
   ```bash
   php generate-product-configs.php
   ```

3. **Accéder à l'admin**
   ```
   https://votre-site.com/admin/
   ```

4. **Configurer quelques produits**
   - Allez dans "Configurateur Produit"
   - Testez avec 5-10 produits
   - Personnalisez les options selon vos besoins

5. **Tester le configurateur**
   - Allez sur une page produit
   - Cliquez sur "Devis gratuit"
   - Vérifiez que les options apparaissent correctement

### Utilisation quotidienne

**Pour ajouter un nouveau produit :**
1. Admin > Produits > Ajouter
2. Remplir les infos (nom, ref, sport, famille, prix...)
3. Uploader les photos
4. Aller dans Configurateur Produit
5. Rechercher le nouveau produit
6. Configurer les options
7. Sauvegarder

**Pour modifier les prix :**
1. Admin > Produits
2. Rechercher le produit
3. Modifier les paliers de prix
4. Sauvegarder
5. Le configurateur utilise automatiquement les nouveaux prix !

**Pour gérer un devis :**
1. Admin > Devis
2. Voir les nouveaux devis
3. Cliquer pour voir les détails
4. Exporter en PDF si besoin
5. Changer le statut (sent, accepted...)

---

## 🎨 Personnalisation

### Changer les couleurs de l'admin

Éditez les fichiers CSS dans chaque page ou créez un fichier `admin/assets/css/admin.css` :

```css
/* Couleur principale */
.admin-header {
    background: linear-gradient(135deg, #VotreCouleur1 0%, #VotreCouleur2 100%);
}

.btn-primary {
    background: #VotreCouleur;
}
```

### Ajouter de nouvelles fonctionnalités

1. Créez une nouvelle page HTML dans `admin/`
2. Utilisez les APIs existantes dans `/api/`
3. Suivez le même design pattern que les autres pages
4. Ajoutez un lien dans `admin/index.html`

---

## 🐛 Dépannage

### Le configurateur ne charge pas les données

**Vérifiez :**
1. L'API est accessible : `curl https://votre-site.com/api/configurator-data.php?action=product&reference=XXX`
2. Les configs ont été générées : `SELECT * FROM product_configurations LIMIT 10;`
3. La console navigateur (F12) pour les erreurs

### Les modifications ne sont pas sauvegardées

**Vérifiez :**
1. Les permissions d'écriture sur la BDD
2. Les erreurs dans la console (F12)
3. Le format JSON envoyé à l'API

### Erreur 404 sur les APIs

**Vérifiez :**
1. Le fichier `.htaccess` avec le rewrite vers `/api/`
2. Le mod_rewrite est activé sur Apache
3. Les chemins sont corrects

---

## 🚀 Performance

### Cache

Les APIs utilisent un système de cache automatique :
- Produits : cache 5 minutes
- Pages : cache 10 minutes
- Settings : cache 15 minutes

Pour vider le cache manuellement, supprimez les fichiers dans `/tmp/flare_cache_*` (si cache fichier).

### Optimisation

- Les listes de produits sont paginées (50 par défaut)
- Les images sont optimisées automatiquement
- Les requêtes BDD utilisent des indexes

---

## 📚 Documentation complète

- **Backend :** Voir `BACKEND_README.md`
- **Import :** Voir `GUIDE_IMPORT.md`
- **Configurateur :** Voir `MIGRATION_CONFIGURATEUR.md`
- **API :** Voir `API_DOCUMENTATION.md`

---

## 🎉 Support

Pour toute question ou problème :
1. Consultez d'abord cette documentation
2. Vérifiez les logs d'erreur PHP
3. Inspectez la console navigateur (F12)
4. Vérifiez que toutes les étapes d'installation ont été suivies

---

**Développé pour FLARE Custom** | Système complet de gestion e-commerce avec configurateur de devis
