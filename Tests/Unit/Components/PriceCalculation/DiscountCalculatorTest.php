<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Unit\Components\PriceCalculation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Calculator\DiscountCalculator;
use SwagBackendOrder\Components\PriceCalculation\DiscountType;

/**
 * @phpstan-import-type CalculateBasketResult from \Shopware_Controllers_Backend_SwagBackendOrder
 */
class DiscountCalculatorTest extends TestCase
{
    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testCalculateDiscountWithAbsoluteDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithAbsoluteDiscount();

        $result = $this->createCalculator()->calculateDiscount($orderData);

        static::assertEqualsWithDelta(91.597, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(17.40343, $result['taxSum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateDiscountWithPercentageDiscount(): void
    {
        $orderData = $this->getTestOrderDataWithPercentageDiscount();

        $result = $this->createCalculator()->calculateDiscount($orderData);

        static::assertEqualsWithDelta(45.798, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(45.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(45.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(-5.0, $result['positions'][1]['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(8.70162, $result['taxSum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateDiscountWithTaxFreeOrder(): void
    {
        $orderData = $this->getTestOrderDataTaxFree();

        $result = $this->createCalculator()->calculateDiscount($orderData);

        static::assertEqualsWithDelta(90.0, $result['totalWithoutTax'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['total'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(90.0, $result['sum'], self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertNull($result['taxSum']);
    }

    public function testCalculateDiscountWithAbsoluteDiscountAndWithDisplayNet(): void
    {
        $orderData = $this->getOrderDataWithAbsolutDiscountAndNetPrices();

        $result = $this->createCalculator()->calculateDiscount($orderData);

        static::assertSame(1.71, $result['sum']);
        static::assertSame(4.99, $result['totalWithoutTax']);
        static::assertSame(5.94, $result['total']);
    }

    private function createCalculator(): DiscountCalculator
    {
        return new DiscountCalculator();
    }

    /**
     * @return array<string, mixed>
     */
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
                    'discountType' => DiscountType::DISCOUNT_PERCENTAGE,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => DiscountType::DISCOUNT_ABSOLUTE,
                    'price' => -10.0,
                    'total' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
                    'discountType' => DiscountType::DISCOUNT_PERCENTAGE,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => DiscountType::DISCOUNT_PERCENTAGE,
                    'price' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
                    'discountType' => DiscountType::DISCOUNT_PERCENTAGE,
                ],
                [
                    'isDiscount' => true,
                    'discountType' => DiscountType::DISCOUNT_ABSOLUTE,
                    'price' => -10.0,
                    'total' => -10.0,
                    'taxRate' => 19.0,
                ],
            ],
        ];
    }

    /**
     * @return CalculateBasketResult
     */
    private function getOrderDataWithAbsolutDiscountAndNetPrices(): array
    {
        return [
            'totalWithoutTax' => 9.99,
            'sum' => 6.71,
            'total' => 11.89,
            'shippingCosts' => 3.9,
            'shippingCostsNet' => 3.28,
            'shippingCostsTaxRate' => 19.0,
            'taxSum' => 1.9000000000000004,
            'positions' => [
                [
                    'price' => 6.714285714285714,
                    'quantity' => 1,
                    'total' => 6.714285714285714,
                    'taxRate' => 19.0,
                    'isDiscount' => false,
                    'discountType' => 0,
                ],
                [
                    'price' => -5.0,
                    'quantity' => 1,
                    'total' => -5.0,
                    'taxRate' => 19.0,
                    'isDiscount' => true,
                    'discountType' => 1,
                ],
            ],
            'dispatchTaxRate' => 19.0,
            'proportionalTaxCalculation' => false,
            'taxes' => [
                [
                    'taxRate' => 19.0,
                    'tax' => 1.9,
                ],
            ],
            'isTaxFree' => false,
            'isDisplayNet' => true,
        ];
    }
}
