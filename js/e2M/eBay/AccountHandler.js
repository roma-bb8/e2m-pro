EbayAccountHandler = Class.create();
EbayAccountHandler.prototype = {

    initialize: function() {},

    get_token: function()
    {
        if ($('token_session').value == '') {
            $('token_session').value = '0';
        }
        if ($('token_expired_date').value == '') {
            $('token_expired_date').value = '0';
        }
        this.submitForm(M2ePro.url.get('adminhtml_m2i/beforeGetToken', {'id': M2ePro.formData.id}));
    },

    submitForm: function(url, newWindow)
    {
        if (typeof newWindow == 'undefined') {
            newWindow = false;
        }

        var oldAction = $('edit_form').action;

        $('edit_form').action = url;
        $('edit_form').target = newWindow ? '_blank' : '_self';

        editForm.submit();

        $('edit_form').action = oldAction;
    }
};