//
//{block name="backend/create_backend_order/store/article"}
//
Ext.define('Shopware.apps.SwagCreateBackendOrder.store.Article', {

    /**
     * extends from the standard ExtJs store class
     */
    extend: 'Ext.data.Store',

    /**
     * defines an alternate class name for an easier identification
     */
    alternateClassName: 'SwagCreateBackendOrder.store.Article',

    /**
     * the model which belongs to the store
     */
    model: 'Shopware.apps.SwagCreateBackendOrder.model.Article',

    remoteSort: true,

    remoteFilter: true,

    pageSize: 10,

    batch: true
});
//
//{/block}