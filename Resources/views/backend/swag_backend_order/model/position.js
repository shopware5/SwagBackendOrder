//
// {block name="backend/create_backend_order/model/position"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.Position', {

    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Model',

    /**
     * alternate class name to identify this model
     */
    alternateClassName: 'SwagBackendOrder.model.Position',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'create_backend_order_id', type: 'int' },
        { name: 'mode', type: 'int' },
        { name: 'articleId', type: 'int' },
        { name: 'detailId', type: 'int' },
        { name: 'articleNumber', type: 'string' },
        { name: 'articleName', type: 'string' },
        { name: 'quantity', type: 'int' },
        { name: 'statusId', type: 'int' },
        { name: 'statusDescription', type: 'string' },
        {
            name: 'price',
            type: 'float',
            convert: function(v) {
                var value = parseFloat(v);
                if (typeof value !== 'number') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        { name: 'taxId', type: 'int' },
        { name: 'taxRate', type: 'float' },
        { name: 'taxDescription', type: 'string' },
        { name: 'inStock', type: 'int' },
        {
            name: 'total',
            type: 'float',
            convert: function(v) {
                var value = parseFloat(v);
                if (typeof value !== 'number') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        { name: 'isDiscount', type: 'bool' },
        { name: 'discountType', type: 'int' },
        { name: 'ean', type: 'string' }
    ]
});
//
// {/block}
