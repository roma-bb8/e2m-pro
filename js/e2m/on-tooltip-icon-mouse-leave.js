function onToolTipIconMouseLeave() {

    e2m.isHideTooltip = true;

    var element = this.up().select('.tooltip-message')[0];
    setTimeout(function () {
        e2m.isHideTooltip && element.hide();
    }, 1000);
}
