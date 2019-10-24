<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Order;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\OrderService;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class OrderServiceTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testCreateOrder()
    {
        /** @var OrderService $orderService */
        $orderService = Shopware()->Container()->get('swag_backend_order.order.service');
        $orderStruct = $this->getOrderStruct();

        /** @var Order $order */
        $order = $orderService->create($orderStruct);
        $order = Shopware()->Container()->get('models')->find(Order::class, $order->getId());

        static::assertEquals($orderStruct->getTotal(), $order->getInvoiceAmount());
        static::assertEquals($orderStruct->getPositions()[0]->getPrice(), $order->getDetails()->get(0)->getPrice());
        static::assertInstanceOf(Order::class, $order);
    }

    /**
     * @return OrderStruct
     */
    private function getOrderStruct()
    {
        $orderStruct = new OrderStruct();
        $orderStruct->setCustomerId(1);
        $orderStruct->setBillingAddressId(1);
        $orderStruct->setShippingAddressId(1);
        $orderStruct->setShippingCosts(3.9);
        $orderStruct->setShippingCostsNet(3.28);
        $orderStruct->setPaymentId(3);
        $orderStruct->setDispatchId(9);
        $orderStruct->setLanguageShopId(1);
        $orderStruct->setCurrency('');
        $orderStruct->setTotal(63.89);
        $orderStruct->setDeviceType('Backend');
        $orderStruct->setNetOrder(false);
        $orderStruct->setTotalWithoutTax(53.69);
        $orderStruct->setCurrencyId(1);

        $orderStruct->setPositions($this->getPositions());
        $orderStruct->setAttributes($this->getAttributes());

        return $orderStruct;
    }

    /**
     * @return PositionStruct[]
     */
    private function getPositions()
    {
        $positionStruct = new PositionStruct();

        $positionStruct->setMode(0);
        $positionStruct->setArticleId(2);
        $positionStruct->setNumber('SW10002.1');
        $positionStruct->setName('Münsterländer Lagerkorn 32% 1,5 Liter');
        $positionStruct->setQuantity(1);
        $positionStruct->setStatusId(0);
        $positionStruct->setTaxRate(19);
        $positionStruct->setTaxId(1);
        $positionStruct->setPrice(59.99);
        $positionStruct->setTotal(59.99);

        return [
            $positionStruct,
        ];
    }

    /**
     * @return array
     */
    private function getAttributes()
    {
        return [
            'attribute1' => 'Freitext 1',
            'attribute2' => 'Freitext 2',
            'attribute3' => 'Freitext 3',
            'attribute4' => 'Freitext 4',
            'attribute5' => 'Freitext 5',
            'attribute6' => 'Freitext 6',
        ];
    }
}
