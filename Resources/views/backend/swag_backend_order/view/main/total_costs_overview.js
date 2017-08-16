//
//{block name="backend/create_backend_order/view/total_costs_overview"}
//{namespace name="backend/swag_backend_order/view/costs_overview"}
Ext.define('Shopware.apps.SwagBackendOrder.view.main.TotalCostsOverview', {

    extend: 'Ext.container.Container',

    alias: 'widget.createbackendorder-totalcostsoverview',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    name: 'totalCostsContainer',

    style: {
        float: 'left'
    },

    id: 'totalCostsContainer',

    flex: 1,

    autoScroll: true,

    padding: '5 10 0 10',

    snippets: {
        sum: '{s name="swag_backend_order/costs_overview/sum"}{/s}',
        shippingCosts: '{s name="swag_backend_order/costs_overview/shipping_costs"}{/s}',
        total: '{s name="swag_backend_order/costs_overview/total"}{/s}',
        totalWithoutTax: '{s name="swag_backend_order/costs_overview/total_without_tax"}{/s}',
        taxSum: '{s name="swag_backend_order/costs_overview/tax_sum"}{/s}'
    },

    /**
     * horizontal container to add the costs labels under the grid
     *
     * @returns { Ext.container.Container }
     */

    initComponent: function () {
        var me = this;

        me.getPluginConfig();

        me.currencyStore = me.subApplication.getStore('Currency');
        me.items = [
            me.createTotalCostsOverviewContainer()
        ];

        me.updateTotalCostsEvents();

        me.displayNetCheckbox.on('change', function (checkbox, newValue, oldValue) {
            me.taxFreeCheckbox.setDisabled(!!newValue);
            me.fireEvent('changeDisplayNet', newValue, oldValue);
        });

        me.sendMailCheckbox.on('change', function (checkbox, newValue, oldValue) {
            me.fireEvent('changeSendMail', newValue, oldValue);
        });

        me.taxFreeCheckbox.on('change', function (checkbox, newValue, oldValue) {
            me.displayNetCheckbox.setDisabled(newValue);
            me.fireEvent('changeTaxFreeCheckbox', newValue, oldValue);
        });

        me.callParent(arguments);

        //Firefox bugfix for get the correct currency symbol
        if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
            me.updateCurrency();
        }
    },

    /**
     * container with a vbox layout the get the labels in a vertical row
     *
     * @returns { Ext.container.Container }
     */
    createTotalCostsOverviewContainer: function () {
        var me = this;

        me.totalCostsLabelsView = Ext.create('Ext.view.View', {
            id: 'totalCostsLabelsView',
            name: 'totalCostsLabelsView',
            height: 100,
            tpl: me.createTotalLabelTemplate()
        });

        me.totalCostsView = Ext.create('Ext.view.View', {
            id: 'totalCostsView',
            name: 'totalCostsView',
            store: me.createTotalCostsStore(),
            height: 100,
            width: 85,
            tpl: me.createTotalCostsTemplate()
        });

        me.totalCostsFloatContainer = Ext.create('Ext.container.Container', {
            layout: {
                type: 'hbox',
                pack: 'end'
            },
            flex: 1,
            items: [
                me.totalCostsLabelsView,
                me.totalCostsView
            ]
        });

        me.totalCostsContainer = Ext.create('Ext.container.Container', {
            flex: 1,
            name: 'totalCostsContainer',
            layout: 'hbox',
            renderTo: document.body,
            items: [
                me.createLeftContainer(),
                me.totalCostsFloatContainer
            ]
        });

        return me.totalCostsContainer;
    },

    /**
     * @returns { Ext.XTemplate }
     */
    createTotalLabelTemplate: function () {
        var me = this;

        me.totalLabelTempalte = new Ext.XTemplate(
            '{literal}',
            '<div style="font-size: 13px;">',
            '<p>' + me.snippets.sum + '</p>',
            '<p>' + me.snippets.shippingCosts + '</p>',
            '<p><b>' + me.snippets.total + '</b></p>',
            '<p>' + me.snippets.totalWithoutTax + '</p>',
            '<p>' + me.snippets.taxSum + '</p>',
            '</div>',
            '{/literal}'
        );

        return me.totalLabelTempalte;
    },

    /**
     * @returns { Ext.XTemplate }
     */
    createTotalCostsTemplate: function () {
        var me = this;

        me.totalCostsTempalte = new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div style="padding-left: 10px; font-size: 13px; text-align: right;">',
            '<p>{sum} ' + me.currencySymbol + '</p>',
            '<p>{shippingCosts:this.shippingCosts} ' + me.currencySymbol + '</p>',
            '<p><b>{total} ' + me.currencySymbol + '</b></p>',
            '<p>{totalWithoutTax} ' + me.currencySymbol + '</p>',
            '<p>{taxSum} ' + me.currencySymbol + '</p>',
            '</div>',
            '</tpl>{/literal}',
            {
                shippingCosts: function (shippingCosts) {
                    if (me.displayNetCheckbox.getValue())
                        // Show net shipping costs if net order
                        return me.totalCostsModel.get('shippingCostsNet');

                    return shippingCosts;
                }
            }
        );

        return me.totalCostsTempalte;
    },

    /**
     * @returns { Shopware.apps.SwagBackendOrder.store.TotalCosts }
     */
    createTotalCostsStore: function () {
        var me = this;
        me.totalCostsModel = Ext.create('Shopware.apps.SwagBackendOrder.model.TotalCosts', {});

        me.totalCostsModel.set('totalWithoutTax', 0);
        me.totalCostsModel.set('sum', 0);
        me.totalCostsModel.set('total', 0);
        me.totalCostsModel.set('shippingCosts', 0);
        me.totalCostsModel.set('taxSum', 0);

        me.totalCostsStore = me.subApplication.getStore('TotalCosts');
        me.totalCostsStore.add(me.totalCostsModel);
        return me.totalCostsStore;
    },

    updateTotalCostsEvents: function () {
        var me = this;

        me.positionStore = me.subApplication.getStore('Position');

        me.positionStore.on('update', function (store, record, operation, modifiedFieldNames) {
            me.updateTotalCosts();
        });

        me.positionStore.on('remove', function () {
            me.fireEvent('calculateBasket');
            me.updateTotalCosts();
        });

        me.currencyStore.on('load', function () {
            me.updateCurrency();
        });

        me.currencyStore.on('update', function () {
            me.updateCurrency();
        });

        me.totalCostsStore.on('update', function (store, record, operation, modifiedFieldNames) {
            me.updateTotalCosts();
        });
    },

    updateTotalCosts: function () {
        var me = this;

        me.remove('totalCostsContainer', true);
        me.totalCostsView.bindStore(me.totalCostsStore);
        me.add(me.totalCostsContainer);
        me.doLayout();
    },

    updateCurrency: function () {
        var me = this,
            currencyIndex, currencyModel;

        currencyIndex = me.currencyStore.findExact('selected', 1);
        currencyModel = me.currencyStore.getAt(currencyIndex);

        if (typeof currencyModel !== "undefined") {
            me.currencySymbol = currencyModel.get('symbol');
            me.totalCostsView.tpl = me.createTotalCostsTemplate();
            me.updateTotalCosts();
        }
    },

    /**
     * @returns { Ext.form.field.Checkbox }
     */
    createDisplayNetCheckbox: function () {
        var me = this;

        me.displayNetCheckbox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: '{s name="display_net"}{/s}',
            inputValue: true,
            uncheckedValue: false,
            padding: '0 5 0 0'
        });

        return me.displayNetCheckbox;
    },

    createSendMailCheckbox: function () {
        var me = this;

        me.sendMailCheckbox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: '{s name="send_mail"}{/s}',
            inputValue: true,
            uncheckedValue: false,
            padding: '0 5 0 0'
        });

        return me.sendMailCheckbox;
    },

    /**
     * @returns { Ext.container.Container }
     */
    createLeftContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'vbox',
            items: [
                me.createDisplayNetCheckbox(),
                me.createTaxFreeCheckbox(),
                me.createSendMailCheckbox()
            ]
        });
    },

    /**
     * @returns { Ext.form.field.Checkbox }
     */
    createTaxFreeCheckbox: function () {
        var me = this;

        me.taxFreeCheckbox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: '{s name="tax_free"}{/s}',
            inputValue: true,
            uncheckedValue: false,
            padding: '0 5 0 0'
        });

        return me.taxFreeCheckbox;
    },

    /**
     * reads the plugin configuration
     */
    getPluginConfig: function() {
        var me = this;

        Ext.Ajax.request({
            url: '{url action=getPluginConfig}',
            success: function(response) {
                var pluginConfigObj = Ext.decode(response.responseText);

                me.sendMail = pluginConfigObj.data.sendMail;
                if (me.sendMail == 1) {
                    me.orderModel.set('sendMail', 1);
                    me.sendMailCheckbox.setValue(true);
                }
            }
        });
    },
});
//
//{/block}