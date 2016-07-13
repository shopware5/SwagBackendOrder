//
//{block name="backend/create_backend_order/view/window"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.Window', {

    /**
     * Extends from the standard ExtJs app Window
     */
    extend: 'Enlight.app.Window',

    height: 850,

    width: 1100,

    alias: 'widget.createbackendorder-mainwindow',

    layout: {
        type: 'vbox',
        align: 'stretch',
        pack: 'start'
    },

    confirmClosed: false,

    //autoScroll: true,

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/title"}Create Order{/s}',
        buttons: {
            cancel: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/buttons/cancel"}Cancel{/s}',
            create: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/buttons/create"}Create{/s}',
            createAndNew: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/buttons/create_and_new"}Create and new{/s}'
        },
        message: {
            closeConfirmTitle: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/msg/confirm_title"}Warning{/s}',
            closeConfirmText: '{s namespace="backend/swag_backend_order/view/main" name="swagbackendorder/msg/confirm_close"}Are you sure to close this window?{/s}'
        }

    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;

        /**
         * loads the ext js components
         */
        me.items = [
            me.createToolbar(),
            me.createCustomerInformation(),
            me.createMiddleContainer(),
            me.createPositionContainer(),
            me.createTotalCostsOverview(),
            me.createSaveContainer()
        ];

        /**
         * confirmation alert message if the user wants to close this window and made changes
         */
        var positionStore = me.subApplication.getStore('Position');
        me.on('beforeclose', function () {
            if (!me.confirmClosed && (positionStore.count() > 0 || me.checkOrderModel() || me.createBackendOrderStore.count() == 1)) {
                Ext.MessageBox.confirm(
                    me.snippets.message.closeConfirmTitle,
                    me.snippets.message.closeConfirmText,
                    function (button) {
                        if (button == 'yes') {
                            me.confirmClosed = true;
                            this[this.closeAction]();
                        }
                    },
                    this);

                return false;
            }
        });

        me.callParent(arguments);
    },

    /**
     * Registers the addition component events.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * Event will be fired when the user clicks the create button
             */
            'createOrder'
        );
    },

    /**
     * creates the customer information container
     * which holds the billing, shipping and payment panel
     *
     */
    createCustomerInformation: function () {
        var me = this;

        return Ext.create('SwagBackendOrder.main.CustomerInformation', {
            subApplication: me.subApplication
        });
    },

    /**
     * returns the toolbar
     */
    createToolbar: function () {
        var me = this;

        return Ext.create('SwagBackendOrder.view.main.Toolbar', {
            subApplication: me.subApplication
        });
    },

    createShippingInformationPanel: function () {
        var me = this;

        var shippingCostsStore = me.subApplication.getStore('ShippingCosts').load();

        return Ext.create('SwagBackendOrder.view.main.ShippingCosts', {
            shippingCostsStore: shippingCostsStore,
            subApplication: me.subApplication,
            orderModel: me.orderModel
        });
    },

    /**
     * creates the additional information container for the
     * Freitextfelder and desktop-type
     */
    createAdditionalInformationPanel: function () {
        var me = this;

        return Ext.create('SwagBackendOrder.view.main.AdditionalInformation', {
            subApplication: me.subApplication
        });
    },

    /**
     * creates the middle container which holds the shipping and additional information,
     * both panels must include in the middle container
     * to create the horziontal layout for the panels
     */
    createMiddleContainer: function () {
        var me = this;

        return Ext.create('Ext.Container', {
            flex: 2,
            name: 'middle-container',
            items: [
                me.createShippingInformationPanel(),
                me.createAdditionalInformationPanel()
            ],
            defaults: {
                height: 175
            },
            layout: {
                type: 'hbox',
                align: 'stretch'
            }
        });
    },

    /**
     * creates a container which holds the grid to add new positions
     *
     * @returns [Ext.Container]
     */
    createPositionContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            padding: '10 10 0 10',
            items: [
                me.createPositionGrid()
            ]
        });
    },

    /**
     * creates the position grid
     *
     * @returns [Shopware.apps.SwagBackendOrder.view.main.list.Positions]
     */
    createPositionGrid: function () {
        var me = this;

        return me.positionsGrid = Ext.create('SwagBackendOrder.view.main.list.Positions', {
            subApplication: me.subApplication,
            orderModel: me.orderModel
        });
    },

    /**
     * creates an container holds the create / cancel buttons
     *
     * @returns [Ext.container.Container]
     */
    createSaveContainer: function () {
        var me = this;

        me.cancelButton = Ext.create('Ext.button.Button', {
            cls: 'secondary',
            text: me.snippets.buttons.cancel,
            handler: function () {
                me.fireEvent('beforeclose');
            }
        });

        me.saveButton = Ext.create('Ext.button.Button', {
            cls: 'primary',
            text: me.snippets.buttons.create,
            action: 'create-order',
            name: 'create-order',
            handler: function () {
                me.confirmClosed = true;
                me.fireEvent(
                    'createOrder', me.positionsGrid, 'detail'
                );
            }
        });

        me.saveAndNewButton = Ext.create('Ext.button.Button', {
            cls: 'secondary',
            text: me.snippets.buttons.createAndNew,
            handler: function () {
                me.confirmClosed = true;
                me.fireEvent(
                    'createOrder', me.positionsGrid, 'new'
                );
            }
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'bottom',
            float: 'right',
            ui: 'shopware-ui',
            items: [
                '->',
                me.cancelButton,
                me.saveAndExitButton,
                me.saveAndNewButton,
                me.saveButton
            ]
        });
    },

    createTotalCostsOverview: function () {
        var me = this;

        return Ext.create('Shopware.apps.SwagBackendOrder.view.main.TotalCostsOverview', {
            subApplication: me.subApplication,
            orderModel: me.orderModel,
            createBackendOrderStore: me.createBackendOrderStore
        });
    },

    /**
     * checks if something was set by the user
     *
     * @returns boolean
     */
    checkOrderModel: function () {
        var me = this;

        var orderModelItems = me.orderModel.fields.items;
        for (var field = 0; field < orderModelItems.length; field++) {
            var fieldName = orderModelItems[field].name;

            switch (fieldName) {
                case 'currency':
                    continue;
                    break;
                case 'languageShopId':
                    continue;
                    break;
                case 'currencyId':
                    continue;
                    break;
                default:
                    break;
            }

            if (me.orderModel.get(fieldName) > 0) {
                return true;
            }

        }
        return false;
    }
});
//
//{/block}