<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Functional\Components\Translation;

use PHPUnit\Framework\TestCase;
use Shopware_Components_Translation as ShopwareTranslationService;
use SwagBackendOrder\Components\Translation\ShippingTranslator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class ShippingTranslatorTest extends TestCase
{
    use ContainerTrait;

    public const SHIPPING_METHOD_ARRAY = [
        'id' => 1,
        'dispatch_name' => 'Lorem ipsum',
        'description' => 'At vero eos et accusam et justo duo dolores et ea rebum.',
    ];

    public const GERMAN_LANGUAGE_ID = 1;

    public function testTranslateIsNotOverwrittenWithoutTranslation(): void
    {
        $result = $this->createShippingTranslator()->translate(self::SHIPPING_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame(self::SHIPPING_METHOD_ARRAY['dispatch_name'], $result['dispatch_name']);
        static::assertSame(self::SHIPPING_METHOD_ARRAY['description'], $result['description']);
        static::assertArrayNotHasKey('name', $result);
    }

    public function testTranslateOverwriteOnlyDispatchName(): void
    {
        $translationService = $this->createMock(ShopwareTranslationService::class);

        $expectedResult = [self::SHIPPING_METHOD_ARRAY['id'] => ['dispatch_name' => 'newDispatchName']];
        $translationService->method('read')->willReturn($expectedResult);

        $result = $this->createShippingTranslator($translationService)->translate(self::SHIPPING_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame($expectedResult[self::SHIPPING_METHOD_ARRAY['id']]['dispatch_name'], $result['name']);
        static::assertSame($expectedResult[self::SHIPPING_METHOD_ARRAY['id']]['dispatch_name'], $result['dispatch_name']);
        static::assertSame(self::SHIPPING_METHOD_ARRAY['description'], $result['description']);
    }

    public function testTranslate(): void
    {
        $translationService = $this->createMock(ShopwareTranslationService::class);

        $expectedResult = [self::SHIPPING_METHOD_ARRAY['id'] => ['dispatch_name' => 'newDispatchName', 'description' => 'newDescription']];
        $translationService->method('read')->willReturn($expectedResult);

        $result = $this->createShippingTranslator($translationService)->translate(self::SHIPPING_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame($expectedResult[self::SHIPPING_METHOD_ARRAY['id']]['dispatch_name'], $result['name']);
        static::assertSame($expectedResult[self::SHIPPING_METHOD_ARRAY['id']]['dispatch_name'], $result['dispatch_name']);
        static::assertSame($expectedResult[self::SHIPPING_METHOD_ARRAY['id']]['description'], $result['description']);
    }

    private function createShippingTranslator(
        ?ShopwareTranslationService $translationService = null
    ): ShippingTranslator {
        if ($translationService !== null) {
            return new ShippingTranslator($translationService);
        }

        return new ShippingTranslator($this->getContainer()->get('translation'));
    }
}
