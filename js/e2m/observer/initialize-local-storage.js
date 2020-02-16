(function () {
    'use strict';

    Event.observe(window, 'load', function () {
        var data = localStorage.getItem(e2m.prefix);
        if (data === null) {
            localStorage.setItem(e2m.prefix, JSON.stringify({}));
            return;
        }

        try {
            e2m.localStorage = JSON.parse(data);
        } catch (exception) {
            localStorage.setItem(e2m.prefix, JSON.stringify({}));
        }
    }, false);
})();
