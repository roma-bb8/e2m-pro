(function () {
    'use strict';

    Event.observe(window, 'load', function () {

        cron();
        initializeLocalStorage();
        attributeSet();
        configInput();
        ebayFieldMagentoAttribute();
        e2mHideBlock();
        noteBlock();
    }, false);
})();
