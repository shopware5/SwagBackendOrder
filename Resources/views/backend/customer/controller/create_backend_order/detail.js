//{block name="backend/customer/controller/detail" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.controller.Detail', {
    override: 'Shopware.apps.Customer.controller.Detail',

    /**
     * Override init to add additional event for button to perform a backend order
     */
    init: function () {
        var me = this;

        me.callParent(arguments);

        me.control({
            'customer-additional-panel': {
                performBackendOrder: me.onPerformBackendOrder
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