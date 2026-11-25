/**
 * Tech Googly Partner Program - Service Worker
 * Safe static-asset caching only. Never caches authenticated financial or private KYC data.
 */

const CACHE_NAME = 'tg-partner-static-v1';
const STATIC_ASSETS = [
    '/favicon.png',
    '/favicon.ico',
    '/images/brand/tech-googly-logo.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    '/offline.html',
    '/manifest.json'
];

// Install: Cache public static shell assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate: Clean up old cache versions
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch: Network-First for navigations and API; Cache-First only for public static assets
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests immediately
    if (request.method !== 'GET') {
        return;
    }

    // Explicit security rule: NEVER cache or serve authenticated financial/KYC pages or storage documents from SW cache
    const isSensitivePath = url.pathname.startsWith('/admin') ||
                            url.pathname.startsWith('/partner') ||
                            url.pathname.startsWith('/team') ||
                            url.pathname.startsWith('/storage') ||
                            url.pathname.startsWith('/kyc');

    // 1. Navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const offlinePage = await cache.match('/offline.html');
                return offlinePage || new Response('Offline', { status: 503, statusText: 'Offline' });
            })
        );
        return;
    }

    // 2. Sensitive paths: always bypass cache directly to network
    if (isSensitivePath) {
        event.respondWith(fetch(request));
        return;
    }

    // 3. Static assets (images, Vite build assets, manifest, etc.)
    if (url.origin === self.location.origin) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    // Only cache successful static responses (css, js, images)
                    if (
                        networkResponse &&
                        networkResponse.status === 200 &&
                        (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/'))
                    ) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                });
            })
        );
    }
});

// Message listener for explicit cache clearing on user logout
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.keys().then((keys) => {
            keys.forEach((key) => caches.delete(key));
        });
    }
});
