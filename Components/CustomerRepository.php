<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Group;

class CustomerRepository
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function getList(?string $filter = null): array
    {
        return $this->getListQueryBuilder($filter)->getQuery()->getArrayResult();
    }

    public function get(int $customerId): array
    {
        $builder = $this->getDetailBuilder($customerId);
        $customer = $builder->getQuery()->getArrayResult()[0];

        $customer['address'] = $this->modelManager->getRepository(Address::class)->getListArray($customerId);
        $customer['billing'] = $customer['address'];
        $customer['shipping'] = $customer['address'];
        $customer['group'] = $this->getGroup((string) $customer['groupKey']);

        return $customer;
    }

    protected function getDetailBuilder(int $customerId): QueryBuilder
    {
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select([
            'customer',
            'paymentData',
            'shop',
            'languageSubShop',
        ]);

        $builder->from(Customer::class, 'customer');
        $builder->leftJoin('customer.paymentData', 'paymentData');
        $builder->leftJoin('customer.languageSubShop', 'languageSubShop');
        $builder->leftJoin('customer.shop', 'shop');
        $builder->where('customer.id = :customerId');
        $builder->setParameter('customerId', $customerId);

        return $builder;
    }

    protected function getListQueryBuilder(?string $filter = null): QueryBuilder
    {
        $builder = $this->modelManager->createQueryBuilder();

        // add the displayed columns
        $builder->select([
            'customer.id',
            'customer.email',
            'customer.firstname as firstname',
            'customer.lastname as lastname',
            'customer.number as number',
            'billing.company as company',
            'billing.zipcode as zipCode',
            'billing.city as city',
        ]);

        $builder->from(Customer::class, 'customer')
            ->join('customer.defaultBillingAddress', 'billing');

        // filter the displayed columns with the passed filter string
        if ($filter !== null) {
            $fullNameExp = $builder->expr()->concat('customer.firstname', $builder->expr()->concat($builder->expr()->literal(' '), 'customer.lastname'));
            $fullNameReversedExp = $builder->expr()->concat('customer.lastname', $builder->expr()->concat($builder->expr()->literal(' '), 'customer.firstname'));

            $builder->where('customer.number LIKE ?1') // Search only the beginning of the customer number.
                ->orWhere('customer.firstname LIKE ?2') // Full text search for the first name of the customer
                ->orWhere('customer.lastname LIKE ?2') // Full text search for the last name of the customer
                ->orWhere($fullNameExp . ' LIKE ?2') // Full text search for the full name of the customer
                ->orWhere($fullNameReversedExp . ' LIKE ?2') // Full text search for the full name in reversed order of the customer
                ->orWhere('customer.email LIKE ?2') // Full text search for the customer email
                ->orWhere('billing.company LIKE ?2') // Full text search for the company of the customer
                ->orWhere('billing.city LIKE ?2') // Full text search for the city of the customer
                ->orWhere('billing.zipcode LIKE ?1') // Search only the beginning of the customer number.
                ->setParameter(1, $filter . '%')
                ->setParameter(2, '%' . $filter . '%');
        }

        return $builder;
    }

    private function getGroup(string $key): array
    {
        $group = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => $key]);
        if (!$group instanceof Group) {
            return [];
        }

        return $this->modelManager->toArray($group);
    }
}
