<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\PriceCalculation;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;
use SwagBackendOrder\Components\PriceCalculation\Context\PriceContextFactory;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class PriceContextFactoryTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    /**
     * @var PriceContextFactory
     */
    private $priceContextFactory;

    protected function setUp(): void
    {
        $this->priceContextFactory = new PriceContextFactory($this->getModelManager());
    }

    public function testCreate(): void
    {
        $price = 59.99;
        $taxRate = 19.00;
        $currencyId = 2;
        $currency = $this->getModelManager()->find(Currency::class, $currencyId);
        static::assertInstanceOf(Currency::class, $currency);

        $priceContext = $this->priceContextFactory->create($price, $taxRate, false, false, $currencyId);

        static::assertSame($price, $priceContext->getPrice());
        static::assertSame($taxRate, $priceContext->getTaxRate());
        static::assertFalse($priceContext->isNetPrice());
        static::assertSame($currency->getFactor(), $priceContext->getCurrencyFactor());
    }

    public function testCreateWithInvalidCurrencyId(): void
    {
        $price = 59.99;
        $taxRate = 19.00;
        $invalidCurrencyId = 123;
        $defaultCurrencyFactor = 1.0;

        $priceContext = $this->priceContextFactory->create($price, $taxRate, false, false, $invalidCurrencyId);

        static::assertSame($defaultCurrencyFactor, $priceContext->getCurrencyFactor());
    }

    private function getModelManager(): ModelManager
    {
        return $this->getContainer()->get('models');
    }
}
