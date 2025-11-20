# 🚀 INSTALLATION SIMPLE - 5 ÉTAPES

**IMPORTANT : Lis et suis ces 5 étapes dans l'ordre !**

---

## ✅ ÉTAPE 1 : METTRE TON MOT DE PASSE MYSQL

1. **Ouvre le fichier** `config/database.php` (avec un éditeur de texte ou cPanel File Manager)

2. **Trouve la ligne 26 :**
   ```php
   define('DB_PASS', '');
   ```

3. **Remplace par TON vrai mot de passe MySQL :**
   ```php
   define('DB_PASS', 'ton_mot_de_passe_ici');
   ```

4. **Enregistre le fichier**

**Comment trouver ton mot de passe MySQL ?**
- C'est le mot de passe que tu as défini dans cPanel > MySQL Databases
- Ou dans ton email de bienvenue o2switch
- Si tu ne le connais pas : dans cPanel > MySQL Databases, change le mot de passe de l'utilisateur `sc1ispy2055_flare`

---

## ✅ ÉTAPE 2 : TESTER LA CONNEXION BDD

1. **Dans ton navigateur, va sur :**
   ```
   https://ton-site.com/test-connexion-simple.php
   ```

2. **Tu DOIS voir :**
   ```
   ✅ CONNEXION RÉUSSIE !
   ```

**Si tu vois une erreur :**
- Retourne à l'étape 1
- Vérifie que le mot de passe est correct
- Vérifie que la base `sc1ispy2055_flare_custom` existe dans cPanel > MySQL Databases
- Vérifie que l'utilisateur `sc1ispy2055_flare` a les droits sur cette base

---

## ✅ ÉTAPE 3 : IMPORTER LES TABLES EN BDD

**Tu as 2 méthodes :**

### Méthode A : Via PHPMyAdmin (la plus simple)

1. **Va dans cPanel > PHPMyAdmin**

2. **Clique sur ta base** `sc1ispy2055_flare_custom` dans la colonne de gauche

3. **Clique sur l'onglet "Importer"** en haut

4. **Clique "Choisir un fichier"**

5. **Sélectionne** `database/schema.sql`

6. **Clique "Exécuter"** en bas

7. **Tu dois voir :** "Import réussi, 8 requêtes exécutées"

8. **Répète pour** `database/schema-advanced.sql`

9. **Tu dois voir :** "Import réussi, 7 requêtes exécutées"

### Méthode B : Via SSH (si tu as accès SSH)

```bash
cd /home/sc1ispy2055/public_html
mysql -u sc1ispy2055_flare -p sc1ispy2055_flare_custom < database/schema.sql
mysql -u sc1ispy2055_flare -p sc1ispy2055_flare_custom < database/schema-advanced.sql
```

**Vérification :**
- Retourne sur `test-connexion-simple.php`
- Tu dois voir **15 tables** listées

---

## ✅ ÉTAPE 4 : IMPORTER LES PRODUITS

1. **Dans ton navigateur, va sur :**
   ```
   https://ton-site.com/import-produits-simple.php
   ```

