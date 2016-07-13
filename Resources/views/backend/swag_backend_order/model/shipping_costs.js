//{block name="backend/create_backend_order/model/shipping_costs"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.ShippingCosts', {

    extend: 'Ext.data.Model',

    fields: [
        { name: 'id', mapping: 'dispatch.id', type: 'int' },
        { name: 'value', type: 'float' },
        { name: 'name', mapping: 'dispatch.name', type: 'string' }
    ]
});
//{/block}