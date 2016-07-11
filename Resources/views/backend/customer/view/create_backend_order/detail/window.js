//
//{block name="backend/customer/view/detail/window" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.view.Window', {
    override: 'Shopware.apps.Customer.view.detail.Window',

    setWindowTitle: function () {
        var me = this;

        //set different titles for create and edit customers
        if (me.record.get('id')) {
            me.setTitle(me.snippets.titleEdit + ' ' + me.record.getBilling().getAt(0).get('number'));
        } else if (me.record.get('guest')) {
            me.setTitle('{s namespace=backend/customer/view/main name=swag_backend_order/customer/window/create_guest_title}Customer administration - Create a guest{/s}');
        } else {
            me.setTitle(me.snippets.titleCreate);
        }
    }
});
//
//{/block}
//