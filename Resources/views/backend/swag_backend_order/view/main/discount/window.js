//{block name="backend/create_backend_order/view/main/discount/window"}
//{namespace name="backend/swag_backend_order/view/discount"}
Ext.define('Shopware.apps.SwagBackendOrder.view.main.discount.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.createbackendorder-discount-window',

    title: '{s name="title"}Add new discount{/s}',
    height: 220,
    width: 300,
    maximizable: false,
    resizable: false,
    modal: true,
    layout: 'anchor',
    minimizable: false,

    /**
     * @type { Ext.form.field.ComboBox }
     */
    discountTypeSelection: null,

    /**
     * @type { Shopware.apps.Base.view.element.Number }
     */
    valueField: null,

    /**
     * @type { Ext.form.field.Text }
     */
    nameField: null,

    /**
     * @type { Ext.form.field.ComboBox }
     */
    taxSelection: null,

    /**
     * @type { Ext.form.Panel }
     */
    contentContainer: null,

    /**
     * @type { Shopware.apps.Base.view.element.Button }
     */
    submitButton: null,

    /**
     * @type { Shopware.apps.Base.view.element.Button }
     */
    cancelButton: null,

    initComponent: function() {
        var me = this;

        me.items = me.getItems();
        me.registerEvents();

        me.callParent(arguments);
    },

    close: function() {
        var me = this;

        me.destroy();
    },

    registerEvents: function() {
        var me = this;

        me.addEvents(
            /**
             * This event will be fired, when the user confirms to add the discount.
             *
             * @param { Object } data
             */
            'addDiscount'
        );
    },

    /**
     * @returns { Ext.form.Panel }
     */
    getItems: function() {
        var me = this;

        me.discountTypeSelection = me.createDiscountTypeSelection();
        me.taxSelection = me.createTaxSelection();
        me.valueField = me.createValueField();
        me.nameField = me.createNameField();
        me.submitButton = me.createSubmitButton();
        me.contentContainer = Ext.create('Ext.form.Panel', {
            border: false,
            bodyPadding: 10,
            fieldDefaults: {
                anchor: '100%',
                labelWidth: 50
            },
            items: [
                me.discountTypeSelection,
                me.taxSelection,
                me.valueField,
                me.nameField,
                me.submitButton
            ]
        });

        return me.contentContainer;
    },

    /**
     * @returns { Ext.form.field.ComboBox }
     */
    createDiscountTypeSelection: function() {
        return Ext.create('Ext.form.field.ComboBox', {
            fieldLabel: '{s name="field/type"}Type{/s}',
            name: 'type',
            valueField: 'id',
            value: 0,
            editable: false,
            store: Ext.create('Shopware.apps.SwagBackendOrder.store.DiscountTypes')
        });
    },

    /**
     * @returns { Ext.form.field.ComboBox }
     */
    createTaxSelection: function() {
        var store = Ext.create('Shopware.apps.Base.store.Tax');

        return Ext.create('Ext.form.field.ComboBox', {
            name: 'tax',
            store: store,
            displayField: 'name',
            valueField: 'tax',
            fieldLabel: '{s name="field/tax"}Tax{/s}',
            editable: false,
            allowBlank: false
        });
    },

    /**
     * @returns { Shopware.apps.Base.view.element.Number }
     */
    createValueField: function() {
        var me = this;

        return Ext.create('Shopware.apps.Base.view.element.Number', {
            name: 'value',
            fieldLabel: '{s name="field/value"}Value{/s}',
            allowDecimals: true,
            minValue: 0.01,
            validator: Ext.bind(me.validateValueField, me)
        });
    },

    /**
     * @returns { Ext.form.field.Text }
     */
    createNameField: function() {
        return Ext.create('Ext.form.field.Text', {
            fieldLabel: '{s name="field/name"}Name{/s}',
            name: 'name',
            emptyText: '{s name="field/name/emptyText"}Optional name{/s}'
        });
    },

    /**
     * @returns { Shopware.apps.Base.view.element.Button }
     */
    createSubmitButton: function() {
        var me = this;

        return Ext.create('Shopware.apps.Base.view.element.Button', {
            text: '{s name="button/submit"}Apply{/s}',
            region: 'right',
            anchor: '100%',
            margin: 10,
            cls: 'primary',
            handler: Ext.bind(me.onSubmit, me)
        });
    },

    /**
     * @param value
     * @returns { string|bool }
     */
    validateValueField: function(value) {
        var me = this,
            discountType = me.discountTypeSelection.value;

        if (value <= 0) {
            return '{s name="validation/error/valueZero"}The entered value should be greater than 0.{/s}';
        }

        if (discountType === 0 && (value <= 0 || value > 100)) {
            return '{s name="validation/error/valueZeroOrTooHigh"}The entered value should be greater than 0 and less than or equal to 100.{/s}';
        }

        return true;
    },

    onSubmit: function() {
        var me = this,
            type = me.discountTypeSelection.value,
            value = me.valueField.value,
            name = me.nameField.value,
            taxRate = me.taxSelection.value,
            validationResult = me.validateValueField(value);

        //Check if the value field is valid, otherwise return.
        if (validationResult !== true) {
            Shopware.Notification.createGrowlMessage('{s name="growl/validation/title"}Validation error{/s}', validationResult, 'SwagBackendOrder ' + me.title, 'growl', false);
            return;
        }

        me.fireEvent('addDiscount', {
            value: value,
            type: type,
            name: name,
            tax: taxRate
        });

        me.close();
    }
});
//{/block}