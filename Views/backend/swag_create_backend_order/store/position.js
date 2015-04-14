//
//{block name="backend/create_backend_order/store/position"}
//
Ext.define('Shopware.apps.SwagCreateBackendOrder.store.Position', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagCreateBackendOrder.model.Position',

    requires: [
        'Shopware.apps.SwagCreateBackendOrder.model.Position'
    ],

    remoteSort: false,

    remoteFilter: false,

    pageSize: 50,

    batch: true
});
//
//{/block}