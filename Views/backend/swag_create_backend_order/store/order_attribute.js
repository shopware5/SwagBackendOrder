//
//{block name="backend/create_backend_order/store/order_attribute"}
//
Ext.define('Shopware.apps.SwagCreateBackendOrder.store.OrderAttribute', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagCreateBackendOrder.model.OrderAttribute',

    requires: [
        'Shopware.apps.SwagCreateBackendOrder.model.OrderAttribute'
    ],

    remoteSort: true,

    remoteFilter: true,

    pageSize: 50,

    batch: true
});
//
//{/block}