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

final class DiscountType
{
    public const DISCOUNT_PERCENTAGE = 0;
    public const DISCOUNT_ABSOLUTE = 1;

    private function __construct()
    {
    }
}
