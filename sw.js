var CACHE_NAME  = "app_uwuzu";
var urlsToCache = [
    "home/index.php",
    "home/ftl.php",
    "notification/index.php",
    "search/index.php",
    "require/botbox.php",
    "require/leftbox.php",
    "require/rightbox.php",
    "require/botbox.php",
    "user/index.php",
    "settings/index.php",
    "rule/terms.php",
    "rule/privacypolicy.php",
    "rule/uwuzuabout.php",
    "index.php",
    "login.php",
    "new.php",
    "check.php",
    "success.php",
    "img/",
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(
            function(cache){
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
      caches.match(event.request)
        .then(
        function (response) {
            if (response) {
                return response;
            }
            return fetch(event.request);
        })
    );
});