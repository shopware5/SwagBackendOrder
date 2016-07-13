//
//{block name="backend/create_backend_order/store/customer"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.Customer', {

    /**
     * extends from the standard ExtJs Ext.data.Store
     */
    extend: 'Ext.data.Store',

    /**
     * defines the model where the store belongs to
     */
    model: 'Shopware.apps.SwagBackendOrder.model.Customer',

    remoteSort: true,

    remoteFilter: true,

    pageSize: 50,

    batch: true,

    proxy: {
        type: 'ajax',

        api: {
            read: '{url action="getCustomer"}'
        },

        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        },

        actionMethods: {
            read: 'POST'
        }
    }
});
//
//{/block}