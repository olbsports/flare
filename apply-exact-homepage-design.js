const fs = require('fs');
const path = require('path');
const glob = require('glob');

// CSS EXACT DE LA PAGE D'ACCUEIL - ADAPTÉ POUR TOUTES LES PAGES
const exactHomepageHeroCSS = `
        /* ===== HERO MODERNE - DESIGN IDENTIQUE PAGE D'ACCUEIL ===== */
        .hero-sport,
        .hero-faq,
        .hero-livraison,
        .hero-contact,
        .hero-budget,
        .hero-retours,
        .hero-revendeur,
        .hero-page,
        .hero-innovation-v2 {
            position: relative;
            min-height: 70vh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            align-items: center;
            padding: 140px 5% 100px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f1f3f5 100%);
            gap: 80px;
            max-width: 100%;
            overflow: hidden;
            margin-top: 0 !important;
        }

        /* Formes décoratives animées */
        .hero-sport::before,
        .hero-faq::before,
        .hero-livraison::before,
        .hero-contact::before,
        .hero-budget::before,
        .hero-retours::before,
        .hero-revendeur::before,
        .hero-page::before {
            content: '';
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,75,38,0.12) 0%, transparent 70%);
            border-radius: 50%;
            top: -300px;
            right: -200px;
            z-index: 0;
            animation: pulse 8s ease-in-out infinite;
        }

        .hero-sport::after,
        .hero-faq::after,
        .hero-livraison::after,
        .hero-contact::after,
        .hero-budget::after,
        .hero-retours::after,
        .hero-revendeur::after,
        .hero-page::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(0,123,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -200px;
            left: -150px;
            z-index: 0;
            animation: pulse 10s ease-in-out infinite reverse;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        /* === CONTENU GAUCHE === */
        .hero-sport-content,
        .hero-faq-content,
        .hero-livraison-content,
        .hero-contact-content,
        .hero-budget-content,
        .hero-retours-content,
        .hero-revendeur-content,
        .hero-page-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-sport-left,
        .hero-faq-left,
        .hero-livraison-left,
        .hero-contact-left,
        .hero-budget-left,
        .hero-retours-left,
        .hero-revendeur-left,
        .hero-page-left {
            animation: fadeInLeft 1s ease;
            position: relative;
            z-index: 1;
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .hero-sport-right,
        .hero-faq-right,
        .hero-livraison-right,
        .hero-contact-right,
        .hero-budget-right,
        .hero-retours-right,
        .hero-revendeur-right,
        .hero-page-right {
            animation: fadeInRight 1s ease 0.3s backwards;
            position: relative;
            z-index: 1;
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .hero-sport-eyebrow,
        .hero-faq-eyebrow,
        .hero-livraison-eyebrow,
        .hero-contact-eyebrow,
        .hero-budget-eyebrow,
        .hero-retours-eyebrow,
        .hero-revendeur-eyebrow,
        .hero-page-eyebrow,
        .hero-eyebrow,
        .hero-eyebrow-v2 {
            display: inline-block;
            padding: 10px 24px;
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 32px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(255,75,38,0.3);
        }

        .hero-sport-title,
        .hero-faq-title,
        .hero-livraison-title,
        .hero-contact-title,
        .hero-budget-title,
        .hero-retours-title,
        .hero-revendeur-title,
        .hero-page-title,
        .hero-title,
        .hero-title-v2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(64px, 8vw, 120px);
            font-weight: 700;
            line-height: 0.95;
            letter-spacing: 2px;
            margin-bottom: 28px;
            color: #1a1a1a;
        }

        .hero-sport-subtitle,
        .hero-faq-subtitle,
        .hero-livraison-subtitle,
        .hero-contact-subtitle,
        .hero-budget-subtitle,
        .hero-budget-desc,
        .hero-retours-subtitle,
        .hero-revendeur-subtitle,
        .hero-page-subtitle,
        .hero-subtitle,
        .hero-subtitle-v2 {
            font-size: clamp(16px, 2vw, 20px);
            line-height: 1.7;
            color: #495057;
            margin-bottom: 48px;
            max-width: 600px;
            font-weight: 400;
        }

        .hero-sport-cta,
        .hero-faq-cta,
        .hero-livraison-cta,
        .hero-contact-cta,
        .hero-budget-cta,
        .hero-retours-cta,
        .hero-revendeur-cta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-hero-primary,
        .btn-primary {
            padding: 18px 48px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-hero-primary::before,
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-hero-primary:hover::before,
        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-hero-primary:hover,
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }

        .btn-hero-secondary,
        .btn-secondary {
            padding: 18px 48px;
            background: transparent;
            color: #1a1a1a;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 50px;
            border: 2px solid #1a1a1a;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
        }

        .btn-hero-secondary::before,
        .btn-secondary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: #1a1a1a;
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
            z-index: -1;
        }

        .btn-hero-secondary:hover::before,
        .btn-secondary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-hero-secondary:hover,
        .btn-secondary:hover {
            color: #fff;
        }

        /* === CONTENU DROITE (FEATURES/STATS) === */
        .hero-sport-features,
        .hero-faq-features,
        .hero-livraison-features,
        .hero-contact-features,
        .hero-budget-features,
        .hero-retours-features,
        .hero-revendeur-features,
        .hero-contact-badges {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #fff;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }

        .hero-sport-features:hover,
        .hero-faq-features:hover,
        .hero-contact-badges:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.15);
        }

        .feature-badge,
        .contact-badge {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-badge::before,
        .contact-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #FF4B26 0%, #E63910 100%);
            transition: width 0.3s ease;
        }

        .feature-badge:hover::before,
        .contact-badge:hover::before {
            width: 100%;
            opacity: 0.05;
        }

        .feature-badge:hover,
        .contact-badge:hover {
            background: #fff;
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .feature-badge svg,
        .contact-badge svg {
            width: 40px;
            height: 40px;
            color: #FF4B26;
            fill: #FF4B26;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .feature-badge:hover svg,
        .contact-badge:hover svg {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-badge span,
        .contact-badge span {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1.4;
        }

        /* Masquer anciens éléments */
        .hero-sport-background,
        .hero-sport-overlay,
        .hero-contact-background,
        .hero-faq-background,
        .hero-livraison-background,
        .hero-budget-overlay,
        .hero-budget-image,
        .hero-innovation-bg,
        .breadcrumb {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-sport,
            .hero-faq,
            .hero-livraison,
            .hero-contact,
            .hero-budget {
                grid-template-columns: 1fr;
                padding: 120px 5% 80px;
                gap: 60px;
                min-height: auto;
            }

            .hero-sport-content,
            .hero-faq-content,
            .hero-livraison-content,
            .hero-contact-content {
                grid-template-columns: 1fr;
            }

            .hero-sport-left,
            .hero-faq-left,
            .hero-livraison-left,
            .hero-contact-left {
                text-align: center;
            }

            .hero-sport-subtitle,
            .hero-faq-subtitle {
                margin: 0 auto 48px;
            }

            .hero-sport-cta,
            .hero-faq-cta {
                justify-content: center;
            }

            .hero-sport-features,
            .hero-contact-badges {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero-sport,
            .hero-faq {
                padding: 100px 5% 60px;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }`;

