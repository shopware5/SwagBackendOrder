<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\PriceCalculation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class DiscountCalculatorTest extends TestCase
{
    use ContainerTrait;

    public function testCalculateDiscountWithAbsoluteDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithAbsoluteDiscount();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertSame(91.597, $result['totalWithoutTax']);
        static::assertSame(90.0, $result['total']);
        static::assertSame(90.0, $result['sum']);
        static::assertSame(17.40343, $result['taxSum']);
    }

    public function testCalculateDiscountWithPercentageDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithPercentageDiscount();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertSame(45.798, $result['totalWithoutTax']);
        static::assertSame(45.0, $result['total']);
        static::assertSame(45.0, $result['sum']);
        static::assertSame(-5.0, $result['positions'][1]['total']);
        static::assertSame(8.70162, $result['taxSum']);
    }

    public function testCalculateDiscountWithTaxFreeOrder(): void
    {
        $orderData = $this->getTestOrderDataTaxFree();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertSame(90.0, $result['totalWithoutTax']);
        static::assertSame(90.0, $result['total']);
        static::assertSame(90.0, $result['sum']);
        static::assertNull($result['taxSum']);
    }

    private function getTestOrderDataWithAbsoluteDiscount(): array
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

    private function getTestOrderDataWithPercentageDiscount(): array
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

    private function getTestOrderDataTaxFree(): array
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
