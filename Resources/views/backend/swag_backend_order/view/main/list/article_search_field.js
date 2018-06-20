//
// {namespace name="backend/swag_backend_order/view/search"}
// {block name="backend/create_backend_order/view/list/article_search_field"}
Ext.define('Shopware.apps.SwagBackendOrder.view.main.list.ArticleSearchField', {
    extend: 'Ext.form.FieldContainer',
    alias: 'widget.backendorder-articlesearchfield',

    margin: '8 2 3 2',
    layout: 'anchor',
    allowBlank: true,
    minChars: 1,

    mixins: {
        formField: 'Ext.form.field.Base'
    },

    defaults: {
        anchor: '100%'
    },

    initComponent: function () {
        var me = this;

        me.timeOut = null;

        me.items = me.createItems();

        me.registerEvents();

        me.callParent(arguments);

        if (me.value) {
            me.setValue(me.value);
        }
    },

    /**
     * @returns { Array }
     */
    createItems: function () {
        return [
            this.createSearchField()
        ];
    },

    /**
     * @returns { Shopware.form.field.PagingComboBox }
     */
    createSearchField: function () {
        var me = this,
            config = me.getComboConfig(),
            events, fireComboBoxEvents;

        fireComboBoxEvents = function (event) {
            me.combo.on(event, function () {
                var args = [event];
                for (var i = 0; i <= arguments.length; i++) {
                    args.push(arguments[i]);
                }
                return me.fireEvent.apply(me, args);
            });
        };

        me.combo = Ext.create('Shopware.form.field.PagingComboBox', config);
        events = Object.keys(me.combo.events);
        Ext.each(events, fireComboBoxEvents);

        return me.combo;
    },

    /**
     * @returns { object }
     */
    getComboConfig: function () {
        var me = this,
            config = {
                disableLoadingSelectedName: true,
                emptyText: me.emptyText,
                helpText: me.helpText,
                helpTitle: me.helpTitle,
                queryMode: 'remote',
                store: me.store,
                allowBlank: me.allowBlank,
                isFormField: false,
                style: 'margin-right: 0 !important',
                pageSize: me.store.pageSize,
                labelWidth: 180,
                minChars: 0,
                displayField: me.displayField,
                valueField: me.valueField,
                displayTpl: null,
                defaultPageSize: 10
            };

        config.tpl = Ext.create('Ext.XTemplate',
            '<tpl for=".">',
                '<div class="x-boundlist-item">' +
                    // active renderer
                    '<tpl if="articleActive && variantActive">' +
                        '[{s name=active_single_selection}{/s}]' +
                    '<tpl else>' +
                        '[{s name=inactive_single_selection}{/s}]' +
                    '</tpl>' +

                    // number + data renderer
                    ' {literal}<b>{number}</b> - {name}{/literal}' +

                    // additional text renderer
                    '<tpl if="additionalText">' +
                        '<i>{literal} ({additionalText})</i>{/literal}' +
                    '</tpl>',
                '</div>',
            '</tpl>'
        );

        return config;
    },

    /**
     * @returns { string }
     */
    getValue: function () {
        return this.combo.getValue();
    },

    /**
     * @param  { string } value
     */
    setValue: function (value) {
        var me = this;

        if (!value) {
            me.combo.clearValue();
        } else {
            me.combo.setValue(value);
        }
    },

    registerEvents: function () {
        var me = this;

        me.listeners = {
            select: Ext.bind(me.onSelect, me)
        };

        me.store.on('beforeload', Ext.bind(me.onBeforeLoadStore, me));
    },

    /**
     * @param { Ext.form.field.ComboBox } combo
     * @param { Array } records
     */
    onSelect: function (combo, records) {
        var me = this,
            selectedRecord = records[0];

        me.fireEvent('valueselect', me, selectedRecord);
    },

    /**
     * @returns { object }
     */
    getSubmitData: function() {
        var me = this,
            value = { };

        value[me.name] = me.getValue();

        return value;
    },

    /**
     * @param { Ext.data.Store } store
     * @param { Ext.data.Operation } operation
     */
    onBeforeLoadStore: function (store, operation) {
        this.fireEvent('beforeload-productstore', this, operation);
    }
});
// {/block}
