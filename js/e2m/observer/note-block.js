(function () {
    'use strict';

    Event.observe(window, 'load', function () {
        $$('div.entry-edit').each(function (blockObj) {
            blockObj.select('p.note').each(function (noteElement) {

                if (noteElement.hasClassName('note-no-tool-tip') || noteElement.innerHTML.length <= 0) {
                    return;
                }

                if (typeof noteElement.up().next() != 'undefined' && noteElement.up().next() != null
                    && noteElement.up().next().select('.tool-tip-image').length > 0) {
                    return;
                }

                var imageUrl = e2m.url.skinURL + '/images/tool-tip-icon.png';
                var toolTipImg = new Element('img', {
                    'class': 'tool-tip-image',
                    'src': imageUrl
                });

                var toolTipContainer = new Element('td', {
                    class: 'value'
                });

                toolTipContainer.insert({
                    top: toolTipImg
                });

                noteElement.hide();
                noteElement.up().insert({after: toolTipContainer});
            });
        });
    }, false);
})();
