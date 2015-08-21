//
//{block name="backend/create_backend_order/view/list/grid"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.list.Grid', {

    extend: 'Ext.grid.Panel',

    title: 'Positionen',

    width: '100%',

    alias: 'widget.createbackendorder-position-grid',

    id: 'positionsGrid',

    renderTo: Ext.getBody(),

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/title"}Positions{/s}',
        columns: {
            number: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/number"}Articlenumber{/s}',
            name: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/name"}Name{/s}',
            quantity: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/quantity"}Quantity{/s}',
            price: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/price"}Price{/s}',
            total: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/total"}Total{/s}',
            tax: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/tax"}Tax in %{/s}',
            inStock: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/instock"}Instock{/s}'
        },
        toolbar: {
            delete: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/toolbar/delete"}Delete all selected{/s}',
            add: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/toolbar/add"}Add position{/s}'
        },
        error: {
            articleNumberTitle: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/article_number_title"}Invalid article{/s}',
            articleNumberText: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/article_number_text"}Article number is invalid: {/s}',
            articleNameTitle: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/article_name_title"}Invalid article{/s}',
            articleNameText: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/article_name_text"}Article name is invalid: {/s}',
            esdTitle: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/esd_title"}Invalid article{/s}',
            esdText: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/error/esd_text"}ESD article are not available in the backend order module: {/s}'
        },
        confirmMsg: {
            deleteRowTitle: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteRowTitle"}Delete position{/s}',
            deleteRowMsg1: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteRowMsg1"}Are you sure you want to delete{/s}',
            deleteRowMsg2: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteRowMsg2"}?{/s}',
            deleteMarkedTitle: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteMarkedTitle"}Delete all marked articles{/s}',
            deleteMarkedMsg1: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteMarkedMsg1"}Are you sure you want to delete{/s}',
            deleteMarkedMsg2: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/confirmMsg/deleteMarkedMsg2"}positions?{/s}'
        }
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;

        me.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 2,
            autoCancel: true,
            listeners: {
                beforeedit: function (editor, record) {
                    me.record = record;

                    //Sets the column for the user input
                    var columns = editor.editor.items.items;
                    columns[1].setValue(me.record.record.get('articleNumber'));
                    columns[2].setValue(me.record.record.get('articleName'));
                }
            }
        });

        me.plugins = [me.rowEditing];

        me.columns = me.getColumns();

        me.tbar = me.createToolbarItems();
        me.selModel = me.getGridSelModel();

        me.store = me.subApplication.getStore('Position');

        me.callParent(arguments);
        me.traceGridEvents();
    },

    /**
     * Defines additional events which will be
     * fired from the component
     *
     * @return void
     */
    registerEvents: function () {
        this.addEvents(
            'addPosition',

            /**
             * Event will be fired when the user search for an article name in the row editor
             * and selects an article in the drop down menu.
             *
             * @event articleNameSelect
             * @param [object] editor - Ext.grid.plugin.RowEditing
             * @param [string] value - Value of the Ext.form.field.Trigger
             * @param [object] record - Selected record
             */
            'articleNameSelect',

            /**
             * Event will be fired when the user search for an article number in the row editor
             * and selects an article in the drop down menu.
             *
             * @event articleNameSelect
             * @param [object] editor - Ext.grid.plugin.RowEditing
             * @param [string] value - Value of the Ext.form.field.Trigger
             * @param [object] record - Selected record
             */
            'articleNumberSelect',

            /**
             * Event will be fired when the user adds a new order position and before he save
             * this position he clicks the cancel button.
             *
             * @event cancelEdit
             * @param [Ext.data.Store] - The position store
             * @param [Ext.data.Model] - The edited record
             */
            'cancelEdit'
        );
    },

    traceGridEvents: function () {
        var me = this;

        /**
         * initalizes the article search fields
         */
        me.articleNumberSearch.on('valueselect', function (field, value, hiddenValue, record) {
            me.fireEvent('articleNumberSelect', me.rowEditing, value, record);
        });

        me.articleNameSearch.on('valueselect', function (field, value, hiddenValue, record) {
            me.fireEvent('articleNameSelect', me.rowEditing, value, record);
        });

        /**
         * deletes new rows if editing will be cancelled
         */
        me.rowEditing.on('canceledit', function (grid, eOpts) {
            me.fireEvent('cancelEdit', grid, eOpts);
        });

        me.on('canceledit', function () {
            me.articleNumberSearch.getDropDownMenu().hide();
            me.articleNameSearch.getDropDownMenu().hide();
        }, me);

        me.rowEditing.on('edit', function (editor, e) {
            /**
             * only remove positions which aren't saved in the store and save the articleid to open the correct article
             */
            var articlePosition = me.articleNameSearch.dropDownStore.find('number', e.record.get('articleNumber'));
            if (articlePosition > -1) {
                var articleId = me.articleNameSearch.dropDownStore.getAt(articlePosition).get('articleId');
                if ((e.store.getAt(e.rowIdx) instanceof Ext.data.Model)) {
                    e.store.getAt(e.rowIdx).set('articleId', articleId);
                }
            }

            articlePosition = me.articleNumberSearch.dropDownStore.find('number', e.record.get('articleNumber'));
            if (articlePosition > -1) {
                articleId = me.articleNumberSearch.dropDownStore.getAt(articlePosition).get('articleId');
                if ((e.store.getAt(e.rowIdx) instanceof Ext.data.Model)) {
                    e.store.getAt(e.rowIdx).set('articleId', articleId);
                }
            }
        });

        me.rowEditing.on('validateedit', function (editor, e) {
            var record = me.store.getAt(e.rowIdx);

            Ext.Ajax.request({
                url: '{url action="validateEdit"}',
                params: record.data,
                success: function (response) {
                    var responseObj = Ext.JSON.decode(response.responseText);

                    if (typeof responseObj.data !== 'undefined') {
                        switch (responseObj.data.error) {
                            case 'esd':
                                Ext.Msg.alert(me.snippets.error.esdTitle, me.snippets.error.esdText + responseObj.data.articleNumber);
                                record.set('articleNumber', '');
                                record.set('articleName', '');
                                record.set('price', 0);
                                record.set('quantity', 0);
                                record.set('inStock', 0);
                                record.set('articleId', 0);
                                editor.startEdit(record, 0);
                                break;
                            case 'articleNumber':
                                Ext.Msg.alert(me.snippets.error.articleNumberTitle, me.snippets.error.articleNumberText + responseObj.data.articleNumber);
                                editor.startEdit(record, 0);
                                break;
                            case 'articleName':
                                Ext.Msg.alert(me.snippets.error.articleNameTitle, me.snippets.error.articleNameText + responseObj.data.articleName);
                                editor.startEdit(record, 0);
                                break;
                            default:
                                break;
                        }
                    }
                },
                failure: function (response) {
                }
            });
        });
    },

    /**
     * returns the columns of the grid
     */
    getColumns: function () {
        var me = this;
        me.articleNumberSearch = me.createArticleSearch('number', 'name', 'articleNumber');
        me.articleNameSearch = me.createArticleSearch('name', 'number', 'articleName');
        me.taxStore = Ext.create('Shopware.apps.Base.store.Tax', {}).load();

        var columns = [
            {
                header: me.snippets.columns.number,
                dataIndex: 'articleNumber',
                allowBlank: false,
                flex: 2,
                editor: me.articleNumberSearch
            },
            {
                header: me.snippets.columns.name,
                dataIndex: 'articleName',
                flex: 2,
                editor: me.articleNameSearch
            },
            {
                header: me.snippets.columns.quantity,
                dataIndex: 'quantity',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    minValue: 1
                }
            },
            {
                header: me.snippets.columns.price,
                dataIndex: 'price',
                flex: 1,
                renderer: function (value, metaData, record, row, col, store, gridView) {
                    return me.renderPrice(value, record);
                },
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    minValue: 0
                }
            },
            {
                header: me.snippets.columns.total,
                dataIndex: 'total',
                flex: 1,
                renderer: function (value, metaData, record, row, col, store, gridView) {
                    var total = me.renderTotal(value, record);
                    store.getAt(row).set('total', total);

                    return total;
                }
            },
            {
                header: me.snippets.columns.tax,
                dataIndex: 'taxRate',
                flex: 1,
                renderer: function (value, metaData, record, row, col, store, gridView) {
                    return me.renderTaxRate(value, row, store);
                },
                editor: {
                    xtype: 'combo',
                    store: me.taxStore,
                    valueField: 'id',
                    displayField: 'tax',
                    editable: false
                }
            },
            {
                header: me.snippets.columns.inStock,
                dataIndex: 'inStock',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false
                }
            },
            {
                /**
                 * Special column type which provides
                 * clickable icons in each row
                 */
                xtype: 'actioncolumn',
                width: 90,
                items: [
                    {
                        iconCls: 'sprite-minus-circle-frame',
                        handler: function (view, rowIndex, colIndex, item) {
                            var articleName = me.store.getAt(rowIndex).get('articleName');

                            Ext.MessageBox.confirm(
                                me.snippets.confirmMsg.deleteRowTitle,
                                me.snippets.confirmMsg.deleteRowMsg1 + ' <b>' + articleName + '</b> ' + me.snippets.confirmMsg.deleteRowMsg2,
                                function (button) {
                                    if (button == 'yes') {
                                        me.store.removeAt(rowIndex);
                                    }
                                },
                                this);
                        }
                    },
                    {
                        iconCls: 'sprite-inbox',
                        handler: function (view, rowIndex, colIndex, item) {
                            var store = view.getStore(),
                                record = store.getAt(rowIndex);

                            me.fireEvent('openArticle', record);
                        }
                    }
                ]
            }
        ];

        return columns;
    },

    /**
     * creates toolbar items and return the toolbar for the position grid
     * it contains the add and delete button
     *
     * @returns [Ext.toolbar.Toolbar]
     */
    createToolbarItems: function () {
        var me = this;

        me.addPositionButton = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle-frame',
            text: me.snippets.toolbar.add,
            action: 'addPosition',
            handler: function (record) {
                me.rowEditing.cancelEdit();

                var r = Ext.create('Shopware.apps.SwagBackendOrder.model.Position', {
                    quantity: 1,
                    price: '0',
                    taxRate: '19',
                    inStock: '0'
                });

                me.store.insert(me.store.count(), r);
                me.rowEditing.startEdit(me.store.count() - 1, 0);
            }
        });

        me.deletePositionsButton = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-minus-circle-frame',
            text: me.snippets.toolbar.delete,
            disabled: true,
            handler: function () {
                var selModel = me.getSelectionModel();
                var sel = selModel.selected.items;

                var title = me.snippets.confirmMsg.deleteMarkedTitle;
                var message = me.snippets.confirmMsg.deleteMarkedMsg1 + ' <b>' + sel.length + '</b> ' + me.snippets.confirmMsg.deleteMarkedMsg2;

                if (sel.length == 1) {
                    title = me.snippets.confirmMsg.deleteRowTitle;
                    message = me.snippets.confirmMsg.deleteRowMsg1 + ' <b>' + sel[0].get('articleName') + '</b> ' + me.snippets.confirmMsg.deleteRowMsg2;
                }

                Ext.MessageBox.confirm(
                    title,
                    message,
                    function (button) {
                        if (button == 'yes') {
                            for (var i = 0; i < sel.length; i++) {
                                me.store.remove(sel[i]);
                            }
                        }
                    },
                    this);

            }
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            ui: 'shopware-ui',
            items: [
                me.addPositionButton,
                me.deletePositionsButton
            ]
        });
    },

    /**
     * Creates the grid selection model for checkboxes
     *
     * @return [Ext.selection.CheckboxModel] grid selection model
     */
    getGridSelModel: function () {
        var me = this;

        var selModel = Ext.create('Ext.selection.CheckboxModel', {
            listeners: {
                // Unlocks the save button if the user has checked at least one checkbox
                selectionchange: function (sm, selections) {
                    me.deletePositionsButton.setDisabled(selections.length === 0);
                }
            }
        });
        return selModel;
    },

    /**
     *
     * @param returnValue
     * @param hiddenReturnValue
     * @param name
     * @return Shopware.form.field.ArticleSearch
     */
    createArticleSearch: function (returnValue, hiddenReturnValue, name) {
        var me = this;
        me.articleStore = me.subApplication.getStore('Article');

        return Ext.create('Shopware.apps.SwagBackendOrder.view.main.list.ArticleSearchField', {
            name: name,
            returnValue: returnValue,
            hiddenReturnValue: hiddenReturnValue,
            articleStore: me.articleStore,
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
     * calculates the total sum of a position
     *
     * @param value
     * @param record
     * @returns number
     */
    renderTotal: function (value, record) {
        var me = this;

        var currencyStore = me.subApplication.getStore('Currency');
        var currencyStoreIndex = currencyStore.findExact('selected', 1);

        var symbol = currencyStore.getAt(currencyStoreIndex).get('symbol');
        var total = record.data.quantity * record.data.price;

        total = total.toFixed(2);
        total = total + ' ' + symbol;
        return total;
    },

    /**
     *
     * @param value
     * @param record
     * @returns string
     */
    renderPrice: function (value, record) {
        var me = this;

        var currencyStore = me.subApplication.getStore('Currency');
        var currencyStoreIndex = currencyStore.findExact('selected', 1);

        var symbol = currencyStore.getAt(currencyStoreIndex).get('symbol');

        return value + ' ' + symbol;
    },

    /**
     *
     * @param value
     * @param row
     * @param store
     * @returns int
     */
    renderTaxRate: function (value, row, store) {
        var me = this,
            taxIndex = me.taxStore.findExact('id', value);

        var taxRate = value;
        var taxId = 0;
        if (taxIndex > -1) {
            taxRate = me.taxStore.getAt(taxIndex).get('tax');
            taxId = me.taxStore.getAt(taxIndex).get('id');

            me.store.getAt(row).set('taxRate', taxRate);
            me.store.getAt(row).set('taxId', taxId);
        }
        return taxRate;
    }
});
//{/block}