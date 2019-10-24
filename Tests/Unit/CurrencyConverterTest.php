<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;

class CurrencyConverterTest extends TestCase
{
    public function testGetBaseCurrencyPrice()
    {
        $price = 81.74;
        $currencyFactor = 1.3625;

        $currencyCalculation = $this->getCurrencyCalculation();
        $actualPrice = $currencyCalculation->getBaseCurrencyPrice($price, $currencyFactor);

        static::assertEquals(59.992660550458709, $actualPrice);
    }

    public function testGetCurrencyPrice()
    {
        $price = 59.99;
        $currencyFactor = 1.3625;

        $currencyCalculation = $this->getCurrencyCalculation();
        $actualPrice = $currencyCalculation->getCurrencyPrice($price, $currencyFactor);

        static::assertEquals(81.73637500000001, $actualPrice);
    }

    /**
     * @return CurrencyConverter
     */
    private function getCurrencyCalculation()
    {
        return new CurrencyConverter();
    }
}
