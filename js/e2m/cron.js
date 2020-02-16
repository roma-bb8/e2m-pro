function cron() {
    new Ajax.Request(e2m.url.cronURL, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'hidden'
            });
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);

            response.run && setTimeout(cron, 3000);

            response.handlers.forEach(function (data) {
                data.handler === 'downloadInventoryHandler' && downloadInventoryHandler(data.data);
                data.handler === 'importInventoryHandler' && importInventoryHandler(data.data);
            });
        },
        onFailure: function (transport) {
            console.log(transport);
            alert('Cron tasks did not complete successfully...');
        }
    });
}
