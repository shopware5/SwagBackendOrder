<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use Shopware\Components\Test\Plugin\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ShippingPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class ShippingPriceCalculatorTest extends TestCase
{
    /**
     * @var ShippingPriceCalculator
     */
    private $SUT;

    protected function setUp()
    {
        $this->SUT = $this->getShippingPriceCalculator();
    }

    /**
     * @covers ShippingPriceCalculator::calculate()
     */
    public function testCalculate()
    {
        $context = new PriceContext(3.90, 19.00, false, false, 1.3625);

        $price = $this->SUT->calculate($context);
        $this->assertEquals(5.31375, $price->getGross());
        $this->assertEquals(4.4653361344537812, $price->getNet());
    }

    /**
     * @covers ShippingPriceCalculator::calculateBasePrice()
     */
    public function testCalculateBasePriceNet()
    {
        $context = new PriceContext(5.31, 19.00, false, false, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        $this->assertEquals(3.8972477064220179, $price);
    }

    /**
     * @covers ShippingPriceCalculator::calculateBasePrice()
     */
    public function testCalculateBasePrice()
    {
        $context = new PriceContext(5.31, 19.00, true, false, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        $this->assertEquals(3.8972477064220179, $price);
    }

    /**
     * @covers ShippingPriceCalculator::calculateBasePrice()
     */
    public function testCalculateBasePriceTaxfree()
    {
        $context = new PriceContext(4.47, 19.00, true, true, 1.3625);

        $price = $this->SUT->calculateBasePrice($context);
        $this->assertEquals(3.9040733944954122, $price);
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