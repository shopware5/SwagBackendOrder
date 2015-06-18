//
//{block name="backend/create_backend_order/store/position"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.Position', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagBackendOrder.model.Position',

    requires: [
        'Shopware.apps.SwagBackendOrder.model.Position'
    ],

    remoteSort: false,

    remoteFilter: false,

    pageSize: 50,

    batch: true
});
//
//{/block}