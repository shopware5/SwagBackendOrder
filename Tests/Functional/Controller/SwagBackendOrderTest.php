<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Controller;

require_once __DIR__ . '/../../../Controllers/Backend/SwagBackendOrder.php';

use PHPUnit\Framework\TestCase;
use Shopware\Components\DependencyInjection\Container;
use SwagBackendOrder\Components\PriceCalculation\DiscountType;
use SwagBackendOrder\Tests\B2bOrderTrait;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class SwagBackendOrderTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use B2bOrderTrait;

    public function testCalculateBasket()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals(142.43, $result['totalWithoutTax']);
        static::assertEquals(154.94, $result['sum']);
        static::assertEquals(16.4, round($result['taxSum'], 1));
        static::assertEquals(59.99, $result['positions'][0]['price']);
        static::assertEquals(59.99, $result['positions'][0]['total']);
    }

    public function testCalculateBasket_with_empty_dispatchId()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithEmptyDispatch());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals(3.9, $result['shippingCosts']);
        static::assertEquals(3.9, $result['shippingCostsNet']);
    }

    public function testCalculateBasket_with_invalid_dispatchId()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithInvalidDispatch());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can not find given dispatch with id 99999');
        $controller->calculateBasketAction();
    }

    public function testCalculateBasket_with_empty_basketTaxRates()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithEmptyPositions());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');
        Shopware()->Front()->Router()->assemble();
        static::assertTrue($view->getAssign('success'));
        static::assertEquals(3.9, $result['shippingCosts']);
        static::assertEquals(3.9, $result['shippingCostsNet']);
    }

    public function testBasketCalculationWithChangedDisplayNetFlag()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals(50.41, round($result['sum'], 2));

        static::assertEquals(3.90, $result['shippingCosts']);
        static::assertEquals(3.28, $result['shippingCostsNet']);

        static::assertEquals(10.20, $result['taxSum']);

        static::assertEquals(63.89, $result['total']);
        static::assertEquals(53.69, $result['totalWithoutTax']);

        static::assertEquals(50.41, round($result['positions'][0]['price'], 2));
    }

    public function testBasketCalculationWithChangedTaxfreeFlag()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedTaxfreeFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals(50.41, round($result['sum'], 2));

        static::assertEquals(3.28, $result['shippingCosts']);
        static::assertEquals(3.28, $result['shippingCostsNet']);

        static::assertEquals(0, $result['taxSum']);

        static::assertEquals(53.69, $result['total']);
        static::assertEquals(53.69, $result['totalWithoutTax']);

        static::assertEquals(50.41, round($result['positions'][0]['price'], 2));
    }

    public function testBasketCalculationWithChangedCurrency()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithChangedCurrency());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals(271.08, $result['sum']);

        static::assertEquals(5.31, $result['shippingCosts']);
        static::assertEquals(4.47, $result['shippingCostsNet']);

        static::assertEquals(41.68, $result['taxSum']);

        static::assertEquals(276.39, $result['total']);
        static::assertEquals(234.71, $result['totalWithoutTax']);

        static::assertEquals(81.74, round($result['positions'][0]['price'], 2));
        static::assertEquals(245.21, round($result['positions'][0]['total'], 2));
    }

    public function testGetProduct()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals('SW10002.1', $result['number']);
        static::assertEquals(59.99, $result['price']);
    }

    public function test_getDiscount_absolute()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 50.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals('Test_absolute', $result['articleName']);
        static::assertEquals('DISCOUNT.1', $result['articleNumber']);
        static::assertEquals(0, $result['articleId']);
        static::assertEquals(-50.0, $result['price']);
        static::assertEquals(4, $result['mode']);
        static::assertEquals(1, $result['quantity']);
        static::assertEquals(1, $result['inStock']);
        static::assertTrue($result['isDiscount']);
        static::assertEquals(DiscountType::DISCOUNT_ABSOLUTE, $result['discountType']);
        static::assertEquals(-50.0, $result['total']);
    }

    public function test_getDiscount_percentage()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_PERCENTAGE, 10.0, 'Test_percentage'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals('Test_percentage', $result['articleName']);
        static::assertEquals('DISCOUNT.0', $result['articleNumber']);
        static::assertEquals(0, $result['articleId']);
        static::assertEquals(-10.0, $result['price']);
        static::assertEquals(4, $result['mode']);
        static::assertEquals(1, $result['quantity']);
        static::assertEquals(1, $result['inStock']);
        static::assertTrue($result['isDiscount']);
        static::assertEquals(DiscountType::DISCOUNT_PERCENTAGE, $result['discountType']);
        static::assertEquals(-10.0, $result['total']);
    }

    public function test_getDiscount_absolute_will_fail_due_to_invalid_amount()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 999.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        static::assertFalse($view->getAssign('success'));
    }

    public function test_getProduct_with_block_prices()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPrices());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];
        $expectedBlockPricesJSON = '{"1":{"net":0.84,"gross":1},"11":{"net":0.76,"gross":0.9},"21":{"net":0.67,"gross":0.8},"31":{"net":0.63,"gross":0.75},"41":{"net":0.59,"gross":0.7}}';
        static::assertEquals($expectedBlockPricesJSON, $blockPrices);
        static::assertEquals(0.9044, $result['price']);
    }

    public function testGetProductWithDisplayNet()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertEquals('SW10002.1', $result['number']);
        static::assertEquals(50.41, $result['price']);
    }

    public function test_getProduct_with_block_prices_and_display_net()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPricesAndDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];
        $expectedBlockPricesJSON = '{"1":{"net":0.84,"gross":1},"11":{"net":0.76,"gross":0.9},"21":{"net":0.67,"gross":0.8},"31":{"net":0.63,"gross":0.75},"41":{"net":0.59,"gross":0.7}}';
        static::assertEquals($expectedBlockPricesJSON, $blockPrices);
        static::assertEquals(0.76, $result['price']);
    }

    public function test_getCustomer_list()
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

        $result = $view->getAssign('data');

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

    public function test_getCustomer_single()
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

    public function test_getArticles()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductSearchData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(1, $result);
        static::assertEquals('SW10239', $result[0]['number']);
    }

    public function test_getArticles_multiple()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams([
            'query' => 'flip flop',
        ]);

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        static::assertTrue($view->getAssign('success'));
        static::assertCount(35, $result);
        static::assertEquals('SW10153.1', $result[0]['number']);
    }

    public function test_getArticles_by_ordernumber()
    {
        $sql = file_get_contents(__DIR__ . '/_fixtures/createProduct.sql');
        Shopware()->Container()->get('dbal_connection')->exec($sql);

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
        static::assertEquals('SW_10002_test_123', $result[0]['number']);
    }

    public function test_getArticles_by_supplier()
    {
        $sql = file_get_contents(__DIR__ . '/_fixtures/createProduct.sql');
        Shopware()->Container()->get('dbal_connection')->exec($sql);

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
        static::assertEquals('SW_10002_test_123', $result[0]['number']);
    }

    public function test_createOrder_withEAN()
    {
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
        $result = Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [$viewResult['orderId']]);
        static::assertSame('UnitTestEAN', $result);

        $b2bResult = $this->getB2bOrder($viewResult['ordernumber']);
        static::assertNull($b2bResult);
    }

    public function test_createOrder_withB2bOrder()
    {
        $isB2bPluginInstalled = $this->isB2bPluginInstalled();

        if (!$isB2bPluginInstalled) {
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
        $result = Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [$viewResult['orderId']]);

        static::assertSame('UnitTestEAN', $result);

        $b2bResult = $this->getB2bOrder($viewResult['ordernumber']);
        static::assertNotNull($b2bResult);
        static::assertCount(1, $b2bResult);
        static::assertSame($viewResult['ordernumber'], $b2bResult[0]['ordernumber']);
    }

    /**
     * @return array
     */
    private function getDemoData()
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

    /**
     * @return array
     */
    private function getDemoDataWithEmptyDispatch()
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

    /**
     * @return array
     */
    private function getDemoDataWithInvalidDispatch()
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

    /**
     * @return array
     */
    private function getDemoDataWithEmptyPositions()
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

    /**
     * @return array
     */
    private function getDemoDataWithChangedCurrency()
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

    /**
     * @return array
     */
    private function getDemoWithChangedDisplayNetFlag()
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

    /**
     * @return array
     */
    private function getDemoWithChangedTaxfreeFlag()
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

    /**
     * @return array
     */
    private function getProductDemoData()
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

    /**
     * @return array
     */
    private function getProductDemoDataWithBlockPrices()
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

    /**
     * @return array
     */
    private function getProductDemoDataWithDisplayNetFlag()
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

    /**
     * @return array
     */
    private function getProductDemoDataWithBlockPricesAndDisplayNetFlag()
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

    /**
     * @return \Enlight_View_Default
     */
    private function getView()
    {
        return new \Enlight_View_Default(
            new \Enlight_Template_Manager()
        );
    }

    /**
     * @param $request
     * @param $view
     *
     * @return SwagBackendOrderMock
     */
    private function getControllerMock($request, $view)
    {
        return new SwagBackendOrderMock(
            $request,
            Shopware()->Container(),
            $view
        );
    }

    /**
     * @return array
     */
    private function getProductSearchData()
    {
        return [
            'query' => 'spachtel',
        ];
    }

    /**
     * @param string $type
     * @param float  $value
     * @param string $name
     * @param float  $currentTotal
     *
     * @return array
     */
    private function getDiscountRequestData($type, $value, $name, $currentTotal = 500.0)
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
        \Enlight_View_Default $view
    ) {
        $this->request = $request;
        $this->response = new \Enlight_Controller_Response_ResponseTestCase();
        $this->container = $container;
        $this->view = $view;
    }
}
