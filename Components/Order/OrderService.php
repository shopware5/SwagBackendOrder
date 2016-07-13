<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\NumberRangeIncrementerInterface;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Factory\OrderFactory;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;

class OrderService implements OrderServiceInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var NumberRangeIncrementerInterface
     */
    private $numberRangeIncrementer;

    /**
     * @param OrderFactory $orderFactory
     * @param ModelManager $modelManager
     * @param NumberRangeIncrementerInterface $numberRangeIncrementer
     */
    public function __construct(
        OrderFactory $orderFactory,
        ModelManager $modelManager,
        NumberRangeIncrementerInterface $numberRangeIncrementer
    ) {
        $this->orderFactory = $orderFactory;
        $this->modelManager = $modelManager;
        $this->numberRangeIncrementer = $numberRangeIncrementer;
    }

    /**
     * @param OrderStruct $orderStruct
     * @return Order
     */
    public function create(OrderStruct $orderStruct)
    {
        $number = $this->numberRangeIncrementer->increment('invoice');
        $orderStruct->setNumber($number);

        $order = $this->orderFactory->create($orderStruct);

        $this->modelManager->persist($order);
        $this->modelManager->persist($order->getPaymentInstances());
        $this->modelManager->flush();

        return $order;
    }
}