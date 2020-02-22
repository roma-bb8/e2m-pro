function cron() {
    new Ajax.Request(e2m.url.cron, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'hidden'
            });
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);
            console.log(response);

            setTimeout(cron, 3000);
            response.data.forEach(function (task) {
                task.handler === 'downloadInventoryHandler' && downloadInventoryHandler(task.data);
                task.handler === 'importInventoryHandler' && importInventoryHandler(task.data);
            });
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Cron tasks did not complete successfully...');
        }
    });
}
