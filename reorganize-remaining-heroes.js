const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🔧 Réorganisation des heroes restants...\n');

let fixedCount = 0;

const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    let modified = false;

    // 1. HERO-RETOURS (simple structure)
    const retoursPattern = /<section class="hero-retours">\s*<div class="hero-retours-content">\s*<span class="hero-retours-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-retours-title">([^<]+)<\/h1>\s*<p class="hero-retours-subtitle">([^<]+)<\/p>\s*<\/div>\s*<\/section>/;

    if (content.match(retoursPattern)) {
        const match = content.match(retoursPattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];

        const newHeroHTML = `<section class="hero-retours">
        <div class="hero-retours-content">
            <div class="hero-retours-left">
                <span class="hero-retours-eyebrow">${eyebrow}</span>
                <h1 class="hero-retours-title">${title}</h1>
                <p class="hero-retours-subtitle">${subtitle}</p>

                <div class="hero-retours-cta">
                    <a href="/pages/info/contact.html" class="btn-hero-primary">
                        Demander un devis
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="#garantie-section" class="btn-hero-secondary">En savoir plus</a>
                </div>
            </div>

            <div class="hero-retours-right">
                <div class="hero-retours-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Garantie fabrication</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 4V8H8M20 20V16H16M4 8H8L12 4L16 8H20M4 16H8L12 20L16 16H20" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Refabrication gratuite</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                        </svg>
                        <span>Satisfaction garantie</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 8L10.89 13.26C11.54 13.67 12.46 13.67 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Support client 7j/7</span>
                    </div>
                </div>
            </div>
        </div>
    </section>`;

        content = content.replace(retoursPattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-retours: ${pagePath}`);
    }

    // 2. HERO-REVENDEUR (already has CTA)
    const revendeurPattern = /<section class="hero-revendeur">\s*<div class="hero-revendeur-content">\s*<span class="hero-revendeur-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-revendeur-title">([^<]+)<\/h1>\s*<p class="hero-revendeur-subtitle">([^<]+)<\/p>\s*<div class="hero-revendeur-cta">([\s\S]*?)<\/div>\s*<\/div>\s*<\/section>/;

    if (content.match(revendeurPattern)) {
        const match = content.match(revendeurPattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];
        const cta = match[4];

        const newHeroHTML = `<section class="hero-revendeur">
        <div class="hero-revendeur-content">
            <div class="hero-revendeur-left">
                <span class="hero-revendeur-eyebrow">${eyebrow}</span>
                <h1 class="hero-revendeur-title">${title}</h1>
                <p class="hero-revendeur-subtitle">${subtitle}</p>

                <div class="hero-revendeur-cta">${cta}</div>
            </div>

            <div class="hero-revendeur-right">
                <div class="hero-revendeur-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                        </svg>
                        <span>Tarifs pro négociés</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M17 20H22V18C22 16.3431 20.6569 15 19 15C18.0444 15 17.1931 15.4468 16.6438 16.1429M17 20H7M17 20V18C17 17.3438 16.8736 16.717 16.6438 16.1429M7 20H2V18C2 16.3431 3.34315 15 5 15C5.95561 15 6.80686 15.4468 7.35625 16.1429M7 20V18C7 17.3438 7.12642 16.717 7.35625 16.1429M7.35625 16.1429C8.0935 14.301 9.89482 13 12 13C14.1052 13 15.9065 14.301 16.6438 16.1429M15 7C15 8.65685 13.6569 10 12 10C10.3431 10 9 8.65685 9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7ZM21 10C21 11.1046 20.1046 12 19 12C17.8954 12 17 11.1046 17 10C17 8.89543 17.8954 8 19 8C20.1046 8 21 8.89543 21 10ZM7 10C7 11.1046 6.10457 12 5 12C3.89543 12 3 11.1046 3 10C3 8.89543 3.89543 8 5 8C6.10457 8 7 8.89543 7 10Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Support commercial dédié</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Catalogue complet</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 10V3L4 14H11V21L20 10H13Z" fill="currentColor"/>
                        </svg>
                        <span>Livraison rapide</span>
                    </div>
                </div>
            </div>
        </div>
    </section>`;

        content = content.replace(revendeurPattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-revendeur: ${pagePath}`);
    }

    // 3. HERO-PAGE (generic pages)
    const pagePattern = /<section class="hero-page">\s*<div class="hero-page-content">\s*<span class="hero-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-title">([^<]+)<\/h1>\s*<p class="hero-subtitle">([\s\S]*?)<\/p>\s*<\/div>\s*<\/section>/;

    if (content.match(pagePattern)) {
        const match = content.match(pagePattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];

        const newHeroHTML = `<section class="hero-page">
        <div class="hero-page-content">
            <div class="hero-page-left">
                <span class="hero-page-eyebrow">${eyebrow}</span>
                <h1 class="hero-page-title">${title}</h1>
                <p class="hero-page-subtitle">${subtitle}</p>

                <div class="hero-page-cta">
                    <a href="/pages/info/contact.html" class="btn-hero-primary">
                        Demander un devis gratuit
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="#content-section" class="btn-hero-secondary">Découvrir</a>
                </div>
            </div>

            <div class="hero-page-right">
                <div class="hero-page-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                        </svg>
                        <span>Service professionnel</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Qualité garantie</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 10V3L4 14H11V21L20 10H13Z" fill="currentColor"/>
                        </svg>
                        <span>Livraison rapide</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 8L10.89 13.26C11.54 13.67 12.46 13.67 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Support réactif</span>
                    </div>
                </div>
            </div>
        </div>
    </section>`;

        content = content.replace(pagePattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-page: ${pagePath}`);
    }

    // 4. HERO-BUDGET (complex with breadcrumb)
    const budgetPattern = /<section class="hero-budget">\s*<div class="hero-budget-overlay"><\/div>\s*<div class="hero-budget-content">([\s\S]*?)<div class="hero-budget-features">([\s\S]*?)<\/div>\s*<\/div>\s*<\/section>/;

    if (content.match(budgetPattern)) {
        const match = content.match(budgetPattern);
        const contentPart = match[1];
        const features = match[2];

        // Extraire les parties individuelles
        const eyebrowMatch = contentPart.match(/<span class="hero-budget-eyebrow">([^<]+)<\/span>/);
        const titleMatch = contentPart.match(/<h1 class="hero-budget-title">\s*([^<]+)\s*<\/h1>/);
        const subtitleMatch = contentPart.match(/<div class="hero-budget-subtitle">\s*([^<]+)\s*<\/div>/);
        const descMatch = contentPart.match(/<p class="hero-budget-desc">([\s\S]*?)<\/p>/);
        const ctaMatch = contentPart.match(/<div class="hero-budget-cta">([\s\S]*?)<\/div>/);

        if (eyebrowMatch && titleMatch) {
            const eyebrow = eyebrowMatch[1];
            const title = titleMatch[1];
            const subtitle = subtitleMatch ? subtitleMatch[1] : '';
            const desc = descMatch ? descMatch[1] : '';
            const cta = ctaMatch ? ctaMatch[1] : '';

            const newHeroHTML = `<section class="hero-budget">
        <div class="hero-budget-content">
            <div class="hero-budget-left">
                <span class="hero-budget-eyebrow">${eyebrow}</span>
                <h1 class="hero-budget-title">${title}</h1>
                <div class="hero-budget-subtitle">${subtitle}</div>
                <p class="hero-budget-desc">${desc}</p>

                <div class="hero-budget-cta">${cta}</div>
            </div>

            <div class="hero-budget-right">
                <div class="hero-budget-features">${features}</div>
            </div>
        </div>
    </section>`;

            content = content.replace(budgetPattern, newHeroHTML);
            modified = true;
            console.log(`  ✓ Réorganisé hero-budget: ${pagePath}`);
        }
    }

    // Sauvegarder si modifié
    if (modified) {
        fs.writeFileSync(fullPath, content, 'utf-8');
        fixedCount++;
    }
});

console.log(`\n✅ ${fixedCount} pages supplémentaires réorganisées`);
console.log('✨ Structure 2 colonnes appliquée avec les mêmes codes couleurs !');
