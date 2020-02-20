function startImportInventory(element) {
    new Ajax.Request(e2m.url.startTaskImportInventory, {
        method: 'get',
        onSuccess: function (transport) {

            var response = JSON.parse(transport.responseText);
            importInventoryHandler(response.data);

            var button = $('pause-download-inventory-button').children[0];
            button.removeClassName('disabled');
            button.removeAttribute('disabled');

            element.addClassName('disabled');
            element.innerHTML = 'Import inventory (in progress...)';
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
