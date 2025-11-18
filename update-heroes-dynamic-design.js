const fs = require('fs');
const path = require('path');
const glob = require('glob');

// NOUVEAU DESIGN HERO DYNAMIQUE ET MODERNE
const modernDynamicHeroCSS = `        /* ===== HERO MODERNE DYNAMIQUE - DESIGN 2024 ===== */
        .hero-sport,
        .hero-faq,
        .hero-livraison,
        .hero-contact,
        .hero-budget,
        .hero-retours,
        .hero-revendeur,
        .hero-page,
        .hero-innovation-v2 {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin-top: 80px;
            padding: 4rem 5% 3rem;
            min-height: auto !important;
            position: relative !important;
            overflow: hidden;
        }

        /* Formes décoratives en arrière-plan */
        .hero-sport::before,
        .hero-faq::before,
        .hero-livraison::before,
        .hero-contact::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,75,38,0.08) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            z-index: 0;
        }

        .hero-sport::after,
        .hero-faq::after,
        .hero-livraison::after,
        .hero-contact::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0,123,255,0.05) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -100px;
            z-index: 0;
        }

        .hero-sport-content,
        .hero-faq-content,
        .hero-livraison-content,
        .hero-contact-content,
        .hero-budget-content,
        .hero-retours-content,
        .hero-revendeur-content,
        .hero-page-content,
        .hero-innovation-content-v2 {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
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
        .hero-page-left,
        .hero-innovation-left {
            text-align: left;
            animation: fadeInLeft 0.8s ease-out;
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-sport-right,
        .hero-faq-right,
        .hero-livraison-right,
        .hero-contact-right,
        .hero-budget-right,
        .hero-retours-right,
        .hero-revendeur-right,
        .hero-page-right,
        .hero-innovation-right {
            background: #fff;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
            position: relative;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
            transition: all 0.4s ease;
        }

        .hero-sport-right:hover,
        .hero-faq-right:hover,
        .hero-livraison-right:hover,
        .hero-contact-right:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-sport-eyebrow,
        .hero-faq-eyebrow,
        .hero-livraison-eyebrow,
        .hero-contact-eyebrow,
        .hero-budget-eyebrow,
        .hero-retours-eyebrow,
        .hero-revendeur-eyebrow,
        .hero-page-eyebrow,
        .hero-eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
            padding: 10px 24px;
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            margin-bottom: 28px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(255,75,38,0.3);
        }

        .hero-sport-title,
        .hero-faq-title,
        .hero-livraison-title,
        .hero-contact-title,
        .hero-budget-title,
        .hero-retours-title,
        .hero-revendeur-title,
        .hero-page-title,
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(48px, 7vw, 72px);
            line-height: 1;
            letter-spacing: 1px;
            margin-bottom: 24px;
            color: #1a1a1a;
            background: linear-gradient(135deg, #1a1a1a 0%, #4a4a4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        .hero-subtitle {
            font-size: clamp(16px, 2.5vw, 20px);
            line-height: 1.6;
            color: #495057;
            margin-bottom: 40px;
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
            margin-bottom: 0;
        }

        .btn-hero-primary,
        .btn-primary {
            padding: 18px 40px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            background: linear-gradient(135deg, #FF4B26 0%, #E63910 100%);
            color: #fff;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(255,75,38,0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-hero-primary::before,
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-hero-primary:hover::before,
        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-hero-primary:hover,
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,75,38,0.4);
        }

        .btn-hero-secondary,
        .btn-secondary {
            padding: 18px 40px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            background: transparent;
            color: #1a1a1a;
            border: 2px solid #1a1a1a;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
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
            border-color: #1a1a1a;
        }

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
            width: 32px;
            height: 32px;
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
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1.4;
        }

        /* Masquer les anciens éléments */
        .hero-sport-background,
        .hero-sport-overlay,
        .hero-contact-background,
        .hero-faq-background,
        .hero-livraison-background,
        .hero-budget-overlay,
        .hero-budget-image,
        .breadcrumb {
            display: none !important;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-sport,
            .hero-faq,
            .hero-livraison,
            .hero-contact {
                padding: 3rem 5% 2rem;
            }

            .hero-sport-content,
            .hero-faq-content,
            .hero-livraison-content,
            .hero-contact-content,
            .hero-budget-content,
            .hero-retours-content,
            .hero-revendeur-content,
            .hero-page-content,
            .hero-innovation-content-v2 {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .hero-sport-left,
            .hero-faq-left,
            .hero-livraison-left,
            .hero-contact-left,
            .hero-budget-left,
            .hero-retours-left,
            .hero-revendeur-left {
                text-align: center;
            }

            .hero-sport-features,
            .hero-contact-badges {
                grid-template-columns: 1fr;
            }

            .hero-sport-eyebrow,
            .hero-faq-eyebrow,
            .hero-livraison-eyebrow,
            .hero-contact-eyebrow {
                display: inline-block;
            }
        }`;

console.log('🚀 Application du nouveau design DYNAMIQUE pour tous les hero headers...\n');

let modifiedCount = 0;

// Modifier toutes les pages products/
const productPages = glob.sync('pages/products/*.html', { cwd: __dirname });

productPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    if (!content.includes('class="hero-')) {
        return;
    }

    // Remplacer tout le CSS hero existant
    const styleRegex = /(<style>[\s\S]*?)(<\/style>)/;
    const styleMatch = content.match(styleRegex);

    if (styleMatch) {
        let styleContent = styleMatch[1];

        // Supprimer tous les anciens CSS de hero
        styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?(@media|<\/style>)/g, '$1');
        styleContent = styleContent.replace(/\.hero-sport[\s\S]*?(\@media|\}[\s\S]*?@media)/g, '$1');
        styleContent = styleContent.replace(/\.feature-badge[\s\S]*?(\@media)/g, '$1');

        // Ajouter le nouveau CSS
        const newStyleBlock = styleContent + '\n' + modernDynamicHeroCSS + '\n    ' + styleMatch[2];
        content = content.replace(styleRegex, newStyleBlock);
    }

    fs.writeFileSync(fullPath, content, 'utf-8');
    console.log(`✅ ${pagePath}`);
    modifiedCount++;
});

// Modifier toutes les pages info/
const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    if (!content.includes('class="hero-')) {
        return;
    }

    // Ajouter ou remplacer le CSS
    if (content.includes('<style>')) {
        const styleRegex = /(<style>[\s\S]*?)(<\/style>)/;
        const styleMatch = content.match(styleRegex);
        if (styleMatch) {
            let styleContent = styleMatch[1];
            styleContent = styleContent.replace(/\/\*[\s\S]*?HERO[\s\S]*?\*\/[\s\S]*?(@media|<\/style>)/g, '$1');
            const newStyleBlock = styleContent + '\n' + modernDynamicHeroCSS + '\n    ' + styleMatch[2];
            content = content.replace(styleRegex, newStyleBlock);
        }
    } else {
        // Ajouter un bloc style avant </head>
        const headEndRegex = /(<\/head>)/;
        const styleBlock = `    <style>\n${modernDynamicHeroCSS}\n    </style>\n</head>`;
        content = content.replace(headEndRegex, styleBlock);
    }

    fs.writeFileSync(fullPath, content, 'utf-8');
    console.log(`✅ ${pagePath}`);
    modifiedCount++;
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages avec le nouveau design DYNAMIQUE.`);
console.log('✨ Features : Gradients, animations, hover effects, formes décoratives, shadows modernes !');
