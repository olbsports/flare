# 📝 Comment Ajouter un Nouvel Article au Blog

Ce guide explique comment ajouter facilement un nouvel article au blog FLARE CUSTOM.

## 🚀 Étapes Rapides

### 1. Ajouter l'Article au JSON

Ouvrez le fichier `/assets/data/blog-articles.json` et ajoutez votre nouvel article dans le tableau `articles` :

```json
{
  "id": "titre-de-votre-article",
  "title": "Titre Complet de Votre Article",
  "slug": "titre-de-votre-article",
  "description": "Description courte de 150-200 caractères qui apparaîtra sur la carte.",
  "image": "/assets/images/blog/votre-image.jpg",
  "category": "Guide",
  "date": "2025-01-20",
  "author": "FLARE CUSTOM",
  "readTime": "5 min",
  "featured": false
}
```

**Catégories disponibles :** `Guide`, `Technologie`, `Conseils`

### 2. Créer le Fichier HTML de l'Article

1. **Copiez** un article existant (par exemple `comment-choisir-equipement-rugby-personnalise.html`)
2. **Renommez-le** avec le slug de votre article : `titre-de-votre-article.html`
3. **Placez-le** dans le dossier `/pages/blog/`

### 3. Modifier le Contenu

Ouvrez votre nouveau fichier HTML et modifiez :

#### Dans le `<head>` :
```html
<title>Votre Titre | FLARE CUSTOM</title>
<meta name="description" content="Votre description">
```

#### Dans le hero :
```html
<span class="article-category">Votre Catégorie</span>
<h1 class="article-title">Votre Titre Complet</h1>
<div class="article-meta">
    <span>📅 Date</span>
    <span>⏱️ Temps de lecture</span>
    <span>✍️ FLARE CUSTOM</span>
</div>
```

#### Dans le contenu :
- Remplacez tout le contenu entre `<article class="article-content">` et `</article>`
- Utilisez les balises HTML : `<h2>`, `<h3>`, `<p>`, `<ul>`, `<li>`, `<strong>`
- Gardez la structure CTA (Call-to-Action) à mi-parcours

### 4. Vérifier et Tester

1. Ouvrez `/pages/info/blog.html` dans votre navigateur
2. Vérifiez que votre nouvel article apparaît
3. Cliquez dessus pour vérifier qu'il s'affiche correctement

## 📋 Template HTML Minimal

Voici un template minimal pour démarrer rapidement :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOTRE TITRE | FLARE CUSTOM</title>
    <meta name="description" content="VOTRE DESCRIPTION">

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">

    <!-- Copiez tout le <style> d'un article existant -->
</head>
<body>
    <div id="dynamic-header"></div>

    <section class="article-hero">
        <div class="article-hero-container">
            <span class="article-category">CATÉGORIE</span>
            <h1 class="article-title">VOTRE TITRE</h1>
            <div class="article-meta">
                <span>📅 Date</span>
                <span>⏱️ X min</span>
                <span>✍️ FLARE CUSTOM</span>
            </div>
        </div>
    </section>

    <article class="article-content">
        <a href="../info/blog.html" class="back-to-blog">← Retour au blog</a>

        <!-- VOTRE CONTENU ICI -->
        <p>Introduction...</p>

        <h2>Premier Titre</h2>
        <p>Contenu...</p>

        <!-- CTA à mi-parcours -->
        <div class="article-cta">
            <h3>Titre CTA</h3>
            <p>Description CTA</p>
            <a href="/pages/info/contact.html" class="article-cta-btn">Demander un Devis Gratuit</a>
        </div>

        <h2>Deuxième Titre</h2>
        <p>Suite du contenu...</p>

        <h2>Conclusion</h2>
        <p>Conclusion...</p>
    </article>

    <div id="dynamic-footer"></div>

    <script src="../../assets/js/components-loader.js"></script>
</body>
</html>
```

## ✅ Checklist Avant Publication

- [ ] Article ajouté dans `blog-articles.json`
- [ ] Fichier HTML créé dans `/pages/blog/`
- [ ] Le `slug` est identique dans JSON et nom de fichier
- [ ] La `catégorie` correspond à une catégorie existante
- [ ] La `date` est au format YYYY-MM-DD
- [ ] Le titre est clair et attractif
- [ ] La description fait 150-200 caractères
- [ ] Le contenu est structuré avec des titres H2 et H3
- [ ] Le CTA est présent
- [ ] Les liens internes fonctionnent
- [ ] L'article s'affiche correctement sur mobile

## 🎨 Conseils de Rédaction

### Titres Accrocheurs
- Utilisez des chiffres : "5 Conseils pour...", "10 Erreurs à Éviter..."
- Posez des questions : "Comment Choisir...", "Quelle Différence entre..."
- Promettez une valeur : "Guide Complet", "Tout Savoir sur..."

### Structure Idéale
1. **Introduction** (1-2 paragraphes) : Contexte et problématique
2. **Corps** (3-5 sections H2) : Contenu principal structuré
3. **CTA** : Appel à l'action vers contact/devis
4. **Conclusion** : Résumé et prochaines étapes

### Longueur
- **Articles courts** : 800-1200 mots (4-5 min)
- **Articles moyens** : 1200-1800 mots (5-7 min)
- **Articles longs** : 1800-2500 mots (8-10 min)

## 🔧 Dépannage

### L'article n'apparaît pas sur la page blog
- Vérifiez que le JSON est valide (pas de virgule manquante)
- Vérifiez que le slug est unique
- Rafraîchissez la page avec Ctrl+F5

### L'article ne s'ouvre pas
- Vérifiez que le nom du fichier HTML correspond exactement au slug
- Vérifiez que le fichier est bien dans `/pages/blog/`

### Le style ne s'applique pas
- Vérifiez que vous avez copié tout le bloc `<style>` d'un article existant
- Vérifiez les chemins vers les CSS dans le `<head>`

## 📞 Support

En cas de problème, contactez l'équipe technique FLARE CUSTOM.

---

**Dernière mise à jour :** 17 novembre 2025
