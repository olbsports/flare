// Guide des Tailles - Générateur avec Design FAQ
(function() {
    'use strict';

    // Config sports
    const SPORT_CONFIG = {
        'RUGBY': { icon: '🏉', name: 'Rugby' },
        'BASKETBALL': { icon: '🏀', name: 'Basketball' },
        'FOOTBALL': { icon: '⚽', name: 'Football' },
        'HANDBALL': { icon: '🤾', name: 'Handball' },
        'VOLLEYBALL': { icon: '🏐', name: 'Volleyball' },
        'CYCLISME': { icon: '🚴', name: 'Cyclisme' },
        'RUNNING': { icon: '🏃', name: 'Running' },
        'SPORTSWEAR': { icon: '👕', name: 'Sportswear' },
        'SOFTSHELL': { icon: '🧥', name: 'Softshell' }
    };

    let allTableaux = [];
    let currentSport = 'all';

    // Transformer les tailles enfants T4 → 4 ans
    function formatTaille(taille) {
        if (typeof taille === 'string' && taille.startsWith('T') && /^\d+$/.test(taille.substring(1))) {
            const age = taille.substring(1);
            return age + ' ans';
        }
        return taille;
    }

    // Détecter le badge
    function getBadge(productName) {
        const name = productName.toLowerCase();
        
        if (name.includes('femme') || name.includes('coupe femme')) {
            return '<span class="badge badge-femme">Femme</span>';
        }
        if (name.includes('moulant')) {
            return '<span class="badge badge-moulant">Moulant</span>';
        }
        if (name.includes('gardien')) {
            return '<span class="badge badge-pro">Gardien</span>';
        }
        if (name.includes('reversible') || name.includes('réversible')) {
            return '<span class="badge badge-moulant">Réversible</span>';
        }
        if (name.includes('pro') || name.includes('performance')) {
            return '<span class="badge badge-pro">Performance</span>';
        }
        
        return '<span class="badge">Homme / Unisexe</span>';
    }

    // Générer un tableau (format FAQ accordion)
    function generateTableauItem(sportKey, productName, productData) {
        const badge = getBadge(productName);
        const sport = SPORT_CONFIG[sportKey];
        
        let tableauHTML = `
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Mesure</th>`;
        
        productData.tailles.forEach(taille => {
            tableauHTML += `<th>${formatTaille(taille)}</th>`;
        });
        
        tableauHTML += `</tr></thead><tbody>`;
        
        productData.mesures.forEach(mesure => {
            tableauHTML += `<tr><td class="label-cell">${mesure.label}</td>`;
            mesure.values.forEach(value => {
                tableauHTML += `<td>${value}</td>`;
            });
            tableauHTML += `</tr>`;
        });
        
        tableauHTML += `</tbody></table>`;
        
        return {
            html: `
                <div class="tableau-item" data-sport="${sportKey.toLowerCase()}">
                    <button class="tableau-header" onclick="toggleTableau(this)">
                        <div style="display: flex; align-items: center; gap: 16px; flex: 1;">
                            <span style="font-size: 24px;">${sport.icon}</span>
                            <span class="tableau-title">${productName}</span>
                        </div>
                        ${badge}
                        <div class="tableau-icon">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 9L12 16L5 9"/>
                            </svg>
                        </div>
                    </button>
                    <div class="tableau-content">
                        <div class="tableau-scroll">
                            ${tableauHTML}
                        </div>
                    </div>
                </div>
            `,
            sport: sportKey.toLowerCase(),
            productName: productName
        };
    }

    // Toggle tableau (comme FAQ)
    window.toggleTableau = function(button) {
        const item = button.parentElement;
        const wasActive = item.classList.contains('active');
        
        // Fermer tous les autres
        document.querySelectorAll('.tableau-item').forEach(i => {
            i.classList.remove('active');
        });
        
        // Ouvrir celui-ci si il était fermé
        if (!wasActive) {
            item.classList.add('active');
        }
    };

    // Render tableaux
    function renderTableaux(filter = 'all', searchTerm = '') {
        const container = document.getElementById('tableaux-container');
        if (!container) return;
        
        let filteredTableaux = allTableaux;
        
        // Filtre sport
        if (filter !== 'all') {
            filteredTableaux = allTableaux.filter(t => t.sport === filter);
        }
        
        // Filtre recherche
        if (searchTerm) {
            filteredTableaux = filteredTableaux.filter(t => 
                t.productName.toLowerCase().includes(searchTerm.toLowerCase())
            );
        }
        
        if (filteredTableaux.length === 0) {
            container.innerHTML = '<div class="loading-text">Aucun résultat trouvé</div>';
            return;
        }
        
        container.innerHTML = filteredTableaux.map(t => t.html).join('');
    }

    // Filtres sports
    function initFilters() {
        const tabs = document.querySelectorAll('.faq-tab');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const sport = this.dataset.sport;
                currentSport = sport;
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Render
                const searchInput = document.getElementById('guideSearch');
                renderTableaux(sport, searchInput ? searchInput.value : '');
            });
        });
    }

    // Recherche
    function initSearch() {
        const searchInput = document.getElementById('guideSearch');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', function() {
            renderTableaux(currentSport, this.value);
        });
    }

    // Charger et générer
    async function loadAndGenerate() {
        try {
            const response = await fetch('tableaux-tailles-complet.json');
            const data = await response.json();
            
            const container = document.getElementById('tableaux-container');
            if (!container) {
                console.error('Container non trouvé');
                return;
            }
            
            allTableaux = [];
            
            // Générer tous les tableaux
            Object.entries(data).forEach(([sportKey, sportData]) => {
                const config = SPORT_CONFIG[sportKey];
                if (config) {
                    Object.entries(sportData).forEach(([productName, productData]) => {
                        allTableaux.push(generateTableauItem(sportKey, productName, productData));
                    });
                }
            });
            
            // Render initial
            renderTableaux('all', '');
            
            // Init filters et search
            initFilters();
            initSearch();
            
            console.log(`✅ ${allTableaux.length} tableaux générés !`);
            
        } catch (error) {
            console.error('Erreur chargement:', error);
            document.getElementById('tableaux-container').innerHTML = `
                <div class="loading-text" style="color: #FF4B26;">
                    Erreur de chargement. Vérifiez que le fichier JSON est présent.
                </div>
            `;
        }
    }

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndGenerate);
    } else {
        loadAndGenerate();
    }
})();
