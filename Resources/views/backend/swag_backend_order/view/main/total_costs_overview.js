//
//{block name="backend/create_backend_order/view/total_costs_overview"}
//
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
        sum: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/sum"}Sum: {/s}',
        shippingCosts: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/shipping_costs"}Shipping costs: {/s}',
        total: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/total"}Total: {/s}',
        totalWithoutTax: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/total_without_tax"}Total without tax: {/s}',
        taxSum: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/tax_sum"}Tax sum: {/s}',
        netOrder: '{s namespace="backend/swag_backend_order/view/costs_overview" name="swag_backend_order/costs_overview/net_order"}Net order{/s}'
    },

    /**
     * horizontal container to add the costs labels under the grid
     *
     * @returns [Ext.container.Container]
     */

    initComponent: function () {
        var me = this;

        me.currencyStore = me.subApplication.getStore('Currency');
        me.items = [
            me.createTotalCostsOverviewContainer()
        ];

        me.updateTotalCostsEvents();

        me.netCheckBox.on('change', function (netCheckbox, newValue, oldValue) {
            me.fireEvent('changeNetCheckbox', newValue);
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
     * @returns [Ext.container.Container]
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
                me.createNetCheckbox(),
                me.totalCostsFloatContainer
            ]
        });

        return me.totalCostsContainer;
    },

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

    createTotalCostsTemplate: function () {
        var me = this;

        me.totalCostsTempalte = new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div style="padding-left: 10px; font-size: 13px; text-align: right;">',
            '<p>{sum} ' + me.currencySymbol + '</p>',
            '<p>{shippingCosts} ' + me.currencySymbol + '</p>',
            '<p><b>{total} ' + me.currencySymbol + '</b></p>',
            '<p>{totalWithoutTax} ' + me.currencySymbol + '</p>',
            '<p>{taxSum} ' + me.currencySymbol + '</p>',
            '</div>',
            '</tpl>{/literal}'
        );

        return me.totalCostsTempalte;
    },

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
            if (modifiedFieldNames[0] == 'price' || modifiedFieldNames[0] == 'total' || modifiedFieldNames[0] == 'taxRate') {
                me.fireEvent('calculateTax');
                me.updateTotalCosts();
            }
        });

        me.positionStore.on('remove', function () {
            me.fireEvent('calculateTax');
            me.updateTotalCosts();
        });

        me.currencyStore.on('load', function () {
            me.updateCurrency();
        });

        me.currencyStore.on('update', function () {
            me.updateCurrency();
        });

        me.totalCostsStore.on('update', function (store, record, operation, modifiedFieldNames) {
            if (modifiedFieldNames[0] == 'shippingCosts') {
                me.fireEvent('calculateTax');
            }
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
        var me = this;

        var currencyIndex = me.currencyStore.findExact('selected', 1);
        var currencyModel = me.currencyStore.getAt(currencyIndex);

        if (typeof currencyModel !== "undefined") {
            me.currencySymbol = currencyModel.get('symbol');
            me.totalCostsView.tpl = me.createTotalCostsTemplate();
            me.updateTotalCosts();
        }
    },

    createNetCheckbox: function () {
        var me = this;

        me.netCheckBox = Ext.create('Ext.form.field.Checkbox', {
            boxLabel: me.snippets.netOrder,
            inputValue: true,
            uncheckedValue: false,
            padding: '0 5 0 0'
        });

        return me.netCheckBox;
    }
});
//
//{/block}