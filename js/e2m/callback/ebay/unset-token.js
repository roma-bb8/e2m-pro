function unsetToken() {
    new Ajax.Request(e2m.url.unsetEbayToken, {
            method: 'get',
            onSuccess: function () {
                window.location.reload();
            },
            onFailure: function (transport) {
                console.log(transport);

                alert('Something went wrong...');
            }
        }
    );
}
