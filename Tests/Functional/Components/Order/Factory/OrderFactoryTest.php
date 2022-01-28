<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order\Factory;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use SwagBackendOrder\Components\Order\Factory\OrderFactory;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class OrderFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreateShippingAddressShoudAddDefaultShippingToCustomer(): void
    {
        $orderFactory = $this->getOrderFactory();

        $reflectionMethod = (new \ReflectionClass(OrderFactory::class))->getMethod('createShippingAddress');
        $reflectionMethod->setAccessible(true);

        $customer = $this->getContainer()->get('models')->getRepository(Customer::class)->find(1);
        static::assertInstanceOf(Customer::class, $customer);
        $oderStruct = new OrderStruct();
        $oderStruct->setShippingAddressId(1);

        $result = $reflectionMethod->invokeArgs($orderFactory, [$oderStruct, $customer]);

        $defaultShippingAddress = $customer->getDefaultShippingAddress();
        static::assertInstanceOf(Address::class, $defaultShippingAddress);
        static::assertSame(3, $defaultShippingAddress->getId());
        static::assertSame('20001', $result->getCustomer()->getNumber());
        static::assertSame('Musterhausen', $result->getCity());
    }

    public function testCreateBillingAddressShoudAddDefaultShippingToCustomer(): void
    {
        $orderFactory = $this->getOrderFactory();

        $reflectionMethod = (new \ReflectionClass(OrderFactory::class))->getMethod('createBillingAddress');
        $reflectionMethod->setAccessible(true);

        $customer = $this->getContainer()->get('models')->getRepository(Customer::class)->find(1);
        static::assertInstanceOf(Customer::class, $customer);
        $oderStruct = new OrderStruct();
        $oderStruct->setBillingAddressId(1);

        $result = $reflectionMethod->invokeArgs($orderFactory, [$oderStruct, $customer]);

        $defaultBillingAddress = $customer->getDefaultBillingAddress();
        static::assertInstanceOf(Address::class, $defaultBillingAddress);
        static::assertSame(1, $defaultBillingAddress->getId());
        static::assertSame('20001', $result->getCustomer()->getNumber());
        static::assertSame('Musterhausen', $result->getCity());
    }

    private function getOrderFactory(): OrderFactory
    {
        return new OrderFactory(
            $this->getContainer()->get('models'),
            $this->getContainer()->get('swag_backend_order.order.detail_factory')
        );
    }
}
