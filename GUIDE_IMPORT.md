# 📥 Guide d'import de toutes vos données dans la BDD

## 🎯 Vue d'ensemble

Ce guide vous explique comment importer **TOUTES** vos données existantes (produits, pages, blog, templates) dans votre base de données `sc1ispy2055_flare_custom`.

### 📦 Ce qui sera importé

1. **~1697 produits** depuis `assets/data/PRICING-FLARE-2025.csv`
2. **~500+ pages HTML** depuis `pages/produits/` et `pages/info/`
3. **Articles de blog** depuis `assets/data/blog-articles.json`
4. **Templates SVG** depuis `assets/templates/`

---

## ⚙️ Étape 1 : Configuration de la base de données

### 1.1 Vérifier la configuration

Éditez le fichier `config/database.php` et vérifiez ces paramètres :

```php
define('DB_HOST', 'localhost');  // ou votre hôte MySQL
define('DB_NAME', 'sc1ispy2055_flare_custom');
define('DB_USER', 'sc1ispy2055_flare');  // votre utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe');  // votre mot de passe
```

### 1.2 Vérifier que les tables existent

Connectez-vous à votre base de données et vérifiez que les tables sont créées :

```bash
mysql -u sc1ispy2055_flare -p sc1ispy2055_flare_custom

# Puis dans MySQL :
SHOW TABLES;
```

Vous devriez voir :
- `products`
- `categories`
- `pages`
- `quotes`
- `media`
- `templates`
- `settings`
- `users`

Si les tables n'existent pas, exécutez :

```bash
mysql -u sc1ispy2055_flare -p sc1ispy2055_flare_custom < database/schema.sql
```

---

## 🚀 Étape 2 : Import RAPIDE (tout en une fois)

### Option A : Import complet automatique

```bash
php import-all.php
```

Ce script va importer **TOUT** automatiquement en une seule commande ! Il affichera la progression en temps réel.

**Durée estimée** : 2-5 minutes

---

## 🔧 Étape 3 : Import étape par étape (si besoin)

Si vous préférez importer étape par étape, ou si l'import complet a échoué :

### 3.1 Importer les produits (prioritaire !)

```bash
php import-products.php
```

**Ce que ça fait :**
- Lit `assets/data/PRICING-FLARE-2025.csv`
- Importe ~1697 produits avec prix, photos, descriptions
- Met à jour les produits existants
- Génère automatiquement les slugs

**Résultat attendu :**
```
✅ Produits importés : 1697
🔄 Produits mis à jour : 0
⏭️  Produits ignorés : 0
```

### 3.2 Importer les pages

```bash
php import-pages.php
```

**Ce que ça fait :**
- Scanne `pages/produits/` et `pages/info/`
- Importe toutes les pages HTML
- Extrait automatiquement : titre, meta description, meta keywords
- Crée les pages dans la table `pages`

**Résultat attendu :**
```
✅ Pages importées : 500+
🔄 Pages mises à jour : 0
```

### 3.3 Importer les articles de blog

```bash
php import-blog.php
```

**Ce que ça fait :**
- Lit `assets/data/blog-articles.json`
- Importe tous les articles
- Génère le HTML de chaque article

**Résultat attendu :**
```
✅ Articles importés : 10+
🔄 Articles mis à jour : 0
```

### 3.4 Scanner les templates

Les templates sont importés automatiquement via l'API :

```bash
curl "http://votre-site.com/api/templates.php?scan=true"
```

---

## ✅ Étape 4 : Vérification

### 4.1 Vérifier les produits

**Via MySQL :**
```sql
SELECT COUNT(*) FROM products;
SELECT * FROM products LIMIT 10;
```

**Via l'API :**
```bash
curl "http://votre-site.com/api/products.php?limit=10"
```

### 4.2 Vérifier les pages

**Via MySQL :**
```sql
SELECT COUNT(*) FROM pages;
SELECT title, slug, type FROM pages LIMIT 10;
```

### 4.3 Vérifier les catégories

Les catégories sont automatiquement créées depuis le CSV (colonne SPORT et FAMILLE_PRODUIT).

**Via l'API :**
```bash
curl "http://votre-site.com/api/categories.php?type=sport"
curl "http://votre-site.com/api/categories.php?type=famille"
```

