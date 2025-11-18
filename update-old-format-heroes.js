const fs = require('fs');
const path = require('path');

// Nouveau CSS pour hero 2 colonnes
const newHeroCSS = `        /* ===== HERO 2 COLONNES COMPACT AVEC FOND GRIS ===== */
        .hero-sport {
            background: #f5f5f5;
            margin-top: 80px;
            padding: 2.5rem 5% 2rem;
        }
        .hero-sport-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .hero-sport-left {
            text-align: left;
        }
        .hero-sport-right {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .hero-sport-eyebrow {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #FF4B26;
            padding: 10px 24px;
            border: 1px solid #FF4B26;
            margin-bottom: 24px;
        }
        .hero-sport-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(42px, 6vw, 64px);
            line-height: 1.05;
            letter-spacing: 2px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        .hero-sport-subtitle {
            font-size: clamp(15px, 2vw, 18px);
            line-height: 1.7;
            color: #666;
            margin-bottom: 32px;
        }
        .hero-sport-cta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .btn-hero-primary {
            padding: 16px 36px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            background: #FF4B26;
            color: #fff;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .btn-hero-primary:hover {
            background: #E63910;
            transform: translateY(-2px);
        }
        .btn-hero-secondary {
            padding: 16px 36px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            background: transparent;
            color: #1a1a1a;
            border: 2px solid #1a1a1a;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .btn-hero-secondary:hover {
            background: #1a1a1a;
            color: #fff;
        }
        .hero-sport-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .feature-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f8f8f8;
            border-radius: 8px;
        }
        .feature-badge svg {
            width: 24px;
            height: 24px;
            color: #FF4B26;
            fill: #FF4B26;
            flex-shrink: 0;
        }
        .feature-badge span {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .breadcrumb {
            display: none;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-sport-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .hero-sport-left {
                text-align: center;
            }
        }`;

// Pages à mettre à jour (sans le nouveau format)
const pagesToUpdate = [
    'pages/products/bandeaux-running-personnalises.html',
    'pages/products/casquettes-club-personnalisees.html',
    'pages/products/chaussettes-sport-personnalisees.html',
    'pages/products/collection-2025.html',
    'pages/products/ensembles-entrainement.html',
    'pages/products/equipement-basketball-personnalise-sublimation.html',
    'pages/products/equipement-club-volume.html',
    'pages/products/equipement-cyclisme-velo-personnalise-sublimation.html',
    'pages/products/equipement-football-personnalise-sublimation.html',
    'pages/products/equipement-handball-personnalise-sublimation.html',
    'pages/products/equipement-petanque-personnalise-club.html',
    'pages/products/equipement-rugby-personnalise-sublimation.html',
    'pages/products/equipement-running-course-pied-personnalise.html',
    'pages/products/equipement-triathlon-personnalise-sublimation.html',
    'pages/products/equipement-volleyball-personnalise-sublimation.html',
    'pages/products/equipements-sportifs-pas-cher.html',
    'pages/products/maillot.html',
    'pages/products/maillots-club-petit-budget.html',
    'pages/products/merchandising-accessoires-club-personnalises.html',
    'pages/products/pack-club-complet.html',
    'pages/products/pantalons-entrainement-personnalises.html',
    'pages/products/sacs-sport-personnalises.html',
    'pages/products/tenues-match-completes.html'
];

console.log('🚀 Mise à jour des heroes avec ancien format vers 2 colonnes...\n');

let modifiedCount = 0;

