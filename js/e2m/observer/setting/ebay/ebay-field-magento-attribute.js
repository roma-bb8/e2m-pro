(function () {
    'use strict';

    Event.observe(window, 'load', function () {
        $$('.ebay-field-magento-attribute').invoke('observe', 'change', function() {

            var ebayField = $('ebay-field');
            var ebayFieldValue = ebayField.options[ebayField.selectedIndex].value;
            if (!ebayFieldValue) {
                return;
            }

            var magentoAttribute = $('magento-attribute');
            var magentoAttributeValue = magentoAttribute.options[magentoAttribute.selectedIndex].value;
            if (!magentoAttributeValue) {
                delete e2m.fieldAttribute[ebayFieldValue];
                return;
            }

            e2m.fieldAttribute[ebayFieldValue] = magentoAttributeValue;

            $('magento-attribute').selectedIndex = 0;
            $('ebay-field').selectedIndex = 0;

            paintImportProperties();
        });
    }, false);
})();
