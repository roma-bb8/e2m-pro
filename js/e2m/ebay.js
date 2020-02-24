function paintImportProperties() {
    var eBayFieldList = $('field-attribute-list-e');
    var magentoFieldList = $('field-attribute-list-m');
    if (eBayFieldList === null || magentoFieldList === null) {
        return;
    }

    eBayFieldList.innerHTML = '';
    magentoFieldList.innerHTML = '';

    for (var [attributeCode, eBayCode] of Object.entries(e2m.attributes)) {

        var attributeCodeSpan = document.createElement('span');
        attributeCodeSpan.innerHTML = ">&nbsp;&nbsp;" + e2m.magentoAttributes[attributeCode];
        attributeCodeSpan.append(document.createElement('br'));
        magentoFieldList.append(attributeCodeSpan);

        var eBayCodeSpan = document.createElement('span');
        eBayCodeSpan.innerHTML = e2m.eBayFields[eBayCode];
        eBayCodeSpan.append(document.createElement('br'));
        eBayFieldList.append(eBayCodeSpan);
    }
}

function attributeSet() {

    paintImportProperties();

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

                paintImportProperties();

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

function ebayFieldMagentoAttribute() {

    $$('.ebay-field-magento-attribute').invoke('observe', 'change', function () {

        var magentoAttribute = $('magento-attribute');
        var magentoAttributeValue = magentoAttribute.options[magentoAttribute.selectedIndex].value;
        if (!magentoAttributeValue) {
            return;
        }

        var ebayField = $('ebay-field');
        var ebayFieldValue = ebayField.options[ebayField.selectedIndex].value;
        if (!ebayFieldValue) {
            delete e2m.attributes[magentoAttributeValue];
        } else {

            e2m.attributes[magentoAttributeValue] = ebayFieldValue;

            $('magento-attribute').selectedIndex = 0;
            $('ebay-field').selectedIndex = 0;
        }

        paintImportProperties();
    });
}
