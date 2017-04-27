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
        $this->assertEquals(59.99, $result['positions'][0]->price);
        $this->assertEquals(59.99, $result['positions'][0]->total);
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

        $this->assertEquals(50.41, $result['positions'][0]->price);
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

        $this->assertEquals(50.41, $result['positions'][0]->price);
    }

    /**
     * @covers \Shopware_Controllers_Backend_SwagBackendOrder::calculateBasketAction()
     */
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

        $this->assertEquals(81.74, $result['positions'][0]->price);
        $this->assertEquals(245.22, $result['positions'][0]->total);
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
}

class SwagBackendOrderMock extends \Shopware_Controllers_Backend_SwagBackendOrder
{
    /**
     * @param \Enlight_Controller_Request_RequestTestCase   $request
     * @param Container                                     $container
     * @param \Enlight_View_Default                         $view
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
