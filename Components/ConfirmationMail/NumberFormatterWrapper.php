<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use RuntimeException;

class NumberFormatterWrapper
{
    const LOCALE_GREAT_BRITAIN = 'en_EN';
    const LOCALE_GERMANY = 'de_DE';

    /**
     * @param float $number
     * @param string $locale
     * @return bool|string
     */
    public function format($number, $locale = self::LOCALE_GREAT_BRITAIN)
    {
        $decimalPoint = '.';
        if (!$locale) {
            throw new RuntimeException('$locale is empty.');
        }

        if ($locale === self::LOCALE_GERMANY) {
            $decimalPoint = ',';
        }

        return number_format($number, 2, $decimalPoint, '');
    }
}