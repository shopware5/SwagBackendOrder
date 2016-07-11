//
//{block name="backend/create_backend_order/view/customer_information/billing"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Billing', {

    /**
     * extends from the extjs standard panel component
     */
    extend: 'Ext.panel.Panel',

    alias: 'widget.createbackendorder-customer-billing',

    /**
     * alternate class name as a second identifier
     */
    alternateClassName: 'SwagBackendOrder.view.main.CustomerInformation.Billing',

    bodyPadding: 10,

    flex: 1,

    autoScroll: true,

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/billing/title"}Billing address{/s}',
        salutation: {
            mister: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/salutation/mister"}Mr{/s}',
            miss: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/salutation/miss"}Ms{/s}'
        }
    },

    paddingRight: 5,

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;

        /**
         * gets the customer store and loads the selected customer
         */
        me.customerStore = me.subApplication.getStore('Customer');
        me.customerStore.on('load', function () {
            if (Ext.isObject(me.customerStore) && me.customerStore.count() == 1) {
                me.billingStore = me.customerStore.getAt(0).billing();
                me.billingAddressComboBox.bindStore(me.billingStore);

                me.resetFields();
            }
        });

        me.items = me.createBillingItems();

        me.callParent(arguments);
    },

    /**
     * register events
     */
    registerEvents: function () {
        this.addEvents(
            'selectBillingAddress'
        );
    },

    /**
     * creates the billing combobox and the data view for the selected address
     */
    createBillingItems: function () {
        var me = this;

        me.billingAddressComboBox = Ext.create('Ext.form.field.ComboBox', {
            name: 'billingAddresses',
            queryMode: 'local',
            height: 35,
            store: me.billingStore,
            displayField: 'displayField',
            valueField: 'displayField',
            allowBlank: false,
            tpl: me.createBillingAddressComboTpl(),
            anchor: '100%',
            listConfig: {
                maxHeight: 200
            },
            listeners: {
                'select': function (comboBox, record) {
                    me.fireEvent('selectBillingAddress', record, me);

                    var billingAddressTemplateStore = Ext.create('Ext.data.Store', {
                        model: 'Shopware.apps.SwagBackendOrder.model.Billing',
                        data: record[0].data
                    });

                    me.dataView = Ext.create('Ext.view.View', {
                        id: 'billingDataView',
                        name: 'billingDataView',
                        store: billingAddressTemplateStore,
                        tpl: me.createBillingTemplate(),
                        layout: 'fit'
                    });

                    me.remove('billingDataView', true);
                    me.add(me.dataView);
                    me.doLayout();

                }
            }
        });

        return [me.billingAddressComboBox];
    },

    /**
     * returns the template for the billing combox (display field)
     *
     * @returns [Ext.XTemplate]
     */
    createBillingAddressComboTpl: function () {
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

    /**
     * returns the XTemplate for the data view which shows the billing address
     *
     * @returns [Ext.XTemplate|*]
     */
    createBillingTemplate: function () {
        var me = this;

        me.billingTemplate = new Ext.XTemplate(
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
            '<span>{additionalAddressLine2}</span>',
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

        return me.billingTemplate;
    },

    resetFields: function () {
        var me = this;

        me.billingAddressComboBox.setValue('');
        me.remove('billingDataView', true);
        me.doLayout();
    }
});
//
//{/block}