<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\Order\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Order as OrderAttributes;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\PaymentData;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Shipping;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Payment\PaymentInstance;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use SwagBackendOrder\Components\Order\Struct\OrderStruct;

class OrderFactory
{
    public const ORDER_STATUS_OPEN = 0;
    public const PAYMENT_STATUS_OPEN = 17;
    public const DEFAULT_DEVICE_TYPE = 'Backend';

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var DetailFactory
     */
    private $detailFactory;

    public function __construct(ModelManager $modelManager, DetailFactory $detailFactory)
    {
        $this->modelManager = $modelManager;
        $this->detailFactory = $detailFactory;
    }

    public function create(OrderStruct $orderStruct): Order
    {
        $order = $this->initOrderModel($orderStruct);

        $customer = $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());
        if (!$customer instanceof Customer) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Customer::class, $orderStruct->getCustomerId()));
        }
        $order->setCustomer($customer);

        $dispatch = $this->modelManager->find(Dispatch::class, $orderStruct->getDispatchId());
        if (!$dispatch instanceof Dispatch) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Dispatch::class, $orderStruct->getDispatchId()));
        }
        $order->setDispatch($dispatch);

        $payment = $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
        if (!$payment instanceof Payment) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Payment::class, $orderStruct->getPaymentId()));
        }
        $order->setPayment($payment);

        $orderStatus = $this->modelManager->getReference(Status::class, self::ORDER_STATUS_OPEN);
        if (!$orderStatus instanceof Status) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Status::class, self::ORDER_STATUS_OPEN));
        }
        $order->setOrderStatus($orderStatus);

        $paymentStatus = $this->modelManager->getReference(Status::class, self::PAYMENT_STATUS_OPEN);
        if (!$paymentStatus instanceof Status) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Status::class, self::PAYMENT_STATUS_OPEN));
        }
        $order->setPaymentStatus($paymentStatus);

        $languageSubShop = $this->modelManager->find(Shop::class, $orderStruct->getLanguageShopId());
        if (!$languageSubShop instanceof Shop) {
            throw new \RuntimeException(sprintf('Could not find %s with ID %s', Shop::class, $orderStruct->getLanguageShopId()));
        }
        $order->setLanguageSubShop($languageSubShop);

        $order->setInvoiceShippingNet($orderStruct->getShippingCostsNet());
        $order->setInvoiceShipping($orderStruct->getShippingCosts());
        $order->setInvoiceShippingTaxRate($orderStruct->getShippingCostsTaxRate());

        $order->setInvoiceAmount($orderStruct->getTotal());
        $order->setInvoiceAmountNet($orderStruct->getTotalWithoutTax());

        $order->setShop($customer->getShop());

        $order->setOrderTime(new \DateTime());

        $order->setDeviceType(self::DEFAULT_DEVICE_TYPE);
        if ($orderStruct->getDeviceType()) {
            $order->setDeviceType($orderStruct->getDeviceType());
        }

        $order->setTransactionId('');
        $order->setComment('');
        $order->setCustomerComment('');
        $order->setInternalComment('');
        $order->setTemporaryId('');
        $order->setReferer('');
        $order->setTrackingCode('');
        $order->setRemoteAddress('');

        $order->setNet($orderStruct->getNetOrder() ? 1 : 0);
        $order->setTaxFree($orderStruct->isTaxFree() ? 1 : 0);
        if ($orderStruct->isTaxFree()) {
            $order->setNet(1);
        }

        $currency = $this->modelManager->getReference(Currency::class, $orderStruct->getCurrencyId());
        if (!$currency instanceof Currency) {
            throw new \RuntimeException(sprintf('Could not find %s with ID "%s"', Currency::class, $orderStruct->getCurrencyId()));
        }
        $order->setCurrencyFactor($currency->getFactor());
        $order->setCurrency($currency->getCurrency());

        $billing = $this->createBillingAddress($orderStruct, $customer);
        $order->setBilling($billing);

        $shipping = $this->createShippingAddress($orderStruct, $customer);
        $order->setShipping($shipping);

        $details = $this->createOrderDetails($orderStruct, $order);
        $order->setDetails($details);

        $attributes = $this->createOrderAttributes($orderStruct);
        $attributes->setOrder($order);
        $order->setAttribute($attributes);

        $order->setPaymentInstances(new ArrayCollection([$this->createPaymentInstance($order)]));

        return $order;
    }

    /**
     * Workaround to fix 'Partner can not be null.' exception.
     */
    private function initOrderModel(OrderStruct $orderStruct): Order
    {
        $connection = $this->modelManager->getConnection();
        $sql = 'INSERT INTO s_order (ordernumber) VALUES (?)';
        $connection->executeQuery($sql, [$orderStruct->getNumber()]);

        $order = $this->modelManager->find(Order::class, $connection->lastInsertId());
        if (!$order instanceof Order) {
            throw new \RuntimeException('Order with number "%s" could not be created properly');
        }

        return $order;
    }

    /**
     * @return Detail[]
     */
    private function createOrderDetails(OrderStruct $orderStruct, Order $order): array
    {
        $details = [];

        foreach ($orderStruct->getPositions() as $positionStruct) {
            $detail = $this->detailFactory->create($positionStruct, $orderStruct->getNetOrder());
            $detail->setNumber((string) $orderStruct->getNumber());
            $detail->setOrder($order);
            $details[] = $detail;
        }

        return $details;
    }

    private function createPaymentInstance(Order $orderModel): PaymentInstance
    {
        $paymentId = (int) $orderModel->getPayment()->getId();
        $paymentInstance = new PaymentInstance();

        if (!$orderModel->getCustomer() instanceof Customer) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no customer', $orderModel->getId()));
        }

        $paymentDataModel = $orderModel->getCustomer()->getPaymentData()->filter(function (PaymentData $paymentData) use ($paymentId) {
            return (int) $paymentData->getPaymentMeanId() === $paymentId;
        });

        if ($paymentDataModel[0] instanceof PaymentData) {
            $paymentDataModel = $paymentDataModel[0];

            $paymentInstance->setBankName((string) $paymentDataModel->getBankName());
            $paymentInstance->setBankCode((string) $paymentDataModel->getBankCode());
            $paymentInstance->setAccountHolder((string) $paymentDataModel->getAccountHolder());

            $paymentInstance->setIban((string) $paymentDataModel->getIban());
            $paymentInstance->setBic((string) $paymentDataModel->getBic());

            $paymentInstance->setBankCode((string) $paymentDataModel->getBankCode());
            $paymentInstance->setAccountNumber((string) $paymentDataModel->getAccountHolder());
        }

        $paymentInstance->setPaymentMean($orderModel->getPayment());

        $paymentInstance->setOrder($orderModel);
        $paymentInstance->setCreatedAt($orderModel->getOrderTime());

        if (!$orderModel->getBilling() instanceof Billing) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no billing address', $orderModel->getId()));
        }
        $paymentInstance->setCustomer($orderModel->getCustomer());
        $paymentInstance->setFirstName($orderModel->getBilling()->getFirstName());
        $paymentInstance->setLastName($orderModel->getBilling()->getLastName());
        $paymentInstance->setAddress($orderModel->getBilling()->getStreet());
        $paymentInstance->setZipCode($orderModel->getBilling()->getZipCode());
        $paymentInstance->setCity($orderModel->getBilling()->getCity());
        $paymentInstance->setAmount($orderModel->getInvoiceAmount());

        return $paymentInstance;
    }

    private function createOrderAttributes(OrderStruct $orderStruct): OrderAttributes
    {
        $attributeData = $orderStruct->getAttributes();

        $attributes = new OrderAttributes();
        $attributes->setAttribute1($attributeData['attribute1']);
        $attributes->setAttribute2($attributeData['attribute2']);
        $attributes->setAttribute3($attributeData['attribute3']);
        $attributes->setAttribute4($attributeData['attribute4']);
        $attributes->setAttribute5($attributeData['attribute5']);
        $attributes->setAttribute6($attributeData['attribute6']);

        return $attributes;
    }

    private function createShippingAddress(OrderStruct $orderStruct, Customer $customer): Shipping
    {
        $shipping = new Shipping();

        $shippingAddressId = $orderStruct->getShippingAddressId();
        $defaultShippingAddress = $customer->getDefaultShippingAddress();
        if ($defaultShippingAddress instanceof Address && $defaultShippingAddress->getId() === $shippingAddressId) {
            $shipping->fromAddress($defaultShippingAddress);
        } else {
            $shippingAddress = $this->modelManager->find(Address::class, $shippingAddressId);
            if (!$shippingAddress instanceof Address) {
                throw new \RuntimeException(sprintf('Order struct contains invalid shipping address ID %s', $shippingAddressId));
            }
            $shipping->fromAddress($shippingAddress);
        }

        $shipping->setCustomer($customer);

        return $shipping;
    }

    private function createBillingAddress(OrderStruct $orderStruct, Customer $customer): Billing
    {
        $billing = new Billing();

        $billingAddressId = $orderStruct->getBillingAddressId();
        $defaultBillingAddress = $customer->getDefaultBillingAddress();
        if ($defaultBillingAddress instanceof Address && $defaultBillingAddress->getId() === $billingAddressId) {
            $billing->fromAddress($defaultBillingAddress);
        } else {
            $billingAddress = $this->modelManager->find(Address::class, $billingAddressId);
            if (!$billingAddress instanceof Address) {
                throw new \RuntimeException(sprintf('Order struct contains invalid billing address ID %s', $billingAddressId));
            }
            $billing->fromAddress($billingAddress);
        }

        $billing->setCustomer($customer);

        return $billing;
    }
}
