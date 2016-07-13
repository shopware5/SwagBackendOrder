<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components;

use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail as ArticleDetail;
use Shopware\Models\Attribute\Order as OrderAttribute;
use Shopware\Models\Attribute\OrderDetail;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Billing as CustomerBilling;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\PaymentData;
use Shopware\Models\Customer\Shipping as CustomerShipping;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Shipping;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Payment\PaymentInstance;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Tax\Tax;

class CreateBackendOrder
{
    /**
     * sets the default desktop type if no desktop type was chosen
     */
    const DEFAULT_DESKTOP_TYPE = 'Backend';

    /**
     * holds the order id
     *
     * @var int
     */
    private $orderId;

    /**
     * is true if billing and shipping are equal
     *
     * @var bool
     */
    private $equalBillingAddress = false;

    /**
     * @param array $data
     * @param int|string $orderNumber
     * @return Order
     */
    public function createOrder(array $data, $orderNumber)
    {
        $positions = $data['position'];
        $net = $data['net'];

        /**
         * creates an empty row
         * -> workaround for the partner model (you must pass one, but not every order has a partner)
         */
        $sql = 'INSERT INTO s_order (ordernumber) VALUES (?)';

        Shopware()->Db()->query($sql, [$orderNumber]);

        $sql = 'SELECT id FROM s_order WHERE ordernumber = ?';
        $this->orderId = Shopware()->Db()->fetchOne($sql, [$orderNumber]);

        /** @var Order $orderModel */
        $orderModel = Shopware()->Models()->find(Order::class, $this->orderId);

        /** @var Customer $customerModel */
        $customerModel = Shopware()->Models()->find(Customer::class, $data['customerId']);
        $orderModel->setCustomer($customerModel);

        /** @var Dispatch $dispatchModel */
        $dispatchModel = Shopware()->Models()->getReference(Dispatch::class, $data['dispatchId']);
        $orderModel->setDispatch($dispatchModel);


        /** @var Payment $paymentModel */
        $paymentModel = Shopware()->Models()->getReference(Payment::class, $data['paymentId']);
        $orderModel->setPayment($paymentModel);

        /**
         * 0 = order status open
         *
         * @var Status $orderStatusModel
         */
        $orderStatusModel = Shopware()->Models()->getReference(Status::class, 0);
        $orderModel->setOrderStatus($orderStatusModel);

        /**
         * 17 = payment status open
         *
         * @var Status $paymentStatusModel
         */
        $paymentStatusModel = Shopware()->Models()->getReference(Status::class, 17);
        $orderModel->setPaymentStatus($paymentStatusModel);

        /** @var Shop $languageSubShopModel */
        $languageSubShopModel = Shopware()->Models()->getReference(Shop::class, $data['languageShopId']);
        $orderModel->setLanguageSubShop($languageSubShopModel);

        $orderModel->setInvoiceShippingNet($data['shippingCostsNet']);
        if ($net) {
            $orderModel->setInvoiceShipping($data['shippingCostsNet']);
        } else {
            $orderModel->setInvoiceShipping($data['shippingCosts']);
        }

        $orderModel->setInvoiceAmount($data['total']);
        $orderModel->setInvoiceAmountNet($data['totalWithoutTax']);

        $orderModel->setShop($customerModel->getShop());

        $orderModel->setNumber($orderNumber);

        $orderModel->setOrderTime(new \DateTime('now'));

        if ($data['desktopType'] !== '' && $data['desktopType'] !== null && isset($data['desktopType'])) {
            $orderModel->setDeviceType($data['desktopType']);
        } else {
            $orderModel->setDeviceType(self::DEFAULT_DESKTOP_TYPE);
        }

        $orderModel->setTransactionId('');
        $orderModel->setComment('');
        $orderModel->setCustomerComment('');
        $orderModel->setInternalComment('');
        $orderModel->setNet($net);
        $orderModel->setTaxFree($net);
        $orderModel->setTemporaryId('');
        $orderModel->setReferer('');
        $orderModel->setTrackingCode('');
        $orderModel->setRemoteAddress('');

        /** @var Currency $currencyModel */
        $currencyModel = Shopware()->Models()->getReference(Currency::class, $data['currencyId']);
        $orderModel->setCurrencyFactor($currencyModel->getFactor());
        $orderModel->setCurrency($currencyModel->getCurrency());

        /** @var Detail[] $details */
        $details = [];

        //checks if more than one position was passed
        if ($this->isAssoc($positions)) {
            $details[] = $this->createOrderDetail($positions, $orderModel);

            $lastDetail = end($details);
            if (!$lastDetail instanceof Detail) {
                $this->deleteOrder();

                return $lastDetail;
            }
        } else {
            foreach ($positions as $position) {
                $details[] = $this->createOrderDetail($position, $orderModel);

                $lastDetail = end($details);
                if (!$lastDetail instanceof Detail) {
                    $this->deleteOrder();

                    return $lastDetail;
                }
            }
        }
        $orderModel->setDetails($details);

        /** @var OrderAttribute[] $orderAttributes */
        $orderAttributes = $this->setOrderAttributes($data['orderAttribute'][0]);
        $orderModel->setAttribute($orderAttributes);

        /** @var Billing $billingModel */
        $billingModel = $this->createBillingAddress($data);
        $orderModel->setBilling($billingModel);

        /** @var Shipping $shippingModel */
        $shippingModel = $this->createShippingAddress($data);
        $orderModel->setShipping($shippingModel);

        /** @var PaymentInstance $paymentInstance */
        $paymentInstance = $this->preparePaymentInstance($orderModel);
        $orderModel->setPaymentInstances([ $paymentInstance ]);

        Shopware()->Models()->persist($paymentInstance);
        Shopware()->Models()->persist($orderModel);
        Shopware()->Models()->flush();

        /*
         * the 'amountNet' changes to the 'amount' after the first flushing but it was written correctly to the db
         * this is only for using the model without problems
         */
        $orderModel->setInvoiceAmountNet($data['totalWithoutTax']);
        Shopware()->Models()->persist($orderModel);
        Shopware()->Models()->flush();

        if (is_null($billingModel->getState())) {
            Shopware()->Db()->update('s_order_billingaddress', ['stateID' => 0], ['id' => $billingModel->getId()]);
        }
        if (is_null($shippingModel->getState())) {
            Shopware()->Db()->update('s_order_shippingaddress', ['stateID' => 0], ['id' => $shippingModel->getId()]);
        }

        return $orderModel;
    }