---

## 🎨 Étape 5 : Créer les catégories manquantes

Si besoin, créez des catégories supplémentaires :

```bash
curl -X POST "http://votre-site.com/api/categories.php" \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Football",
    "type": "sport",
    "description": "Équipements de football personnalisés",
    "active": true
  }'
```

---

## 🐛 Dépannage

### Erreur "Database connection failed"

**Solution :**
1. Vérifiez les identifiants dans `config/database.php`
2. Vérifiez que MySQL est en cours d'exécution
3. Testez la connexion :
```bash
mysql -u sc1ispy2055_flare -p -e "USE sc1ispy2055_flare_custom; SELECT 1;"
```

### Erreur "Table doesn't exist"

**Solution :**
```bash
mysql -u sc1ispy2055_flare -p sc1ispy2055_flare_custom < database/schema.sql
```

### Erreur "Duplicate entry"

C'est normal ! Les scripts détectent les doublons et font une mise à jour au lieu d'une insertion.

### Import trop lent

**Solution :**
- Augmentez `max_execution_time` dans php.ini
- Ou modifiez le script pour importer par lots

### Caractères spéciaux mal affichés

**Solution :**
```sql
-- Vérifier l'encodage
SHOW VARIABLES LIKE 'character_set%';

-- Forcer UTF-8
ALTER DATABASE sc1ispy2055_flare_custom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 📊 Statistiques attendues

Après un import complet, vous devriez avoir :

| Table | Nombre d'entrées |
|-------|-----------------|
| products | ~1697 |
| pages | ~500+ |
| categories | ~20-30 (auto-créées) |
| templates | Variable (selon dossier) |
| users | 1 (admin) |
| settings | 7 (par défaut) |

---

## 🔄 Ré-exécuter l'import

Vous pouvez ré-exécuter les scripts autant de fois que vous voulez :

- Les produits existants seront **mis à jour**
- Les pages existantes seront **mises à jour**
- Aucune donnée ne sera perdue
- Les doublons sont automatiquement gérés

---

## 🎯 Import sur serveur de production

### Via SSH

```bash
# Se connecter au serveur
ssh user@votre-serveur.com

# Aller dans le dossier du projet
cd /var/www/flare-custom

# Lancer l'import
php import-all.php
```

### Via cPanel / PhpMyAdmin

1. Uploadez les fichiers via FTP
2. Ouvrez phpMyAdmin
3. Créez un fichier `run-import.php` avec :

```php
<?php
require_once 'import-all.php';
```

4. Accédez à `http://votre-site.com/run-import.php`
5. **Supprimez le fichier après** pour des raisons de sécurité

---

## 🔒 Sécurité

⚠️ **IMPORTANT** : Après l'import, supprimez les scripts d'import du serveur de production :

```bash
rm import-all.php
rm import-products.php
rm import-pages.php
rm import-blog.php
```

Ou protégez-les avec un `.htaccess` :

```apache
<Files "import-*.php">
    Require all denied
</Files>
```

---

## 💡 Astuces

### Import incrémental

Si vous ajoutez de nouveaux produits au CSV, relancez simplement :

```bash
php import-products.php
```

Les nouveaux produits seront ajoutés, les existants seront mis à jour.

### Import en arrière-plan

```bash
nohup php import-all.php > import.log 2>&1 &
tail -f import.log
```

### Backup avant import

```bash
mysqldump -u sc1ispy2055_flare -p sc1ispy2055_flare_custom > backup_avant_import.sql
```

---

## 🎉 C'est fait !

Une fois l'import terminé, votre base de données contient **TOUTES** vos données !

### Prochaines étapes :

1. ✅ Testez vos APIs :
   - `GET /api/products.php`
   - `GET /api/categories.php`
   - `GET /api/templates.php`

2. ✅ Connectez votre frontend aux APIs

3. ✅ Configurez le configurateur de devis

4. ✅ Lancez votre site ! 🚀

---

## 📞 Support

Si vous rencontrez des problèmes :

1. Vérifiez les logs : `import.log` ou `php error.log`
2. Consultez la section Dépannage ci-dessus
3. Vérifiez que toutes les dépendances PHP sont installées

**Bon import ! 🎊**
