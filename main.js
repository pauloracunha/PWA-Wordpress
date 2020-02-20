/*
*
*  Push Notifications codelab
*  Copyright 2015 Google Inc. All rights reserved.
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*      https://www.apache.org/licenses/LICENSE-2.0
*
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License
*
*/

/* eslint-env browser, es6 */

'use strict';

const applicationServerPublicKey = js_global.publicKey;

const pushButton = document.querySelector('.js-push-btn');

let isSubscribed = false;
let swRegistration = null;

function urlB64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

function initialiseUI() {
  // Set the initial subscription value
  swRegistration.pushManager.getSubscription()
  .then(function(subscription) {
    isSubscribed = !(subscription === null);
	
    if (isSubscribed) {
      updateSubscriptionOnServer(subscription);
      const notification_img = $('span#resolve-notification>img').attr('src').replace(/notification-no/,"notification-ok");
      $('span#resolve-notification>img').attr('src', notification_img);
      $('span#resolve-notification>span').html('<i class="fas fa-caret-left"></i><strong>Maravilha!</strong> Você está recebendo nossas notificações.<button id="resolve-notification-unsubscribe">Desinscrever-se</button>');
    } else {
      const notification_img = $('span#resolve-notification>img').attr('src').replace(/notification-ok/,"notification-no");
      $('span#resolve-notification>img').attr('src', notification_img);
      $('span#resolve-notification>span').html('<i class="fas fa-caret-left"></i>Você ainda não está recebendo nossas notificações.<button id="resolve-notification-subscribe">Receber</button>');
      subscribeUser();
    }
  });
}

function subscribeUser() {
  const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
  swRegistration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: applicationServerKey
  })
  .then(function(subscription) {

    updateSubscriptionOnServer(subscription);

    isSubscribed = true;
  })
  .catch(function(err) {
    console.log('Failed to subscribe the user: ', err);
  });
}

function unsubscribeUser() {
  swRegistration.pushManager.getSubscription()
  .then(function(subscription) {
    if (subscription) {
      console.log(subscription);
      return subscription.unsubscribe();
    }
  })
  .catch(function(error) {
    console.log('Error unsubscribing', error);
  })
  .then(function() {
    updateSubscriptionOnServer(null);

    console.log('User is unsubscribed.');
    isSubscribed = false;
  });
}
function updateSubscriptionOnServer(subscription) {
	const key = subscription.getKey('p256dh');
	const token = subscription.getKey('auth');
	const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
	
	
	return $.post(js_global.xhr_url, {
			action: 'pwa_subscribe_client',
			'user_id': js_global.user_id,
			'pwa_subscription': js_global.pwa_subscription,
			endpoint: subscription.endpoint,
			publicKey: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
			authToken: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
			contentEncoding,
		},
		function(response){
			//console.log(response);
		}
	);
}
$(window).on('load', function(){
	$(document).on('click','#resolve-notification-subscribe', function(){
		initialiseUI();
	});
});