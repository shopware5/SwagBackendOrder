<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'module' => 'backend',
    'controller' => 'SwagBackendOrder',
    'action' => 'createOrder',
    'data' => [
        'customerId' => 1,
        'billingAddressId' => 1,
        'shippingAddressId' => 0,
        'shippingCosts' => 3.8999999999999999,
        'shippingCostsNet' => 3.2799999999999998,
        'shippingCostsTaxRate' => 19,
        'paymentId' => 3,
        'dispatchId' => 9,
        'languageShopId' => 1,
        'currency' => '',
        'total' => 23.850000000000001,
        'totalWithoutTax' => 20.039999999999999,
        'currencyId' => '1',
        'desktopType' => 'Backend',
        'displayNet' => false,
        'sendMail' => false,
        'taxFree' => false,
        'id' => null,
        'position' => [
            [
                'id' => 0,
                'create_backend_order_id' => 0,
                'mode' => 0,
                'articleId' => 0,
                'detailId' => 0,
                'articleNumber' => 'SW10178',
                'articleName' => 'Strandtuch "Ibiza"',
                'quantity' => 1,
                'statusId' => 0,
                'statusDescription' => '',
                'taxId' => 1,
                'taxRate' => 19,
                'taxDescription' => '',
                'inStock' => 76,
                'isDiscount' => false,
                'discountType' => 0,
                'ean' => 'UnitTestEAN',
                'price' => '19.95',
                'total' => '19.95',
            ],
        ],
        'orderAttribute' => [
            [
                'attribute1' => '',
                'attribute2' => '',
                'attribute3' => '',
                'attribute4' => '',
                'attribute5' => '',
                'attribute6' => '',
                'id' => null,
            ],
        ],
    ],
];
