//{block name="backend/create_backend_order/model/payment"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.Payment', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Model',

    /**
     * define an alternate class name for an easier identification
     */
    alternateClassName: 'SwagBackendOrder.model.Payment',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        //{block name="backend/base/model/payment/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' },
        { name: 'description', type: 'string' },
        { name: 'position', type: 'int' },
        { name: 'active', type: 'boolean' }
    ]
});
//
//{/block}