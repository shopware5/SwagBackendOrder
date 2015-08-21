/**
 * Shopware Model - Plugin Create Backend Order
 *
 * The billing model represents a single row of the s_order_billingaddress and belongs to
 * the order model which is defined in backend/order/model/order.js.
 * The order billing model is an simple extension of the global standard billingAddress model
 * which contains all required fields for an billing address.
 */
    //
    //{block name="backend/create_backend_order/model/billing"}
    //
Ext.define('Shopware.apps.SwagBackendOrder.model.Billing', {
    /**
     * Extends the standard Ext Model
     * @string
     */
    extend: 'Ext.data.Model',

    alternateClassName: 'SwagBackendOrder.model.Billing',

    fields: [
        { name: 'id', type: 'int' },
        { name: 'salutation', type: 'string' },
        { name: 'company', type: 'string' },
        { name: 'department', type: 'string' },
        { name: 'firstName', type: 'string' },
        { name: 'lastName', type: 'string' },
        { name: 'street', type: 'string' },
        { name: 'zipCode', type: 'string' },
        { name: 'city', type: 'string' },
        { name: 'additionalAddressLine1', type: 'string' },
        { name: 'additionalAddressLine2', type: 'string' },
        { name: 'countryId', type: 'int', useNull: true },
        { name: 'number', type: 'string' },
        { name: 'phone', type: 'string' },
        { name: 'fax', type: 'string' },
        { name: 'vatId', type: 'string' },
        { name: 'country', type: 'string' },
        { name: 'state', type: 'string' },
        {
            name: 'displayField',
            type: 'string',
            convert: function (v, record) {
                return record.get('company')
                    + ' ' + record.get('firstName')
                    + ' ' + record.get('lastName')
                    + ' ' + record.get('street')
                    + ' ' + record.get('zipCode')
                    + ' ' + record.get('city')
                    + ' ' + record.get('country')
                    + ' ' + record.get('state')
            }
        }
    ]
});
//
//{/block}