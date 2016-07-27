<?php


namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContextFactory;

class PriceContextFactoryTest extends \Enlight_Components_Test_TestCase
{
    /**
     * @var PriceContextFactory
     */
    private $SUT;

    protected function setUp()
    {
        $this->SUT = new PriceContextFactory($this->getModelManager());
    }

    /**
     * @covers PriceContextFactory::create()
     */
    public function testCreate()
    {
        $price = 59.99;
        $taxRate = 19.00;
        $isNet = false;
        $currencyId = 2;
        $currency = $this->getModelManager()->find(Currency::class, $currencyId);

        $priceContext = $this->SUT->create($price, $taxRate, $isNet, $currencyId);

        $this->assertEquals($price, $priceContext->getPrice());
        $this->assertEquals($taxRate, $priceContext->getTaxRate());
        $this->assertEquals($isNet, $priceContext->isNetPrice());
        $this->assertEquals($currency->getFactor(), $priceContext->getCurrencyFactor());
    }

    /**
     * @covers PriceContextFactory::create()
     * @expectedException \Exception
     */
    public function testCreateWithInvalidNumbers()
    {
        $price = 'invalid price';
        $taxRate = 'invalid tax rate';
        $isNet = false;
        $currencyId = 2;

        $this->SUT->create($price, $taxRate, $isNet, $currencyId);
    }

    /**
     * @covers PriceContextFactory::create()
     */
    public function testCreateWithInvalidCurrencyId()
    {
        $price = 59.99;
        $taxRate = 19.00;
        $isNet = false;
        $invalidCurrencyId = 123;
        $defaultCurrencyFactor = 1;

        $priceContext = $this->SUT->create($price, $taxRate, $isNet, $invalidCurrencyId);

        $this->assertEquals($defaultCurrencyFactor, $priceContext->getCurrencyFactor());
    }

    /**
     * @return ModelManager
     */
    private function getModelManager()
    {
        return Shopware()->Container()->get('models');
    }
}