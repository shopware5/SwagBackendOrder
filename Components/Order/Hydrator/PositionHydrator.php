<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Hydrator;

use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class PositionHydrator
{
    /**
     * @param array $data
     *
     * @return PositionStruct
     */
    public function hydrate(array $data)
    {
        $positionStruct = new PositionStruct();

        $positionStruct->setMode((int) $data['mode']);
        $positionStruct->setArticleId((int) $data['articleId']);
        $positionStruct->setDetailId((int) $data['detailId']);
        $positionStruct->setNumber((string) $data['articleNumber']);
        $positionStruct->setName((string) $data['articleName']);
        $positionStruct->setQuantity((int) $data['quantity']);
        $positionStruct->setStatusId((int) $data['statusId']);
        $positionStruct->setTaxRate((int) $data['taxRate']);
        $positionStruct->setTaxId((int) $data['taxId']);
        $positionStruct->setEan((string) $data['ean']);
        $positionStruct->setPrice((float) $data['price']);
        $positionStruct->setTotal((float) $data['total']);

        return $positionStruct;
    }
}
