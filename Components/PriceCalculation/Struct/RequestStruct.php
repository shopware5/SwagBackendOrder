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
     * @var PositionStruct[]
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
     * @var bool
     */
    private $displayNet;

    /**
     * @var bool
     */
    private $previousDisplayNet;

    /**
     * @return PositionStruct[]
     */
    public function getPositions()
    {
        return $this->positions;
    }

    /**
     * @return array[]
     */
    public function getPositionsArray()
    {
        $positions = [];
        foreach ($this->getPositions() as $position) {
            $positions[] = $position->toArray();
        }

        return $positions;
    }

    /**
     * @param PositionStruct[] $positions
     */
    public function setPositions(array $positions)
    {
        $this->positions = $positions;
    }

    /**
     * @return bool
     */
    public function isTaxFree()
    {
        return $this->taxFree;
    }

    /**
     * @param bool $taxFree
     */
    public function setTaxFree($taxFree)
    {
        $this->taxFree = $taxFree;
    }

    /**
     * @return bool
     */
    public function isPreviousTaxFree()
    {
        return $this->previousTaxFree;
    }

    /**
     * @param bool $taxFreeChanged
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
     * @return bool
     */
    public function isPreviousDisplayNet()
    {
        return $this->previousDisplayNet;
    }

    /**
     * @param bool $previousNetChanged
     */
    public function setPreviousDisplayNet($previousNetChanged)
    {
        $this->previousDisplayNet = $previousNetChanged;
    }

    /**
     * @return bool
     */
    public function isDisplayNet()
    {
        return $this->displayNet;
    }

    /**
     * @param bool $displayNet
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
