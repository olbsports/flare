// FLARE CUSTOM - Main JavaScript (OPTIMIZED)

// Smooth scroll navigation avec passive listeners
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar scroll effect optimisé avec requestAnimationFrame
let lastScroll = 0;
const nav = document.getElementById('mainNav');
let ticking = false;

function updateNavbar(currentScroll) {
    if (currentScroll <= 0) {
        nav.classList.remove('scroll-up');
        return;
    }
    
    if (currentScroll > lastScroll && !nav.classList.contains('scroll-down')) {
        nav.classList.remove('scroll-up');
        nav.classList.add('scroll-down');
    } else if (currentScroll < lastScroll && nav.classList.contains('scroll-down')) {
        nav.classList.remove('scroll-down');
        nav.classList.add('scroll-up');
    }
    lastScroll = currentScroll;
}

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (!ticking) {
        window.requestAnimationFrame(() => {
            updateNavbar(currentScroll);
            ticking = false;
        });
        ticking = true;
    }
}, { passive: true });

// Intersection Observer pour animations optimisé
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            // Unobserve après animation pour performance
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all sport cards
document.querySelectorAll('.sport-card').forEach(card => {
    observer.observe(card);
});

// FAQ Accordion optimisé
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const isExpanded = button.getAttribute('aria-expanded') === 'true';
        
        // Close all other FAQ items
        document.querySelectorAll('.faq-question').forEach(otherButton => {
            if (otherButton !== button) {
                otherButton.setAttribute('aria-expanded', 'false');
                otherButton.parentElement.classList.remove('active');
            }
        });
        
        // Toggle current item
        button.setAttribute('aria-expanded', !isExpanded);
        button.parentElement.classList.toggle('active');
    });
});

// Defer non-critical resources
if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
        console.log('🔥 FLARE CUSTOM initialized');
    });
} else {
    setTimeout(() => {
        console.log('🔥 FLARE CUSTOM initialized');
    }, 1);
}
