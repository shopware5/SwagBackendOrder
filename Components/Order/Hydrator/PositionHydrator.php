<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Hydrator;

use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class PositionHydrator
{
    /**
     * @param array<string, mixed> $data
     */
    public function hydrate(array $data): PositionStruct
    {
        $positionStruct = new PositionStruct();

        $positionStruct->setMode((int) $data['mode']);
        $positionStruct->setProductId((int) $data['articleId']);
        $positionStruct->setVariantId((int) $data['detailId']);
        $positionStruct->setNumber((string) $data['articleNumber']);
        $positionStruct->setName((string) $data['articleName']);
        $positionStruct->setQuantity((int) $data['quantity']);
        $positionStruct->setStatusId((int) $data['statusId']);
        $positionStruct->setTaxRate((float) $data['taxRate']);
        $positionStruct->setTaxId((int) $data['taxId']);
        $positionStruct->setPrice((float) $data['price']);
        $positionStruct->setTotal((float) $data['total']);
        $positionStruct->setEan((string) $data['ean']);

        return $positionStruct;
    }
}
