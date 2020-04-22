(function () {
    'use strict';

    Event.observe(window, 'load', function () {

        initializeLocalStorage();
        attributeSet();
        configInput();
        e2mHideBlock();
        noteBlock();
    }, false);
})();
