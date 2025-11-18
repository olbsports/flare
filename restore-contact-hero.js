const fs = require('fs');
const path = require('path');

console.log('🔧 Restauration du hero original de contact...\n');

const contactPath = path.join(__dirname, 'pages/info/contact.html');
let content = fs.readFileSync(contactPath, 'utf-8');

// Pattern pour trouver le hero-contact actuel
const heroPattern = /<section class="hero-contact">\s*<div class="hero-contact-content">[\s\S]*?<\/div>\s*<\/section>/;

const originalHero = `<section class="hero-contact">
        <div class="hero-contact-background"></div>

        <div class="hero-contact-content">
            <span class="hero-contact-eyebrow">📞 Parlons de votre projet</span>

            <h1 class="hero-contact-title">
                Contactez-nous
            </h1>

            <p class="hero-contact-subtitle">
                Devis gratuit sous 24h • Conseil expert • design offert dès 10 pièces
            </p>

            <div class="hero-contact-badges">
                <div class="hero-badge">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12L11 14L15 10M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3C16.971 3 21 7.029 21 12Z"/>
                    </svg>
                    <span>Réponse garantie 24h</span>
                </div>
                <div class="hero-badge">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <span>WhatsApp disponible</span>
                </div>
                <div class="hero-badge">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 8L10.89 13.26C11.54 13.67 12.46 13.67 13.11 13.26L21 8M5 19H19C20.1 19 21 18.1 21 17V7C21 5.9 20.1 5 19 5H5C3.9 5 3 5.9 3 7V17C3 18.1 3.9 19 5 19Z"/>
                    </svg>
                    <span>Support multilingue</span>
                </div>
            </div>
        </div>
    </section>`;

if (content.match(heroPattern)) {
    content = content.replace(heroPattern, originalHero);
    fs.writeFileSync(contactPath, content, 'utf-8');
    console.log('✅ Hero original de contact restauré !');
} else {
    console.log('❌ Pattern hero-contact non trouvé');
}
