<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Context;

class BasketContext
{
    /**
     * @var float
     */
    private $currencyFactor;

    /**
     * @var boolean
     */
    private $net;

    /**
     * @var float
     */
    private $dispatchTaxRate;

    /**
     * @param float $currencyFactor
     * @param boolean $net
     * @param float $dispatchTaxRate
     */
    public function __construct($currencyFactor, $net, $dispatchTaxRate)
    {
        $this->currencyFactor = $currencyFactor;
        $this->net = $net;
        $this->dispatchTaxRate = $dispatchTaxRate;
    }

    /**
     * @return float
     */
    public function getCurrencyFactor()
    {
        return $this->currencyFactor;
    }

    /**
     * @return boolean
     */
    public function isNet()
    {
        return $this->net;
    }

    /**
     * @return float
     */
    public function getDispatchTaxRate()
    {
        return $this->dispatchTaxRate;
    }
}
