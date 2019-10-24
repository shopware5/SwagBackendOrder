<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Functional\PriceCalculation;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContextFactory;
use SwagBackendOrder\Tests\DatabaseTestCaseTrait;

class PriceContextFactoryTest extends TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var PriceContextFactory
     */
    private $SUT;

    protected function setUp(): void
    {
        $this->SUT = new PriceContextFactory($this->getModelManager());
    }

    public function testCreate()
    {
        $price = 59.99;
        $taxRate = 19.00;
        $isNet = false;
        $taxFree = false;
        $currencyId = 2;
        $currency = $this->getModelManager()->find(Currency::class, $currencyId);

        $priceContext = $this->SUT->create($price, $taxRate, $isNet, $taxFree, $currencyId);

        static::assertEquals($price, $priceContext->getPrice());
        static::assertEquals($taxRate, $priceContext->getTaxRate());
        static::assertEquals($isNet, $priceContext->isNetPrice());
        static::assertEquals($currency->getFactor(), $priceContext->getCurrencyFactor());
    }

    public function testCreateWithInvalidNumbers()
    {
        $price = 'invalid price';
        $taxRate = 'invalid tax rate';
        $isNet = false;
        $taxFree = false;
        $currencyId = 2;

        $this->expectException(\Exception::class);
        $this->SUT->create($price, $taxRate, $isNet, $taxFree, $currencyId);
    }

    public function testCreateWithInvalidCurrencyId()
    {
        $price = 59.99;
        $taxRate = 19.00;
        $isNet = false;
        $taxFree = false;
        $invalidCurrencyId = 123;
        $defaultCurrencyFactor = 1;

        $priceContext = $this->SUT->create($price, $taxRate, $isNet, $taxFree, $invalidCurrencyId);

        static::assertEquals($defaultCurrencyFactor, $priceContext->getCurrencyFactor());
    }

    /**
     * @return ModelManager
     */
    private function getModelManager()
    {
        return Shopware()->Container()->get('models');
    }
}
