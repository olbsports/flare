# ✅ J'AI NETTOYÉ ET SIMPLIFIÉ TOUT LE SYSTÈME !

## 🧹 CE QUI A ÉTÉ VIRÉ (inutile pour l'instant)

**5 pages admin supprimées :**
- ❌ `admin/page-builder.html` (pas prioritaire)
- ❌ `admin/media.html` (pas prioritaire)
- ❌ `admin/templates.html` (pas prioritaire)
- ❌ `admin/settings.html` (pas prioritaire)
- ❌ `admin/pages.html` (pas prioritaire)

**5 fichiers backend supprimés :**
- ❌ `includes/PageBuilder.php`
- ❌ `includes/FormBuilder.php`
- ❌ `includes/Page.php`
- ❌ `api/page-builder.php`
- ❌ `api/pages.php`

**Total supprimé : 5300 lignes de code inutile !**

---

## ✅ CE QUI RESTE (l'ESSENTIEL qui fonctionne)

### Admin (4 pages seulement) :

1. **`admin/index.html`** - Dashboard principal
2. **`admin/product-configurator.html`** - ⭐ **LE PLUS IMPORTANT** : Configure le configurateur
3. **`admin/products.html`** - Gestion des produits
4. **`admin/quotes.html`** - Gestion des devis
5. **`admin/categories.html`** - Gestion des catégories

### Backend (l'essentiel) :

**Classes :**
- ✅ `includes/Product.php` - Gestion produits
- ✅ `includes/ProductConfig.php` - Config configurateur
- ✅ `includes/Quote.php` - Gestion devis
- ✅ `includes/Category.php` - Gestion catégories
- ✅ `includes/Media.php` - Gestion médias
- ✅ `includes/Template.php` - Gestion templates
- ✅ `includes/Settings.php` - Paramètres
- ✅ `includes/Auth.php` - Authentification

**APIs :**
- ✅ `api/products.php` - API produits
- ✅ `api/configurator-data.php` - **API du configurateur**
- ✅ `api/product-config.php` - API config produits
- ✅ `api/quotes.php` - API devis
- ✅ `api/categories.php` - API catégories
- ✅ `api/media.php` - API médias
- ✅ `api/templates.php` - API templates
- ✅ `api/settings.php` - API paramètres
- ✅ `api/auth.php` - API auth

---

## 🆕 NOUVEAUX FICHIERS POUR QUE ÇA MARCHE

### 1. **`test-connexion-simple.php`** ⭐

**Ce fichier teste la connexion à ta BDD et te dit exactement ce qui ne va pas !**

**Utilise-le comme ça :**
```
https://ton-site.com/test-connexion-simple.php
```

**Ce qu'il fait :**
- ✅ Vérifie la connexion BDD
- ✅ Liste les tables présentes
- ✅ Compte les produits, devis, etc.
- ✅ Affiche des exemples de données
- ✅ Te dit exactement quoi faire si problème

---

### 2. **`import-produits-simple.php`** ⭐

**Ce fichier importe tes produits depuis le CSV de manière SIMPLE et VISUELLE !**

**Utilise-le comme ça :**
```
https://ton-site.com/import-produits-simple.php
```

**Ce qu'il fait :**
- ✅ Vérifie la connexion BDD
- ✅ Vérifie que les tables existent
- ✅ Lit le CSV
- ✅ Importe tous les produits (avec progression affichée)
- ✅ Affiche un résumé (combien créés, mis à jour, erreurs)
- ✅ Affiche des exemples de produits importés

---

### 3. **`INSTALLATION_SIMPLE.md`** 📚

**Guide d'installation en 5 ÉTAPES SIMPLES (pas compliqué) !**

**Les 5 étapes :**
1. Mettre ton mot de passe MySQL dans `config/database.php`
2. Tester la connexion avec `test-connexion-simple.php`
3. Importer les tables SQL via PHPMyAdmin
4. Importer les produits avec `import-produits-simple.php`
5. Générer les configs avec `generate-product-configs.php`

**C'est TOUT !**

---

## 🎯 QUE FAIRE MAINTENANT ?

### ÉTAPE 1 : Configurer ton mot de passe MySQL

**Ouvre :** `config/database.php`

