<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit;

use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;

class CurrencyConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers CurrencyConverter::getBaseCurrencyPrice()
     */
    public function testGetBaseCurrencyPrice()
    {
        $price = 81.74;
        $currencyFactor = 1.3625;

        $currencyCalculation = $this->getCurrencyCalculation();
        $actualPrice = $currencyCalculation->getBaseCurrencyPrice($price, $currencyFactor);

        $this->assertEquals(59.992660550458709, $actualPrice);
    }

    /**
     * @covers CurrencyConverter::getCurrencyPrice()
     */
    public function testGetCurrencyPrice()
    {
        $price = 59.99;
        $currencyFactor = 1.3625;

        $currencyCalculation = $this->getCurrencyCalculation();
        $actualPrice = $currencyCalculation->getCurrencyPrice($price, $currencyFactor);

        $this->assertEquals(81.73637500000001, $actualPrice);
    }

    /**
     * @return CurrencyConverter
     */
    private function getCurrencyCalculation()
    {
        return new CurrencyConverter();
    }
}
