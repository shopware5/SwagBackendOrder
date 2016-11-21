
/**
 * Shopware Controller - Customer list backend module
 *
 * Override to set window title and preset guest mode
 */
//
//{block name="backend/customer/view/detail/window" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.view.Window', {
    override: 'Shopware.apps.Customer.view.detail.Window',

    setWindowTitle: function () {
        var me = this;

        //set different titles for create and edit customers
        if (me.record.get('id')) {
            me.setTitle(me.snippets.titleEdit + ' ' + me.record.get('number'));
        } else if (me.record.get('guest')) {
            me.setTitle('{s namespace=backend/customer/view/main name=swag_backend_order/customer/window/create_guest_title}Customer administration - Create a guest{/s}');
        } else {
            me.setTitle(me.snippets.titleCreate);
        }
    },

    /*
     * Executed before view is rendered, so set guest mode params here
     */
    createTabPanel: function (stores) {
        var me = this;

        /**
         * checks if additional params was passed and sets the default guest email
         * if it is necessary
         */
        if (typeof me.subApplication.params != 'undefined') {
            if (me.subApplication.params.guest === true) {
                me.record.set('email', me.subApplication.params.email);
                me.record.set('guest', true);
                me.record.set('accountMode', 1);
                me.record.set('active', 1);
            }
        }

        me.callParent(arguments);
    }
});
//
//{/block}
//