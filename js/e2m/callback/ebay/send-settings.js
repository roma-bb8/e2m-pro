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

    settings['ebay-field-magento-attribute'] = e2m.attributes;

    //----------------------------------------

    new Ajax.Request(e2m.url.setSettings, {
        method: 'get',
        parameters: {
            settings: JSON.stringify(settings)
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
