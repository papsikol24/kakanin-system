const CACHE_NAME = 'kakanin-pwa-v2';
const API_CACHE_NAME = 'kakanin-api-v2';

const urlsToCache = [
  '/',
  '/landing-page',
  '/offline.html',
  '/manifest.json',
  '/menu',
  '/assets/images/owner.jpg',
  '/assets/images/bilao.jpg',
  '/assets/images/placeholder.jpg',
  '/assets/css/style.css',
  '/assets/js/script.js',
  '/assets/js/notification-sound.js',
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://unpkg.com/aos@2.3.1/dist/aos.css',
  'https://unpkg.com/aos@2.3.1/dist/aos.js'
];

// Install event - cache core assets
self.addEventListener('install', event => {
  console.log('🔧 Service Worker installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('✅ Caching core assets');
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('🚀 Service Worker activating...');
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== API_CACHE_NAME) {
            console.log('🗑️ Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// ===== HANDLE ADD TO CART - BLOCK WHEN OFFLINE =====
async function handleOfflineAddToCart() {
  return new Response(JSON.stringify({
    success: false,
    error: 'You are offline. Please connect to the internet to add items to your cart.',
    offline: true
  }), {
    status: 503,
    headers: { 'Content-Type': 'application/json' }
  });
}

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // ===== BLOCK ADD TO CART WHEN OFFLINE =====
  if (url.pathname.includes('/ajax/add-to-cart.php')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          return response;
        })
        .catch(error => {
          console.log('📴 Offline: Blocking add to cart');
          return handleOfflineAddToCart();
        })
    );
    return;
  }
  
  // ===== HANDLE PRODUCT API FOR MENU =====
  if (url.pathname.includes('/api/get-products.php') || 
      url.pathname.includes('/api/get-menu.php')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const responseClone = response.clone();
          caches.open(API_CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Return cached products from menu page
            return caches.match('/menu').then(menuResponse => {
              if (menuResponse) {
                return menuResponse.clone();
              }
              return new Response(JSON.stringify({
                success: false,
                error: 'You are offline. Menu unavailable.',
                offline: true
              }), {
                headers: { 'Content-Type': 'application/json' }
              });
            });
          });
        })
    );
    return;
  }
  
  // ===== HANDLE MENU PAGE - CACHE FOR OFFLINE BROWSING =====
  if (url.pathname === '/menu' || url.pathname === '/menu/') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }
            return caches.match('/offline.html');
          });
        })
    );
    return;
  }
  
  // ===== HANDLE CART PAGE - SHOW OFFLINE MESSAGE =====
  if (url.pathname === '/cart' || url.pathname === '/cart/') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          return response;
        })
        .catch(() => {
          // Serve offline cart page
          return new Response(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Cart Offline - Jen's Kakanin</title>
              <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
              <style>
                body {
                  font-family: 'Poppins', sans-serif;
                  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                  min-height: 100vh;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  margin: 0;
                  padding: 20px;
                }
                .offline-card {
                  background: white;
                  border-radius: 30px;
                  padding: 40px;
                  text-align: center;
                  max-width: 450px;
                  box-shadow: 0 20px 60px rgba(0,0,0,0.1);
                  animation: fadeIn 0.5s ease;
                }
                @keyframes fadeIn {
                  from { opacity: 0; transform: translateY(20px); }
                  to { opacity: 1; transform: translateY(0); }
                }
                .offline-icon {
                  font-size: 5rem;
                  margin-bottom: 20px;
                  color: #008080;
                }
                h2 {
                  color: #333;
                  margin-bottom: 15px;
                }
                p {
                  color: #666;
                  margin-bottom: 25px;
                  line-height: 1.6;
                }
                .btn-menu {
                  background: linear-gradient(135deg, #008080, #20b2aa);
                  color: white;
                  border: none;
                  border-radius: 50px;
                  padding: 12px 30px;
                  font-size: 1rem;
                  font-weight: 600;
                  cursor: pointer;
                  transition: all 0.3s;
                  text-decoration: none;
                  display: inline-block;
                  margin: 10px;
                }
                .btn-menu:hover {
                  transform: translateY(-2px);
                  box-shadow: 0 8px 20px rgba(0,128,128,0.3);
                }
                .btn-retry {
                  background: linear-gradient(135deg, #6c757d, #5a6268);
                  color: white;
                  border: none;
                  border-radius: 50px;
                  padding: 12px 30px;
                  font-size: 1rem;
                  font-weight: 600;
                  cursor: pointer;
                  transition: all 0.3s;
                  text-decoration: none;
                  display: inline-block;
                  margin: 10px;
                }
                .btn-retry:hover {
                  transform: translateY(-2px);
                  box-shadow: 0 8px 20px rgba(108,117,125,0.3);
                }
                .features {
                  margin-top: 25px;
                  padding-top: 20px;
                  border-top: 1px solid #eee;
                }
                .feature {
                  color: #28a745;
                  font-size: 0.9rem;
                  margin: 5px 0;
                }
                .feature i {
                  margin-right: 8px;
                }
              </style>
            </head>
            <body>
              <div class="offline-card">
                <div class="offline-icon">📶</div>
                <h2>You're Offline</h2>
                <p>You can still browse our menu, but you need an internet connection to view your cart or place orders.</p>
                
                <div class="features">
                  <div class="feature">
                    <i class="fas fa-check-circle text-success"></i> Browse menu
                  </div>
                  <div class="feature">
                    <i class="fas fa-times-circle text-danger"></i> Add to cart (offline)
                  </div>
                  <div class="feature">
                    <i class="fas fa-times-circle text-danger"></i> Checkout
                  </div>
                  <div class="feature">
                    <i class="fas fa-check-circle text-success"></i> Reconnect to continue
                  </div>
                </div>
                
                <div>
                  <a href="/menu" class="btn-menu">
                    <i class="fas fa-utensils me-2"></i>Browse Menu
                  </a>
                  <button class="btn-retry" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Retry
                  </button>
                </div>
                
                <p style="margin-top: 20px; font-size: 0.8rem; color: #999;">
                  <i class="fas fa-store me-1"></i> Jen's Kakanin
                </p>
              </div>
            </body>
            </html>
          `, {
            headers: { 'Content-Type': 'text/html' }
          });
        })
    );
    return;
  }
  
  // ===== HANDLE STATIC ASSETS =====
  if (
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/) ||
    url.pathname === '/manifest.json'
  ) {
    event.respondWith(
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then(response => {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
          return response;
        });
      })
    );
    return;
  }
  
  // ===== HANDLE PAGE NAVIGATIONS =====
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
          return response;
        })
        .catch(() => {
          return caches.match(event.request).then(cachedPage => {
            if (cachedPage) {
              return cachedPage;
            }
            return caches.match('/offline.html');
          });
        })
    );
    return;
  }
  
  // ===== DEFAULT: NETWORK FIRST, THEN CACHE =====
  event.respondWith(
    fetch(event.request)
      .then(response => {
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, responseClone);
        });
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});

// ===== PUSH NOTIFICATIONS =====
self.addEventListener('push', event => {
  let data = {
    title: 'Jen\'s Kakanin',
    body: 'Your order status has been updated!',
    icon: '/assets/images/owner.jpg',
    badge: '/assets/images/owner.jpg',
    url: '/dashboard'
  };

  if (event.data) {
    try {
      data = { ...data, ...event.data.json() };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge,
    vibrate: [200, 100, 200],
    data: {
      url: data.url,
      dateOfArrival: Date.now()
    },
    actions: [
      {
        action: 'open',
        title: 'View Order'
      },
      {
        action: 'close',
        title: 'Close'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// ===== NOTIFICATION CLICK =====
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'close') {
    return;
  }

  const urlToOpen = event.notification.data?.url || '/dashboard';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(windowClients => {
        for (const client of windowClients) {
          if (client.url.includes(urlToOpen) && 'focus' in client) {
            return client.focus();
          }
        }
        return clients.openWindow(urlToOpen);
      })
  );
});