    /**
     * @param array $position
     * @param Order $orderModel
     * @return array
     */
    private function createOrderDetail($position, $orderModel)
    {
        $orderDetailModel = new Detail();

        $articleIds = Shopware()->Db()->fetchRow(
            "SELECT a.id, ad.id AS detailId
              FROM s_articles a, s_articles_details ad
              WHERE a.id = ad.articleID
              AND ad.ordernumber = ?",
            [$position['articleNumber']]
        );

        //checks if the article exists
        if (empty($articleIds)) {
            $articleIdentification = $this->createInvalidArticleIdentificationForErrorMessage($position);

            return ['success' => false, 'article' => $articleIdentification];
        }

        $articleId = $articleIds['id'];
        $articleDetailId = $articleIds['detailId'];

        /** @var Article $articleModel */
        $articleModel = Shopware()->Models()->find(Article::class, $articleId);

        /** @var ArticleDetail $articleDetailModel */
        $articleDetailModel = Shopware()->Models()->find(ArticleDetail::class, $articleDetailId);

        if (is_object($articleDetailModel->getUnit())) {
            $unitName = $articleDetailModel->getUnit()->getName();
        } else {
            $unitName = 0;
        }

        /** @var Tax $taxModel */
        $taxModel = Shopware()->Models()->find(Tax::class, $position['taxId']);
        $orderDetailModel->setTax($taxModel);
        if ($orderModel->getNet()) {
            $orderDetailModel->setTaxRate(0);
        } else {
            $orderDetailModel->setTaxRate($position['taxRate']);
        }

        /** checks if it is an esdArticle */
        $orderDetailModel->setEsdArticle(0);

        /** @var DetailStatus $detailStatusModel */
        $detailStatusModel = Shopware()->Models()->find(DetailStatus::class, 0);
        $orderDetailModel->setStatus($detailStatusModel);

        $orderDetailModel->setArticleId($articleModel->getId());
        $orderDetailModel->setArticleName($articleModel->getName());
        $orderDetailModel->setArticleNumber($articleDetailModel->getNumber());
        $orderDetailModel->setPrice($position['price']);
        $orderDetailModel->setMode($position['mode']);
        $orderDetailModel->setQuantity($position['quantity']);
        $orderDetailModel->setShipped(0);
        $orderDetailModel->setUnit($unitName);
        $orderDetailModel->setPackUnit($articleDetailModel->getPackUnit());

        $orderDetailModel->setNumber($orderModel->getNumber());
        $orderDetailModel->setOrder($orderModel);

        /** @var OrderDetail $orderDetailAttributeModel */
        $orderDetailAttributeModel = new OrderDetail();
        $orderDetailAttributeModel->setAttribute1('');
        $orderDetailAttributeModel->setAttribute2('');
        $orderDetailAttributeModel->setAttribute3('');
        $orderDetailAttributeModel->setAttribute4('');
        $orderDetailAttributeModel->setAttribute5('');
        $orderDetailAttributeModel->setAttribute6('');
        $orderDetailModel->setAttribute($orderDetailAttributeModel);

        return $orderDetailModel;
    }

