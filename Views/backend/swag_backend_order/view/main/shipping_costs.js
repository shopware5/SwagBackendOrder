//
//{block name="backend/create_backend_order/view/shipping_costs"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.ShippingCosts', {

    extend: 'Ext.panel.Panel',

    alternateClassName: 'SwagBackendOrder.view.main.ShippingCosts',

    alias: 'widget.createbackendorder-shippingcosts',

    flex: 1,

    layout: 'hbox',

    margin: '15 0 0 10',

    autoScroll: true,

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/shipping_costs" name="swagbackendorder/shipping_costs/title"}Shipping costs{/s}',
        fields: {
            art: '{s namespace="backend/swag_backend_order/view/shipping_costs" name="swagbackendorder/shipping_costs/fields/art"}Shipping art{/s}',
            costs: '{s namespace="backend/swag_backend_order/view/shipping_costs" name="swagbackendorder/shipping_costs/fields/costs"}Shipping costs{/s}',
            costsNet: '{s namespace="backend/swag_backend_order/view/shipping_costs" name="swagbackendorder/shipping_costs/fields/costs_net"}Shipping costs net{/s}'
        }
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;
        me.items = me.createShippingCostsItems();
        me.createShippingCostsEvents();

        /**
         * sets the new shipping costs if an position was added
         */
        me.totalCostsStore = me.subApplication.getStore('TotalCosts');
        me.totalCostsStore.on('update', function (store, record) {
            me.shippingCostsNetNumber.setValue(record.data.shippingCostsNet);
        });

        me.callParent(arguments);
    },

    /**
     * Defines additional events which will be
     * fired from the component
     *
     * @return void
     */
    registerEvents: function () {
        this.addEvents(
            'addShippingCosts'
        );
    },

    createShippingCostsItems: function () {
        var me = this;

        return [me.createShippingCostsContainerLeft(), me.createShippingCostsContainerRight()];
    },

    /**
     * creates the left shipping container which holds shipping costs combo box
     *
     * @returns [Ext.container.Container]
     */
    createShippingCostsContainerLeft: function () {
        var me = this;

        var shippingArt = Ext.create('Ext.form.field.ComboBox', {
            name: 'shipping',
            width: 250,
            queryMode: 'local',
            store: me.shippingCostsStore,
            displayField: 'name',
            valueField: 'id',
            allowBlank: false,
            fieldLabel: me.snippets.fields.art,
            listeners: {
                select: function (combo, records, eOpts) {
                    me.shippingCostsNumber.setValue(records[0].data.value);

                    if (typeof me.totalCostsStore.getAt(0) === 'undefined') {
                        me.shippingCostsNetNumber.setValue(0);
                    }

                    var shippingFieldsArray = [me.shippingCostsNumber, me.shippingCostsNetNumber];

                    var shippingCosts = me.shippingCostsNumber.getValue(),
                        shippingCostsNet = me.shippingCostsNetNumber.getValue();
                    me.dispatchId = records[0].data.id;

                    me.fireEvent('addShippingCosts', shippingCosts, shippingCostsNet, me.dispatchId, shippingFieldsArray);
                    me.fireEvent('calculateTax');
                }
            }
        });

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            flex: 3,
            width: '50%',
            title: 'left',
            padding: '10 0 0 10',
            autoHeight: true,
            items: [
                shippingArt
            ]
        });
    },

    /**
     * creates the shipping container which holds the shipping costs
     *
     * @returns [Ext.container.Container]
     */
    createShippingCostsContainerRight: function () {
        var me = this;

        me.shippingCostsNumber = Ext.create('Ext.form.field.Number', {
            name: 'shippingCosts',
            width: 170,
            fieldLabel: me.snippets.fields.costs
        });

        me.shippingCostsNetNumber = Ext.create('Ext.form.field.Number', {
            name: 'shippingCostsNet',
            width: 170,
            fieldLabel: me.snippets.fields.costsNet,
            readOnly: true
        });

        var shippingCostsContainer = Ext.create('Ext.Container', {
            name: 'shippingCostsContainer',
            width: 75,
            height: 'auto',
            items: [
                me.shippingCostsNumber,
                me.shippingCostsNetNumber
            ]
        });

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            flex: 2,
            width: '50%',
            title: 'right',
            padding: '10 0 0 10',
            autoHeight: true,
            items: [
                shippingCostsContainer
            ]
        });
    },

    createShippingCostsEvents: function () {
        var me = this;

        me.shippingCosts = me.shippingCostsNumber.getValue();
        me.shippingCostsNet = me.shippingCostsNetNumber.getValue();

        me.shippingCostsNumber.on('change', function (numberField, newValue, oldValue) {
            me.shippingCostsNet = me.orderModel.get('shippingCostsNet');
            me.fireEvent('addShippingCosts', newValue, me.shippingCostsNet, me.dispatchId, undefined);
        });
    }
});
//
//{/block}