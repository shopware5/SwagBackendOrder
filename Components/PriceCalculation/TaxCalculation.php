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

class TaxCalculation
{
    public function getNetPrice(float $price, float $taxRate): float
    {
        return $price / ((100 + $taxRate) / 100);
    }

    public function getGrossPrice(float $price, float $taxRate): float
    {
        return $price * ((100 + $taxRate) / 100);
    }
}
