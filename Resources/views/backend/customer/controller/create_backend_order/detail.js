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
            'customer-debit-field-set':{
                changePayment:me.onChangePayment
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
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.SwagBackendOrder',
            action: 'detail',
            params: {
                userId: record.data.id
            }
        });
    },

    /**
     * Overriding to set random password for new guest accounts
     */
    onSaveCustomer: function (btn) {
        var me = this, number,
            win = btn.up('window'),
            form = win.down('form'),
            model = form.getRecord();

        if (typeof me.subApplication.params != 'undefined') {
            if (me.subApplication.params.guest == true) {
                var password = me.generateRandomPassword();
                model.set('newPassword', password);
            }
        }

        me.callParent(arguments);
    },

    /**
     * @returns { string }
     */
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