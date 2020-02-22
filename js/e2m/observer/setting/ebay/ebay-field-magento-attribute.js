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
