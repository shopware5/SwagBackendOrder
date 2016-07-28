<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Group;
use Shopware\Models\Customer\Repository;

class CustomerRepository
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param string $filter
     * @return array
     */
    public function getList($filter)
    {
        /** @var Repository $repository */
        $repository = $this->modelManager->getRepository(Customer::class);
        $builder = $repository->getListQueryBuilder($filter);
        $builder->addSelect('customer.email');

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function get($customerId)
    {
        $builder = $this->getDetailBuilder($customerId);
        $customer = $builder->getQuery()->getArrayResult()[0];

        $addressRepository = $this->modelManager->getRepository(Address::class);
        $customer['address'] = $addressRepository->getListArray($customerId);
        $customer['billing'] = $customer['address'];
        $customer['shipping'] = $customer['address'];
        $customer['group'] = $this->getGroup($customer['groupKey']);

        return $customer;
    }

    /**
     * @param int $customerId
     * @return QueryBuilder
     */
    protected function getDetailBuilder($customerId)
    {
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select([
            'customer',
            'paymentData',
            'shop',
            'languageSubShop'
        ]);

        $builder->from(Customer::class, 'customer');
        $builder->leftJoin('customer.paymentData', 'paymentData');
        $builder->leftJoin('customer.languageSubShop', 'languageSubShop');
        $builder->leftJoin('customer.shop', 'shop');
        $builder->where('customer.id = :customerId');
        $builder->setParameter('customerId', $customerId);

        return $builder;
    }

    /**
     * @param string $key
     * @return array
     */
    private function getGroup($key)
    {
        $repository = $this->modelManager->getRepository(Group::class);
        $group = $repository->findOneBy([ 'key' => $key ]);

        return $this->modelManager->toArray($group);
    }
}
