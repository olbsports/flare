/**
 * FLARE CUSTOM - Templates Display
 * Charge et affiche les templates SVG dans l'onglet Templates des pages produits
 */

// Cache pour les templates
let templatesCache = null;

/**
 * Charge les templates depuis l'API
 */
async function loadTemplates() {
    if (templatesCache) {
        return templatesCache;
    }

    try {
        const response = await fetch('/api/list-templates.php');
        const data = await response.json();

        if (data.success && data.templates) {
            templatesCache = data.templates;
            return templatesCache;
        }

        return [];
    } catch (error) {
        console.error('Erreur chargement templates:', error);
        return [];
    }
}

/**
 * Affiche les templates dans l'onglet
 */
async function displayTemplates() {
    const container = document.getElementById('templates-dynamic-content');

    if (!container) {
        return;
    }

    // Afficher un loader pendant le chargement
    container.innerHTML = `
        <div style="text-align: center; padding: 60px 20px;">
            <div style="display: inline-block; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #FF4B26; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 20px; color: #666;">Chargement des templates...</p>
        </div>
    `;

    // Charger les templates
    const templates = await loadTemplates();

    if (!templates || templates.length === 0) {
        container.innerHTML = `
            <p style="text-align: center; padding: 60px 20px; color: #666; font-size: 16px;">
                Aucun template disponible pour le moment.
            </p>
        `;
        return;
    }

    // Afficher les templates en grille
    let html = `
        <div style="margin-bottom: 30px;">
            <p style="color: #666; font-size: 16px; margin-bottom: 20px;">
                Découvrez nos templates de design prêts à l'emploi. Sélectionnez-en un lors de la configuration de votre devis.
            </p>
        </div>
        <div class="templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 24px; margin-bottom: 40px;">
    `;

    templates.forEach((tpl, index) => {
        const templateNumber = String(index + 1).padStart(3, '0');
        html += `
            <div class="template-card" style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 16px; transition: all 0.3s ease; background: #fff;">
                <div style="aspect-ratio: 3/4; background: #f8f9fa; border-radius: 8px; overflow: hidden; margin-bottom: 12px;">
                    <img src="${tpl.path}" alt="Template ${templateNumber}"
                         style="width: 100%; height: 100%; object-fit: contain;"
                         onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:14px;\'>Template ${templateNumber}</div>'">
                </div>
                <h4 style="text-align: center; font-size: 16px; font-weight: 600; color: #333; margin: 0;">Template ${templateNumber}</h4>
            </div>
        `;
    });

    html += `
        </div>
        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 12px;">
            <p style="color: #666; margin-bottom: 15px;">
                <strong>Vous aimez un de ces templates ?</strong> Sélectionnez-le lors de la configuration de votre devis !
            </p>
            <button class="btn-primary" onclick="document.querySelector('.btn-primary[onclick*=initConfigurateurProduit]').click()">
                CONFIGURER MON DEVIS
            </button>
        </div>
    `;

    container.innerHTML = html;

    // Ajouter effet hover sur les cards
    const style = document.createElement('style');
    style.textContent = `
        .template-card:hover {
            border-color: #FF4B26;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(255, 75, 38, 0.15);
        }
    `;
    document.head.appendChild(style);
}

// Charger les templates au chargement de la page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', displayTemplates);
} else {
    displayTemplates();
}
