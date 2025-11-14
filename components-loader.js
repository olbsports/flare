// 🔥 FLARE CUSTOM - COMPONENTS LOADER TOUT-EN-UN

console.log('🔥 FLARE - Loader démarré');

// ========================================
// 1. CHARGER LES COMPOSANTS
// ========================================

async function loadComponent(elementId, filePath) {
    try {
        const response = await fetch(filePath);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const html = await response.text();
        const element = document.getElementById(elementId);
        if (element) element.innerHTML = html;
    } catch (error) {
        console.error(`Error loading ${filePath}:`, error);
    }
}

// ========================================
// 2. FERMER TOUS LES MENUS
// ========================================

function fermerTousLesMenus() {
    // Mega menus desktop
    document.querySelectorAll('.has-mega-menu').forEach(item => {
        item.classList.remove('active');
    });
    
    // Menu mobile
    const mobile = document.getElementById('mobileMenu');
    if (mobile) mobile.classList.remove('active');
    
    // Overlay mobile
    const overlay = document.getElementById('mobileMenuOverlay');
    if (overlay) overlay.classList.remove('active');
    
    // Burger
    const burger = document.querySelector('.btn-burger');
    if (burger) burger.classList.remove('active');
    
    // Body
    document.body.style.overflow = 'auto';
}

// ========================================
// 3. FONCTIONS GLOBALES (appelées depuis HTML)
// ========================================

window.toggleMega = function(e, menuId) {
    e.stopPropagation();
    const button = e.target.closest('.nav-link-premium');
    if (!button) return;
    const parentItem = button.closest('.has-mega-menu');
    if (!parentItem) return;
    
    // Fermer tous les autres mega menus
    document.querySelectorAll('.has-mega-menu').forEach(item => {
        if (item !== parentItem) {
            item.classList.remove('active');
        }
    });
    
    // Toggle le mega menu actuel
    parentItem.classList.toggle('active');
};

window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const burger = document.querySelector('.btn-burger');
    
    if (menu) menu.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
    if (burger) burger.classList.toggle('active');
    
    if (menu && menu.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
};

window.toggleMobileSection = function(button) {
    const section = button.parentElement;
    
    // Fermer les autres sections
    document.querySelectorAll('.mobile-section-ultra').forEach(s => {
        if (s !== section) {
            s.classList.remove('active');
        }
    });
    
    // Toggle la section actuelle
    section.classList.toggle('active');
};

// ========================================
// 4. INIT AU CHARGEMENT
// ========================================

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🔥 Chargement composants...');
    
    // Charger header et footer
    await Promise.all([
        loadComponent('dynamic-header', '/header.html'),
        loadComponent('dynamic-footer', '/footer.html')
    ]);
    
    console.log('✅ Composants chargés');
    
    // Fermer TOUS les menus immédiatement
    setTimeout(fermerTousLesMenus, 50);
    setTimeout(fermerTousLesMenus, 200);
    
    // Init navbar scroll
    initNavbar();
    
    // Init back to top
    setTimeout(initBackToTop, 500);
});

// ========================================
// 5. NAVBAR SCROLL
// ========================================

function initNavbar() {
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('mainNav');
        if (!nav) return;
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 100) {
            nav.classList.remove('scrolled', 'hidden');
        } else {
            nav.classList.add('scrolled');
            if (currentScroll > lastScroll && currentScroll > 300) {
                nav.classList.add('hidden');
            } else {
                nav.classList.remove('hidden');
            }
        }
        lastScroll = currentScroll;
    }, { passive: true });
}

// ========================================
// 6. BACK TO TOP
// ========================================

function initBackToTop() {
    const btn = document.querySelector('.back-to-top-ultra, .back-to-top-gorgeous, .back-to-top-nike, .back-to-top-v3');
    if (btn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 500) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }, { passive: true });
        
        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

// ========================================
// 7. FERMER MEGA MENU AU CLIC AILLEURS
// ========================================

document.addEventListener('click', e => {
    if (!e.target.closest('.has-mega-menu')) {
        document.querySelectorAll('.has-mega-menu').forEach(item => {
            item.classList.remove('active');
        });
    }
});

// ========================================
// 8. FERMER AVEC ESCAPE
// ========================================

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        fermerTousLesMenus();
    }
});

// ========================================
// 9. FERMER MENU MOBILE AU CLIC SUR LIEN
// ========================================

document.addEventListener('click', e => {
    if (e.target.closest('.mobile-link-ultra, .mobile-simple-link')) {
        setTimeout(() => {
            const mobile = document.getElementById('mobileMenu');
            const overlay = document.getElementById('mobileMenuOverlay');
            const burger = document.querySelector('.btn-burger');
            
            if (mobile) mobile.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (burger) burger.classList.remove('active');
            document.body.style.overflow = 'auto';
        }, 100);
    }
});

console.log('✅ Loader tout-en-un prêt');
