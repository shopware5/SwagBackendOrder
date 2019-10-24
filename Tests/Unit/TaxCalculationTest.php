<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class TaxCalculationTest extends TestCase
{
    public function testGetNetPrice()
    {
        $grossPrice = 59.99;
        $taxRate = 19.00;

        $taxCalculation = $this->getTaxCalculation();
        $netPrice = $taxCalculation->getNetPrice($grossPrice, $taxRate);

        static::assertEquals(50.411764705882355, $netPrice);
    }

    public function testGetGrossPrice()
    {
        $netPrice = 50.41;
        $taxRate = 19.00;

        $taxCalculation = $this->getTaxCalculation();
        $grossPrice = $taxCalculation->getGrossPrice($netPrice, $taxRate);

        static::assertEquals(59.9879, $grossPrice);
    }

    /**
     * @return TaxCalculation
     */
    private function getTaxCalculation()
    {
        return new TaxCalculation();
    }
}