pagesToUpdate.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);

    if (!fs.existsSync(fullPath)) {
        console.log(`⚠️  Fichier non trouvé: ${pagePath}`);
        return;
    }

    let content = fs.readFileSync(fullPath, 'utf-8');

    // Vérifier si la page a un hero-sport
    if (!content.includes('<section class="hero-sport">')) {
        console.log(`⏭️  Pas de hero-sport: ${pagePath}`);
        return;
    }

    // 1. Supprimer ou remplacer le CSS minifié du hero dans <style>
    // Chercher le bloc <style> et injecter le nouveau CSS hero
    const styleRegex = /(<style>[\s\S]*?)(<\/style>)/;
    const styleMatch = content.match(styleRegex);

    if (styleMatch) {
        // Ajouter le nouveau CSS avant </style>
        const newStyleBlock = styleMatch[1] + '\n' + newHeroCSS + '\n    ' + styleMatch[2];
        content = content.replace(styleRegex, newStyleBlock);
    }

    // 2. Transformer la structure HTML du hero
    // Ancien format:
    // <section class="hero-sport">
    //   <div class="hero-sport-background">...</div>
    //   <div class="hero-sport-content">
    //     <div class="breadcrumb">...</div>
    //     <h1>...</h1>
    //     <p class="hero-sport-desc">...</p>
    //     <div class="hero-sport-cta">...</div>
    //     <div class="hero-sport-features">...</div>
    //   </div>
    // </section>

    // Nouveau format:
    // <section class="hero-sport">
    //   <div class="hero-sport-content">
    //     <div class="hero-sport-left">
    //       <span class="hero-sport-eyebrow">...</span>
    //       <h1 class="hero-sport-title">...</h1>
    //       <p class="hero-sport-subtitle">...</p>
    //       <div class="hero-sport-cta">...</div>
    //     </div>
    //     <div class="hero-sport-right">
    //       <div class="hero-sport-features">...</div>
    //     </div>
    //   </div>
    // </section>

    const heroSectionRegex = /<section class="hero-sport">([\s\S]*?)<\/section>/;
    const heroMatch = content.match(heroSectionRegex);

    if (heroMatch) {
        const heroContent = heroMatch[1];

        // Extraire les éléments
        const eyebrowMatch = heroContent.match(/<span class="hero-sport-eyebrow">([\s\S]*?)<\/span>/);
        const titleMatch = heroContent.match(/<h1[^>]*class="hero-sport-title"[^>]*>([\s\S]*?)<\/h1>/);
        const descMatch = heroContent.match(/<p[^>]*class="hero-sport-desc"[^>]*>([\s\S]*?)<\/p>/);
        const ctaMatch = heroContent.match(/(<div class="hero-sport-cta">[\s\S]*?<\/div>)/);
        const featuresMatch = heroContent.match(/(<div class="hero-sport-features">[\s\S]*?<\/div>\s*<\/div>)/);

        // Extraire le titre complet (avec eyebrow, main, sub)
        let eyebrow = '';
        let title = '';
        let subtitle = '';

        if (titleMatch) {
            const titleHTML = titleMatch[1];
            const eyebrowInTitle = titleHTML.match(/<span class="hero-sport-eyebrow">([\s\S]*?)<\/span>/);
            const mainInTitle = titleHTML.match(/<span class="hero-sport-main">([\s\S]*?)<\/span>/);
            const subInTitle = titleHTML.match(/<span class="hero-sport-sub">([\s\S]*?)<\/span>/);

            if (eyebrowInTitle) {
                eyebrow = eyebrowInTitle[1].trim();
            }
            if (mainInTitle) {
                title = mainInTitle[1].trim();
            }
            if (subInTitle) {
                subtitle = subInTitle[1].trim();
            }
        }

        // Si pas de structure span, essayer de l'extraire directement
        if (!eyebrow && eyebrowMatch) {
            eyebrow = eyebrowMatch[1].trim();
        }
        if (!subtitle && descMatch) {
            subtitle = descMatch[1].trim().replace(/<br>/g, ' · ');
        }

        const ctaHTML = ctaMatch ? ctaMatch[1] : '';
        const featuresHTML = featuresMatch ? featuresMatch[1] : '';

        // Construire le nouveau HTML
        const newHeroHTML = `
        <section class="hero-sport">
        <div class="hero-sport-content">
            <div class="hero-sport-left">
                <span class="hero-sport-eyebrow">${eyebrow}</span>
                <h1 class="hero-sport-title">${title}</h1>
                <p class="hero-sport-subtitle">${subtitle}</p>

                ${ctaHTML}
            </div>

            <div class="hero-sport-right">
                ${featuresHTML}
            </div>
        </div>
    </section>`;

        content = content.replace(heroSectionRegex, newHeroHTML);

        // Écrire le fichier
        fs.writeFileSync(fullPath, content, 'utf-8');
        console.log(`✅ Modifié: ${pagePath}`);
        modifiedCount++;
    }
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages modifiées avec le nouveau hero 2 colonnes.`);
console.log('📐 Nouveau design : 2 colonnes, fond gris, card blanche, plus compact !');