**Trouve la ligne 26 :**
```php
define('DB_PASS', '');
```

**Remplace par ton VRAI mot de passe :**
```php
define('DB_PASS', 'ton_mot_de_passe_mysql');
```

**Enregistre !**

---

### ÉTAPE 2 : Tester la connexion

**Dans ton navigateur :**
```
https://ton-site.com/test-connexion-simple.php
```

**Tu DOIS voir :**
```
✅ CONNEXION RÉUSSIE !
```

**Si tu vois une erreur :**
- Retourne à l'étape 1
- Vérifie que ton mot de passe est correct
- Vérifie dans cPanel > MySQL Databases que :
  - La base `sc1ispy2055_flare_custom` existe
  - L'utilisateur `sc1ispy2055_flare` a les droits sur cette base

---

### ÉTAPE 3 : Importer les tables

**Va dans cPanel > PHPMyAdmin**

1. Clique sur `sc1ispy2055_flare_custom` (ta base)
2. Clique sur "Importer"
3. Choisis `database/schema.sql`
4. Clique "Exécuter"
5. Tu dois voir : "Import réussi, 8 requêtes exécutées"

**Répète pour :** `database/schema-advanced.sql`

**Vérification :**
- Retourne sur `test-connexion-simple.php`
- Tu dois voir **15 tables** listées

---

### ÉTAPE 4 : Importer les produits

**Dans ton navigateur :**
```
https://ton-site.com/import-produits-simple.php
```

