<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class TaxCalculationTest extends TestCase
{
    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testGetNetPrice(): void
    {
        $grossPrice = 59.99;
        $taxRate = 19.00;

        $netPrice = $this->getTaxCalculation()->getNetPrice($grossPrice, $taxRate);

        static::assertEqualsWithDelta(50.411764705882355, $netPrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testGetGrossPrice(): void
    {
        $netPrice = 50.41;
        $taxRate = 19.00;

        $grossPrice = $this->getTaxCalculation()->getGrossPrice($netPrice, $taxRate);

        static::assertEqualsWithDelta(59.9879, $grossPrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    private function getTaxCalculation(): TaxCalculation
    {
        return new TaxCalculation();
    }
}
