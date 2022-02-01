<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation;

class CurrencyConverter
{
    public function getBaseCurrencyPrice(float $price, float $currencyFactor): float
    {
        return $price / $currencyFactor;
    }

    public function getCurrencyPrice(float $price, float $currencyFactor): float
    {
        return $price * $currencyFactor;
    }
}
