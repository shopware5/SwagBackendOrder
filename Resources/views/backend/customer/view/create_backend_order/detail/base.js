// {block name="backend/customer/view/detail/base"}
// {$smarty.block.parent}
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
            passwordElements,
            email;

        if (me.record.get('guest')) {
            email = Ext.create('Ext.form.field.Text', {
                fieldLabel: me.snippets.email.label,
                labelWidth: 155,
                anchor: '95%',
                name: 'email',
                supportText: '{s namespace="backend/customer/view/main" name="swag_backend_order/customer/mail/support_text"}Guest email:{/s} ' + me.record.get('email'),
                disabled: false,
                allowBlank: false,
                required: true,
                enableKeyEvents: true,
                checkChangeBuffer: 700,
                vtype: 'remote',
                validationUrl: null,
                validationRequestParams: { 'isBackendOrder': true, 'param': me.record.get('id'), 'subshopId': me.record.get('shopId') },
                validationErrorMsg: me.snippets.email.message,
                listeners: {
                    scope: me,
                    afterrender: function (field) {
                        window.setTimeout(function () {
                            field.validationUrl = '{url action="validateEmail"}';
                        }, 500);
                    }
                }
            });

            passwordElements = this.getPasswordElements(baseFormLeft);

            // Remove password elements
            baseFormLeft = Ext.Array.remove(baseFormLeft, passwordElements[0]); // Password input
            baseFormLeft = Ext.Array.remove(baseFormLeft, passwordElements[1]); // Password confirm input

            baseFormLeft[0] = email;
        }

        return baseFormLeft;
    },

    createBaseFormRight: function () {
        var me = this,
            baseFormRight = me.callParent(arguments);

        if (me.record.get('guest')) {
            return [];
        }

        return baseFormRight;
    },

    /**
     * Iterates the given elements and finds the elements for the password field and password confirm field.
     * Returns the result as an array.
     *
     * This function is required, because the view could have been extended already and the default indexes for the
     * password fields may not match anymore.
     *
     * @param { array } elements
     * @returns { array }
     */
    getPasswordElements: function (elements) {
        var passwordEl,
            passwordConfirmEl;

        Ext.each(elements, function (element) {
            // Since ExtJs does generate random IDs for the elements,
            // it's important to find another constant for the fields.
            // The password confirm field has a name, but the actual password field is just a container without a name.
            // But it has the x-password-container class instead.
            if (element.name === 'confirm') {
                passwordConfirmEl = element;
            } else if (element.cls === 'x-password-container') {
                passwordEl = element;
            }
        });

        return [ passwordEl, passwordConfirmEl ];
    }
});
// {/block}
