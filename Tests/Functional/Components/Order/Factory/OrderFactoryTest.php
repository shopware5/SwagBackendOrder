<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order\Factory;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Customer\Customer;
use SwagBackendOrder\Components\Order\Factory\OrderFactory;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;

class OrderFactoryTest extends TestCase
{
    public function testCreateShippingAddressShoudAddDefaultShippingToCustomer()
    {
        $orderFactory = $this->getOrderFactory();

        $reflectionMethod = (new \ReflectionClass(OrderFactory::class))->getMethod('createShippingAddress');
        $reflectionMethod->setAccessible(true);

        $customer = Shopware()->Container()->get('models')->getRepository(Customer::class)->find(1);
        $oderStruct = new OrderStruct();
        $oderStruct->setShippingAddressId(1);

        $result = $reflectionMethod->invokeArgs($orderFactory, [$oderStruct, $customer]);

        static::assertSame(3, $customer->getDefaultShippingAddress()->getId());
        static::assertSame('20001', $result->getCustomer()->getNumber());
        static::assertSame('Musterhausen', $result->getCity());
    }

    public function testCreateBillingAddressShoudAddDefaultShippingToCustomer()
    {
        $orderFactory = $this->getOrderFactory();

        $reflectionMethod = (new \ReflectionClass(OrderFactory::class))->getMethod('createBillingAddress');
        $reflectionMethod->setAccessible(true);

        $customer = Shopware()->Container()->get('models')->getRepository(Customer::class)->find(1);
        $oderStruct = new OrderStruct();
        $oderStruct->setBillingAddressId(1);

        $result = $reflectionMethod->invokeArgs($orderFactory, [$oderStruct, $customer]);

        static::assertSame(1, $customer->getDefaultBillingAddress()->getId());
        static::assertSame('20001', $result->getCustomer()->getNumber());
        static::assertSame('Musterhausen', $result->getCity());
    }

    private function getOrderFactory()
    {
        return new OrderFactory(
            Shopware()->Container()->get('models'),
            Shopware()->Container()->get('shopware_account.address_service'),
            Shopware()->Container()->get('swag_backend_order.order.detail_factory')
        );
    }
}
