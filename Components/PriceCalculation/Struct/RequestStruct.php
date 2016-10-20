<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Struct;

class RequestStruct
{
    /**
     * @var array
     */
    private $positions;

    /**
     * @var bool
     */
    private $taxFree;

    /**
     * @var bool
     */
    private $previousTaxFree;

    /**
     * @var int
     */
    private $dispatchId;

    /**
     * @var int
     */
    private $currencyId;

    /**
     * @var int
     */
    private $previousCurrencyId;

    /**
     * @var float
     */
    private $shippingCosts;

    /**
     * @var float
     */
    private $shippingCostsNet;

    /**
     * @var float
     */
    private $previousShippingTaxRate;

    /**
     * @var float[]
     */
    private $basketTaxRates;

    /**
     * @var boolean
     */
    private $displayNet;

    /**
     * @var boolean
     */
    private $previousDisplayNet;

    /**
     * @return array
     */
    public function getPositions()
    {
        return $this->positions;
    }

    /**
     * @param array $positions
     */
    public function setPositions(array $positions)
    {
        $this->positions = $positions;
    }

    /**
     * @return boolean
     */
    public function isTaxFree()
    {
        return $this->taxFree;
    }

    /**
     * @param boolean $taxFree
     */
    public function setTaxFree($taxFree)
    {
        $this->taxFree = $taxFree;
    }

    /**
     * @return boolean
     */
    public function isPreviousTaxFree()
    {
        return $this->previousTaxFree;
    }

    /**
     * @param boolean $taxFreeChanged
     */
    public function setPreviousTaxFree($taxFreeChanged)
    {
        $this->previousTaxFree = $taxFreeChanged;
    }

    /**
     * @return int
     */
    public function getDispatchId()
    {
        return $this->dispatchId;
    }

    /**
     * @param int $dispatchId
     */
    public function setDispatchId($dispatchId)
    {
        $this->dispatchId = $dispatchId;
        if ($dispatchId === 0) {
            $this->dispatchId = null;
        }
    }

    /**
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param int $currencyId
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * @return int
     */
    public function getPreviousCurrencyId()
    {
        return $this->previousCurrencyId;
    }

    /**
     * @param int $previousCurrencyId
     */
    public function setPreviousCurrencyId($previousCurrencyId)
    {
        $this->previousCurrencyId = $previousCurrencyId;
    }

    /**
     * @return float
     */
    public function getShippingCosts()
    {
        return $this->shippingCosts;
    }

    /**
     * @param float $shippingCosts
     */
    public function setShippingCosts($shippingCosts)
    {
        $this->shippingCosts = $shippingCosts;
    }

    /**
     * @return float
     */
    public function getShippingCostsNet()
    {
        return $this->shippingCostsNet;
    }

    /**
     * @param float $shippingCostsNet
     */
    public function setShippingCostsNet($shippingCostsNet)
    {
        $this->shippingCostsNet = $shippingCostsNet;
    }

    /**
     * @return float[]
     */
    public function getBasketTaxRates()
    {
        return $this->basketTaxRates;
    }

    /**
     * @param float[] $basketTaxRates
     */
    public function setBasketTaxRates(array $basketTaxRates)
    {
        $this->basketTaxRates = $basketTaxRates;
    }

    /**
     * @return boolean
     */
    public function isPreviousDisplayNet()
    {
        return $this->previousDisplayNet;
    }

    /**
     * @param boolean $previousNetChanged
     */
    public function setPreviousDisplayNet($previousNetChanged)
    {
        $this->previousDisplayNet = $previousNetChanged;
    }

    /**
     * @return boolean
     */
    public function isDisplayNet()
    {
        return $this->displayNet;
    }

    /**
     * @param boolean $displayNet
     */
    public function setDisplayNet($displayNet)
    {
        $this->displayNet = $displayNet;
    }

    /**
     * @return float
     */
    public function getPreviousShippingTaxRate()
    {
        return $this->previousShippingTaxRate;
    }

    /**
     * @param float $previousShippingTaxRate
     */
    public function setPreviousShippingTaxRate($previousShippingTaxRate)
    {
        $this->previousShippingTaxRate = $previousShippingTaxRate;
    }
}