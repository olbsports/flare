// FLARE CUSTOM - Service Worker v1.0
// Cache assets pour performance et offline support

const CACHE_NAME = 'flare-custom-v1';
const RUNTIME_CACHE = 'flare-runtime';

// Assets critiques à mettre en cache
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/assets/css/style.css',
    '/assets/css/style-sport.css',
    '/assets/css/components.css',
    '/assets/js/script.js',
    '/assets/js/components-loader.js',
    '/pages/components/header.html',
    '/pages/components/footer.html'
];

// Installation: cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activation: cleanup old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME && name !== RUNTIME_CACHE)
                    .map(name => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Network First pour HTML/CSV, Cache First pour assets
self.addEventListener('fetch', event => {
    const {request} = event;
    const url = new URL(request.url);
    
    // Skip cross-origin requests sauf fonts/images
    if (url.origin !== location.origin && 
        !url.hostname.includes('fonts.g') && 
        !url.hostname.includes('unsplash')) {
        return;
    }
    
    // Network First pour HTML et CSV
    if (request.destination === 'document' || url.pathname.endsWith('.csv')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE).then(cache => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }
    
    // Cache First pour CSS, JS, images, fonts
    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            
            return fetch(request).then(response => {
                // Ne cache que les réponses OK
                if (!response || response.status !== 200) {
                    return response;
                }
                
                const responseClone = response.clone();
                caches.open(RUNTIME_CACHE).then(cache => {
                    cache.put(request, responseClone);
                });
                
                return response;
            });
        })
    );
});
