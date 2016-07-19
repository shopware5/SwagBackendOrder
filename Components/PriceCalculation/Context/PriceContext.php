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
     * @var float
     */
    private $taxRate;

    /**
     * @param float $price
     * @param float $taxRate
     * @throws \Exception
     */
    public function __construct($price, $taxRate)
    {
        if (!is_numeric($price)) {
            throw new \Exception("Given price is not numeric.");
        }

        if (!is_numeric($taxRate)) {
            throw new \Exception("Given tax rate is not numeric.");
        }

        $this->price = (float) $price;
        $this->taxRate = (float) $taxRate;
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
}