# 🚀 GUIDE COMPLET - Système de Publication des Pages

Ce guide explique comment modifier le contenu de tes pages HTML depuis l'admin et publier les modifications sur ton site.

---

## 📋 COMMENT ÇA FONCTIONNE

### Workflow en 3 étapes :

```
1. IMPORT       2. ÉDITION       3. PUBLICATION
   HTML            Admin             HTML
    ↓               ↓                 ↓
  Pages     →    Base de     →     Pages
  .html          Données          .html
                                 (mises à jour)
```

### Détails du process :

1. **IMPORT** (une seule fois) :
   - Tu lances `import-html-to-database.php`
   - Tout le contenu de tes pages HTML est copié dans la BDD
   - Tes pages originales restent intactes

2. **ÉDITION** (autant de fois que tu veux) :
   - Tu te connectes à l'admin
   - Tu modifies le contenu dans "Éditeur de Contenu"
   - Les modifications sont sauvegardées dans la BDD
   - ⚠️ Le site n'est PAS encore modifié

3. **PUBLICATION** (quand tu es prêt) :
   - Tu cliques sur "Publier les Modifications"
   - Le système régénère les fichiers HTML depuis la BDD
   - Les changements apparaissent IMMÉDIATEMENT sur le site

---

## 🎬 UTILISATION ÉTAPE PAR ÉTAPE

### ÉTAPE 1 : Import initial (À FAIRE UNE SEULE FOIS)

#### 1.1 Accède à la page d'import

```
https://ton-site.com/import-html-to-database.php
```

#### 1.2 Attends la fin de l'import

Tu verras en temps réel :
- Pages scannées
- Blocs créés
- Statistiques

Exemple de résultat :
```
📊 Statistiques
📄 Pages scannées: 32
✅ Blocks créés: 28
✏️ Blocks mis à jour: 4
❌ Erreurs: 0
```

#### 1.3 Vérifie que tout est OK

Retourne à l'admin : `https://ton-site.com/admin/index.php`

---

### ÉTAPE 2 : Modifier le contenu

#### 2.1 Accède à l'éditeur

Dans le menu de gauche, clique sur **"📝 Éditeur de Contenu"**

Ou accède directement :
```
https://ton-site.com/admin/content-editor.php
```

#### 2.2 Sélectionne une page

Dans la sidebar de gauche, tu verras la liste de toutes tes pages :

```
product_page_maillot
product_page_short
page_about
page_contact
...
```

Clique sur la page que tu veux modifier.

#### 2.3 Modifie le contenu

Tu peux éditer :

**📋 Informations de la Page** :
- Titre de la page (admin) : Pour t'y retrouver dans l'admin
- Title (balise `<title>`) : Ce qui apparaît dans l'onglet du navigateur
- H1 Principal : Le grand titre de la page
- Meta Description : Description pour Google (160 caractères recommandés)

**📝 Contenu Textuel** :
- Paragraphes : Tout le texte de ta page
  - Sépare chaque paragraphe par une ligne vide
  - Exemple :
    ```
    Premier paragraphe ici.
    Avec plusieurs lignes si besoin.

    Deuxième paragraphe après une ligne vide.
    ```

**Sections avancées** (si présentes) :
- 📋 Listes : Format JSON
- 🖼️ Images : Format JSON
- 📊 Tableaux : Format JSON

#### 2.4 Sauvegarde

Clique sur **"💾 Sauvegarder"**

Tu verras un message :
```
✅ Modifications enregistrées avec succès !
Cliquez sur "Publier" pour mettre à jour le site.
```

⚠️ **IMPORTANT** : À ce stade, le site n'est PAS encore modifié !
Les changements sont juste sauvegardés dans la BDD.

---

### ÉTAPE 3 : Publier les modifications

#### 3.1 Va à la page de publication

Clique sur le bouton vert **"🚀 Publier les Modifications"**

Ou accède directement :
```
https://ton-site.com/admin/publish-pages.php
```

#### 3.2 Lis l'avertissement

Tu verras :
```
⚠️ Important - Lisez avant de publier

Cette action va :
- Régénérer tous les fichiers HTML depuis le contenu de la base de données
- Créer une sauvegarde (.backup) de chaque fichier avant modification
- Remplacer les titres, meta descriptions, et H1 par le nouveau contenu
- Les changements seront visibles immédiatement sur votre site
```

#### 3.3 Confirme la publication

Clique sur **"🚀 Publier les Modifications"**

Une popup de confirmation apparaîtra :
```
Êtes-vous sûr de vouloir publier les modifications ?
Cette action va régénérer tous les fichiers HTML.
```

Clique sur **OK**.

#### 3.4 Attends la fin

Le système va :
1. Parcourir tous les content blocks
2. Charger chaque fichier HTML original
3. Remplacer le contenu (title, meta, h1, textes)
4. Sauvegarder le fichier HTML mis à jour

Tu verras :
```
📊 Statistiques
Total: 32
Succès: 30
Erreurs: 0
Ignorés: 2

📋 Journal de publication
✅ maillot.html - Publié avec succès
✅ short.html - Publié avec succès
...
```

#### 3.5 C'est fait !

✅ Tes pages HTML sont maintenant mises à jour
✅ Les visiteurs voient le nouveau contenu
✅ Tout fonctionne comme avant, mais avec le nouveau contenu

---

## 🔒 SÉCURITÉ

### Sauvegardes automatiques

Chaque fois que tu publies, le système crée automatiquement une sauvegarde :

```
maillot.html → maillot.html.backup_20241120153045
```

Si quelque chose ne va pas, tu peux restaurer :

```bash
# Restaure la sauvegarde
mv maillot.html.backup_20241120153045 maillot.html
```

### Ce qui est modifié

