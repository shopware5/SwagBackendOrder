<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

use Shopware\Components\Cart\Struct\Price;
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
use SwagBackendOrder\Components\Order\Struct\PositionStruct as OrderPositionStruct;
use SwagBackendOrder\Components\Order\Validator\InvalidOrderException;
use SwagBackendOrder\Components\Order\Validator\Validators\ProductContext;
use SwagBackendOrder\Components\PriceCalculation\DiscountType;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\Result\TotalPricesResult;
use SwagBackendOrder\Components\PriceCalculation\Struct\PositionStruct;
use SwagBackendOrder\Components\PriceCalculation\Struct\RequestStruct;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

/**
 * @phpstan-import-type PositionArray from RequestStruct
 *
 * @phpstan-type TaxArray list<array{taxRate: float, tax: float}>
 */
class Shopware_Controllers_Backend_SwagBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Return a list of customer on search or return a single customer on select.
     */
    public function getCustomerAction(): void
    {
        $repository = $this->get('swag_backend_order.customer_repository');

        if ($filter = $this->getListRequestParam()) {
            $result = $repository->getList($filter);

            $this->view->assign([
                'data' => $result,
                'total' => \count($result),
                'success' => true,
            ]);

            return;
        }

        $customerId = (int) $this->Request()->get('searchParam');
        $result = $repository->get($customerId);

        $this->view->assign([
            'data' => $result,
            'total' => \count($result),
            'success' => true,
        ]);
    }

    public function createOrderAction(): void
    {
        $modelManager = $this->get('models');

        $orderHydrator = $this->get('swag_backend_order.order.order_hydrator');

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
            // we need to fake a shop instance if we want to use the Articles Module
            $shopRepository = $this->get('models')->getRepository(Shop::class);
            $shop = $shopRepository->getActiveById($orderStruct->getLanguageShopId());

            if ($shop === null) {
                throw new RuntimeException('Shop not found');
            }

            $this->get('shopware.components.shop_registration_service')->registerResources($shop);

            $orderService = $this->get('swag_backend_order.order.service');
            $order = $orderService->create($orderStruct);

            $modelManager->getConnection()->commit();

            if ($orderStruct->getSendMail()) {
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

        $this->get('events')->notify('Shopware_Modules_Order_SaveOrder_OrderCreated', [
            'subject' => $this,
            'orderId' => $order->getId(),
            'orderNumber' => $order->getNumber(),
        ]);

        $orderService = $this->container->get('swag_backend_order.b2b_order_service');
        $orderService->createB2BOrder($order);

        $this->view->assign([
            'success' => true,
            'orderId' => $order->getId(),
            'ordernumber' => $order->getNumber(),
        ]);
    }

    public function getProductsAction(): void
    {
        $limit = (int) $this->request->getParam('limit', 10);
        $offset = (int) $this->request->getParam('start', 0);
        $search = (string) $this->request->getParam('query');
        $shopId = $this->getShopId();

        $productSearch = $this->container->get('swag_backend_order.product_search');
        $result = $productSearch->findProducts($search, $shopId, $limit, $offset);

        $this->view->assign(
            [
                'success' => true,
                'data' => $result,
                'total' => $productSearch->getLastResultTotalCount(),
            ]
        );
    }

    public function getProductAction(): void
    {
        $params = $this->request->getParams();
        $number = $this->request->getParam('ordernumber');

        $productSearch = $this->container->get('swag_backend_order.product_search');
        $product = $productSearch->getProduct($number, $params, $this->getShopId(), $this->getCustomerGroupKey());

        $this->view->assign([
            'data' => $product,
            'success' => true,
        ]);
    }

    public function getDiscountAction(): void
    {
        $type = (int) $this->Request()->getParam('type');
        $value = (float) $this->Request()->getParam('value');
        $productName = $this->Request()->getParam('name');
        $totalAmount = $this->Request()->getParam('currentTotal');
        $taxRate = $this->Request()->getParam('tax');

        if ($type === DiscountType::DISCOUNT_ABSOLUTE && $totalAmount < $value) {
            $this->view->assign(['success' => false]);

            return;
        }

        $result = [
            'articleName' => $productName,
            'articleNumber' => OrderPositionStruct::DISCOUNT_ORDER_NUMBER_PREFIX . $type,
            'articleId' => 0,
            'price' => $value * -1,
            'mode' => 4,
            'quantity' => 1,
            'inStock' => 1,
            'isDiscount' => true,
            'discountType' => $type,
            'total' => $value * -1,
            'taxRate' => $taxRate,
        ];

        $this->view->assign([
            'data' => $result,
            'success' => true,
        ]);
    }

    /**
     * gets all available payments for the backend order
     */
    public function getPaymentAction(): void
    {
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

        $total = \count($paymentMethods);

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
    public function getShippingCostsAction(): void
    {
        $dispatchTranslator = $this->get('swag_backend_order.shipping_translator');

        $builder = $this->get('models')->createQueryBuilder();

        $builder->select(['dispatch', 'shipping'])
            ->from(ShippingCost::class, 'shipping')
            ->innerJoin('shipping.dispatch', 'dispatch')
            ->groupBy('dispatch.id')
            ->orderBy('dispatch.position', 'ASC');
        $shippingCosts = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($shippingCosts as &$shippingCost) {
            $shippingCost['dispatch'] = $dispatchTranslator->translate($shippingCost['dispatch'], $languageId);
        }
        unset($shippingCost);

        $total = \count($shippingCosts);

        $this->view->assign(
            [
                'data' => $shippingCosts,
                'total' => $total,
                'success' => true,
            ]
        );
    }

    public function getCurrenciesAction(): void
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
    public function getPluginConfigAction(): void
    {
        $configReader = $this->get('shopware.plugin.config_reader');
        $pluginConfig = $configReader->getByPluginName('SwagBackendOrder');

        $desktopTypes = $pluginConfig['desktopTypes'];
        $desktopTypes = \explode(',', $desktopTypes);
        $validationMail = $pluginConfig['validationMail'];

        $config = [];
        $config['desktopTypes'] = [];
        $count = 0;

        foreach ($desktopTypes as $desktopType) {
            $config['desktopTypes'][$count]['id'] = $count;
            $config['desktopTypes'][$count]['name'] = $desktopType;
            ++$count;
        }

        $config['validationMail'] = $validationMail;
        $config['sendMail'] = (bool) $pluginConfig['sendMail'];

        $total = \count($config);

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
    public function getCustomerPaymentDataAction(): void
    {
        $modelManager = $this->get('models');
        $request = $this->Request()->getParams();
        $customerId = $request['customerId'];
        $paymentId = $request['paymentId'];

        $paymentDataRepository = $modelManager->getRepository(PaymentData::class);
        $paymentModel = $paymentDataRepository->findBy(['paymentMeanId' => $paymentId, 'customer' => $customerId]);
        $paymentModel = $paymentModel[0];

        $accountHolder = false;
        if ($paymentModel !== null) {
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
    public function getLanguageSubShopsAction(): void
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

        // Gets the correct language name for every shop
        foreach ($result as &$shop) {
            $shopModel = $this->get('models')->find(Shop::class, $shop['id']);
            $shop['name'] = $shopModel->getLocale()->getLanguage();
        }
        unset($shop);
        $total = \count($result);

        $this->view->assign(
            [
                'data' => $result,
                'success' => true,
                'total' => $total,
            ]
        );
    }

    /**
     * checks if the product which was added or edited is no voucher or esd product
     */
    public function validateEditAction(): void
    {
        $data = $this->Request()->getParams();
        $productNumber = (string) $data['articleNumber'];
        $quantity = (int) $data['quantity'];

        $productContext = new ProductContext($productNumber, $quantity);

        $violations = $this->get('swag_backend_order.order.product_validator')->validate($productContext);

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
    public function calculateBasketAction(): void
    {
        $requestHydrator = $this->get('swag_backend_order.price_calculation.request_hydrator');
        $requestStruct = $requestHydrator->hydrateFromRequest($this->Request()->getParams());

        $config = $this->container->get('config');
        $proportionalTaxCalculation = ((bool) $config->get('proportionalTaxCalculation')) && !$requestStruct->isTaxFree();

        // Basket position price calculation
        $positionPrices = [];
        foreach ($requestStruct->getPositions() as $position) {
            $positionPrice = $this->getPositionPrice($position, $requestStruct);
            $totalPositionPrice = new PriceResult();
            $totalPositionPrice->setNet($this->getTotalPrice($positionPrice->getNet(), $position->getQuantity()));
            $totalPositionPrice->setGross($this->getTotalPrice($positionPrice->getGross(), $position->getQuantity()));
            $totalPositionPrice->setTaxRate($position->getTaxRate());

            if ($requestStruct->isDisplayNet()) {
                $calculatedGross = $positionPrice->getNet() * (1 + ($position->getTaxRate() / 100));
                $totalPositionPrice->setGross($this->getTotalPrice($calculatedGross, $position->getQuantity()));
            }

            // Don't set the total amount of the product if it's a discount.
            if (!$position->getIsDiscount()) {
                $positionPrices[] = $totalPositionPrice;

                $position->setPrice($positionPrice->getGross());

                // Use net prices if it's configured like that
                if ($requestStruct->isTaxFree() || $requestStruct->isDisplayNet()) {
                    $position->setPrice($positionPrice->getNet());
                }

                $position->setTotal($this->getTotalPrice($position->getPrice(), $position->getQuantity()));
            }
        }

        $dispatchPrice = $this->getShippingPrice($requestStruct);

        $totalPriceCalculator = $this->get('swag_backend_order.price_calculation.total_price_calculator');
        $totalPriceResult = $totalPriceCalculator->calculate($positionPrices, $dispatchPrice, $proportionalTaxCalculation);
        $result = $this->createBasketCalculationResult($totalPriceResult, $requestStruct, $proportionalTaxCalculation);
        $result['isTaxFree'] = $requestStruct->isTaxFree();
        $result['isDisplayNet'] = $requestStruct->isDisplayNet();

        $discountCalculator = $this->get('swag_backend_order.price_calculation.discount_calculator');
        $result = $discountCalculator->calculateDiscount($result);

        $this->view->assign([
            'data' => $result,
            'success' => true,
        ]);
    }

    private function sendOrderConfirmationMail(Order $orderModel): void
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

            $mail = $this->container->get('templatemail')->createMail('sORDER', $context);
            $mail->addTo($context['additional']['user']['email']);
            $mail->send();

            // If configured email to the shop owner
            $mailNotToShopOwner = $this->container->get('config')->get('no_order_mail');
            if (!$mailNotToShopOwner) {
                $mail->clearRecipients();
                $mail->addTo($this->container->get('config')->get('mail'));
                $mail->send();
            }
        } catch (\Exception $e) {
            $this->view->assign('mail', $e->getMessage());
        }
    }

    private function getBackendLanguage(): int
    {
        $auth = $this->container->get('plugins')->Backend()->Auth()->checkAuth();
        if (!$auth instanceof Shopware_Components_Auth) {
            throw new RuntimeException('User not logged in');
        }
        $identity = $auth->getIdentity();

        return (int) $identity->locale->getId();
    }

    private function getListRequestParam(): ?string
    {
        $data = $this->Request()->getParams();

        return $data['filter'][0]['value'];
    }

    private function getTotalPrice(float $price, int $quantity): float
    {
        return $price * $quantity;
    }

    private function getAccountHolder(int $customerId): string
    {
        $customer = $this->get('models')->find(Customer::class, $customerId);

        return $customer->getBilling()->getFirstName() . ' ' . $customer->getBilling()->getLastName();
    }

    /**
     * @return array{totalWithoutTax: float, sum: float, total: float, shippingCosts: float, shippingCostsNet: float, shippingCostsTaxRate: float, taxSum: float, positions: PositionArray, dispatchTaxRate: float, proportionalTaxCalculation: bool, taxes: TaxArray}
     */
    private function createBasketCalculationResult(
        TotalPricesResult $totalPriceResult,
        RequestStruct $requestStruct,
        bool $proportionalTaxCalculation
    ): array {
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

        // Total prices calculation
        $totalNetPrice = $totalPriceResult->getTotal()->getRoundedNetPrice();
        $shippingCostsNet = $totalPriceResult->getShipping()->getRoundedNetPrice();

        if ($proportionalTaxCalculation) {
            $proportionalTaxCalculator = $this->container->get('shopware.cart.proportional_tax_calculator');
            $prices = $this->getPricesFromPositions($requestStruct->getPositions());
            $tax = $proportionalTaxCalculator->calculate($shippingCosts, $prices, true);
            foreach ($tax as $price) {
                $totalPriceResult->addTax($price->getTaxRate(), $price->getTax());
            }
        }

        return [
            'totalWithoutTax' => $totalNetPrice,
            'sum' => $productSum,
            'total' => $total,
            'shippingCosts' => $shippingCosts,
            'shippingCostsNet' => $shippingCostsNet,
            'shippingCostsTaxRate' => $totalPriceResult->getShipping()->getTaxRate(),
            'taxSum' => $taxSum,
            'positions' => $requestStruct->getPositionsArray(),
            'dispatchTaxRate' => $totalPriceResult->getShipping()->getTaxRate(),
            'proportionalTaxCalculation' => $proportionalTaxCalculation && !$requestStruct->isTaxFree(),
            'taxes' => $this->convertTaxes($totalPriceResult->getTaxes()),
        ];
    }

    /**
     * @param array<string|int, float> $taxes
     *
     * @return TaxArray
     */
    private function convertTaxes(array $taxes): array
    {
        $result = [];
        foreach ($taxes as $taxRate => $tax) {
            $result[] = [
                'taxRate' => (float) $taxRate,
                'tax' => \round($tax, 2),
            ];
        }

        return $result;
    }

    private function getPositionPrice(PositionStruct $position, RequestStruct $requestStruct): PriceResult
    {
        $priceContextFactory = $this->get('swag_backend_order.price_calculation.price_context_factory');
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
     * @param float[] $basketTaxRates
     *
     * @throws \RuntimeException
     */
    private function getDispatchTaxRate(?int $dispatchId, array $basketTaxRates = []): float
    {
        if ($dispatchId === null) {
            return 0.00;
        }

        $dispatch = $this->getModelManager()->find(Dispatch::class, $dispatchId);

        if ($dispatch === null) {
            throw new \RuntimeException('Can not find given dispatch with id ' . $dispatchId);
        }

        $taxId = $dispatch->getTaxCalculation();
        $tax = $this->getModelManager()->find(Tax::class, $taxId);

        if ($tax instanceof Tax) {
            return (float) $tax->getTax();
        }

        if (empty($basketTaxRates)) {
            return 0.00;
        }

        return $this->getHighestDispatchTaxRate($basketTaxRates);
    }

    /**
     * @param float[] $basketTaxRates
     */
    private function getHighestDispatchTaxRate(array $basketTaxRates): float
    {
        return (float) \max($basketTaxRates);
    }

    private function getShippingPrice(RequestStruct $requestStruct): PriceResult
    {
        $dispatchTaxRate = $this->getDispatchTaxRate($requestStruct->getDispatchId(), $requestStruct->getBasketTaxRates());
        $priceContextFactory = $this->get('swag_backend_order.price_calculation.price_context_factory');
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

    private function getShopId(): int
    {
        $shopId = (int) $this->Request()->getParam('shopId');

        if ($shopId === 0) {
            $shopRepo = $this->get('models')->getRepository(Shop::class);
            $shopId = $shopRepo->getActiveDefault()->getId();
        }

        return $shopId;
    }

    private function getCustomerGroupKey(): string
    {
        $customerId = (int) $this->Request()->getParam('customerId');

        // default customer group key of shopware
        $customerGroupKey = 'EK';

        if ($customerId !== 0) {
            $customerRepo = $this->get('swag_backend_order.customer_repository');
            $customer = $customerRepo->get($customerId);
            $customerGroupKey = $customer['groupKey'];
        }

        return $customerGroupKey;
    }

    /**
     * @param array<PositionStruct> $positions
     *
     * @return array<Price>
     */
    private function getPricesFromPositions(array $positions): array
    {
        $prices = [];
        foreach ($positions as $position) {
            $price = new Price(
                $position->getPrice(),
                $position->getNetPrice(),
                $position->getTaxRate(),
                $position->getNetPrice() * ($position->getTaxRate() / 100)
            );
            $prices[] = $price;
        }

        return $prices;
    }
}
