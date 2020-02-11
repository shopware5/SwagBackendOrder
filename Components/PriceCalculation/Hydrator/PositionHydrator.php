<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Hydrator;

use SwagBackendOrder\Components\PriceCalculation\Struct\PositionStruct;

class PositionHydrator
{
    /**
     * @return PositionStruct
     */
    public function hydrate(array $data)
    {
        $position = new PositionStruct();
        $position->setPrice((float) $data['price']);
        $position->setQuantity((int) $data['quantity']);
        $position->setTotal((float) $data['total']);
        $position->setTaxRate((float) $data['taxRate']);
        $position->setIsDiscount((bool) $data['isDiscount']);
        $position->setDiscountType((int) $data['discountType']);

        return $position;
    }
}
