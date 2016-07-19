<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation;

class CurrencyConverter
{
    /**
     * @param float $currencyFactor
     * @param float $price
     * @return float
     */
    public function getBaseCurrencyPrice($price, $currencyFactor)
    {
        return $price / $currencyFactor;
    }

    /**
     * @param float $currencyFactor
     * @param float $price
     * @return float
     */
    public function getCurrencyPrice($price, $currencyFactor)
    {
        return $price * $currencyFactor;
    }
}
