/**
 * models which holds the current order with the necessary information
 */
// {block name="backend/create_backend_order/model/create_backend_order"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.CreateBackendOrder', {
    /**
     * Extends the Ext.data.Model
     */
    extend: 'Ext.data.Model',

    requires: [
        'Shopware.apps.SwagBackendOrder.model.Position',
        'Shopware.apps.SwagBackendOrder.model.OrderAttribute'
    ],

    fields: [
        { name: 'customerId', type: 'float' },
        { name: 'billingAddressId', type: 'int' },
        { name: 'shippingAddressId', type: 'int' },
        { name: 'shippingCosts', type: 'float' },
        { name: 'shippingCostsNet', type: 'float' },
        { name: 'shippingCostsTaxRate', type: 'float' },
        { name: 'paymentId', type: 'int' },
        { name: 'dispatchId', type: 'int' },
        { name: 'languageShopId', type: 'int' },
        { name: 'currency', type: 'string' },
        { name: 'total', type: 'float' },
        { name: 'totalWithoutTax', type: 'float' },
        { name: 'currencyId', type: 'string' },
        { name: 'desktopType', type: 'string' },
        { name: 'displayNet', type: 'boolean' },
        { name: 'sendMail', type: 'boolean'},
        { name: 'taxFree', type: 'boolean' }
    ],

    hasMany: [
        {
            name: 'position',
            model: 'Shopware.apps.SwagBackendOrder.model.Position',
            associationKey: 'position'
        },
        {
            name: 'orderAttribute',
            model: 'Shopware.apps.SwagBackendOrder.model.OrderAttribute',
            associationKey: 'orderAttribute'
        }
    ],

    proxy: {
        type: 'ajax',

        api: {
            create: '{url action="createOrder"}'
        },

        writer: {
            type: 'json',
            root: 'data'
        }
    }
});
//
// {/block}
