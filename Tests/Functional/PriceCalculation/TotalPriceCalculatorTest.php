<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\Calculator\TotalPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class TotalPriceCalculatorTest extends TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var TotalPriceCalculator
     */
    private $SUT;

    protected function setUp(): void
    {
        $this->SUT = new TotalPriceCalculator();
    }

    public function testCalculate()
    {
        $positionPrices = $this->getPositionPrices();
        $shippingPrice = $this->getShippingPrice();
        $expectedTaxRates = ['19' => 12.1];

        $result = $this->SUT->calculate($positionPrices, $shippingPrice);

        static::assertEquals(63.69, $result->getTotal()->getNet());
        static::assertEquals(75.79, $result->getTotal()->getGross());

        static::assertEquals(60.41, $result->getSum()->getNet());
        static::assertEquals(71.89, $result->getSum()->getGross());

        static::assertEquals(3.28, $result->getShipping()->getNet());
        static::assertEquals(3.90, $result->getShipping()->getGross());

        static::assertEquals($expectedTaxRates['19'], $result->getTaxes()['19']);
    }

    /**
     * @return PriceResult[]
     */
    private function getPositionPrices()
    {
        $price1 = new PriceResult();
        $price1->setNet(50.41);
        $price1->setGross(59.99);
        $price1->setTaxRate(19.00);

        $price2 = new PriceResult();
        $price2->setNet(10.00);
        $price2->setGross(11.90);
        $price2->setTaxRate(19.00);

        return [$price1, $price2];
    }

    /**
     * @return PriceResult
     */
    private function getShippingPrice()
    {
        $price = new PriceResult();
        $price->setGross(3.90);
        $price->setNet(3.28);
        $price->setTaxRate(19.00);

        return $price;
    }
}
