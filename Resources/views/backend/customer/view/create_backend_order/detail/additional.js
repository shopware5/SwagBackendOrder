
//{block name="backend/customer/view/detail/additional" append}
Ext.define('Shopware.apps.CreateBackendOrder..view.detail.Additional', {
    override: 'Shopware.apps.Customer.view.detail.Additional',

    /**
     * Adds the "perform backend order" button to the buttons container.
     *
     * @return { Ext.container.Container }
     */
    createButtonsContainer: function() {
        var me = this,
            container = me.callParent(arguments);

        // Reset container height to "auto-mode"
        delete container.height;

        // Add "perform backend order" button to container
        container.add(Ext.create('Ext.button.Button', {
            margin: '5 0 0 0',
            text: '{s namespace="backend/customer/view/main" name="swag_backend_order/customer/additional/create_backend_order"}Create order in the backend{/s}',
            handler: function () {
                me.fireEvent('performBackendOrder', me.record);
            }
        }));

        return container;
    }
});
//{/block}
