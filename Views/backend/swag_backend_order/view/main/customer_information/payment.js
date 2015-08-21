//
//{block name="backend/create_backend_order/view/customer_information/payment"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.CustomerInformation.Payment', {

    /**
     * extends from the standard ExtJs panel component
     */
    extend: 'Ext.panel.Panel',

    /**
     * defines an alternate classname
     */
    alternateClassName: 'SwagBackendOrder.view.main.CustomerInformation.Payment',

    alias: 'widget.createbackendorder-customer-payment',

    bodyPadding: 10,

    flex: 1,

    autoScroll: true,

    paddingRight: 5,

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/title"}Shipping address{/s}',
        paymentData: {
            accountHolder: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/account_holder"}Account holder:{/s}',
            bankCode: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/bank_code"}Bank code:{/s}',
            accountNumber: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/account_number"}Account number:{/s}',
            bic: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/bic"}BIC:{/s}',
            iban: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/iban"}IBAN:{/s}',
            bankName: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/bank_name"}Bank name:{/s}',
            noPaymentData: '{s namespace="backend/swag_backend_order/view/customer_information" name="swag_backend_order/customer_information/payment/no_payment_data"}No payment data found.{/s}'
        }
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;
        me.paymentStore = me.subApplication.getStore('Payment').load();
        me.items = me.createPaymentContainer();

        var customerStore = me.subApplication.getStore('Customer');

        me.customerId = -1;
        customerStore.on('load', function () {
            var customerModel = customerStore.getAt(0);
            if (customerModel !== undefined) {
                me.customerId = customerModel.get('id');
            }
            me.resetFields();
        });

        me.paymentComboBox.on('change', function (combo, newValue, oldValue) {
            if (newValue === '') return false;

            Ext.Ajax.request({
                url: '{url action="getCustomerPaymentData"}',
                params: {
                    paymentId: newValue,
                    customerId: me.customerId
                },
                success: function (response) {
                    me.responseObj = Ext.decode(response.responseText);

                    if (me.responseObj.data.length === 0) {
                        me.paymentDataView.setVisible(false);
                        me.remove(me.noDataView);

                        me.noDataView = Ext.create('Ext.view.View', {
                            name: 'noDataView',
                            tpl: new Ext.XTemplate(
                                '<div>',
                                '<p>' + me.snippets.paymentData.noPaymentData + '</p>',
                                '</div>'
                            )
                        });

                        me.remove('paymentDataView');
                        me.add(me.noDataView);
                        me.doLayout();

                        return false;
                    }

                    if (me.noDataView instanceof Ext.view.View) {
                        me.remove(me.noDataView);
                    }

                    me.paymentUserStore = Ext.create('Ext.data.Store', {
                        fields: [
                            'accountHolder',
                            'accountNumber',
                            'bankCode',
                            'bankName',
                            'bic',
                            'iban',
                            'id',
                            'paymentMeanId'
                        ],
                        data: me.responseObj.data
                    });

                    me.paymentDataView.setVisible(true);
                    me.remove('paymentDataView');
                    me.paymentDataView.bindStore(me.paymentUserStore);
                    me.paymentDataView.update();
                    me.add(me.paymentDataView);
                    me.doLayout();
                }
            });
        });

        me.callParent(arguments);
    },

    /**
     * registers events
     */
    registerEvents: function () {
        this.addEvents(
            'selectPayment'
        );
    },

    createPaymentContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            type: 'vbox',
            items: me.createPaymentItems()
        });
    },

    createPaymentItems: function () {
        var me = this;

        me.paymentComboBox = Ext.create('Ext.form.field.ComboBox', {
            name: 'payment',
            height: 35,
            store: me.paymentStore,
            displayField: 'description',
            allowBlank: false,
            valueField: 'id',
            listeners: {
                'select': function (comboBox, record) {
                    me.fireEvent('selectPayment', record);
                }
            }
        });

        me.paymentDataView = Ext.create('Ext.view.View', {
            name: 'paymentDataView',
            store: me.paymentUserStore,
            visible: false,
            tpl: me.createPaymentTemplate()
        });

        return [me.paymentComboBox, me.paymentDataView];
    },

    createPaymentTemplate: function () {
        var me = this;

        return new Ext.XTemplate(
            '{literal}',
            '<tpl for=".">',
            '<div>',
            '<p><b>' + me.snippets.paymentData.accountHolder + '</b> {accountHolder}</p>',
            '<tpl if="accountNumber">',
            '<p><b>' + me.snippets.paymentData.accountNumber + '</b> {accountNumber}</p>',
            '</tpl>',
            '<tpl if="iban">',
            '<p><b>' + me.snippets.paymentData.iban + '</b> {iban}</p>',
            '</tpl>',
            '<tpl if="bankCode">',
            '<p><b>' + me.snippets.paymentData.bankCode + '</b> {bankCode}</p>',
            '</tpl>',
            '<tpl if="bic">',
            '<p><b>' + me.snippets.paymentData.bic + '</b> {bic}</p>',
            '</tpl>',
            '<tpl if="bankName">',
            '<p><b>' + me.snippets.paymentData.bankName + '</b> {bankName}</p>',
            '</tpl>',
            '</div>',
            '</tpl>',
            '{/literal}'
        );
    },

    resetFields: function () {
        var me = this;

        me.paymentComboBox.setValue('');
        me.paymentDataView.setVisible(false);
    }
});
//
//{/block}