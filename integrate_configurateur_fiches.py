#!/usr/bin/env python3
"""
Script pour intégrer le configurateur produit sur les FICHES PRODUITS individuelles
(FLARE-*.html dans pages/produits/)
"""

import os
import re
import json
from pathlib import Path

PRODUITS_DIR = Path("/home/user/flare/pages/produits")

# Template CSS/JS à ajouter dans le <head>
HEAD_TEMPLATE = """
    <!-- Configurateur Produit -->
    <link rel="stylesheet" href="../../assets/css/configurateur-produit.css">
    <script src="../../assets/js/configurateur-produit.js" defer></script>
"""

def extract_product_data_from_jsonld(content):
    """Extract product data from JSON-LD schema.org"""
    try:
        # Find JSON-LD block
        match = re.search(r'<script type="application/ld\+json">\s*({.*?})\s*</script>', content, re.DOTALL)
        if not match:
            return None

        json_str = match.group(1)
        data = json.loads(json_str)

        # Extract data
        nom = data.get('name', 'Produit Personnalisé')
        reference = data.get('mpn', '')

        # Extract sport from category or additionalProperty
        sport = 'Multi-sports'
        category = data.get('category', '')
        if 'Football' in category:
            sport = 'Football'
        elif 'Basketball' in category or 'Basket' in category:
            sport = 'Basketball'
        elif 'Rugby' in category:
            sport = 'Rugby'
        elif 'Cyclisme' in category:
            sport = 'Cyclisme'
        elif 'Running' in category:
            sport = 'Running'
        elif 'Handball' in category:
            sport = 'Handball'
        elif 'Volleyball' in category:
            sport = 'Volleyball'
        elif 'Triathlon' in category:
            sport = 'Triathlon'

        # Check additionalProperty for sport
        if 'additionalProperty' in data:
            for prop in data['additionalProperty']:
                if prop.get('name') == 'Sport':
                    sport = prop.get('value', sport)

        # Detect famille from category or name
        famille = 'Équipement'
        if 'Maillot' in category or 'Maillot' in nom:
            famille = 'Maillot'
        elif 'Short' in category or 'Short' in nom:
            famille = 'Short'
        elif 'T-Shirt' in nom or 'Tshirt' in nom:
            famille = 'T-Shirt'
        elif 'Polo' in nom:
            famille = 'Polo'
        elif 'Sweat' in nom:
            famille = 'Sweat'
        elif 'Veste' in nom:
            famille = 'Veste'
        elif 'Pantalon' in nom:
            famille = 'Pantalon'
        elif 'Débardeur' in nom or 'Debardeur' in nom:
            famille = 'Débardeur'
        elif 'Gilet' in nom:
            famille = 'Gilet'
        elif 'Combinaison' in nom:
            famille = 'Combinaison'
        elif 'Cuissard' in nom:
            famille = 'Cuissard'

        # Extract tissu and grammage
        tissu = data.get('material', 'Performance Mesh')
        grammage = ''
        for prop in data.get('additionalProperty', []):
            if prop.get('name') == 'Grammage':
                grammage = prop.get('value', '')

        # Extract photo from og:image
        photo = ''
        photo_match = re.search(r'<meta property="og:image" content="([^"]+)"', content)
        if photo_match:
            photo = photo_match.group(1)

        # Extract price from offers
        prix_base = 25.00
        if 'offers' in data and 'lowPrice' in data['offers']:
            try:
                prix_base = float(data['offers']['lowPrice'])
            except:
                pass

        return {
            'reference': reference,
            'nom': nom,
            'sport': sport,
            'famille': famille,
            'photo': photo,
            'tissu': tissu,
            'grammage': grammage,
            'prixBase': prix_base
        }

    except Exception as e:
        print(f"  ⚠️  Error parsing JSON-LD: {e}")
        return None

def integrate_configurateur(filepath):
    """Integrate configurateur into a product page"""
    print(f"Processing: {filepath.name}")

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already integrated
    if 'configurateur-produit.css' in content:
        print(f"  ✅ Already integrated, skipping")
        return False

    # 1. Add CSS/JS in <head> after components.css or other CSS
    pattern = r'(<link rel="stylesheet" href="../../assets/css/product-page\.css">)'
    if re.search(pattern, content):
        replacement = r'\1' + HEAD_TEMPLATE
        content = re.sub(pattern, replacement, content, count=1)
    else:
        # Fallback: add before </head>
        pattern = r'(</head>)'
        replacement = HEAD_TEMPLATE + r'\1'
        content = re.sub(pattern, replacement, content, count=1)

    # 2. Extract product data from JSON-LD
    product_data = extract_product_data_from_jsonld(content)

    if not product_data:
        print(f"  ⚠️  Could not extract product data")
        return False

    # 3. Update the existing button "CONFIGURER MON DEVIS"
    # Find the button and replace onclick
    product_js = json.dumps(product_data, ensure_ascii=False)

    # Replace the button
    old_button_pattern = r'<a href="#configurator" class="btn-primary" onclick="scrollToConfigurator\(\)">CONFIGURER MON DEVIS</a>'
    new_button = f'<button class="btn-primary" onclick=\'initConfigurateurProduit({product_js})\'>CONFIGURER MON DEVIS</button>'

    if re.search(old_button_pattern, content):
        content = re.sub(old_button_pattern, new_button, content)
    else:
        # Try without onclick
        old_button_pattern2 = r'<a href="#configurator" class="btn-primary">CONFIGURER MON DEVIS</a>'
        if re.search(old_button_pattern2, content):
            content = re.sub(old_button_pattern2, new_button, content)
        else:
            print(f"  ⚠️  Button not found")
            # Still write the file with CSS/JS added

    # Write back
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"  ✅ Integrated successfully")
    print(f"      Sport: {product_data['sport']}, Famille: {product_data['famille']}, Prix: {product_data['prixBase']}€")
    return True

def main():
    print("🚀 Intégration du configurateur sur les FICHES PRODUITS individuelles...")
    print()

    # Find all FLARE-*.html files
    product_files = sorted(PRODUITS_DIR.glob("FLARE-*.html"))

    if not product_files:
        print("❌ Aucun fichier FLARE-*.html trouvé")
        return

    print(f"📦 {len(product_files)} fiches produits trouvées")
    print()

    modified_count = 0

    for product_file in product_files:
        if integrate_configurateur(product_file):
            modified_count += 1

    print()
    print(f"✅ Terminé ! {modified_count} fiches produits modifiées sur {len(product_files)} total.")

if __name__ == '__main__':
    main()
