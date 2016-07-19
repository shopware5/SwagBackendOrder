<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation;

use SwagBackendOrder\Components\PriceCalculation\Context\BasketContext;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;

interface BasketPriceCalculatorInterface
{
    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param PriceContext $priceContext
     * @return PriceStruct
     */
    public function calculateProductPrice(BasketContext $context, BasketContext $oldContext, PriceContext $priceContext);

    /**
     * @param BasketContext $context
     * @param BasketContext $oldContext
     * @param float $price
     * @return PriceStruct
     */
    public function calculateDispatchPrice(BasketContext $context, BasketContext $oldContext, $price);
}
