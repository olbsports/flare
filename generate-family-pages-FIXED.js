const fs = require('fs');

// Fonctions de parsing CSV correct (gère quotes et multi-lignes)
function parseCSV(content) {
    const lines = [];
    let currentLine = '';
    let inQuotes = false;

    for (let i = 0; i < content.length; i++) {
        const char = content[i];
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === '\n' && !inQuotes) {
            if (currentLine.trim()) lines.push(currentLine);
            currentLine = '';
            continue;
        }
        currentLine += char;
    }
    if (currentLine.trim()) lines.push(currentLine);
    return lines;
}

function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;

    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === ';' && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }
    result.push(current.trim());
    return result;
}

// Lire et parser le CSV
const csvPath = './assets/data/PRICING-FLARE-2025.csv';
const csvContent = fs.readFileSync(csvPath, 'utf-8');
const lines = parseCSV(csvContent);
const headers = parseCSVLine(lines[0]);

// Parser le CSV
const products = [];
for (let i = 1; i < lines.length; i++) {
    const values = parseCSVLine(lines[i]);
    const product = {};
    headers.forEach((header, index) => {
        product[header] = values[index] || '';
    });
    if (product.FAMILLE_PRODUIT && product.FAMILLE_PRODUIT.length > 0 && !product.FAMILLE_PRODUIT.startsWith('-') && !product.FAMILLE_PRODUIT.startsWith('http')) {
        products.push(product);
    }
}

// Grouper par famille de produit
const productsByFamily = {};
products.forEach(product => {
    const family = product.FAMILLE_PRODUIT;
    if (!productsByFamily[family]) {
        productsByFamily[family] = [];
    }
    productsByFamily[family].push(product);
});

