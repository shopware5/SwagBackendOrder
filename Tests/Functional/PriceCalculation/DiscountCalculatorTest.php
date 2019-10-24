<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Calculator\DiscountCalculator;

class DiscountCalculatorTest extends TestCase
{
    public function test_calculateDiscount_with_absolute_discount()
    {
        $orderData = $this->getTestOrderDataWithAbsoluteDiscount();

        /** @var DiscountCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEquals(91.597, $result['totalWithoutTax']);
        static::assertEquals(90, $result['total']);
        static::assertEquals(90, $result['sum']);
        static::assertEquals(17.40343, $result['taxSum']);
    }

    public function test_calculateDiscount_with_percentage_discount()
    {
        $orderData = $this->getTestOrderDataWithPercentageDiscount();

        /** @var DiscountCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEquals(45.798, $result['totalWithoutTax']);
        static::assertEquals(45, $result['total']);
        static::assertEquals(45, $result['sum']);
        static::assertEquals(-5, $result['positions'][1]['total']);
        static::assertEquals(8.70162, $result['taxSum']);
    }

    public function test_calculateDiscount_with_tax_free_order()
    {
        $orderData = $this->getTestOrderDataTaxFree();

        /** @var DiscountCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEquals(90, $result['totalWithoutTax']);
        static::assertEquals(90, $result['total']);
        static::assertEquals(90, $result['sum']);
        static::assertEquals(0, $result['taxSum']);
    }

    /**
     * @return array
     */
    private function getTestOrderDataWithAbsoluteDiscount()
    {
        return [
            'isTaxFree' => false,
            'totalWithoutTax' => 100,
            'sum' => 100,
            'total' => 100,
            'taxSum' => 19,

            'positions' => [
                [
                    'isDiscount' => false,
                    'discountType' => 0,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => 1,
                    'price' => -10.0,
                    'total' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getTestOrderDataWithPercentageDiscount()
    {
        return [
            'isTaxFree' => false,
            'totalWithoutTax' => 50,
            'sum' => 50,
            'total' => 50,
            'taxSum' => 9.5,
            'positions' => [
                [
                    'isDiscount' => false,
                    'discountType' => 0,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => 0,
                    'price' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getTestOrderDataTaxFree()
    {
        return [
            'isTaxFree' => true,
            'totalWithoutTax' => 100,
            'sum' => 100,
            'total' => 100,

            'positions' => [
                [
                    'isDiscount' => false,
                    'discountType' => 0,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => 1,
                    'price' => -10.0,
                    'total' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }
}
