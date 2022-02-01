<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\ProductSearch;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ShopContextFactoryInterface;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\PositionHydrator;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\ProductSearch\ProductSearch;
use SwagBackendOrder\Tests\Functional\ContainerTrait;
use SwagBackendOrder\Tests\Functional\DatabaseTestCaseTrait;

class ProductSearchTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    public function testPrepareProductPriceNormalSelectedPriceByCustomerGroup(): void
    {
        $method = (new ReflectionClass(ProductSearch::class))->getMethod('prepareProductPrice');
        $method->setAccessible(true);

        $product = [
            'price' => '16.764705882353',
            'to' => 'beliebig',
            'defaultPrice' => '20.0',
            'defaultPriceTo' => 'beliebig',
        ];

        $result = $method->invokeArgs($this->getService(), [$product]);

        $expectedProduct = [
            'price' => '16.764705882353',
            'to' => 'beliebig',
        ];

        static::assertSame($expectedProduct['price'], $result['price']);
        static::assertSame($expectedProduct['to'], $result['to']);
    }

    public function testPrepareProductPriceWithPriceFromDefaultCustomerGroup(): void
    {
        $method = (new ReflectionClass(ProductSearch::class))->getMethod('prepareProductPrice');
        $method->setAccessible(true);

        $product = [
            'price' => null,
            'to' => null,
            'defaultPrice' => '20.0',
            'defaultPriceTo' => 'beliebig',
        ];

        $result = $method->invokeArgs($this->getService(), [$product]);

        $expectedProduct = [
            'price' => '20.0',
            'to' => 'beliebig',
        ];

        static::assertSame($expectedProduct['price'], $result['price']);
        static::assertSame($expectedProduct['to'], $result['to']);
        static::assertTrue($result['isFallbackPrice']);
    }

    private function getService(): ProductSearch
    {
        return new ProductSearch(
            $this->getContainer()->get('dbal_connection'),
            new ProductPriceCalculator(
                new TaxCalculation(),
                new CurrencyConverter()
            ),
            $this->getContainer()->get('shopware_storefront.context_service'),
            new RequestHydrator(
                new PositionHydrator(
                    $this->getContainer()->get('models'),
                    $this->getContainer()->get(ShopContextFactoryInterface::class)
                )
            ),
            $this->getContainer()->get('shopware_storefront.additional_text_service')
        );
    }
}
