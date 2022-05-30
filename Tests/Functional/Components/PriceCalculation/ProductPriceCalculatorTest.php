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
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class ProductPriceCalculatorTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    /**
     * @var ProductPriceCalculator
     */
    private $productPriceCalculator;

    protected function setUp(): void
    {
        $this->productPriceCalculator = new ProductPriceCalculator(
            new TaxCalculation(),
            new CurrencyConverter()
        );
    }

    public function testCalculate(): void
    {
        $context = new PriceContext(50.41, 19.00, true, false, 1.3625);

        $price = $this->productPriceCalculator->calculate($context);
        static::assertEqualsWithDelta(68.68, \round($price->getNet(), 2), self::FORMER_PHPUNIT_FLOAT_EPSILON);
        static::assertEqualsWithDelta(81.73, \round($price->getGross(), 2), self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceFromGrossPriceWithCurrencyFactor(): void
    {
        $currencyFactor = 1.3625;
        $context = new PriceContext(81.74, 19.00, false, false, $currencyFactor);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(50.414000462570343, $basePrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceFromNetPrice(): void
    {
        $context = new PriceContext(50.00, 19.00, true);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(50.00, $basePrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceFromTaxFreePrice(): void
    {
        $context = new PriceContext(50.00, 19.00, false, true);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(50.00, $basePrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testCalculateBasePriceFromTaxFreePriceWithCurrencyFactor(): void
    {
        $currencyFactor = 2.0;
        $context = new PriceContext(50.00, 19.00, false, true, $currencyFactor);

        $basePrice = $this->productPriceCalculator->calculateBasePrice($context);
        static::assertEqualsWithDelta(25.0, $basePrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }
}
