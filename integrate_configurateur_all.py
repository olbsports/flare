#!/usr/bin/env python3
"""
Script pour intégrer le configurateur produit dans toutes les pages produits
"""

import os
import re
from pathlib import Path

PAGES_DIR = Path("/home/user/flare/pages/products")

# Template CSS/JS à ajouter dans le <head>
HEAD_TEMPLATE = """
    <!-- Configurateur Produit -->
    <link rel="stylesheet" href="../../assets/css/configurateur-produit.css">
    <script src="../../assets/js/configurateur-produit.js" defer></script>
"""

# Template de la section configurateur
SECTION_TEMPLATE = """
    <!-- Section Configurateur Produit -->
    <section style="padding: 100px 5%; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
            <div style="display: inline-block; background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%); color: #fff; padding: 8px 24px; font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 24px; margin-bottom: 24px;">
                Configurateur en ligne
            </div>
            <h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 56px; letter-spacing: 2px; margin-bottom: 24px; color: #1a1a1a; line-height: 1.2;">
                Créez votre devis personnalisé
            </h2>
            <p style="font-size: 18px; line-height: 1.8; color: #495057; max-width: 900px; margin: 0 auto 48px;">
                Configurez vos équipements en quelques clics : choix du design, des couleurs, des tailles, de la personnalisation. Recevez votre devis détaillé par email sous 24h.
            </p>

            <button onclick="initConfigurateurProduit({{
                reference: '{reference}',
                nom: '{nom}',
                sport: '{sport}',
                famille: '{famille}',
                photo: '{photo}',
                tissu: '{tissu}',
                grammage: '{grammage}',
                prixBase: {prix}
            }})" style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 40px; background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%); color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(255, 75, 38, 0.3);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                <span>Configurer mon devis</span>
            </button>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-top: 64px; max-width: 900px; margin-left: auto; margin-right: auto;">
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">⚡</div>
                    <h4 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 1px; margin: 0 0 8px 0; color: #1a1a1a;">Rapide</h4>
                    <p style="color: #666; margin: 0; font-size: 0.95rem;">5 minutes pour un devis complet</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">🎨</div>
                    <h4 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 1px; margin: 0 0 8px 0; color: #1a1a1a;">Personnalisé</h4>
                    <p style="color: #666; margin: 0; font-size: 0.95rem;">Design, couleurs, tailles, logos</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 12px;">💰</div>
                    <h4 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 1px; margin: 0 0 8px 0; color: #1a1a1a;">Prix dégressifs</h4>
                    <p style="color: #666; margin: 0; font-size: 0.95rem;">Jusqu'à -35% dès 500 pièces</p>
                </div>
            </div>
        </div>
    </section>

"""

