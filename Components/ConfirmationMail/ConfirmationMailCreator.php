<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use DateTime;
use sArticles;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;
use Shopware\Models\Article\Unit;
use Shopware\Models\Attribute\Payment;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\Translation\ShippingTranslator;
use SwagBackendOrder\Components\Translation\PaymentTranslator;

class ConfirmationMailCreator
{
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    /**
     * @var PaymentTranslator
     */
    private $paymentTranslator;

    /**
     * @var ShippingTranslator
     */
    private $shippingTranslator;

    /**
     * @var ConfirmationMailRepository
     */
    private $confirmationMailRepository;

    /**
     * @var Repository
     */
    private $articleDetailRepository;

    /**
     * @var sArticles
     */
    private $sArticles;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @return ConfirmationMailCreator
     */
    public static function create()
    {
        return new self(
            new TaxCalculation(),
            Shopware()->Container()->get('swag_backend_order.payment_translator'),
            Shopware()->Container()->get('swag_backend_order.shipping_translator'),
            new ConfirmationMailRepository(Shopware()->Db(), Shopware()->Container()->get('dbal_connection')),
            Shopware()->Models()->getRepository(Detail::class),
            Shopware()->Config()
        );
    }

    /**
     * @param TaxCalculation $taxCalculation
     * @param PaymentTranslator $paymentTranslator
     * @param ShippingTranslator $shippingTranslator
     * @param ConfirmationMailRepository $confirmationMailRepository
     * @param Repository $articleDetailRepository
     * @param \Shopware_Components_Config $config
     */
    public function __construct(
        TaxCalculation $taxCalculation,
        PaymentTranslator $paymentTranslator,
        ShippingTranslator $shippingTranslator,
        ConfirmationMailRepository $confirmationMailRepository,
        Repository $articleDetailRepository,
        \Shopware_Components_Config $config
    ) {
        $this->taxCalculation = $taxCalculation;
        $this->paymentTranslator = $paymentTranslator;
        $this->shippingTranslator = $shippingTranslator;
        $this->confirmationMailRepository = $confirmationMailRepository;
        $this->articleDetailRepository = $articleDetailRepository;
        $this->config = $config;

        $this->sArticles = Shopware()->Modules()->Articles();
    }

    /**
     * @inheritdoc
     */
    public function prepareOrderDetailsConfirmationMailData(Order $orderModel)
    {
        /** @var Customer $customerModel */
        $customerModel = $orderModel->getCustomer();

        /** @var DateTime $orderDateTime */
        $orderDateTime = $orderModel->getOrderTime();

        $details = $this->confirmationMailRepository->getOrderDetailsByOrderId($orderModel->getId());

        foreach ($details as &$mailOrderPositions) {
            /** @var Detail $articleDetailModel */
            $articleDetailModel = $this->articleDetailRepository->findOneBy(['number' => $mailOrderPositions['articleordernumber']]);
            $mailOrderPositions['articlename'] = $mailOrderPositions['name'];

            $articleDetail = $this->confirmationMailRepository->getArticleDetailsByOrderNumber($mailOrderPositions['articleordernumber']);

            $mailOrderPositions = array_merge($mailOrderPositions, $articleDetail);

            $mailOrderPositions['additional_details'] = $this->sArticles->sGetProductByOrdernumber($articleDetailModel->getNumber());

            $mailOrderPositions['netprice'] = $this->taxCalculation->getNetPrice($mailOrderPositions['price'], $mailOrderPositions['tax_rate']);
            $mailOrderPositions['amount'] = $mailOrderPositions['price'] * $mailOrderPositions['quantity'];
            $mailOrderPositions['amountnet'] = $mailOrderPositions['netprice'] * $mailOrderPositions['quantity'];
            $mailOrderPositions['priceNumeric'] = $mailOrderPositions['price'];

            $mailOrderPositions['image'] = $this->sArticles->sGetArticlePictures(
                $mailOrderPositions['articleID'],
                true,
                $this->config->get('sTHUMBBASKET'),
                $articleDetailModel->getNumber()
            );

            $mailOrderPositions = $this->getOrderDetailAttributes($mailOrderPositions);

            $mailOrderPositions['partnerID'] = '';
            $mailOrderPositions['shippinginfo'] = 1;
            $mailOrderPositions['esdarticle'] = null;
            $mailOrderPositions['userID'] = $customerModel->getId();
            $mailOrderPositions['currencyFactor'] = $orderModel->getCurrencyFactor();
            $mailOrderPositions['datum'] = $orderDateTime->format('Y-m-d H:i:s');

            unset($mailOrderPositions['name'], $mailOrderPositions['articleordernumber']);
        }

        return $details;
    }

    /**
     * @param array $mailOrderPositions
     * @return array
     */
    private function getOrderDetailAttributes(array $mailOrderPositions)
    {
        $mailOrderPositions['ob_attr1'] = '';
        $mailOrderPositions['ob_attr2'] = '';
        $mailOrderPositions['ob_attr3'] = '';
        $mailOrderPositions['ob_attr4'] = '';
        $mailOrderPositions['ob_attr5'] = '';
        $mailOrderPositions['ob_attr6'] = '';
        return $mailOrderPositions;
    }

