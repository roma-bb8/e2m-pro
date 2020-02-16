(function () {
    'use strict';

    Event.observe(window, 'load', function () {

        paintImportProperties();

        $('attribute-set1').observe('change', function (element) {
            new Ajax.Request(e2m.url.getAttributs, {
                method: 'get',
                parameters: {
                    set_id: element.target.options[element.target.selectedIndex].value
                },
                onSuccess: function (transport) {
                    var response = JSON.parse(transport.responseText);

                    e2m.magentoAttributes = response.attributes;
                    e2m.attributes = {};

                    paintImportProperties();

                    var magentoAttribute = $('magento-attribute');

                    magentoAttribute.select('option').invoke('remove');
                    var cleanOption = document.createElement('option');
                    cleanOption.text = '';
                    magentoAttribute.add(cleanOption);

                    for (var [code, title] of Object.entries(e2m.magentoAttributes)) {
                        var option = document.createElement('option');
                        option.id = code;
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
    }, false);
})();
