<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Tests\Unit\Components\ConfirmationMail;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;

class NumberFormatterTest extends TestCase
{
    public const LOCALE_GERMANY = 'de_DE';
    public const LOCALE_GREAT_BRITAIN = 'en_EN';
    public const LOCALE_ITALIA = 'it_IT';
    public const EMPTY_LOCALE = '';

    public function testItCanBeCreated(): void
    {
        $numberFormatterWrapper = new NumberFormatterWrapper();

        static::assertInstanceOf(NumberFormatterWrapper::class, $numberFormatterWrapper);
    }

    public function testItShouldFormatNumberForLocaleDe(): void
    {
        $number = 1.988888;

        $formattedNumber = (new NumberFormatterWrapper())->format($number, self::LOCALE_GERMANY);

        static::assertSame('1,99', $formattedNumber);
    }

    public function testItShouldAdd2DecimalDigits(): void
    {
        $number = 2;

        $formattedNumber = (new NumberFormatterWrapper())->format($number, self::LOCALE_GERMANY);

        static::assertSame('2,00', $formattedNumber);
    }

    public function testItShouldFormatEnglishNumbers(): void
    {
        $number = 2;

        $formattedNumber = (new NumberFormatterWrapper())->format($number, self::LOCALE_GREAT_BRITAIN);

        static::assertSame('2.00', $formattedNumber);
    }

    public function testItShouldThrowExceptionIfLocaleIsEmpty(): void
    {
        $number = 1;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $this->expectException(RuntimeException::class);
        $numberFormatterWrapper->format($number, self::EMPTY_LOCALE);
    }

    public function testItShouldUseEnglishNotationAsDefault(): void
    {
        $number = 2;

        $formattedNumber = (new NumberFormatterWrapper())->format($number, self::LOCALE_ITALIA);

        static::assertSame('2.00', $formattedNumber);
    }
}
