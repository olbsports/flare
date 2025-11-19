#!/bin/bash

# Script pour intégrer le configurateur produit dans toutes les pages produits

# Liste des pages produits avec section seo-mega
pages=(
    "maillots-rugby-personnalises.html"
    "maillots-basket-personnalises.html"
    "maillots-cyclisme-personnalises.html"
    "maillots-running-personnalises.html"
    "shorts-football-personnalises.html"
    "shorts-basketball-personnalises.html"
    "shorts-sport-personnalises.html"
    "equipement-football-personnalise-sublimation.html"
    "equipement-basketball-personnalise-sublimation.html"
    "equipement-rugby-personnalise-sublimation.html"
    "equipement-handball-personnalise-sublimation.html"
    "equipement-volleyball-personnalise-sublimation.html"
    "equipement-cyclisme-velo-personnalise-sublimation.html"
    "equipement-triathlon-personnalise-sublimation.html"
    "equipement-running-course-pied-personnalise.html"
    "equipement-petanque-personnalise-club.html"
    "tshirts-sport-personnalises.html"
    "polos-sport-personnalises.html"
    "sweats-sport-personnalises.html"
    "sweats-capuche-sport-personnalises.html"
    "vestes-sport-personnalisees.html"
    "vestes-clubs-personnalisees.html"
    "pantalons-sport-personnalises.html"
    "pantalons-entrainement-personnalises.html"
    "debardeurs-sport-personnalises.html"
    "gilets-sport-personnalises.html"
)

PAGES_DIR="/home/user/flare/pages/products"

echo "🚀 Intégration du configurateur produit dans ${#pages[@]} pages..."

for page in "${pages[@]}"; do
    file_path="$PAGES_DIR/$page"

    if [ ! -f "$file_path" ]; then
        echo "⚠️  Fichier non trouvé: $page"
        continue
    fi

    echo "✏️  Traitement de: $page"

    # Vérifier si le configurateur n'est pas déjà ajouté
    if grep -q "configurateur-produit.css" "$file_path"; then
        echo "   ✅ Configurateur déjà intégré, passage..."
        continue
    fi

    # 1. Ajouter CSS et JS dans le <head>
    # Chercher la dernière ligne <noscript> du head et ajouter après
    sed -i '/<noscript>.*components\.css.*<\/noscript>/a\
\
    <!-- Configurateur Produit -->\
    <link rel="stylesheet" href="../../assets/css/configurateur-produit.css">\
    <script src="../../assets/js/configurateur-produit.js" defer></script>' "$file_path"

    echo "   ✅ CSS et JS ajoutés"
done

echo ""
echo "✅ Intégration terminée pour ${#pages[@]} pages !"
echo ""
echo "⚠️  Note: La section de bouton doit être ajoutée manuellement avant seo-mega"
echo "   car les données produit varient pour chaque page."
