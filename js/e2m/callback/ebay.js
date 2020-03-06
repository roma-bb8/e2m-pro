function linkAccount() {

    var accountMode = $('account-mode');
    var accountId = accountMode.options[accountMode.selectedIndex].value;

    new Ajax.Request(e2m.url.linkEbayAccount, {
        method: 'get',
        parameters: {
            account_id: accountId
        },
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {
            window.location.reload();
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function unlinkAccount() {
    new Ajax.Request(e2m.url.unlinkEbayAccount, {
            method: 'get',
            onCreate: function () {
                $('loading-mask').setStyle({
                    visibility: 'visible'
                });
            },
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

function startDownloadInventory(element) {
    new Ajax.Request(e2m.url.startEbayDownloadInventory, {
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

function sendSettings() {

    var settings = {};

    //----------------------------------------

    settings['marketplace-store'] = {};
    $$('.marketplace-store').forEach(function (element) {
        var value = element.options[element.selectedIndex].value;
        if (value) {
            settings['marketplace-store'][element.id] = value.toString();
        }
    });

    //----------------------------------------

    var productIdentifier = $('inventory-settings-product-identifier');
    settings['product-identifier'] = productIdentifier.options[productIdentifier.selectedIndex].value;

    //----------------------------------------

    var actionFound = $('inventory-settings-action-found');
    settings['action-found'] = actionFound.options[actionFound.selectedIndex].value;

    //----------------------------------------

    var importQty = $('import-qty');
    settings['import-qty'] = importQty.options[importQty.selectedIndex].value;

    //----------------------------------------

    var generateSku = $('generate-sku');
    settings['generate-sku'] = generateSku.options[generateSku.selectedIndex].value;

    //----------------------------------------

    var importImage = $('import-image');
    settings['import-image'] = importImage.options[importImage.selectedIndex].value;

    //----------------------------------------

    var deleteHtml = $('delete-html');
    settings['delete-html'] = deleteHtml.options[deleteHtml.selectedIndex].value;

    //----------------------------------------

    var attributeSet = $('attribute-set');
    settings['attribute-set'] = attributeSet.options[attributeSet.selectedIndex].value;

    //----------------------------------------

    var importSpecifics = $('import-specifics');
    settings['import-specifics'] = importSpecifics.options[importSpecifics.selectedIndex].value;

    //----------------------------------------

    var renameAttributeTitleForSpecifics = $('rename-attribute-title-for-specifics');
    settings['rename-attribute-title-for-specifics'] = renameAttributeTitleForSpecifics.options[renameAttributeTitleForSpecifics.selectedIndex].value;

    //----------------------------------------

    settings['ebay-field-magento-attribute'] = e2m.attributes;

    //----------------------------------------

    new Ajax.Request(e2m.url.setSettings, {
        method: 'get',
        parameters: {
            settings: JSON.stringify(settings)
        },
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {
            window.location.reload();
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function startImportInventory(element) {
    new Ajax.Request(e2m.url.startEbayImportInventory, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
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

function pauseFinishImportInventory() {
    new Ajax.Request(e2m.url.proceedEbayImportInventory, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {
            window.location.reload();
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function pauseStartImportInventory() {
    new Ajax.Request(e2m.url.pauseEbayImportInventory, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {
            window.location.reload();
        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
