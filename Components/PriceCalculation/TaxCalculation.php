<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation;

class TaxCalculation
{
    /**
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    public function getNetPrice($price, $taxRate)
    {
        return $price / ((100 + $taxRate) / 100);
    }

    /**
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    public function getGrossPrice($price, $taxRate)
    {
        return $price * ((100 + $taxRate) / 100);
    }
}
