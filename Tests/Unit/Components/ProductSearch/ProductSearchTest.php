<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit\Components\ProductSearch;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SwagBackendOrder\Components\PriceCalculation\Calculator\ProductPriceCalculator;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\PositionHydrator;
use SwagBackendOrder\Components\PriceCalculation\Hydrator\RequestHydrator;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;
use SwagBackendOrder\Components\ProductSearch\ProductSearch;
use SwagBackendOrder\Tests\KernelTestCaseTrait;

class ProductSearchTest extends TestCase
{
    use KernelTestCaseTrait;

    public function test_prepareProductPrice_normal_selected_price_by_customerGroup()
    {
        $reflectionClass = new ReflectionClass(ProductSearch::class);
        $method = $reflectionClass->getMethod('prepareProductPrice');
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

    public function test_prepareProductPrice_with_price_from_default_customerGroup()
    {
        $reflectionClass = new ReflectionClass(ProductSearch::class);
        $method = $reflectionClass->getMethod('prepareProductPrice');
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
            'isFallbackPrice' => true,
        ];

        static::assertSame($expectedProduct['price'], $result['price']);
        static::assertSame($expectedProduct['to'], $result['to']);
        static::assertSame($expectedProduct['isFallbackPrice'], $result['isFallbackPrice']);
    }

    private function getService()
    {
        return new ProductSearch(
            Shopware()->Container()->get('dbal_connection'),
            new ProductPriceCalculator(
                new TaxCalculation(),
                new CurrencyConverter()
            ),
            Shopware()->Container()->get('shopware_storefront.context_service'),
            new RequestHydrator(
                new PositionHydrator()
            ),
            Shopware()->Container()->get('shopware_storefront.additional_text_service')
        );
    }
}
