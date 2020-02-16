(function () {
    'use strict';

    Event.observe(window, 'load', function () {
        $$('.config-input').invoke('observe', 'change', function () {
            $$('.config-button').forEach(function (element) {
                element.show();
            });
        });
    }, false);
})();
