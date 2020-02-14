function paintFieldsAttributes() {
    var listE = $('field-attribute-list-e');
    var listM = $('field-attribute-list-m');
    if (listE === null || listM === null) {
        return;
    }

    listE.innerHTML = '';
    listM.innerHTML = '';
    for (var [key, value] of Object.entries(e2M.fieldAttribute)) {

        var span1 = document.createElement('span');

        span1.innerHTML = " <&nbsp;&nbsp;" + e2M.eBayAllFields[key];
        span1.append(document.createElement('br'));
        listE.append(span1);

        var span2 = document.createElement('span');
        span2.innerHTML = e2M.magentoAllAttributes[value];
        span2.append(document.createElement('br'));
        listM.append(span2);
    }
}

//----------------------------------------

$$('.ebay-field-magento-attribute').invoke('observe', 'change', function () {

    var ebayField = $('ebay-field');
    var ebayFieldValue = ebayField.options[ebayField.selectedIndex].value;
    if (!ebayFieldValue) {
        return;
    }

    var magentoAttribute = $('magento-attribute');
    var magentoAttributeValue = magentoAttribute.options[magentoAttribute.selectedIndex].value;
    if (!magentoAttributeValue) {
        delete e2M.fieldAttribute[ebayFieldValue];
        return;
    } else {

        e2M.fieldAttribute.forEach(function (e, i) {
            if (e === magentoAttributeValue) {
                delete e2M.fieldAttribute[i];
            }
        });

        e2M.fieldAttribute[ebayFieldValue] = magentoAttributeValue;

        $('magento-attribute').selectedIndex = 0;
        $('ebay-field').selectedIndex = 0;
    }

    paintFieldsAttributes();
});

//----------------------------------------

function sendSettings() {

    var settings = {};

    //----------------------------------------

    settings['marketplace-store'] = {};
    $$('.marketplace-store').forEach(function (element) {
        var value = element.options[element.selectedIndex].value;
        if (value === undefined || value === null) {
            settings['marketplace-store'].message = 'Not all selected marketplaces: ' + element.options[element.selectedIndex].innerHTML;
        } else {
            settings['marketplace-store'][element.id.toString()] = value.toString();
        }
    });

    //----------------------------------------

    var productIdentifier = $('inventory-settings-product-identifier');
    settings['product-identifier'] = productIdentifier.options[productIdentifier.selectedIndex].value;

    var actionFound = $('inventory-settings-action-found');
    settings['action-found'] = actionFound.options[actionFound.selectedIndex].value;

    //----------------------------------------

    settings['ebay-field-magento-attribute'] = e2M.fieldAttribute;

    //----------------------------------------

    var attributeSet = $('attribute-set');
    settings['attribute-set'] = attributeSet.options[attributeSet.selectedIndex].value;

    //----------------------------------------

    var importImage = $('import-image');
    settings['import-image'] = importImage.options[importImage.selectedIndex].value;

    //----------------------------------------

    var importQty = $('import-qty');
    settings['import-qty'] = importQty.options[importQty.selectedIndex].value;

    //----------------------------------------

    new Ajax.Request(e2M.url.sendSettings, {
        method: 'get',
        parameters: {
            settings: JSON.stringify(settings)
        },
        onSuccess: function (transport) {
            var response = JSON.parse(transport.responseText);
            console.log(response);

            window.location.reload();
        },
        onFailure: function (transport) {
            var response = JSON.parse(transport.responseText);
            console.log(response);

            alert('Something went wrong...');
            return false;
        }
    });
}
