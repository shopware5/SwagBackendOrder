<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\Controller;

require_once __DIR__ . '/../../../Controllers/Backend/SwagBackendOrder.php';

use Shopware\Components\DependencyInjection\Container;
use SwagBackendOrder\Components\PriceCalculation\DiscountType;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class SwagBackendOrderTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    public function testCalculateBasket()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(142.44, $result['totalWithoutTax']);
        $this->assertEquals(154.94, $result['sum']);
        $this->assertEquals(16.4, $result['taxSum']);
        $this->assertEquals(59.99, $result['positions'][0]['price']);
        $this->assertEquals(59.99, $result['positions'][0]['total']);
    }

    public function testCalculateBasket_with_empty_dispatchId()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithEmptyDispatch());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(3.9, $result['shippingCosts']);
        $this->assertEquals(3.9, $result['shippingCostsNet']);
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
        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(3.9, $result['shippingCosts']);
        $this->assertEquals(3.9, $result['shippingCostsNet']);
    }

    public function testBasketCalculationWithChangedDisplayNetFlag()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(50.41, $result['sum']);

        $this->assertEquals(3.90, $result['shippingCosts']);
        $this->assertEquals(3.28, $result['shippingCostsNet']);

        $this->assertEquals(10.20, $result['taxSum']);

        $this->assertEquals(63.89, $result['total']);
        $this->assertEquals(53.69, $result['totalWithoutTax']);

        $this->assertEquals(50.41, $result['positions'][0]['price']);
    }

    public function testBasketCalculationWithChangedTaxfreeFlag()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoWithChangedTaxfreeFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(50.41, $result['sum']);

        $this->assertEquals(3.28, $result['shippingCosts']);
        $this->assertEquals(3.28, $result['shippingCostsNet']);

        $this->assertEquals(0, $result['taxSum']);

        $this->assertEquals(53.69, $result['total']);
        $this->assertEquals(53.69, $result['totalWithoutTax']);

        $this->assertEquals(50.41, $result['positions'][0]['price']);
    }

    public function testBasketCalculationWithChangedCurrency()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDemoDataWithChangedCurrency());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->calculateBasketAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals(271.09, $result['sum']);

        $this->assertEquals(5.31, $result['shippingCosts']);
        $this->assertEquals(4.47, $result['shippingCostsNet']);

        $this->assertEquals(41.68, $result['taxSum']);

        $this->assertEquals(276.4, $result['total']);
        $this->assertEquals(234.72, $result['totalWithoutTax']);

        $this->assertEquals(81.74, $result['positions'][0]['price']);
        $this->assertEquals(245.22, $result['positions'][0]['total']);
    }

    public function testGetProduct()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals('SW10002.1', $result['number']);
        $this->assertEquals(59.99, $result['price']);
    }

    public function test_getDiscount_absolute()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 50.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals('Test_absolute', $result['articleName']);
        $this->assertEquals('DISCOUNT.1', $result['articleNumber']);
        $this->assertEquals(0, $result['articleId']);
        $this->assertEquals(-50.0, $result['price']);
        $this->assertEquals(4, $result['mode']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals(1, $result['inStock']);
        $this->assertTrue($result['isDiscount']);
        $this->assertEquals(DiscountType::DISCOUNT_ABSOLUTE, $result['discountType']);
        $this->assertEquals(-50.0, $result['total']);
    }

    public function test_getDiscount_percentage()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_PERCENTAGE, 10.0, 'Test_percentage'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals('Test_percentage', $result['articleName']);
        $this->assertEquals('DISCOUNT.0', $result['articleNumber']);
        $this->assertEquals(0, $result['articleId']);
        $this->assertEquals(-10.0, $result['price']);
        $this->assertEquals(4, $result['mode']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals(1, $result['inStock']);
        $this->assertTrue($result['isDiscount']);
        $this->assertEquals(DiscountType::DISCOUNT_PERCENTAGE, $result['discountType']);
        $this->assertEquals(-10.0, $result['total']);
    }

    public function test_getDiscount_absolute_will_fail_due_to_invalid_amount()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getDiscountRequestData(DiscountType::DISCOUNT_ABSOLUTE, 999.0, 'Test_absolute'));

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);
        $controller->getDiscountAction();

        $this->assertFalse($view->getAssign('success'));
    }

    public function test_getProduct_with_block_prices()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPrices());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];
        $expectedBlockPricesJSON = '{"1":{"net":0.84,"gross":1},"11":{"net":0.76,"gross":0.9},"21":{"net":0.67,"gross":0.8},"31":{"net":0.63,"gross":0.75},"41":{"net":0.59,"gross":0.7}}';
        $this->assertEquals($expectedBlockPricesJSON, $blockPrices);
        $this->assertEquals(0.9, $result['price']);
    }

    public function testGetProductWithDisplayNet()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertEquals('SW10002.1', $result['number']);
        $this->assertEquals(50.41, $result['price']);
    }

    public function test_getProduct_with_block_prices_and_display_net()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductDemoDataWithBlockPricesAndDisplayNetFlag());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getProductAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $blockPrices = $result['blockPrices'];
        $expectedBlockPricesJSON = '{"1":{"net":0.84,"gross":1},"11":{"net":0.76,"gross":0.9},"21":{"net":0.67,"gross":0.8},"31":{"net":0.63,"gross":0.75},"41":{"net":0.59,"gross":0.7}}';
        $this->assertEquals($expectedBlockPricesJSON, $blockPrices);
        $this->assertEquals(0.76, $result['price']);
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

        $this->assertTrue($view->getAssign('success'));
        $this->assertSame(1, $view->getAssign('total'));
        $this->assertArraySubset($expectedUser, $result);
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

        $this->assertTrue($view->getAssign('success'));
        $this->assertArraySubset($expectedUser, $result);
    }

    public function test_getArticles()
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($this->getProductSearchData());

        $view = $this->getView();

        $controller = $this->getControllerMock($request, $view);

        $controller->getArticlesAction();

        $result = $view->getAssign('data');

        $this->assertTrue($view->getAssign('success'));
        $this->assertCount(1, $result);
        $this->assertEquals('SW10239', $result[0]['number']);
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
            'searchParam' => 'spachtel',
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
    /**
     * @param \Enlight_Controller_Request_RequestTestCase $request
     * @param Container                                   $container
     * @param \Enlight_View_Default                       $view
     */
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
