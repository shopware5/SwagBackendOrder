<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use SwagBackendOrder\Components\PriceCalculation\Calculator\SurchargeCalculator;

class SurchargeCalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_calculateSurcharge_with_absolute_surcharge()
    {
        $orderData = $this->getTestOrderDataWithAbsoluteSurcharge();

        /** @var SurchargeCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.surcharge_calculator');

        $result = $calculator->calculateSurcharge($orderData);

        $this->assertEquals(92.436, $result['totalWithoutTax']);
        $this->assertEquals(110, $result['total']);
        $this->assertEquals(110, $result['sum']);
        $this->assertEquals(17.56257, $result['taxSum']);
    }

    public function test_calculateSurcharge_with_percentage_surcharge()
    {
        $orderData = $this->getTestOrderDataWithPercentageSurcharge();

        /** @var SurchargeCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.surcharge_calculator');

        $result = $calculator->calculateSurcharge($orderData);

        $this->assertEquals(46.219, $result['totalWithoutTax']);
        $this->assertEquals(55, $result['total']);
        $this->assertEquals(55, $result['sum']);
        $this->assertEquals(5, $result['positions'][1]['total']);
        $this->assertEquals(8.78138, $result['taxSum']);
    }

    public function test_calculateSurcharge_with_tax_free_order()
    {
        $orderData = $this->getTestOrderDataTaxFree();

        /** @var SurchargeCalculator $calculator */
        $calculator = Shopware()->Container()->get('swag_backend_order.price_calculation.surcharge_calculator');

        $result = $calculator->calculateSurcharge($orderData);

        $this->assertEquals(110, $result['totalWithoutTax']);
        $this->assertEquals(110, $result['total']);
        $this->assertEquals(110, $result['sum']);
        $this->assertEquals(0, $result['taxSum']);
    }

    /**
     * @return array
     */
    private function getTestOrderDataWithAbsoluteSurcharge()
    {
        return [
            'isTaxFree' => false,
            'totalWithoutTax' => 84.033,
            'sum' => 100,
            'total' => 100,
            'taxSum' => 15.966,

            'positions' => [
                [
                    'isSurcharge' => false,
                    'surchargeType' => 0,
                ],
                [
                    'isSurcharge' => true,
                    'surchargeType' => 1,
                    'price' => 10.0,
                    'total' => 10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getTestOrderDataWithPercentageSurcharge()
    {
        return [
            'isTaxFree' => false,
            'totalWithoutTax' => 42.017,
            'sum' => 50,
            'total' => 50,
            'taxSum' => 7.983,
            'positions' => [
                [
                    'isSurcharge' => false,
                    'surchargeType' => 0,
                ],
                [
                    'isSurcharge' => true,
                    'surchargeType' => 0,
                    'price' => 10.0,
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
                    'isSurcharge' => false,
                    'surchargeType' => 0,
                ],
                [
                    'isSurcharge' => true,
                    'surchargeType' => 1,
                    'price' => 10.0,
                    'total' => 10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }
}
