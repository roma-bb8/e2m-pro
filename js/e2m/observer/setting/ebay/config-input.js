function configInput() {
    $$('.config-input').invoke('observe', 'change', function () {
        $$('.config-button').forEach(function (element) {
            element.show();
        });
    });
}
