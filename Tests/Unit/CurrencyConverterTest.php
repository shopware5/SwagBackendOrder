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
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;

class CurrencyConverterTest extends TestCase
{
    private const FORMER_PHPUNIT_FLOAT_EPSILON = 0.0000000001;

    public function testGetBaseCurrencyPrice(): void
    {
        $price = 81.74;
        $currencyFactor = 1.3625;

        $actualPrice = $this->getCurrencyCalculation()->getBaseCurrencyPrice($price, $currencyFactor);

        static::assertEqualsWithDelta(59.992660550458709, $actualPrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    public function testGetCurrencyPrice(): void
    {
        $price = 59.99;
        $currencyFactor = 1.3625;

        $actualPrice = $this->getCurrencyCalculation()->getCurrencyPrice($price, $currencyFactor);

        static::assertEqualsWithDelta(81.73637500000001, $actualPrice, self::FORMER_PHPUNIT_FLOAT_EPSILON);
    }

    private function getCurrencyCalculation(): CurrencyConverter
    {
        return new CurrencyConverter();
    }
}
