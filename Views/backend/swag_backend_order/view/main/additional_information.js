//
//{block name="backend/create_backend_order/view/additional_information"}
//
Ext.define('Shopware.apps.SwagBackendOrder.view.main.AdditionalInformation', {

    extend: 'Ext.panel.Panel',

    alternateClassName: 'SwagBackendOrder.view.main.AdditionalInformation',

    alias: 'widget.createbackendorder-additional',

    flex: 1,

    layout: 'hbox',

    margin: '15 10 0 5',

    overflowY: 'auto',

    snippets: {
        title: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/title"}Additional Information{/s}',
        additionalInformation: {
            attribute1Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute1/label"}Attribute 1{/s}',
            attribute2Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute2/label"}Attribute 2{/s}',
            attribute3Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute3/label"}Attribute 3{/s}',
            attribute4Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute4/label"}Attribute 4{/s}',
            attribute5Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute5/label"}Attribute 5{/s}',
            attribute6Label: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/attribute6/label"}Attribute 6{/s}',
            desktopType: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/device_type/label"}Device-Type{/s}',
            desktopTypeHelpText: '{s namespace="backend/swag_backend_order/view/additional_information" name="swag_backend_order/additional/device_type/help_text"}The device type determines via which communication channel the order has been placed, for example fax, telephone or personally in your local store. The turnover by device types can be viewed in the statistics under "Turnover by device type".{/s}'
        }
    },

    initComponent: function () {
        var me = this;

        me.title = me.snippets.title;
        me.items = me.createAdditionalInformationItems();

        me.callParent(arguments);
    },

    createAdditionalInformationItems: function () {
        var me = this;

        return [me.createAdditionalInformationContainerLeft(), me.createAdditionalInformationContainerRight()]
    },

    /**
     * creates the attribute text fields in a container
     *
     * @returns [Ext.container.Container]
     */
    createAdditionalInformationContainerLeft: function () {
        var me = this;

        var attr1TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr1TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute1Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var attr2TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr2TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute2Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var attr3TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr3TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute3Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var attr4TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr4TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute4Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var attr5TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr5TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute5Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var attr6TxtBox = Ext.create('Ext.form.TextField', {
            name: 'attr6TxtBox',
            width: 230,
            fieldLabel: me.snippets.additionalInformation.attribute6Label,
            maxLengthText: 255,
            listeners: {
                change: function (field, newValue, oldValue, eOpts) {
                    me.fireEvent('changeAttrField', field, newValue, oldValue);
                }
            }
        });

        var additionalInfoContainer = Ext.create('Ext.Container', {
            name: 'additionalInformationContainer',
            width: 75,
            height: 'auto',
            items: [
                attr1TxtBox,
                attr2TxtBox,
                attr3TxtBox,
                attr4TxtBox,
                attr5TxtBox,
                attr6TxtBox
            ]
        });

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            flex: 10,
            title: 'left',
            padding: '10 0 0 10',
            autoHeight: true,
            items: [
                additionalInfoContainer
            ]
        });
    },

    /**
     * creates the desktop type combobox in a extra container for the correct layout
     *
     * @returns [Ext.container.Container]
     */
    createAdditionalInformationContainerRight: function () {
        var me = this;

        var desktopType = Ext.create('Ext.form.field.ComboBox', {
            name: 'desktop-type',
            width: 220,
            queryMode: 'local',
            store: me.subApplication.getStore('DesktopTypes'),
            displayField: 'name',
            helpTitle: me.snippets.additionalInformation.desktopType,
            helpText: me.snippets.additionalInformation.desktopTypeHelpText,
            valueField: 'id',
            fieldLabel: me.snippets.additionalInformation.desktopType,
            listeners: {
                change: function (comboBox, newValue, oldValue) {
                    me.fireEvent('changeDesktopType', comboBox, newValue);
                }
            }
        });

        return Ext.create('Ext.container.Container', {
            layout: 'hbox',
            flex: 9,
            title: 'right',
            padding: '10 0 0 10',
            autoHeight: true,
            items: [
                desktopType
            ]
        });
    }
});
//{/block}