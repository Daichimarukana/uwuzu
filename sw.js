var CACHE_NAME = "app_uwuzu";
var urlsToCache = [
    "/home/index.php",
    "/unsupported.php",
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            }).catch(function(error) {
                console.error("Failed to cache:", error);
            })
    );
});

/*
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }

                var url = new URL(event.request.url);

                if (!url.pathname.includes(".")) {
                    if (!url.pathname.endsWith('/')) {
                        url.pathname += '/';
                    }
                    return caches.match(url.pathname + "index.php");
                }

                return fetch(event.request)
                    .then(function(networkResponse) {
                        return caches.open("app_uwuzu").then(function(cache) {
                            cache.put(event.request, networkResponse.clone());
                            return networkResponse;
                        });
                    })
                    .catch(function() {
                        return caches.match('/unsupported.php');
                    });
            })
    );
});
*/