    /**
     * @inheritdoc
     */
    public function prepareOrderConfirmationMailData(Order $orderModel)
    {
        $orderMail = [];
        /** @var DateTime $orderTime */
        $orderTime = $orderModel->getOrderTime();

        /** @var Shop $languageShopModel */
        $languageShopModel = $orderModel->getLanguageSubShop();

        /** @var Shop $languageShop */
        $shopModel = $orderModel->getShop();

        $shippingModel = $orderModel->getShipping();
        $billingModel = $orderModel->getBilling();
        $shippingStateModel = $shippingModel->getState();
        $billingStateModel = $billingModel->getState();
        $customerModel = $orderModel->getCustomer();

        $orderAttributes = $this->confirmationMailRepository->getOrderAttributesByOrderId($orderModel->getId());
        $customer = $this->confirmationMailRepository->getCustomerByUserId($customerModel->getId());

        $orderMail['sOrderNumber'] = $orderModel->getNumber();

        $orderMail['sOrderDay'] = $orderTime->format('d.m.Y');
        $orderMail['sOrderTime'] = $orderTime->format('H:i');

        $orderMail['sLanguage'] = $languageShopModel->getId();
        $orderMail['sSubShop'] = $shopModel->getId();
        $orderMail['attributes'] = $orderAttributes;
        $orderMail['additional']['user'] = $customer;

        $orderMail = $this->setOrderCosts($orderModel, $orderMail);
        $orderMail = $this->setBillingAddress($orderModel, $orderMail, $billingStateModel);
        $orderMail = $this->setShippingAddress($orderModel, $orderMail, $shippingStateModel);

        $orderMail['sDispatch'] = $this->getTranslatedShipping($orderModel, $languageShopModel);
        $orderMail['additional']['payment'] = $this->getTranslatedPayment($orderModel, $languageShopModel);

        $orderMail['sPaymentTable'] = [];
        $orderMail['sComment'] = '';
        $orderMail['additional']['show_net'] = $orderModel->getNet();
        $orderMail['additional']['charge_var'] = 1;

        return $orderMail;
    }

    /**
     * @param Order $orderModel
     * @param array $orderMail
     * @return array
     */
    private function setOrderCosts(Order $orderModel, array $orderMail)
    {
        $orderMail['sCurrency'] = $orderModel->getCurrency();
        $orderMail['sAmount'] = $orderModel->getInvoiceAmount() . ' ' . $orderModel->getCurrency();
        $orderMail['sAmountNet'] = $orderModel->getInvoiceAmountNet() . ' ' . $orderModel->getCurrency();
        $orderMail['sShippingCosts'] = $orderModel->getInvoiceShipping() . ' ' . $orderModel->getCurrency();
        if ($orderModel->getNet()) {
            $orderMail['sShippingCosts'] = $orderModel->getInvoiceShippingNet() . ' ' . $orderModel->getCurrency();
        }
        return $orderMail;
    }

    /**
     * @param Order $orderModel
     * @param array $orderMail
     * @param State $billingStateModel
     * @return array
     */
    private function setBillingAddress(Order $orderModel, array $orderMail, $billingStateModel)
    {
        $billingAddress = $this->confirmationMailRepository->getBillingAddressByOrderId($orderModel->getId());
        $billingCountry = $this->confirmationMailRepository->getCountryByCountryId($billingAddress['countryID']);

        $orderMail['billingaddress'] = $billingAddress;
        $orderMail['additional']['country'] = $billingCountry;
        $orderMail['additional']['state'] = [];
        if ($billingStateModel) {
            $billingState = $this->confirmationMailRepository->getStateByStateId($billingStateModel->getId());
            $orderMail['additional']['state'] = $billingState;
        }
        return $orderMail;
    }

    /**
     * @param Order $orderModel
     * @param array $orderMail
     * @param State $shippingStateModel
     * @return array
     */
    private function setShippingAddress(Order $orderModel, array $orderMail, $shippingStateModel)
    {
        $shippingAddress = $this->confirmationMailRepository->getShippingAddressByOrderId($orderModel->getId());
        $shippingCountry = $this->confirmationMailRepository->getCountryByCountryId($shippingAddress['countryID']);

        $orderMail['shippingaddress'] = $shippingAddress;
        $orderMail['additional']['countryShipping'] = $shippingCountry;
        $orderMail['additional']['stateShipping'] = [];
        if ($shippingStateModel) {
            $shippingStateModel = $this->confirmationMailRepository->getStateByStateId($shippingStateModel->getId());
            $orderMail['additional']['stateShipping'] = $shippingStateModel;
        }
        return $orderMail;
    }

    /**
     * @param Order $orderModel
     * @param Shop $languageShopModel
     * @return array
     */
    private function getTranslatedShipping(Order $orderModel, Shop $languageShopModel)
    {
        /** @var Dispatch $dispatchModel */
        $dispatchModel = $orderModel->getDispatch();

        $dispatch = $this->confirmationMailRepository->getDispatchByDispatchId($dispatchModel->getId());
        return $this->shippingTranslator->translate($dispatch, $languageShopModel->getId());
    }

    /**
     * @param Order $orderModel
     * @param Shop $languageShop
     * @return array
     */
    private function getTranslatedPayment(Order $orderModel, Shop $languageShop)
    {
        /** @var Payment $paymentModel */
        $paymentModel = $orderModel->getPayment();

        $payment = $this->confirmationMailRepository->getPaymentmeanByPaymentmeanId($paymentModel->getId());
        return $this->paymentTranslator->translate($payment, $languageShop->getId());
    }
}