/**
 * Product Tabs - Gestion responsive des onglets produit
 * FLARE CUSTOM
 */

document.addEventListener('DOMContentLoaded', function() {
    const tabsNav = document.querySelector('.tabs-nav');
    const tabButtons = document.querySelectorAll('.tab-btn');

    // Gestion du clic sur les tabs
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = 'tab-' + btn.dataset.tab;

            // Désactiver tous les tabs
            tabButtons.forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // Activer le tab cliqué
            btn.classList.add('active');
            document.getElementById(tabId).classList.add('active');

            // Sur mobile/tablette, centrer le tab actif dans la zone de scroll
            if (window.innerWidth <= 1024) {
                scrollTabIntoView(btn);
            }
        });
    });

    // Fonction pour centrer le tab actif
    function scrollTabIntoView(tabBtn) {
        if (!tabsNav) return;

        const tabsNavRect = tabsNav.getBoundingClientRect();
        const btnRect = tabBtn.getBoundingClientRect();

        // Calculer la position pour centrer le bouton
        const scrollLeft = tabsNav.scrollLeft + (btnRect.left - tabsNavRect.left) - (tabsNavRect.width / 2) + (btnRect.width / 2);

        // Smooth scroll vers la position
        tabsNav.scrollTo({
            left: scrollLeft,
            behavior: 'smooth'
        });
    }

    // Gérer l'indicateur de scroll (flèche →)
    function updateScrollIndicator() {
        if (!tabsNav || window.innerWidth > 768) return;

        const isScrollable = tabsNav.scrollWidth > tabsNav.clientWidth;
        const isAtEnd = Math.abs(tabsNav.scrollWidth - tabsNav.clientWidth - tabsNav.scrollLeft) < 5;

        // Cacher la flèche si on est à la fin ou si pas scrollable
        if (!isScrollable || isAtEnd) {
            tabsNav.style.setProperty('--show-scroll-hint', '0');
        } else {
            tabsNav.style.setProperty('--show-scroll-hint', '0.7');
        }
    }

    // Écouter les événements de scroll
    if (tabsNav) {
        tabsNav.addEventListener('scroll', updateScrollIndicator);
        window.addEventListener('resize', updateScrollIndicator);

        // Vérifier au chargement
        setTimeout(updateScrollIndicator, 100);
    }

    // Support du swipe tactile amélioré
    let startX = 0;
    let scrollLeft = 0;
    let isScrolling = false;

    if (tabsNav) {
        tabsNav.addEventListener('touchstart', (e) => {
            isScrolling = true;
            startX = e.touches[0].pageX - tabsNav.offsetLeft;
            scrollLeft = tabsNav.scrollLeft;
        }, { passive: true });

        tabsNav.addEventListener('touchmove', (e) => {
            if (!isScrolling) return;

            const x = e.touches[0].pageX - tabsNav.offsetLeft;
            const walk = (startX - x) * 1.5; // Multiplicateur pour un scroll plus rapide
            tabsNav.scrollLeft = scrollLeft + walk;
        }, { passive: true });

        tabsNav.addEventListener('touchend', () => {
            isScrolling = false;
            updateScrollIndicator();
        }, { passive: true });
    }
});
