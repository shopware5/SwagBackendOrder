<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use Shopware\Models\Article\Detail as ProductVariant;
use Shopware\Models\Article\Repository;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Shipping;
use Shopware\Models\Shop\Locale;
use Shopware\Models\Shop\Shop;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Components\Translation\ShippingTranslator;

class ConfirmationMailCreator
{
    /**
     * Once the minimum version is Shopware 5.7.4, \Shopware\Bundle\CartBundle\CartPositionsMode::PAYMENT_SURCHARGE_OR_DISCOUNT should be used instead
     */
    public const PAYMENT_SURCHARGE_OR_DISCOUNT = 4;

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
    private $productVariantRepository;

    /**
     * @var \sArticles
     */
    private $productCoreClass;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var NumberFormatterWrapper
     */
    private $numberFormatterWrapper;

    public function __construct(
        TaxCalculation $taxCalculation,
        PaymentTranslator $paymentTranslator,
        ShippingTranslator $shippingTranslator,
        ConfirmationMailRepository $confirmationMailRepository,
        Repository $productVariantRepository,
        \Shopware_Components_Config $config,
        NumberFormatterWrapper $numberFormatterWrapper,
        \sArticles $productCoreClass
    ) {
        $this->taxCalculation = $taxCalculation;
        $this->paymentTranslator = $paymentTranslator;
        $this->shippingTranslator = $shippingTranslator;
        $this->confirmationMailRepository = $confirmationMailRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->config = $config;
        $this->numberFormatterWrapper = $numberFormatterWrapper;
        $this->productCoreClass = $productCoreClass;
    }

    public function prepareOrderDetailsConfirmationMailData(Order $orderModel, Locale $localeModel): array
    {
        $customerModel = $orderModel->getCustomer();
        if (!$customerModel instanceof Customer) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no customer', $orderModel->getId()));
        }

        $orderDateTime = $orderModel->getOrderTime();

        $details = $this->confirmationMailRepository->getOrderDetailsByOrderId($orderModel->getId());

        foreach ($details as &$result) {
            // Handle position as discount
            if ((int) $result['modus'] === self::PAYMENT_SURCHARGE_OR_DISCOUNT) {
                $result['ordernumber'] = $result['articleordernumber'];
                $result['articlename'] = $result['name'];
                $result['shippinginfo'] = 1;
                $result['esdarticle'] = null;
                $result['userID'] = $customerModel->getId();
                $result['currencyFactor'] = $orderModel->getCurrencyFactor();
                $result['datum'] = $orderDateTime->format('Y-m-d H:i:s');
                $result['amount'] = $result['price'];

                unset($result['name'], $result['articleordernumber']);
                continue;
            }

            $productVariant = $this->productVariantRepository->findOneBy(['number' => $result['articleordernumber']]);
            if (!$productVariant instanceof ProductVariant) {
                throw new \RuntimeException(sprintf('Could not find %s with number "%s"', ProductVariant::class, $result['articleordernumber']));
            }
            $result['articlename'] = $result['name'];

            $productVariantArray = $this->confirmationMailRepository->getProductVariantsByOrderNumber($result['articleordernumber']);

            $result = \array_merge($result, $productVariantArray);

            $result['additional_details'] = $this->productCoreClass->sGetProductByOrdernumber((string) $productVariant->getNumber());

            $result = $this->setPositionPrices($result, $localeModel);

            $result['image'] = $this->productCoreClass->sGetArticlePictures(
                $result['articleID'],
                true,
                $this->config->get('sTHUMBBASKET'),
                $productVariant->getNumber()
            );

            $result = $this->getOrderDetailAttributes($result);

            $result['partnerID'] = '';
            $result['shippinginfo'] = 1;
            $result['esdarticle'] = null;
            $result['userID'] = $customerModel->getId();
            $result['currencyFactor'] = $orderModel->getCurrencyFactor();
            $result['datum'] = $orderDateTime->format('Y-m-d H:i:s');

            unset($result['name'], $result['articleordernumber']);
        }