console.log('🚀 Application du design EXACT de la page d\'accueil à TOUTES les pages...\n');

let modifiedCount = 0;
let errors = [];

// FONCTION POUR REMPLACER LE CSS DE MANIÈRE SÉCURISÉE
function replaceHeroCSS(content, filePath) {
    try {
        // Chercher le bloc <style>
        const styleStartMatch = content.match(/<style>/i);
        const styleEndMatch = content.match(/<\/style>/i);

        if (!styleStartMatch || !styleEndMatch) {
            return content; // Pas de bloc style, on skip
        }

        const styleStart = styleStartMatch.index;
        const styleEnd = styleEndMatch.index + 8; // longueur de "</style>"

        // Extraire le contenu du style
        let styleContent = content.substring(styleStart + 7, styleEnd - 8);

        // Supprimer UNIQUEMENT le CSS hero (tout ce qui commence par /* === et contient HERO)
        // On garde tout le reste intact
        styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?(?=(\/\*|@media \(max-width:|<\/style>|$))/gi, '');

        // Supprimer les anciennes classes hero
        styleContent = styleContent.replace(/\.hero-sport[\s\S]*?(?=(\.(?!hero-)\w+|@media \(max-width:|<\/style>|$))/gi, '');
        styleContent = styleContent.replace(/\.hero-faq[\s\S]*?(?=(\.(?!hero-)\w+|@media \(max-width:|<\/style>|$))/gi, '');
        styleContent = styleContent.replace(/\.hero-livraison[\s\S]*?(?=(\.(?!hero-)\w+|@media \(max-width:|<\/style>|$))/gi, '');
        styleContent = styleContent.replace(/\.feature-badge[\s\S]*?(?=(\.(?!feature-)\w+|@media \(max-width:|<\/style>|$))/gi, '');

        // Nettoyer les lignes vides multiples
        styleContent = styleContent.replace(/\n\s*\n\s*\n/g, '\n\n');

        // Reconstruire le bloc style avec le nouveau CSS
        const newStyleBlock = `<style>${styleContent}\n${exactHomepageHeroCSS}\n    </style>`;

        // Reconstruire le fichier
        const newContent = content.substring(0, styleStart) + newStyleBlock + content.substring(styleEnd);

        return newContent;
    } catch (error) {
        errors.push({ file: filePath, error: error.message });
        return content; // En cas d'erreur, on retourne le contenu original
    }
}

// Modifier toutes les pages products/
const productPages = glob.sync('pages/products/*.html', { cwd: __dirname });

productPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);

    try {
        let content = fs.readFileSync(fullPath, 'utf-8');

        // Si pas de hero, skip
        if (!content.includes('class="hero-')) {
            return;
        }

        const newContent = replaceHeroCSS(content, pagePath);

        // Écrire seulement si changement
        if (newContent !== content) {
            fs.writeFileSync(fullPath, newContent, 'utf-8');
            console.log(`✅ ${pagePath}`);
            modifiedCount++;
        }
    } catch (error) {
        errors.push({ file: pagePath, error: error.message });
        console.log(`❌ ${pagePath} - ${error.message}`);
    }
});

// Modifier toutes les pages info/
const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);

    try {
        let content = fs.readFileSync(fullPath, 'utf-8');

        // Si pas de hero, skip
        if (!content.includes('class="hero-')) {
            return;
        }

        const newContent = replaceHeroCSS(content, pagePath);

        // Écrire seulement si changement
        if (newContent !== content) {
            fs.writeFileSync(fullPath, newContent, 'utf-8');
            console.log(`✅ ${pagePath}`);
            modifiedCount++;
        }
    } catch (error) {
        errors.push({ file: pagePath, error: error.message });
        console.log(`❌ ${pagePath} - ${error.message}`);
    }
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages modifiées avec le design EXACT de la page d'accueil.`);

if (errors.length > 0) {
    console.log(`\n⚠️  ${errors.length} erreurs rencontrées :`);
    errors.forEach(e => console.log(`  - ${e.file}: ${e.error}`));
}

console.log('\n✨ Design identique partout : gradient, formes animées, boutons modernes !');
console.log('🔒 Code non-hero préservé intact dans chaque fichier.');
