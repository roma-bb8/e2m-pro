function downloadInventoryHandler(data) {
    $('download-inventory-progress').innerHTML = data.process;

    $('download-inventory-total-items').innerHTML = data.total;
    $('download-inventory-variation-items').innerHTML = data.variation;
    $('download-inventory-simple-items').innerHTML = data.simple;
}

function attributeSet() {

    var attributeSet = $('attribute-set');
    if (attributeSet === null) {
        return;
    }

    attributeSet.observe('change', function (element) {
        new Ajax.Request(e2m.url.getAttributesBySetId, {
            method: 'get',
            parameters: {
                set_id: element.target.options[element.target.selectedIndex].value
            },
            onSuccess: function (transport) {
                var response = JSON.parse(transport.responseText);

                e2m.magentoAttributes = response.data.attributes;
                e2m.attributes = {};

                var magentoAttribute = $('magento-attribute');

                magentoAttribute.select('option').invoke('remove');
                var cleanOption = document.createElement('option');
                cleanOption.text = '';
                magentoAttribute.add(cleanOption);

                for (var [code, title] of Object.entries(e2m.magentoAttributes)) {
                    var option = document.createElement('option');
                    option.value = code;
                    option.text = title.toString();
                    magentoAttribute.add(option);
                }
            },
            onFailure: function (transport) {
                var response = JSON.parse(transport.responseText);
                console.log(response);

                alert('Something went wrong...');
            }
        });
    });
}

function configInput() {
    $$('.config-input').invoke('observe', 'change', function () {
        $$('.config-button').forEach(function (element) {
            element.show();
        });
    });
}

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

    var generateSku = $('generate-sku');
    settings['generate-sku'] = generateSku.options[generateSku.selectedIndex].value;

    //----------------------------------------

    var deleteHtml = $('delete-html');
    settings['delete-html'] = deleteHtml.options[deleteHtml.selectedIndex].value;

    //----------------------------------------

    var attributeSet = $('attribute-set');
    settings['attribute-set'] = attributeSet.options[attributeSet.selectedIndex].value;

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

function getMagmiInventoryExportCSV() {
    new Ajax.Request(e2m.url.getMagmiInventoryExportCSV, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {

        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function getNativeInventoryExportCSV() {
    new Ajax.Request(e2m.url.getNativeInventoryExportCSV, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {

        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function getAttributesSQL() {
    new Ajax.Request(e2m.url.getAttributesSQL, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {

        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function getAttributesExportCSV() {
    new Ajax.Request(e2m.url.getAttributesExportCSV, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {

        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}

function getAttributesMatchingCSV() {
    new Ajax.Request(e2m.url.getAttributesMatchingCSV, {
        method: 'get',
        onCreate: function () {
            $('loading-mask').setStyle({
                visibility: 'visible'
            });
        },
        onSuccess: function () {

        },
        onFailure: function (transport) {
            console.log(transport);

            alert('Something went wrong...');
        }
    });
}
