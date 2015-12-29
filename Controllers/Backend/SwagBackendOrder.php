<?php

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
     * gets customers by the email, customer number, company or full name
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

        foreach ($result as $i => $customer) {
            $result[$i] = $this->extractCustomerData($customer);
        }

        $total = count($result);

        $this->view->assign(
            [
                'data' => $result,
                'total' => $total,
                'success' => true
            ]
        );
    }

    /**
     * Only return relevant information about the customer (e.g. not their password hash).
     *
     * @param array $customer
     * @return array
     */
    private function extractCustomerData(array $customer)
    {
        return [
            'id' => $customer['id'],
            'email' => $customer['email'],
            'billing' => $customer['billing'],
            'debit' => $customer['debit'],
            'shipping' => $customer['shipping'],
            'shop' => $customer['shop'],
            'shopId' => $customer['shopId'],
            'languageId' => $customer['languageId'],
            'languageSubShop' => $customer['languageSubShop'],

            // Used for search:
            'customerCompany' => $customer['customerCompany'],
            'customerName' => $customer['customerName'],
            'customerNumber' => $customer['customerNumber']
        ];
    }

    /**
     * method to create an order
     */
    public function createOrderAction()
    {
        $data = $this->Request()->getParams();
        $data = $data['data'];

        $orderNumber = $this->getOrderNumber();

        /** @var \Shopware_Components_CreateBackendOrder $createBackendOrder */
        $createBackendOrder = Shopware()->CreateBackendOrder();
        $hasMailError = false;

        try {
            /** @var Shopware\Models\Order\Order $orderModel */
            $orderModel = $createBackendOrder->createOrder($data, $orderNumber);

            if (!$orderModel instanceof \Shopware\Models\Order\Order) {
                $this->view->assign($orderModel);

                return false;
            }
        } catch (\Exception $e) {
            $this->view->assign(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );

            return;
        }

        try {
            //sends and prepares the order confirmation mail
            $this->sendOrderConfirmationMail($orderModel);
        } catch (\Exception $e) {
            $hasMailError = $e->getMessage();
        }

        if ($hasMailError) {
            $this->view->assign(
                [
                    'success' => true,
                    'orderId' => $orderModel->getId(),
                    'mail' => $hasMailError,
                    'ordernumber' => $orderModel->getNumber()
                ]
            );

            return;
        }

        $this->view->assign(
            [
                'success' => true,
                'orderId' => $orderModel->getId(),
                'ordernumber' => $orderModel->getNumber()
            ]
        );
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
        $builder->from('Shopware\Models\Article\Article', 'articles')
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
            ->from('Shopware\Models\Payment\Payment', 'payment');

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
            ->from('Shopware\Models\Dispatch\ShippingCost', 'shipping')
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
     * if the billing address changes it updates the address in s_user_billingaddress
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

        $this->view->assign(['billingAddressId' => $billingAddressModel->getId()]);
    }

    /**
     * if the billing address changes it updates the address in s_user_billingaddress
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

        if ($shippingAddressModel = $customerModel->getShipping()) {
            $shippingAddressModel->fromArray($data);

            Shopware()->Models()->persist($shippingAddressModel);
            Shopware()->Models()->flush();

            $this->view->assign(['shippingAddressId' => $shippingAddressModel->getId()]);
        }
    }

    /**
     * returns the currencies which are available
     */
    public function getCurrenciesAction()
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Currency');

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
        /** @var \Enlight_Config $config */
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
        $request = $this->Request()->getParams();
        $customerId = $request['customerId'];
        $paymentId = $request['paymentId'];

        /** @var \Shopware_Components_CreateBackendOrder $createBackendOrder */
        $createBackendOrder = Shopware()->CreateBackendOrder();
        $paymentModel = $createBackendOrder->getCustomerPaymentData($customerId, $paymentId);
        /** @var Shopware\Models\Customer\PaymentData $payment */
        $payment = Shopware()->Models()->toArray($paymentModel);

        $this->view->assign(
            [
                'success' => true,
                'data' => $payment
            ]
        );
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

        //Gets the correct language name for every shop
        foreach ($result as &$shop) {
            /** @var \Shopware\Models\Shop\Shop $shopModel */
            $shopModel = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $shop['id']);
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
            ->from('Shopware\Models\Article\Article', 'articles')
            ->leftJoin('articles.details', 'details')
            ->where('details.number = :articleNumber')
            ->setParameter('articleNumber', $articleNumber);

        /** @var Shopware\Models\Article\Article[] $articleModels */
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
     * @param \Shopware\Models\Order\Order $orderModel
     */
    private function sendOrderConfirmationMail($orderModel)
    {
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
    }

    /**
     * prepares the correct array structure for the mail template
     *
     * @param \Shopware\Models\Order\Order $orderModel
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
            $articleDetailRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
            /** @var Shopware\Models\Article\Detail[] $articleDetailModel */
            $articleDetailModel = $articleDetailRepository->findBy(['number' => $detail['articleordernumber']]);
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
     * @param \Shopware\Models\Order\Order $orderModel
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

        /** @var \Shopware_Components_CreateBackendOrder $createBackendOrder */
        $createBackendOrder = Shopware()->CreateBackendOrder();
        if ($createBackendOrder->getEqualBillingAddress()) {
            $shippingAddress = $billingAddress;
        } else {
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
        if ($context['additional']['payment']['name'] === 'debit') {
            $paymentTable = Shopware()->Db()->fetchRow(
                'SELECT * FROM s_core_payment_data WHERE user_id = ?',
                [$orderModel->getCustomer()->getId()]
            );
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
        $number = Shopware()->Db()->fetchOne("/*NO LIMIT*/ SELECT number FROM s_order_number WHERE name='invoice' FOR UPDATE");
        Shopware()->Db()->executeUpdate("UPDATE s_order_number SET number = number + 1 WHERE name='invoice'");
        $number += 1;

        return $number;
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

        /** @var \Shopware\Models\Customer\Customer $customerModel */
        $customerModel = $this->getModelManager()->find('Shopware\Models\Customer\Customer', $data['customerId']);

        /** @var \Shopware\Models\Article\Repository $productRepository */
        $productRepository = $this->getModelManager()->getRepository('Shopware\Models\Article\Detail');

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
}