        return $details;
    }

    public function prepareOrderConfirmationMailData(Order $orderModel): array
    {
        $result = [];
        $orderTime = $orderModel->getOrderTime();

        $languageShopModel = $orderModel->getLanguageSubShop();

        $languageLocaleModel = $languageShopModel->getLocale();

        $shopModel = $orderModel->getShop();

        $shippingModel = $orderModel->getShipping();
        if (!$shippingModel instanceof Shipping) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no shipping address', $orderModel->getId()));
        }
        $billingModel = $orderModel->getBilling();
        if (!$billingModel instanceof Billing) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no billing address', $orderModel->getId()));
        }
        $shippingStateModel = $shippingModel->getState();
        $billingStateModel = $billingModel->getState();
        $customerModel = $orderModel->getCustomer();
        if (!$customerModel instanceof Customer) {
            throw new \RuntimeException(sprintf('Order with ID "%s" has no customer', $orderModel->getId()));
        }

        $orderAttributes = $this->confirmationMailRepository->getOrderAttributesByOrderId($orderModel->getId());
        $customer = $this->confirmationMailRepository->getCustomerByUserId($customerModel->getId());

        $result['sOrderNumber'] = $orderModel->getNumber();

        $result['sOrderDay'] = $orderTime->format('d.m.Y');
        $result['sOrderTime'] = $orderTime->format('H:i');

        $result['sLanguage'] = $languageShopModel->getId();
        $result['sSubShop'] = $shopModel->getId();
        $result['attributes'] = $orderAttributes;
        $result['additional']['user'] = $customer;

        $result = $this->setOrderCosts($orderModel, $result, $languageLocaleModel);
        $result = $this->setBillingAddress($orderModel, $result, $billingStateModel);
        $result = $this->setShippingAddress($orderModel, $result, $shippingStateModel);

        $result['sDispatch'] = $this->getTranslatedShipping($orderModel, $languageShopModel);
        $result['additional']['payment'] = $this->getTranslatedPayment($orderModel, $languageShopModel);

        $result['sPaymentTable'] = [];
        $result['sComment'] = '';
        $result['additional']['show_net'] = $orderModel->getNet();
        $result['additional']['charge_var'] = 1;

        // Prevent displaying of gross prices on confirmation mail.
        if ($orderModel->getTaxFree() === 1) {
            $result['sNet'] = 1;
        }

        return $result;
    }

    private function setPositionPrices(array $mailOrderPositions, Locale $localeModel): array
    {
        $netPrice = $this->taxCalculation->getNetPrice((float) $mailOrderPositions['price'], (float) $mailOrderPositions['tax_rate']);
        $mailOrderPositions['netprice'] = $this->numberFormatterWrapper->format($netPrice, $localeModel->getLocale());

        $amount = $mailOrderPositions['price'] * $mailOrderPositions['quantity'];
        $mailOrderPositions['amount'] = $this->numberFormatterWrapper->format($amount, $localeModel->getLocale());

        $amountNet = $mailOrderPositions['netprice'] * $mailOrderPositions['quantity'];
        $mailOrderPositions['amountnet'] = $this->numberFormatterWrapper->format($amountNet, $localeModel->getLocale());

        $mailOrderPositions['priceNumeric'] = $this->numberFormatterWrapper->format(
            (float) $mailOrderPositions['price'],
            $localeModel->getLocale()
        );

        $mailOrderPositions['price'] = $this->numberFormatterWrapper->format(
            (float) $mailOrderPositions['price'],
            $localeModel->getLocale()
        );

        return $mailOrderPositions;
    }

    private function getOrderDetailAttributes(array $mailOrderPositions): array
    {
        $mailOrderPositions['ob_attr1'] = '';
        $mailOrderPositions['ob_attr2'] = '';
        $mailOrderPositions['ob_attr3'] = '';
        $mailOrderPositions['ob_attr4'] = '';
        $mailOrderPositions['ob_attr5'] = '';
        $mailOrderPositions['ob_attr6'] = '';

        return $mailOrderPositions;
    }

    private function setOrderCosts(Order $orderModel, array $orderMail, Locale $localeModel): array
    {
        $orderMail['sCurrency'] = $orderModel->getCurrency();

        $formattedAmount = $this->numberFormatterWrapper->format($orderModel->getInvoiceAmount(), $localeModel->getLocale());
        $orderMail['sAmount'] = $formattedAmount . ' ' . $orderModel->getCurrency();

        $formattedAmountNet = $this->numberFormatterWrapper->format(
            $orderModel->getInvoiceAmountNet(),
            $localeModel->getLocale()
        );
        $orderMail['sAmountNet'] = $formattedAmountNet . ' ' . $orderModel->getCurrency();

        $formattedShippingCosts = $this->numberFormatterWrapper->format(
            $orderModel->getInvoiceShipping(),
            $localeModel->getLocale()
        );
        $orderMail['sShippingCosts'] = $formattedShippingCosts . ' ' . $orderModel->getCurrency();

        if ($orderModel->getNet()) {
            $formattedShippingCostsNet = $this->numberFormatterWrapper->format(
                $orderModel->getInvoiceShippingNet(),
                $localeModel->getLocale()
            );
            $orderMail['sShippingCosts'] = $formattedShippingCostsNet . ' ' . $orderModel->getCurrency();
        }

        return $orderMail;
    }

    private function setBillingAddress(Order $orderModel, array $orderMail, ?State $billingStateModel): array
    {
        $billingAddress = $this->confirmationMailRepository->getBillingAddressByOrderId($orderModel->getId());
        $billingCountry = $this->confirmationMailRepository->getCountryByCountryId((int) $billingAddress['countryID']);

        $orderMail['billingaddress'] = $billingAddress;
        $orderMail['additional']['country'] = $billingCountry;
        $orderMail['additional']['state'] = [];
        if ($billingStateModel) {
            $billingState = $this->confirmationMailRepository->getStateByStateId($billingStateModel->getId());
            $orderMail['additional']['state'] = $billingState;
        }

        return $orderMail;
    }

    private function setShippingAddress(Order $orderModel, array $orderMail, ?State $shippingStateModel): array
    {
        $shippingAddress = $this->confirmationMailRepository->getShippingAddressByOrderId($orderModel->getId());
        $shippingCountry = $this->confirmationMailRepository->getCountryByCountryId((int) $shippingAddress['countryID']);

        $orderMail['shippingaddress'] = $shippingAddress;
        $orderMail['additional']['countryShipping'] = $shippingCountry;
        $orderMail['additional']['stateShipping'] = [];
        if ($shippingStateModel) {
            $shippingStateModel = $this->confirmationMailRepository->getStateByStateId($shippingStateModel->getId());
            $orderMail['additional']['stateShipping'] = $shippingStateModel;
        }

        return $orderMail;
    }

    private function getTranslatedShipping(Order $orderModel, Shop $languageShopModel): array
    {
        $dispatchModel = $orderModel->getDispatch();

        $dispatch = $this->confirmationMailRepository->getDispatchByDispatchId($dispatchModel->getId());

        return $this->shippingTranslator->translate($dispatch, $languageShopModel->getId());
    }

    private function getTranslatedPayment(Order $orderModel, Shop $languageShop): array
    {
        $paymentModel = $orderModel->getPayment();

        $payment = $this->confirmationMailRepository->getPaymentmeanByPaymentmeanId($paymentModel->getId());

        return $this->paymentTranslator->translate($payment, $languageShop->getId());
    }
}