def extract_product_data(filepath):
    """Extract product data from HTML file"""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Extract title
    title_match = re.search(r'<title>(.*?)\|', content)
    nom = title_match.group(1).strip() if title_match else "Produit Personnalisé"

    # Detect product type from filename
    filename = filepath.stem.lower()

    if 'maillot' in filename:
        famille = 'Maillot'
        tissu = 'Performance Mesh 130g ECO'
        grammage = '130g/m²'
    elif 'short' in filename:
        famille = 'Short'
        tissu = 'Performance Mesh 140g'
        grammage = '140g/m²'
    elif 'tshirt' in filename or 't-shirt' in filename:
        famille = 'T-Shirt'
        tissu = 'Jersey 150g'
        grammage = '150g/m²'
    elif 'polo' in filename:
        famille = 'Polo'
        tissu = 'Piqué 180g'
        grammage = '180g/m²'
    elif 'sweat' in filename:
        famille = 'Sweat'
        tissu = 'Molleton 280g'
        grammage = '280g/m²'
    elif 'veste' in filename:
        famille = 'Veste'
        tissu = 'Softshell 320g'
        grammage = '320g/m²'
    elif 'pantalon' in filename:
        famille = 'Pantalon'
        tissu = 'Polyester 200g'
        grammage = '200g/m²'
    elif 'debardeur' in filename:
        famille = 'Débardeur'
        tissu = 'Performance Mesh 120g'
        grammage = '120g/m²'
    elif 'gilet' in filename:
        famille = 'Gilet'
        tissu = 'Softshell 250g'
        grammage = '250g/m²'
    elif 'combinaison' in filename:
        famille = 'Combinaison'
        tissu = 'Lycra Performance 220g'
        grammage = '220g/m²'
    elif 'cuissard' in filename:
        famille = 'Cuissard'
        tissu = 'Lycra Cyclisme 230g'
        grammage = '230g/m²'
    elif 'corsaire' in filename:
        famille = 'Corsaire'
        tissu = 'Lycra 210g'
        grammage = '210g/m²'
    elif 'coupe-vent' in filename:
        famille = 'Coupe-vent'
        tissu = 'Windproof 120g'
        grammage = '120g/m²'
    else:
        famille = 'Équipement Sport'
        tissu = 'Tissu technique'
        grammage = '150g/m²'

    # Detect sport
    if 'football' in filename:
        sport = 'Football'
    elif 'basket' in filename:
        sport = 'Basketball'
    elif 'rugby' in filename:
        sport = 'Rugby'
    elif 'cyclisme' in filename or 'velo' in filename:
        sport = 'Cyclisme'
    elif 'running' in filename or 'course' in filename:
        sport = 'Running'
    elif 'handball' in filename:
        sport = 'Handball'
    elif 'volleyball' in filename:
        sport = 'Volleyball'
    elif 'triathlon' in filename:
        sport = 'Triathlon'
    elif 'petanque' in filename:
        sport = 'Pétanque'
    else:
        sport = 'Multi-sports'

    # Generate reference
    reference = filename.upper().replace('-', '_').replace('.HTML', '')

    # Find first product image
    img_match = re.search(r'<img src="(https://flare-custom\.com/photos/produits/[^"]+)"', content)
    photo = img_match.group(1) if img_match else ''

    # Prix base selon famille
    prix_map = {
        'Maillot': 22.50,
        'Short': 18.50,
        'T-Shirt': 15.00,
        'Polo': 24.00,
        'Sweat': 32.00,
        'Veste': 45.00,
        'Pantalon': 28.00,
        'Débardeur': 16.00,
        'Gilet': 38.00,
        'Combinaison': 65.00,
        'Cuissard': 35.00,
        'Corsaire': 28.00,
        'Coupe-vent': 35.00
    }
    prix = prix_map.get(famille, 25.00)

    return {
        'reference': reference,
        'nom': nom,
        'sport': sport,
        'famille': famille,
        'photo': photo,
        'tissu': tissu,
        'grammage': grammage,
        'prix': prix
    }

def integrate_configurateur(filepath):
    """Integrate configurateur into a product page"""
    print(f"Processing: {filepath.name}")

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already integrated
    if 'configurateur-produit.css' in content:
        print(f"  ✅ Already integrated, skipping")
        return False

    # Check if has seo-mega section
    if 'class="seo-mega"' not in content:
        print(f"  ⚠️  No seo-mega section, skipping")
        return False

    # 1. Add CSS/JS in <head>
    # Find last noscript with components.css
    pattern = r'(<noscript>.*?components\.css.*?</noscript>)'
    replacement = r'\1' + HEAD_TEMPLATE
    content = re.sub(pattern, replacement, content, count=1)

    # 2. Add configurateur section before seo-mega
    product_data = extract_product_data(filepath)
    section_html = SECTION_TEMPLATE.format(**product_data)

    pattern = r'(    </section>\s*\n\s*<section class="seo-mega">)'
    replacement = '    </section>\n\n' + section_html.rstrip() + '\n    <section class="seo-mega">'
    content = re.sub(pattern, replacement, content, count=1)

    # Write back
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"  ✅ Integrated successfully")
    return True

def main():
    print("🚀 Intégration du configurateur produit sur toutes les pages...")
    print()

    # Find all product pages with seo-mega
    pages = list(PAGES_DIR.glob("*.html"))
    modified_count = 0

    for page in sorted(pages):
        if integrate_configurateur(page):
            modified_count += 1

    print()
    print(f"✅ Terminé ! {modified_count} pages modifiées sur {len(pages)} pages total.")

if __name__ == '__main__':
    main()
