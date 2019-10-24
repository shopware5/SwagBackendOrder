<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\CustomerRepository;

class CustomerRepositoryTest extends TestCase
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

        static::assertCount(1, $result);
        static::assertSame($expectedUser[0]['email'], $result[0]['email']);
        static::assertSame($expectedUser[0]['firstname'], $result[0]['firstname']);
        static::assertSame($expectedUser[0]['lastname'], $result[0]['lastname']);
        static::assertSame($expectedUser[0]['number'], $result[0]['number']);
        static::assertSame($expectedUser[0]['company'], $result[0]['company']);
        static::assertSame($expectedUser[0]['zipCode'], $result[0]['zipCode']);
        static::assertSame($expectedUser[0]['city'], $result[0]['city']);
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

        $result1 = null;
        $result2 = null;

        foreach ($result as $user) {
            if ($user['email'] === 'test@example.com') {
                $result1 = $user;
            }

            if ($user['email'] === 'mustermann@b2b.de') {
                $result2 = $user;
            }
        }

        static::assertCount(2, $result);

        static::assertSame($expectedUsers[0]['email'], $result1['email']);
        static::assertSame($expectedUsers[0]['firstname'], $result1['firstname']);
        static::assertSame($expectedUsers[0]['lastname'], $result1['lastname']);
        static::assertSame($expectedUsers[0]['number'], $result1['number']);
        static::assertSame($expectedUsers[0]['company'], $result1['company']);
        static::assertSame($expectedUsers[0]['zipCode'], $result1['zipCode']);
        static::assertSame($expectedUsers[0]['city'], $result1['city']);

        static::assertSame($expectedUsers[1]['email'], $result2['email']);
        static::assertSame($expectedUsers[1]['firstname'], $result2['firstname']);
        static::assertSame($expectedUsers[1]['lastname'], $result2['lastname']);
        static::assertSame($expectedUsers[1]['number'], $result2['number']);
        static::assertSame($expectedUsers[1]['company'], $result2['company']);
        static::assertSame($expectedUsers[1]['zipCode'], $result2['zipCode']);
        static::assertSame($expectedUsers[1]['city'], $result2['city']);
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

        static::assertSame($expectedUser['email'], $result['email']);
        static::assertSame($expectedUser['firstname'], $result['firstname']);
        static::assertSame($expectedUser['lastname'], $result['lastname']);
        static::assertSame($expectedUser['number'], $result['number']);
    }

    public function test_get_one_customer_by_invalid_id()
    {
        $result = $this->getCustomerRepository()->get(999);
        static::assertNull($result['email']);
    }

    /**
     * @return CustomerRepository
     */
    private function getCustomerRepository()
    {
        return Shopware()->Container()->get('swag_backend_order.customer_repository');
    }
}
