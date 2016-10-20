
/**
 * Shopware Controller - Customer list backend module
 *
 * The customer module main controller handles the initialisation of the customer backend list.
 * It is possible to pass a customer id to the module to open the detail window directly. To
 * open the detail window directly pass the customer id in the parameter "customerId"
 */
//{block name="backend/customer/controller/main" append}
Ext.define('Shopware.apps.CreateBackendOrder.controller.Main', {
    override: 'Shopware.apps.Customer.controller.Main',

    /**
     * Creates the necessary event listener for this
     * specific controller and opens a new Ext.window.Window
     * to display the sub application
     *
     * @params customerId - The main controller can handle a customerId parameter to open the customer detail page directly
     * @return void
     */
    init: function () {
        var me = this,
            store;

        if (me.subApplication.action && me.subApplication.action.toLowerCase() === 'detail') {
            if (me.subApplication.params && me.subApplication.params.customerId) {
                //open the customer detail page with the passed customer id
                store = me.subApplication.getStore('Detail');
                store.getProxy().extraParams = {
                    customerID: me.subApplication.params.customerId
                };

                me.mainWindow = me.subApplication.getView('detail.Window').create().show();
                me.mainWindow.setLoading(true);

                store.load({
                    callback: function (records) {
                        var customer = records[0];
                        var store = Ext.create('Shopware.apps.Customer.store.Batch');
                        store.load({
                            callback: function (records) {
                                var storeData = records[0];
                                me.mainWindow.record = customer;
                                me.mainWindow.record.data['guest'] = false;

                                /**
                                 * checks if additional params was passed and sets the default guest email
                                 * if it is necessary
                                 */
                                if (typeof me.subApplication.params != 'undefined') {
                                    if (me.subApplication.params.guest === true) {
                                        me.mainWindow.record.data.email = me.subApplication.params.email;
                                        me.mainWindow.record.data['guest'] = true;
                                        me.mainWindow.record.data.accountMode = 1;
                                        me.mainWindow.record.data.active = 1;
                                    }
                                }

                                me.mainWindow.createTabPanel();
                                me.mainWindow.setLoading(false);
                                me.mainWindow.setStores(storeData);
                                me.subApplication.setAppWindow(me.mainWindow);
                            }
                        });
                    }
                });
            } else {
                store = Ext.create('Shopware.apps.Customer.store.Batch');
                store.load({
                    callback: function (records) {
                        var storeData = records[0];

                        me.mainWindow = me.subApplication.getView('detail.Window').create().show();
                        me.mainWindow.setLoading(true);
                        me.mainWindow.record = Ext.create('Shopware.apps.Customer.model.Customer');

                        /**
                         * checks if additional params was passed and sets the default guest email
                         * if it is necessary
                         */
                        if (typeof me.subApplication.params != 'undefined') {
                            if (me.subApplication.params.guest === true) {
                                me.mainWindow.record.data.email = me.subApplication.params.email;
                                me.mainWindow.record.data['guest'] = true;
                                me.mainWindow.record.data.accountMode = 1;
                                me.mainWindow.record.data.active = 1;
                            }
                        }

                        me.mainWindow.createTabPanel();
                        me.mainWindow.setStores(storeData);
                        me.mainWindow.setLoading(false);
                    }
                });
            }
        } else {
            //open the customer listing window
            me.mainWindow = me.getView('main.Window').create({
                listStore: me.subApplication.getStore('List').load()
            });
        }

        this.callParent(arguments);
    }

});
//{/block}
