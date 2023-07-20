<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\PriceCalculation\Hydrator;

use PHPUnit\Framework\TestCase;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ShopContextFactoryInterface;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\PositionHydrator;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class RequestHydratorTest extends TestCase
{
    use ContainerTrait;

    public const ADDRESS_ID_AUSTRIA = 1;

    public const ADDRESS_ID_GERMANY = 3;

    public const TAX_AUSTRIA = 20.0;

    public const TAX_GERMANY = 19.0;

    public const BASIC_REQUEST_DATA = [
        'module' => 'backend',
        'controller' => 'SwagBackendOrder',
        'action' => 'calculateBasket',
        'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":0,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\\u00fcnsterl\\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":20,"taxDescription":"","inStock":15,"isDiscount":false,"discountType":0,"ean":"","price":"59.99","total":"59.99"}]',
        'shippingCosts' => '3.9',
        'shippingCostsNet' => '3.25',
        'displayNet' => 'false',
        'oldCurrencyId' => '',
        'dispatchId' => '9',
        'taxFree' => 'false',
        'previousDisplayNet' => 'false',
        'previousTaxFree' => 'false',
        'previousDispatchTaxRate' => '20',
    ];

    public function setUp(): void
    {
        $sql = file_get_contents(__DIR__ . '/_fixtures/tax-rule-and-country.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeQuery($sql);
        $this->getContainer()->get('models')->clear();
    }

    public function tearDown(): void
    {
        $sql = file_get_contents(__DIR__ . '/_fixtures/tear-down.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeQuery($sql);
    }

    /**
     * @dataProvider hydrateFromRequestShouldUseShippingAddressIdTestDataProvider
     *
     * @param array<string,mixed> $requestData
     */
    public function testHydrateFromRequestShouldUseShippingAddressId(array $requestData, float $expectedResult): void
    {
        $result = $this->createRequestHydrator()->hydrateFromRequest($requestData)->getBasketTaxRates();

        static::assertSame($expectedResult, array_shift($result));
    }

    /**
     * @return \Generator<array<int,mixed>>
     */
    public function hydrateFromRequestShouldUseShippingAddressIdTestDataProvider(): \Generator
    {
        yield 'Shipping address is located in Germany' => [
            array_merge(self::BASIC_REQUEST_DATA, [
                'billingAddressId' => self::ADDRESS_ID_AUSTRIA,
                'shippingAddressId' => self::ADDRESS_ID_GERMANY,
            ]),
            self::TAX_GERMANY,
        ];

        yield 'Shipping address is located in Austria' => [
            array_merge(self::BASIC_REQUEST_DATA, [
                'billingAddressId' => self::ADDRESS_ID_GERMANY,
                'shippingAddressId' => self::ADDRESS_ID_AUSTRIA,
            ]),
            self::TAX_AUSTRIA,
        ];
    }

    private function createRequestHydrator(): RequestHydrator
    {
        return new RequestHydrator(
            new PositionHydrator(
                $this->getContainer()->get('models'),
                $this->getContainer()->get(ShopContextFactoryInterface::class)
            )
        );
    }
}
