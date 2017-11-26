'use strict';

self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');

    var data = JSON.parse(event.data.text());
    console.log(data);

    const title = data.title;
    const options = {
        body: data.text,
        icon: 'images/icon.png',
        badge: 'images/badge.png'
    };

    event.waitUntil(self.registration.showNotification(title, options));
});