(function () {
    'use strict';

    Event.observe(window, 'load', function () {
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
    }, false);
})();
