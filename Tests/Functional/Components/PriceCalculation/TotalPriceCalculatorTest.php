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
use SwagBackendOrder\Components\PriceCalculation\Calculator\TotalPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class TotalPriceCalculatorTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    /**
     * @var TotalPriceCalculator
     */
    private $totalPriceCalculator;

    protected function setUp(): void
    {
        $this->totalPriceCalculator = new TotalPriceCalculator();
    }

    public function testCalculate(): void
    {
        $positionPrices = $this->getPositionPrices();
        $shippingPrice = $this->getShippingPrice();
        $expectedTaxRates = [
            '19' => 0.62,
            '19.5' => 11.48,
        ];

        $result = $this->totalPriceCalculator->calculate($positionPrices, $shippingPrice);

        static::assertSame(63.69, $result->getTotal()->getNet());
        static::assertSame(75.79, $result->getTotal()->getGross());

        static::assertSame(60.41, $result->getSum()->getNet());
        static::assertSame(71.89, $result->getSum()->getGross());

        static::assertSame(3.28, $result->getShipping()->getNet());
        static::assertSame(3.90, $result->getShipping()->getGross());

        static::assertSame($expectedTaxRates['19'], $result->getTaxes()['19']);
        static::assertSame($expectedTaxRates['19.5'], $result->getTaxes()['19.5']);
    }

    /**
     * @return PriceResult[]
     */
    private function getPositionPrices(): array
    {
        $price1 = new PriceResult();
        $price1->setNet(50.41);
        $price1->setGross(59.99);
        $price1->setTaxRate(19.50);

        $price2 = new PriceResult();
        $price2->setNet(10.00);
        $price2->setGross(11.90);
        $price2->setTaxRate(19.50);

        return [$price1, $price2];
    }

    private function getShippingPrice(): PriceResult
    {
        $price = new PriceResult();
        $price->setGross(3.90);
        $price->setNet(3.28);
        $price->setTaxRate(19.0);

        return $price;
    }
}