    /**
     * sets the order attributes
     *
     * @param array $attributes
     * @return OrderAttribute
     */
    private function setOrderAttributes(array $attributes)
    {
        $orderAttributeModel = new OrderAttribute();
        $orderAttributeModel->setAttribute1($attributes['attribute1']);
        $orderAttributeModel->setAttribute2($attributes['attribute2']);
        $orderAttributeModel->setAttribute3($attributes['attribute3']);
        $orderAttributeModel->setAttribute4($attributes['attribute4']);
        $orderAttributeModel->setAttribute5($attributes['attribute5']);
        $orderAttributeModel->setAttribute6($attributes['attribute6']);

        return $orderAttributeModel;
    }

    /**
     * creates the billing address which belongs to the order and
     * saves it as the new last used address
     *
     * @param array $data
     * @return Billing
     */
    private function createBillingAddress($data)
    {
        /** @var CustomerBilling $billingCustomerModel */
        $billingCustomerModel = Shopware()->Models()->find(CustomerBilling::class, $data['billingAddressId']);

        $billingOrderModel = new Billing();
        $billingOrderModel->setCity($billingCustomerModel->getCity());
        $billingOrderModel->setStreet($billingCustomerModel->getStreet());
        $billingOrderModel->setSalutation($billingCustomerModel->getSalutation());
        $billingOrderModel->setZipCode($billingCustomerModel->getZipCode());
        $billingOrderModel->setFirstName($billingCustomerModel->getFirstName());
        $billingOrderModel->setLastName($billingCustomerModel->getLastName());
        $billingOrderModel->setAdditionalAddressLine1($billingCustomerModel->getAdditionalAddressLine1());
        $billingOrderModel->setAdditionalAddressLine2($billingCustomerModel->getAdditionalAddressLine2());
        $billingOrderModel->setVatId($billingCustomerModel->getVatId());
        $billingOrderModel->setPhone($billingCustomerModel->getPhone());
        $billingOrderModel->setCompany($billingCustomerModel->getCompany());
        $billingOrderModel->setDepartment($billingCustomerModel->getDepartment());
        $billingOrderModel->setNumber($billingCustomerModel->getCustomer()->getNumber());
        $billingOrderModel->setCustomer($billingCustomerModel->getCustomer());

        if ($billingCustomerModel->getCountryId()) {
            /** @var Country $countryModel */
            $countryModel = Shopware()->Models()->find(Country::class, $billingCustomerModel->getCountryId());
            $billingOrderModel->setCountry($countryModel);
        }

        if ($billingCustomerModel->getStateId()) {
            /** @var State $stateModel */
            $stateModel = Shopware()->Models()->find(State::class, $billingCustomerModel->getStateId());
            $billingOrderModel->setState($stateModel);
        }

        return $billingOrderModel;
    }

    /**
     * creates the shipping address which belongs to the order and
     * saves it as the new last used address
     *
     * @param array $data
     * @return Shipping
     */
    private function createShippingAddress($data)
    {
        if ($data['shippingAddressId']) {
            /** @var CustomerShipping $addressHolderModel */
            $addressHolderModel = Shopware()->Models()->find(CustomerShipping::class, $data['shippingAddressId']);
        } else {
            /** @var CustomerBilling $shippingAddressHolder */
            $addressHolderModel = Shopware()->Models()->find(CustomerBilling::class, $data['billingAddressId']);
            $this->equalBillingAddress = true;
        }

        $shippingOrderModel = new Shipping();
        $shippingOrderModel->setCity($addressHolderModel->getCity());
        $shippingOrderModel->setStreet($addressHolderModel->getStreet());
        $shippingOrderModel->setSalutation($addressHolderModel->getSalutation());
        $shippingOrderModel->setZipCode($addressHolderModel->getZipCode());
        $shippingOrderModel->setFirstName($addressHolderModel->getFirstName());
        $shippingOrderModel->setLastName($addressHolderModel->getLastName());
        $shippingOrderModel->setAdditionalAddressLine1($addressHolderModel->getAdditionalAddressLine1());
        $shippingOrderModel->setAdditionalAddressLine2($addressHolderModel->getAdditionalAddressLine2());
        $shippingOrderModel->setCompany($addressHolderModel->getCompany());
        $shippingOrderModel->setDepartment($addressHolderModel->getDepartment());
        $shippingOrderModel->setCustomer($addressHolderModel->getCustomer());

        if ($addressHolderModel->getCountryId()) {
            /** @var Country $countryModel */
            $countryModel = Shopware()->Models()->find(Country::class, $addressHolderModel->getCountryId());
            $shippingOrderModel->setCountry($countryModel);
        }

        if ($addressHolderModel->getStateId()) {
            /** @var State $stateModel */
            $stateModel = Shopware()->Models()->find(State::class, $addressHolderModel->getStateId());
            $shippingOrderModel->setState($stateModel);
        }

        return $shippingOrderModel;
    }

