//
//{block name="backend/create_backend_order/store/create_backend_order"}
//
Ext.define('Shopware.apps.SwagCreateBackendOrder.store.CreateBackendOrder', {

    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagCreateBackendOrder.model.CreateBackendOrder',

    remoteSort: true,

    remoteFilter: true,

    pageSize: 50,

    batch: true
});
//
//{/block}