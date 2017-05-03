<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Components;

use SwagBackendOrder\Components\CustomerRepository;

class CustomerRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function test_getList_with_one_match()
    {
        $expectedUser = [
            [
                'email' => 'test@example.com',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'number' => '20001',
                'company' => 'Muster GmbH',
                'zipCode' => '55555',
                'city' => 'Musterhausen',
            ],
        ];

        $result = $this->getCustomerRepository()->getList('test');

        $this->assertCount(1, $result);
        $this->assertArraySubset($expectedUser, $result);
    }

    public function test_getList_with_all_matches()
    {
        $expectedUsers = [
            [
                'id' => 1,
                'email' => 'test@example.com',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'number' => '20001',
                'company' => 'Muster GmbH',
                'zipCode' => '55555',
                'city' => 'Musterhausen',
            ], [
                'id' => 2,
                'email' => 'mustermann@b2b.de',
                'firstname' => 'Händler',
                'lastname' => 'Kundengruppe-Netto',
                'number' => '20003',
                'company' => 'B2B',
                'zipCode' => '55555',
                'city' => 'Musterstadt',
            ],
        ];

        $result = $this->getCustomerRepository()->getList(null);

        $this->assertCount(2, $result);
        $this->assertArraySubset($expectedUsers, $result);
    }

    public function test_get_one_customer()
    {
        $expectedUser = [
            'email' => 'test@example.com',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'number' => '20001',
        ];

        $result = $this->getCustomerRepository()->get(1);
        $this->assertArraySubset($expectedUser, $result);
    }

    public function test_get_one_customer_by_invalid_id()
    {
        $result = $this->getCustomerRepository()->get(999);
        $this->assertNull($result['email']);
    }

    /**
     * @return CustomerRepository
     */
    private function getCustomerRepository()
    {
        return Shopware()->Container()->get('swag_backend_order.customer_repository');
    }
}
