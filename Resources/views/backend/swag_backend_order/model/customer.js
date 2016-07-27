//
//{block name="backend/create_backend_order/model/customer"}
Ext.define('Shopware.apps.SwagBackendOrder.model.Customer', {

    /**
     * @string
     */
    extend: 'Shopware.data.Model',

    /**
     * @array
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'email', type: 'string' },
        { name: 'languageId', type: 'int' },
        { name: 'shopId', type: 'int', useNull: true },
        { name: 'firstname', type: 'string' },
        { name: 'lastname', type: 'string' },
        { name: 'number', type: 'string' },
        { name: 'company', type: 'string' },
        { name: 'city', type: 'string' },
        { name: 'zipCode', type: 'string' }
    ],

    alternateClassName: 'SwagBackendOrder.model.Customer',

    requires: [
        'Shopware.apps.Base.model.Shop'
    ],

    hasMany: [
        {
            name: 'billing',
            model: 'Shopware.apps.SwagBackendOrder.model.Address',
            associationKey: 'address'
        },
        {
            name: 'shipping',
            model: 'Shopware.apps.SwagBackendOrder.model.Address',
            associationKey: 'address'
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
        },
        {
            name: 'customerGroup',
            model: 'Shopware.apps.Base.model.CustomerGroup',
            associationKey: 'group'
        }
    ]
});
//
//{/block}