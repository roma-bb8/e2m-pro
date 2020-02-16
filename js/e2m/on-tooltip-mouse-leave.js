function onToolTipMouseLeave() {

    e2m.isHideTooltip = true;

    setTimeout(function () {
        e2m.isHideTooltip && this.hide();
    }, 1000);
}
