# 🔐 GUIDE COMPLET - Admin Sécurisé + Contenu Dynamique

Ce guide explique comment utiliser le système d'authentification sécurisé et le système de contenu dynamique (HTML → BDD → Pages dynamiques).

---

## 📋 TABLE DES MATIÈRES

1. [Connexion à l'admin](#connexion-à-ladmin)
2. [Import du contenu HTML vers la BDD](#import-du-contenu-html-vers-la-bdd)
3. [Créer des pages dynamiques](#créer-des-pages-dynamiques)
4. [Protection des pages admin](#protection-des-pages-admin)
5. [Gestion des utilisateurs](#gestion-des-utilisateurs)
6. [Troubleshooting](#troubleshooting)

---

## 🔑 1. CONNEXION À L'ADMIN

### Accès à la page de connexion

```
https://ton-site.com/admin/login.php
```

### Identifiants par défaut

- **Username**: `admin`
- **Mot de passe**: `admin123`

⚠️ **IMPORTANT** : Changez ce mot de passe dès la première connexion !

### Que se passe-t-il lors de la connexion ?

1. Le système vérifie vos identifiants dans la table `users`
2. Si correct, une session sécurisée est créée
3. Vous êtes redirigé vers le **Dashboard admin**
4. La date `last_login` est mise à jour dans la BDD

### Rôles utilisateurs

- **admin** : Accès complet à toutes les fonctionnalités
- **editor** : Peut éditer le contenu mais pas les paramètres
- **viewer** : Lecture seule

---

## 📥 2. IMPORT DU CONTENU HTML VERS LA BDD

### Pourquoi importer le contenu ?

Actuellement, ton contenu est dans des fichiers HTML statiques. Pour le rendre **facilement éditable depuis l'admin**, on doit l'importer dans la base de données.

### Comment lancer l'import ?

#### Option 1 : Via le navigateur

```
https://ton-site.com/import-html-to-database.php
```

#### Option 2 : Via ligne de commande

```bash
php /chemin/vers/ton-site/import-html-to-database.php
```

### Que fait le script ?

1. **Scanne** tous les fichiers HTML dans `/pages/products/`
2. **Parse** chaque page pour extraire :
   - Titre (`<title>`, `<h1>`)
   - Meta description
   - Paragraphes (`<p>`)
   - Listes (`<ul>`, `<ol>`)
   - Images (`<img>`)
   - Tableaux (`<table>`)
   - Liens (`<a>`)
3. **Stocke** tout dans la table `content_blocks` au format JSON
4. **Crée ou met à jour** les blocs existants

### Résultat de l'import

Après l'import, tu verras :

```
📊 Statistiques
📄 Pages scannées: 32
✅ Blocks créés: 28
✏️ Blocks mis à jour: 4
❌ Erreurs: 0
```

### Où est stocké le contenu ?

Table : `content_blocks`

Colonnes :
- `block_key` : Identifiant unique (ex: `product_page_maillot`)
- `titre` : Titre extrait
- `contenu` : Tout le contenu au format JSON
- `active` : 1 = visible, 0 = caché

Exemple de `block_key` :
- `product_page_maillot` → /pages/products/maillot.html
- `page_home` → /index.html
- `page_about` → /pages/a-propos.html

---

## 🌐 3. CRÉER DES PAGES DYNAMIQUES

### Concept

Au lieu d'avoir des fichiers HTML statiques, tu crées des pages **PHP dynamiques** qui chargent leur contenu depuis la BDD.

### Avantages

✅ **Éditable depuis l'admin** (futur)
✅ **Une seule source de vérité** (la BDD)
✅ **Multilingue facile** (une page, plusieurs langues dans la BDD)
✅ **Versionning** (on peut garder l'historique des modifications)
✅ **Recherche facile** (tout est indexé dans la BDD)

### Étape 1 : Créer une page dynamique

**Exemple** : Créer une page "À propos" dynamique

1. Copie le template :

```bash
cp page-dynamic-template.php pages/a-propos.php
```

2. Édite `pages/a-propos.php` :

```php
<?php
require_once __DIR__ . '/../config/database.php';

// Clé du block à charger
$blockKey = 'page_about';  // ← Change ici !

// Le reste du code reste identique
// ...
?>
```

3. Accède à la page :

```
https://ton-site.com/pages/a-propos.php
```

Le contenu sera **automatiquement chargé depuis la BDD** !

### Étape 2 : Passer le block_key en paramètre

Tu peux aussi créer **UNE SEULE page** qui affiche n'importe quel contenu selon l'URL :

```php
// page.php
$blockKey = $_GET['page'] ?? 'page_home';
```

Puis :

```
https://ton-site.com/page.php?page=page_about
https://ton-site.com/page.php?page=product_page_maillot
https://ton-site.com/page.php?page=page_contact
```

### Étape 3 : URLs propres avec .htaccess

Tu peux créer des URLs propres :

```apache
# .htaccess
RewriteEngine On
RewriteRule ^page/(.*)$ page.php?page=$1 [L]
```

Résultat :

```
https://ton-site.com/page/about  →  page.php?page=about
https://ton-site.com/page/contact  →  page.php?page=contact
```

### Mode Debug

Ajoute `?debug=1` à l'URL pour voir tout le contenu JSON :

```
https://ton-site.com/page.php?page=page_about&debug=1
```

---

## 🔒 4. PROTECTION DES PAGES ADMIN

### Pages qui DOIVENT être protégées

Toutes les pages dans `/admin/` :
- `index.php`
- `products.php`
- `quotes.php`
- `configurator-admin-complete.html` (doit être converti en `.php`)
- `gestion-produits-complete.html` (doit être converti en `.php`)
- etc.

### Comment protéger une page ?

#### Option 1 : Inclure auth-check.php (recommandé)

En haut de chaque page admin PHP :

```php
<?php
require_once __DIR__ . '/auth-check.php';
// Votre code ici...
?>
```

C'est fait ! Si l'utilisateur n'est pas connecté, il sera redirigé vers `login.php`.

#### Option 2 : Utiliser la classe Auth

```php
<?php
require_once __DIR__ . '/../config/auth.php';

// Requiert connexion
Auth::requireAuth();

// Ou requiert admin
Auth::requireAdmin();

// Ou check manuel
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}
?>
```

### Convertir les pages HTML en PHP

Les pages `configurator-admin-complete.html` et `gestion-produits-complete.html` doivent être converties en `.php` pour pouvoir inclure la protection.

**Étapes** :

1. Renomme `.html` en `.php` :

```bash
mv admin/configurator-admin-complete.html admin/configurator-admin-complete.php
mv admin/gestion-produits-complete.html admin/gestion-produits-complete.php
```

2. Ajoute en haut de chaque fichier :

```php
<?php require_once __DIR__ . '/auth-check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
...
```

3. Mets à jour les liens dans `index.php` :

```php
<a href="configurator-admin-complete.php" class="nav-item">
    <span class="nav-icon">🔧</span>
    Configurateur
</a>
```

### Vérifier qui est connecté

Dans n'importe quelle page admin :

```php
<?php
require_once __DIR__ . '/auth-check.php';

echo "Bonjour, " . $current_user['username'];
echo "Rôle : " . $current_user['role'];
?>
```

---

## 👥 5. GESTION DES UTILISATEURS

### Créer un nouvel utilisateur

Via PHP (pour l'instant, en attendant l'interface admin) :

```php
<?php
require_once __DIR__ . '/config/auth.php';

$auth = Auth::getInstance();

$result = $auth->createUser(
    'john',              // username
    'john@email.com',    // email
    'motdepasse123',     // password (sera hashé)
    'editor'             // role (admin, editor, viewer)
);

if ($result['success']) {
    echo "✅ Utilisateur créé avec l'ID " . $result['user_id'];
} else {
    echo "❌ Erreur : " . $result['error'];
}
?>
```

### Changer le mot de passe

```php
<?php
require_once __DIR__ . '/config/auth.php';

$auth = Auth::getInstance();

$result = $auth->changePassword(
    1,                    // user_id
    'ancien_mdp',         // old password
    'nouveau_mdp'         // new password
);

if ($result['success']) {
    echo "✅ Mot de passe changé";
} else {
    echo "❌ Erreur : " . $result['error'];
}
?>
```

### Modifier directement dans la BDD

Via PHPMyAdmin :

1. Accède à la table `users`
2. Clique sur "Éditer" sur la ligne de l'utilisateur
3. Pour changer le mot de passe :

```sql
UPDATE users
SET password = '$2y$10$...'  -- Hash généré avec password_hash()
WHERE id = 1;
```

Pour générer un hash :

```php
echo password_hash('nouveau_mdp', PASSWORD_DEFAULT);
```

---

## 🔧 6. TROUBLESHOOTING

### Problème : "Page not found" après connexion

**Solution** : Vérifie que `index.php` existe dans `/admin/`

### Problème : Redirection infinie (login → index → login)

**Cause** : `index.php` n'a pas de protection auth ou session mal configurée

**Solution** :

1. Vérifie que `index.php` contient :

```php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}
```

2. Vérifie que les cookies de session sont activés dans le navigateur

### Problème : "Database connection failed"

**Solution** :

1. Vérifie `config/database.php`
2. Vérifie que les identifiants BDD sont corrects :
   - Host : `localhost`
   - Database : `sc1ispy2055_flare_custom`
   - User : `sc1ispy2055_flare_adm`
   - Password : (ton mot de passe)

### Problème : Import HTML → BDD plante

**Causes possibles** :

1. Timeout PHP trop court → Augmente dans `php.ini` :

```ini
max_execution_time = 300
```

2. Mémoire insuffisante :

```ini
memory_limit = 256M
```

3. Fichiers HTML mal formés → Vérifie les logs d'erreur

### Problème : Page dynamique affiche "Page introuvable"

**Cause** : Le `block_key` n'existe pas dans la BDD

**Solution** :

1. Vérifie que l'import a bien été fait
2. Liste les blocks disponibles :

```sql
SELECT block_key, titre FROM content_blocks WHERE active = 1;
```

3. Utilise exactement le même `block_key`

### Problème : Déconnexion automatique trop rapide

**Solution** : Augmente la durée de session dans `php.ini` :

```ini
session.gc_maxlifetime = 86400  ; 24 heures
```

Ou dans ton code :

```php
ini_set('session.gc_maxlifetime', 86400);
session_start();
```

---

## 📖 RÉCAPITULATIF COMPLET

### Fichiers créés

| Fichier | Description |
|---------|-------------|
| `config/auth.php` | Classe d'authentification |
| `admin/auth-check.php` | Protection rapide pour pages admin |
| `import-html-to-database.php` | Script d'import HTML → BDD |
| `page-dynamic-template.php` | Template pour pages dynamiques |
| `GUIDE-ADMIN-SECURISE.md` | Ce guide ! |

### Tables BDD utilisées

| Table | Usage |
|-------|-------|
| `users` | Utilisateurs admin |
| `content_blocks` | Contenu des pages |
| `products` | Produits |
| `product_configurator_settings` | Config du configurateur |
| `product_photos` | Photos produits |
| `templates` | Templates SVG |
| `size_guides` | Guides des tailles |

### Workflow complet

1. **Importer le contenu** : `import-html-to-database.php`
2. **Se connecter** : `admin/login.php`
3. **Accéder à l'admin** : `admin/index.php`
4. **Gérer les produits** : `admin/configurator-admin-complete.php`
5. **Créer des pages dynamiques** : Copier `page-dynamic-template.php`

---

## 🎉 C'EST PRÊT !

Tu as maintenant :

✅ **Système d'authentification sécurisé**
✅ **Import du contenu HTML vers BDD**
✅ **Pages dynamiques qui chargent depuis la BDD**
✅ **Protection de toutes les pages admin**
✅ **Gestion des utilisateurs**

**Prochaines étapes** :

1. Importe le schema SQL : `database/schema-configurator-complet.sql`
2. Lance l'import du contenu : `import-html-to-database.php`
3. Connecte-toi à l'admin : `admin/login.php`
4. Commence à gérer ton site !

---

**Besoin d'aide ?** Consulte ce guide ou demande à Claude ! 😊
