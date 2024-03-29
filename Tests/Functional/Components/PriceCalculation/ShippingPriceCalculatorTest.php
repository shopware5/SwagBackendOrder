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
use SwagBackendOrder\Components\PriceCalculation\Calculator\ShippingPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class ShippingPriceCalculatorTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    /**
     * @var ShippingPriceCalculator
     */
    private $shippingPriceCalculator;

    protected function setUp(): void
    {
        $this->shippingPriceCalculator = new ShippingPriceCalculator(
            new TaxCalculation(),
            new CurrencyConverter()
        );
    }

    public function testCalculate(): void
    {
        $context = new PriceContext(3.90, 19.00, false, false, 1.3625);

        $price = $this->shippingPriceCalculator->calculate($context);
        static::assertEqualsWithDelta(5.31375, $price->getGross(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(4.4653361344537812, $price->getNet(), self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceNet(): void
    {
        $context = new PriceContext(5.31, 19.00, false, false, 1.3625);

        $price = $this->shippingPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(3.8972477064220179, $price, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePrice(): void
    {
        $context = new PriceContext(5.31, 19.00, true, false, 1.3625);

        $price = $this->shippingPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(3.8972477064220179, $price, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceTaxfree(): void
    {
        $context = new PriceContext(4.47, 19.00, true, true, 1.3625);

        $price = $this->shippingPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(3.9040733944954122, $price, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }
}
