(function () {
    'use strict';

    Event.observe(window, 'load', function () {

        initializeLocalStorage();
        attributeSet();
        configInput();
        ebayFieldMagentoAttribute();
        e2mHideBlock();
        noteBlock();
    }, false);
})();