2. **Attends 1-3 minutes** (c'est normal, ça importe ~1697 produits)

3. **Tu DOIS voir à la fin :**
   ```
   ✅ IMPORT TERMINÉ !
   Créés : XXXX produits
   ```

4. **Un tableau avec des exemples de produits** doit s'afficher

**Si erreur :**
- Vérifie que le fichier `assets/data/PRICING-FLARE-2025.csv` existe
- Vérifie que l'étape 3 (tables) a bien été faite

---

## ✅ ÉTAPE 5 : GÉNÉRER LES CONFIGURATIONS

1. **Dans ton navigateur, va sur :**
   ```
   https://ton-site.com/generate-product-configs.php
   ```

2. **Attends 1-2 minutes**

3. **Tu DOIS voir :**
   ```
   ✅ GÉNÉRATION TERMINÉE !
   Créées : XXXX configurations
   ```

---

## 🎉 C'EST FINI ! TON ADMIN EST PRÊT !

### Accède à ton admin :

```
https://ton-site.com/admin/
```

### Tu verras 4 sections :

1. **🎨 Configurateur Produit** ⭐ **LE PLUS IMPORTANT**
   - Configure couleurs, tailles, options pour chaque produit
   - C'est ICI que tu peux TOUT configurer de A à Z

2. **📦 Gestion des Produits**
   - Liste de tous tes produits
   - Modifier les prix, photos, descriptions

3. **💰 Gestion des Devis**
   - Voir tous les devis générés par les clients
   - Changer les statuts, exporter

4. **🏷️ Catégories**
   - Gérer les sports et familles

---

## 🎯 COMMENT UTILISER LE CONFIGURATEUR

### Pour configurer un produit :

1. **Va sur** `https://ton-site.com/admin/product-configurator.html`

2. **Recherche un produit** (tape la référence, ex: FLARE-BSKMAIH-372)

3. **Configure TOUT ce que tu veux :**
   - ✅ Couleurs disponibles (avec color picker)
   - ✅ Tailles disponibles (cocher les cases)
   - ✅ Options personnalisées (col, manches, poches...)
   - ✅ Zones de personnalisation (où mettre logos/textes)
   - ✅ Prix des options (logo +5€, texte +2€...)
   - ✅ Quantités min/max
   - ✅ Délai de livraison

4. **Clique "Enregistrer"**

5. **C'est sauvegardé !** Le configurateur sur ton site utilisera automatiquement cette config !

---

## 🐛 PROBLÈMES ?

### "Connexion BDD échoue"
➡️ Vérifie le mot de passe dans `config/database.php` ligne 26

### "Table products doesn't exist"
➡️ Refais l'étape 3 (import des schémas SQL)

### "Fichier CSV non trouvé"
➡️ Vérifie que `assets/data/PRICING-FLARE-2025.csv` existe

### "Admin pages are blank"
➡️ Ouvre la console du navigateur (F12 > Console) et regarde les erreurs
➡️ Vérifie que les fichiers `/api/*.php` sont bien uploadés

### "API returns 404"
➡️ Vérifie que le dossier `/api/` existe avec tous les fichiers PHP dedans

---

## 📂 FICHIERS ESSENTIELS

**Ces fichiers DOIVENT être sur ton serveur :**

```
ton-site.com/
├── config/
│   └── database.php              ← AVEC TON MOT DE PASSE
│
├── database/
│   ├── schema.sql                ← À importer en BDD
│   └── schema-advanced.sql       ← À importer en BDD
│
├── includes/                     ← Tous les fichiers PHP
│   ├── Product.php
│   ├── ProductConfig.php
│   ├── Quote.php
│   └── Category.php
│
├── api/                          ← Tous les fichiers PHP
│   ├── products.php
│   ├── configurator-data.php
│   ├── product-config.php
│   ├── quotes.php
│   └── categories.php
│
├── admin/                        ← Tous les fichiers HTML
│   ├── index.html
│   ├── product-configurator.html ← LE PLUS IMPORTANT
│   ├── products.html
│   ├── quotes.html
│   └── categories.html
│
├── assets/data/
│   └── PRICING-FLARE-2025.csv    ← Tes produits
│
├── test-connexion-simple.php     ← Pour tester
├── import-produits-simple.php    ← Pour importer
└── generate-product-configs.php  ← Pour générer configs
```

---

## 📞 AIDE

**Si ça ne marche toujours pas :**

1. **Teste d'abord** `test-connexion-simple.php` et donne-moi le message d'erreur exact
2. **Vérifie** que tous les fichiers sont bien uploadés
3. **Regarde** la console du navigateur (F12) pour les erreurs JavaScript
4. **Vérifie** les permissions des fichiers (755 pour les dossiers, 644 pour les fichiers)

---

**C'est TOUT ! Si tu as suivi ces 5 étapes, ça DOIT marcher ! 🚀**
