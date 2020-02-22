function noteBlock() {

    e2m.isHideTooltip = false;

    $$('div.entry-edit').each(function (blockObj) {
        blockObj.select('p.note').each(function (noteElement) {

            if (noteElement.hasClassName('note-no-tool-tip') || noteElement.innerHTML.length <= 0) {
                return;
            }

            if (typeof noteElement.up().next() != 'undefined' && noteElement.up().next() != null
                && noteElement.up().next().select('.tooltip-image').length > 0) {
                return;
            }

            var imageUrl = e2m.url.skin + '/images/tool-tip-icon.png';
            var toolTipImg = new Element('img', {
                'class': 'tooltip-image',
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

    $$('.tooltip-image').each(function (element) {
        element.observe('mouseover', showToolTip);
        element.observe('mouseout', onToolTipIconMouseLeave);
    });

    $$('.tooltip-message').each(function (element) {
        element.observe('mouseout', onToolTipMouseLeave);
        element.observe('mouseover', onToolTipMouseEnter);
    });
}
