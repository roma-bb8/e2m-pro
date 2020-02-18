function pauseStartImportInventory() {
    new Ajax.Request(e2m.url.pauseStartTaskImportInventory, {
        method: 'get',
        onSuccess: function () {
            window.location.reload();
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
