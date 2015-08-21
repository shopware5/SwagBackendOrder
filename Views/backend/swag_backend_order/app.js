//
//{block name="backend/create_backend_order/app"}
//
Ext.define('Shopware.apps.SwagBackendOrder', {

    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.SwagBackendOrder',

    /**
     * Enable bulk loading
     * @boolean
     */
    bulkLoad: true,

    /**
     * Sets the loading path for the sub-application.
     *
     * @string
     */
    loadPath: '{url controller="SwagBackendOrder" action="load"}',

    controllers: ['Main'],

    views: [
        'main.Window',
        'main.CustomerSearch',
        'main.Toolbar',
        'main.TotalCostsOverview',

        /**
         * Customer Information panel for the shipping- & billingaddress and the payment informations
         */
        'main.CustomerInformation.Shipping',
        'main.CustomerInformation.Billing',
        'main.CustomerInformation.Payment',
        'main.CustomerInformation.CustomerInformation',

        /**
         * views which are hold by the middle container
         */
        'main.ShippingCosts',
        'main.AdditionalInformation',

        /**
         * Grid which shows, adds and deletes the actual positions of the order
         */
        'main.list.Positions',
        'main.list.Grid',
        'main.list.ArticleSearchField'
    ],

    stores: [
        'CreateBackendOrder',
        'DesktopTypes',

        'Customer',

        'Article',
        'Position',

        'ShippingCosts',
        'TotalCosts'
    ],

    models: [
        /**
         * main model which holds the basic informations to create an order
         */
        'CreateBackendOrder',
        'DesktopTypes',

        /**
         * customer models
         */
        'Billing',
        'Shipping',
        'Debit',
        'Customer',

        /**
         * article models
         */
        'Article',
        'Position',

        'ShippingCosts',
        'TotalCosts'
    ],

    launch: function () {
        var me = this,
            mainController = me.getController('Main');

        return mainController.mainWindow;
    }
});
//{/block}