    /**
     * @param Order $orderModel
     * @return PaymentInstance
     */
    private function preparePaymentInstance(Order $orderModel)
    {
        $paymentId = $orderModel->getPayment()->getId();
        $customerId = $orderModel->getCustomer()->getId();

        $paymentInstanceModel = new PaymentInstance();

        /** @var PaymentData[] $paymentDataModel */
        $paymentDataModel = $this->getCustomerPaymentData($customerId, $paymentId);

        if ($paymentDataModel[0] instanceof PaymentData) {
            /** @var PaymentData $paymentDataModel */
            $paymentDataModel = $paymentDataModel[0];

            $paymentInstanceModel->setBankName($paymentDataModel->getBankName());
            $paymentInstanceModel->setBankCode($paymentDataModel->getBankCode());
            $paymentInstanceModel->setAccountHolder($paymentDataModel->getAccountHolder());

            $paymentInstanceModel->setIban($paymentDataModel->getIban());
            $paymentInstanceModel->setBic($paymentDataModel->getBic());

            $paymentInstanceModel->setBankCode($paymentDataModel->getBankCode());
            $paymentInstanceModel->setAccountNumber($paymentDataModel->getAccountHolder());
        }

        $paymentInstanceModel->setPaymentMean($orderModel->getPayment());

        $paymentInstanceModel->setOrder($orderModel);
        $paymentInstanceModel->setCreatedAt($orderModel->getOrderTime());

        $paymentInstanceModel->setCustomer($orderModel->getCustomer());
        $paymentInstanceModel->setFirstName($orderModel->getBilling()->getFirstName());
        $paymentInstanceModel->setLastName($orderModel->getBilling()->getLastName());
        $paymentInstanceModel->setAddress($orderModel->getBilling()->getStreet());
        $paymentInstanceModel->setZipCode($orderModel->getBilling()->getZipCode());
        $paymentInstanceModel->setCity($orderModel->getBilling()->getCity());
        $paymentInstanceModel->setAmount($orderModel->getInvoiceAmount());

        return $paymentInstanceModel;
    }

    /**
     * selects the payment data by user and payment id
     *
     * @param int $customerId
     * @param int $paymentId
     * @return PaymentData
     */
    public function getCustomerPaymentData($customerId, $paymentId)
    {
        $paymentDataRepository = Shopware()->Models()->getRepository(PaymentData::class);
        $paymentModel = $paymentDataRepository->findBy(['paymentMeanId' => $paymentId, 'customer' => $customerId]);

        return $paymentModel;
    }

    /**
     * helper function which checks if it is an associative array,
     * to distinguish between an order with one or an order with more than
     * one position
     *
     * @param array $array
     * @return bool
     */
    private function isAssoc(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * deletes the empty row
     */
    private function deleteOrder()
    {
        if (isset($this->orderId) && $this->orderId > 0) {
            Shopware()->Db()->query('DELETE FROM s_order WHERE id  = ?', $this->orderId);
        }
    }

    /**
     * @return bool
     */
    public function getEqualBillingAddress()
    {
        return $this->equalBillingAddress;
    }

    /**
     * If the user entered an invalid product he needs to know which article was invalid. This will return the
     * information we have of the product.
     *
     * @param array $position
     * @return string
     */
    private function createInvalidArticleIdentificationForErrorMessage($position)
    {
        if (empty($position['articleName'])) {
            return $position['articleNumber'];
        }

        if (empty($position['articleNumber'])) {
            return $position['articleName'];
        }

        return $position['articleName'] . ' - ' . $position['articleNumber'];
    }
}
