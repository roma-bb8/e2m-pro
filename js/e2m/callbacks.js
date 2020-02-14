function getToken() {

    var accountMode = $('account-mode');
    var mode = accountMode.options[accountMode.selectedIndex].value;

    new Ajax.Request(e2M.url.beforeEbayGetToken, {
        method: 'get',
        parameters: {
            mode: mode
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);

            window.location.replace(response.auth_url);
        },
        onFailure: function () {
            alert('Something went wrong...');
            return false;
        }
    });
}

function unsetToken() {
    new Ajax.Request(e2M.url.deleteEbayToken, {
            method: 'get',
            onSuccess: function () {
                location.reload();
            },
            onFailure: function (error) {
                console.log(error);

                alert('Something went wrong...');
                return false;
            }
        }
    );
}

// ----------------------------------------

function startDownloadInventory(element) {
    new Ajax.Request(e2M.url.startTaskDownloadInventory, {
        method: 'get',
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);

            $$('.block-download-inventory-progress')[0].show();

            element.addClassName('disabled');
            element.innerHTML = 'Download inventory (in progress...)';

            $('download-inventory-progress').innerHTML = response.data.process;

            $('download-inventory-total-items').innerHTML = response.data.total;
            $('download-inventory-variation-items').innerHTML = response.data.variation;
            $('download-inventory-simple-items').innerHTML = response.data.simple;

            console.log(response.message);
        },
        onFailure: function (transport) {
            var response = JSON.parse(transport.responseText);

            console.log(response);

            alert(response.message ? response.message : 'Something went wrong...');
            return false;
        }
    });
}

function startImportInventory(element) {
    new Ajax.Request(e2M.url.startTaskImportInventory, {
        method: 'get',
        onComplete: function (error) {

            console.log(error);
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);

            $$('.block-import-inventory-progress')[0].show();

            element.addClassName('disabled');
            element.innerHTML = 'Import inventory (in progress...)';

            $('import-inventory-progress').innerHTML = response.data.process;

            console.log(response.message);
        },
        onFailure: function (transport) {
            var response = JSON.parse(transport.responseText);

            console.log(response);

            alert(response.message ? response.message : 'Something went wrong...');
            return false;
        }
    });
}
