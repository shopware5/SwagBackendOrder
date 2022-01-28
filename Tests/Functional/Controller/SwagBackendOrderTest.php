<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Controller;

require_once __DIR__ . '/../../../Controllers/Backend/SwagBackendOrder.php';

use Enlight_View_Default;
use PHPUnit\Framework\TestCase;
use Shopware\Components\DependencyInjection\Container;
use SwagBackendOrder\Components\PriceCalculation\DiscountType;
use SwagBackendOrder\Tests\Functional\B2bOrderTrait;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class SwagBackendOrderTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;
    use B2bOrderTrait;

    public function testCalculateBasket(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame(142.43, $result['totalWithoutTax']);
        static::assertSame(154.94, $result['sum']);
        static::assertSame(16.4, \round($result['taxSum'], 1));
        static::assertSame(59.99, $result['positions'][0]['price']);
        static::assertSame(59.99, $result['positions'][0]['total']);
    }

    public function testCalculateBasketWithEmptyDispatchId(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithEmptyDispatch());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame(3.9, $result['shippingCosts']);
        static::assertSame(3.9, $result['shippingCostsNet']);
    }

    public function testCalculateBasketWithInvalidDispatchId(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithInvalidDispatch());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can not find given dispatch with id 99999');
        $controller->calculateBasketAction();
    }

    public function testCalculateBasketWithEmptyBasketTaxRates(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithEmptyPositions());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');
        Shopware()->Front()->Router()->assemble();
        static::assertTrue($view->getAssign('success'));
        static::assertSame(3.9, $result['shippingCosts']);
        static::assertSame(3.9, $result['shippingCostsNet']);
    }

    public function testBasketCalculationWithChangedDisplayNetFlag(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame(50.41, \round($result['sum'], 2));

        static::assertSame(3.90, $result['shippingCosts']);
        static::assertSame(3.28, $result['shippingCostsNet']);

        static::assertSame(10.20, $result['taxSum']);

        static::assertSame(63.89, $result['total']);
        static::assertSame(53.69, $result['totalWithoutTax']);

        static::assertSame(50.41, \round($result['positions'][0]['price'], 2));
    }

    public function testBasketCalculationWithChangedTaxfreeFlag(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedTaxfreeFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame(50.41, \round($result['sum'], 2));

        static::assertSame(3.28, $result['shippingCosts']);
        static::assertSame(3.28, $result['shippingCostsNet']);

        static::assertSame(0.0, $result['taxSum']);

        static::assertSame(53.69, $result['total']);
        static::assertSame(53.69, $result['totalWithoutTax']);

        static::assertSame(50.41, \round($result['positions'][0]['price'], 2));
    }

    public function testBasketCalculationWithChangedCurrency(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithChangedCurrency());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame(271.08, $result['sum']);

        static::assertSame(5.31, $result['shippingCosts']);
        static::assertSame(4.47, $result['shippingCostsNet']);

        static::assertSame(41.68, $result['taxSum']);

        static::assertSame(276.39, $result['total']);
        static::assertSame(234.71, $result['totalWithoutTax']);

        static::assertSame(81.74, \round($result['positions'][0]['price'], 2));
        static::assertSame(245.21, \round($result['positions'][0]['total'], 2));
    }

    public function testGetProduct(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame('SW10002.1', $result['number']);
        static::assertSame(59.99, $result['price']);
    }

    public function testGetDiscountAbsolute(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 50.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame('Test_absolute', $result['articleName']);
        static::assertSame('DISCOUNT.1', $result['articleNumber']);
        static::assertSame(0, $result['articleId']);
        static::assertSame(-50.0, $result['price']);
        static::assertSame(4, $result['mode']);
        static::assertSame(1, $result['quantity']);
        static::assertSame(1, $result['inStock']);
        static::assertTrue($result['isDiscount']);
        static::assertSame(DiscountType::DISCOUNT_ABSOLUTE, $result['discountType']);
        static::assertSame(-50.0, $result['total']);
    }

    public function testGetDiscountPercentage(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_PERCENTAGE, 10.0, 'Test_percentage'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame('Test_percentage', $result['articleName']);
        static::assertSame('DISCOUNT.0', $result['articleNumber']);
        static::assertSame(0, $result['articleId']);
        static::assertSame(-10.0, $result['price']);
        static::assertSame(4, $result['mode']);
        static::assertSame(1, $result['quantity']);
        static::assertSame(1, $result['inStock']);
        static::assertTrue($result['isDiscount']);
        static::assertSame(DiscountType::DISCOUNT_PERCENTAGE, $result['discountType']);
        static::assertSame(-10.0, $result['total']);
    }

    public function testGetDiscountAbsoluteWillFailDueToInvalidAmount(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 999.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        static::assertFalse($view->getAssign('success'));
    }

    public function testGetProductWithBlockPrices(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPrices());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];

        $expectedBlockPricesArray = [
            1 => [
                'net' => 0.83999999999999997,
                'gross' => 1,
            ],
            11 => [
                'net' => 0.76000000000000001,
                'gross' => 0.90000000000000002,
            ],
            21 => [
                'net' => 0.67000000000000004,
                'gross' => 0.80000000000000004,
            ],
            31 => [
                'net' => 0.63,
                'gross' => 0.75,
            ],
            41 => [
                'net' => 0.58999999999999997,
                'gross' => 0.69999999999999996,
            ],
        ];

        foreach ($blockPrices as $index => $blockPrice) {
            static::assertSame(\round($blockPrice['net'], 2), \round($expectedBlockPricesArray[$index]['net'], 2));
            static::assertSame(\round($blockPrice['gross'], 2), \round($expectedBlockPricesArray[$index]['gross'], 2));
        }

        static::assertSame(0.9044, $result['price']);
    }

    public function testGetProductWithDisplayNet(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame('SW10002.1', $result['number']);
        static::assertSame(50.41, $result['price']);
    }

    public function testGetProductWithBlockPricesAndDisplayNet(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPricesAndDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];

        $expectedBlockPricesArray = [
            1 => [
                'net' => 0.83999999999999997,
                'gross' => 1,
            ],
            11 => [
                'net' => 0.76000000000000001,
                'gross' => 0.90000000000000002,
            ],
            21 => [
                'net' => 0.67000000000000004,
                'gross' => 0.80000000000000004,
            ],
            31 => [
                'net' => 0.63,
                'gross' => 0.75,
            ],
            41 => [
                'net' => 0.58999999999999997,
                'gross' => 0.69999999999999996,
            ],
        ];

        foreach ($blockPrices as $index => $blockPrice) {
            static::assertSame(\round($blockPrice['net'], 2), \round($expectedBlockPricesArray[$index]['net'], 2));
            static::assertSame(\round($blockPrice['gross'], 2), \round($expectedBlockPricesArray[$index]['gross'], 2));
        }

        static::assertSame(0.76, $result['price']);
    }

    public function testGetCustomerList(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $filter = [
            ['value' => 'test'],
        ];
        $request->setParam('filter', $filter);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getCustomerAction();

        $expectedUser = [
            'email' => 'test@example.com',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'number' => '20001',
            'company' => 'Muster GmbH',
            'zipCode' => '55555',
            'city' => 'Musterhausen',
        ];

        $result = $view->getAssign('data')[0];

        static::assertTrue($view->getAssign('success'));
        static::assertSame(1, $view->getAssign('total'));
        static::assertSame($expectedUser['email'], $result['email']);
        static::assertSame($expectedUser['firstname'], $result['firstname']);
        static::assertSame($expectedUser['lastname'], $result['lastname']);
        static::assertSame($expectedUser['number'], $result['number']);
        static::assertSame($expectedUser['company'], $result['company']);
        static::assertSame($expectedUser['zipCode'], $result['zipCode']);
        static::assertSame($expectedUser['city'], $result['city']);
    }

    public function testGetCustomerSingle(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParam('searchParam', 1);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getCustomerAction();

        $expectedUser = [
            'email' => 'test@example.com',
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'number' => '20001',
        ];

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertSame($expectedUser['email'], $result['email']);
        static::assertSame($expectedUser['firstname'], $result['firstname']);
        static::assertSame($expectedUser['lastname'], $result['lastname']);
        static::assertSame($expectedUser['number'], $result['number']);
    }

    public function testGetArticles(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductSearchData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(1, $result);
        static::assertSame('SW10239', $result[0]['number']);
    }

    public function testGetArticlesMultiple(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams([
            'query' => 'flip flop',
            'limit' => 50,
        ]);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(35, $result);
        static::assertSame('SW10153.1', $result[0]['number']);
    }

    public function testGetArticlesByOrdernumber(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/createProduct.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeUpdate($sql);

        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams([
            'query' => '_test',
        ]);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(1, $result);
        static::assertSame('SW_10002_test_123', $result[0]['number']);
    }

    public function testGetArticlesBySupplier(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/createProduct.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeUpdate($sql);

        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams([
            'query' => 'shopware',
        ]);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(1, $result);
        static::assertSame('SW_10002_test_123', $result[0]['number']);
    }

    public function testCreateOrderWithEAN(): void
    {
        if ($this->isB2bPluginInstalled() === false) {
            static::markTestSkipped('SwagB2bPlugin is not installed');
        }

        $requestHeaderData = require __DIR__ . '/_fixtures/HeaderData.php';
        $view = $this->getView();
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($requestHeaderData);

        $controller = $this->getControllerMock($request, $view);
        $controller->createOrderAction();

        $viewResult = $view->getAssign();
        static::assertTrue($viewResult['success']);
        static::assertNotEmpty($viewResult['orderId']);
        static::assertNotEmpty($viewResult['ordernumber']);

        $sql = 'SELECT ean FROM s_order_details WHERE orderID = ?';
        $result = $this->getContainer()->get('dbal_connection')->fetchColumn($sql, [$viewResult['orderId']]);
        static::assertSame('UnitTestEAN', $result);

        $b2bResult = $this->getB2bOrder($viewResult['ordernumber']);
        static::assertNull($b2bResult);
    }

    public function testCreateOrderWithB2bOrder(): void
    {
        if (!$this->isB2bPluginInstalled()) {
            static::markTestSkipped('SwagB2bPlugin is not installed');
        }

        $this->b2bUserIsDebitor();

        $requestHeaderData = require __DIR__ . '/_fixtures/HeaderData.php';
        $requestHeaderData['data']['customerId'] = 2;

        $view = $this->getView();
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($requestHeaderData);

        $controller = $this->getControllerMock($request, $view);
        $controller->createOrderAction();

        $viewResult = $view->getAssign();
        static::assertTrue($viewResult['success']);
        static::assertNotEmpty($viewResult['orderId']);
        static::assertNotEmpty($viewResult['ordernumber']);

        $sql = 'SELECT ean FROM s_order_details WHERE orderID = ?';
        $result = $this->getContainer()->get('dbal_connection')->fetchColumn($sql, [$viewResult['orderId']]);

        static::assertSame('UnitTestEAN', $result);

        $b2bResult = $this->getB2bOrder($viewResult['ordernumber']);
        static::assertNotNull($b2bResult);
        static::assertCount(1, $b2bResult);
        static::assertSame($viewResult['ordernumber'], $b2bResult[0]['ordernumber']);
    }

    private function getDemoData(): array
    {
        return [
            'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":1,"price":"59.99","total":"59.99"},{"id":0,"create_backend_order_id":0,"mode":0,"articleId":272,"detailId":0,"articleNumber":"SW10239","articleName":"Spachtelmasse","quantity":5,"statusId":0,"statusDescription":"","taxId":4,"taxRate":7,"taxDescription":"","inStock":-1,"price":"18.99","total":"94.95"}]',
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'false',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'dispatchId' => 9,
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getDemoDataWithEmptyDispatch(): array
    {
        return [
            'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":1,"price":"59.99","total":"59.99"},{"id":0,"create_backend_order_id":0,"mode":0,"articleId":272,"detailId":0,"articleNumber":"SW10239","articleName":"Spachtelmasse","quantity":5,"statusId":0,"statusDescription":"","taxId":4,"taxRate":7,"taxDescription":"","inStock":-1,"price":"18.99","total":"94.95"}]',
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'false',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getDemoDataWithInvalidDispatch(): array
    {
        return [
            'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":1,"price":"59.99","total":"59.99"},{"id":0,"create_backend_order_id":0,"mode":0,"articleId":272,"detailId":0,"articleNumber":"SW10239","articleName":"Spachtelmasse","quantity":5,"statusId":0,"statusDescription":"","taxId":4,"taxRate":7,"taxDescription":"","inStock":-1,"price":"18.99","total":"94.95"}]',
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'false',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'dispatchId' => 99999,
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getDemoDataWithEmptyPositions(): array
    {
        return [
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'false',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'dispatchId' => 9,
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getDemoDataWithChangedCurrency(): array
    {
        return [
                'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":3,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":-4,"price":"59.99","total":"179.97"},{"id":0,"create_backend_order_id":0,"mode":0,"articleId":272,"detailId":0,"articleNumber":"SW10239","articleName":"Spachtelmasse","quantity":1,"statusId":0,"statusDescription":"","taxId":4,"taxRate":7,"taxDescription":"","inStock":-1,"price":"18.99","total":"18.99"}]',
                'shippingCosts' => 3.90,
                'shippingCostsNet' => 3.28,
                'displayNet' => 'false',
                'oldCurrencyId' => '1',
                'newCurrencyId' => '2',
            ] + $this->getDemoData();
    }

    private function getDemoWithChangedDisplayNetFlag(): array
    {
        return [
            'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":-7,"price":"59.99","total":"59.99"}]',
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'true',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'dispatchId' => 9,
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getDemoWithChangedTaxfreeFlag(): array
    {
        return [
            'positions' => '[{"id":0,"create_backend_order_id":0,"mode":0,"articleId":2,"detailId":0,"articleNumber":"SW10002.1","articleName":"M\u00fcnsterl\u00e4nder Lagerkorn 32% 1,5 Liter","quantity":1,"statusId":0,"statusDescription":"","taxId":1,"taxRate":19,"taxDescription":"","inStock":-7,"price":"59.99","total":"59.99"}]',
            'shippingCosts' => 3.90,
            'shippingCostsNet' => 3.28,
            'displayNet' => 'true',
            'oldCurrencyId' => '',
            'newCurrencyId' => '',
            'dispatchId' => 9,
            'taxFree' => 'true',
            'previousDisplayNet' => 'false',
            'previousTaxFree' => 'false',
            'previousDispatchTaxRate' => '19',
        ];
    }

    private function getProductDemoData(): array
    {
        return [
            'ordernumber' => 'SW10002.1',
            'displayNet' => 'false',
            'newCurrencyId' => '1',
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxRate' => 'false',
            'customerId' => 1,
        ];
    }

    private function getProductDemoDataWithBlockPrices(): array
    {
        return [
            'ordernumber' => 'SW10208',
            'displayNet' => 'false',
            'newCurrencyId' => '1',
            'taxFree' => 'false',
            'previousDisplayNet' => 'false',
            'previousTaxRate' => 'false',
            'quantity' => 12,
        ];
    }

    private function getProductDemoDataWithDisplayNetFlag(): array
    {
        return [
            'ordernumber' => 'SW10002.1',
            'displayNet' => 'true',
            'newCurrencyId' => '1',
            'taxFree' => 'true',
            'previousDisplayNet' => 'false',
            'previousTaxRate' => 'false',
        ];
    }

    private function getProductDemoDataWithBlockPricesAndDisplayNetFlag(): array
    {
        return [
            'ordernumber' => 'SW10208',
            'displayNet' => 'true',
            'newCurrencyId' => '1',
            'taxFree' => 'true',
            'previousDisplayNet' => 'false',
            'previousTaxRate' => 'false',
            'quantity' => 12,
        ];
    }

    private function getView(): Enlight_View_Default
    {
        return new Enlight_View_Default(
            new \Enlight_Template_Manager()
        );
    }

    private function getControllerMock(\Enlight_Controller_Request_RequestTestCase $request, Enlight_View_Default $view): SwagBackendOrderMock
    {
        return new SwagBackendOrderMock(
            $request,
            $this->getContainer(),
            $view
        );
    }

    private function getProductSearchData(): array
    {
        return [
            'query' => 'spachtel',
        ];
    }

    private function getDiscountRequestData(int $type, float $value, string $name, float $currentTotal = 500.0): array
    {
        return [
            'type' => $type,
            'value' => $value,
            'name' => $name,
            'currentTotal' => $currentTotal,
        ];
    }
}

class SwagBackendOrderMock extends \Shopware_Controllers_Backend_SwagBackendOrder
{
    public function __construct(
        \Enlight_Controller_Request_RequestTestCase $request,
        Container $container,
        Enlight_View_Default $view
    ) {
        $this->request = $request;
        $this->response = new \Enlight_Controller_Response_ResponseTestCase();
        $this->container = $container;
        $this->view = $view;
    }
}
