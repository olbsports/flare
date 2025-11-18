const fs = require('fs');
const path = require('path');
const glob = require('glob');

console.log('🔧 Réorganisation des heroes des pages info...\n');

let fixedCount = 0;

const infoPages = glob.sync('pages/info/*.html', { cwd: __dirname });

infoPages.forEach(pagePath => {
    const fullPath = path.join(__dirname, pagePath);
    let content = fs.readFileSync(fullPath, 'utf-8');

    // Ne traiter que les pages avec un hero-xxx
    if (!content.match(/class="hero-(faq|livraison|contact|budget|retours|revendeur|page|innovation)/)) {
        return;
    }

    let modified = false;

    // 1. NETTOYER LES @MEDIA DUPLIQUÉS
    // Supprimer les lignes dupliquées qui commencent par @media (max-width: 1024px) {
    const duplicateMediaRegex = /(\s*)@media \(max-width: 1024px\) \{\s*\n\s*@media \(max-width: 1024px\) \{\s*\n\s*@media \(max-width: (1024px|768px)\) \{\s*\n/g;

    if (content.match(duplicateMediaRegex)) {
        // Garder seulement la première occurrence
        content = content.replace(duplicateMediaRegex, '$1@media (max-width: 768px) {\n');
        modified = true;
        console.log(`  ✓ Nettoyé @media dupliqués: ${pagePath}`);
    }

    // 2. RÉORGANISER LA STRUCTURE HTML DES HEROES
    // Pattern pour hero-contact
    const contactHeroPattern = /<section class="hero-contact">\s*<div class="hero-contact-background"><\/div>\s*<div class="hero-contact-content">\s*<span class="hero-contact-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-contact-title">\s*([^<]+)\s*<\/h1>\s*<p class="hero-contact-subtitle">\s*([^<]+)\s*<\/p>\s*<div class="hero-contact-badges">([\s\S]*?)<\/div>\s*<\/div>\s*<\/section>/;

    if (content.match(contactHeroPattern)) {
        const match = content.match(contactHeroPattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];
        const badges = match[4];

        const newHeroHTML = `<section class="hero-contact">
        <div class="hero-contact-content">
            <div class="hero-contact-left">
                <span class="hero-contact-eyebrow">${eyebrow}</span>
                <h1 class="hero-contact-title">${title}</h1>
                <p class="hero-contact-subtitle">${subtitle}</p>

                <div class="hero-contact-cta">
                    <a href="/pages/info/contact.html#contact-form" class="btn-hero-primary">
                        Demander un devis
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="tel:+33123456789" class="btn-hero-secondary">Appeler maintenant</a>
                </div>
            </div>

            <div class="hero-contact-right">
                <div class="hero-contact-badges">${badges}</div>
            </div>
        </div>
    </section>`;

        content = content.replace(contactHeroPattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-contact en 2 colonnes: ${pagePath}`);
    }

    // Pattern pour hero-faq
    const faqHeroPattern = /<section class="hero-faq">\s*<div class="hero-faq-content">\s*<span class="hero-faq-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-faq-title">([^<]+)<\/h1>\s*<p class="hero-faq-subtitle">([^<]+)<\/p>\s*([\s\S]*?)<\/div>\s*<\/section>/;

    if (content.match(faqHeroPattern)) {
        const match = content.match(faqHeroPattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];
        const extraContent = match[4]; // search input, etc.

        const newHeroHTML = `<section class="hero-faq">
        <div class="hero-faq-content">
            <div class="hero-faq-left">
                <span class="hero-faq-eyebrow">${eyebrow}</span>
                <h1 class="hero-faq-title">${title}</h1>
                <p class="hero-faq-subtitle">${subtitle}</p>

                <div class="hero-faq-cta">
                    <a href="/pages/info/contact.html" class="btn-hero-primary">
                        Poser une question
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="#faq-categories" class="btn-hero-secondary">Parcourir les catégories</a>
                </div>
            </div>

            <div class="hero-faq-right">
                <div class="hero-faq-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Réponse rapide 24h</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                        </svg>
                        <span>Expertise reconnue</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 10V3L4 14H11V21L20 10H13Z" fill="currentColor"/>
                        </svg>
                        <span>Support réactif</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 8L10.89 13.26C11.54 13.67 12.46 13.67 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Contact multicanal</span>
                    </div>
                </div>
            </div>
        </div>

        ${extraContent}
    </section>`;

        content = content.replace(faqHeroPattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-faq en 2 colonnes: ${pagePath}`);
    }

    // Pattern pour hero-livraison
    const livraisonHeroPattern = /<section class="hero-livraison">\s*<div class="hero-livraison-content">\s*<span class="hero-livraison-eyebrow">([^<]+)<\/span>\s*<h1 class="hero-livraison-title">([^<]+)<\/h1>\s*<p class="hero-livraison-subtitle">([^<]+)<\/p>\s*<\/div>\s*<\/section>/;

    if (content.match(livraisonHeroPattern)) {
        const match = content.match(livraisonHeroPattern);
        const eyebrow = match[1];
        const title = match[2];
        const subtitle = match[3];

        const newHeroHTML = `<section class="hero-livraison">
        <div class="hero-livraison-content">
            <div class="hero-livraison-left">
                <span class="hero-livraison-eyebrow">${eyebrow}</span>
                <h1 class="hero-livraison-title">${title}</h1>
                <p class="hero-livraison-subtitle">${subtitle}</p>

                <div class="hero-livraison-cta">
                    <a href="/pages/info/contact.html" class="btn-hero-primary">
                        Demander un devis
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="#livraison-section" class="btn-hero-secondary">En savoir plus</a>
                </div>
            </div>

            <div class="hero-livraison-right">
                <div class="hero-livraison-features">
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Fabrication Europe</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Délais 3-4 semaines</span>
                    </div>
                    <div class="feature-badge">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Suivi de commande</span>
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

        content = content.replace(livraisonHeroPattern, newHeroHTML);
        modified = true;
        console.log(`  ✓ Réorganisé hero-livraison en 2 colonnes: ${pagePath}`);
    }

    // Sauvegarder si modifié
    if (modified) {
        fs.writeFileSync(fullPath, content, 'utf-8');
        fixedCount++;
    }
});

console.log(`\n✅ ${fixedCount} pages info réorganisées avec structure 2 colonnes`);
console.log('✨ Même codes couleurs, nouvelle organisation !');
