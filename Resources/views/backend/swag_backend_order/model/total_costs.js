/**
 * Model which holds the total costs for the data view
 */
// {block name="backend/create_backend_order/model/total_costs"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.TotalCosts', {

    /**
     * extends from the standard ExtJs Model
     */
    extend: 'Ext.data.Model',

    /**
     * convert functions to get '0.00' digits
     */
    fields: [
        {
            name: 'total',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'sum',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'totalWithoutTax',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'shippingCosts',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'shippingCostsNet',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'shippingCostsTaxRate',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    return 0;
                }
                return value;
            }
        },
        {
            name: 'taxSum',
            type: 'float',
            convert: function (v) {
                var value = v;
                if (value === '') {
                    value = 0.0;
                }
                return value.toFixed(2);
            }
        },
        {
            name: 'proportionalTaxCalculation',
            type: 'bool'
        },
        {
            name: 'taxes',
            type: 'array'
        }
    ]
});
//
// {/block}
