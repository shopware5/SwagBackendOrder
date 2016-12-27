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
     * @var string
     */
    private $locale;

    /**
     * @param string $locale
     */
    public function __construct($locale = self::LOCALE_GREAT_BRITAIN)
    {
        $this->locale = $locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @param float $number
     * @return bool|string
     * @throws RuntimeException
     */
    public function format($number)
    {
        if (!$this->locale) {
            throw new RuntimeException('Property $locale is empty.');
        }

        $numberFormatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        return $numberFormatter->format($number);
    }
}