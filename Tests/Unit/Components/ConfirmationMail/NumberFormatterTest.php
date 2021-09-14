<?php
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

    public function testItCanBeCreated()
    {
        $numberFormatterWrapper = new NumberFormatterWrapper();

        static::assertInstanceOf(NumberFormatterWrapper::class, $numberFormatterWrapper);
    }

    public function testItShouldFormatNumberForLocaleDe()
    {
        $number = 1.988888;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GERMANY);

        static::assertEquals('1,99', $formattedNumber);
    }

    public function testItShouldAdd2DecimalDigits()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GERMANY);

        static::assertEquals('2,00', $formattedNumber);
    }

    public function testItShouldFormatEnglishNumbers()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GREAT_BRITAIN);

        static::assertEquals('2.00', $formattedNumber);
    }

    public function testItShouldThrowExceptionIfLocaleIsEmpty()
    {
        $number = 1;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $this->expectException(RuntimeException::class);
        $numberFormatterWrapper->format($number, self::EMPTY_LOCALE);
    }

    public function testItShouldUseEnglishNotationAsDefault()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_ITALIA);

        static::assertEquals('2.00', $formattedNumber);
    }
}
