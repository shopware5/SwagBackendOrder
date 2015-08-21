//{block name="backend/customer/view/detail/additional" append}
Ext.define('Shopware.apps.CreateBackendOrder..view.detail.Additional', {
    override: 'Shopware.apps.Customer.view.detail.Additional',

    /**
     * Creates the container for the "Perform order" button which
     * is displayed on bottom of the panel.
     * @return [Ext.container.Container] - Contains the perform order button and the create account button when the accountMode of the customer is set to 1
     */
    createButtonsContainer: function () {
        var me = this,
            buttons = [];

        /*{if {acl_is_allowed privilege=perform_order}}*/
        me.performOrderBtn = Ext.create('Ext.button.Button', {
            text: me.snippets.performOrderBtn,
            handler: function () {
                me.fireEvent('performOrder', me.record);
            }
        });
        buttons.push(me.performOrderBtn);

        me.performBackendOrderBtn = Ext.create('Ext.button.Button', {
            text: '{s namespace="backend/customer/view/main" name="swag_backend_order/customer/additional/create_backend_order"}Create order in the backend{/s}',
            handler: function () {
                me.fireEvent('performBackendOrder', me.record);
            }
        });
        buttons.push(me.performBackendOrderBtn);
        /*{/if}*/

        /*{if {acl_is_allowed privilege=update}}*/
        if (me.record.get('accountMode') == 1) {
            me.createAccountButton = Ext.create('Ext.button.Button', {
                text: me.snippets.createAccountBtn,
                handler: function () {
                    var tpl = me.createInfoPanelTemplate();
                    me.fireEvent('createAccount', me.record, me.infoView, tpl, me.createAccountButton);
                }
            });
            buttons.push(me.createAccountButton);
        }
        /*{/if}*/

        return Ext.create('Ext.container.Container', {
            height: 85,
            defaults: {
                margin: '5 0 0 0'
            },
            cls: Ext.baseCSSPrefix + 'button-container',
            items: buttons
        });
    }
});
//{/block}