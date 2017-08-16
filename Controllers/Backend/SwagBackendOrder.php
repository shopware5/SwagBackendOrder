<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Bundle\SearchBundle\Condition\SearchTermCondition;
use Shopware\Bundle\SearchBundle\ProductSearchInterface;
use Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Article\Detail;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\PaymentData;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Tax\Tax;
use SwagBackendOrder\Bundle\SearchBundle\Condition\VariantCondition;
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
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ShippingPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Calculator\TotalPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContextFactory;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\Result\TotalPricesResult;
use SwagBackendOrder\Components\PriceCalculation\Struct\PositionStruct;
use SwagBackendOrder\Components\PriceCalculation\Struct\RequestStruct;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Components\Translation\ShippingTranslator;

class Shopware_Controllers_Backend_SwagBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
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
                'success' => true,
            ]);

            return;
        }

        $customerId = (int) $this->Request()->get('searchParam');
        $result = $repository->get($customerId);

        $this->view->assign([
            'data' => $result,
            'total' => count($result),
            'success' => true,
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
                'violations' => $violations->getMessages(),
            ]);

            return;
        }

        $modelManager->getConnection()->beginTransaction();
        try {
            //we need to fake a shop instance if we want to use the Articles Module
            /** @var Repository $shopRepository */
            $shopRepository = $this->get('models')->getRepository(Shop::class);
            $shop = $shopRepository->getActiveById($orderStruct->getLanguageShopId());
            $shop->registerResources();

            /** @var OrderService $orderService */
            $orderService = $this->get('swag_backend_order.order.service');
            $order = $orderService->create($orderStruct);

            $modelManager->getConnection()->commit();

            if ($orderStruct->getSendMail() == 1) {
                $this->sendOrderConfirmationMail($order);
            }
        } catch (InvalidOrderException $e) {
            $modelManager->getConnection()->rollBack();

            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return;
        } catch (\Exception $e) {
            $modelManager->getConnection()->rollBack();

            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $this->view->assign([
            'success' => true,
            'orderId' => $order->getId(),
            'ordernumber' => $order->getNumber(),
        ]);
    }

    public function getArticlesAction()
    {
        $params = $this->Request()->getParams();
        $search = $params['filter'][0]['value'];
        $shopId = $this->getShopId();

        if (!isset($params['filter'][0]['value'])) {
            $search = '%' . $this->Request()->get('searchParam') . '%';
        }

        /** @var ContextServiceInterface $contextService */
        $contextService = $this->get('shopware_storefront.context_service');
        $shopContext = $contextService->createShopContext($shopId);

        /** @var StoreFrontCriteriaFactoryInterface $criteriaFactory */
        $criteriaFactory = $this->get('shopware_search.store_front_criteria_factory');
        $criteria = $criteriaFactory->createBaseCriteria(
            [$shopContext->getShop()->getCategory()->getId()],
            $shopContext
        );
        $criteria->addCondition(new SearchTermCondition($search));
        $criteria->addCondition(new VariantCondition('swag_backend_order_variant_condition'));
        $criteria->limit(6);

        /** @var ProductSearchInterface $productSearch */
        $productSearch = $this->get('shopware_search.product_search');
        $productSearchResult = $productSearch->search($criteria, $shopContext);

        $result = [];

        foreach ($productSearchResult->getProducts() as $product) {
            $productArray = [];

            $productArray['number'] = $product->getNumber();
            $productArray['name'] = $product->getName();
            $productArray['additionalText'] = $product->getAdditional();

            $result[] = $productArray;
        }

        $this->view->assign(
            [
                'success' => true,
                'data' => $result,
                'total' => $productSearchResult->getTotalCount(),
            ]
        );
    }

    public function getProductAction()
    {
        $number = $this->Request()->getParam('ordernumber');
        $shopId = $this->getShopId();
        $customerGroupKey = $this->getCustomerGroupKey();
        $quantity = $this->getQuantity();

        /** @var RequestHydrator $requestHydrator */
        $requestHydrator = $this->get('swag_backend_order.price_calculation.request_hydrator');
        $requestStruct = $requestHydrator->hydrateFromRequest($this->Request()->getParams());

        /** @var ContextServiceInterface $contextService */
        $contextService = $this->get('shopware_storefront.context_service');
        $shopContext = $contextService->createShopContext(
            $shopId,
            $requestStruct->getCurrencyId(),
            $customerGroupKey
        );

        /** @var ListProductServiceInterface $listProductService */
        $listProductService = $this->get('shopware_storefront.list_product_service');
        $listProduct = $listProductService->get($number, $shopContext);

        $result = [];
        $result['number'] = $listProduct->getNumber();
        $result['name'] = $listProduct->getName();
        $result['additionalText'] = $listProduct->getAdditional();
        $result['inStock'] = $listProduct->getStock();
        $result['taxId'] = $listProduct->getTax()->getId();
        $result['quantity'] = $quantity;

        $prices = $listProduct->getPrices();

        $result['price'] = round($prices[0]->getCalculatedPrice(), PriceResult::ROUND_PRECISION);
        if ($requestStruct->isDisplayNet() || $requestStruct->isTaxFree()) {
            $result['price'] = round($prices[0]->getRule()->getPrice(), PriceResult::ROUND_PRECISION);
        }

        if (count($prices) > 1) {
            $blockPrices = [];
            foreach ($prices as $priceStruct) {
                $netPrice = round($priceStruct->getRule()->getPrice(), PriceResult::ROUND_PRECISION);
                $grossPrice = round($priceStruct->getCalculatedPrice(), PriceResult::ROUND_PRECISION);

                $blockPrice = $grossPrice;
                if ($requestStruct->isDisplayNet() || $requestStruct->isTaxFree()) {
                    $blockPrice = $netPrice;
                }

                if ($quantity >= $priceStruct->getFrom()) {
                    $result['price'] = $blockPrice;
                }

                $blockPrices[$priceStruct->getFrom()] = [
                    'net' => $netPrice,
                    'gross' => $grossPrice,
                ];
            }
            $result['blockPrices'] = json_encode($blockPrices);
        }

        $this->view->assign([
            'data' => $result,
            'success' => true,
        ]);
    }

    /**
     * gets all available payments for the backend order
     */
    public function getPaymentAction()
    {
        /** @var PaymentTranslator $paymentTranslator */
        $paymentTranslator = $this->get('swag_backend_order.payment_translator');

        $builder = $this->get('models')->createQueryBuilder();
        $builder->select(['payment'])
            ->from(Payment::class, 'payment')
            ->orderBy('payment.active', 'DESC');

        $paymentMethods = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod = $paymentTranslator->translate($paymentMethod, $languageId);
        }
        unset($paymentMethod);

        $total = count($paymentMethods);

        $this->view->assign(
            [
                'data' => $paymentMethods,
                'total' => $total,
                'success' => true,
            ]
        );
    }

    /**
     * method which selects all shipping costs
     */
    public function getShippingCostsAction()
    {
        /** @var ShippingTranslator $dispatchTranslator */
        $dispatchTranslator = $this->get('swag_backend_order.shipping_translator');

        $builder = $this->get('models')->createQueryBuilder();

        $builder->select(['dispatch', 'shipping'])
            ->from(ShippingCost::class, 'shipping')
            ->innerJoin('shipping.dispatch', 'dispatch')
            ->groupBy('dispatch.id');
        $shippingCosts = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($shippingCosts as &$shippingCost) {
            $shippingCost['dispatch'] = $dispatchTranslator->translate($shippingCost['dispatch'], $languageId);
        }
        unset($shippingCost);

        $total = count($shippingCosts);

        $this->view->assign(
            [
                'data' => $shippingCosts,
                'total' => $total,
                'success' => true,
            ]
        );
    }

    public function getCurrenciesAction()
    {
        $repository = $this->get('models')->getRepository(Currency::class);

        $builder = $repository->createQueryBuilder('c');
        $builder->select(
            [
                'c.id as id',
                'c.name as name',
                'c.currency as currency',
                'c.symbol as symbol',
                'c.factor as factor',
                'c.default as default',
            ]
        );

        $query = $builder->getQuery();

        $total = $this->get('models')->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign(
            [
                'success' => true,
                'data' => $data,
                'total' => $total,
            ]
        );
    }

    /**
     * reads the plugin config and passes it to the ext js application
     */
    public function getPluginConfigAction()
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->get('shopware.plugin.config_reader');
        $pluginConfig = $configReader->getByPluginName('SwagBackendOrder');

        $desktopTypes = $pluginConfig['desktopTypes'];
        $desktopTypes = explode(',', $desktopTypes);
        $validationMail = $pluginConfig['validationMail'];
        $sendMail = $pluginConfig['sendMail'];
        $sendMailConfigGlobal = $this->get('config')->get('sendOrderMail');
        if($sendMailConfigGlobal == 0) {
            $sendMail = $sendMailConfigGlobal;
        }

        $config = [];
        $config['desktopTypes'] = [];
        $count = 0;

        foreach ($desktopTypes as $desktopType) {
            $config['desktopTypes'][$count]['id'] = $count;
            $config['desktopTypes'][$count]['name'] = $desktopType;
            ++$count;
        }

        $config['validationMail'] = $validationMail;
        $config['sendMail'] = $sendMail;

        $total = count($config);

        $this->view->assign(
            [
                'success' => true,
                'data' => $config,
                'total' => $total,
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
        if (null !== $paymentModel) {
            /** @var Payment $paymentMean */
            $paymentMean = $paymentModel->getPaymentMean();
            if ($paymentModel->getUseBillingData() && $paymentMean->getName() === 'sepa') {
                $accountHolder = $this->getAccountHolder($customerId);
            }
        }

        $payment = $this->get('models')->toArray($paymentModel);
        if ($accountHolder) {
            $payment['accountHolder'] = $accountHolder;
        }

        $this->view->assign([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * assigns the shop data to ExtJs to show the data in the view
     */
    public function getLanguageSubShopsAction()
    {
        $mainShopId = $this->Request()->getParam('mainShopId');

        $builder = $this->get('models')->createQueryBuilder();
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
            $shopModel = $this->get('models')->find(Shop::class, $shop['id']);
            $shop['name'] = $shopModel->getLocale()->getLanguage();
        }
        unset($shop);
        $total = count($result);

        $this->view->assign(
            [
                'data' => $result,
                'success' => true,
                'total' => $total,
            ]
        );
    }

    /**
     * checks if the article which was added or edited is no voucher or esd article
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
                'violations' => $violations->getMessages(),
            ]);

            return;
        }

        $this->view->assign('success', true);
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
            $totalPositionPrice->setNet($this->getTotalPrice($positionPrice->getRoundedNetPrice(), $position->getQuantity()));
            $totalPositionPrice->setGross($this->getTotalPrice($positionPrice->getRoundedGrossPrice(), $position->getQuantity()));
            $positionPrices[] = $totalPositionPrice;

            $position->setPrice($positionPrice->getRoundedGrossPrice());
            if ($requestStruct->isTaxFree() || $requestStruct->isDisplayNet()) {
                $position->setPrice($positionPrice->getRoundedNetPrice());
            }
            $position->setTotal($this->getTotalPrice($position->getPrice(), $position->getQuantity()));
        }
        unset($position);

        $dispatchPrice = $this->getShippingPrice($requestStruct);

        /** @var TotalPriceCalculator $totalPriceCalculator */
        $totalPriceCalculator = $this->get('swag_backend_order.price_calculation.total_price_calculator');
        $totalPriceResult = $totalPriceCalculator->calculate($positionPrices, $dispatchPrice);
        $result = $this->createBasketCalculationResult($totalPriceResult, $requestStruct);

        $this->view->assign([
            'data' => $result,
            'success' => true,
        ]);
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
                $orderModel,
                $orderModel->getLanguageSubShop()->getLocale()
            );

            $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
            $mail->addTo($context['additional']['user']['email']);
            $mail->send();

            //If configured send an email to the shop owner
            $mailNotToShopOwner = Shopware()->Config()->get('no_order_mail');
            if (!$mailNotToShopOwner) {
                $mail->clearRecipients();
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
     * @return string
     */
    private function getListRequestParam()
    {
        $data = $this->Request()->getParams();

        return $data['filter'][0]['value'];
    }

    /**
     * @param float $price
     * @param int   $quantity
     *
     * @return float
     */
    private function getTotalPrice($price, $quantity)
    {
        return $price * (float) $quantity;
    }

    /**
     * @param int $customerId
     *
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
     * @param TotalPricesResult $totalPriceResult
     * @param RequestStruct     $requestStruct
     *
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
            'positions' => $requestStruct->getPositionsArray(),
            'dispatchTaxRate' => $totalPriceResult->getShipping()->getTaxRate(),
        ];
    }

    /**
     * @param PositionStruct $position
     * @param RequestStruct  $requestStruct
     *
     * @return PriceResult
     */
    private function getPositionPrice($position, $requestStruct)
    {
        /** @var PriceContextFactory $priceContextFactory */
        $priceContextFactory = $this->get('swag_backend_order.price_calculation.price_context_factory');
        /** @var ProductPriceCalculator $productCalculator */
        $productCalculator = $this->get('swag_backend_order.price_calculation.product_calculator');

        $previousPriceContext = $priceContextFactory->create(
            $position->getPrice(),
            $position->getTaxRate(),
            $requestStruct->isPreviousDisplayNet(),
            $requestStruct->isPreviousTaxFree(),
            $requestStruct->getPreviousCurrencyId()
        );
        $basePrice = $productCalculator->calculateBasePrice($previousPriceContext);

        $currentPriceContext = $priceContextFactory->create(
            $basePrice,
            $position->getTaxRate(),
            true,
            $requestStruct->isTaxFree(),
            $requestStruct->getCurrencyId()
        );

        return $productCalculator->calculate($currentPriceContext);
    }

    /**
     * @param int     $dispatchId
     * @param float[] $basketTaxRates
     *
     * @throws \RuntimeException
     *
     * @return float
     */
    private function getDispatchTaxRate($dispatchId, array $basketTaxRates = [])
    {
        if (null === $dispatchId) {
            return 0.00;
        }

        /** @var Dispatch $dispatch */
        $dispatch = $this->getModelManager()->find(Dispatch::class, $dispatchId);

        if (null === $dispatch) {
            throw new \RuntimeException('Can not find given dispatch with id ' . $dispatchId);
        }

        $taxId = $dispatch->getTaxCalculation();
        $tax = $this->getModelManager()->find(Tax::class, $taxId);

        if (null !== $tax) {
            return $tax->getTax();
        }

        if (empty($basketTaxRates)) {
            return 0.00;
        }

        return $this->getHighestDispatchTaxRate($basketTaxRates);
    }

    /**
     * @param float[] $basketTaxRates
     *
     * @return float
     */
    private function getHighestDispatchTaxRate(array $basketTaxRates)
    {
        return max($basketTaxRates);
    }

    /**
     * @param RequestStruct $requestStruct
     *
     * @return PriceResult
     */
    private function getShippingPrice($requestStruct)
    {
        $dispatchTaxRate = $this->getDispatchTaxRate($requestStruct->getDispatchId(), $requestStruct->getBasketTaxRates());
        /** @var PriceContextFactory $priceContextFactory */
        $priceContextFactory = $this->get('swag_backend_order.price_calculation.price_context_factory');
        /** @var ShippingPriceCalculator $shippingCalculator */
        $shippingCalculator = $this->get('swag_backend_order.price_calculation.shipping_calculator');

        // Get base/gross shipping costs (even if tax free)
        $previousPriceContext = $priceContextFactory->create(
            $requestStruct->getShippingCosts(),
            $dispatchTaxRate,
            $requestStruct->isPreviousDisplayNet(),
            $requestStruct->isPreviousTaxFree(),
            $requestStruct->getPreviousCurrencyId()
        );
        $baseShippingPrice = $shippingCalculator->calculateBasePrice($previousPriceContext);

        // Calculate actual gross & net shipping costs for order
        $currentPriceContext = $priceContextFactory->create(
            $baseShippingPrice,
            $dispatchTaxRate,
            $requestStruct->isDisplayNet(),
            $requestStruct->isTaxFree(),
            $requestStruct->getCurrencyId()
        );

        return $shippingCalculator->calculate($currentPriceContext);
    }

    /**
     * @return int
     */
    private function getShopId()
    {
        $shopId = (int) $this->Request()->getParam('shopId');

        if ($shopId === 0) {
            /** @var Repository $shopRepo */
            $shopRepo = $this->get('models')->getRepository(Shop::class);
            $shopId = $shopRepo->getActiveDefault()->getId();
        }

        return $shopId;
    }

    /**
     * @return string
     */
    private function getCustomerGroupKey()
    {
        $customerId = (int) $this->Request()->getParam('customerId');

        // default customer group key of shopware
        $customerGroupKey = 'EK';

        if ($customerId !== 0) {
            /** @var CustomerRepository $customerRepo */
            $customerRepo = $this->get('swag_backend_order.customer_repository');
            $customer = $customerRepo->get($customerId);
            $customerGroupKey = $customer['groupKey'];
        }

        return $customerGroupKey;
    }

    /**
     * @return int
     */
    private function getQuantity()
    {
        $quantity = (int) $this->Request()->getParam('quantity');

        if ($quantity === 0) {
            return 1;
        }

        return $quantity;
    }
}
