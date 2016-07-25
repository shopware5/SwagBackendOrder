<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\AddressRepository;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\PaymentData;
use Shopware\Models\Dispatch\ShippingCost;
use Shopware\Models\Order\Order;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use SwagBackendOrder\Components\CustomerRepository;
use SwagBackendOrder\Components\Order\Hydrator\OrderHydrator;
use SwagBackendOrder\Components\Order\OrderException;
use SwagBackendOrder\Components\Order\OrderService;

class Shopware_Controllers_Backend_SwagBackendOrder extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * configures how much digits the prices have
     *
     * @const int PRICE_PRECISION
     */
    const PRICE_PRECISION = 2;

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
        $modelManager->getConnection()->beginTransaction();

        try {
            /** @var OrderHydrator $orderHydrator */
            $orderHydrator = $this->get('swag_backend_order.order.order_hydrator');
            $orderStruct = $orderHydrator->hydrateFromRequest($this->request);

            /** @var OrderService $orderService */
            $orderService = $this->get('swag_backend_order.order.service');
            $order = $orderService->create($orderStruct);

            $modelManager->getConnection()->commit();

            $this->sendOrderConfirmationMail($order);
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

    /**
     * method which searches all articles by their ordernumber, name and additional text
     */
    public function getArticlesAction()
    {
        $params = $this->Request()->getParams();
        $search = $params['filter'][0]['value'];


        if (!isset($params['filter'][0]['value'])) {
            $search = '%' . $this->Request()->get('searchParam') . '%';
        }

        $builder = Shopware()->Models()->createQueryBuilder();

        /**
         * query to search for article variants or the article ordernumber
         * the query concats the article name and the additional text field for the search
         */
        $builder->select(
            'articles.id AS articleId,
            details.number,
            articles.name,
            details.id,
            details.inStock,
            articles.taxId,
            prices.price,
            details.additionalText,
            tax.tax'
        );
        $builder->from(Article::class, 'articles')
            ->leftJoin('articles.details', 'details')
            ->leftJoin('details.prices', 'prices')
            ->leftJoin('articles.tax', 'tax')
            ->where(
                $builder->expr()->like(
                    $builder->expr()->concat(
                        'articles.name',
                        $builder->expr()->concat(
                            $builder->expr()->literal(' '),
                            'details.additionalText'
                        )
                    ),
                    $builder->expr()->literal($search)
                )
            )
            ->orWhere('details.number LIKE :number')
            ->andWhere('articles.active = 1')
            ->andWhere('articles.active = 1')
            ->setParameter('number', $search)
            ->orderBy('details.number')
            ->groupBy('details.number')
            ->setMaxResults(8);
        $result = $builder->getQuery()->getArrayResult();
        $total = count($result);

        foreach ($result as &$article) {
            $article['price'] = $this->calculateGrossPrice($article['price'], $article['tax']);
        }

        $this->view->assign(
            [
                'success' => true,
                'data' => $result,
                'total' => $total
            ]
        );
    }

    /**
     * gets all available payments for the backend order
     */
    public function getPaymentAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['payment'])
            ->from(Payment::class, 'payment');

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
     * translates the payment methods
     *
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
     * translates the dispatch fields
     *
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

    /**
     * returns the currencies which are available
     */
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

        $paymentDataRepository = $this->get('models')->getRepository(PaymentData::class);
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
        $articleNumber = $data['articleNumber'];

        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select('articles')
            ->from(Article::class, 'articles')
            ->leftJoin('articles.details', 'details')
            ->where('details.number = :articleNumber')
            ->setParameter('articleNumber', $articleNumber);

        /** @var Article[] $articleModels */
        $articleModels = $builder->getQuery()->getResult();

        if (count($articleModels) < 1) {
            if (!empty($articleNumber)) {
                $this->view->assign(
                    [
                        'data' => ['articleNumber' => $articleNumber, 'error' => 'articleNumber'],
                        'success' => false
                    ]
                );

                return false;
            }

            $this->view->assign(
                [
                    'data' => ['articleName' => $data['articleName'], 'error' => 'articleName'],
                    'success' => false
                ]
            );

            return false;
        }

        if ($articleModels[0]->getMainDetail()->getEsd()) {
            $this->view->assign(
                [
                    'data' => ['articleNumber' => $articleNumber, 'error' => 'esd'],
                    'success' => false
                ]
            );

            return false;
        }

        return true;
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

        //we need to fake a shop instance if we want to use the Articles Module
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->getActiveById($orderModel->getLanguageSubShop()->getId());
        $shop->registerResources(Shopware()->Bootstrap());

        foreach ($details as &$detail) {
            /** @var Shopware\Models\Article\Repository $articleDetailRepository */
            $articleDetailRepository = Shopware()->Models()->getRepository(Detail::class);
            /** @var Detail[] $articleDetailModel */
            $articleDetailModel = $articleDetailRepository->findBy(['number' => $detail['articleordernumber']]);
            /** @var Detail $articleDetailModel */
            $articleDetailModel = $articleDetailModel[0];

            $detail['articlename'] = $detail['name'];
            unset($detail['name']);

            $detail['userID'] = $orderModel->getCustomer()->getId();

            $detail['ordernumber'] = $detail['articleordernumber'];
            unset($detail['articleordernumber']);

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
            $detail['netprice'] = $this->calculateNetPrice($detail['price'], $detail['tax_rate']);
            $detail['amount'] = $detail['price'] * $detail['quantity'];
            $detail['amountnet'] = $detail['netprice'] * $detail['quantity'];
            $detail['priceNumeric'] = $detail['price'];

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

            $detail['additional_details'] = Shopware()->Modules()->Articles()->sGetProductByOrdernumber($detail['ordernumber']);
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
            [ $orderModel->getId() ]
        );
        $shippingAddressAttributes = Shopware()->Db()->fetchRow(
            'SELECT * FROM s_order_shippingaddress_attributes WHERE shippingID = ?',
            [ $shippingAddress['id'] ]
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
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    private function calculateNetPrice($price, $taxRate)
    {
        return $price / ((100 + $taxRate) / 100);
    }

    /**
     * @param float $price
     * @param float $taxRate
     * @return float
     */
    private function calculateGrossPrice($price, $taxRate)
    {
        return $price * ((100 + $taxRate) / 100);
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
    public function calculateTaxAction()
    {
        $data = $this->Request()->getParams();

        //init variables
        $positions = json_decode($data['positions']);
        $shippingCosts = $data['shippingCosts'];
        $net = $data['net'];
        $total = 0;
        $taxSums = [];
        $totalWithoutTax = 0;
        $articleNumber = 0;
        $shippingCostsNet = 0;

        foreach ($positions as $position) {
            $total += $position->price * $position->quantity;
            $priceWithoutTax = $this->calculateNetPrice($position->price, $position->taxRate) * $position->quantity;
            $totalWithoutTax += $priceWithoutTax;

            if (!isset($taxSums[$position->taxRate])) {
                $taxSums[$position->taxRate] = [];
                $taxSums[$position->taxRate]['sum'] = 0;
                $taxSums[$position->taxRate]['count'] = 0;
            }
            $taxSums[$position->taxRate]['sum'] += $this->calculateNetPrice($position->price, $position->taxRate);
            $taxSums[$position->taxRate]['count'] += $position->quantity;

            $articleNumber += $position->quantity;
        }

        if ($shippingCosts != 0 && !is_null($shippingCosts) && $shippingCosts != '') {
            foreach ($taxSums as $taxRate => $taxRow) {
                $taxRelation = $articleNumber / $taxRow['count'];
                $shippingCostsRelation = $shippingCosts / $taxRelation;

                $shippingCostsNet += $this->calculateNetPrice($shippingCostsRelation, $taxRate);
            }
            if (count($taxSums) == 0) {
                $shippingCostsNet = $shippingCosts;
            }
        }

        $sum = $total;
        if ($net === 'true') {
            $total = $shippingCostsNet + $total;
        } else {
            $total = $shippingCosts + $total;
        }
        $totalWithoutTax = $shippingCostsNet + $totalWithoutTax;

        $taxSum = $total - $totalWithoutTax;
        if ($totalWithoutTax == 0) {
            $taxSum = 0;
        }

        if ($net === 'true') {
            $shippingCosts = $shippingCostsNet;
            $totalWithoutTax = $total;
            $taxSum = 0;
        }

        $result = [
            'totalWithoutTax' => $this->roundPrice($totalWithoutTax),
            'sum' => $this->roundPrice($sum),
            'total' => $this->roundPrice($total),
            'shippingCosts' => $this->roundPrice($shippingCosts),
            'shippingCostsNet' => $this->roundPrice($shippingCostsNet),
            'taxSum' => $this->roundPrice($taxSum)
        ];

        $this->view->assign(
            [
                'data' => $result,
                'success' => true
            ]
        );
    }

    /**
     * calculates the new currency and returns the actual prices
     */
    public function calculateCurrencyAction()
    {
        $data = $this->Request()->getParams();
        $positions = json_decode($data['positions']);

        if ($data['oldCurrencyId'] < 1) {
            return;
        }

        /** @var Shopware\Models\Shop\Currency $oldCurrencyModel */
        $oldCurrencyModel = Shopware()->Models()->find('Shopware\Models\Shop\Currency', $data['oldCurrencyId']);

        /** @var Shopware\Models\Shop\Currency $newCurrencyModel */
        $newCurrencyModel = Shopware()->Models()->find('Shopware\Models\Shop\Currency', $data['newCurrencyId']);

        foreach ($positions as &$position) {
            $position->price = $position->price / $oldCurrencyModel->getFactor();
            $position->price = $position->price * $newCurrencyModel->getFactor();

            $position->total = $position->price * $position->quantity;

            $position->price = $this->roundPrice($position->price);
            $position->total = $this->roundPrice($position->total);
        }

        $data['shippingCosts'] = $data['shippingCosts'] / $oldCurrencyModel->getFactor();
        $data['shippingCosts'] = $data['shippingCosts'] * $newCurrencyModel->getFactor();
        $data['shippingCosts'] = $this->roundPrice($data['shippingCosts']);

        $data['shippingCostsNet'] = $data['shippingCostsNet'] / $oldCurrencyModel->getFactor();
        $data['shippingCostsNet'] = $data['shippingCostsNet'] * $newCurrencyModel->getFactor();
        $data['shippingCostsNet'] = $this->roundPrice($data['shippingCostsNet']);

        $data['positions'] = $positions;

        $this->view->assign(
            [
                'data' => $data,
                'success' => true
            ]
        );
    }

    /**
     * calculates the net price for each position
     */
    public function changedNetBoxAction()
    {
        $data = $this->Request()->getParams();
        $positions = json_decode($data['positions']);

        if ($data['net'] == 'true') {
            foreach ($positions as &$position) {
                $position->price = $this->calculateNetPrice($position->price, $position->taxRate);
                $position->total = $position->price * $position->quantity;

                $position->price = $this->roundPrice($position->price);
                $position->total = $this->roundPrice($position->total);
            }
        }

        if ($data['net'] == 'false') {
            foreach ($positions as $position) {
                $position->price = $this->calculateGrossPrice($position->price, $position->taxRate);
                $position->total = $position->price * $position->quantity;

                $position->price = $this->roundPrice($position->price);
                $position->total = $this->roundPrice($position->total);
            }
        }

        $this->view->assign(
            [
                'data' => $positions,
                'success' => true
            ]
        );
    }

    /**
     * @param float $price
     * @param int $digits
     * @return float
     */
    private function roundPrice($price, $digits = self::PRICE_PRECISION)
    {
        return round($price, $digits);
    }

    /**
     * Gets the customer group price
     */
    public function getCustomerGroupPriceByOrdernumberAction()
    {
        $data = $this->Request()->getParams();

        if (empty($data['customerId']) || empty($data['ordernumber'])) {
            $this->view->assign(['success' => false]);

            return;
        }

        /** @var Customer $customerModel */
        $customerModel = $this->getModelManager()->find('Shopware\Models\Customer\Customer', $data['customerId']);

        /** @var \Shopware\Models\Article\Repository $productRepository */
        $productRepository = $this->getModelManager()->getRepository(Detail::class);

        /** @var Shopware\Models\Article\Detail $productModel */
        $productModel = $productRepository->findOneBy(['number' => $data['ordernumber']]);
        $prices = $productModel->getPrices();

        $priceForCustomerGroup = 0;

        /** @var \Shopware\Models\Article\Price $price */
        foreach ($prices as $price) {
            if ($price->getCustomerGroup()->getKey() == $customerModel->getGroup()->getKey()) {
                $priceForCustomerGroup = $price->getPrice();
                break;
            }
            $priceForCustomerGroup = $price->getPrice();
        }

        $priceForCustomerGroup = $this->calculateGrossPrice(
            $priceForCustomerGroup,
            $productModel->getArticle()->getTax()->getTax()
        );

        $this->view->assign(
            [
                'data' => ['price' => $priceForCustomerGroup],
                'success' => true
            ]
        );
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
}
