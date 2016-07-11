//
//{block name="backend/create_backend_order/store/payment"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.Payment', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Store',

    model: 'Shopware.apps.SwagBackendOrder.model.Payment',

    /**
     * define an alternate class name for an easier identification
     */
    alternateClassName: 'SwagBackendOrder.store.Payment',

    remoteSort: true,

    remoteFilter: true,

    pageSize: 50,

    batch: true,

    proxy: {
        type: 'ajax',

        api: {
            read: '{url action="getPayment"}'
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