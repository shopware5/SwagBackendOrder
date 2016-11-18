<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Context;

class PriceContext
{
    /**
     * @var float
     */
    private $price;

    /**
     * @var boolean
     */
    private $netPrice;

    /**
     * @var boolean
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

    /**
     * @param float $price
     * @param float $taxRate
     * @param boolean $netPrice
     * @param boolean $taxFree
     * @param float $currencyFactor
     * @throws \Exception
     */
    public function __construct($price, $taxRate, $netPrice = false, $taxFree = false, $currencyFactor = 1.0)
    {
        if (!is_numeric($price)) {
            throw new \Exception("Given price is not numeric.");
        }

        if (!is_numeric($taxRate)) {
            throw new \Exception("Given tax rate is not numeric.");
        }

        if (!is_numeric($currencyFactor)) {
            throw new \Exception("Given currency factor rate is not numeric.");
        }

        $this->price = (float) $price;
        $this->taxRate = (float) $taxRate;
        $this->currencyFactor = (float) $currencyFactor;
        $this->netPrice = (boolean) $netPrice;
        $this->taxFree = (boolean) $taxFree;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @return boolean
     */
    public function isNetPrice()
    {
        return $this->netPrice;
    }

    /**
     * @return boolean
     */
    public function isTaxFree()
    {
        return $this->taxFree;
    }

    /**
     * @return float
     */
    public function getCurrencyFactor()
    {
        return $this->currencyFactor;
    }
}