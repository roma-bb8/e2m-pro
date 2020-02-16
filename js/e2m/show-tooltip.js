function showToolTip() {

    e2m.isHideTooltip = false;

    $$('.tooltip-message').each(function (element) {
        element.hide();
    });

    if (this.up().select('.tooltip-message').length > 0) {
        changeToolTipPosition(this);
        this.up().select('.tooltip-message')[0].show();
        return;
    }

    var isShowLeft = false;
    if (this.up().previous('td').select('p.note')[0].hasClassName('show-left')) {
        isShowLeft = true;
    }

    var tipText = this.up().previous('td').select('p.note')[0].innerHTML;
    var tipWidth = this.up().previous('td').select('p.note')[0].getWidth();
    if (tipWidth > 500) {
        tipWidth = 500;
    }

    var additionalClassName = 'tooltip-right';
    if (isShowLeft) {
        additionalClassName = 'tooltip-left';
    }

    var toolTipSpan = new Element('span', {
        'class': 'tooltip-message ' + additionalClassName
    }).update(tipText).hide();

    if (isShowLeft) {
        toolTipSpan.style.width = tipWidth + 'px';
    }

    var imgUrl = e2m.url.skin + '/images/help.png';
    var toolTipImg = new Element('img', {
        'src': imgUrl
    });

    toolTipSpan.insert({
        top: toolTipImg
    });

    this.insert({
        after: toolTipSpan
    });

    changeToolTipPosition(this);

    toolTipSpan.show();

    toolTipSpan.observe('mouseout', onToolTipMouseLeave);
    toolTipSpan.observe('mouseover', onToolTipMouseEnter);
}
