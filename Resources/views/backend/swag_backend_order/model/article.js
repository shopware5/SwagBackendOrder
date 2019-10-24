// {block name="backend/create_backend_order/model/article"}
Ext.define('Shopware.apps.SwagBackendOrder.model.Article', {

    extend: 'Ext.data.Model',

    alternateClassName: 'SwagBackendOrder.model.Article',

    /**
     * fields which represent a product row from it's doctrine model
     * name field is for the live search to show the product name + variant text
     */
    fields: [
        { name: 'id', type: 'int' },
        { name: 'additionalText', type: 'string' },
        { name: 'articleName', mapping: 'name', type: 'string' },
        { name: 'name', type: 'string' },
        { name: 'articleId', type: 'int' },
        { name: 'taxId', type: 'int' },
        { name: 'inStock', type: 'int' },
        { name: 'number', type: 'string' },
        { name: 'tax', type: 'int' },
        { name: 'price', type: 'float' },
        { name: 'description', type: 'string' },
        { name: 'supplierName', type: 'string' },
        { name: 'active', type: 'int' },
        { name: 'articleActive', type: 'int' },
        { name: 'variantActive', type: 'int' },
        { name: 'blockPrices', type: 'string' },
        { name: 'ean', type: 'string' }
    ],

    proxy: {
        type: 'ajax',

        api: {
            read: '{url action="getArticles"}'
        },

        reader: {
            type: 'json',
            root: 'data',
            totalProperty: 'total'
        },

        actionMethods: {
            read: 'POST'
        }
    }
});
// {/block}
