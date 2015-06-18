//
//{block name="backend/create_backend_order/store/article"}
//
Ext.define('Shopware.apps.SwagBackendOrder.store.Article', {

    /**
     * extends from the standard ExtJs store class
     */
    extend: 'Ext.data.Store',

    /**
     * defines an alternate class name for an easier identification
     */
    alternateClassName: 'SwagBackendOrder.store.Article',

    /**
     * the model which belongs to the store
     */
    model: 'Shopware.apps.SwagBackendOrder.model.Article',

    remoteSort: true,

    remoteFilter: true,

    pageSize: 10,

    batch: true
});
//
//{/block}