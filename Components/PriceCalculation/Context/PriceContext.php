<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Context;

class PriceContext
{
    /**
     * @var float
     */
    private $price;

    /**
     * @var bool
     */
    private $netPrice;

    /**
     * @var bool
     */
    private $taxFree;

    /**
     * @var float
     */
    private $currencyFactor;

    /**
     * @var float
     */
    private $taxRate;

    public function __construct(
        float $price,
        float $taxRate,
        bool $netPrice = false,
        bool $taxFree = false,
        float $currencyFactor = 1.0
    ) {
        $this->price = $price;
        $this->taxRate = $taxRate;
        $this->currencyFactor = $currencyFactor;
        $this->netPrice = $netPrice;
        $this->taxFree = $taxFree;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function isNetPrice(): bool
    {
        return $this->netPrice;
    }

    public function isTaxFree(): bool
    {
        return $this->taxFree;
    }

    public function getCurrencyFactor(): float
    {
        return $this->currencyFactor;
    }
}
