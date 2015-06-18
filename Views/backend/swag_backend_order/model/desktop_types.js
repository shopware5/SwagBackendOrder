/**
 * holds the desktop types which can be configured in the plugin configuration
 */
//{block name="backend/create_backend_order/model/desktop_types"}
//
Ext.define('Shopware.apps.SwagBackendOrder.model.DesktopTypes', {
    /**
     * extends from the standard ExtJs Model
     */
    extend: 'Ext.data.Model',

    alternateClassName: 'SwagBackendOrder.model.Debit',

    fields: [
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' }
    ]
});
//
//{/block}