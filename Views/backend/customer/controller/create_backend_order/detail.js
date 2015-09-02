//{block name="backend/customer/controller/detail" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.controller.Detail', {
    override: 'Shopware.apps.Customer.controller.Detail',

    /**
     * Component event method which is fired when the component is initials.
     * Register the different events to handle all around the customer editing and creation
     * @return void
     */
    init: function () {
        var me = this;

        me.control({
            'customer-list': {
                editColumn: me.onEditCustomer,
                itemdblclick: me.onGridDblClick
            },
            'customer-list button[action=addCustomer]': {
                click: me.onCreateCustomer
            },
            'customer-detail-window button[action=save-customer]': {
                click: me.onSaveCustomer
            },
            'customer-billing-field-set': {
                countryChanged: me.onCountryChanged
            },
            'customer-base-field-set': {
                generatePassword: me.onGeneratePassword
            },
            'customer-shipping-field-set': {
                copyAddress: me.onCopyAddress,
                countryChanged: me.onCountryChanged
            },
            'customer-debit-field-set': {
                changePayment: me.onChangePayment
            },
            'customer-additional-panel': {
                performOrder: me.onPerformOrder,
                createAccount: me.onCreateAccount,
                performBackendOrder: me.onPerformBackendOrder
            }
        });
    },

    onCreateCustomer: function () {
        var me = this,
            record = me.getModel('Customer').create({ active: true });

        var detailWindow = me.subApplication.getView('detail.Window').create().show();
        detailWindow.setLoading(true);

        var store = Ext.create('Shopware.apps.Customer.store.Batch');
        store.load({
            callback: function (records) {
                var storeData = records[0];
                detailWindow.record = record;
                detailWindow.createTabPanel();
                detailWindow.setLoading(false);
                detailWindow.setStores(storeData);
            }
        });
    },

    /**
     * opens the backend order subApplication and passes the user id
     *
     * @param record
     */
    onPerformBackendOrder: function (record) {
        var me = this;

        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.SwagBackendOrder',
            action: 'detail',
            params: {
                userId: record.data.id
            }
        });
    },

    /**
     * Event listener method which is fired when the user try to save
     * the inserted customer detail data. Merges the form record with
     * the form values to get a model with all data.
     *
     * Overriding to set a password
     *
     * @param btn Ext.button.Button contains the save button
     * @return void
     */
    onSaveCustomer: function (btn) {
        var me = this, number,
            win = btn.up('window'),
            form = win.down('form'),
            model = form.getRecord(),
            missingField = "Unknown field",
            listStore = me.subApplication.getStore('List');

        if (!form.getForm().isValid()) {
            // check which field is not valid in order to tell the user, why the customer cannot be saved
            // SW-4322
            form.getForm().getFields().each(function (f) {
                if (!f.validate()) {
                    if (f.fieldLabel) {
                        missingField = f.fieldLabel;
                    } else if (f.name) {
                        missingField = f.name;
                    }
                    Shopware.Notification.createGrowlMessage(me.snippets.form.errorTitle, Ext.String.format(me.snippets.form.errorMessage, missingField), me.snippets.growlMessage);
                    return false;
                }

            });
            return;
        }

        form.getForm().updateRecord(model);

        if (typeof me.subApplication.params != 'undefined') {
            if (me.subApplication.params.guest == true) {
                var password = me.generateRandomPassword();
                model.set('newPassword', password);
            }
        }

        //save the model and check in the callback function if the operation was successfully
        model.save({
            callback: function (data, operation) {
                var records = operation.getRecords(),
                    record = records[0],
                    rawData = record.getProxy().getReader().rawData;

                if (operation.success === true) {
                    var billing = model.getBilling().first();
                    number = billing.get('number');
                    Shopware.Notification.createGrowlMessage(me.snippets.password.successTitle, Ext.String.format(me.snippets.password.successText, number), me.snippets.growlMessage);
                    win.destroy();
                    listStore.load();
                } else {
                    Shopware.Notification.createGrowlMessage(me.snippets.password.errorTitle, me.snippets.password.errorText + '<br> ' + rawData.message, me.snippets.growlMessage)
                }
            }
        });
    },

    generateRandomPassword: function () {
        var pool = '01234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            password = '', i = 8, length = pool.length;

        while (i--) {
            password += pool[Math.floor(length * Math.random())];
        }

        return password;
    }
});

//{/block}