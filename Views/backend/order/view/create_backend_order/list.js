//{block name="backend/order/view/list/list" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.view.List', {
    override: 'Shopware.apps.Order.view.list.List',

    getToolbar: function () {
        var me = this,
            toolbar = me.callParent(arguments),
            btn;

        /*{if {acl_is_allowed privilege=perform_order}}*/
        btn = Ext.create('Ext.button.Button', {
            text: '{s namespace="backend/order/view/main" name="list/create_button"}Create Order{/s}',
            iconCls: 'sprite-plus-circle-frame',
            handler: function () {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.SwagBackendOrder'
                });
            }
        });

        toolbar.insert(1, btn);
        /*{/if}*/

        return toolbar;
    }
});

//{/block}