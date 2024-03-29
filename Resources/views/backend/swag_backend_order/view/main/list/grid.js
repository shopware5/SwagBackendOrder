// {block name="backend/create_backend_order/view/list/grid"}
Ext.define('Shopware.apps.SwagBackendOrder.view.main.list.Grid', {

    extend: 'Ext.grid.Panel',

    title: 'Positionen',

    width: '100%',

    alias: 'widget.createbackendorder-position-grid',

    id: 'positionsGrid',

    renderTo: Ext.getBody(),

    priceFieldEditorKeyMap: [
        Ext.EventObject.ZERO,
        Ext.EventObject.ONE,
        Ext.EventObject.TWO,
        Ext.EventObject.THREE,
        Ext.EventObject.FOUR,
        Ext.EventObject.FIVE,
        Ext.EventObject.SIX,
        Ext.EventObject.SEVEN,
        Ext.EventObject.EIGHT,
        Ext.EventObject.NINE,
        Ext.EventObject.NUM_ZERO,
        Ext.EventObject.NUM_ONE,
        Ext.EventObject.NUM_TWO,
        Ext.EventObject.NUM_THREE,
        Ext.EventObject.NUM_FOUR,
        Ext.EventObject.NUM_FIVE,
        Ext.EventObject.NUM_SIX,
        Ext.EventObject.NUM_SEVEN,
        Ext.EventObject.NUM_EIGHT,
        Ext.EventObject.NUM_NINE
    ],

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
            add: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/toolbar/add"}Add position{/s}',
            addDiscount: '{s namespace="backend/swag_backend_order/view/grid" name="swag_backend_order/position/grid/toolbar/addDiscount"}Add Discount{/s}'
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

    initComponent: function() {
        var me = this;

        me.title = me.snippets.title;

        me.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 2,
            autoCancel: true,
            listeners: {
                beforeEdit: function(editor, event) {
                    var record = event.record;

                    // Discounts do not support inline editing!
                    if (record.get('isDiscount') === true) {
                        return false;
                    }
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
    registerEvents: function() {
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

    traceGridEvents: function() {
        var me = this;

        /**
         * initializes the product search fields
         */
        me.articleNumberSearch.on('valueselect', function(grid, record) {
            me.fireEvent('articleNumberSelect', me.rowEditing, record);
        });

        me.articleNameSearch.on('valueselect', function(grid, record) {
            me.fireEvent('articleNameSelect', me.rowEditing, record);
        });

        /**
         * deletes new rows if editing will be cancelled
         */
        me.rowEditing.on('canceledit', function(grid, eOpts) {
            me.fireEvent('cancelEdit', grid, eOpts);
        });

        me.rowEditing.on('edit', function(editor, e) {
            var productId,
                positionModel,
                productPosition,
                productModel,
                blockPrices,
                quantity;

            /**
             * only remove positions which aren't saved in the store and save the 'articleId' to open the correct product
             */
            productPosition = me.articleNameSearch.store.find('number', e.record.get('articleNumber'));
            if (productPosition > -1) {
                positionModel = e.store.getAt(e.rowIdx);

                if (!Ext.isEmpty(positionModel)) {
                    productModel = me.articleNameSearch.store.getAt(productPosition);
                    productId = productModel.get('articleId');
                    positionModel.set('articleId', productId);

                    // if there are block prices for the product, set a new price
                    blockPrices = productModel.get('blockPrices');

                    if (blockPrices !== '' && !me.isManualPriceEdit) {
                        blockPrices = Ext.JSON.decode(blockPrices);
                        quantity = positionModel.get('quantity');
                        Ext.iterate(blockPrices, function(from, price) {
                            from = parseInt(from, 10);
                            if (quantity >= from) {
                                if (me.orderModel.get('taxFree') || me.orderModel.get('displayNet')) {
                                    positionModel.set('price', price.net);
                                } else {
                                    positionModel.set('price', price.gross);
                                }
                            }
                        });
                    }
                }
            }

            me.fireEvent('calculateBasket');
        });

        me.rowEditing.on('validateedit', function(editor, e) {
            var record = me.store.getAt(e.rowIdx);

            Ext.Ajax.request({
                url: '{url action="validateEdit"}',
                params: e.newValues,
                success: function(response) {
                    var responseObj = Ext.JSON.decode(response.responseText),
                        message = '';

                    if (!responseObj.success) {
                        Ext.Array.forEach(responseObj.violations, function(item) {
                            message += item + '<br />';
                        });

                        Ext.Msg.alert('{s namespace="backend/swag_backend_order/validations" name="title"}{/s}', message);
                        editor.startEdit(record, 0);
                    }

                    if (responseObj.success) {
                        me.isManualPriceEdit = false;
                    }
                }
            });
        });
    },

    /**
     * returns the columns of the grid
     * @returns { Array }
     */
    getColumns: function() {
        var me = this;
        me.articleStore = Ext.create('Shopware.apps.SwagBackendOrder.store.Article');
        me.articleNumberSearch = me.createArticleSearch(me.articleStore, 'number', 'articleNumber', 'number');
        me.articleNameSearch = me.createArticleSearch(me.articleStore, 'number', 'articleName', 'articleName');
        me.taxStore = Ext.create('Shopware.apps.Base.store.Tax', {}).load();

        return [
            {
                header: me.snippets.columns.number,
                dataIndex: 'articleNumber',
                allowBlank: false,
                flex: 4,
                editor: me.articleNumberSearch
            },
            {
                header: me.snippets.columns.name,
                dataIndex: 'articleName',
                flex: 4,
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
                renderer: function(value, metaData, record) {
                    return me.renderPrice(value, record);
                },
                editor: me.createPriceFieldEditor()
            },
            {
                header: me.snippets.columns.total,
                dataIndex: 'total',
                flex: 1,
                renderer: function(value, metaData, record, row, col, store) {
                    var total = me.renderTotal(value, record);
                    store.getAt(row).set('total', total);

                    return total;
                }
            },
            {
                header: me.snippets.columns.tax,
                dataIndex: 'taxRate',
                flex: 1,
                renderer: function(value, metaData, record, row) {
                    return me.renderTaxRate(value, row);
                },
                editor: {
                    xtype: 'combo',
                    store: me.taxStore,
                    valueField: 'tax',
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
                        handler: function(view, rowIndex) {
                            var articleName = me.store.getAt(rowIndex).get('articleName');

                            Ext.MessageBox.confirm(
                                me.snippets.confirmMsg.deleteRowTitle,
                                me.snippets.confirmMsg.deleteRowMsg1 + ' <b>' + articleName + '</b> ' + me.snippets.confirmMsg.deleteRowMsg2,
                                function(button) {
                                    if (button === 'yes') {
                                        me.store.removeAt(rowIndex);
                                    }
                                },
                                this);
                        }
                    },
                    {
                        iconCls: 'sprite-inbox',
                        handler: function(view, rowIndex) {
                            var store = view.getStore(),
                                record = store.getAt(rowIndex);

                            me.fireEvent('openArticle', record);
                        }
                    }
                ]
            }
        ];
    },

    createPriceFieldEditor: function() {
        var me = this;

        return Ext.create('Ext.form.field.Number', {
            allowBlank: false,
            minValue: 0,
            name: 'priceField',
            enableKeyEvents: true,
            listeners: {
                keydown: function(field, event) {
                    if (Ext.Array.contains(me.priceFieldEditorKeyMap, event.getCharCode())) {
                        me.isManualPriceEdit = true;
                    }
                }
            }
        });
    },

    /**
     * creates toolbar items and return the toolbar for the position grid
     * it contains the add and delete button
     *
     * @returns { Ext.toolbar.Toolbar }
     */
    createToolbarItems: function() {
        var me = this;

        me.addPositionButton = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-plus-circle-frame',
            text: me.snippets.toolbar.add,
            action: 'addPosition',
            handler: function() {
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
            handler: function() {
                var selModel = me.getSelectionModel();
                var sel = selModel.selected.items;

                var title = me.snippets.confirmMsg.deleteMarkedTitle;
                var message = me.snippets.confirmMsg.deleteMarkedMsg1 + ' <b>' + sel.length + '</b> ' + me.snippets.confirmMsg.deleteMarkedMsg2;

                if (sel.length === 1) {
                    title = me.snippets.confirmMsg.deleteRowTitle;
                    message = me.snippets.confirmMsg.deleteRowMsg1 + ' <b>' + sel[0].get('articleName') + '</b> ' + me.snippets.confirmMsg.deleteRowMsg2;
                }

                Ext.MessageBox.confirm(
                    title,
                    message,
                    function(button) {
                        if (button === 'yes') {
                            for (var i = 0; i < sel.length; i++) {
                                me.store.remove(sel[i]);
                            }
                        }
                    },
                    this);
            }
        });

        me.addDiscountButton = Ext.create('Ext.button.Button', {
            iconCls: 'sprite-money--plus',
            text: me.snippets.toolbar.addDiscount,
            handler: Ext.bind(me.showDiscountModal, me),
            itemId: 'addDiscountButton'
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            ui: 'shopware-ui',
            items: [
                me.addPositionButton,
                me.deletePositionsButton,
                me.addDiscountButton
            ]
        });
    },

    showDiscountModal: function() {
        Ext.create('Shopware.apps.SwagBackendOrder.view.main.discount.Window').show();
    },

    /**
     * Creates the grid selection model for checkboxes
     *
     * @return { Ext.selection.CheckboxModel } grid selection model
     */
    getGridSelModel: function() {
        var me = this;

        return Ext.create('Ext.selection.CheckboxModel', {
            listeners: {
                // Unlocks the save button if the user has checked at least one checkbox
                selectionchange: function(sm, selections) {
                    me.deletePositionsButton.setDisabled(selections.length === 0);
                }
            }
        });
    },

    /**
     * @param { Ext.data.Store } store
     * @param { string } returnValue
     * @param { string } name
     * @param { string } displayField
     * @return { Shopware.apps.SwagBackendOrder.view.main.list.ArticleSearchField }
     */
    createArticleSearch: function(store, returnValue, name, displayField) {
        displayField = displayField || 'name';

        return Ext.create('Shopware.apps.SwagBackendOrder.view.main.list.ArticleSearchField', {
            store: store,
            name: name,
            displayField: displayField,
            valueFiled: returnValue,
            multiSelect: false
        });
    },

    /**
     * calculates the total sum of a position
     *
     * @param value
     * @param record
     *
     * @returns string
     */
    renderTotal: function(value, record) {
        var me = this,
            currencyStore, currencyStoreIndex, symbol, total;

        currencyStore = me.subApplication.getStore('Currency');
        currencyStoreIndex = currencyStore.findExact('selected', 1);

        symbol = currencyStore.getAt(currencyStoreIndex).get('symbol');

        if (record.get('isDiscount') && record.get('discountType') === 0) {
            total = record.get('total') * 1; // * 1 to "convert" to float so toFixed can to its stuff.
        } else {
            total = record.get('quantity') * record.get('price');
        }

        total = total.toFixed(2);
        total = total + ' ' + symbol;

        return total;
    },

    /**
     *
     * @param value
     * @param record
     *
     * @returns string
     */
    renderPrice: function(value, record) {
        var me = this,
            currencyStore, currencyStoreIndex, symbol;

        if (record === undefined) {
            return value;
        }

        if (record.get('isDiscount') && record.get('discountType') === 0) {
            return value + ' %';
        }

        currencyStore = me.subApplication.getStore('Currency');
        currencyStoreIndex = currencyStore.findExact('selected', 1);

        symbol = currencyStore.getAt(currencyStoreIndex).get('symbol');

        return value + ' ' + symbol;
    },

    /**
     *
     * @param value
     * @param row
     * @returns int
     */
    renderTaxRate: function(value, row) {
        var me = this,
            taxIndex = me.taxStore.findExact('tax', value),
            taxRate = value,
            taxId = 0;

        if (taxIndex > -1) {
            taxRate = me.taxStore.getAt(taxIndex).get('tax');
            taxId = me.taxStore.getAt(taxIndex).get('id');

            me.store.getAt(row).set('taxRate', taxRate);
            me.store.getAt(row).set('taxId', taxId);
        }
        return taxRate;
    }
});
// {/block}
