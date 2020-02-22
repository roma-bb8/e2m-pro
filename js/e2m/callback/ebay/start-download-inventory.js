function startDownloadInventory(element) {
    new Ajax.Request(e2m.url.startTaskDownloadInventory, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function (transport) {

            $$('.block-download-inventory-progress')[0].show();

            var response = JSON.parse(transport.responseText);
            downloadInventoryHandler(response.data);

            element.addClassName('disabled');
            element.innerHTML = 'Download inventory (in progress...)';
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
