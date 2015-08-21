//
//{block name="backend/create_backend_order/view/customer_search"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.CustomerSearch', {

    extend: 'Shopware.form.field.ArticleSearch',

    /**
     * Defines alternate names for this class
     * @array
     */
    alternateClassName: ['SwagBackendOrder.view.main.CustomerSearch'],

    /**
     * List of short aliases for class names. Most useful for defining xtypes for widgets
     * @array
     */
    alias: 'widget.createbackendorder-customersearch',

    /**
     * List of classes that have to be loaded before instantiating this class
     * @array
     */
    requires: [
        'Ext.form.field.Trigger',
        'Ext.view.View',
        'Ext.form.field.Hidden',
        'Ext.XTemplate',
        'Shopware.apps.SwagBackendOrder.model.Customer',
        'Shopware.apps.SwagBackendOrder.store.Customer',
        'Ext.grid.Panel'
    ],

    /**
     * Default return value which will be set into the search field
     * if the user clicks on an entry in the drop down menu.
     * @string
     */
    returnValue: 'email',

    /**
     * Return value which will be set into an hidden input field
     * if the user clicks on an entry in the drop down menu.
     * @string
     */
    hiddenReturnValue: 'customerId',

    /**
     * Name attribute of the search field.
     * @string
     */
    searchFieldName: 'live-customer-search',

    /**
     * Name attribute of the hidden field.
     * @string
     */
    hiddenFieldName: 'hidden-customer-search',

    /**
     * Is true if a value is selected from the drop down box,
     * after that it is setted to false again
     * @boolean
     */
    valueSelected: false,

    /**
     * Store for selected customers.
     * @object
     */
    multiSelectStore: Ext.create('Ext.data.Store', {
        model: 'Shopware.apps.SwagBackendOrder.model.Customer'
    }),

    /**
     * Initializes the Live Article Search component
     *
     * @public
     * @return void
     */
    initComponent: function () {
        var me = this;
        me.registerEvents();

        if (!(me.customerStore instanceof Ext.data.Store)) {
            me.customerStore = Ext.create('Shopware.apps.SwagBackendOrder.store.Customer');
        }

        // We need to filter the store on loading to prevent to show the first article in the store on startup
        me.dropDownStore = Ext.create('Shopware.apps.SwagBackendOrder.store.Customer', {
            listeners: {
                single: true,
                load: function () {
                    me.loadCustomerStore(me.customerStore);
                }
            }
        });
        //article store passed to the component?
        if (Ext.isObject(me.customerStore) && me.customerStore.data.items.length > 0) {
            me.loadCustomerStore(me.customerStore);
        }

        me.hiddenField = me.createHiddenField();
        me.searchField = me.createSearchField();
        me.dropDownMenu = me.createDropDownMenu();
        me.items = [me.hiddenField, me.searchField, me.dropDownMenu];

        // Create an store and a grid for the selected articles
        if (!me.multiSelect) {
            delete me.multiSelectStore;
        } else {
            me.multiSelectGrid = me.createMultiSelectGrid();
            me.items.push(me.multiSelectGrid);
        }

        // Are we're having an store to preselect articles?
        if (me.customerStore && me.multiSelect) {
            me.multiSelectGrid.show();
        }
        me.dropDownStore.on('datachanged', me.onSearchFinish, me);

        /**
         * if the user id was passed to the subApplication
         * the name will be setted in the search field
         */
        var originalCustomerStore = me.subApplication.getStore('Customer');
        originalCustomerStore.on('load', function () {
            if (originalCustomerStore.count() === 1) {
                var billingModel = originalCustomerStore.getAt(0).billing().getAt(0);
                me.setValue(
                    billingModel.get('firstName') +
                    ' ' +
                    billingModel.get('lastName')
                );
            }
        });

        this.superclass.superclass.initComponent.call(this);
    },

    /**
     * Creates the searchfield for the live search.
     *
     * @private
     * @return [object] input -  created Ext.form.field.Trigger
     */
    createSearchField: function () {
        var me = this;

        fieldConfig = Ext.apply({
            componentLayout: 'textfield',
            triggerCls: 'reset',
            padding: '10 0 0 0',
            emptyText: '{s namespace="backend/swag_backend_order/view/customer_search" name="customer_search/search_default_text"}Customer search...{/s}',
            fieldLabel: (me.fieldLabel || undefined),
            cls: 'searchfield', //Ext.baseCSSPrefix + 'search-article-live-field'
            name: me.searchFieldName,
            enableKeyEvents: true,
            anchor: (me.anchor || undefined),
            onTriggerClick: function () {
                this.reset();
                this.focus();
                this.setHideTrigger(true);
                me.dropDownMenu.hide();
                me.fireEvent('reset', me, this);
            },
            hideTrigger: true,
            listeners: {
                scope: me,
                keyup: me.onSearchKeyUp,
                blur: me.onSearchBlur,
                change: function (newValue) {
                    if (me.valueSelected == true) {
                        me.fireEvent('selectCustomer', newValue, me.customerId);
                        me.valueSelected = false;
                    }
                }
            }
        }, me.formFieldConfig);

        var input = Ext.create('Ext.form.field.Trigger', fieldConfig);
        return input;
    },

    /**
     * Creates the drop down menu which represents the
     * search result.
     *
     * @private
     * @return [object] view - created Ext.view.View
     */
    createDropDownMenu: function () {
        var me = this,
            view = Ext.create('Ext.view.View', {
                floating: true,
                autoShow: false,
                autoRender: true,
                hidden: true,
                shadow: false,
                width: 222,
                toFrontOnShow: true,
                focusOnToFront: false,
                store: me.dropDownStore,
                cls: Ext.baseCSSPrefix + 'search-article-live-drop-down',
                overItemCls: Ext.baseCSSPrefix + 'drop-down-over',
                selectedItemCls: Ext.baseCSSPrefix + 'drop-down-over',
                trackOver: true,
                itemSelector: 'div.item',
                singleSelect: true,
                listeners: {
                    scope: me,
                    itemclick: function (view, record) {
                        me.onSelectCustomer(view, record);
                    }
                },
                tpl: me.createDropDownMenuTpl()
            });

        return view;
    },

    /**
     * Creates the template for the search result.
     *
     * TODO: no match view
     * @private
     * @return [object] created Ext.XTemplate
     */
    createDropDownMenuTpl: function () {
        var me = this;

        return new Ext.XTemplate(
            '<div class="header">',
            '<div class="header-inner">',
            '<div class="arrow">&nbsp;</div>',
            '<span class="title">',
            'Kunden',
            '</span>',
            '</div>',
            '</div>',
            '</div>',
            '<div class="content">',
            '{literal}<tpl if="total==0">',
            '<div class="item">',
            '<span class="name">Keine Ergebnisse gefunden.</span>',
            '</div>',
            '</tpl>',
            '<tpl for=".">',
            '<div class="item">',
            '<strong class="name">{customerName}</strong>',
            '<tpl if="customerCompany">',
            '<span class="company">{customerCompany}</span><br />',
            '</tpl>',
            '<span class="email">{email}, {customerNumber}</span>',
            '</div>',
            '</tpl>{/literal}',
            '</div>'
        );
    },

    /**
     * Attempts to destroy and then remove a set of named properties of the passed object.
     *
     * @public
     * @return void
     */
    destroy: function () {
        Ext.destroyMembers(this, 'mulitSelectGrid', 'hiddenField', 'searchField', 'dropDownMenu', 'multiSelectToolbar');
    },

    /**
     * Helper method which loads a store into the grid if the parameter this.article is
     * passed to the constructor of this component.
     *
     * @public
     * @param [object] store - Ext.data.Store which contains preselected articles.
     * @return void
     */
    loadCustomerStore: function (store) {
        var me = this;
        Ext.each(store.data.items, function (record) {
            delete record.internalId;
            me.multiSelectStore.add(record);
        });
        return true;
    },

    /**
     * Event listener method which will be fired when the user selects
     * an article in the drop down menu.
     *
     * @event itemclick
     * @public
     * @return void
     */
    onSelectCustomer: function (view, record) {
        var me = this;

        //sets true if a value was selected to search the customer in the main controller
        me.valueSelected = true;
        me.customerId = record.get(me.hiddenReturnValue);

        if (!me.multiSelect) {
            me.getSearchField().setValue(record.get(me.returnValue));
            me.getHiddenField().setValue(record.get(me.hiddenReturnValue));
            me.returnRecord = record;
            me.getDropDownMenu().hide();
        } else {
            if (me.getMultiSelectGrid().isHidden()) {
                me.getMultiSelectGrid().show();
            }
            delete record.internalId;
            me.multiSelectStore.add(record);
            me.getDropDownMenu().getSelectionModel().deselectAll();
        }
        me.fireEvent('valueselect', me, record.get(me.returnValue), record.get(me.hiddenReturnValue), record);
    }
});
//{/block}