<?php

use Doctrine\ORM\Query\Expr\Join;

class Shopware_Components_CustomerInformationHandler extends Enlight_Class
{
    /**
     * @param string $customerId
     * @return array
     */
    public function getCustomer($customerId)
    {
        $builder = $this->getCustomerQueryBuilder();
        $builder->setParameter('search', $customerId);

        $result = $builder->getQuery()->getArrayResult();

        $billingAddresses = $this->getOrderAddresses($customerId, 'Shopware\Models\Order\Billing', 'billings');
        $shippingAddresses = $this->getOrderAddresses($customerId, 'Shopware\Models\Order\Shipping', 'shipping');
        $alternativeShippingAddress = $this->getAlternativeShippingAddress($customerId);

        if (!$this->isEqualShippingAddresses($shippingAddresses, $alternativeShippingAddress)) {
            $shippingAddresses[] = $alternativeShippingAddress;
        }

        if ($billingAddresses) {
            $result[0]['billing'] = $billingAddresses;
        }

        if ($shippingAddresses) {
            $result[0]['shipping'] = $shippingAddresses;
        }

        return $result;
    }

    /**
     * Gets the customer list for the drop down live search list
     *
     * @param string $search
     * @return array
     */
    public function getCustomerList($search)
    {
        $builder = $this->getCustomerQueryBuilder();

        /**
         * adding where statements
         * concats the first name and the last name to a full name for the search (uses the billing table)
         */
        $builder->where(
            $builder->expr()->like(
                $builder->expr()->concat(
                    'billing.firstName',
                    $builder->expr()->concat($builder->expr()->literal(' '), 'billing.lastName')
                ),
                $builder->expr()->literal($search)
            )
        )
            ->orWhere('billing.company LIKE :search')
            ->orWhere('shipping.company LIKE :search')
            ->orWhere('billing.number LIKE :search')
            ->orWhere('customers.email LIKE :search')
            ->setParameter('search', $search)
            ->groupBy('customers.id')
            ->orderBy('billing.firstName');

        $result = $builder->getQuery()->getArrayResult();

        /**
         * maps data for the customer drop down search field
         */
        foreach ($result as &$customer) {
            $customer['customerCompany'] = $customer['billing']['company'];
            $customer['customerNumber'] = $customer['billing']['number'];
            $customer['customerName'] = $customer['billing']['firstName'] . ' ' . $customer['billing']['lastName'];
        }

        return $result;
    }

    /**
     * Query builder for customer
     *
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getCustomerQueryBuilder()
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select(['customers', 'billing', 'shipping', 'debit', 'shop', 'languageSubShop'])
            ->from('Shopware\Models\Customer\Customer', 'customers')
            ->leftJoin('customers.billing', 'billing')
            ->leftJoin('customers.shipping', 'shipping')
            ->leftJoin('customers.debit', 'debit')
            ->leftJoin('customers.languageSubShop', 'languageSubShop')
            ->leftJoin('customers.shop', 'shop')
            ->where('customers.id = :search');

        return $builder;
    }

    /**
     * @param int $customerId
     * @return array|bool
     */
    private function getAlternativeShippingAddress($customerId)
    {
        $builder = $this->getCustomerShippingAddressQueryBuilder($customerId);

        $result = $builder->getQuery()->getArrayResult();

        // return false if no shipping address is set for customer
        if (empty($result)) {
            return false;
        }

        return $this->mapAlternativeShippingAddressResult($result);
    }

    /**
     * @param array $result
     * @return array
     */
    private function mapAlternativeShippingAddressResult(array $result)
    {
        $alternativeShippingAddress = $result[0];
        $alternativeShippingAddress['country'] = $result[1]['name'];
        $alternativeShippingAddress['state'] = $result[2]['name'];

        return $alternativeShippingAddress;
    }


    /**
     * gets shipping and billing addresses
     *
     * @param string $customerId
     * @param string $model doctrine model
     * @param string $alias
     * @return array
     */
    private function getOrderAddresses($customerId, $model, $alias)
    {
        $builder = $this->getOrderAddressesQueryBuilder($customerId, $model, $alias);

        $fieldsGroupBy = $this->getGroupByFieldsForOrderAddresses($model);

        /**
         * creates group by statements to get unique addresses
         */
        foreach ($fieldsGroupBy as $groupBy) {
            $builder->addGroupBy($alias . '.' . $groupBy);
        }
        $result = $builder->getQuery()->getArrayResult();

        /**
         * renames the association key to be sure where the id belongs to
         */
        foreach ($result as &$address) {
            $address['orderAddressId'] = $address['id'];
            $address['country'] = $address['country']['name'];
            $address['state'] = $address['state']['name'];
            unset($address['id']);
        }

        return $result;
    }

    /**
     * @param string $model
     * @return array
     */
    public function getGroupByFieldsForOrderAddresses($model)
    {
        $fieldsGroupBy = [
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
        ];

        if ($model === 'Shopware\Models\Order\Billing') {
            array_push(
                $fieldsGroupBy,
                'phone',
                'fax',
                'vatId'
            );
        }

        return $fieldsGroupBy;
    }

    /**
     * Gets the query builder for the alternative shipping address which is placed in the `s_user_shippingaddress` table
     *
     * @param string $searchParam
     * @return \Shopware\Components\Model\QueryBuilder
     */
    private function getCustomerShippingAddressQueryBuilder($searchParam)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $alias = 'shippingCustomer';

        $builder->select([$alias, 'country', 'state'])
            ->from('Shopware\Models\Customer\Shipping', $alias)
            ->leftJoin('Shopware\Models\Country\Country', 'country', Join::LEFT_JOIN, 'country.id = ' . $alias . '.countryId')
            ->leftJoin('Shopware\Models\Country\State', 'state', Join::LEFT_JOIN, 'state.id = ' . $alias . '.stateId')
            ->where($alias . '.customerId = :search')
            ->setParameter('search', $searchParam)
            ->groupBy($alias . '.customerId');

        return $builder;
    }

    /**
     * @param string $searchParam
     * @param string $model
     * @param string $alias
     * @return \Shopware\Components\Model\QueryBuilder
     */
    private function getOrderAddressesQueryBuilder($searchParam, $model, $alias)
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select([$alias, 'country', 'state'])
            ->from($model, $alias)
            ->leftJoin($alias . '.country', 'country')
            ->leftJoin($alias . '.state', 'state')
            ->where($alias . '.customerId = :search')
            ->setParameter('search', $searchParam)
            ->groupBy($alias . '.customerId');

        return $builder;
    }

    /**
     * Checks the shipping addresses for an address with matches exactly the alternative shipping address
     *
     * @param array $shippingAddresses
     * @param array $alternativeShippingAddress
     * @return bool
     */
    private function isEqualShippingAddresses($shippingAddresses, $alternativeShippingAddress)
    {
        foreach ($shippingAddresses as $shippingAddress) {
            $result = array_diff($shippingAddress, $alternativeShippingAddress);
            //unset individual data
            unset($result['id']);
            unset($result['orderId']);
            unset($result['orderAddressId']);

            if (empty($result)) {
                return true;
            }
        }

        return false;
    }
}
