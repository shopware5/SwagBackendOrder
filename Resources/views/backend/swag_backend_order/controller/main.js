//{block name="backend/create_backend_order/controller/main"}
//
Ext.define('Shopware.apps.SwagBackendOrder.controller.Main', {

    /**
     * extends from the standard ExtJs Controller
     */
    extend: 'Ext.app.Controller',

    snippets: {
        error: {
            customer: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/customer"}Please select a customer.{/s}',
            billing: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/billing"}Please select a billing address.{/s}',
            payment: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/payment"}Please select a payment method.{/s}',
            shippingArt: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/shipping_art"}Please select a shipping art.{/s}',
            positions: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/positions"}Please add positions.{/s}',
            textInvalidArticle: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/invalid_article"}Invalid article: {/s}',
            invalidArticleTitle: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/invalid_article_title"}Error!{/s}',
            instanceText: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/instanceText"}You can not create more than one order at the same time.{/s}',
            instanceTitle: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/instanceTitle"}Erro!{/s}',
            title: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/title"}Error! Couldn\'t create order{/s}',
            mailTitle: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/error/mail_title"}Error! Couldn\'t send mail{/s}'
        },
        success: {
            text: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/success/text"}Order was created successfully. Ordernumber: {/s}',
            title: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/success/title"}Success!{/s}'
        },
        title: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/title/selected_user"}Create order for{/s}'
    },

    /**
     * A template method that is called when your application boots.
     * It is called before the Application's launch function is executed
     * so gives a hook point to run any code before your Viewport is created.
     *
     * @return void
     */
    init: function () {
        var me = this;

        //checks if a window is already open
        var createOrderWindow = Ext.getCmp('swagBackendOrderWindow');
        if (createOrderWindow instanceof Ext.window.Window) {
            Ext.Msg.alert(me.snippets.error.instanceTitle, me.snippets.error.instanceText);
            return;
        }

        me.control({
            'createbackendorder-customer-billing': {
                selectBillingAddress: me.onSelectBillingAddress
            },
            'createbackendorder-customer-shipping': {
                selectShippingAddress: me.onSelectShippingAddress,
                selectBillingAsShippingAddress: me.onSelectBillingAsShippingAddress,
                calculateTax: me.onCalculateTax
            },
            'createbackendorder-customer-payment': {
                selectPayment: me.onSelectPayment
            },
            'createbackendorder-additional': {
                changeAttrField: me.onChangeAttrField,
                changeDesktopType: me.onChangeDesktopType
            },
            'createbackendorder-customersearch': {
                selectCustomer: me.onSelectCustomer
            },
            'createbackendorder-toolbar': {
                openCustomer: me.onOpenCustomer,
                createCustomer: me.onCreateCustomer,
                changeCurrency: me.onChangeCurrency,
                changeCustomer: me.onChangeCustomer,
                changeLanguage: me.onChangeLanguage
            },
            'createbackendorder-mainwindow': {
                createOrder: me.onCreateOrder
            },
            'createbackendorder-shippingcosts': {
                addShippingCosts: me.onAddShippingCosts
            },
            'createbackendorder-position-grid': {
                openArticle: me.onOpenArticle,
                articleNameSelect: me.onArticleSelect,
                articleNumberSelect: me.onArticleSelect,
                cancelEdit: me.onCancelEdit
            },
            'createbackendorder-totalcostsoverview': {
                calculateTax: me.onCalculateTax,
                changeNetCheckbox: me.onChangeNetCheckbox
            }
        });

        me.getPluginConfig();

        /**
         * holds the actual orderData
         */
        me.orderModel = Ext.create('Shopware.apps.SwagBackendOrder.model.CreateBackendOrder', {});
        me.orderAttributeModel = Ext.create('Shopware.apps.SwagBackendOrder.model.OrderAttribute', {});
        me.createBackendOrderStore = me.subApplication.getStore('CreateBackendOrder');
        me.orderModel.set('currencyFactor', 1);

        me.currencyStore = me.subApplication.getStore('Currency').load();

        //passed a user id
        if (typeof me.subApplication.params !== 'undefined') {
            if (me.subApplication.params.userId) {
                me.onSelectCustomer(null, me.subApplication.params.userId);
            }
        }

        /**
         * initializes the window
         */
        me.window = me.getView('main.Window').create({
            id: 'swagBackendOrderWindow',
            subApplication: me.subApplication,
            orderModel: me.orderModel,
            createBackendOrderStore: me.createBackendOrderStore
        }).show();

        me.callParent(arguments);
    },

    /**
     * creates the order
     */
    onCreateOrder: function (positionsGridContainer, modus) {
        var me = this,
            errmsg = "";
        me.modus = modus;
        me.window.disable(true);

        /**
         * get the grid component for the position listing
         */
        me.positionsGrid = positionsGridContainer.getComponent('positionsGrid');

        /**
         * checks if all required fields was setted
         */
        var customerStore = me.subApplication.getStore('Customer');
        if (customerStore.count() > 0) {

            if (me.orderModel.get('billingAddressId') == 0) {
                errmsg += me.snippets.error.billing + '<br>';
            }
            if (me.orderModel.get('paymentId') == 0) {
                errmsg += me.snippets.error.payment + '<br>';
            }
            if (me.orderModel.get('dispatchId') == 0) {
                errmsg += me.snippets.error.shippingArt + '<br>';
            }

        } else {
            errmsg += me.snippets.error.customer + '<br>';
        }

        var positionsStore = me.positionsGrid.getStore();
        if (positionsStore.count() == 0) {
            errmsg += me.snippets.error.positions + '<br>'
        }

        if (errmsg.length > 0) {
            me.window.enable(true);
            Shopware.Notification.createGrowlMessage(me.snippets.error.title, errmsg);
            return;
        }

        //gets the positionModel which belongs to the actual orderModel
        var positionOrderStore = me.orderModel.position();
        if (positionOrderStore.count() > 0) {
            positionOrderStore.removeAll();
        }

        //iterates the created positions and adds every record to the positionModel
        positionsStore.each(
            function (record) {
                positionOrderStore.add(record);
            }
        );

        var orderAttributeStore = me.orderModel.orderAttribute();
        orderAttributeStore.add(me.orderAttributeModel);

        /**
         * sends the request to the php controller
         */
        me.orderModel.set('total', me.totalCostsModel.get('total'));
        me.orderModel.set('totalWithoutTax', me.totalCostsModel.get('totalWithoutTax'));

        me.createBackendOrderStore.sync({
            success: function (response) {
                me.orderId = response.proxy.reader.rawData.orderId;
                me.ordernumber = response.proxy.reader.rawData.ordernumber;
                me.mailErrorMessage = response.proxy.reader.rawData.mail;

                switch (me.modus) {
                    case 'new':
                        me.window.close();
                        if (response.proxy.reader.rawData.mail) {
                            Shopware.Notification.createGrowlMessage(me.snippets.error.mailTitle, me.mailErrorMessage);
                        }
                        Shopware.Notification.createGrowlMessage(me.snippets.success.title, me.snippets.success.text + me.ordernumber);

                        Shopware.app.Application.addSubApplication({
                            name: 'Shopware.apps.SwagBackendOrder',
                            action: 'detail'
                        });
                        break;
                    case 'close':
                        me.window.close();
                        break;
                    case 'detail':
                        if (me.orderId > 0) {
                            Shopware.app.Application.addSubApplication({
                                name: 'Shopware.apps.Order',
                                action: 'detail',
                                params: {
                                    orderId: me.orderId
                                }
                            });

                        }
                        if (response.proxy.reader.rawData.mail) {
                            Shopware.Notification.createGrowlMessage(me.snippets.error.mailTitle, me.mailErrorMessage);
                        }
                        Shopware.Notification.createGrowlMessage(me.snippets.success.title, me.snippets.success.text + ' - ' + me.ordernumber);
                        me.window.close();
                        break;
                    default:
                        break;
                }
            },
            failure: function (response) {
                var article = response.proxy.reader.rawData.article;

                if (article || article == "") {
                    Shopware.Notification.createGrowlMessage(me.snippets.error.title, me.snippets.error.textInvalidArticle + ' ' + article);
                } else {
                    Shopware.Notification.createGrowlMessage(me.snippets.error.title, response.proxy.reader.rawData.message);
                }

                me.window.enable(true);
            }
        });

    },

    /**
     * event which is fired by the shipping combobox and the number fields
     *
     * @param shippingCosts
     * @param shippingCostsNet
     * @param dispatchId
     * @param shippingCostsFields
     */
    onAddShippingCosts: function (shippingCosts, shippingCostsNet, dispatchId, shippingCostsFields) {
        var me = this;

        if (shippingCostsFields !== undefined) {
            me.shippingCostsFields = shippingCostsFields;

            shippingCosts = me.calculateCurrency(shippingCosts);
            shippingCostsNet = me.calculateCurrency(shippingCostsNet);

            shippingCostsFields[0].setValue(shippingCosts);
            shippingCostsFields[1].setValue(shippingCostsNet);
        }

        me.orderModel.set('shippingCosts', shippingCosts);
        me.orderModel.set('shippingCostsNet', shippingCostsNet);

        if (me.orderModel.get('dispatchId') != undefined && me.orderModel.get('dispatchId') != dispatchId) {
            me.orderModel.set('dispatchId', dispatchId);
        }
        me.createBackendOrderStore.add(me.orderModel);

        me.totalCostsModel = me.subApplication.getStore('TotalCosts').getAt(0);
        me.totalCostsModel.set('shippingCosts', shippingCosts);
    },

    /**
     * selects the correct billing address and updates it to the default address
     *
     * @param record
     * @param billing
     */
    onSelectBillingAddress: function (record, billing) {
        var me = this;
        record = record[0].data;

        Ext.Ajax.request({
            url: '{url action="setBillingAddress"}',
            params: {
                salutation: record.salutation,
                company: record.company,
                firstName: record.firstName,
                lastName: record.lastName,
                city: record.city,
                zipCode: record.zipCode,
                countyId: record.countryId,
                phone: record.phone,
                street: record.street,
                vatId: record.vatId,
                additionalAddressLine1: record.additionalAddressLine1,
                additionalAddressLine2: record.additionalAddressLine2,
                department: record.department,
                userId: me.customerStore.getAt(0).get('id')
            },
            success: function (response) {
                var responseObj = Ext.JSON.decode(response.responseText);
                me.orderModel.set('billingAddressId', responseObj.billingAddressId);
            }
        });
    },

    /**
     * selects the correct shipping address and updates it to the default address
     *
     * @param record false for no selected record, otherwise a single data model
     */
    onSelectShippingAddress: function (record) {
        var me = this;

        if (record === false) { // No shipping address selected.
            var EMPTY_SHIPPING_ADDRESS_ID = 0; // Magic constant
            me.orderModel.set('shippingAddressId', EMPTY_SHIPPING_ADDRESS_ID);
            return;
        }

        record = record.data;

        Ext.Ajax.request({
            url: '{url action="setShippingAddress"}',
            params: {
                salutation: record.salutation,
                company: record.company,
                firstName: record.firstName,
                lastName: record.lastName,
                city: record.city,
                zipCode: record.zipCode,
                countyId: record.countryId,
                street: record.street,
                additionalAddressLine1: record.additionalAddressLine1,
                additionalAddressLine2: record.additionalAddressLine2,
                department: record.department,
                userId: me.customerStore.getAt(0).get('id')
            },
            success: function (response) {
                var responseObj = Ext.JSON.decode(response.responseText);
                me.orderModel.set('shippingAddressId', responseObj.shippingAddressId);
            }
        });
    },

    /**
     * fired when the user selects a payment
     * sets the payment in the orderModel
     *
     * @param record
     */
    onSelectPayment: function (record) {
        var me = this;
        me.orderModel.set('paymentId', record[0].data.id);
    },

    /**
     * Event will be fired when the user search for an article number in the row editor
     * and selects an article in the drop down menu.
     *
     * @param [object] editor - Ext.grid.plugin.RowEditing
     * @param [string] value - Value of the Ext.form.field.Trigger
     * @param [object] record - Selected record
     */
    onArticleSelect: function (editor, value, record) {
        var me = this;

        var columns = editor.editor.items.items,
            updateButton = editor.editor.floatingButtons.items.items[0];

        updateButton.setDisabled(true);

        //sends a request to get the price for the customer group
        Ext.Ajax.request({
            url: '{url action="getCustomerGroupPriceByOrdernumber"}',
            params: {
                ordernumber: record.get('number'),
                customerId: me.orderModel.get('customerId')
            },
            success: function (response) {
                var responseObj = Ext.JSON.decode(response.responseText);
                var result = responseObj.data;

                var price = 0;
                if (responseObj.success == true) {
                    price = me.calculateCurrency(result.price);
                } else {
                    price = me.calculateCurrency(record.get('price'));
                }

                if (me.orderModel.get('net')) {
                    price = me.calculateNetPrice(price, record.get('tax'));
                }

                /**
                 * columns[0] -> selected
                 * columns[1] -> articlenumber
                 * columns[2] -> articlename
                 * columns[3] -> quantity
                 * columns[4] -> price
                 * columns[5] -> total
                 * columns[6] -> tax
                 * columns[7] -> instock
                 */
                columns[1].setValue(record.get('number'));
                columns[2].setValue(record.get('name'));
                columns[3].setValue(1);
                columns[4].setValue(price);
                columns[7].setValue(record.get('inStock'));

                var taxComboStore = columns[6].store;
                var valueField = columns[6].valueField;
                var displayField = columns[6].displayField;

                var recordNumber = taxComboStore.findExact(valueField, record.get('taxId'), 0);

                var displayValue = taxComboStore.getAt(recordNumber).data[displayField];
                columns[6].setValue(record.get('taxRate'));
                columns[6].setRawValue(displayValue);
                columns[6].selectedIndex = recordNumber;
                updateButton.setDisabled(false);
            }

        });


    },

    /**
     * Event listener method which is fired when the user cancel the row editing in the position grid
     * on the detail page. If the edited record is a new position, the position will be removed.
     *
     * @param grid
     * @param eOpts
     */
    onCancelEdit: function (grid, eOpts) {
        var record = eOpts.record,
            store = eOpts.store;

        if (!(record instanceof Ext.data.Model) || !(store instanceof Ext.data.Store)) {
            return;
        }
        if (record.get('articleId') === 0 && record.get('articleNumber') === '') {
            store.remove(record);
        }
    },

    /**
     * fires only when a new value was selected from the drop down menu to select the correct customer by his id
     *
     * @param newValue
     * @param customerId
     */
    onSelectCustomer: function (newValue, customerId) {
        var me = this;

        me.customerStore = me.subApplication.getStore('Customer').load({
            params: { searchParam: customerId }
        });

        me.customerStore.on('load', function () {
            if (Ext.isObject(me.customerStore)) {
                me.orderModel.set('customerId', customerId);
                me.customerSelected = true;

                if (typeof me.customerStore.getAt(0) === 'undefined') {
                    return;
                }
                var billingModel = me.customerStore.getAt(0).billing().getAt(0);

                var title = me.snippets.title + ' ' +
                    billingModel.get('firstName') +
                    ' ' +
                    billingModel.get('lastName');

                if (billingModel.get('number')) {
                    title += ' - ' + billingModel.get('number');
                }

                if (billingModel.get('company')) {
                    title += ' - ' + billingModel.get('company');
                }

                me.window.setTitle(title);
            }
        });
    },

    /**
     * opens an article from the positions grid
     *
     * @param record
     */
    onOpenArticle: function (record) {
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Article',
            action: 'detail',
            params: {
                articleId: record.get('articleId')
            }
        });
    },

    /**
     * opens the selected customer
     */
    onOpenCustomer: function () {
        var me = this,
            customerId = me.subApplication.getStore('Customer').getAt(0).get('id');

        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Customer',
            action: 'detail',
            params: {
                customerId: customerId
            }
        });
    },

    /**
     * @param createGuest
     */
    onCreateCustomer: function (createGuest) {
        var me = this,
            email = '',
            guest = false;

        if (createGuest) {
            email = me.validationMail;
            guest = true;
        }

        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Customer',
            action: 'detail',
            params: {
                guest: guest,
                email: email
            }
        });
    },

    /**
     * calculates the new price for another currency
     *
     * @param comboBox
     * @param newValue
     * @param oldValue
     * @param eOpts
     */
    onChangeCurrency: function (comboBox, newValue, oldValue, eOpts) {
        var me = this,
            shippingCosts = 0,
            shippingCostsNet = 0;

        me.orderModel.set('currencyId', newValue);

        var currencyIndex = me.currencyStore.findExact('id', newValue);
        var newCurrency = me.currencyStore.getAt(currencyIndex);
        newCurrency.set('selected', 1);

        if (oldValue !== undefined) {
            currencyIndex = me.currencyStore.findExact('id', oldValue);
            var oldCurrency = me.currencyStore.getAt(currencyIndex);
            oldCurrency.set('selected', 0);
        }

        var positionJsonString = '';
        if (me.positionStore instanceof Ext.data.Store) {
            var positionArray = [];
            me.positionStore.each(function (record) {
                positionArray.push(record.data);
            });
            positionJsonString = Ext.JSON.encode(positionArray);
        }

        Ext.Ajax.request({
            url: '{url action="calculateCurrency"}',
            params: {
                positions: positionJsonString,
                oldCurrencyId: oldValue,
                newCurrencyId: newValue,
                shippingCosts: me.orderModel.get('shippingCosts'),
                shippingCostsNet: me.orderModel.get('shippingCostsNet')
            },
            success: function (response) {
                var responseObj = Ext.JSON.decode(response.responseText).data;

                if (typeof responseObj === 'undefined') {
                    return;
                }

                for (var i = 0; i < responseObj.positions.length; i++) {
                    var position = me.positionStore.getAt(i);
                    position.set('price', responseObj.positions[i].price);
                    position.set('total', responseObj.positions[i].total);
                }

                me.orderModel.set('shippingCosts', responseObj.shippingCosts);
                me.orderModel.set('shippingCostsNet', responseObj.shippingCostsNet);

                if (me.shippingCostsFields !== undefined) {
                    me.shippingCostsFields[0].setValue(me.orderModel.get('shippingCosts'));
                    me.shippingCostsFields[1].setValue(me.orderModel.get('shippingCostsNet'));
                }
            }
        });
    },

    /**
     * @param price
     * @returns
     */
    calculateCurrency: function (price) {
        var me = this;
        var index = me.currencyStore.findExact('selected', 1);

        price = price * me.currencyStore.getAt(index).get('factor');
        return price;
    },

    /**
     * saves the attribute fields in the correct store field
     *
     * @param field
     * @param newValue
     * @param oldValue
     */
    onChangeAttrField: function (field, newValue, oldValue) {
        var me = this;

        switch (field.name) {
            case 'attr1TxtBox':
                me.orderAttributeModel.set('attribute1', field.getValue());
                break;
            case 'attr2TxtBox':
                me.orderAttributeModel.set('attribute2', field.getValue());
                break;
            case 'attr3TxtBox':
                me.orderAttributeModel.set('attribute3', field.getValue());
                break;
            case 'attr4TxtBox':
                me.orderAttributeModel.set('attribute4', field.getValue());
                break;
            case 'attr5TxtBox':
                me.orderAttributeModel.set('attribute5', field.getValue());
                break;
            case 'attr6TxtBox':
                me.orderAttributeModel.set('attribute6', field.getValue());
                break;
            default:
                break;
        }

        me.subApplication.getStore("OrderAttribute").add(me.orderAttributeModel);
    },

    /**
     * event fires when the desktop type combox changes the data index
     *
     * @param comboBox
     * @param newValue
     */
    onChangeDesktopType: function (comboBox, newValue) {
        var me = this;

        var desktopType = comboBox.findRecordByValue(newValue);
        me.orderModel.set('desktopType', desktopType.data.name);
    },

    /**
     * reads the plugin configuration
     */
    getPluginConfig: function () {
        var me = this;

        Ext.Ajax.request({
            url: '{url action=getPluginConfig}',
            success: function (response, opts) {
                var pluginConfigObj = Ext.decode(response.responseText);

                me.validationMail = pluginConfigObj.data.validationMail;
                me.desktopTypes = pluginConfigObj.data.desktopTypes;

                me.subApplication.getStore('DesktopTypes').loadData(me.desktopTypes, false);
            }
        });
    },

    /**
     * deselects the shipping address
     */
    onSelectBillingAsShippingAddress: function () {
        var me = this;
        me.orderModel.set('shippingAddressId', null);
    },

    /**
     * calculates the tax costs for every tax rate and the shipping tax
     */
    onCalculateTax: function () {
        var me = this;

        me.positionStore = me.subApplication.getStore('Position');
        me.totalCostsStore = me.subApplication.getStore('TotalCosts');
        me.totalCostsModel = me.totalCostsStore.getAt(0);

        var positionArray = [];
        me.positionStore.each(function (record) {
            positionArray.push(record.data);
        });
        var positionJsonString = Ext.JSON.encode(positionArray);

        Ext.Ajax.request({
            url: '{url action="calculateTax"}',
            params: {
                positions: positionJsonString,
                shippingCosts: me.orderModel.get('shippingCosts'),
                net: me.orderModel.get('net')
            },
            success: function (response) {
                var totalCostsJson = Ext.JSON.decode(response.responseText);
                var record = totalCostsJson.data;

                me.totalCostsModel.beginEdit();
                try {
                    me.totalCostsModel.set('totalWithoutTax', record.totalWithoutTax);
                    me.totalCostsModel.set('sum', record.sum);
                    me.totalCostsModel.set('total', record.total);
                    me.totalCostsModel.set('shippingCosts', record.shippingCosts);
                    me.totalCostsModel.set('shippingCostsNet', record.shippingCostsNet);
                    me.totalCostsModel.set('taxSum', record.taxSum);
                } finally {
                    me.totalCostsModel.endEdit();
                }

                me.orderModel.set('shippingCostsNet', record.shippingCostsNet);
            }
        });
    },

    /**
     *
     * @param value
     * @param taxRate
     * @returns number
     */
    calculateNetPrice: function (value, taxRate) {
        taxRate = parseInt(taxRate);
        return value / ((100 + taxRate) / 100);
    },

    /**
     * resets all setted data which belongs to the customer which was selected by the user
     */
    onChangeCustomer: function () {
        var me = this;

        me.orderModel.set('billingAddressId', null);
        me.orderModel.set('shippingAddressId', null);
        me.orderModel.set('shippingCosts', null);
        me.orderModel.set('shippingCostsNet', null);
        me.orderModel.set('paymentId', null);
    },

    /**
     * calculates the new prices and sets the net flag to true
     * @param net
     */
    onChangeNetCheckbox: function (net) {
        var me = this;
        me.orderModel.set('net', net);

        if (me.totalCostsModel) {
            me.totalCostsModel.set('net', net);
        }

        var positionStore = me.subApplication.getStore('Position');
        if (!(positionStore instanceof Ext.data.Store)) return;

        var positionArray = [];
        positionStore.each(function (record) {
            positionArray.push(record.data);
        });
        var positionJsonString = Ext.JSON.encode(positionArray);

        /**
         * ajax request to calculate the new prices
         */
        Ext.Ajax.request({
            url: '{url action="changedNetBox"}',
            params: {
                positions: positionJsonString,
                net: net
            },
            success: function (response) {
                var responseObj = Ext.JSON.decode(response.responseText);
                var positions = responseObj.data;

                for (var index = 0; index < positionStore.count(); index++) {
                    var actualPosition = positionStore.getAt(index);
                    actualPosition.beginEdit();
                    try {
                        actualPosition.set('price', positions[index].price);
                        actualPosition.set('total', positions[index].total);
                    } finally {
                        actualPosition.endEdit();
                    }
                }
            }
        });
    },

    /**
     * changes the actual language for the confirmation mail
     *
     * @param languageShopId
     */
    onChangeLanguage: function (languageShopId) {
        var me = this;

        me.orderModel.set('languageShopId', languageShopId);
    }

});
//
//{/block}