// Contenus SEO personnalisés par famille
const familySEOContent = {
    'Polo': {
        title: 'Polo Sport Personnalisé',
        slug: 'polos-sport-personnalises',
        eyebrow: `${productsByFamily['Polo']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Polo']?.length || 0} modèles tous sports. Tissus techniques Jersey et Piqué, Col tissu ou bord côte, Manches courtes et longues, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Polo Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Polo sport personnalisé en sublimation intégrale. 24 modèles pour tous sports : football, rugby, basketball, running, cyclisme. Tissus Jersey 140g et Piqué 210g. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>polos sport personnalisés en sublimation intégrale</strong> représentent la solution idéale pour équiper clubs sportifs, associations et entreprises. Notre gamme de <strong>24 modèles de polos personnalisables</strong> couvre tous les sports et toutes les situations : entraînements, compétitions, événements et représentation quotidienne.</p>

<p>Nos <strong>polos techniques sport</strong> utilisent deux types de tissus premium : le <strong>Premium Jersey 140 gr/m²</strong> ultra-doux et confortable pour un usage quotidien, et le <strong>Premium Piqué 210 gr/m²</strong> texturé et respirant pour une tenue structurée professionnelle. Les deux tissus sont <strong>éco-responsables</strong> avec matériaux recyclés certifiés.</p>

<p>Deux options de col selon vos préférences : le <strong>col tissu classique</strong> élégant et intemporel, ou le <strong>col bord côte</strong> offrant un ajustement optimal et un maintien parfait. Choisissez entre <strong>manches courtes</strong> pour ventilation maximale ou <strong>manches longues</strong> pour protection complète.</p>

<p>La <strong>personnalisation polo sublimation</strong> permet designs illimités : logos clubs multiples, sponsors partenaires, noms numéros joueurs, dégradés couleurs complexes, tout est possible sans surcoût. La sublimation intègre encres directement dans fibres garantissant <strong>résistance lavages industriels</strong> et couleurs éclatantes durables.</p>

<p>Les <strong>polos club personnalisés</strong> conviennent parfaitement aux dirigeants, entraîneurs, staff technique et joueurs hors terrain. Idéals pour <strong>événements corporate</strong>, séminaires entreprises, tournois golf, compétitions pétanque et représentation quotidienne club.</p>

<p>Notre politique tarifaire accessible propose <strong>polos personnalisés pas cher</strong> avec qualité professionnelle : prix dégressifs automatiques dès 5 pièces, sans minimum commande, fabrication européenne certifiée, délais rapides 3-4 semaines, service design graphique inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">24 modèles polos sport • Jersey 140g et Piqué 210g • Col tissu ou bord côte • Manches courtes et longues • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Sweat': {
        title: 'Sweat Sport Personnalisé',
        slug: 'sweats-sport-personnalises',
        eyebrow: `${productsByFamily['Sweat']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Sweat']?.length || 0} modèles tous sports. Molleton confortable, Bords côtes poignets et taille, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sweat Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Sweat sport personnalisé en sublimation intégrale. 22 modèles pour tous sports : football, rugby, basketball, running. Molleton confortable, bords côtes. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>sweats sport personnalisés en sublimation intégrale</strong> offrent confort thermique et style pour équiper clubs sportifs toutes disciplines. Notre collection de <strong>22 modèles de sweats personnalisables</strong> combine chaleur, respirabilité et design illimité pour entraînements, échauffements et moments hors terrain.</p>

<p>Nos <strong>sweats techniques club</strong> utilisent molleton gratté intérieur procurant chaleur optimale tout en évacuant humidité durant efforts physiques. Les <strong>bords côtes aux poignets et à la taille</strong> assurent ajustement parfait empêchant entrée air froid et maintenant chaleur corporelle.</p>

<p>La <strong>personnalisation sweat sublimation</strong> transforme chaque pièce en support communication club : logos géants dos devant, sponsors multiples, noms joueurs numéros, slogans motivants, dégradés couleurs spectaculaires. La sublimation garantit <strong>résistance lavages répétés</strong> sans craquelure ni décoloration.</p>

<p>Les <strong>sweats club personnalisés</strong> conviennent échauffements avant match, entraînements températures fraîches, déplacements équipes, cérémonies remise prix et représentation quotidienne club. Parfaits pour <strong>associations sportives</strong> cherchant équipements polyvalents confortables durables.</p>

<p>Version <strong>classique col rond</strong> pour look sportif épuré ou <strong>sweat 1/4 zip</strong> permettant ajustement thermique modulable selon intensité effort. Certains modèles incluent <strong>2 poches pratiques</strong> pour ranger petits accessoires clés téléphone.</p>

<p>Tarification accessible avec <strong>sweats personnalisés pas cher</strong> qualité premium : prix dégressifs automatiques volumes, fabrication européenne certifiée éco-responsable, matières recyclées, délais production rapides, service création graphique professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">22 modèles sweats sport • Molleton confortable • Bords côtes ajustables • Col rond et 1/4 zip • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'T-Shirt': {
        title: 'T-Shirt Sport Personnalisé',
        slug: 'tshirts-sport-personnalises',
        eyebrow: `${productsByFamily['T-Shirt']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['T-Shirt']?.length || 0} modèles tous sports. Tissus légers respirants, Évacuation rapide transpiration, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'T-Shirt Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'T-shirt sport personnalisé en sublimation intégrale. 8 modèles pour tous sports : running, fitness, training. Tissus légers respirants. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>t-shirts sport personnalisés en sublimation intégrale</strong> combinent légèreté, respirabilité et designs illimités pour équiper clubs sportifs et associations. Notre sélection de <strong>8 modèles de t-shirts techniques</strong> couvre entraînements intensifs, compétitions et usage quotidien représentation club.</p>

<p>Nos <strong>t-shirts techniques performance</strong> utilisent tissus ultra-légers évacuant transpiration rapidement pour maintenir corps sec confortable durant efforts prolongés. Les matières <strong>respirantes mesh</strong> favorisent circulation air optimale régulation thermique efficace même entraînements intensifs.</p>

<p>La <strong>personnalisation t-shirt sublimation</strong> offre créativité totale : logos clubs couleurs vives, sponsors partenaires multiples, noms numéros personnalisés, slogans équipes, dégradés complexes impossibles techniques traditionnelles. La sublimation garantit <strong>durabilité exceptionnelle</strong> résistant lavages fréquents sans altération.</p>

<p>Les <strong>t-shirts club personnalisés</strong> conviennent parfaitement running, fitness, training fonctionnel, sports salle, échauffements, entraînements été et événements sportifs outdoor. Idéals <strong>courses caritatives</strong>, challenges corporate, teams building entreprises et manifestations associatives.</p>

<p>Coupes disponibles : <strong>coupe classique confortable</strong> mixte convenant toutes morphologies, <strong>coupe femme ajustée</strong> spécifiquement adaptée anatomie féminine, <strong>coupe homme athlétique</strong> favorisant liberté mouvement maximale. Tailles du XS au 5XL pour inclusivité complète.</p>

<p>Politique tarifaire accessible <strong>t-shirts personnalisés pas cher</strong> sans compromettre qualité : prix dégressifs automatiques commandes groupées, fabrication européenne certifiée, tissus éco-responsables recyclés, délais rapides, service design création visuelle professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">8 modèles t-shirts sport • Tissus ultra-légers respirants • Évacuation transpiration rapide • Coupes homme femme mixte • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Débardeur': {
        title: 'Débardeur Sport Personnalisé',
        slug: 'debardeurs-sport-personnalises',
        eyebrow: `${productsByFamily['Débardeur']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Débardeur']?.length || 0} modèles tous sports. Sans manches liberté maximale, Tissus ultra-légers, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Débardeur Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Débardeur sport personnalisé en sublimation intégrale. 8 modèles pour tous sports : running, basketball, volleyball. Sans manches, ultra-légers. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>débardeurs sport personnalisés en sublimation intégrale</strong> offrent liberté mouvement maximale et ventilation optimale pour disciplines intensives. Notre gamme de <strong>8 modèles de débardeurs techniques</strong> équipe clubs basketball, volleyball, running, triathlon et athlétisme avec performance et style.</p>

<p>Nos <strong>débardeurs techniques performance</strong> sont <strong>sans manches</strong> permettant amplitude gestuelle totale bras épaules sans restriction. Les tissus <strong>ultra-légers respirants</strong> évacuent transpiration instantanément maintenant corps sec frais même efforts intenses températures élevées.</p>

<p>La <strong>personnalisation débardeur sublimation</strong> transforme chaque pièce en véritable support visuel club : grands logos colorés dos devant, sponsors multiples, numéros joueurs géants lisibilité arbitres spectateurs, designs géométriques modernes, dégradés spectaculaires. La sublimation assure <strong>résistance lavages industriels</strong> fréquents sans décoloration.</p>

<p>Les <strong>débardeurs club personnalisés</strong> conviennent parfaitement basketball débardeurs réversibles, volleyball débardeurs femme ajustés, running débardeurs ultra-légers marathons, triathlon débardeurs techniques transitions rapides, athlétisme débardeurs compétition homologués. Idéals <strong>courses route</strong>, trails, compétitions salle.</p>

<p>Options coupes : <strong>coupe classique ample</strong> confort maximal, <strong>coupe ajustée technique</strong> réduction frottements aérodynamisme, <strong>débardeur femme</strong> avec soutien renforcé maintien optimal, <strong>débardeur homme</strong> emmanchures larges liberté totale. Versions <strong>réversibles</strong> disponibles basketball économie budget.</p>

<p>Tarifs accessibles <strong>débardeurs personnalisés pas cher</strong> qualité professionnelle : prix dégressifs volumes commandes clubs, fabrication européenne certifiée, matières recyclées éco-responsables, délais production rapides 3-4 semaines, service création design graphique professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">8 modèles débardeurs sport • Sans manches liberté totale • Ultra-légers respirants • Coupes homme femme techniques • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Sweat à Capuche': {
        title: 'Sweat à Capuche Sport Personnalisé',
        slug: 'sweats-capuche-sport-personnalises',
        eyebrow: `${productsByFamily['Sweat à Capuche']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Sweat à Capuche']?.length || 0} modèles tous sports. Capuche ajustable, Molleton confortable, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Sweat à Capuche Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Sweat à capuche sport personnalisé en sublimation intégrale. 9 modèles pour tous sports : football, rugby, basketball, running. Capuche ajustable, molleton. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>sweats à capuche sport personnalisés en sublimation intégrale</strong> combinent protection thermique, confort et style urbain pour équiper clubs sportifs. Notre collection de <strong>9 modèles de hoodies techniques</strong> protège du froid et du vent tout en affichant fièrement couleurs identité club.</p>

<p>Nos <strong>hoodies techniques club</strong> intègrent <strong>capuche ajustable avec cordon de serrage</strong> permettant protection optimale tête nuque contre intempéries. Le molleton gratté intérieur procure <strong>chaleur maximale</strong> tout en restant respirant évacuant humidité durant échauffements entraînements.</p>

<p>La <strong>personnalisation hoodie sublimation</strong> offre possibilités créatives illimitées : grands logos clubs dos devant, sponsors partenaires multiples, noms joueurs numéros, slogans motivants équipes, dégradés couleurs modernes urbains. La sublimation garantit <strong>durabilité couleurs</strong> résistant lavages répétés sans craquelure.</p>

<p>Les <strong>sweats capuche club personnalisés</strong> conviennent parfaitement échauffements avant match températures fraîches, déplacements équipes bus, cérémonies podium remise prix, représentation quotidienne club hors terrains et boutiques supporters merchandising club. Idéals <strong>teams esport gaming</strong> valorisant image marque moderne.</p>

<p>Versions disponibles : <strong>hoodie classique</strong> poche kangourou devant rangement mains, <strong>hoodie zippé</strong> fermeture éclair complète enfilage facile, <strong>hoodie technique</strong> matières performance respirantes. Bords côtes poignets taille assurent <strong>ajustement parfait</strong> maintien chaleur.</p>

<p>Politique tarifaire accessible <strong>hoodies personnalisés pas cher</strong> qualité premium : prix dégressifs automatiques commandes volumes, fabrication européenne certifiée éco-responsable, matières recyclées, délais rapides 3-4 semaines, service design création visuelle professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">9 modèles sweats capuche sport • Capuche ajustable cordon • Molleton chaleur maximale • Poche kangourou et zippés • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Veste': {
        title: 'Veste Sport Personnalisée',
        slug: 'vestes-sport-personnalisees',
        eyebrow: `${productsByFamily['Veste']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Veste']?.length || 0} modèles tous sports. Protection optimale, Fermeture éclair, Poches zippées, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Veste Sport Personnalisée Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Veste sport personnalisée en sublimation intégrale. 28 modèles pour tous sports : football, rugby, basketball, running, cyclisme. Protection optimale. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>vestes sport personnalisées en sublimation intégrale</strong> offrent protection complète contre intempéries tout en affichant identité club avec fierté. Notre vaste collection de <strong>28 modèles de vestes techniques</strong> équipe clubs tous sports toutes saisons avec performance thermique et designs spectaculaires.</p>

<p>Nos <strong>vestes techniques performance</strong> utilisent matières <strong>softshell coupe-vent déperlantes</strong> protégeant du vent pluie fine tout en restant respirantes. Les modèles <strong>isolants thermiques</strong> intègrent garnissage haute performance procurant chaleur optimale légèreté maximale pour températures très fraîches.</p>

<p>La <strong>personnalisation veste sublimation</strong> transforme chaque pièce en support communication visuelle puissant : logos clubs géants dos devant manches, sponsors partenaires multiples règlementaires, noms joueurs numéros staff, dégradés couleurs modernes coordonnés identité visuelle club. La sublimation assure <strong>résistance lavages</strong> professionnels sans altération.</p>

<p>Les <strong>vestes club personnalisées</strong> conviennent déplacements équipes bus compétitions extérieures, échauffements terrains températures basses, bancs touche protection dirigeants remplaçants, cérémonies officielles podiums remise prix et représentation quotidienne club événements publics. Parfaites <strong>staff technique</strong> entraîneurs dirigeants kinés.</p>

<p>Gamme complète : <strong>vestes légères coupe-vent</strong> printemps automne, <strong>vestes molleton</strong> confort quotidien, <strong>vestes matelassées</strong> isolation thermique hiver, <strong>vestes softshell</strong> techniques polyvalentes, <strong>vestes imperméables</strong> pluie battante. <strong>Poches zippées sécurisées</strong> rangement objets valeur téléphones clés.</p>

<p>Tarification accessible <strong>vestes personnalisées pas cher</strong> qualité professionnelle : prix dégressifs automatiques volumes importants, fabrication européenne certifiée, matières éco-responsables recyclées, délais production rapides, service création graphique design professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">28 modèles vestes sport • Coupe-vent déperlantes • Isolation thermique • Poches zippées sécurisées • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Pantalon': {
        title: 'Pantalon Sport Personnalisé',
        slug: 'pantalons-sport-personnalises',
        eyebrow: `${productsByFamily['Pantalon']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Pantalon']?.length || 0} modèles tous sports. Confort et performance, Ceinture élastique, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Pantalon Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Pantalon sport personnalisé en sublimation intégrale. 9 modèles pour tous sports : football, running, training. Confort et performance. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>pantalons sport personnalisés en sublimation intégrale</strong> combinent confort, liberté mouvement et protection thermique pour entraînements toutes saisons. Notre sélection de <strong>9 modèles de pantalons techniques</strong> équipe clubs football, running, fitness et sports collectifs avec performance et style coordonné.</p>

<p>Nos <strong>pantalons techniques performance</strong> utilisent tissus stretch légers suivant mouvements naturels sans restriction amplitude gestuelle. La <strong>ceinture élastique ajustable</strong> avec cordon serrage assure maintien parfait confortable durant efforts physiques intenses évitant glissements gênants.</p>

<p>La <strong>personnalisation pantalon sublimation</strong> permet designs coordonnés tenues complètes club : bandes latérales colorées logos clubs, sponsors réglementaires jambes, noms numéros joueurs, dégradés couleurs identité visuelle. La sublimation garantit <strong>résistance lavages fréquents</strong> clubs intensifs sans décoloration craquelure.</p>

<p>Les <strong>pantalons club personnalisés</strong> conviennent parfaitement entraînements football températures fraîches, running trails automne hiver, fitness training fonctionnel, échauffements avant match, déplacements équipes et représentation quotidienne club. Idéals <strong>survêtements complets</strong> assortis vestes coordonnées.</p>

<p>Types disponibles : <strong>pantalons droits classiques</strong> confort optimal, <strong>pantalons fuselés ajustés</strong> chevilles look moderne, <strong>pantalons molleton</strong> chaleur maximale, <strong>pantalons techniques</strong> respirants évacuation transpiration. Poches latérales zippées sécurisées rangement téléphones clés.</p>

<p>Politique tarifaire accessible <strong>pantalons personnalisés pas cher</strong> qualité professionnelle : prix dégressifs automatiques commandes groupées clubs, fabrication européenne certifiée, tissus éco-responsables recyclés, délais rapides 3-4 semaines, service design création visuelle professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">9 modèles pantalons sport • Ceinture élastique ajustable • Confort et liberté mouvement • Poches zippées sécurisées • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Gilet': {
        title: 'Gilet Sport Personnalisé',
        slug: 'gilets-sport-personnalises',
        eyebrow: `${productsByFamily['Gilet']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Gilet']?.length || 0} modèles tous sports. Léger et pratique, Sans manches, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Gilet Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Gilet sport personnalisé en sublimation intégrale. 12 modèles pour tous sports : cyclisme, running. Sans manches, léger. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>gilets sport personnalisés en sublimation intégrale</strong> offrent protection thermique cœur tout en préservant liberté mouvement bras pour disciplines endurance. Notre gamme de <strong>12 modèles de gilets techniques</strong> équipe clubs cyclisme, running, triathlon avec légèreté performance et designs éclatants.</p>

<p>Nos <strong>gilets techniques performance</strong> sont <strong>sans manches</strong> permettant amplitude gestuelle totale bras épaules sans restriction tout en protégeant buste dos contre vent froid. Les versions <strong>coupe-vent ultra-légères</strong> se plient compactent facilement poches maillots pour polyvalence maximale.</p>

<p>La <strong>personnalisation gilet sublimation</strong> transforme chaque pièce en support visuel haute visibilité : grands logos clubs dos devant couleurs vives, sponsors multiples réglementaires, numéros dossards géants lisibilité, bandes réfléchissantes sécurité. La sublimation assure <strong>durabilité couleurs</strong> lavages répétés sans altération.</p>

<p>Les <strong>gilets club personnalisés</strong> conviennent parfaitement cyclisme route VTT sorties matinales fraîches, running trails automne hiver protection thermique légère, triathlon transitions rapides vélo course, randonnée marche nordique et événements sportifs outdoor toutes saisons. Idéals <strong>couches intermédiaires</strong> systèmes multicouches.</p>

<p>Options disponibles : <strong>gilets coupe-vent</strong> ultra-légers compactables, <strong>gilets softshell</strong> techniques respirants, <strong>gilets réversibles</strong> double face économiques, <strong>gilets haute visibilité</strong> bandes réfléchissantes sécurité circulation. Certains modèles incluent <strong>manches amovibles</strong> transformation veste 2-en-1 polyvalence.</p>

<p>Tarifs accessibles <strong>gilets personnalisés pas cher</strong> qualité premium : prix dégressifs volumes commandes clubs, fabrication européenne certifiée, matières recyclées éco-responsables, délais production rapides, service création design graphique professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">12 modèles gilets sport • Sans manches liberté bras • Ultra-légers coupe-vent • Haute visibilité sécurité • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    },
    'Coupe-Vent': {
        title: 'Coupe-Vent Sport Personnalisé',
        slug: 'coupe-vent-sport-personnalises',
        eyebrow: `${productsByFamily['Coupe-Vent']?.length || 0} modèles personnalisables tous sports`,
        subtitle: `${productsByFamily['Coupe-Vent']?.length || 0} modèles tous sports. Protection contre le vent, Ultra-léger, Personnalisation illimitée, fabrication européenne, prix dégressifs dès 5 pièces.`,
        seoTitle: 'Coupe-Vent Sport Personnalisé Sublimation | Tous Sports - FLARE CUSTOM',
        seoDescription: 'Coupe-vent sport personnalisé en sublimation intégrale. 11 modèles pour tous sports : cyclisme, running. Protection vent, ultra-léger. Fabrication européenne, prix dégressifs. Devis gratuit 24h.',
        seoContent: `<p>Les <strong>coupe-vent sport personnalisés en sublimation intégrale</strong> protègent efficacement du vent tout en restant ultra-légers compactables pour disciplines endurance. Notre collection de <strong>11 modèles de coupe-vent techniques</strong> équipe clubs cyclisme, running, triathlon avec protection optimale et designs haute visibilité.</p>

<p>Nos <strong>coupe-vent techniques performance</strong> utilisent matières <strong>ultra-légères déperlantes</strong> bloquant vent pluie fine tout en restant extrêmement respirantes évacuant transpiration. Le poids plume permet <strong>pliage compact</strong> poche maillot rangement facile sorties longues distances changements météo imprévisibles.</p>

<p>La <strong>personnalisation coupe-vent sublimation</strong> offre visibilité maximale sécurité : grands logos clubs couleurs vives fluo dos devant, sponsors multiples, numéros dossards géants, bandes réfléchissantes haute visibilité circulation nocturne. La sublimation garantit <strong>résistance intempéries</strong> lavages fréquents sans décoloration.</p>

<p>Les <strong>coupe-vent club personnalisés</strong> conviennent parfaitement cyclisme route sorties matinales fraîches, running trails automne hiver protection légère, triathlon courses longue distance conditions variables, randonnée marche nordique et compétitions outdoor toutes saisons météo incertaine. Essentiels <strong>équipements sécurité</strong> clubs.</p>

<p>Versions proposées : <strong>coupe-vent classiques</strong> fermeture éclair complète, <strong>coupe-vent capuche</strong> protection tête intégrée, <strong>coupe-vent sans manches</strong> liberté bras maximale, <strong>coupe-vent réfléchissants</strong> visibilité 360° sécurité nuit. Poches zippées dos rangement barres énergétiques gels.</p>

<p>Politique tarifaire accessible <strong>coupe-vent personnalisés pas cher</strong> qualité professionnelle : prix dégressifs automatiques commandes volumes, fabrication européenne certifiée, matières recyclées éco-responsables, délais rapides 3-4 semaines, service design création visuelle professionnel inclus gratuitement.</p>

<p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #FF4B26; text-align: center;">11 modèles coupe-vent sport • Ultra-légers compactables • Protection vent pluie • Haute visibilité réfléchissante • Design professionnel inclus • Devis gratuit 24h • Prix dégressifs dès 5 pièces • Fabrication européenne certifiée</p>`
    }
};

// Fonction pour générer une carte produit
function generateProductCard(product) {
    const photos = [
        product.PHOTO_1,
        product.PHOTO_2,
        product.PHOTO_3,
        product.PHOTO_4,
        product.PHOTO_5
    ].filter(p => p && p.trim() && p.startsWith('http'));

    const finitions = product.FINITION ? product.FINITION.split(',').map(f => f.trim()) : [];
    const prixQty500 = parseFloat(product.QTY_500) || 0;
    const prixAdulte = prixQty500.toFixed(2);
    const prixEnfant = (prixQty500 * 0.9).toFixed(2);

    const slidesHTML = photos.map((photo, index) =>
        `<div class="product-slide ${index === 0 ? 'active' : ''}">
                <img src="${photo}" alt="${product.TITRE_VENDEUR} - Photo ${index + 1}" class="product-image" loading="lazy" width="420" height="560" decoding="async">
            </div>`
    ).join('');

    const dotsHTML = photos.map((_, index) =>
        `<button class="slider-dot ${index === 0 ? 'active' : ''}" data-slide="${index}" aria-label="Voir photo ${index + 1}"></button>`
    ).join('');

    const finitionsHTML = finitions.map(f =>
        `<span class="product-finition-badge">${f}</span>`
    ).join('');

    return `<div class="product-card">
                    <div class="product-image-wrapper">
                        <div class="product-slider">
                            ${slidesHTML}
                        </div>
                        <button class="slider-nav prev" aria-label="Photo précédente">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="slider-nav next" aria-label="Photo suivante">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
                        <div class="product-slider-dots">${dotsHTML}</div>
                        <div class="product-badges"></div>
                    </div>
                    <div class="product-info">
                        <div class="product-family">${product.FAMILLE_PRODUIT}</div>
                        <h3 class="product-name">${product.TITRE_VENDEUR}</h3>
                        <div class="product-specs">
                            <span class="product-spec">${product.SPORT}</span><span class="product-spec">${product.GENRE || 'Unisexe'}</span>
                        </div>
                        <div class="product-finitions">${finitionsHTML}</div>
                        <div class="product-pricing">
                            <div class="product-price-label">À partir de</div>
                            <div class="product-price-adulte">
                                <span class="product-price-type">Adulte</span>
                                <span class="product-price">${prixAdulte}€</span>
                            </div>
                            <div class="product-price-enfant">
                                <span class="product-price-type">Enfant</span>
                                <span class="product-price-small">${prixEnfant}€</span>
                            </div>
                        </div>
                    </div>
                </div>
`;
}

// Fonction pour générer la page HTML complète
function generateFamilyPage(family, config) {
    const familyProducts = productsByFamily[family] || [];
    if (familyProducts.length === 0) {
        console.log(`Pas de produits pour ${family}`);
        return null;
    }

    const productsCardsHTML = familyProducts.map(p => generateProductCard(p)).join('\n');

    // Lire le template de base
    const templatePath = './pages/products/maillots-sport-personnalises.html';
    let template = fs.readFileSync(templatePath, 'utf-8');

    // Remplacer les infos spécifiques
    template = template.replace(/<title>.*?<\/title>/, `<title>${config.seoTitle}</title>`);
    template = template.replace(/<meta name="description" content=".*?"/, `<meta name="description" content="${config.seoDescription}"`);

    // Remplacer le hero
    template = template.replace(/108 modèles personnalisables tous sports/g, config.eyebrow);
    template = template.replace(/Maillots Sport Sublimation/g, config.title);
    template = template.replace(/108 modèles tous sports\. Tissus techniques haute performance.*?pièces\./g, config.subtitle);

    // Remplacer le compteur de produits
    template = template.replace(/108 produits/g, `${familyProducts.length} produits`);

    // Remplacer toute la grille de produits
    const gridStart = template.indexOf('<div class="products-grid" id="productsGrid">');
    let searchPos = gridStart;
    let depth = 0;
    let gridEnd = -1;

    while (searchPos < template.length) {
        if (template.substring(searchPos, searchPos + 5) === '<div ') {
            depth++;
        } else if (template.substring(searchPos, searchPos + 6) === '</div>') {
            depth--;
            if (depth === 0) {
                gridEnd = searchPos + 6;
                break;
            }
        }
        searchPos++;
    }

    if (gridStart !== -1 && gridEnd !== -1) {
        const before = template.substring(0, gridStart);
        const after = template.substring(gridEnd);
        template = before + `<div class="products-grid" id="productsGrid">\n${productsCardsHTML}\n            </div>` + after;
    }

    // Remplacer TOUTE la section SEO longtail
    const seoStart = template.indexOf('<section class="seo-longtail-mega">');
    const seoEnd = template.indexOf('</section>', seoStart) + 10;

    if (seoStart !== -1 && seoEnd > seoStart) {
        const before = template.substring(0, seoStart);
        const after = template.substring(seoEnd);
        template = before + `<section class="seo-mega">
        <div class="container" style="max-width: 900px; margin: 0 auto;">
            <div class="seo-hero">
                <div class="seo-hero-badge">${family.toUpperCase()}</div>
                <h2 class="seo-hero-title">${config.title} Tous Sports</h2>
                <div class="seo-hero-intro">
                    ${config.seoContent}
                </div>
            </div>
        </div>
    </section>` + after;
    }

    return template;
}

// Générer les pages
console.log('🔧 Génération des pages de familles avec VRAI contenu SEO adapté...\n');

Object.keys(familySEOContent).forEach(family => {
    const config = familySEOContent[family];
    const familyProducts = productsByFamily[family] || [];

    console.log(`${family}: ${familyProducts.length} produits`);

    if (familyProducts.length >= 8) {
        const pageContent = generateFamilyPage(family, config);
        if (pageContent) {
            const outputPath = `./pages/products/${config.slug}.html`;
            fs.writeFileSync(outputPath, pageContent);
            console.log(`  ✅ Créé: ${outputPath}`);
        }
    } else {
        console.log(`  ⏭️  Ignoré (moins de 8 produits)`);
    }
});

console.log('\n✨ FINALEMENT TERMINÉ avec le BON contenu SEO !');
