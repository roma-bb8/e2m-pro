function hideBlock(blockClass, init) {
    blockClass = blockClass || '';
    if (blockClass === '') {
        return false;
    }

    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_visibility_changer').each(function (o) {
        o.remove();
    });
    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_tips_changer').each(function (o) {
        o.hide();
    });

    var tempObj = $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-left')[0];
    tempObj.writeAttribute("onclick", "showBlock('" + blockClass + "','0');");

    var tempHtml = $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML;
    var tempHtml2 = '<div class="block_visibility_changer collapseable" style="float: right; color: white; font-size: 11px; margin-left: 20px;">';
    tempHtml2 += '<a href="javascript:void(0);" onclick="showBlock(\'' + blockClass + '\',\'0\');" style="width: 20px; border: 0px;">&nbsp;</a>';
    tempHtml2 += '</div>';
    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML = tempHtml2 + tempHtml;

    setHashedStorage(blockClass);

    $$('div.' + blockClass + ' div.fieldset')[0].hide();
    $$('div.' + blockClass + ' div.entry-edit-head')[0].setStyle({
        marginBottom: '15px'
    });
    $$('div.' + blockClass + ' div.fieldset')[0].setStyle({
        marginBottom: '0px'
    });

    return true;
}

function showBlock(blockClass, init) {
    blockClass = blockClass || '';
    if (blockClass === '') {
        return false;
    }

    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_visibility_changer').each(function (o) {
        o.remove();
    });
    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right div.block_tips_changer').each(function (o) {
        o.show();
    });

    var tempObj = $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-left')[0];
    tempObj.writeAttribute("onclick", "hideBlock('" + blockClass + "','0');");

    var tempHtml = $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML;
    var tempHtml2 = '<div class="block_visibility_changer collapseable" style="float: right; color: white; font-size: 11px; margin-left: 20px;">';
    tempHtml2 += '<a href="javascript:void(0);" onclick="hideBlock(\'' + blockClass + '\',\'0\');" style="width: 20px; border: 0px;" class="open">&nbsp;</a>';
    tempHtml2 += '</div>';
    $$('div.' + blockClass)[0].select('div.entry-edit-head div.entry-edit-head-right')[0].innerHTML = tempHtml2 + tempHtml;

    deleteHashedStorage(blockClass);

    $$('div.' + blockClass + ' div.fieldset')[0].show();
    $$('div.' + blockClass + ' div.entry-edit-head')[0].setStyle({
        marginBottom: '0px'
    });
    $$('div.' + blockClass + ' div.fieldset')[0].setStyle({
        marginBottom: '15px'
    });

    return true;
}

function changeToolTipPosition(element) {

    var toolTip = element.up().select('.tooltip-message')[0];
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

    if (toolTip.hasClassName('tooltip-left')) {
        toolTip.style.left = (parseInt(toolTip.style.left) - toolTip.getWidth() - 10) + 'px';
    }
}

function onToolTipIconMouseLeave() {

    e2m.isHideTooltip = true;

    var element = this.up().select('.tooltip-message')[0];
    setTimeout(function () {
        e2m.isHideTooltip && element.hide();
    }, 1000);
}

function onToolTipMouseEnter() {
    e2m.isHideTooltip = false;
}

function onToolTipMouseLeave() {

    e2m.isHideTooltip = true;

    setTimeout(function () {
        e2m.isHideTooltip && this.hide();
    }, 1000);
}

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

function e2mHideBlock() {

    $$('div.entry-edit').each(function (blockObj) {

        if (blockObj.select('div.entry-edit-head').length === 0) {
            return;
        }

        if (blockObj.readAttribute('magento_block') === 'no') {
            return;
        }

        blockObj.select('div.entry-edit-head')[0].innerHTML = '<div class="entry-edit-head-left" style="float: left; width: 78%;">' + blockObj.select('div.entry-edit-head')[0].innerHTML + '</div>' + '<div class="entry-edit-head-right" style="float: right; width: 20%;"></div>';

        var tempCollapseable = blockObj.readAttribute('collapseable');
        if (typeof tempCollapseable === 'string' && tempCollapseable === 'no') {
            return;
        }

        var id = blockObj.readAttribute('id');
        if (typeof id !== 'string') {
            id = 'magento_block_md5_' + md5(blockObj.innerHTML.replace(/[^A-Za-z]/g, ''));
            blockObj.writeAttribute('id', id);
        }

        var blockClass = id + '_hide';
        blockObj.addClassName(blockClass);

        blockObj.select('div.entry-edit-head div.entry-edit-head-left')[0].setStyle({
            cursor: 'pointer'
        });

        var isClosed = getHashedStorage(blockClass);
        if (isClosed === '' || isClosed === '0') {
            showBlock(blockClass, '1');
        } else {
            hideBlock(blockClass, '1');
        }
    });
}
