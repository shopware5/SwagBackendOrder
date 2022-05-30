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

    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testCalculateDiscountWithAbsoluteDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithAbsoluteDiscount();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEqualsWithDelta(91.597, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(17.40343, $result['taxSum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateDiscountWithPercentageDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithPercentageDiscount();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEqualsWithDelta(45.798, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(45.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(45.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(-5.0, $result['positions'][1]['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(8.70162, $result['taxSum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateDiscountWithTaxFreeOrder(): void
    {
        $orderData = $this->getTestOrderDataTaxFree();

        $calculator = $this->getContainer()->get('swag_backend_order.price_calculation.discount_calculator');

        $result = $calculator->calculateDiscount($orderData);

        static::assertEqualsWithDelta(90.0, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
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
