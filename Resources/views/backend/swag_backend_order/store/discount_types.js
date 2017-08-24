//{block name="backend/create_backend_order/store/discount_types"}
//{namespace name="backend/swag_backend_order/store/discount_types"}
Ext.define('Shopware.apps.SwagBackendOrder.store.DiscountTypes', {
    extend: 'Ext.data.Store',

    fields: [
        { name: 'id', type: 'int' },
        { name: 'text', type: 'string' }
    ],

    data: [
        { id: 0, text: '{s name="percentage"}Percentage{/s}' },
        { id: 1, text: '{s name="absolute"}Absolute{/s}' }
    ]
});
//
//{/block}