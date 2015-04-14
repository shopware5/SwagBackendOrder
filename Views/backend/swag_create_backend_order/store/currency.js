//
//{block name="backend/create_backend_order/store/currency"}
//
Ext.define('Shopware.apps.SwagCreateBackendOrder.store.Currency', {
    extend: 'Ext.data.Store',

    model : 'Shopware.apps.SwagCreateBackendOrder.model.Currency',

    proxy: {
        type: 'ajax',
        url: '{url action=getCurrencies}',
        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        }
    }
});
//
//{/block}
