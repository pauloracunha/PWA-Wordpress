'use strict';

/**
 * Service Worker
 * 
 */
<?php $blognamewithoutspace = str_replace(' ', '', get_bloginfo('name')); ?>
const cacheName = 'pauloresolve.com.br-pwa0.2';
const startPage = '/';
const offlinePage = '/';
const filesToCache = [startPage, offlinePage];
const neverCacheUrls = [/\/wp-admin/,/\/wp-login/,/preview=true/,/\/finalizar-compra/,/\/minha-conta/];

// Install
self.addEventListener('install', function(e) {
	console.log('<?php echo $blognamewithoutspace; ?>: service worker installation');
	e.waitUntil(
		caches.open(cacheName).then(function(cache) {
			console.log('<?php echo $blognamewithoutspace; ?>: service worker caching dependencies');
			filesToCache.map(function(url) {
				return cache.add(url).catch(function (reason) {
					return console.log('<?php echo $blognamewithoutspace; ?>: ' + String(reason) + ' ' + url);
				});
			});
		})
	);
});

// Activate
self.addEventListener('activate', function(e) {
	console.log('<?php echo $blognamewithoutspace; ?>: service worker activation');
	e.waitUntil(
		caches.keys().then(function(keyList) {
			return Promise.all(keyList.map(function(key) {
				if ( key !== cacheName ) {
					console.log('<?php echo $blognamewithoutspace; ?> old cache removed', key);
					return caches.delete(key);
				}
			}));
		})
	);
	return self.clients.claim();
});

function getWindow() {
	return (function(window) {
    	return window;
	})(new Function('return this;')());
}
// Fetch
self.addEventListener('fetch', function(e) {
	const query = e.request.url.split('?');
	if(query[1]!=undefined){
		const partes = query[1].split('&');
		partes.forEach(function (parte) {
		    var chaveValor = parte.split('=');
		    var chave = chaveValor[0];
		    var valor = chaveValor[1];
		    if(chave=='msg'){
		    	var options = {
					body: decodeURIComponent(valor).replace(/%20/g,' '),
					icon: '<?php echo get_site_icon_url(); ?>'
				};
				if(Notification.permission === "granted") {
					const notificationPromise = self.registration.showNotification('Via Sonho', options);
					e.waitUntil(notificationPromise);
				}
				return;
			}
		});
	}
	// Return if the current request url is in the never cache list
	if ( ! neverCacheUrls.every(checkNeverCacheList, e.request.url) ) {
		console.log( '<?php echo $blognamewithoutspace; ?>: Current request is excluded from cache.' );
		e.respondWith(
			fetch(e.request).catch( function() {
				return caches.match(offlinePage);
			})
		);
		return;
	}
	
	// Return if request url protocal isn't http or https
	if ( ! e.request.url.match(/^(http|https):\/\//i) )
		return;
	
	// Return if request url is from an external domain.
	if ( new URL(e.request.url).origin !== location.origin )
		return;
	
	// For POST requests, do not use the cache. Serve offline page if offline.
	if ( e.request.method !== 'GET' ) {
		e.respondWith(
			fetch(e.request).catch( function() {
				return caches.match(offlinePage);
			})
		);
		return;
	}
	
	// Revving strategy
	if ( e.request.mode === 'navigate' && navigator.onLine ) {
		e.respondWith(
			fetch(e.request).then(function(response) {
				return caches.open(cacheName).then(function(cache) {
					cache.put(e.request, response.clone());
					return response;
				});  
			})
		);
		return;
	}

	e.respondWith(
		caches.match(e.request).then(function(response) {
			return response || fetch(e.request).then(function(response) {
				return caches.open(cacheName).then(function(cache) {
					cache.put(e.request, response.clone());
					return response;
				});  
			});
		}).catch(function() {
			return caches.match(offlinePage);
		})
	);
});

// Check if current url is in the neverCacheUrls list
function checkNeverCacheList(url) {
	if ( this.match(url) ) {
		return false;
	}
	return true;
}

// Notifications Push
self.addEventListener('push', pushEvent => {
	let eventPush = pushEvent.data.json();
	if(eventPush.private){
		if(eventPush.user!=<?php echo get_current_user_id(); ?>){
			return;
		}
	}
	const title = eventPush.title;
	const options = eventPush.options;
	const notificationPromise = self.registration.showNotification(title, options);
	pushEvent.waitUntil(notificationPromise);
});
self.addEventListener('notificationclick', function(event) {
	if(event.notification.data.url==null){
		return;
	}
	var url = event.notification.data.url;
	if(event.notification.data.url.indexOf('?')<=-1){
		url = event.notification.data.url+'?tag='+event.notification.tag;
	} else {
		url = event.notification.data.url+'&tag='+event.notification.tag;
	}
	event.notification.close();
	event.waitUntil(
		clients.openWindow(url)
	);
});
