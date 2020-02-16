function showBlock(blockClass, init) {
    blockClass = blockClass || '';
    if (blockClass == '') {
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