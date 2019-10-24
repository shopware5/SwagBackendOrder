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
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Components\Order\Validator\OrderValidatorInterface;

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
     * @var OrderValidatorInterface
     */
    private $validator;

    public function __construct(
        OrderFactory $orderFactory,
        ModelManager $modelManager,
        NumberRangeIncrementerInterface $numberRangeIncrementer,
        OrderValidatorInterface $validator
    ) {
        $this->orderFactory = $orderFactory;
        $this->modelManager = $modelManager;
        $this->numberRangeIncrementer = $numberRangeIncrementer;
        $this->validator = $validator;
    }

    /**
     * @throws InvalidOrderException
     *
     * @return Order
     */
    public function create(OrderStruct $orderStruct)
    {
        $number = $this->numberRangeIncrementer->increment('invoice');
        $orderStruct->setNumber($number);

        $violations = $this->validator->validate($orderStruct);
        if ($violations->getMessages()) {
            throw new InvalidOrderException('Invalid ' . OrderStruct::class . 'given.');
        }

        $order = $this->orderFactory->create($orderStruct);

        $this->modelManager->persist($order);
        foreach ($order->getPaymentInstances() as $instance) {
            $this->modelManager->persist($instance);
        }

        $this->modelManager->flush();

        return $order;
    }
}
