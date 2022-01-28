<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\ConfirmationMail;

use RuntimeException;

class NumberFormatterWrapper
{
    public const LOCALE_GREAT_BRITAIN = 'en_EN';
    public const LOCALE_GERMANY = 'de_DE';

    public function format(float $number, string $locale = self::LOCALE_GREAT_BRITAIN): string
    {
        $decimalPoint = '.';
        if (!$locale) {
            throw new RuntimeException('$locale is empty.');
        }

        if ($locale === self::LOCALE_GERMANY) {
            $decimalPoint = ',';
        }

        return \number_format($number, 2, $decimalPoint, '');
    }
}
