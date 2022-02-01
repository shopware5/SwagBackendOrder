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
use SwagBackendOrder\Components\Translation\PaymentTranslator;

class PaymentTranslatorTest extends TestCase
{
    public const LANGUAGE_ID_ENGLISH = 2;
    public const PAYMENT_ID = 1;

    public const PAYMENT_DESCRIPTION_GERMAN = 'Vorkasse';
    public const PAYMENT_DESCRIPTION_ENGLISH = 'Payment in advance';
    public const PAYMENT_ADDITIONAL_DESCRIPTION_GERMAN = 'Payment beschreibung';
    public const PAYMENT_ADDITIONAL_DESCRIPTION_ENGLISH = 'Payment description';

    public function testItCanBeCreated(): void
    {
        $paymentTranslator = new PaymentTranslator($this->createMock(\Shopware_Components_Translation::class));

        static::assertInstanceOf(PaymentTranslator::class, $paymentTranslator);
    }

    public function testItShouldTranslatePaymentDescription(): void
    {
        $translationComponentMock = $this->createMock(\Shopware_Components_Translation::class);
        $translationComponentMock
            ->expects(static::once())
            ->method('read')
            ->with(self::LANGUAGE_ID_ENGLISH, 'config_payment')
            ->willReturn([
                self::PAYMENT_ID => ['description' => self::PAYMENT_DESCRIPTION_ENGLISH],
            ]);

        $paymentMethod = [
            'id' => 1,
            'description' => self::PAYMENT_DESCRIPTION_GERMAN,
        ];

        $translatedPaymentMethod = (new PaymentTranslator($translationComponentMock))->translate($paymentMethod, self::LANGUAGE_ID_ENGLISH);

        static::assertSame(self::PAYMENT_DESCRIPTION_ENGLISH, $translatedPaymentMethod['description']);
        static::assertSame(self::PAYMENT_DESCRIPTION_ENGLISH, $translatedPaymentMethod['description']);
    }

    public function testItShouldTranslatePaymentAdditionalDescription(): void
    {
        $translationComponentMock = $this->createMock(\Shopware_Components_Translation::class);
        $translationComponentMock
            ->expects(static::once())
            ->method('read')
            ->willReturn([
                self::PAYMENT_ID => ['additionalDescription' => self::PAYMENT_ADDITIONAL_DESCRIPTION_ENGLISH],
            ]);

        $paymentMethod = [
            'id' => 1,
            'additionalDescription' => self::PAYMENT_DESCRIPTION_GERMAN,
        ];

        $translatedPaymentMethod = (new PaymentTranslator($translationComponentMock))->translate($paymentMethod, self::LANGUAGE_ID_ENGLISH);

        static::assertSame(self::PAYMENT_ADDITIONAL_DESCRIPTION_ENGLISH, $translatedPaymentMethod['additionalDescription']);
        static::assertSame(self::PAYMENT_ADDITIONAL_DESCRIPTION_ENGLISH, $translatedPaymentMethod['additionaldescription']);
    }

    public function testItShouldGivenDescriptionIfNoTranslationIsAvailable(): void
    {
        $translationComponentMock = $this->createMock(\Shopware_Components_Translation::class);
        $translationComponentMock
            ->expects(static::once())
            ->method('read')
            ->willReturn([
                self::PAYMENT_ID => ['description' => null],
            ]);

        $paymentMethod = [
            'id' => 1,
            'description' => self::PAYMENT_DESCRIPTION_GERMAN,
        ];

        $translatedPaymentMethod = (new PaymentTranslator($translationComponentMock))->translate($paymentMethod, self::LANGUAGE_ID_ENGLISH);

        static::assertSame(self::PAYMENT_DESCRIPTION_GERMAN, $translatedPaymentMethod['description']);
    }
}
