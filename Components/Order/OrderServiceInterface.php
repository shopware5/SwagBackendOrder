<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order;

use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;

interface OrderServiceInterface
{
    /**
     * @return Order
     */
    public function create(OrderStruct $orderStruct);
}
