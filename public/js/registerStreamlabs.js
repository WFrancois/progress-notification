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

const button = $('.js--submit-button');
var isSubscribed = button.data('defaultSubscribed');

button.on('click', function(e) {
    e.preventDefault();

    $('.js--chose-region').removeClass('is-invalid');
    $('.js--error').html('');
    button.attr('disabled', true);

    if(isSubscribed) {
        unsubscribeUser();
    } else {
        subscribeUser();
    }

    return false;
});

function subscribeUser() {
    var currentData = getCurrentData();

    if(jQuery.isEmptyObject(currentData)) {
        $('.js--chose-region').addClass('is-invalid');
        $('.js--error').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">\n' +
            '  Please choose a region' +
            '  <button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
            '    <span aria-hidden="true">&times;</span>\n' +
            '  </button>\n' +
            '</div>');
        reloadUi();
        return false;
    }


    $.post('/ajax/stream/register', {subTo: currentData, type: $('.js--streamlabs-type').val(), sound: $('.js--sound').val()}).done(function (data) {
        isSubscribed = true;
        reloadUi();
    });
}

function unsubscribeUser() {
    $.post('/ajax/stream/register', {unsubscribe: true}).done(function (data) {
        isSubscribed = false;
        reloadUi();
    });
}



function reloadUi() {
    button.removeAttr('disabled');
    if (isSubscribed) {
        button.text('Unsubscribe to alert from streamlabs');
        button.addClass('btn-warning');
        button.removeClass('btn-success');
    } else {
        button.text('Subscribe to alert from streamlabs');
        button.removeClass('btn-warning');
        button.addClass('btn-success');
    }
}