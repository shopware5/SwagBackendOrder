//
//{block name="backend/create_backend_order/view/toolbar"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.Toolbar', {

    extend: 'Ext.toolbar.Toolbar',

    alternateClassName: 'SwagBackendOrder.view.main.Toolbar',

    alias: 'widget.createbackendorder-toolbar',

    dock: 'top',

    ui: 'shopware-ui',

    padding: '0 10 0 10',

    snippets: {
        buttons: {
            openCustomer: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/button/open_customer"}Open Customer{/s}',
            createCustomer: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/button/create_customer"}Create Customer{/s}',
            createGuest: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/button/create_guest"}Create Guest{/s}'
        },
        shop: {
            noCustomer: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/shop/label/no_costumer"}Shop: No customer selected.{/s}',
            default: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/shop/label/default"}Shop: {/s}'
        },
        currencyLabel: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/currency/label"}Choose currency{/s}',
        languageLabel: '{s namespace="backend/swag_backend_order/view/toolbar" name="swag_backend_order/toolbar/language/label"}Language{/s}'
    },

    /**
     *
     */
    initComponent: function () {
        var me = this;

        me.items = me.createToolbarItems();

        me.languageStore = Ext.create('Ext.data.Store', {
            name: 'languageStore',
            fields: ['id', 'mainId', 'categoryId', 'name', 'title', 'default']
        });

        /**
         * automatically selects the standard currency
         */
        me.currencyStore = me.subApplication.getStore('Currency');

        me.currencyStore.on('load', function () {
            me.changeCurrencyComboBox.bindStore(me.currencyStore);
            var standardCurrency = me.currencyStore.findExact('default', 1);

            if (standardCurrency > -1) {
                me.currencyModel = me.currencyStore.getAt(standardCurrency);
                me.changeCurrencyComboBox.select(me.currencyModel);

                me.currencyModel.set('selected', 1);
            } else {
                me.changeCurrencyComboBox.select(me.currencyStore.first());
                me.currencyStore.first().set('selected', 1);
            }

        });

        me.customerSearchField.on('valueselect', function () {
            me.openCustomerButton.setDisabled(false);
        });

        //selects and loads the language sub shops
        var customerStore = me.subApplication.getStore('Customer');
        customerStore.on('load', function () {
            if (typeof customerStore.getAt(0) !== 'undefined') {
                var shopName = '',
                    customerModel = customerStore.getAt(0);

                var languageId = customerModel.get('languageId');
                var index      = customerModel.languageSubShop().findExact('id', languageId);

                if (index >= 0) {
                    shopName = customerModel.languageSubShop().getAt(index).get('name');
                } else {
                    index = customerModel.shop().findExact('id', languageId);
                    shopName = customerModel.shop().getAt(index).get('name');
                }

                me.shopLabel.setText(me.snippets.shop.default + shopName);
                me.fireEvent('changeCustomer');

                me.getLanguageShops(customerModel.shop().getAt(0).get('id'), customerStore.getAt(0).get('languageId'));
            }
        });

        me.callParent(arguments);
    },

    /**
     * register the events
     */
    registerEvents: function () {
        this.addEvents(
            'changeSearchField'
        )
    },

    /**
     * creates the top toolbar items
     *
     * @returns []
     */
    createToolbarItems: function () {
        var me = this;

        me.customerSearchField = me.createCustomerSearch('customerName', 'id', 'email');

        me.createCustomerButton = Ext.create('Ext.button.Button', {
            text: me.snippets.buttons.createCustomer,
            handler: function () {
                me.fireEvent('createCustomer', false);
            }
        });

        me.createGuestButton = Ext.create('Ext.button.Button', {
            text: me.snippets.buttons.createGuest,
            handler: function () {
                me.fireEvent('createCustomer', true);
            }
        });

        me.openCustomerButton = Ext.create('Ext.button.Button', {
            text: me.snippets.buttons.openCustomer,
            disabled: true,
            margin: '0 30 0 0',
            handler: function () {
                me.fireEvent('openCustomer');
            }
        });

        me.shopLabel = Ext.create('Ext.form.Label', {
            text: me.snippets.shop.noCustomer,
            style: {
                fontWeight: 'bold'
            }
        });

        me.languageComboBox = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: me.snippets.languageLabel,
            labelWidth: 65,
            store: me.languageStore,
            queryMode: 'local',
            displayField: 'name',
            width: '20%',
            valueField: 'id',
            listeners: {
                change: {
                    fn: function (comboBox, newValue, oldValue, eOpts) {
                        me.fireEvent('changeLanguage', newValue);
                    }
                }
            }
        });

        me.changeCurrencyComboBox = Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: me.snippets.currencyLabel,
            stores: me.currencyStore,
            queryMode: 'local',
            displayField: 'currency',
            width: '20%',
            valueField: 'id',
            listeners: {
                change: {
                    fn: function (comboBox, newValue, oldValue, eOpts) {
                        me.fireEvent('changeCurrency', comboBox, newValue, oldValue, eOpts);
                    }
                }
            }
        });

        return [
            me.changeCurrencyComboBox, me.languageComboBox, me.shopLabel, '->',
            me.createCustomerButton, me.createGuestButton, me.openCustomerButton, me.customerSearchField
        ];
    },

    /**
     *
     * @param returnValue
     * @param hiddenReturnValue
     * @param name
     * @return Shopware.form.field.ArticleSearch
     */
    createCustomerSearch: function (returnValue, hiddenReturnValue, name) {
        var me = this;
        me.customerStore = me.subApplication.getStore('Customer');

        return Ext.create('Shopware.apps.SwagBackendOrder.view.main.CustomerSearch', {
            name: name,
            subApplication: me.subApplication,
            returnValue: returnValue,
            hiddenReturnValue: hiddenReturnValue,
            articleStore: me.customerStore,
            allowBlank: false,
            getValue: function () {
                me.store.getAt(me.record.rowIdx).set(name, this.getSearchField().getValue());
                return this.getSearchField().getValue();
            },
            setValue: function (value) {
                this.getSearchField().setValue(value);
            }
        });
    },

    /**
     * @param mainShopId
     * @param languageId
     */
    getLanguageShops: function (mainShopId, languageId) {
        var me = this;

        Ext.Ajax.request({
            url: '{url action="getLanguageSubShops"}',
            params: {
                mainShopId: mainShopId
            },
            success: function (response) {
                me.languageStore.removeAll();
                var languageSubShops = Ext.JSON.decode(response.responseText);

                languageSubShops.data.forEach(function (record) {
                    me.languageStore.add(record);
                });
                me.languageComboBox.bindStore(me.languageStore);

                //selects the default language shop
                var languageIndex = me.languageStore.findExact('mainId', null);
                me.languageComboBox.setValue(languageId);
            }
        });
    }
});
//
//{/block}
//