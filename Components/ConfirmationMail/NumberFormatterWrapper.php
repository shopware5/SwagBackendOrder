<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use NumberFormatter;
use RuntimeException;

class NumberFormatterWrapper
{
    const LOCALE_GREAT_BRITAIN = 'en_EN';

    /**
     * @param float $number
     * @param $locale
     * @return bool|string
     */
    public function format($number, $locale = self::LOCALE_GREAT_BRITAIN)
    {
        if (!$locale) {
            throw new RuntimeException('$locale is empty.');
        }

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        return $numberFormatter->format($number);
    }
}