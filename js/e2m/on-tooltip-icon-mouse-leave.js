function onToolTipIconMouseLeave() {

    e2m.isHideTooltip = true;

    setTimeout(function () {
        e2m.isHideTooltip && this.up().select('.tool-tip-message')[0].hide();
    }, 1000);
}
