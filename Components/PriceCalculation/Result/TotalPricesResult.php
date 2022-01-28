<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Result;

class TotalPricesResult
{
    /**
     * @var PriceResult
     */
    private $total;

    /**
     * @var PriceResult
     */
    private $sum;

    /**
     * @var PriceResult
     */
    private $shipping;

    /**
     * Indexed by tax rate, value is the amount of the tax
     *
     * @var array<string|int, float>
     */
    private $taxes;

    public function getTotal(): PriceResult
    {
        return $this->total;
    }

    public function setTotal(PriceResult $total): void
    {
        $this->total = $total;
    }

    public function getSum(): PriceResult
    {
        return $this->sum;
    }

    public function setSum(PriceResult $sum): void
    {
        $this->sum = $sum;
    }

    public function getShipping(): PriceResult
    {
        return $this->shipping;
    }

    public function setShipping(PriceResult $shipping): void
    {
        $this->shipping = $shipping;
    }

    /**
     * @return array<string|int, float>
     */
    public function getTaxes(): array
    {
        return $this->taxes;
    }

    /**
     * @param array<string|int, float> $taxes
     */
    public function setTaxes(array $taxes): void
    {
        $this->taxes = $taxes;
    }

    public function addTax(float $taxRate, float $amount): void
    {
        $taxRateKey = (string) $taxRate;
        if (!isset($this->taxes[$taxRateKey])) {
            $this->taxes[$taxRateKey] = 0.00;
        }
        $this->taxes[$taxRateKey] += $amount;
    }

    public function getTaxAmount(): float
    {
        return array_sum($this->taxes);
    }
}
