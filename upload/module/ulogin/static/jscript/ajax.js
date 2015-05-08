jQuery(document).ready(function () {
    var uloginNetwork = jQuery('#ulogin_accounts').find('.ulogin_network');
    uloginNetwork.click(function () {
        var network = jQuery(this).attr('data-ulogin-network');
        var identity = jQuery(this).attr('data-ulogin-identity');
        uloginDeleteAccount(network,identity);
    });
});

function uloginDeleteAccount(network,identity) {
    var query = $.ajax({
        type: 'POST',
        data: {
            identity: identity,
            network: network
        },
        dataType: 'json',
        error: function (data) {
            alert('Не удалось выполнить запрос');
        },
        success: function (data) {
            if (data.answerType == 'error') {
                alert(data.msg);
            }
            if (data.answerType == 'ok') {
                var accounts = jQuery('#ulogin_accounts'),
                    nw = accounts.find('[data-ulogin-network=' + network + ']');
                if (nw.length > 0) nw.hide();
                alert('Соц.сеть успешно удалёна');
            }
        }
    });
    return false;
}
