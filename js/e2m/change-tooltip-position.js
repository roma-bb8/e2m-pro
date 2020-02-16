function changeToolTipPosition(element) {

    var toolTip = element.up().select('.tool-tip-message')[0];
    var settings = {
        setHeight: false,
        setWidth: false,
        setLeft: true,
        offsetTop: 25,
        offsetLeft: 0
    };

    if (element.up().getStyle('float') === 'right') {
        settings.offsetLeft += 18;
    }

    if (element.up().match('span')) {
        settings.offsetLeft += 15;
    }

    toolTip.clonePosition(element, settings);

    if (toolTip.hasClassName('tip-left')) {
        toolTip.style.left = (parseInt(toolTip.style.left) - toolTip.getWidth() - 10) + 'px';
    }
}
