<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var float[]
     */
    private $taxes;

    /**
     * @return PriceResult
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param PriceResult $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return PriceResult
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * @param PriceResult $sum
     */
    public function setSum($sum)
    {
        $this->sum = $sum;
    }

    /**
     * @return PriceResult
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param PriceResult $shipping
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
    }

    /**
     * @return array
     */
    public function getTaxes()
    {
        return $this->taxes;
    }

    public function setTaxes(array $taxes)
    {
        $this->taxes = $taxes;
    }

    /**
     * @param float $taxRate
     * @param float $amount
     */
    public function addTax($taxRate, $amount)
    {
        $taxRate = (string) $taxRate;
        if (!isset($this->taxes[$taxRate])) {
            $this->taxes[$taxRate] = 0.00;
        }
        $this->taxes[$taxRate] += $amount;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        $amount = 0.00;
        foreach ($this->taxes as $tax) {
            $amount += $tax;
        }

        return $amount;
    }
}
