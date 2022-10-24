<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\NumberRangeIncrementerInterface;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use SwagBackendOrder\Components\Order\Factory\OrderFactory;
use SwagBackendOrder\Components\Order\OrderService;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Components\Order\Struct\PositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class OrderServiceTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    public function testCreateOrder(): void
    {
        $orderService = $this->getContainer()->get('swag_backend_order.order.service');
        $orderStruct = $this->getOrderStruct();

        $order = $orderService->create($orderStruct);
        $order = $this->getContainer()->get('models')->find(Order::class, $order->getId());
        static::assertInstanceOf(Order::class, $order);

        static::assertSame($orderStruct->getTotal(), $order->getInvoiceAmount());
        $firstPosition = $order->getDetails()->get(0);
        static::assertInstanceOf(Detail::class, $firstPosition);
        static::assertSame($orderStruct->getPositions()[0]->getPrice(), $firstPosition->getPrice());
    }

    public function testCreateOrderDoesNotIncreaseOrderNumberRange(): void
    {
        $numberRangeIncrementer = $this->createMock(NumberRangeIncrementerInterface::class);
        $numberRangeIncrementer->expects(static::never())->method('increment');
        $orderService = new OrderService(
            $this->createMock(OrderFactory::class),
            $this->createMock(ModelManager::class),
            $numberRangeIncrementer,
            $this->getContainer()->get('swag_backend_order.order.order_validator')
        );

        $orderStruct = new OrderStruct();
        $orderStruct->addPosition(new PositionStruct());

        $this->expectException(InvalidOrderException::class);
        $this->expectExceptionMessage('Invalid SwagBackendOrder\Components\Order\Struct\OrderStruct given.');
        $orderService->create($orderStruct);
    }

    private function getOrderStruct(): OrderStruct
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
    private function getPositions(): array
    {
        $positionStruct = new PositionStruct();

        $positionStruct->setMode(0);
        $positionStruct->setProductId(2);
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

    private function getAttributes(): array
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
