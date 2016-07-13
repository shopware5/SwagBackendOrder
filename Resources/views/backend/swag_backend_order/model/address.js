//
//{block name="backend/create_backend_order/model/address"}
Ext.define('Shopware.apps.SwagBackendOrder.model.Address', {
    /**
     * @string
     */
    extend: 'Shopware.apps.Base.model.Address',

    /**
     * @array
     */
    fields:[
        //{block name="backend/create_backend_order/model/address/fields"}{/block}
        { name:'id', type:'int' },
        { name:'salutation', type:'string' },
        { name:'company', type:'string' },
        { name:'department', type:'string' },
        { name:'firstname', type:'string' },
        { name:'title', type:'string' },
        { name:'lastname', type:'string' },
        { name:'street', type:'string' },
        { name:'zipcode', type:'string' },
        { name:'city', type:'string' },
        { name:'additionalAddressLine1', type:'string' },
        { name:'additionalAddressLine2', type:'string' },
        { name:'salutationSnippet', type:'string' },
        { name:'countryId', type:'int', useNull: true },
        {
            name: 'displayField',
            type: 'string',
            convert: function (v, record) {
                return record.get('company')
                    + ' ' + record.get('firstname')
                    + ' ' + record.get('lastname')
                    + ' ' + record.get('street')
                    + ' ' + record.get('zipcode')
                    + ' ' + record.get('city')
            }
        }
    ]

});
//{/block}


