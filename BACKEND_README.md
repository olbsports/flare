# 🚀 FLARE CUSTOM - Backend Complet

## 📦 Ce qui a été créé

Votre backend complet pour gérer tous les aspects de votre site FLARE CUSTOM est maintenant prêt !

### ✅ Classes PHP (dans `/includes/`)

1. **Database.php** - Connexion singleton à la base de données
2. **Product.php** - Gestion complète des produits
3. **Category.php** - Gestion des catégories (sports et familles)
4. **Quote.php** - Gestion des devis clients
5. **Media.php** - Bibliothèque médias avec upload
6. **Template.php** - Gestion des templates SVG/PNG
7. **Settings.php** - Paramètres du site avec cache
8. **Auth.php** - Authentification et permissions

### ✅ APIs REST (dans `/api/`)

1. **products.php** - CRUD produits
2. **categories.php** - CRUD catégories avec arbre hiérarchique
3. **quotes.php** - CRUD devis avec statistiques
4. **media.php** - Upload et gestion de médias
5. **templates.php** - Upload et scan automatique de templates
6. **settings.php** - Gestion des paramètres avec import/export
7. **auth.php** - Authentification, login, logout, gestion utilisateurs

### ✅ Documentation

- **API_DOCUMENTATION.md** - Documentation complète de toutes les APIs
- **BACKEND_README.md** - Ce fichier

---

## 🎯 Fonctionnalités principales

### 🏷️ Produits
- CRUD complet (Create, Read, Update, Delete)
- Filtrage par sport, famille, recherche
- Pagination automatique
- Multi-prix (1, 5, 10, 20, 50, 100, 250, 500 pièces)
- 5 photos par produit
- SEO optimisé (meta_title, meta_description, slug)
- Import CSV intégré
- Soft delete (désactivation)

### 📂 Catégories
- Deux types : Sports et Familles
- Hiérarchie parent/enfant
- Arbre complet en une requête
- Récupération des produits par catégorie
- Génération automatique de slug

### 💰 Devis
- Génération automatique de référence (DEV-YYYYMMDD-XXXXXX)
- Gestion des statuts (pending, sent, accepted, rejected, completed)
- Stockage JSON pour options, tailles, personnalisation
- Statistiques complètes (revenus, moyennes, etc.)
- Filtrage avancé

### 🖼️ Médias
- Upload de fichiers (images, documents, etc.)
- Génération automatique de miniatures
- Métadonnées (alt_text, title, description)
- Détection automatique du type MIME
- Gestion des dimensions pour les images

### 🎨 Templates
- Upload de templates SVG/PNG/JPG
- Scan automatique du dossier templates
- Lecture du contenu SVG via API
- Tags pour filtrage
- Soft et hard delete

### ⚙️ Paramètres
- Système de clé/valeur
- Types : string, text, number, boolean, json
- Organisation par catégories
- Cache en mémoire
- Import/Export complet

### 🔐 Authentification
- Session PHP sécurisée
- 3 rôles : admin, editor, viewer
- Permissions hiérarchiques
- Changement de mot de passe
- Gestion des utilisateurs (admin only)

---

## 🚀 Démarrage rapide

### 1. Vérifier la base de données

Votre base `sc1ispy2055_flare_custom` doit déjà exister. Si besoin, exécutez :

```bash
mysql -u root -p sc1ispy2055_flare_custom < database/schema.sql
```

### 2. Configurer la connexion

Éditez `config/database.php` ou définissez les variables d'environnement :

```env
DB_HOST=localhost
DB_NAME=sc1ispy2055_flare_custom
DB_USER=votre_user
DB_PASS=votre_password
APP_ENV=production
```

### 3. Créer les dossiers nécessaires

```bash
mkdir -p assets/uploads assets/templates
chmod 755 assets/uploads assets/templates
```

### 4. Tester l'API

```bash
# Lister les produits
curl http://votre-site.com/api/products.php

# Connexion
curl -X POST http://votre-site.com/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 5. Utiliser dans votre code

```php
<?php
require_once __DIR__ . '/includes/Product.php';

$productModel = new Product();
$products = $productModel->getAll(['sport' => 'Football']);

foreach ($products as $product) {
    echo $product['nom'] . " - " . $product['prix_1'] . "€\n";
}
?>
```

---

## 📖 Exemples d'utilisation

### Exemple 1 : Afficher les produits d'une catégorie

```php
<?php
require_once 'includes/Category.php';

$categoryModel = new Category();

// Récupérer la catégorie Football
$category = $categoryModel->getBySlug('football');

if ($category) {
    // Récupérer les produits
    $products = $categoryModel->getProducts($category['id'], 1, 12);

    foreach ($products as $product) {
        echo "<div class='product'>";
        echo "  <h3>{$product['nom']}</h3>";
        echo "  <img src='{$product['photo_1']}' />";
        echo "  <p>À partir de {$product['prix_1']}€</p>";
        echo "</div>";
    }
}
?>
```

### Exemple 2 : Créer un devis depuis un formulaire

```php
<?php
require_once 'includes/Quote.php';

$quoteModel = new Quote();

