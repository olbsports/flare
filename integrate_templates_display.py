#!/usr/bin/env python3
"""
Intègre le script templates-display.js dans toutes les pages produits
"""

import os
import re
from pathlib import Path

# Dossier des pages produits
PRODUITS_DIR = Path(__file__).parent / 'pages' / 'produits'

# Script à ajouter
SCRIPT_TAG = '<script src="../../assets/js/templates-display.js" defer></script>'

def integrate_templates_display():
    """Intègre le script dans toutes les pages produits"""

    # Trouver tous les fichiers FLARE-*.html
    files = list(PRODUITS_DIR.glob('FLARE-*.html'))

    print(f"📁 Trouvé {len(files)} fichiers produits")

    updated_count = 0

    for file_path in files:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Vérifier si le script n'est pas déjà présent
        if 'templates-display.js' in content:
            print(f"⏭️  Déjà intégré: {file_path.name}")
            continue

        # Chercher la balise </head>
        if '</head>' not in content:
            print(f"⚠️  Pas de </head> trouvé: {file_path.name}")
            continue

        # Ajouter le script juste avant </head>
        new_content = content.replace('</head>', f'    {SCRIPT_TAG}\n</head>')

        # Écrire le fichier
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)

        updated_count += 1
        print(f"✅ Intégré: {file_path.name}")

    print(f"\n🎉 Intégration terminée!")
    print(f"📊 {updated_count} fichiers mis à jour sur {len(files)}")

if __name__ == '__main__':
    integrate_templates_display()
