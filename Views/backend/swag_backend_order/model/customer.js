//
//{block name="backend/create_backend_order/model/customer"}
Ext.define('Shopware.apps.SwagBackendOrder.model.Customer', {

    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Shopware.data.Model',

    /**
     * The fields used for this model
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'email', type: 'string' },
        { name: 'languageId', type: 'int' },
        { name: 'shopId', type: 'int', useNull: true },
        { name: 'customerName', type: 'string' },
        { name: 'customerCompany', type: 'string' },
        { name: 'customerNumber', type: 'string' }
    ],

    /**
     * define an alternate class name as a second identifier
     */
    alternateClassName: 'SwagBackendOrder.model.Customer',

    /**
     * required models for this model
     */
    requires: [
        'Shopware.apps.SwagBackendOrder.model.Billing',
        'Shopware.apps.SwagBackendOrder.model.Shipping',
        'Shopware.apps.SwagBackendOrder.model.Debit',
        'Shopware.apps.Base.model.Shop'
    ],

    hasMany: [
        {
            name: 'billing',
            model: 'Shopware.apps.SwagBackendOrder.model.Billing',
            associationKey: 'billing'
        },
        {
            name: 'shipping',
            model: 'Shopware.apps.SwagBackendOrder.model.Shipping',
            associationKey: 'shipping'
        },
        {
            name: 'debit',
            model: 'Shopware.apps.SwagBackendOrder.model.Debit',
            associationKey: 'debit'
        },
        {
            name: 'shop',
            model: 'Shopware.apps.Base.model.Shop',
            associationKey: 'shop'
        },
        {
            name: 'languageSubShop',
            model: 'Shopware.apps.Base.model.Shop',
            associationKey: 'languageSubShop'
        }
    ]
});
//
//{/block}