<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Components\Order;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Order;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagBackendOrder\Components\Order\B2BOrderService;
use SwagBackendOrder\Tests\B2bOrderTrait;

class B2BOrderServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use B2bOrderTrait;

    /**
     * @before
     */
    public function isB2bPlugin()
    {
        $isB2bPluginInstalled = $this->isB2bPluginInstalled();

        if (!$isB2bPluginInstalled) {
            static::markTestSkipped('SwagB2bPlugin is not installed');
        }
    }

    public function test_createB2BOrder_shouldEarlyReturn(): void
    {
        $this->b2bUserIsDebitor();

        $orderService = $this->getB2BOrderService(true);

        $order = $this->createOrder();

        $orderService->createB2BOrder($order);

        static::assertNull($this->getB2bOrder());
    }

    public function test_createB2BOrder_shouldReturnInTryCatch(): void
    {
        $this->b2bUserIsDebitor();

        $orderService = $this->getB2BOrderService();

        $order = $this->createOrder();

        $orderService->createB2BOrder($order);

        static::assertNull($this->getB2bOrder());
    }

    public function test_createB2BOrder_shouldCreateAB2bOrder(): void
    {
        $this->b2bUserIsDebitor();

        $orderService = $this->getB2BOrderService();

        $order = $this->createOrder(15, 2); // user with id 2 is the B2b user

        $orderService->createB2BOrder($order);

        $result = $this->getB2bOrder();

        static::assertNotNull($result);
        static::assertCount(1, $result);
        static::assertSame('20001', $result[0]['ordernumber']);
    }

    private function getB2BOrderService(bool $getEmptyService = false)
    {
        if ($getEmptyService) {
            return new B2BOrderService(null, null, null, null);
        }

        return new B2BOrderService(
            Shopware()->Container()->get('b2b_order.conversion_service'),
            Shopware()->Container()->get('b2b_front_auth.login_context'),
            Shopware()->Container()->get('b2b_front_auth.credential_builder'),
            Shopware()->Container()->get('b2b_sales_representative.client_debtor_authentication_identity_loader')
        );
    }

    private function createOrder(int $orderId = 15, int $customerId = 1): Order
    {
        // Order with id 15 has the number 20001
        // User with id 1 is the default ShopCustomer
        $order = new Order();
        $orderReflectionProperty = (new ReflectionClass(Order::class))->getProperty('id');
        $orderReflectionProperty->setAccessible(true);
        $orderReflectionProperty->setValue($order, $orderId);

        $customer = new Customer();
        $customerReflectionProperty = (new ReflectionClass(Customer::class))->getProperty('id');
        $customerReflectionProperty->setAccessible(true);
        $customerReflectionProperty->setValue($customer, $customerId);

        $order->setCustomer($customer);

        return $order;
    }
}
