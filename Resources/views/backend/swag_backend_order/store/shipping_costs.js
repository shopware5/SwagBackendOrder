//
//{block name="backend/create_backend_order/store/shipping_costs"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.ShippingCosts', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagBackendOrder.model.ShippingCosts',

    proxy: {
        type: 'ajax',

        api: {
            read: '{url action="getShippingCosts"}'
        },

        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
//
//{/block}