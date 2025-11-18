const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🚀 Refonte des hero headers - 2 colonnes compact avec fond gris...\n');

// Nouveau CSS pour hero 2 colonnes compact
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
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 6px;
        }
        .btn-hero-primary:hover {
            background: #E63910;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,75,38,0.3);
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
            border: 2px solid rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            border-radius: 6px;
        }
        .btn-hero-secondary:hover {
            background: #1a1a1a;
            color: #fff;
            border-color: #1a1a1a;
        }
        .hero-sport-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .feature-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1a1a1a;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 18px;
            background: #fafafa;
            border-radius: 8px;
            border-left: 3px solid #FF4B26;
        }
        .feature-badge svg {
            width: 22px;
            height: 22px;
            color: #FF4B26;
            flex-shrink: 0;
        }
        /* Masquer les anciens éléments du hero */
        .hero-sport-background,
        .hero-sport-overlay,
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

// Trouver toutes les pages HTML
const pagesProducts = glob.sync('pages/products/*.html', { cwd: __dirname });
const pagesInfo = glob.sync('pages/info/*.html', { cwd: __dirname });
const allPages = [...pagesProducts, ...pagesInfo];

let modifiedCount = 0;

allPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Vérifier si la page a le hero-sport
    if (!content.includes('.hero-sport {')) {
        return;
    }

    // 1. Remplacer l'ancien CSS du hero par le nouveau
    const oldHeroRegex = /\/\* ===== HERO STYLE EXACT COMME FAQ\/GUIDE ===== \*\/[\s\S]*?\.breadcrumb \{[\s\S]*?\}/;

    if (oldHeroRegex.test(content)) {
        content = content.replace(oldHeroRegex, newHeroCSS);
    }

    // 2. Restructurer le HTML pour avoir 2 colonnes
    // Chercher la structure actuelle du hero
    const heroHTMLRegex = /<section class="hero-sport">\s*<div class="hero-sport-content">([\s\S]*?)<\/div>\s*<\/section>/;
    const heroMatch = content.match(heroHTMLRegex);

    if (heroMatch) {
        const heroInnerContent = heroMatch[1];

        // Extraire les différentes parties
        const eyebrowMatch = heroInnerContent.match(/(<span class="hero-sport-eyebrow">[\s\S]*?<\/span>)/);
        const titleMatch = heroInnerContent.match(/(<h1 class="hero-sport-title">[\s\S]*?<\/h1>)/);
        const subtitleMatch = heroInnerContent.match(/(<p class="hero-sport-subtitle">[\s\S]*?<\/p>)/);
        const ctaMatch = heroInnerContent.match(/(<div class="hero-sport-cta">[\s\S]*?<\/div>)/);
        const featuresMatch = heroInnerContent.match(/(<div class="hero-sport-features">[\s\S]*?<\/div>\s*<\/div>)/);

        if (eyebrowMatch && titleMatch && subtitleMatch && ctaMatch && featuresMatch) {
            // Construire la nouvelle structure HTML
            const newHeroHTML = `    <section class="hero-sport">
        <div class="hero-sport-content">
            <div class="hero-sport-left">
                ${eyebrowMatch[1].trim()}
                ${titleMatch[1].trim()}
                ${subtitleMatch[1].trim()}

                ${ctaMatch[1].trim()}
            </div>

            <div class="hero-sport-right">
                ${featuresMatch[1].trim()}
            </div>
        </div>
    </section>`;

            content = content.replace(heroHTMLRegex, newHeroHTML);
        }
    }

    fs.writeFileSync(fullPath, content, 'utf-8');
    modifiedCount++;
    console.log(`✅ Modifié: ${pagePath}`);
});

console.log(`\n🎉 Terminé ! ${modifiedCount} pages modifiées avec le nouveau hero 2 colonnes.`);
console.log('📐 Nouveau design : 2 colonnes, fond gris, card blanche, plus compact !');
