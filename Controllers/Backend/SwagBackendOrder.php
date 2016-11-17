<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\PaymentData;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Tax\Tax;
use SwagBackendOrder\Components\CustomerRepository;
use SwagBackendOrder\Components\Order\Hydrator\OrderHydrator;
use SwagBackendOrder\Components\Order\OrderService;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Components\Order\Validator\OrderValidator;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ShippingPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Calculator\TotalPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContextFactory;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\Result\TotalPricesResult;
use SwagBackendOrder\Components\PriceCalculation\Struct\RequestStruct;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\ProductRepository;

class Shopware_Controllers_Backend_SwagBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Disable template engine for all actions
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), ['index', 'load'])) {
            $this->Front()->Plugins()->Json()->setRenderer(true);
        }
    }

    /**
     * Return a list of customer on search or return a single customer on select.
     */
    public function getCustomerAction()
    {
        /** @var CustomerRepository $repository */
        $repository = $this->get('swag_backend_order.customer_repository');

        if ($filter = $this->getListRequestParam()) {
            $result = $repository->getList($filter);

            $this->view->assign([
                'data' => $result,
                'total' => count($result),
                'success' => true
            ]);
            return;
        }

        $customerId = (int) $this->Request()->get('searchParam');
        $result = $repository->get($customerId);

        $this->view->assign([
            'data' => $result,
            'total' => count($result),
            'success' => true
        ]);
    }

    public function createOrderAction()
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->get('models');

        /** @var OrderHydrator $orderHydrator */
        $orderHydrator = $this->get('swag_backend_order.order.order_hydrator');

        /** @var OrderValidator $orderValidator */
        $orderValidator = $this->get('swag_backend_order.order.order_validator');

        $orderStruct = $orderHydrator->hydrateFromRequest($this->Request());
        $violations = $orderValidator->validate($orderStruct);
        if ($violations->getMessages()) {
            $this->view->assign([
                'success' => false,
                'violations' => $violations->getMessages()
            ]);
            return;
        }

        $modelManager->getConnection()->beginTransaction();
        try {
            //we need to fake a shop instance if we want to use the Articles Module
            /** @var \Shopware\Models\Shop\Repository $shopRepo */
            $shopRepo = $this->get('models')->getRepository(Shop::class);
            $shop = $shopRepo->getActiveById($orderStruct->getLanguageShopId());
            $shop->registerResources();

            /** @var OrderService $orderService */
            $orderService = $this->get('swag_backend_order.order.service');
            $order = $orderService->create($orderStruct);

            $modelManager->getConnection()->commit();

            $this->sendOrderConfirmationMail($order);
        } catch (InvalidOrderException $e) {
            $modelManager->getConnection()->rollBack();

            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            return;
        } catch (\Exception $e) {
            $modelManager->getConnection()->rollBack();

            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            return;
        }

        $this->view->assign([
            'success' => true,
            'orderId' => $order->getId(),
            'ordernumber' => $order->getNumber()
        ]);
    }

    public function getArticlesAction()
    {
        $params = $this->Request()->getParams();
        $search = $params['filter'][0]['value'];

        if (!isset($params['filter'][0]['value'])) {
            $search = '%' . $this->Request()->get('searchParam') . '%';
        }
        $builder = $this->getProductRepository()->getProductQueryBuilder($search);
        $result = $builder->getQuery()->getArrayResult();
        $total = count($result);

        foreach ($result as &$article) {
            $article['price'] = $this->getTaxCalculation()->getGrossPrice($article['price'], $article['tax']);
        }

        $this->view->assign(
            [
                'success' => true,
                'data' => $result,
                'total' => $total
            ]
        );
    }

    public function getProductAction()
    {
        $number = $this->Request()->getParam('ordernumber');
        $customerId = $this->Request()->getParam('customerId');
        //Default Group key of shopware
        $groupKey = 'EK';

        /** @var RequestHydrator $requestHydrator */
        $requestHydrator = $this->get('swag_backend_order.price_calculation.request_hydrator');
        $requestStruct = $requestHydrator->hydrateFromRequest($this->Request()->getParams());

        if ($customerId != 0) {
            $customer = $this->getCustomerRepository()->get($customerId);
            $groupKey = $customer['groupKey'];
        }

        $builder = $this->getProductRepository()->getProductQueryBuilder($number, $groupKey);

        //check query result if customer id is not 0 and fire query another time with default group
        if ($customerId != 0) {
            if (count($builder->getQuery()->getArrayResult()) == 0) {
                //Another Query with shopware default user grup
                $builder = $this->getProductRepository()->getProductQueryBuilder($number);
            }
        }

        $result = $builder->getQuery()->getArrayResult()[0];

        $currencyFactor = 1;
        $currency = $this->getModelManager()->find(Currency::class, $requestStruct->getCurrencyId());
        if ($currency instanceof Currency) {
            $currencyFactor = $currency->getFactor();
        }

        $priceContext = new PriceContext((float) $result['price'], (float) $result['tax'], true, $currencyFactor);

        $price = $this->getProductCalculator()->calculate($priceContext);
        $result['price'] = $price->getRoundedGrossPrice();
        if ($requestStruct->isDisplayNet()) {
            $result['price'] = $price->getRoundedNetPrice();
        }

        $this->view->assign([
            'data' => $result,
            'success' => true
        ]);
    }

    /**
     * gets all available payments for the backend order
     */
    public function getPaymentAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['payment'])
            ->from(Payment::class, 'payment')
            ->orderBy('payment.active', 'DESC');

        $paymentMethods = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod = $this->translatePayment($paymentMethod, $languageId);
        }

        $total = count($paymentMethods);

        $this->view->assign(
            [
                'data' => $paymentMethods,
                'total' => $total,
                'success' => true
            ]
        );
    }

    /**
     * @param array $paymentMethod
     * @param int $languageId
     * @return array
     */
    private function translatePayment(array $paymentMethod, $languageId)
    {
        $translation = new Shopware_Components_Translation();
        $paymentTranslations = $translation->read($languageId, 'config_payment');

        $paymentId = $paymentMethod['id'];

        if (!is_null($paymentTranslations[$paymentId]['description'])) {
            $paymentMethod['description'] = $paymentTranslations[$paymentId]['description'];
        }

        //for the confirmation mail template
        $paymentMethod['additionaldescription'] = $paymentTranslations[$paymentId]['additionalDescription'];
        $paymentMethod['additionalDescription'] = $paymentTranslations[$paymentId]['additionalDescription'];

        return $paymentMethod;
    }

    /**
     * method which selects all shipping costs
     */
    public function getShippingCostsAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select(['dispatch', 'shipping'])
            ->from(ShippingCost::class, 'shipping')
            ->innerJoin('shipping.dispatch', 'dispatch')
            ->groupBy('dispatch.id');
        $shippingCosts = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($shippingCosts as &$shippingCost) {
            $shippingCost['dispatch'] = $this->translateDispatch($shippingCost['dispatch'], $languageId);
        }

        $total = count($shippingCosts);

        $this->view->assign(
            [
                'data' => $shippingCosts,
                'total' => $total,
                'success' => true
            ]
        );
    }

    /**
     * @param array $dispatch
     * @param int $languageId
     * @return array
     */
    private function translateDispatch(array $dispatch, $languageId)
    {
        $translation = new Shopware_Components_Translation();
        $dispatchTranslations = $translation->read($languageId, 'config_dispatch');

        $dispatchId = $dispatch['id'];

        if (!is_null($dispatchTranslations[$dispatchId]['dispatch_name'])) {
            $dispatch['name'] = $dispatchTranslations[$dispatchId]['dispatch_name'];
            $dispatch['dispatch_name'] = $dispatchTranslations[$dispatchId]['dispatch_name'];
        }

        $dispatch['description'] = $dispatchTranslations[$dispatchId]['description'];

        return $dispatch;
    }

    public function getCurrenciesAction()
    {
        $repository = Shopware()->Models()->getRepository(Currency::class);

        $builder = $repository->createQueryBuilder('c');
        $builder->select(
            [
                'c.id as id',
                'c.name as name',
                'c.currency as currency',
                'c.symbol as symbol',
                'c.factor as factor',
                'c.default as default'
            ]
        );

        $query = $builder->getQuery();

        $total = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign(
            [
                'success' => true,
                'data' => $data,
                'total' => $total
            ]
        );
    }

    /**
     * reads the plugin config and passes it to the ext js application
     */
    public function getPluginConfigAction()
    {
        $configReader = $this->container->get('shopware.plugin.config_reader');
        $pluginConfig = $configReader->getByPluginName('SwagBackendOrder');

        $desktopTypes = $pluginConfig['desktopTypes'];
        $desktopTypes = explode(',', $desktopTypes);
        $validationMail = $pluginConfig['validationMail'];

        $config = [];
        $config['desktopTypes'] = [];
        $count = 0;

        foreach ($desktopTypes as $desktopType) {
            $config['desktopTypes'][$count]['id'] = $count;
            $config['desktopTypes'][$count]['name'] = $desktopType;
            $count++;
        }

        $config['validationMail'] = $validationMail;

        $total = count($config);

        $this->view->assign(
            [
                'success' => true,
                'data' => $config,
                'total' => $total
            ]
        );
    }

    /**
     * assigns the payment data for a user to ExtJs to show the data in the view
     */
    public function getCustomerPaymentDataAction()
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->get('models');
        $request = $this->Request()->getParams();
        $customerId = $request['customerId'];
        $paymentId = $request['paymentId'];

        $paymentDataRepository = $modelManager->getRepository(PaymentData::class);
        /** @var PaymentData[] $paymentModel */
        $paymentModel = $paymentDataRepository->findBy(['paymentMeanId' => $paymentId, 'customer' => $customerId]);
        $paymentModel = $paymentModel[0];

        $accountHolder = false;
        if (!is_null($paymentModel)) {
            /** @var Payment $paymentMean */
            $paymentMean = $paymentModel->getPaymentMean();
            if ($paymentModel->getUseBillingData() && $paymentMean->getName() == 'sepa') {
                $accountHolder = $this->getAccountHolder($customerId);
            }
        }

        $payment = $this->get('models')->toArray($paymentModel);
        if ($accountHolder) {
            $payment['accountHolder'] = $accountHolder;
        }

        $this->view->assign([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * @return array
     */
    public function getLanguageSubShopsAction()
    {
        $mainShopId = $this->Request()->getParam('mainShopId');

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('shops')
            ->from(Shop::class, 'shops')
            ->where('shops.mainId = :mainShopId')
            ->orWhere('shops.id = :mainShopId')
            ->andWhere('shops.active = 1')
            ->setParameter('mainShopId', $mainShopId);

        $result = $builder->getQuery()->getArrayResult();

        //Gets the correct language name for every shop
        foreach ($result as &$shop) {
            /** @var Shop $shopModel */
            $shopModel = Shopware()->Models()->find(Shop::class, $shop['id']);
            $shop['name'] = $shopModel->getLocale()->getLanguage();
        }
        $total = count($result);

        $this->view->assign(
            [
                'data' => $result,
                'success' => true,
                'total' => $total
            ]
        );
    }

    /**
     * checks if the article which was added or edited is no voucher or esd article
     *
     * @return bool
     */
    public function validateEditAction()
    {
        $data = $this->Request()->getParams();
        $articleNumber = (string) $data['articleNumber'];
        $quantity = (int) $data['quantity'];

        $productContext = new ProductContext($articleNumber, $quantity);

        /** @var ProductValidator $validator */
        $validator = $this->get('swag_backend_order.order.product_validator');
        $violations = $validator->validate($productContext);

        if ($violations->getMessages()) {
            $this->view->assign([
                'success' => false,
                'violations' => $violations->getMessages()
            ]);
            return;
        }

        $this->view->assign('success', true);
    }

    /**
     * @param Order $orderModel
     */
    private function sendOrderConfirmationMail($orderModel)
    {
        try {
            $context = $this->prepareOrderConfirmationMailData($orderModel);
            $context['sOrderDetails'] = $this->prepareOrderDetailsConfirmationMailData($orderModel);

            $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
            $mail->addTo($context["additional"]["user"]["email"]);
            $mail->send();

            //If configured send an email to the shop owner
            $mailNotToShopOwner = Shopware()->Config()->get('no_order_mail');
            if (!$mailNotToShopOwner) {
                $mail->addTo(Shopware()->Config()->get('mail'));
                $mail->send();
            }
        } catch (\Exception $e) {
            $this->view->assign('mail', $e->getMessage());
        }
    }

    /**
     * prepares the correct array structure for the mail template
     *
     * @param Order $orderModel
     * @return array
     */
    private function prepareOrderDetailsConfirmationMailData($orderModel)
    {
        $details = Shopware()->Db()->fetchAll(
            'SELECT * FROM s_order_details WHERE orderID = ?',
            [$orderModel->getId()]
        );

        $articleModule = Shopware()->Modules()->Articles();

        foreach ($details as &$detail) {
            /** @var Shopware\Models\Article\Repository $articleDetailRepository */
            $articleDetailRepository = Shopware()->Models()->getRepository(Detail::class);

            $articleOrderNumber = $detail['articleordernumber'];
            unset($detail['articleordernumber']);

            /** @var Detail $articleDetailModel */
            $articleDetailModel = $articleDetailRepository->findOneBy(['number' => $articleOrderNumber]);

            $detail['articlename'] = $detail['name'];
            unset($detail['name']);

            $detail['userID'] = $orderModel->getCustomer()->getId();
            $detail['ordernumber'] = $articleOrderNumber;
            $detail['currencyFactor'] = $orderModel->getCurrencyFactor();
            $detail['mainDetailId'] = $articleDetailModel->getArticle()->getMainDetail()->getId();
            $detail['articleDetailId'] = $articleDetailModel->getId();
            $detail['instock'] = $articleDetailModel->getInStock();

            $detail['taxID'] = $articleDetailModel->getArticle()->getTax()->getId();
            if ($orderModel->getNet()) {
                $detail['taxID'] = 0;
            }

            $detail['maxpurchase'] = $articleDetailModel->getMaxPurchase();
            $detail['minpurchase'] = $articleDetailModel->getMinPurchase();
            $detail['purchasesteps'] = $articleDetailModel->getPurchaseSteps();
            $detail['stockmin'] = $articleDetailModel->getStockMin();
            $detail['suppliernumber'] = $articleDetailModel->getSupplierNumber();
            $detail['laststock'] = $articleDetailModel->getArticle()->getLastStock();
            $detail['purchaseunit'] = $articleDetailModel->getPurchaseUnit();
            $detail['releasedate'] = $articleDetailModel->getReleaseDate();
            $detail['modus'] = $articleDetailModel->getArticle()->getMode();
            $detail['datum'] = $orderModel->getOrderTime()->format('Y-m-d H:i:s');
            $detail['esdarticle'] = $articleDetailModel->getEsd();
            $detail['netprice'] = $this->getTaxCalculation()->getNetPrice($detail['price'], $detail['tax_rate']);
            $detail['amount'] = $detail['price'] * $detail['quantity'];
            $detail['amountnet'] = $detail['netprice'] * $detail['quantity'];
            $detail['priceNumeric'] = $detail['price'];
            $detail['image'] = $articleModule->sGetArticlePictures(
                $detail['articleID'],
                true,
                Shopware()->Config()->get('sTHUMBBASKET'),
                $articleOrderNumber
            );

            /**
             * order basket attributes fake
             */
            $detail['ob_attr1'] = '';
            $detail['ob_attr2'] = '';
            $detail['ob_attr3'] = '';
            $detail['ob_attr4'] = '';
            $detail['ob_attr5'] = '';
            $detail['ob_attr6'] = '';

            $detail['partnerID'] = '';
            $detail['shippinginfo'] = 1;

            if ($articleDetailModel->getUnit()) {
                $detail['unitID'] = $articleDetailModel->getUnit()->getId();
            }

            $detail['additional_details'] = $articleModule->sGetProductByOrdernumber($articleOrderNumber);
        }

        return $details;
    }

    /**
     * @param Order $orderModel
     * @return array
     */
    private function prepareOrderConfirmationMailData($orderModel)
    {
        $billingAddress = Shopware()->Db()->fetchRow(
            'SELECT *, userID AS customerBillingId FROM s_order_billingaddress WHERE orderID = ?',
            [$orderModel->getId()]
        );
        $billingAddressAttributes = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_order_billingaddress_attributes WHERE billingID = ?',
            [$billingAddress['id']]
        );
        if (!empty($billingAddressAttributes)) {
            $billingAddress = array_merge($billingAddress, $billingAddressAttributes);
        }

        $shippingAddress = Shopware()->Db()->fetchRow(
            'SELECT *, userID AS customerBillingId FROM s_order_shippingaddress WHERE orderID = ?',
            [$orderModel->getId()]
        );
        $shippingAddressAttributes = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_order_shippingaddress_attributes WHERE shippingID = ?',
            [$shippingAddress['id']]
        );
        if (!empty($shippingAddressAttributes)) {
            $shippingAddress = array_merge($shippingAddress, $shippingAddressAttributes);
        }

        $context['billingaddress'] = $billingAddress;
        $context['shippingaddress'] = $shippingAddress;

        $context['sOrderNumber'] = $orderModel->getNumber();

        $currency = $orderModel->getCurrency();
        $context['sCurrency'] = $currency;
        $context['sAmount'] = $orderModel->getInvoiceAmount() . ' ' . $currency;
        $context['sAmountNet'] = $orderModel->getInvoiceAmountNet() . ' ' . $currency;
        $context['sShippingCosts'] = $orderModel->getInvoiceShipping() . ' ' . $currency;

        $orderTime = $orderModel->getOrderTime();
        $context['sOrderDay'] = $orderTime->format('d.m.Y');
        $context['sOrderTime'] = $orderTime->format('H:i');

        $context['sComment'] = '';
        $context['sLanguage'] = $orderModel->getLanguageSubShop()->getId();
        $context['sSubShop'] = $orderModel->getShop()->getId();

        $orderAttributes = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_order_attributes WHERE orderID = ?',
            [$orderModel->getId()]
        );
        $context['attributes'] = $orderAttributes;

        $dispatch = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_premium_dispatch WHERE id = ?',
            [$orderModel->getDispatch()->getId()]
        );
        $dispatch = $this->translateDispatch($dispatch, $orderModel->getLanguageSubShop()->getId());
        $context['sDispatch'] = $dispatch;

        $user = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_user WHERE id = ?',
            [$orderModel->getCustomer()->getId()]
        );
        $context['additional']['user'] = $user;

        $country = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_countries WHERE id = ?',
            [$orderModel->getBilling()->getCountry()->getId()]
        );
        $context['additional']['country'] = $country;

        $context['additional']['state'] = [];
        if ($orderModel->getBilling()->getState()) {
            $state = Shopware()->Db()->fetchRow(
                'SELECT * FROM s_core_countries_states WHERE id = ?',
                [$orderModel->getBilling()->getState()->getId()]
            );
            $context['additional']['state'] = $state;
        }

        $country = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_countries WHERE id = ?',
            [$orderModel->getShipping()->getCountry()->getId()]
        );
        $context['additional']['countryShipping'] = $country;

        $context['additional']['stateShipping'] = [];
        if ($orderModel->getShipping()->getState()) {
            $state = Shopware()->Db()->fetchRow(
                'SELECT * FROM s_core_countries_states WHERE id = ?',
                [$orderModel->getShipping()->getState()->getId()]
            );
            $context['additional']['stateShipping'] = $state;
        }

        $payment = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_core_paymentmeans WHERE id = ?',
            [$orderModel->getPayment()->getId()]
        );
        $payment = $this->translatePayment($payment, $orderModel->getLanguageSubShop()->getId());

        $context['additional']['payment'] = $payment;

        $context['sPaymentTable'] = [];

        $context['additional']['show_net'] = $orderModel->getNet();
        $context['additional']['charge_var'] = 1;

        return $context;
    }

    /**
     * @return int
     */
    private function getBackendLanguage()
    {
        /** @var Shopware_Components_Auth $auth */
        $auth = Shopware()->Plugins()->Backend()->Auth()->checkAuth();
        $identity = $auth->getIdentity();

        return $identity->locale->getId();
    }

    /**
     * calculates the tax for this order
     */
    public function calculateBasketAction()
    {
        /** @var RequestHydrator $requestHydrator */
        $requestHydrator = $this->get('swag_backend_order.price_calculation.request_hydrator');
        $requestStruct = $requestHydrator->hydrateFromRequest($this->Request()->getParams());

        //Basket position price calculation
        $positionPrices = [];
        foreach ($requestStruct->getPositions() as &$position) {
            $positionPrice = $this->getPositionPrice($position, $requestStruct);

            $totalPositionPrice = new PriceResult();
            $totalPositionPrice->
                setNet($this->getTotalPrice($positionPrice->getRoundedNetPrice(), $position->quantity));
            $totalPositionPrice->
                setGross($this->getTotalPrice($positionPrice->getRoundedGrossPrice(), $position->quantity));
            $positionPrices[] = $totalPositionPrice;

            $position->price = $positionPrice->getRoundedGrossPrice();
            if ($requestStruct->isTaxFree() || $requestStruct->isDisplayNet()) {
                $position->price = $positionPrice->getRoundedNetPrice();
            }
            $position->total = $this->getTotalPrice($position->price, $position->quantity);
        }

        $dispatchPrice = $this->getShippingPrice($requestStruct);

        $totalPriceResult = $this->getTotalPriceCalculator()->calculate($positionPrices, $dispatchPrice);
        $result = $this->createBasketCalculationResult($totalPriceResult, $requestStruct);

        $this->view->assign([
            'data' => $result,
            'success' => true
        ]);
    }

    /**
     * @return string
     */
    private function getListRequestParam()
    {
        $data = $this->Request()->getParams();

        return $data['filter'][0]['value'];
    }

    /**
     * @param float $price
     * @param int $quantity
     * @return float
     */
    private function getTotalPrice($price, $quantity)
    {
        return $price * (float) $quantity;
    }

    /**
     * @return TaxCalculation
     */
    private function getTaxCalculation()
    {
        return $this->get('swag_backend_order.price_calculation.tax_calculation');
    }

    /**
     * @param int $customerId
     * @return string
     */
    private function getAccountHolder($customerId)
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->get('models');
        $customer = $modelManager->find(Customer::class, $customerId);

        return $customer->getBilling()->getFirstName() . ' ' . $customer->getBilling()->getLastName();
    }

    /**
     * @return TotalPriceCalculator
     */
    private function getTotalPriceCalculator()
    {
        return $this->get('swag_backend_order.price_calculation.total_price_calculator');
    }

    /**
     * @return ShippingPriceCalculator
     */
    private function getShippingCalculator()
    {
        return $this->get('swag_backend_order.price_calculation.shipping_calculator');
    }

    /**
     * @return ProductPriceCalculator
     */
    private function getProductCalculator()
    {
        return $this->get('swag_backend_order.price_calculation.product_calculator');
    }

    /**
     * @param TotalPricesResult $totalPriceResult
     * @param RequestStruct $requestStruct
     * @return array
     */
    private function createBasketCalculationResult(
        TotalPricesResult $totalPriceResult,
        RequestStruct $requestStruct
    ) {
        $shippingCosts = $totalPriceResult->getShipping()->getRoundedGrossPrice();
        $productSum = $totalPriceResult->getSum()->getRoundedGrossPrice();
        $total = $totalPriceResult->getTotal()->getRoundedGrossPrice();
        $taxSum = $totalPriceResult->getTaxAmount();

        if ($requestStruct->isTaxFree() || $requestStruct->isDisplayNet()) {
            $shippingCosts = $totalPriceResult->getShipping()->getRoundedNetPrice();
            $productSum = $totalPriceResult->getSum()->getRoundedNetPrice();
        }

        if ($requestStruct->isTaxFree()) {
            $total = $totalPriceResult->getTotal()->getRoundedNetPrice();
            $taxSum = 0.00;
        }

        //Total prices calculation
        $totalNetPrice = $totalPriceResult->getTotal()->getRoundedNetPrice();
        $shippingCostsNet = $totalPriceResult->getShipping()->getRoundedNetPrice();

        return [
            'totalWithoutTax' => $totalNetPrice,
            'sum' => $productSum,
            'total' => $total,
            'shippingCosts' => $shippingCosts,
            'shippingCostsNet' => $shippingCostsNet,
            'taxSum' => $taxSum,
            'positions' => $requestStruct->getPositions(),
            'dispatchTaxRate' => $totalPriceResult->getShipping()->getTaxRate()
        ];
    }

    /**
     * @return PriceContextFactory
     */
    private function getPriceContextFactory()
    {
        return $this->get('swag_backend_order.price_calculation.price_context_factory');
    }

    /**
     * @param Object $position
     * @param RequestStruct $requestStruct
     * @return PriceResult
     */
    private function getPositionPrice($position, $requestStruct)
    {
        $previousPriceContext = $this->getPriceContextFactory()->create(
            $position->price,
            $position->taxRate,
            $requestStruct->isPreviousDisplayNet(),
            $requestStruct->getPreviousCurrencyId()
        );
        $basePrice = $this->getProductCalculator()->calculateBasePrice($previousPriceContext);

        $currentPriceContext = $this->getPriceContextFactory()->create(
            $basePrice,
            $position->taxRate,
            true,
            $requestStruct->getCurrencyId()
        );

        return $this->getProductCalculator()->calculate($currentPriceContext);
    }

    /**
     * @param int $dispatchId
     * @param float[] $basketTaxRates
     * @return float
     * @throws \Exception
     */
    private function getDispatchTaxRate($dispatchId, $basketTaxRates = [])
    {
        if (is_null($dispatchId)) {
            return 0.00;
        }

        /** @var Dispatch $dispatch */
        $dispatch = $this->getModelManager()->find(Dispatch::class, $dispatchId);

        if (is_null($dispatch)) {
            throw new \Exception("Can not find given dispatch with id " . $dispatchId);
        }

        $taxId = $dispatch->getTaxCalculation();
        $tax = $this->getModelManager()->find(Tax::class, $taxId);

        if (!is_null($tax)) {
            return $tax->getTax();
        }

        if (empty($basketTaxRates)) {
            return 0.00;
        }

        return $this->getHighestDispatchTaxRate($basketTaxRates);
    }

    /**
     * @param float[] $basketTaxRates
     * @return float
     */
    private function getHighestDispatchTaxRate(array $basketTaxRates)
    {
        return max($basketTaxRates);
    }

    /**
     * @param RequestStruct $requestStruct
     * @return PriceResult
     */
    private function getShippingPrice($requestStruct)
    {
        $previousPriceContext = $this->getPriceContextFactory()->create(
            $requestStruct->getShippingCosts(),
            $requestStruct->getPreviousShippingTaxRate(),
            $requestStruct->isPreviousDisplayNet(),
            $requestStruct->getPreviousCurrencyId()
        );
        $baseShippingPrice = $this->getShippingCalculator()->calculateBasePrice($previousPriceContext);

        $currentPriceContext = $this->getPriceContextFactory()->create(
            $baseShippingPrice,
            $this->getDispatchTaxRate($requestStruct->getDispatchId(), $requestStruct->getBasketTaxRates()),
            $requestStruct->isDisplayNet(),
            $requestStruct->getCurrencyId()
        );

        return $this->getShippingCalculator()->calculate($currentPriceContext);
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository()
    {
        return $this->get('swag_backend_order.product_repository');
    }

    /**
     * @return CustomerRepository
     */
    private function getCustomerRepository()
    {
        return $this->get('swag_backend_order.customer_repository');
    }
}
