//{block name="backend/customer/view/detail/base" append}
//
Ext.define('Shopware.apps.CreateBackendOrder.view.Base', {
    override: 'Shopware.apps.Customer.view.detail.Base',

    /**
     * Creates the left container of the base field set.
     * Contains the email field and the combo boxes for
     * the shop and customer group
     *
     * @return [Array] Contains the different form field of the left container
     */
    createBaseFormLeft: function () {
        var me = this,
            baseFormLeft = me.callParent(arguments),
            email;

        if (me.record.get('guest')) {
            email = Ext.create('Ext.form.field.Text', {
                fieldLabel: me.snippets.email.label,
                labelWidth: 150,
                name: 'email',
                supportText: '{s namespace="backend/customer/view/main" name="swag_backend_order/customer/mail/support_text"}Guest email:{/s} ' + me.record.get('email'),
                disabled: false,
                allowBlank: false,
                required: true,
                enableKeyEvents: true,
                checkChangeBuffer: 700,
                vtype: 'remote',
                validationUrl: null,
                validationRequestParams: { 'param': me.record.get('id'), 'subshopId': me.record.get('shopId') },
                validationErrorMsg: me.snippets.email.message,
                listeners: {
                    scope: me,
                    afterrender: function (field) {
                        //only validates the email field if the mail is not the guest account email which can be configured in the plugin config
                        if (field.getValue() != me.record.get('email')) {
                            window.setTimeout(function () {
                                field.validationUrl = '{url action="validateEmail"}';
                            }, 500);
                        }

                    }
                }
            });

            baseFormLeft[0] = email;
        }

        baseFormLeft.push = {
            /*{if {config name=shopwareManagedCustomerNumbers}==1}*/
            xtype: 'displayfield',
            /*{/if}*/
            name: 'billing[number]',
            fieldLabel: me.snippets.number.label,
            helpText: me.snippets.number.helpText,
            helpWidth: 360,
            helpTitle: me.snippets.number.helpTitle
        };

        return baseFormLeft;
    },

    createBaseFormRight: function () {
        var me = this,
            baseFormRight = me.callParent(arguments);

        if (me.record.get('guest')) {
            return [];
        }

        return baseFormRight;
    }
});

//{/block}