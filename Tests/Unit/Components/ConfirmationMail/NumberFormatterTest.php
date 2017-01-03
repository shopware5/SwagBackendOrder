<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Tests\Unit\Components\ConfirmationMail;

use RuntimeException;
use SwagBackendOrder\Components\ConfirmationMail\NumberFormatterWrapper;

class NumberFormatterTest extends \PHPUnit_Framework_TestCase
{
    const LOCALE_GERMANY = 'de_DE';
    const LOCALE_GREAT_BRITAIN = 'en_EN';
    const LOCALE_ITALIA = 'it_IT';
    const EMPTY_LOCALE = '';

    public function test_it_can_be_created()
    {
        $numberFormatterWrapper = new NumberFormatterWrapper();

        $this->assertInstanceOf(NumberFormatterWrapper::class, $numberFormatterWrapper);
    }

    public function test_it_should_format_number_for_locale_de()
    {
        $number = 1.988888;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GERMANY);

        $this->assertEquals('1,99', $formattedNumber);
    }

    public function test_it_should_add_2_decimal_digits()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GERMANY);

        $this->assertEquals('2,00', $formattedNumber);
    }

    public function test_it_should_format_english_numbers()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_GREAT_BRITAIN);

        $this->assertEquals('2.00', $formattedNumber);
    }

    public function test_it_should_throw_exception_if_locale_is_empty()
    {
        $number = 1;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $this->expectException(RuntimeException::class);
        $numberFormatterWrapper->format($number, self::EMPTY_LOCALE);
    }

    public function test_it_should_use_english_notation_as_default()
    {
        $number = 2;

        $numberFormatterWrapper = new NumberFormatterWrapper();

        $formattedNumber = $numberFormatterWrapper->format($number, self::LOCALE_ITALIA);

        $this->assertEquals('2.00', $formattedNumber);
    }
}