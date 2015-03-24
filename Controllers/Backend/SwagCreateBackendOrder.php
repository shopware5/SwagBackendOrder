<?php

class Shopware_Controllers_Backend_SwagCreateBackendOrder
    extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * sets the default desktop type if no desktop type was chosen
     */
    const DEFAULT_DESKTOP_TYPE = 'Backend';

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

        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select(array('customers, billing', 'shipping', 'debit', 'shop'))
                ->from('Shopware\Models\Customer\Customer', 'customers')
                ->leftJoin('customers.billing', 'billing')
                ->leftJoin('customers.shipping', 'shipping')
                ->leftJoin('customers.debit', 'debit')
                ->leftJoin('customers.shop', 'shop')
                ->where('customers.id = :search');

         /**
         * checks if a search was done or a selection from the drop down field
         */
        if (isset($data['filter'][0]['value'])) {
            $search = $data['filter'][0]['value'];

            /**
             * adding where statements
             * concats the first name and the last name to a full name for the search (uses the billing table)
             */
            $builder->where($builder->expr()->like(
                            $builder->expr()->concat('billing.firstName',
                                    $builder->expr()->concat($builder->expr()->literal(' '), 'billing.lastName')
                            ),
                            $builder->expr()->literal($search)
                    )
                )
                ->orWhere('billing.company LIKE :search')
                ->orWhere('shipping.company LIKE :search')
                ->orWhere('billing.number = :search')
                ->orWhere('customers.email LIKE :search')
                ->setParameter('search', $search)
                ->groupBy('customers.id')
                ->orderBy('billing.firstName');

            $result = $builder->getQuery()->getArrayResult();

            /**
             * gets data for the customer drop down search field
             */
            foreach ($result as &$customer) {
                $customer['customerCompany'] = $customer['billing']['company'];
                $customer['customerNumber']  = $customer['billing']['number'];
                $customer['customerName']    = $customer['billing']['firstName'] . ' ' . $customer['billing']['lastName'];
            }

        } else {
            $search = $this->Request()->get('searchParam');
            $builder->setParameter('search', $search);

            $billingAddresses      = $this->getOrderAddresses($search, 'Shopware\Models\Order\Billing', 'billings');
            $shippingAddresses     = $this->getOrderAddresses($search, 'Shopware\Models\Order\Shipping', 'shipping');
            $result                = $builder->getQuery()->getArrayResult();
            if ($billingAddresses) {
                $result[0]['billing']  = $billingAddresses;
            }

            if ($shippingAddresses) {
                $result[0]['shipping'] = $shippingAddresses;
            }
        }
        $total = count($result);

        $this->view->assign(array(
            'data' => $result,
            'total' => $total,
            'success' => true
        ));
    }

    /**
     * gets shipping and billing addresses
     *
     * @param string $searchParam
     * @param string $table doctrine model
     * @param string $alias
     * @return array
     */
    private function getOrderAddresses($searchParam, $table, $alias)
    {
        $builderBilling = Shopware()->Models()->createQueryBuilder();

        $builderBilling->select(array($alias, 'country', 'state'))
                        ->from($table, $alias)
                        ->leftJoin($alias.'.country', 'country')
                        ->leftJoin($alias.'.state', 'state')
                        ->where($alias.'.customerId = :search')
                        ->setParameter('search', $searchParam)
                        ->groupBy($alias.'.customerId');

        $fieldsGroupBy = array(
                'company',
                'countryId',
                'stateId',
                'salutation',
                'zipCode',
                'department',
                'firstName',
                'lastName',
                'street',
                'city'
        );

        if ($table === 'Shopware\Models\Order\Billing') {
            array_push($fieldsGroupBy,
                'phone',
                'fax',
                'vatId'
            );
        }

        /**
         * creates group by statements to get unique addresses
         */
        foreach ($fieldsGroupBy as $groupBy) {
            $builderBilling->addGroupBy($alias . '.' . $groupBy);
        }
        $result = $builderBilling->getQuery()->getArrayResult();

        /**
         * renames the association key to be sure where the id belongs to
         */
        foreach ($result as &$address) {
            $address['orderAddressId'] = $address['id'];
            $address['country']        = $address['country']['name'];
            $address['state']          = $address['state']['name'];
            unset($address['id']);
        }

        return $result;
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

        $positions = $data['position'];

        $ordernumber = $this->getOrderNumber();

        /**
         * creates an empty row
         * -> workaround for the partner model (you must pass one, but not every order has a partner)
         */
        $sql = 'INSERT INTO s_order (ordernumber)
                        VALUES (?)';

        Shopware()->Db()->query($sql, array($ordernumber));

        $sql = 'SELECT id FROM s_order WHERE ordernumber = ?';
        $this->orderId = Shopware()->Db()->fetchOne($sql, array($ordernumber));

        /** @var Shopware\Models\Order\Order $orderModel */
        $orderModel = Shopware()->Models()->find('Shopware\Models\Order\Order', $this->orderId);

        /** @var Shopware\Models\Customer\Customer $customerModel */
        $customerModel = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $data['customerId']);
        $orderModel->setCustomer($customerModel);

        /** @var Shopware\Models\Dispatch\Dispatch $dispatchModel */
        $dispatchModel = Shopware()->Models()->find('Shopware\Models\Dispatch\Dispatch', $data['dispatchId']);
        $orderModel->setDispatch($dispatchModel);


        /** @var Shopware\Models\Payment\Payment $paymentModel */
        $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $data['paymentId']);
        $orderModel->setPayment($paymentModel);

        /**
         * 0 = order status open
         * @var Shopware\Models\Order\Status $orderStatusModel
         */
        $orderStatusModel = Shopware()->Models()->find('Shopware\Models\Order\Status', 0);
        $orderModel->setOrderStatus($orderStatusModel);

        /**
         * 17 = payment status open
         * @var Shopware\Models\Order\Status $paymentStatusModel
         */
        $paymentStatusModel = Shopware()->Models()->find('Shopware\Models\Order\Status', 17);
        $orderModel->setPaymentStatus($paymentStatusModel);

        /**
         * @var Shopware\Models\Shop\Shop $languageSubShopModel
         */
        $languageSubShopModel = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $data['languageShopId']);
        $orderModel->setLanguageSubShop($languageSubShopModel);

        $orderModel->setInvoiceShippingNet($data['shippingCostsNet']);
        $orderModel->setInvoiceShipping($data['shippingCosts']);

        $orderModel->setInvoiceAmount($data['total']);
        $orderModel->setInvoiceAmountNet($data['totalWithoutTax']);

        $orderModel->setShop($customerModel->getShop());

        $orderModel->setNumber($ordernumber);

        $orderModel->setOrderTime(new DateTime('now'));

        $data['desktopType'] = self::DEFAULT_DESKTOP_TYPE;
        if ($data['desktopType'] !== '' && $data['desktopType'] !== null && isset($data['desktopType'])) {
            $orderModel->setDeviceType($data['desktopType']);
        }

        $orderModel->setTransactionId('');
        $orderModel->setComment('');
        $orderModel->setCustomerComment('');
        $orderModel->setInternalComment('');
        $orderModel->setNet($data['net']);
        $orderModel->setTemporaryId('');
        $orderModel->setReferer('');
        $orderModel->setTrackingCode('');
        $orderModel->setRemoteAddress('');

        /** @var Shopware\Models\Shop\Currency $currencyModel */
        $currencyModel = Shopware()->Models()->find('Shopware\Models\Shop\Currency', $data['currencyId']);
        $orderModel->setCurrencyFactor($currencyModel->getFactor());
        $orderModel->setCurrency($currencyModel->getCurrency());

        /** @var Shopware\Models\Order\Detail[] $details */
        $details = array();

        //checks if more than one position was passed
        if ( $this->isAssoc($positions)) {
            $details[] = $this->createOrderDetail($positions, $orderModel);

            if ( !end($details)) {
                $this->deleteOrder();
                return false;
            }
        } else {
            foreach ($positions as $position) {
                $details[] = $this->createOrderDetail($position, $orderModel);

                if ( !end($details)) {
                    $this->deleteOrder();
                    return false;
                }
            }
        }
        $orderModel->setDetails($details);

        /** @var Shopware\Models\Attribute\Order[] $orderAttributes */
        $orderAttributes = $this->setOrderAttributes($data['orderAttribute'][0]);
        $orderModel->setAttribute($orderAttributes);

        /** @var Shopware\Models\Order\Billing $billingModel */
        $billingModel = $this->createBillingAddress($data);
        $orderModel->setBilling($billingModel);

        /** @var Shopware\Models\Order\Shipping $shippingModel */
        $shippingModel = $this->createShippingAddress($data);
        $orderModel->setShipping($shippingModel);

        /** @var Shopware\Models\Payment\PaymentInstance $paymentInstance */
        $paymentInstance = $this->preparePaymentInstance($orderModel);
        $orderModel->setPaymentInstances($paymentInstance);

        Shopware()->Models()->persist($paymentInstance);
        Shopware()->Models()->persist($orderModel);
        Shopware()->Models()->flush();

        /*
         * I don't know why but the amountNet changes to the amount after the first flushing but it was written correct to the db
         * this is only for using the model without problems
         */
        $orderModel->setInvoiceAmountNet($data['totalWithoutTax']);
        Shopware()->Models()->persist($orderModel);
        Shopware()->Models()->flush();

        if ( is_null($billingModel->getState()))  {
            Shopware()->Db()->update('s_order_billingaddress', array('stateID' => 0), array('id' => $billingModel->getId()));
        }
        if ( is_null($shippingModel->getState())) {
            Shopware()->Db()->update(
                    's_order_shippingaddress',
                    array('stateID' => 0),
                    array('id' => $shippingModel->getId())
            );
        }

        //sends and prepares the order confirmation mail
        $this->sendOrderConfirmationMail($orderModel);

        $this->view->assign(array(
            'success' => true,
            'orderId' => $this->orderId
        ));
    }

    /**
     * @param array $position
     * @param Shopware\Models\Order\Order $orderModel
     * @return array
     */
    private function createOrderDetail($position, $orderModel)
    {
        $orderDetailModel = new Shopware\Models\Order\Detail();

        $articleIds = Shopware()->Db()->fetchRow("SELECT a.id, ad.id AS detailId
                                                  FROM s_articles a, s_articles_details ad
                                                  WHERE a.id = ad.articleID
                                                  AND ad.ordernumber = ?",
                                        array($position['articleNumber']));

        //checks if the article exists
        if ( empty($articleIds)) {
            $this->view->assign(array(
                'success' => false,
                'data' => array('articleNumber' => $position['articleNumber'])
            ));

            return false;
        }

        $articleId = $articleIds['id'];
        $articleDetailId = $articleIds['detailId'];

        /** @var Shopware\Models\Article\Article $articleModel */
        $articleModel = Shopware()->Models()->find('Shopware\Models\Article\Article', $articleId);

        /** @var Shopware\Models\Article\Detail $articleDetailModel */
        $articleDetailModel = Shopware()->Models()->find('Shopware\Models\Article\Detail', $articleDetailId);

        if ( is_object($articleDetailModel->getUnit())) {
            $unitName = $articleDetailModel->getUnit()->getName();
        } else {
            $unitName = 0;
        }

        /** @var Shopware\Models\Tax\Tax $taxModel */
        $taxModel = Shopware()->Models()->find('Shopware\Models\Tax\Tax', $position['taxId']);
        $orderDetailModel->setTax($taxModel);
        $orderDetailModel->setTaxRate($position['taxRate']);

        /** checks if it is an esdArticle */
        $orderDetailModel->setEsdArticle(0);

        /** @var Shopware\Models\Order\DetailStatus $detailStatusModel */
        $detailStatusModel = Shopware()->Models()->find('Shopware\Models\Order\DetailStatus', 0);
        $orderDetailModel->setStatus($detailStatusModel);

        $orderDetailModel->setArticleId($articleModel->getId());
        $orderDetailModel->setArticleName($articleModel->getName());
        $orderDetailModel->setArticleNumber($articleModel->getMainDetail()->getNumber());
        $orderDetailModel->setPrice($position['price']);
        $orderDetailModel->setMode(4);
        $orderDetailModel->setQuantity($position['quantity']);
        $orderDetailModel->setShipped(0);
        $orderDetailModel->setUnit($unitName);
        $orderDetailModel->setPackUnit($articleDetailModel->getPackUnit());

        $orderDetailModel->setNumber($orderModel->getNumber());
        $orderDetailModel->setOrder($orderModel);

        return $orderDetailModel;
    }

    /**
     * @param Shopware\Models\Order\Order $orderModel
     * @return string
     */
    private function preparePaymentInstance($orderModel)
    {
        $paymentId = $orderModel->getPayment()->getId();
        $customerId = $orderModel->getCustomer()->getId();

        $paymentInstanceModel = new Shopware\Models\Payment\PaymentInstance();

        /** @var Shopware\Models\Customer\PaymentData[] $paymentDataModel */
        $paymentDataModel = $this->getCustomerPaymentData($customerId, $paymentId);

        if ($paymentDataModel[0] instanceof Shopware\Models\Customer\PaymentData) {
            /** @var Shopware\Models\Customer\PaymentData $paymentDataModel */
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
        $config = Shopware()->Plugins()->Backend()->SwagCreateBackendOrder()->Config();

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
        $paymentModel = $this->getCustomerPaymentData($customerId, $paymentId);
        $payment = Shopware()->Models()->toArray($paymentModel);

        $this->view->assign(array(
            'success' => true,
            'data' => $payment
        ));
    }

    /**
     * selects the payment data by user and payment id
     *
     * @param $customerId
     * @param $paymentId
     * @return Shopware\Models\Customer\PaymentData
     */
    private function getCustomerPaymentData($customerId, $paymentId) {

        $PaymentDataRepository = Shopware()->Models()->getRepository('Shopware\Models\Customer\PaymentData');
        $paymentModel          = $PaymentDataRepository->findBy(array('paymentMeanId' => $paymentId, 'customer' => $customerId));

        return $paymentModel;
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
     * creates the shipping address which belongs to the order and
     * saves it as the new last used address
     *
     * @param array $data
     * @return \Shopware\Models\Order\Shipping
     */
    private function createShippingAddress($data)
    {
        if ($data['shippingAddressId']) {
            /** @var Shopware\Models\Customer\Shipping $addressHolderModel */
            $addressHolderModel = Shopware()->Models()->find('Shopware\Models\Customer\Shipping', $data['shippingAddressId']);
        } else {
            /** @var Shopware\Models\Customer\Billing $shippingAddressHolder */
            $addressHolderModel = Shopware()->Models()->find('Shopware\Models\Customer\Billing', $data['billingAddressId']);
            $this->equalBillingAddress = true;
        }

        $shippingOrderModel = new Shopware\Models\Order\Shipping();
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
            /** @var Shopware\Models\Country\Country $countryModel */
            $countryModel = Shopware()->Models()->find('Shopware\Models\Country\Country', $addressHolderModel->getCountryId());
            $shippingOrderModel->setCountry($countryModel);
        }

        if ($addressHolderModel->getStateId()) {
            /** @var Shopware\Models\Country\State $stateModel */
            $stateModel = Shopware()->Models()->find('Shopware\Models\Country\State', $addressHolderModel->getStateId());
            $shippingOrderModel->setState($stateModel);
        }

        return $shippingOrderModel;
    }

    /**
     * creates the billing address which belongs to the order and
     * saves it as the new last used address
     *
     * @param array $data
     * @return \Shopware\Models\Order\Billing
     */
    private function createBillingAddress($data)
    {
        /** @var Shopware\Models\Customer\Billing $billingCustomerModel */
        $billingCustomerModel = Shopware()->Models()->find('Shopware\Models\Customer\Billing', $data['billingAddressId']);

        $billingOrderModel = new Shopware\Models\Order\Billing();
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
        $billingOrderModel->setFax($billingCustomerModel->getFax());
        $billingOrderModel->setCompany($billingCustomerModel->getCompany());
        $billingOrderModel->setDepartment($billingCustomerModel->getDepartment());
        $billingOrderModel->setNumber($billingCustomerModel->getNumber());
        $billingOrderModel->setCustomer($billingCustomerModel->getCustomer());

        if ($billingCustomerModel->getCountryId()) {
            /** @var Shopware\Models\Country\Country $countryModel */
            $countryModel = Shopware()->Models()->find('Shopware\Models\Country\Country', $billingCustomerModel->getCountryId());
            $billingOrderModel->setCountry($countryModel);
        }

        if ($billingCustomerModel->getStateId()) {
            /** @var Shopware\Models\Country\State $stateModel */
            $stateModel = Shopware()->Models()->find('Shopware\Models\Country\State', $billingCustomerModel->getStateId());
            $billingOrderModel->setState($stateModel);
        }

        return $billingOrderModel;
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

        if ($this->equalBillingAddress) {
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
     * helper function which checks if it is an associative array,
     * to distinguish between an order with one or an order with more than
     * one position
     *
     * @param $array
     * @return bool
     */
    private function isAssoc($array)
    {
        return array_keys($array) !== range(0, count($array) -1);
    }


    /**
     * sets the order attributes
     *
     * @param $attributes
     * @return \Shopware\Models\Attribute\Order
     */
    private function setOrderAttributes($attributes)
    {
        $orderAttributeModel = new \Shopware\Models\Attribute\Order();
        $orderAttributeModel->setAttribute1($attributes['attribute1']);
        $orderAttributeModel->setAttribute2($attributes['attribute2']);
        $orderAttributeModel->setAttribute3($attributes['attribute3']);
        $orderAttributeModel->setAttribute4($attributes['attribute4']);
        $orderAttributeModel->setAttribute5($attributes['attribute5']);
        $orderAttributeModel->setAttribute6($attributes['attribute6']);

        return $orderAttributeModel;
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
     * deletes the empty row
     */
    private function deleteOrder()
    {
        if ( isset($this->orderId) && $this->orderId > 0) {
            Shopware()->Db()->delete('s_order', ['id' => $this->orderId]);
        }
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
            $total += $position->price;
            $totalWithoutTax += $this->calculateNetPrice($position->price, $position->taxRate);

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