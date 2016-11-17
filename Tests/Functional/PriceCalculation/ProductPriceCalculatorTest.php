<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class ProductPriceCalculatorTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var ProductPriceCalculator
     */
    private $productPriceCalculator;

    protected function setUp()
    {
        $this->productPriceCalculator = new ProductPriceCalculator(
            new TaxCalculation(),
            new CurrencyConverter()
        );
    }

    /**
     * @covers ProductPriceCalculator::calculate()
     */
    public function testCalculate()
    {
        $context = new PriceContext(50.41, 19.00, true, false, 1.3625);

        $price = $this->productPriceCalculator->calculate($context);
        $this->assertEquals(68.683624999999992, $price->getNet());
        $this->assertEquals(81.733513749999986, $price->getGross());
    }

    /**
     * @covers ProductPriceCalculator::calculateBasePrice()
     */
    public function testCalculateBasePriceFromGrossPriceWithCurrencyFactor()
    {
        $currencyFactor = 1.3625;
        $isNetPrice = false;
        $isTaxFree = false;
        $context = new PriceContext(81.74, 19.00, $isNetPrice, $isTaxFree, $currencyFactor);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        $this->assertEquals(50.414000462570343, $basePrice);
    }

    public function testCalculateBasePriceFromNetPrice()
    {
        $isNetPrice = true;
        $context = new PriceContext(50.00, 19.00, $isNetPrice);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        $this->assertEquals(50.00, $basePrice);
    }

    public function testCalculateBasePriceFromTaxFreePrice()
    {
        $isTaxFree = true;
        $isNetPrice = false;
        $context = new PriceContext(50.00, 19.00, $isNetPrice, $isTaxFree);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        $this->assertEquals(50.00, $basePrice);
    }

    public function testCalculateBasePriceFromTaxFreePriceWithCurrencyFactor()
    {
        $isTaxFree = true;
        $isNetPrice = false;
        $currencyFactor = 2.0;
        $context = new PriceContext(50.00, 19.00, $isNetPrice, $isTaxFree, $currencyFactor);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        $this->assertEquals(25, $basePrice);
    }
}