var CACHE_VERSION = 'uwuzu-cache-v1';

var resources = [
    "/css/color.css",
    "/css/font.css",
    "/css/home.css",
    "/home/index.php",
    "/unsupported.php",
    "/img/sysimage/menuicon/addemoji.svg",
    "/img/sysimage/menuicon/addnotice.svg",
    "/img/sysimage/menuicon/bookmark.svg",
    "/img/sysimage/menuicon/emoji.svg",
    "/img/sysimage/menuicon/home.svg",
    "/img/sysimage/menuicon/info.svg",
    "/img/sysimage/menuicon/logout.svg",
    "/img/sysimage/menuicon/menu.svg",
    "/img/sysimage/menuicon/notice.svg",
    "/img/sysimage/menuicon/notification.svg",
    "/img/sysimage/menuicon/notification2.svg",
    "/img/sysimage/menuicon/others.svg",
    "/img/sysimage/menuicon/privacypolicy.svg",
    "/img/sysimage/menuicon/profile.svg",
    "/img/sysimage/menuicon/search.svg",
    "/img/sysimage/menuicon/server.svg",
    "/img/sysimage/menuicon/settings.svg",
    "/img/sysimage/menuicon/terms.svg",
    "/img/sysimage/menuicon/useradmin.svg"
];

self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(CACHE_VERSION).then(function (cache) {
            return cache.addAll(resources);
        })
    );
});

self.addEventListener('fetch', function (e) {
    if (!(e.request.url.indexOf('http') === 0)) return;
    
    if (e.request.method !== 'GET') {
        return;
    }

    const url = new URL(e.request.url);
    const pathname = url.pathname;

    if (resources.includes(pathname)) {
        e.respondWith(
            caches.match(e.request).then(function (response) {
                if (response) {
                    return response;
                }
                return fetch(e.request);
            })
        );
    } else {
        e.respondWith(fetch(e.request));
    }
});


self.addEventListener('message', function (e) {
    if (e.data && e.data.action === 'clearCache') {
        caches.keys().then(function (cacheNames) {
            Promise.all(
                cacheNames.map(function (cacheName) {
                    if (cacheName === CACHE_VERSION) {
                        return caches.delete(cacheName);
                    }
                })
            ).then(function (results) {
                if (results.includes(true)) {
                    console.log('キャッシュを削除しました');
                    caches.open(CACHE_VERSION).then(function (cache) {
                        cache.addAll(resources).then(function () {
                            console.log('リソースを再キャッシュしました');
                        }).catch(function (error) {
                            console.error('リソースの再キャッシュに失敗しました:', error);
                        });
                    });
                } else {
                    console.log('キャッシュ削除に失敗しました');
                }
            });
        });
    }
});
