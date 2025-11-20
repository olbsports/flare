# FLARE CUSTOM - Documentation API Backend

## 🚀 Introduction

Bienvenue dans la documentation complète de l'API Backend FLARE CUSTOM. Ce backend gère tous les aspects de votre site : produits, catégories, médias, templates, devis, paramètres et authentification.

## 📋 Table des matières

1. [Configuration](#configuration)
2. [Authentification](#authentification)
3. [Produits](#produits)
4. [Catégories](#catégories)
5. [Devis](#devis)
6. [Médias](#médias)
7. [Templates](#templates)
8. [Paramètres](#paramètres)

---

## 🔧 Configuration

### Base de données

Le backend utilise une base de données MySQL/MariaDB. La configuration se trouve dans `config/database.php`.

**Variables d'environnement (production) :**
```env
DB_HOST=localhost
DB_NAME=sc1ispy2055_flare_custom
DB_USER=votre_utilisateur
DB_PASS=votre_mot_de_passe
APP_ENV=production
```

**Environnement de développement :**
Définir `APP_ENV=development` pour activer les erreurs détaillées.

### Structure des réponses

Toutes les réponses de l'API suivent ce format JSON :

```json
{
  "success": true,
  "data": {},
  "message": "Message optionnel",
  "error": "Erreur optionnelle"
}
```

**Codes HTTP :**
- `200` - Succès
- `201` - Créé
- `400` - Requête invalide
- `401` - Non authentifié
- `403` - Permission refusée
- `404` - Non trouvé
- `500` - Erreur serveur

---

## 🔐 Authentification

### Connexion
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
  "username": "admin",
  "password": "votre_mot_de_passe"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

### Déconnexion
```http
POST /api/auth.php?action=logout
```

### Vérifier l'utilisateur connecté
```http
GET /api/auth.php?action=me
```

### Vérifier le statut de connexion
```http
GET /api/auth.php?action=check
```

### Créer un utilisateur (Admin uniquement)
```http
POST /api/auth.php?action=register
Content-Type: application/json

{
  "username": "nouvel_utilisateur",
  "email": "user@example.com",
  "password": "mot_de_passe",
  "role": "editor"
}
```

**Rôles disponibles :** `admin`, `editor`, `viewer`

### Changer son mot de passe
```http
POST /api/auth.php?action=change-password
Content-Type: application/json

{
  "current_password": "ancien",
  "new_password": "nouveau"
}
```

---

## 📦 Produits

### Récupérer tous les produits
```http
GET /api/products.php?page=1&limit=20&sport=football&famille=maillot&search=terme
```

**Paramètres :**
- `page` : Numéro de page (défaut: 1)
- `limit` : Nombre par page (défaut: 20)
- `sport` : Filtrer par sport
- `famille` : Filtrer par famille
- `search` : Recherche par nom/description/référence

**Réponse :**
```json
{
  "success": true,
  "products": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}
```

### Récupérer un produit
```http
GET /api/products.php?id=123
GET /api/products.php?reference=PROD-001
```

### Créer un produit
```http
POST /api/products.php
Content-Type: application/json

{
  "reference": "PROD-001",
  "nom": "Maillot Football Personnalisé",
  "sport": "Football",
  "famille": "Maillot",
  "description": "Description du produit...",
  "description_seo": "Description optimisée SEO...",
  "tissu": "Polyester",
  "grammage": "180g/m²",
  "prix_1": 45.00,
  "prix_10": 38.00,
  "prix_50": 32.00,
  "photo_1": "/assets/images/product1.jpg",
  "genre": "Homme",
  "meta_title": "Titre SEO",
  "meta_description": "Description SEO",
  "active": true
}
```

### Mettre à jour un produit
```http
PUT /api/products.php?id=123
Content-Type: application/json

{
  "nom": "Nouveau nom",
  "prix_1": 50.00
}
```

### Supprimer un produit (soft delete)
```http
DELETE /api/products.php?id=123
```

---

## 📂 Catégories

### Récupérer toutes les catégories
```http
GET /api/categories.php
GET /api/categories.php?type=sport
GET /api/categories.php?type=famille
GET /api/categories.php?root=true
```

**Types :** `sport`, `famille`

### Récupérer l'arbre des catégories
```http
GET /api/categories.php?tree=true&type=sport
```

**Réponse :**
```json
{
  "success": true,
  "categories": [
    {
      "id": 1,
      "nom": "Football",
      "slug": "football",
      "type": "sport",
      "children": [
        {
          "id": 10,
          "nom": "Maillot Football",
          "slug": "maillot-football"
        }
      ]
    }
  ]
}
```

### Récupérer les produits d'une catégorie
```http
GET /api/categories.php?id=1&products=true&page=1&limit=20
```

### Créer une catégorie
```http
POST /api/categories.php
Content-Type: application/json

{
  "nom": "Basketball",
  "type": "sport",
  "description": "Équipements de basketball",
  "image": "/assets/images/basketball.jpg",
  "parent_id": null,
  "ordre": 5,
  "active": true
}
```

### Mettre à jour une catégorie
```http
PUT /api/categories.php?id=1
Content-Type: application/json

{
  "nom": "Nouveau nom",
  "ordre": 10
}
```

### Supprimer une catégorie
```http
DELETE /api/categories.php?id=1
```

---

## 💰 Devis

### Récupérer tous les devis
```http
GET /api/quotes.php?page=1&limit=20&status=pending
```

**Statuts :** `pending`, `sent`, `accepted`, `rejected`, `completed`

### Récupérer un devis
```http
GET /api/quotes.php?id=123
GET /api/quotes.php?reference=DEV-20250101-ABC123
```

### Récupérer les statistiques
```http
GET /api/quotes.php?stats=true
```

**Réponse :**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "pending": 25,
    "sent": 40,
    "accepted": 60,
    "rejected": 10,
    "completed": 15,
    "total_revenue": 125000.50,
    "average_revenue": 833.34
  }
}
```

### Créer un devis
```http
POST /api/quotes.php
Content-Type: application/json

{
  "client_prenom": "Jean",
  "client_nom": "Dupont",
  "client_email": "jean.dupont@example.com",
  "client_telephone": "0612345678",
  "client_club": "FC Exemple",
  "client_fonction": "Président",
  "product_reference": "PROD-001",
  "product_nom": "Maillot Football",
  "sport": "Football",
  "famille": "Maillot",
  "design_type": "flare",
  "design_template_id": 5,
  "design_description": "Logo sur le devant",
  "options": {
    "couleur_principale": "bleu",
    "couleur_secondaire": "blanc"
  },
  "genre": "Homme",
  "tailles": {
    "S": 5,
    "M": 10,
    "L": 15,
    "XL": 5
  },
  "personnalisation": {
    "logo": "/assets/uploads/logo.png",
    "texte": "FC EXEMPLE"
  },
  "total_pieces": 35,
  "prix_unitaire": 32.00,
  "prix_total": 1120.00,
  "notes": "Livraison urgente"
}
```

### Mettre à jour un devis
```http
PUT /api/quotes.php?id=123
Content-Type: application/json

{
  "status": "sent",
  "prix_unitaire": 30.00,
  "prix_total": 1050.00,
  "notes": "Prix négocié"
}
```

---

## 🖼️ Médias

### Récupérer tous les médias
```http
GET /api/media.php?page=1&limit=30&type=image&search=logo
```

**Types :** `image`, `video`, `document`, `other`

### Upload un média
```http
POST /api/media.php
Content-Type: multipart/form-data

file: [fichier]
alt_text: "Texte alternatif"
title: "Titre"
description: "Description"
```

**Réponse :**
```json
{
  "success": true,
  "message": "Média uploadé avec succès",
  "media": {
    "id": 50,
    "filename": "abc123_logo.png",
    "url": "/assets/uploads/abc123_logo.png",
    "type": "image",
    "mime_type": "image/png",
    "size": 125000,
    "width": 1920,
    "height": 1080
  }
}
```

### Mettre à jour un média
```http
PUT /api/media.php?id=50
Content-Type: application/json

{
  "alt_text": "Nouveau texte alternatif",
  "title": "Nouveau titre",
  "description": "Nouvelle description"
}
```

### Supprimer un média
```http
DELETE /api/media.php?id=50
```

---

## 🎨 Templates

### Récupérer tous les templates
```http
GET /api/templates.php?page=1&limit=30&type=svg&tags=football
```

**Types :** `svg`, `png`, `jpg`

### Scanner et importer les templates
```http
GET /api/templates.php?scan=true
```

Cette commande scan le dossier `assets/templates/` et importe automatiquement les nouveaux fichiers.

### Upload un template
```http
POST /api/templates.php
Content-Type: multipart/form-data

file: [fichier]
nom: "Template Football"
description: "Template pour maillot de football"
tags: "football,maillot"
ordre: 5
```

### Récupérer un template avec son contenu SVG
```http
GET /api/templates.php?id=5&include_content=true
```

### Mettre à jour un template
```http
PUT /api/templates.php?id=5
Content-Type: application/json

{
  "nom": "Nouveau nom",
  "tags": "football,maillot,personnalisé",
  "ordre": 10
}
```

### Supprimer un template
```http
DELETE /api/templates.php?id=5
DELETE /api/templates.php?id=5&hard=1  // Suppression définitive
```

---

## ⚙️ Paramètres

### Récupérer tous les paramètres
```http
GET /api/settings.php
```

**Réponse organisée par catégorie :**
```json
{
  "success": true,
  "settings": {
    "general": [
      {
        "setting_key": "site_name",
        "setting_value": "FLARE CUSTOM",
        "setting_type": "string"
      }
    ],
    "pricing": [
      {
        "setting_key": "tax_rate",
        "setting_value": 20,
        "setting_type": "number"
      }
    ]
  }
}
```

### Récupérer un paramètre
```http
GET /api/settings.php?key=site_name
```

### Récupérer les paramètres par catégorie
```http
GET /api/settings.php?category=general
```

### Créer/Mettre à jour un paramètre
```http
POST /api/settings.php
Content-Type: application/json

{
  "key": "site_name",
  "value": "FLARE CUSTOM",
  "type": "string",
  "category": "general",
  "description": "Nom du site"
}
```

**Types :** `string`, `text`, `number`, `boolean`, `json`

### Mise à jour en masse
```http
PUT /api/settings.php
Content-Type: application/json

{
  "site_name": {
    "value": "Nouveau nom",
    "type": "string"
  },
  "tax_rate": {
    "value": 20,
    "type": "number"
  }
}
```

### Exporter tous les paramètres
```http
GET /api/settings.php?export=true
```

### Importer des paramètres
```http
PUT /api/settings.php?import=true
Content-Type: application/json

{
  "site_name": {
    "value": "FLARE CUSTOM",
    "type": "string",
    "category": "general"
  }
}
```

### Supprimer un paramètre
```http
DELETE /api/settings.php?key=custom_setting
```

---

## 🔑 Classes PHP disponibles

Toutes les classes sont dans le dossier `includes/` :

- `Database.php` - Connexion à la base de données (Singleton)
- `Product.php` - Gestion des produits
- `Category.php` - Gestion des catégories
- `Quote.php` - Gestion des devis
- `Media.php` - Gestion de la bibliothèque médias
- `Template.php` - Gestion des templates
- `Settings.php` - Gestion des paramètres
- `Auth.php` - Authentification et permissions

### Exemple d'utilisation dans votre code PHP

```php
<?php
require_once __DIR__ . '/includes/Product.php';

$productModel = new Product();

// Récupérer tous les produits
$products = $productModel->getAll(['sport' => 'Football']);

// Récupérer un produit
$product = $productModel->getById(123);

// Créer un produit
$id = $productModel->create([
    'reference' => 'PROD-001',
    'nom' => 'Maillot Football',
    // ...
]);

// Mettre à jour
$productModel->update($id, ['prix_1' => 50.00]);

// Supprimer
$productModel->delete($id);
```

---

## 🛡️ Sécurité

### CORS
Tous les endpoints API supportent CORS pour permettre les appels depuis votre frontend.

### Sessions PHP
L'authentification utilise les sessions PHP. Assurez-vous que les cookies sont activés.

### Validation des données
Toutes les données sont validées et échappées avant insertion en base.

### Protection contre les injections SQL
Utilisation de requêtes préparées PDO sur tous les endpoints.

---

## 📝 Notes importantes

1. **Base de données** : Assurez-vous que la base `sc1ispy2055_flare_custom` existe et que les tables sont créées avec `database/schema.sql`

2. **Dossiers uploads** : Les dossiers suivants doivent être accessibles en écriture :
   - `assets/uploads/` - Pour les médias
   - `assets/templates/` - Pour les templates

3. **Environnement de production** :
   - Désactivez l'affichage des erreurs
   - Utilisez HTTPS
   - Changez le mot de passe admin par défaut

4. **Performance** :
   - Les requêtes sont paginées par défaut
   - La classe Settings utilise un cache en mémoire
   - Pensez à créer des index sur les colonnes fréquemment recherchées

---

## 🚀 Démarrage rapide

1. **Installer la base de données** :
```bash
mysql -u root -p < database/schema.sql
```

2. **Configurer la connexion** dans `config/database.php`

3. **Tester l'API** :
```bash
curl http://votre-site.com/api/products.php
```

4. **Se connecter à l'admin** :
   - Username: `admin`
   - Password: `admin123` (à changer immédiatement !)

---

## 📞 Support

Pour toute question ou problème, consultez les logs d'erreurs PHP ou contactez l'équipe de développement.

**Bon développement ! 🎉**
