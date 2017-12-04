/** Activate region */
$('.js--checkbox-active').on('click', function (e) {
    var idActivate = $(this).data('target');

    if ($(this).is(':checked')) {
        $('#' + idActivate).show();
    } else {
        $('#' + idActivate).hide();
    }
});

function getCurrentData() {
    var data = {};

    var howMany = parseInt($('.js--how-many-guilds').val());

    $('.js--chose-region').each(function (i, item) {
        if ($(item).is(':checked')) {
            var region = $(item).data('region');
            data[region] = howMany;
        }
    });

    return data;
}

/** Submit Form */
var swRegistration = null;
var serviceWorkerEnabled = false;
var isSubscribed = false;
var untouchForm = true;

var button = $('.js--submit-button');

if ('serviceWorker' in navigator && 'PushManager' in window) {
    console.log('Service Worker and Push is supported');
    serviceWorkerEnabled = true;

    navigator.serviceWorker.register('sw.js')
        .then(function (swReg) {
            serviceWorkerEnabled = true;
            console.log('Service Worker is registered');
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

$('.js--input-option').on('change', function(e) {
    untouchForm = false;
});

$(document).ready(function () {
    // Set the initial subscription value
    swRegistration.pushManager.getSubscription().then(function (subscription) {
        if (!subscription) {
            isSubscribed = false;
            return;
        }

        $.post('/ajax/current-subscription', {subscription: subscription.toJSON()}, function (data) {
            isSubscribed = true;
            if (!data.regions || !data.howMuch) {
                return;
            }

            if(!untouchForm) {
                return;
            }

            $('.js--chose-region').each(function (i, item) {
                var region = $(item).data('region');

                $(item).prop('checked', data.regions.indexOf(region) !== -1);
            });

            $('.js--how-many-guilds').val(data.howMuch);
        }, 'json').catch(function(e) {
            if(e.status === 404) {
                subscription.unsubscribe();
                isSubscribed = false;
            }
        }).always(function () {
            reloadUi();
        });
    })
});

function reloadUi() {
    if (serviceWorkerEnabled !== true) {
        button.text(serviceWorkerEnabled);
        button.addClass('btn-danger');
        button.removeClass('btn-success');
        button.attr('disabled', true);
        return;
    }

    button.removeAttr('disabled');
    if (isSubscribed) {
        button.text('Disable notification');
        button.addClass('btn-warning');
        button.removeClass('btn-success');
    } else {
        button.text('Enable notification');
        button.removeClass('btn-warning');
        button.addClass('btn-success');
    }
}

function initialiseUi() {
    button.on('click', function (e) {
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
    $('.js--chose-region').removeClass('is-invalid');
    $('.js--error').html('');
    button.attr('disabled', true);

    const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
    var currentData = getCurrentData();

    if(jQuery.isEmptyObject(currentData)) {
        $('.js--chose-region').addClass('is-invalid');
        $('.js--error').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">\n' +
            '  Please choose a region' +
            '  <button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
            '    <span aria-hidden="true">&times;</span>\n' +
            '  </button>\n' +
            '</div>');
        button.removeAttr('disabled');
        return;
    }

    swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
    }).then(function (subscription) {
        $.post('/ajax/register', {subscription: subscription.toJSON(), subTo: currentData}).done(function (data) {
            isSubscribed = true;
            reloadUi();
        });
    }).catch(function (err) {
        console.warn('Failed to subscribe the user: ', err);
        serviceWorkerEnabled = 'Failed to subscribe the user';
        reloadUi();
    });
}

function unsubscribeUser() {
    button.attr('disabled', true);
    swRegistration.pushManager.getSubscription().then(function (subscription) {
        if (subscription) {
            $.post('/ajax/register', {subscription: subscription.toJSON(), unsubscribe: true})
                .always(function () {
                    isSubscribed = false;
                    reloadUi();

                    return subscription.unsubscribe();
                });
        }
    }).catch(function (error) {
        console.log('Error unsubscribing', error);
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