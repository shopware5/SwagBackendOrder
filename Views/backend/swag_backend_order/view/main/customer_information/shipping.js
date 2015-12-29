//
//{block name="backend/create_backend_order/view/customer_information/shipping"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Shipping', {

    extend: 'Ext.panel.Panel',

    alternateClassName: 'SwagBackendOrder.view.main.CustomerInformation.Shipping',

    alias: 'widget.createbackendorder-customer-shipping',

    bodyPadding: 10,

    flex: 1,

    autoScroll: true,

    paddingRight: 5,

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/shipping/title"}Payment{/s}',
        billingAsShipping: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/shipping/billing_as_shipping"}Use billing address{/s}',
        salutation: {
            mister: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/salutation/mister"}Mr{/s}',
            miss: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/salutation/miss"}Ms{/s}'
        }
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;

        /**
         * gets the customer store and loads the selected customer
         */
        me.customerStore = me.subApplication.getStore('Customer');
        me.customerStore.on('load', function () {
            if (Ext.isObject(me.customerStore) && me.customerStore.count() == 1) {
                me.shippingStore = me.customerStore.getAt(0).shipping();
                me.shippingAddressComboBox.bindStore(me.shippingStore);
            }

            me.resetFields();
        });

        me.items = me.createShippingContainer();

        me.billingAsShippingCheckbox.on('change', function () {
            if (me.billingAsShippingCheckbox.getValue()) {
                me.shippingAddressComboBox.setValue('');
                me.remove('shippingDataView', true);
                me.doLayout();

                me.fireEvent('selectBillingAsShippingAddress');
            }
        });

        me.callParent(arguments);
    },

    registerEvents: function () {
        this.addEvents(
            'selectShippingAddress'
        );
    },

    createShippingContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            items: me.createShippingItems()
        });
    },

    createShippingItems: function () {
        var me = this;

        /**
         * @TODO: renderer for the display field, correct value field
         */
        me.shippingAddressComboBox = Ext.create('Ext.form.field.ComboBox', {
            name: 'shippingAddresses',
            queryMode: 'local',
            store: me.shippingStore,
            flex: 1,
            disabled: true,
            displayField: 'displayField',
            valueField: 'displayField',
            listConfig: {
                maxHeight: 200
            },
            tpl: me.createShippingAddressComboTpl(),
            listeners: {
                'change': function (comboBox, value) {
                    var record = this.findRecordByValue(value);

                    me.fireEvent(
                        'selectShippingAddress', record
                    );

                    if (record === false) {
                        // Do nothing if there is no corresponding record.
                        return;
                    }

                    me.billingAsShippingCheckbox.setValue(false);

                    var shippingAddressTemplateStore = Ext.create('Ext.data.Store', {
                        model: 'Shopware.apps.SwagBackendOrder.model.Shipping',
                        data: record.data
                    });

                    me.dataView = Ext.create('Ext.view.View', {
                        id: 'shippingDataView',
                        name: 'shippingDataView',
                        store: shippingAddressTemplateStore,
                        tpl: me.createShippingTemplate(),
                        layout: 'fit',
                        padding: '5 0 0 0'
                    });

                    me.remove('shippingDataView', true);
                    me.add(me.dataView);
                    me.doLayout();
                }
            }
        });

        me.billingAsShippingCheckbox = Ext.create('Ext.form.field.Checkbox', {
            flex: 1,
            boxLabel: me.snippets.billingAsShipping,
            name: 'billingAsShipping',
            id: 'billingAsShippingCheckBox',
            inputValue: true,
            uncheckedValue: false,
            checked: true,
            height: 35,
            listeners: {
                change: function (field, value) {
                    if (value) {
                        me.shippingAddressComboBox.disable();
                    } else {
                        me.shippingAddressComboBox.enable();
                        me.shippingAddressComboBox.setValue(me.shippingStore.getAt(0).get(me.shippingAddressComboBox.valueField));
                    }
                }
            }
        });

        return [me.shippingAddressComboBox, me.billingAsShippingCheckbox];
    },

    createShippingAddressComboTpl: function () {
        var me = this;

        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div class= "x-combo-list-item x-boundlist-item">',
            '<tpl if="company">',
            '{company},<br/>',
            '</tpl>',
            '<tpl switch="salutation">',
            '<tpl case="mr">',
            me.snippets.salutation.mister + ' ',
            '<tpl case="ms">',
            me.snippets.salutation.miss + ' ',
            '</tpl>',
            '{firstName} {lastName},<br/>{zipCode} {city},<br/>{street}',
            '<tpl if="state">',
            ',<br/>{state}',
            '</tpl>',
            '<tpl if="country">',
            ',<br/>{country}',
            '</tpl>',
            '</div>',
            '</tpl>{/literal}'
        );
    },

    createShippingTemplate: function () {
        var me = this;

        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div class="customeer-info-pnl">',
            '<div class="base-info">',
            '<p>',
            '<span>{company}</span>',
            '</p>',
            '<p>',
            '<tpl switch="salutation">',
            '<tpl case="mr">',
            me.snippets.salutation.mister + ' ',
            '<tpl case="ms">',
            me.snippets.salutation.miss + ' ',
            '</tpl>',
            '<span>{firstName}</span>&nbsp;',
            '<span>{lastName}</span>',
            '</p>',
            '<p>',
            '<span>{street}</span>',
            '</p>',
            '<tpl if="additionalAddressLine1">',
            '<p>',
            '<span>{additionalAddressLine1}</span>',
            '</p>',
            '</tpl>',
            '<tpl if="additionalAddressLine2">',
            '<p>',
            '<span>{additionalAddressLine1}</span>',
            '</p>',
            '</tpl>',
            '<p>',
            '<span>{zipCode}</span>&nbsp;',
            '<span>{city}</span>',
            '</p>',
            '<p>',
            '<span>{state}</span>',
            '</p>',
            '<p>',
            '<span>{country}</span>',
            '</p>',
            '</div>',
            '</div>',
            '</tpl>{/literal}'
        );
    },

    resetFields: function () {
        var me = this;

        me.shippingAddressComboBox.setValue('');
        me.remove('shippingDataView', true);
        me.doLayout();
    }
});
//
//{/block}