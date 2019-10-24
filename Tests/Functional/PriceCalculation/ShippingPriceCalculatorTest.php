<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ShippingPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class ShippingPriceCalculatorTest extends TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var ShippingPriceCalculator
     */
    private $SUT;

    protected function setUp(): void
    {
        $this->SUT = $this->getShippingPriceCalculator();
    }

    public function testCalculate()
    {
        $context = new PriceContext(3.90, 19.00, false, false, 1.3625);

        $price = $this->SUT->calculate($context);
        static::assertEquals(5.31375, $price->getGross());
        static::assertEquals(4.4653361344537812, $price->getNet());
    }

    public function testCalculateBasePriceNet()
    {
        $context = new PriceContext(5.31, 19.00, false, false, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        static::assertEquals(3.8972477064220179, $price);
    }

    public function testCalculateBasePrice()
    {
        $context = new PriceContext(5.31, 19.00, true, false, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        static::assertEquals(3.8972477064220179, $price);
    }

    public function testCalculateBasePriceTaxfree()
    {
        $context = new PriceContext(4.47, 19.00, true, true, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        static::assertEquals(3.9040733944954122, $price);
    }

    /**
     * @return ShippingPriceCalculator
     */
    private function getShippingPriceCalculator()
    {
        return new ShippingPriceCalculator(
            new TaxCalculation(),
            new CurrencyConverter()
        );
    }
}
