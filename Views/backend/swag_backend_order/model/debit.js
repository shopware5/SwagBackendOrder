//
//{block name="backend/create_backend_order/model/debit"}
Ext.define('Shopware.apps.SwagBackendOrder.model.Debit', {
    /**
     * extends from the shopware base model for a customer
     */
    extend: 'Ext.data.Model',

    /**
     * define an alternate class name as a second identifier
     */
    alternateClassName: 'SwagBackendOrder.model.Debit',

    /**
     * fields which represent the debit doctrine model
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'account', type: 'string' },
        { name: 'bankCode', type: 'string' },
        { name: 'bankName', type: 'string' },
        { name: 'accountHolder', type: 'string' }
    ]
});
//
//{/block}