**Attends 1-3 minutes** (c'est normal)

**Tu DOIS voir à la fin :**
```
✅ IMPORT TERMINÉ !
Créés : XXX produits
```

---

### ÉTAPE 5 : Générer les configurations

**Dans ton navigateur :**
```
https://ton-site.com/generate-product-configs.php
```

**Attends 1-2 minutes**

**Tu DOIS voir :**
```
✅ GÉNÉRATION TERMINÉE !
Créées : XXX configurations
```

---

## 🎉 C'EST PRÊT !

### Accède à ton admin :

```
https://ton-site.com/admin/
```

### Tu verras 4 sections :

**1. 🎨 Configurateur Produit** ⭐ **LE PLUS IMPORTANT**

C'est ICI que tu peux configurer TOUT ton configurateur pour chaque produit :
- Couleurs disponibles (avec color picker)
- Tailles disponibles (cocher les cases)
- Options personnalisées (col, manches, poches, fermeture...)
- Zones de personnalisation (où mettre logos/textes/numéros)
- Prix des options (logo +5€, texte +2€...)
- Quantités min/max
- Délai de livraison

**Comment l'utiliser :**
1. Va sur `admin/product-configurator.html`
2. Recherche un produit (tape la référence, ex: FLARE-BSKMAIH-372)
3. Configure tout ce que tu veux
4. Clique "Enregistrer"
5. C'est sauvegardé en BDD !
6. Le configurateur sur ton site utilise automatiquement cette config !

**2. 📦 Gestion des Produits**

Liste de tous tes produits, tu peux :
- Modifier les prix
- Changer les descriptions
- Upload les photos
- Ajouter/supprimer des produits

**3. 💰 Gestion des Devis**

Voir tous les devis générés par les clients :
- Changer les statuts (pending → sent → accepted)
- Voir les détails complets
- Exporter (PDF bientôt)

**4. 🏷️ Catégories**

Gérer les sports et familles de produits

---

## 🐛 PROBLÈMES FRÉQUENTS

### "Connexion BDD échoue"

➡️ **Solution :**
1. Ouvre `config/database.php`
2. Ligne 26, vérifie le mot de passe
3. Enregistre
4. Reteste `test-connexion-simple.php`

---

### "Table 'products' doesn't exist"

➡️ **Solution :**
1. Va dans PHPMyAdmin
2. Importe `database/schema.sql`
3. Puis importe `database/schema-advanced.sql`
4. Reteste `test-connexion-simple.php`

---

### "Fichier CSV non trouvé"

➡️ **Solution :**
1. Vérifie que le dossier `assets/data/` existe
2. Vérifie que le fichier `PRICING-FLARE-2025.csv` est dedans
3. Si pas, upload-le via FTP ou cPanel File Manager

---

### "Admin pages are blank / Admin ne marche pas"

➡️ **Solution :**
1. Ouvre la console du navigateur (touche F12, onglet "Console")
2. Regarde les erreurs
3. Si erreur 404 sur `/api/...` → vérifie que le dossier `/api/` existe avec tous les fichiers PHP
4. Si erreur CORS → ajoute un fichier `.htaccess` à la racine :
   ```apache
   <IfModule mod_headers.c>
       Header set Access-Control-Allow-Origin "*"
       Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
   </IfModule>
   ```

---

### "API returns 404"

➡️ **Solution :**
1. Vérifie que tous les fichiers dans `/api/` sont bien uploadés
2. Vérifie les permissions : 755 pour le dossier, 644 pour les fichiers
3. Teste directement dans le navigateur :
   ```
   https://ton-site.com/api/products.php?limit=5
   ```
   Tu dois voir du JSON, pas une erreur 404

---

## 📂 FICHIERS QUI DOIVENT ÊTRE SUR TON SERVEUR

**MINIMUM REQUIS :**

```
ton-site.com/
├── config/
│   └── database.php              ← AVEC TON MOT DE PASSE (ligne 26)
│
├── database/
│   ├── schema.sql                ← À importer via PHPMyAdmin
│   └── schema-advanced.sql       ← À importer via PHPMyAdmin
│
├── includes/                     ← TOUS les fichiers PHP
│   ├── Product.php
│   ├── ProductConfig.php
│   ├── Quote.php
│   ├── Category.php
│   ├── Media.php
│   ├── Template.php
│   ├── Settings.php
│   └── Auth.php
│
├── api/                          ← TOUS les fichiers PHP
│   ├── products.php
│   ├── configurator-data.php     ← IMPORTANT pour le configurateur
│   ├── product-config.php
│   ├── quotes.php
│   ├── categories.php
│   ├── media.php
│   ├── templates.php
│   ├── settings.php
│   └── auth.php
│
├── admin/                        ← TOUS les fichiers HTML
│   ├── index.html
│   ├── product-configurator.html ← LE PLUS IMPORTANT
│   ├── products.html
│   ├── quotes.html
│   └── categories.html
│
├── assets/
│   ├── data/
│   │   └── PRICING-FLARE-2025.csv ← Tes produits
│   └── js/
│       ├── configurateur-produit.js
│       └── configurateur-produit-api.js
│
├── test-connexion-simple.php     ← Pour tester
├── import-produits-simple.php    ← Pour importer
├── generate-product-configs.php  ← Pour générer configs
│
└── INSTALLATION_SIMPLE.md        ← Guide d'installation
```

---

## 📞 BESOIN D'AIDE ?

**Fais dans cet ordre :**

1. **Teste d'abord** `test-connexion-simple.php`
   - Si ça marche → passe à l'étape suivante
   - Si ça marche pas → donne-moi le message d'erreur EXACT

2. **Vérifie que tous les fichiers sont uploadés**
   - Surtout `/api/` et `/includes/`

3. **Regarde la console du navigateur**
   - F12 > Console
   - Copie les erreurs rouges

4. **Vérifie les permissions**
   - Dossiers : 755
   - Fichiers : 644

5. **Si rien ne marche**, donne-moi :
   - Le message d'erreur de `test-connexion-simple.php`
   - Les erreurs de la console (F12)
   - Une capture d'écran de ce que tu vois

---

## ✨ RÉSUMÉ

**J'ai NETTOYÉ et SIMPLIFIÉ tout le système :**

✅ Viré 5300 lignes de code inutile
✅ Gardé que l'ESSENTIEL (configurateur, produits, devis)
✅ Créé 2 fichiers de test/import SIMPLES
✅ Créé un guide en 5 ÉTAPES SIMPLES

**TON SYSTÈME EST PRÊT :**

1. Configure le mot de passe MySQL → `config/database.php` ligne 26
2. Teste → `test-connexion-simple.php`
3. Importe tables → PHPMyAdmin
4. Importe produits → `import-produits-simple.php`
5. Génère configs → `generate-product-configs.php`
6. Admin prêt → `admin/`

**ÇA DOIT MARCHER MAINTENANT ! 🚀**

Si problème, utilise `test-connexion-simple.php` qui te dira EXACTEMENT ce qui ne va pas !
