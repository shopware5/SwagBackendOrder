//
//{block name="backend/create_backend_order/view/list/positions"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.list.Positions', {

    /**
     * extends from the standard ExtJs Container
     */
    extend: 'Ext.container.Container',

    /**
     * defines an alternate class name for an easier identification
     */
    alternateClassName: 'SwagBackendOrder.view.main.list.Positions',

    alias: 'widget.create-order-positions-container',

    layout: {
        type: 'hbox',
        align: 'stretch'
    },

    flex: 1,

    defaults: {
        height: 260
    },

    autoScroll: true,

    initComponent: function () {
        var me = this;

        me.items = [
            me.createOrderPositionGrid()
        ];

        me.callParent(arguments);
    },

    /**
     * create the positions grid where positions can be added or deleted
     */
    createOrderPositionGrid: function () {
        var me = this;

        me.rowEditor = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToMoveEditor: 1,
            autoCancel: true
        });

        return me.orderPositionGrid = Ext.create('Shopware.apps.SwagBackendOrder.view.main.list.Grid', {
            subApplication: me.subApplication,
            orderModel: me.orderModel
        });
    }

});
//
//{/block}