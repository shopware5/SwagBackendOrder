//
//{block name="backend/create_backend_order/store/order_attribute"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.OrderAttribute', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagBackendOrder.model.OrderAttribute',

    requires: [
        'Shopware.apps.SwagBackendOrder.model.OrderAttribute'
    ],

    remoteSort: true,

    remoteFilter: true,

    pageSize: 50,

    batch: true
});
//
//{/block}