Le système modifie SEULEMENT :
- `<title>` : Le titre de la page
- `<meta name="description">` : La meta description
- `<h1>` : Le titre principal (premier H1 trouvé)

### Ce qui n'est PAS modifié

✅ La structure HTML
✅ Le CSS
✅ Le JavaScript
✅ Les images
✅ Les liens
✅ Le footer/header
✅ Tout le reste !

---

## 💡 CONSEILS D'UTILISATION

### 1. Modifie plusieurs pages avant de publier

Tu peux :
1. Modifier la page 1 → Sauvegarder
2. Modifier la page 2 → Sauvegarder
3. Modifier la page 3 → Sauvegarder
4. **Ensuite** publier tout d'un coup

C'est plus efficace !

### 2. Utilise la recherche

Dans l'éditeur, utilise la barre de recherche pour trouver une page rapidement :

```
🔍 Rechercher... : maillot
```

Ça filtre instantanément la liste.

### 3. Garde les paragraphes séparés

Quand tu édites les paragraphes, sépare-les par **une ligne vide** :

```
✅ BON :
Premier paragraphe.

Deuxième paragraphe.

✖️ MAUVAIS :
Premier paragraphe.
Deuxième paragraphe.
```

### 4. Vérifie le compteur de caractères

Pour la **Meta Description**, respecte la limite de 160 caractères :

```
📝 Meta Description
[...ton texte...]
125 / 160 caractères recommandés  ← Regarde ici !
```

---

## 🔧 TROUBLESHOOTING

### Problème : "Fichier source non trouvé"

**Cause** : Le système ne trouve pas le fichier HTML original

**Solution** :
1. Vérifie que le fichier existe dans `/pages/products/` ou `/pages/`
2. Vérifie que le nom du fichier correspond au block_key
3. Exemple : `product_page_maillot` → `/pages/products/maillot.html`

### Problème : Les modifications ne s'affichent pas sur le site

**Causes possibles** :

1. **Tu n'as pas publié** → Clique sur "Publier les Modifications"
2. **Cache du navigateur** → Appuie sur Ctrl+F5 (ou Cmd+Shift+R sur Mac)
3. **Cache du serveur** → Attends 1-2 minutes

**Solution rapide** : Teste en navigation privée

### Problème : Erreurs lors de la publication

**Erreur** : "Impossible d'écrire le fichier"

**Cause** : Permissions insuffisantes

**Solution** :
```bash
# Donne les droits d'écriture
chmod 664 /chemin/vers/page.html
chmod 775 /chemin/vers/dossier/
```

### Problème : Le HTML est cassé après publication

**Cause** : Caractères spéciaux mal encodés

**Solution** :
1. Restaure la sauvegarde :
   ```bash
   mv page.html.backup_XXXXXX page.html
   ```
2. Vérifie que ton contenu n'a pas de balises HTML non fermées
3. Republier

---

## 📊 WORKFLOW RECOMMANDÉ

### Pour une modification mineure (1-2 pages) :

```
1. Admin → Éditeur de Contenu
2. Sélectionne la page
3. Modifie le texte
4. Sauvegarde
5. Publie
6. Vérifie sur le site (Ctrl+F5)
```

**Temps estimé** : 5 minutes

### Pour une modification majeure (10+ pages) :

```
1. Admin → Éditeur de Contenu
2. Modifie page 1 → Sauvegarde
3. Modifie page 2 → Sauvegarde
4. Modifie page 3 → Sauvegarde
...
10. Modifie page 10 → Sauvegarde
11. Publie tout d'un coup
12. Vérifie sur le site
```

**Temps estimé** : 30-60 minutes

### Pour une refonte complète :

```
1. Export de sauvegarde de la BDD (sécurité)
2. Modifie toutes les pages (1-2 jours)
3. Revue finale
4. Publication
5. Tests complets
6. Validation
```

---

## 🎯 RÉCAPITULATIF

### Ce que tu PEUX faire

✅ Modifier les titres de pages
✅ Modifier les meta descriptions (SEO)
✅ Modifier les H1
✅ Modifier tout le contenu textuel
✅ Sauvegarder dans la BDD
✅ Publier sur le site quand tu veux

### Ce que tu NE PEUX PAS (encore) faire

❌ Modifier les images (pour l'instant)
❌ Modifier les listes/tableaux (édition JSON complexe)
❌ Ajouter de nouvelles pages HTML
❌ Modifier la structure/design

### Prochaines améliorations possibles

Si tu veux, je peux créer :
- Éditeur WYSIWYG (comme Word)
- Gestion des images dans l'interface
- Éditeur de listes/tableaux visuels
- Prévisualisation avant publication
- Historique des modifications
- Publication planifiée

---

## 📞 BESOIN D'AIDE ?

### Logs de publication

Si une publication échoue, check le **Journal de publication** qui affiche toutes les erreurs.

### Sauvegardes

Toutes les sauvegardes sont dans le même dossier que tes pages :
```
/pages/products/maillot.html
/pages/products/maillot.html.backup_20241120153045
/pages/products/maillot.html.backup_20241120161230
```

Tu peux les supprimer après validation que tout fonctionne.

---

## ✅ CHECKLIST AVANT PUBLICATION

- [ ] J'ai vérifié toutes mes modifications
- [ ] J'ai relu le contenu (fautes ?)
- [ ] J'ai vérifié la longueur des meta descriptions (≤ 160 caractères)
- [ ] J'ai testé en local si possible
- [ ] Je suis prêt à publier

**Clique sur "Publier" !** 🚀

---

**Note finale** : Tes pages HTML originales ne sont JAMAIS supprimées. Elles sont juste mises à jour avec le nouveau contenu de la BDD. Tout est réversible grâce aux sauvegardes automatiques ! 🔒
