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
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailCreator;
use SwagBackendOrder\Components\ConfirmationMail\ConfirmationMailRepository;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;
use SwagBackendOrder\Components\CustomerRepository;
use SwagBackendOrder\Components\Order\Hydrator\OrderHydrator;
use SwagBackendOrder\Components\Order\OrderService;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Components\Order\Validator\OrderValidator;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductValidator;
use SwagBackendOrder\Components\PaymentTranslator;
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
            /** @var \Shopware\Models\Shop\Repository $shopRepository */
            $shopRepository = $this->get('models')->getRepository(Shop::class);
            $shop = $shopRepository->getActiveById($orderStruct->getLanguageShopId());
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

        /** @var RequestHydrator $requestHydrator */
        $requestHydrator = $this->get('swag_backend_order.price_calculation.request_hydrator');
        $requestStruct = $requestHydrator->hydrateFromRequest($this->Request()->getParams());

        $builder = $this->getProductRepository()->getProductQueryBuilder($number);
        $result = $builder->getQuery()->getArrayResult()[0];

        $currencyFactor = 1;
        $currency = $this->getModelManager()->find(Currency::class, $requestStruct->getCurrencyId());
        if ($currency instanceof Currency) {
            $currencyFactor = $currency->getFactor();
        }

        $isNetPrice = true;
        $priceContext = new PriceContext(
            (float) $result['price'],
            (float) $result['tax'],
            $isNetPrice,
            $requestStruct->isTaxFree(),
            $currencyFactor
        );

        $price = $this->getProductCalculator()->calculate($priceContext);
        $result['price'] = $price->getRoundedGrossPrice();
        if ($requestStruct->isDisplayNet() || $requestStruct->isTaxFree()) {
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
        $paymentTranslator = $this->get('swag_backend_order.payment_translator');

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['payment'])
            ->from(Payment::class, 'payment')
            ->orderBy('payment.active', 'DESC');

        $paymentMethods = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod = $paymentTranslator->translate($paymentMethod, $languageId);
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
     * method which selects all shipping costs
     */
    public function getShippingCostsAction()
    {
        $dispatchTranslator = $this->get('swag_backend_order.shipping_translator');

        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select(['dispatch', 'shipping'])
            ->from(ShippingCost::class, 'shipping')
            ->innerJoin('shipping.dispatch', 'dispatch')
            ->groupBy('dispatch.id');
        $shippingCosts = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($shippingCosts as &$shippingCost) {
            $shippingCost['dispatch'] = $dispatchTranslator->translate($shippingCost['dispatch'], $languageId);
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
        $confirmationMailCreator = new ConfirmationMailCreator(
            new TaxCalculation(),
            $this->get('swag_backend_order.payment_translator'),
            $this->get('swag_backend_order.shipping_translator'),
            new ConfirmationMailRepository($this->get('dbal_connection')),
            $this->get('models')->getRepository(Detail::class),
            $this->get('config'),
            new NumberFormatterWrapper(),
            $this->get('modules')->Articles()
        );

        try {
            $context = $confirmationMailCreator->prepareOrderConfirmationMailData($orderModel);
            $context['sOrderDetails'] = $confirmationMailCreator->prepareOrderDetailsConfirmationMailData(
                $orderModel, $orderModel->getLanguageSubShop()->getLocale()
            );

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
            $totalPositionPrice->setNet($this->getTotalPrice($positionPrice->getRoundedNetPrice(), $position->quantity));
            $totalPositionPrice->setGross($this->getTotalPrice($positionPrice->getRoundedGrossPrice(), $position->quantity));
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

        if ($requestStruct->isDisplayNet()) {
            $productSum = $totalPriceResult->getSum()->getRoundedNetPrice();
        }

        if ($requestStruct->isTaxFree()) {
            $productSum = $totalPriceResult->getSum()->getRoundedNetPrice();
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
            $requestStruct->isPreviousTaxFree(),
            $requestStruct->getPreviousCurrencyId()
        );
        $basePrice = $this->getProductCalculator()->calculateBasePrice($previousPriceContext);

        $currentPriceContext = $this->getPriceContextFactory()->create(
            $basePrice,
            $position->taxRate,
            true,
            $requestStruct->isTaxFree(),
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
        $dispatchTaxRate = $this->getDispatchTaxRate($requestStruct->getDispatchId(), $requestStruct->getBasketTaxRates());

        // Get base/gross shipping costs (even if taxfree)
        $previousPriceContext = $this->getPriceContextFactory()->create(
            $requestStruct->getShippingCosts(),
            $dispatchTaxRate,
            $requestStruct->isPreviousDisplayNet(),
            $requestStruct->isPreviousTaxFree(),
            $requestStruct->getPreviousCurrencyId()
        );
        $baseShippingPrice = $this->getShippingCalculator()->calculateBasePrice($previousPriceContext);

        // Calculate actual gross & net shipping costs for order
        $currentPriceContext = $this->getPriceContextFactory()->create(
            $baseShippingPrice,
            $dispatchTaxRate,
            $requestStruct->isDisplayNet(),
            $requestStruct->isTaxFree(),
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
}
