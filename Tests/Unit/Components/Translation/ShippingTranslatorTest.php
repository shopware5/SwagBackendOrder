<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Unit\Components\Translation;

use PHPUnit\Framework\TestCase;
use SwagBackendOrder\Components\Translation\ShippingTranslator;

class ShippingTranslatorTest extends TestCase
{
    public const LANGUAGE_ID_ENGLISH = 2;
    public const DISPATCH_ID = 1;
    public const DISPATCH_NAME_GERMAN = 'Versandart';
    public const DISPATCH_NAME_ENGLISH = 'Shipping type';
    public const DISPATCH_DESCRIPTION_GERMAN = 'Beschreibung der Versandart';
    public const DISPATCH_DESCRIPTION_ENGLISH = 'Description of the shipping type';

    public function testItCanBeCreated(): void
    {
        $shippingTranslator = new ShippingTranslator(
            $this->createMock(\Shopware_Components_Translation::class)
        );

        static::assertInstanceOf(ShippingTranslator::class, $shippingTranslator);
    }

    public function testItShouldTranslateDispatch(): void
    {
        $translationComponentMock = $this->createMock(\Shopware_Components_Translation::class);
        $translationComponentMock
            ->expects(static::once())
            ->method('read')
            ->willReturn([
                self::DISPATCH_ID => [
                    'dispatch_name' => self::DISPATCH_NAME_ENGLISH,
                    'description' => self::DISPATCH_DESCRIPTION_ENGLISH,
                ],
            ]);

        $dispatch = [
            'id' => 1,
            'name' => self::DISPATCH_NAME_GERMAN,
            'description' => self::DISPATCH_DESCRIPTION_GERMAN,
            'dispatch_name' => self::DISPATCH_NAME_GERMAN,
        ];

        $translatedDispatch = (new ShippingTranslator($translationComponentMock))->translate($dispatch, self::LANGUAGE_ID_ENGLISH);

        static::assertSame(self::DISPATCH_NAME_ENGLISH, $translatedDispatch['name']);
        static::assertSame(self::DISPATCH_NAME_ENGLISH, $translatedDispatch['dispatch_name']);
        static::assertSame(self::DISPATCH_DESCRIPTION_ENGLISH, $translatedDispatch['description']);
    }
}
