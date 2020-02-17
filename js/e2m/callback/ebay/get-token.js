function getToken() {

    var accountMode = $('account-mode');
    var mode = accountMode.options[accountMode.selectedIndex].value;

    new Ajax.Request(e2m.url.getBeforeEbayToken, {
        method: 'get',
        parameters: {
            mode: mode
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);

            window.location.replace(response.url);
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
