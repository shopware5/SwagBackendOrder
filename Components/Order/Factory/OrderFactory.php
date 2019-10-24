<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\Order\Factory;

use Shopware\Bundle\AccountBundle\Service\AddressServiceInterface;
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
use SwagBackendOrder\Components\Order\Struct\PositionStruct;

class OrderFactory
{
    const ORDER_STATUS_OPEN = 0;
    const PAYMENT_STATUS_OPEN = 17;
    const DEFAULT_DEVICE_TYPE = 'Backend';

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var AddressServiceInterface
     */
    private $addressService;

    /**
     * @var DetailFactory
     */
    private $detailFactory;

    public function __construct(ModelManager $modelManager, AddressServiceInterface $addressService, DetailFactory $detailFactory)
    {
        $this->modelManager = $modelManager;
        $this->addressService = $addressService;
        $this->detailFactory = $detailFactory;
    }

    /**
     * @return Order
     */
    public function create(OrderStruct $orderStruct)
    {
        $order = $this->initOrderModel($orderStruct);

        $customer = $this->modelManager->find(Customer::class, $orderStruct->getCustomerId());
        $order->setCustomer($customer);

        $dispatch = $this->modelManager->find(Dispatch::class, $orderStruct->getDispatchId());
        $order->setDispatch($dispatch);

        $payment = $this->modelManager->find(Payment::class, $orderStruct->getPaymentId());
        $order->setPayment($payment);

        $orderStatus = $this->modelManager->getReference(Status::class, self::ORDER_STATUS_OPEN);
        $order->setOrderStatus($orderStatus);

        $paymentStatus = $this->modelManager->getReference(Status::class, self::PAYMENT_STATUS_OPEN);
        $order->setPaymentStatus($paymentStatus);

        $languageSubShop = $this->modelManager->find(Shop::class, $orderStruct->getLanguageShopId());
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

        /** @var Currency $currency */
        $currency = $this->modelManager->getReference(Currency::class, $orderStruct->getCurrencyId());
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

        $order->setPaymentInstances([$this->createPaymentInstance($order)]);

        return $order;
    }

    /**
     * Workaround to fix 'Partner can not be null.' exception.
     *
     *
     * @return Order
     */
    private function initOrderModel(OrderStruct $orderStruct)
    {
        $connection = $this->modelManager->getConnection();
        $sql = 'INSERT INTO s_order (ordernumber) VALUES (?)';
        $connection->executeQuery($sql, [$orderStruct->getNumber()]);

        return $this->modelManager->find(Order::class, $connection->lastInsertId());
    }

    /**
     * @return Detail[]
     */
    private function createOrderDetails(OrderStruct $orderStruct, Order $order)
    {
        $details = [];

        /** @var PositionStruct $positionStruct */
        foreach ($orderStruct->getPositions() as $positionStruct) {
            $detail = $this->detailFactory->create($positionStruct, $orderStruct->getNetOrder());
            $detail->setNumber($orderStruct->getNumber());
            $detail->setOrder($order);
            $details[] = $detail;
        }

        return $details;
    }

    /**
     * @return PaymentInstance
     */
    private function createPaymentInstance(Order $orderModel)
    {
        $paymentId = $orderModel->getPayment()->getId();
        $paymentInstance = new PaymentInstance();

        /** @var PaymentData[] $paymentDataModel */
        $paymentDataModel = $orderModel->getCustomer()->getPaymentData()->filter(function (PaymentData $paymentData) use ($paymentId) {
            return $paymentData->getPaymentMeanId() == $paymentId;
        });

        if ($paymentDataModel[0] instanceof PaymentData) {
            /** @var PaymentData $paymentDataModel */
            $paymentDataModel = $paymentDataModel[0];

            $paymentInstance->setBankName($paymentDataModel->getBankName());
            $paymentInstance->setBankCode($paymentDataModel->getBankCode());
            $paymentInstance->setAccountHolder($paymentDataModel->getAccountHolder());

            $paymentInstance->setIban($paymentDataModel->getIban());
            $paymentInstance->setBic($paymentDataModel->getBic());

            $paymentInstance->setBankCode($paymentDataModel->getBankCode());
            $paymentInstance->setAccountNumber($paymentDataModel->getAccountHolder());
        }

        $paymentInstance->setPaymentMean($orderModel->getPayment());

        $paymentInstance->setOrder($orderModel);
        $paymentInstance->setCreatedAt($orderModel->getOrderTime());

        $paymentInstance->setCustomer($orderModel->getCustomer());
        $paymentInstance->setFirstName($orderModel->getBilling()->getFirstName());
        $paymentInstance->setLastName($orderModel->getBilling()->getLastName());
        $paymentInstance->setAddress($orderModel->getBilling()->getStreet());
        $paymentInstance->setZipCode($orderModel->getBilling()->getZipCode());
        $paymentInstance->setCity($orderModel->getBilling()->getCity());
        $paymentInstance->setAmount($orderModel->getInvoiceAmount());

        return $paymentInstance;
    }

    /**
     * @return OrderAttributes
     */
    private function createOrderAttributes(OrderStruct $orderStruct)
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

    /**
     * @param $customer
     *
     * @return Shipping
     */
    private function createShippingAddress(OrderStruct $orderStruct, $customer)
    {
        $shippingAddress = $this->modelManager->find(Address::class, $orderStruct->getShippingAddressId());
        $shipping = new Shipping();
        $shipping->fromAddress($shippingAddress);
        $shipping->setCustomer($customer);

        return $shipping;
    }

    /**
     * @param $customer
     *
     * @return Billing
     */
    private function createBillingAddress(OrderStruct $orderStruct, $customer)
    {
        $billingAddress = $this->modelManager->find(Address::class, $orderStruct->getBillingAddressId());
        $billing = new Billing();
        $billing->fromAddress($billingAddress);
        $billing->setCustomer($customer);

        return $billing;
    }
}
