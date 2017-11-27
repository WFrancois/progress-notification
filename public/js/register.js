/** Switch advanced/simple */
var formAdvanced = false;
$('.js--switch-advanced').on('click', function(e) {
    formAdvanced = !formAdvanced;
    if(formAdvanced) {
        $('.js--div-advanced').show();
        $('.js--div-simple').hide();
        $(this).text('Simple');
    } else {
        $('.js--div-advanced').hide();
        $('.js--div-simple').show();
        $(this).text('Advanced');
    }
});

/** Activate region */
$('.js--checkbox-active').on('click', function(e) {
    var idActivate = $(this).data('target');
    $(this).remove();
    $('#' + idActivate).show();
});

function getCurrentData() {
    if(!formAdvanced) {
        return {world: $('#simple_world').val()};
    }

    var data = {};
    var inputWorld = $('#advanced_world');
    if(inputWorld.css('display') !== 'none') {
        data.world = inputWorld.val();
    }
    var inputUS = $('#advanced_us');
    if(inputUS.css('display') !== 'none') {
        data.us = inputUS.val();
    }
    var inputEU = $('#advanced_eu');
    if(inputEU.css('display') !== 'none') {
        data.eu = inputEU.val();
    }
    var inputKR = $('#advanced_kr');
    if(inputKR.css('display') !== 'none') {
        data.kr = inputKR.val();
    }
    var inputTW = $('#advanced_tw');
    if(inputTW.css('display') !== 'none') {
        data.tw = inputTW.val();
    }

    return data;
}

/** Submit Form */
const applicationServerPublicKey = 'BFY6IR4YmNp2hLF5jcFLsQV1n6QTMjowGEv8dpjx99AT-7kuXDW_8DnqwbW1v_jX1SX76iuiK7vCN_TeyBMcMKg';
var swRegistration = null;
var serviceWorkerEnabled = false;
var isSubscribed = false;

if ('serviceWorker' in navigator && 'PushManager' in window) {
    console.log('Service Worker and Push is supported');
    serviceWorkerEnabled = true;

    navigator.serviceWorker.register('sw.js')
        .then(function (swReg) {
            serviceWorkerEnabled = true;
            console.log('Service Worker is registered', swReg);
            swRegistration = swReg;
            initialiseUi();
        })
        .catch(function (error) {
            console.error('Service Worker Error', error);
            serviceWorkerEnabled = 'An error has occurred';
            reloadUi();
        });
} else {
    console.warn('Push messaging is not supported');
    serviceWorkerEnabled = 'Push messaging is not supported';
    reloadUi();
}

var button = $('.js--submit-button');

function reloadUi() {
    if (serviceWorkerEnabled !== true) {
        button.text(serviceWorkerEnabled);
        button.attr('disabled', true);
        return;
    }

    button.removeAttr('disabled');
    if (isSubscribed) {
        button.text('Disable notification');
    } else {
        button.text('Enable notification');
    }
}

function initialiseUi() {
    button.on('click', function (e) {
        button.attr('disabled', true);
        if (isSubscribed) {
            unsubscribeUser();
        } else {
            subscribeUser();
        }
    });

    // Set the initial subscription value
    swRegistration.pushManager.getSubscription()
        .then(function (subscription) {
            isSubscribed = !(subscription === null);
            reloadUi();
        });
}

function subscribeUser() {
    const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
    swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
    }).then(function (subscription) {
        console.log(subscription);
        $.post('/ajax/register', {subscription: subscription.toJSON(), subTo: getCurrentData()}).done(function(data) {
            isSubscribed = true;
            reloadUi();
        });
    }).catch(function (err) {
        console.log('Failed to subscribe the user: ', err);
        serviceWorkerEnabled = 'Failed to subscribe the user';
        reloadUi();
    });
}

function unsubscribeUser() {
    swRegistration.pushManager.getSubscription()
        .then(function (subscription) {
            if (subscription) {
                $.post('/ajax/register', {subscription: subscription.toJSON(), unsubscribe: true})
                    .always(function () {
                        return subscription.unsubscribe();
                    });
            }
        })
        .catch(function (error) {
            console.log('Error unsubscribing', error);
        })
        .then(function () {
            console.log('User is unsubscribed.');
            isSubscribed = false;
            reloadUi();
        });
}

function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}