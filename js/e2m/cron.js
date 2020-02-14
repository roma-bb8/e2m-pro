(function () {
    function runCron() {
        new Ajax.Request(e2M.url.cronURL, {
            method: 'get',
            onCreate: function () {
                $('loading-mask').setStyle({
                    visibility: 'hidden'
                });
            },
            onComplete: function(transport) {
                console.log(transport);
            },
            onSuccess: function (transport) {
                var response = JSON.parse(transport.responseText);

                console.log(response);

                response.run && setTimeout(runCron, 5000);
                response.handlers.forEach(function (data) {
                    data.handler === 'downloadInventoryHandler' && downloadInventoryHandler(data.data);
                    data.handler === 'importInventoryHandler' && importInventoryHandler(data.data);
                });
            },
            onFailure: function () {
                alert('cron tasks did not complete successfully...');
            }
        });
    }

    setTimeout(runCron, 100);
})();
