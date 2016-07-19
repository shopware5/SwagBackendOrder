<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional;

use phpunit\framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Context\BasketContext;
use SwagBackendOrder\Components\PriceCalculation\BasketPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\BasketPriceCalculatorInterface;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class BasketPriceCalculatorTest extends TestCase
{
    /**
     * @covers BasketPriceCalculator::calculateProductPrice()
     */
    public function testCalculateProductPrice()
    {
        $context = $this->getBasketContext();
        $oldContext = $this->getOldBasketContext();
        $priceContext = $this->getPriceContext();

        $basketPriceCalculator = $this->getBasketPriceCalculator();
        $priceStruct = $basketPriceCalculator->calculateProductPrice($context, $oldContext, $priceContext);

        $this->assertEquals(68.686029411765, $priceStruct->getNet());
        $this->assertEquals(81.736375, $priceStruct->getGross());
    }

    /**
     * @covers BasketPriceCalculator::calculateDispatchPrice()
     */
    public function testCalculateDispatchPrice()
    {
        $context = $this->getBasketContext();
        $oldContext = $this->getOldBasketContext();
        $price = 3.90;

        $basketPriceCalculator = $this->getBasketPriceCalculator();
        $priceStruct = $basketPriceCalculator->calculateDispatchPrice($context, $oldContext, $price);

        $this->assertEquals(5.3137499999999998, $priceStruct->getGross());
        $this->assertEquals(4.4653361344537812, $priceStruct->getNet());
    }

    /**
     * @return BasketContext
     */
    private function getBasketContext()
    {
        $currencyFactor = 1.3625;
        $net = false;
        $dispatchTaxRate = 19;
        return new BasketContext($currencyFactor, $net, $dispatchTaxRate);
    }

    /**
     * @return BasketContext
     */
    private function getOldBasketContext()
    {
        $currencyFactor = 1.0;
        $net = false;
        $dispatchTaxRate = 19;
        return new BasketContext($currencyFactor, $net, $dispatchTaxRate);
    }

    /**
     * @return PriceContext
     */
    private function getPriceContext()
    {
        $grossPrice = 59.99;
        $taxRate = 19.00;

        return new PriceContext($grossPrice, $taxRate);
    }

    /**
     * @return BasketPriceCalculatorInterface
     */
    private function getBasketPriceCalculator()
    {
        return new BasketPriceCalculator(new TaxCalculation(), new CurrencyConverter());
    }
}
