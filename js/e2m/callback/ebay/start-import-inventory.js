function startImportInventory(element) {
    new Ajax.Request(e2m.url.startTaskImportInventory, {
        method: 'get',
        onSuccess: function (transport) {

            $$('.block-import-inventory-progress')[0].show();

            var response = JSON.parse(transport.responseText);
            importInventoryHandler(response.data);

            element.addClassName('disabled');
            element.innerHTML = 'Import inventory (in progress...)';
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
            return false;
        }
    });
}
