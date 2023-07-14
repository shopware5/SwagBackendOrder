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
use SwagBackendOrder\Components\Translation\PaymentTranslator;
use SwagBackendOrder\Tests\Functional\ContainerTrait;

class PaymentTranslatorTest extends TestCase
{
    use ContainerTrait;

    public const PAYMENT_METHOD_ARRAY = [
        'id' => 1,
        'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr',
        'additionalDescription' => 'At vero eos et accusam et justo duo dolores et ea rebum.',
    ];

    public const GERMAN_LANGUAGE_ID = 1;

    public function testTranslateIsNotOverwrittenWithoutTranslation(): void
    {
        $result = $this->createPaymentTranslator()->translate(self::PAYMENT_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame(self::PAYMENT_METHOD_ARRAY['description'], $result['description']);
        static::assertSame(self::PAYMENT_METHOD_ARRAY['additionalDescription'], $result['additionalDescription']);
        static::assertArrayNotHasKey('additionaldescription', $result);
    }

    public function testTranslateOverwriteOnlyDescription(): void
    {
        $translationService = $this->createMock(ShopwareTranslationService::class);

        $expectedResult = [self::PAYMENT_METHOD_ARRAY['id'] => ['description' => 'newDescription']];
        $translationService->method('read')->willReturn($expectedResult);

        $result = $this->createPaymentTranslator($translationService)->translate(self::PAYMENT_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame($expectedResult[self::PAYMENT_METHOD_ARRAY['id']]['description'], $result['description']);
        static::assertSame(self::PAYMENT_METHOD_ARRAY['additionalDescription'], $result['additionalDescription']);
        static::assertArrayNotHasKey('additionaldescription', $result);
    }

    public function testTranslate(): void
    {
        $translationService = $this->createMock(ShopwareTranslationService::class);

        $expectedResult = [self::PAYMENT_METHOD_ARRAY['id'] => ['description' => 'newDescription', 'additionalDescription' => 'NewAdditionalDescription']];
        $translationService->method('read')->willReturn($expectedResult);

        $result = $this->createPaymentTranslator($translationService)->translate(self::PAYMENT_METHOD_ARRAY, self::GERMAN_LANGUAGE_ID);

        static::assertSame($expectedResult[self::PAYMENT_METHOD_ARRAY['id']]['description'], $result['description']);
        static::assertSame($expectedResult[self::PAYMENT_METHOD_ARRAY['id']]['additionalDescription'], $result['additionalDescription']);
        static::assertSame($expectedResult[self::PAYMENT_METHOD_ARRAY['id']]['additionalDescription'], $result['additionaldescription']);
    }

    private function createPaymentTranslator(
        ?ShopwareTranslationService $translationService = null
    ): PaymentTranslator {
        if ($translationService !== null) {
            return new PaymentTranslator($translationService);
        }

        return new PaymentTranslator($this->getContainer()->get('translation'));
    }
}