// Données du formulaire
$data = [
    'client_nom' => $_POST['nom'],
    'client_email' => $_POST['email'],
    'client_telephone' => $_POST['telephone'],
    'product_reference' => $_POST['product_ref'],
    'total_pieces' => $_POST['quantite'],
    'tailles' => [
        'S' => $_POST['taille_s'],
        'M' => $_POST['taille_m'],
        'L' => $_POST['taille_l'],
        'XL' => $_POST['taille_xl']
    ],
    'personnalisation' => [
        'texte' => $_POST['texte'],
        'couleur' => $_POST['couleur']
    ]
];

try {
    $quoteId = $quoteModel->create($data);
    $quote = $quoteModel->getById($quoteId);

    echo "Devis créé avec succès : " . $quote['reference'];
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
```

### Exemple 3 : Upload d'un média

```php
<?php
require_once 'includes/Media.php';

$mediaModel = new Media();

if (isset($_FILES['fichier'])) {
    try {
        $media = $mediaModel->upload($_FILES['fichier'], [
            'alt_text' => 'Mon logo',
            'title' => 'Logo entreprise'
        ]);

        echo "Média uploadé : " . $media['url'];
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>
```

### Exemple 4 : Système de paramètres

```php
<?php
require_once 'includes/Settings.php';

$settings = new Settings();

// Récupérer un paramètre
$siteName = $settings->get('site_name', 'FLARE CUSTOM');

// Définir un paramètre
$settings->set('contact_email', 'contact@example.com', 'string', 'general');

// Utiliser dans votre site
echo "<title>{$siteName}</title>";
?>
```

---

## 🔒 Sécurité

### ⚠️ Important : Changez le mot de passe admin !

Par défaut, l'utilisateur admin a le mot de passe `admin123`. **Changez-le immédiatement !**

```bash
php generate-password-hash.php
# Entrez votre nouveau mot de passe
# Copiez le hash généré

# Puis mettez à jour dans la BDD :
# UPDATE users SET password='$2y$10$...' WHERE username='admin';
```

Ou via l'API :
```bash
curl -X POST http://votre-site.com/api/auth.php?action=change-password \
  -H "Content-Type: application/json" \
  -d '{"current_password":"admin123","new_password":"VotreNouveauMotDePasse"}'
```

### 🛡️ Bonnes pratiques

1. **Utilisez HTTPS** en production
2. **Validez toujours les entrées utilisateur**
3. **Limitez les permissions** (rôles viewer/editor/admin)
4. **Sauvegardes régulières** de la base de données
5. **Logs d'erreurs** activés en développement, désactivés en production

---

## 📊 Structure de la base de données

Votre base contient 8 tables :

1. **products** - Catalogue produits (35 colonnes)
2. **categories** - Catégories hiérarchiques
3. **quotes** - Devis clients avec JSON
4. **media** - Bibliothèque de fichiers
5. **templates** - Templates de design
6. **settings** - Paramètres clé/valeur
7. **users** - Utilisateurs et authentification
8. **pages** - Pages dynamiques (optionnel)

---

## 🎨 Frontend - Intégration JavaScript

### Exemple : Récupérer et afficher les produits

```javascript
// Récupérer tous les produits Football
fetch('/api/products.php?sport=Football')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const products = data.products;

      products.forEach(product => {
        console.log(product.nom, product.prix_1);
        // Afficher dans votre UI
      });
    }
  });
```

### Exemple : Créer un devis

```javascript
const devisData = {
  client_nom: "Dupont",
  client_email: "dupont@example.com",
  product_reference: "PROD-001",
  total_pieces: 25,
  tailles: { S: 5, M: 10, L: 10 }
};

fetch('/api/quotes.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(devisData)
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    alert(`Devis créé : ${data.reference}`);
  }
});
```

### Exemple : Upload d'image

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('alt_text', 'Mon image');

fetch('/api/media.php', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('URL:', data.media.url);
  }
});
```

---

## 📈 Performance

### Optimisations incluses

- **Pagination automatique** sur tous les listings
- **Cache des paramètres** en mémoire
- **Requêtes préparées** PDO
- **Index sur les colonnes** fréquemment recherchées
- **Lazy loading** des relations

### Conseils supplémentaires

1. Activez **OPcache** PHP en production
2. Utilisez **Redis** pour les sessions si trafic élevé
3. **CDN** pour les images/médias
4. **Gzip** sur les réponses JSON

---

## 🐛 Dépannage

### Erreur "Database connection failed"
- Vérifiez `config/database.php`
- Testez la connexion MySQL
- Vérifiez les permissions de l'utilisateur

### Erreur "Permission denied" sur upload
```bash
chmod 755 assets/uploads
chown www-data:www-data assets/uploads
```

### Les sessions ne fonctionnent pas
- Vérifiez que `session.save_path` est accessible
- Les cookies doivent être activés

### Erreur CORS
- Headers CORS sont inclus dans toutes les APIs
- Vérifiez votre configuration Apache/Nginx

---

## 📞 Support

Consultez la documentation complète : `API_DOCUMENTATION.md`

---

## 🎉 C'est prêt !

Votre backend est maintenant opérationnel. Vous pouvez :

1. ✅ Gérer vos produits via API
2. ✅ Créer des catégories hiérarchiques
3. ✅ Recevoir et gérer des devis
4. ✅ Uploader des médias
5. ✅ Gérer des templates de design
6. ✅ Configurer votre site avec les paramètres
7. ✅ Authentifier vos utilisateurs

**Bon développement ! 🚀**
