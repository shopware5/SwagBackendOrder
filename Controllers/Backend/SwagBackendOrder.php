<?php

class Shopware_Controllers_Backend_SwagBackendOrder
    extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * configures how much digits the prices have
     *
     * @const int PRICE_PRECISION
     */
    const PRICE_PRECISION = 2;

    /**
     * holds the order id
     *
     * @var int
     */
    private $orderId;

    /**
     * is true if billing and shipping are equal
     *
     * @var int
     */
    private $equalBillingAddress = false;

    /**
     * Disable template engine for all actions
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), array('index', 'load'))) {
            $this->Front()->Plugins()->Json()->setRenderer(true);
        }
    }

    /**
     * gets customers by the email, customernumber, company or fullname
     */
    public function getCustomerAction()
    {
        $data = $this->Request()->getParams();

        /** @var Shopware_Components_CustomerInformationHandler $customerInformationHandler */
        $customerInformationHandler = Shopware()->CustomerInformationHandler();

        //Checks if the user used the live search or selected a customer from the drop down list
        if (isset($data['filter'][0]['value'])) {
            $result = $customerInformationHandler->getCustomerList($data['filter'][0]['value']);
        } else {
            $search = $this->Request()->get('searchParam');
            $result = $customerInformationHandler->getCustomer($search);
        }

        $total = count($result);

        $this->view->assign(array(
            'data' => $result,
            'total' => $total,
            'success' => true
        ));
    }

    /**
     * method to create an order
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function createOrderAction()
    {
        $data = $this->Request()->getParams();
        $data = $data['data'];

        $ordernumber = $this->getOrderNumber();

        $createBackendOrder = Shopware()->CreateBackendOrder();

        /** @var Shopware\Models\Order\Order $orderModel */
        $orderModel = $createBackendOrder->createOrder($data, $ordernumber);

        //sends and prepares the order confirmation mail
        $this->sendOrderConfirmationMail($orderModel);

        $this->view->assign(array(
            'success' => true,
            'orderId' => $orderModel->getId()
        ));
    }

    /**
     * method which searches all articles by their ordernumber, name and additional text
     */
    public function getArticlesAction()
    {
        $params = $this->Request()->getParams();
        $search = $params['filter'][0]['value'];


        if ( !isset($params['filter'][0]['value'])) {
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
                tax.tax')
                ->from('Shopware\Models\Article\Article', 'articles')
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
            array(
                'success' => true,
                'data' => $result,
                'total' => $total
            )
        );
    }

    /**
     * gets all available payments for the backend order
     */
    public function getPaymentAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('payment'))
                ->from('Shopware\Models\Payment\Payment', 'payment');

        $paymentMethods = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();

        foreach ($paymentMethods as &$paymentMethod) {
            $paymentMethod = $this->translatePayment($paymentMethod, $languageId);
        }

        $total = count($paymentMethods);

        $this->view->assign(array(
                'data' => $paymentMethods,
                'total' => $total,
                'success' => true
        ));
    }

    /**
     * translates the payment methods
     *
     * @param $paymentMethod
     * @param $languageId
     * @return mixed
     */
    private function translatePayment($paymentMethod, $languageId)
    {
        $translation = new Shopware_Components_Translation();
        $paymentTranslations = $translation->read($languageId, 'config_payment');

        $paymentId = $paymentMethod['id'];

        if ( !is_null($paymentTranslations[$paymentId]['description'])) {
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

        $builder->select(array('dispatch', 'shipping'))
                ->from('Shopware\Models\Dispatch\ShippingCost', 'shipping')
                ->innerJoin('shipping.dispatch', 'dispatch')
                ->groupBy('dispatch.id');
        $shippingCosts = $builder->getQuery()->getArrayResult();

        $languageId = $this->getBackendLanguage();


        foreach ($shippingCosts as &$shippingCost) {
            $shippingCost['dispatch'] = $this->translateDispatch($shippingCost['dispatch'], $languageId);
        }

        $total = count($shippingCosts);

        $this->view->assign(array(
            'data' => $shippingCosts,
            'total' => $total,
            'success' => true
        ));
    }

    /**
     * translates the dispatch fields
     *
     * @param $dispatch
     * @param $languageId
     * @return mixed
     */
    private function translateDispatch($dispatch, $languageId)
    {
        $translation = new Shopware_Components_Translation();
        $dispatchTranslations = $translation->read($languageId, 'config_dispatch');

        $dispatchId = $dispatch['id'];

        if ( !is_null($dispatchTranslations[$dispatchId]['dispatch_name'])) {
            $dispatch['name'] = $dispatchTranslations[$dispatchId]['dispatch_name'];
            $dispatch['dispatch_name'] = $dispatchTranslations[$dispatchId]['dispatch_name'];
        }

        $dispatch['description'] = $dispatchTranslations[$dispatchId]['description'];

        return $dispatch;
    }

    /**
     * if the billing address changes it updates the address in s_user_billingaddress
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function setBillingAddressAction()
    {
        $data = $this->Request()->getParams();

        /** @var Shopware\Models\Customer\Customer $customerModel */
        $customerModel = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $data['userId']);

        $billingAddressModel = $customerModel->getBilling();

        $billingAddressModel->fromArray($data);

        Shopware()->Models()->persist($billingAddressModel);
        Shopware()->Models()->flush();

        $this->view->assign(array(
            'billingAddressId' => $billingAddressModel->getId()
        ));
    }

    /**
     * if the billing address changes it updates the address in s_user_billingaddress
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function setShippingAddressAction()
    {
        $data = $this->Request()->getParams();
        //we need to set this because of a bug in the shopware models
        if (!isset($data['stateId'])) {
            $data['stateId'] = 0;
        }

        /** @var Shopware\Models\Customer\Customer $customerModel */
        $customerModel = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $data['userId']);


        if($shippingAddressModel = $customerModel->getShipping()) {
            $shippingAddressModel->fromArray($data);

            Shopware()->Models()->persist($shippingAddressModel);
            Shopware()->Models()->flush();

            $this->view->assign(array(
                'shippingAddressId' => $shippingAddressModel->getId()
            ));
        }
    }

    /**
     * returns the currencies which are available
     */
    public function getCurrenciesAction()
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Currency');

        $builder = $repository->createQueryBuilder('c');
        $builder->select(array(
            'c.id as id',
            'c.name as name',
            'c.currency as currency',
            'c.symbol as symbol',
            'c.factor as factor',
            'c.default as default'
        ));

        $query = $builder->getQuery();

        $total = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $data,
            'total' => $total
        ));
    }

    /**
     * reads the plugin config and passes it to the ext js application
     */
    public function getPluginConfigAction()
    {
        $config = Shopware()->Plugins()->Backend()->SwagBackendOrder()->Config();

        $desktopTypes = $config->get('desktopTypes');
        $desktopTypes = explode(',', $desktopTypes);
        $validationMail = $config->get('validationMail');

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

        $this->view->assign(array(
            'success' => true,
            'data' => $config,
            'total' => $total
        ));
    }

    /**
     * assigns the payment data for a user to ExtJs to show the data in the view
     */
    public function getCustomerPaymentDataAction() {

        $request    = $this->Request()->getParams();
        $customerId = $request['customerId'];
        $paymentId  = $request['paymentId'];

        /** @var Shopware\Models\Customer\PaymentData $payment */
        $paymentModel = Shopware()->CreateBackendOrder()->getCustomerPaymentData($customerId, $paymentId);
        $payment = Shopware()->Models()->toArray($paymentModel);

        $this->view->assign(array(
            'success' => true,
            'data' => $payment
        ));
    }

    /**
     * @return array
     */
    public function getLanguageSubShopsAction()
    {
        $mainShopId = $this->Request()->getParam('mainShopId');

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('shops')
                ->from('Shopware\Models\Shop\Shop', 'shops')
                ->where('shops.mainId = :mainShopId')
                ->orWhere('shops.id = :mainShopId')
                ->andWhere('shops.active = 1')
                ->setParameter('mainShopId', $mainShopId);

        $result = $builder->getQuery()->getArrayResult();
        $total = count($result);

        $this->view->assign(array(
            'data' => $result,
            'success' => true,
            'total' => $total
        ));
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
                ->from('Shopware\Models\Article\Article', 'articles')
                ->leftJoin('articles.details', 'details')
                ->where('details.number = :articleNumber')
                ->setParameter('articleNumber', $articleNumber);

        /** @var Shopware\Models\Article\Article[] $articleModels */
        $articleModels = $builder->getQuery()->getResult();

        if (count($articleModels) < 1) {
            $this->view->assign(array(
                'data' => array('articleNumber' => $articleNumber, 'error' => 'articleNumber'),
                'success' => false
            ));

            return false;
        }

        if ($articleModels[0]->getMainDetail()->getEsd()) {
            $this->view->assign(array(
                'data' => array('articleNumber' => $articleNumber, 'error' => 'esd'),
                'success' => false
            ));

            return false;
        }

        return true;
    }

    /**
     * @param Shopware\Models\Order\Order $orderModel
     */
    public function sendOrderConfirmationMail($orderModel)
    {
        $context                  = $this->prepareOrderConfirmationMailData($orderModel);
        $context['sOrderDetails'] = $this->prepareOrderDetailsConfirmationMailData($orderModel);

        $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
        $mail->addTo($context["additional"]["user"]["email"]);
        $mail->send();
    }

    /**
     * prepares the correct array structure for the mail template
     *
     * @param Shopware\Models\Order\Order $orderModel
     * @return array
     */
    private function prepareOrderDetailsConfirmationMailData($orderModel)
    {
        $details = Shopware()->Db()->fetchAll('SELECT * FROM s_order_details WHERE orderID = ?', array($orderModel->getId()));

        //we need to fake a shop instance if we want to use the Articles Module
        $shop = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->getActiveById($orderModel->getLanguageSubShop()->getId());
        $shop->registerResources(Shopware()->Bootstrap());
        
        foreach($details as &$detail) {
            /** @var Shopware\Models\Article\Repository $articleDetailRepository */
            $articleDetailRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
            /** @var Shopware\Models\Article\Detail[] $articleDetailModel */
            $articleDetailModel = $articleDetailRepository->findBy(array('number' => $detail['articleordernumber']));
            /** @var Shopware\Models\Article\Detail $articleDetailModel */
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
     * @param Shopware\Models\Order\Order $orderModel
     * @return mixed
     */
    private function prepareOrderConfirmationMailData($orderModel)
    {
        $billingAddress = Shopware()->Db()->fetchRow('SELECT *, userID AS customerBillingId FROM s_order_billingaddress WHERE orderID = ?', array($orderModel->getId()));
        $billingAddressAttributes = Shopware()->Db()->fetchRow('SELECT * FROM s_order_billingaddress_attributes WHERE billingID = ?', array($billingAddress['id']));
        if ( !empty($billingAddressAttributes)) {
            $billingAddress = array_merge($billingAddress, $billingAddressAttributes);
        }

        if (Shopware()->CreateBackendOrder()->getEqualBillingAddress()) {
            $shippingAddress = $billingAddress;
        } else {
            $shippingAddress = Shopware()->Db()->fetchRow('SELECT *, userID AS customerBillingId FROM s_order_shippingaddress WHERE orderID = ?', array($orderModel->getId()));
            $shippingAddressAttributes = Shopware()->Db()->fetchRow('SELECT * FROM s_order_shippingaddress_attributes WHERE shippingId = ?', array($shippingAddress['id']));
            if ( !empty($shippingAddressAttributes)) {
                $shippingAddress = array_merge($shippingAddress, $shippingAddressAttributes);
            }
        }
        $context['billingaddress']  = $billingAddress;
        $context['shippingaddress'] = $shippingAddress;

        $context['sOrderNumber'] = $orderModel->getNumber();

        $currency                  = $orderModel->getCurrency();
        $context['sCurrency']      = $currency;
        $context['sAmount']        = $orderModel->getInvoiceAmount() . ' ' . $currency;
        $context['sAmountNet']     = $orderModel->getInvoiceAmountNet() . ' ' . $currency;
        $context['sShippingCosts'] = $orderModel->getInvoiceShipping() . ' ' . $currency;

        $orderTime              = $orderModel->getOrderTime();
        $context['sOrderDay']   = $orderTime->format('d.m.Y');
        $context['sOrderTime']  = $orderTime->format('H:i');

        $context['sComment']    = '';
        $context['sLanguage']   = $orderModel->getLanguageSubShop()->getId();
        $context['sSubShop']    = $orderModel->getShop()->getId();

        $orderAttributes = Shopware()->Db()->fetchRow('SELECT * FROM s_order_attributes WHERE orderID = ?', array($orderModel->getId()));
        $context['attributes'] = $orderAttributes;

        $dispatch = Shopware()->Db()->fetchRow('SELECT * FROM s_premium_dispatch WHERE id = ?', array($orderModel->getDispatch()->getId()));
        $dispatch = $this->translateDispatch($dispatch, $orderModel->getLanguageSubShop()->getId());
        $context['sDispatch'] = $dispatch;

        $user = Shopware()->Db()->fetchRow('SELECT * FROM s_user WHERE id = ?', array($orderModel->getCustomer()->getId()));
        $context['additional']['user'] = $user;

        $country = Shopware()->Db()->fetchRow('SELECT * FROM s_core_countries WHERE id = ?', array($orderModel->getBilling()->getCountry()->getId()));
        $context['additional']['country'] = $country;

        $context['additional']['state'] = array();
        if ($orderModel->getBilling()->getState()) {
            $state = Shopware()->Db()->fetchRow('SELECT * FROM s_core_countries_states WHERE id = ?', array($orderModel->getBilling()->getState()->getId()));
            $context['additional']['state'] = $state;
        }

        $country = Shopware()->Db()->fetchRow('SELECT * FROM s_core_countries WHERE id = ?', array($orderModel->getShipping()->getCountry()->getId()));
        $context['additional']['countryShipping'] = $country;

        $context['additional']['stateShipping'] = array();
        if ($orderModel->getShipping()->getState()) {
            $state = Shopware()->Db()->fetchRow('SELECT * FROM s_core_countries_states WHERE id = ?', array($orderModel->getShipping()->getState()->getId()));
            $context['additional']['stateShipping'] = $state;
        }

        $payment = Shopware()->Db()->fetchRow('SELECT * FROM s_core_paymentmeans WHERE id = ?', array($orderModel->getPayment()->getId()));
        $payment = $this->translatePayment($payment, $orderModel->getLanguageSubShop()->getId());

        $context['additional']['payment'] = $payment;

        $context['sPaymentTable'] = array();
        if ($context['additional']['payment']['name'] === 'debit') {
            $paymentTable = Shopware()->Db()->fetchRow('SELECT * FROM s_core_payment_data WHERE user_id = ?', array($orderModel->getCustomer()->getId()));
            $context['sPaymentTable'] = $paymentTable;
        }

        $context['additional']['show_net'] = $orderModel->getNet();
        $context['additional']['charge_var'] = 1;

        return $context;
    }

    /**
     * gets the new order number
     *
     * @return int|string
     */
    private function getOrderNumber()
    {
        $number = Shopware()->Db()->fetchOne(
                "/*NO LIMIT*/ SELECT number FROM s_order_number WHERE name='invoice' FOR UPDATE"
        );
        Shopware()->Db()->executeUpdate(
                "UPDATE s_order_number SET number = number + 1 WHERE name='invoice'"
        );
        $number += 1;

        return $number;
    }

    /**
     * @param int $price
     * @param int $taxRate
     * @return float
     */
    private function calculateNetPrice($price, $taxRate)
    {
        return $price / ((100 + $taxRate) / 100);
    }

    /**
     * @param $price
     * @param $taxRate
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
        $sum = 0;
        $taxSum = 0;
        $taxSums = array();
        $totalWithoutTax = 0;
        $articleNumber = 0;
        $shippingCostsNet = 0;

        foreach ($positions as $position) {
            $total           += $position->price * $position->quantity;
            $priceWithoutTax  = $this->calculateNetPrice($position->price, $position->taxRate) * $position->quantity;
            $totalWithoutTax += $priceWithoutTax;

            if (!isset($taxSums[$position->taxRate])) {
                $taxSums[$position->taxRate] = array();
                $taxSums[$position->taxRate]['sum'] = 0;
                $taxSums[$position->taxRate]['count'] = 0;
            }
            $taxSums[$position->taxRate]['sum'] += $this->calculateNetPrice($position->price, $position->taxRate);
            $taxSums[$position->taxRate]['count'] += $position->quantity;

            $articleNumber += $position->quantity;
        }

        if ($shippingCosts != 0 && !is_null($shippingCosts) && $shippingCosts != '') {
            $taxRelation = 0;
            $shippingCostsRelation = 0;

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
        $total = $shippingCosts + $total;
        $totalWithoutTax = $shippingCostsNet + $totalWithoutTax;

        $taxSum = $total - $totalWithoutTax;
        if ($totalWithoutTax == 0)  {
            $taxSum = 0;
        }

        if ($net === 'true') {
            $shippingCostsNet = $shippingCosts;
            $totalWithoutTax = $total;
            $taxSum = 0;
        }

        $result = array(
            'totalWithoutTax' => $this->roundPrice($totalWithoutTax),
            'sum' => $this->roundPrice($sum),
            'total' => $this->roundPrice($total),
            'shippingCosts' => $this->roundPrice($shippingCosts),
            'shippingCostsNet' => $this->roundPrice($shippingCostsNet),
            'taxSum' => $this->roundPrice($taxSum)
        );

        $this->view->assign(array(
            'data' => $result,
            'success' => true
        ));
    }

    /**
     * calculates the new currency and returns the actual prices
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
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

        $this->view->assign(array(
            'data' => $data,
            'success' => true
        ));
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

        $this->view->assign(array(
            'data' => $positions,
            'success' => true
        ));
    }

    /**
     * @param float|int $price
     * @param int $digits
     * @return float
     */
    private function roundPrice($price, $digits = self::PRICE_PRECISION)
    {
        return round($price, $digits);
    }
}