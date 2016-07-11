//{block name="backend/create_backend_order/model/currency"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.Currency', {

    /**
     * Defines an alternate name for this class.
     */

    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Shopware.data.Model',

    /**
     * unique id
     * @int
     */
    idProperty: 'id',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' },
        { name: 'symbol', type: 'string' },
        { name: 'currency', type: 'string' },
        { name: 'factor', type: 'float' },
        { name: 'default', type: 'int' },
        //if currency is selected -> true
        { name: 'selected', type: 'int', defaultValue: 0 }
    ]
});
//
//{/block}

