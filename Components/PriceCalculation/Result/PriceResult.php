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

class PriceResult
{
    public const ROUND_PRECISION = 2;

    /**
     * @var float
     */
    private $net;

    /**
     * @var float
     */
    private $gross;

    /**
     * @var float
     */
    private $taxRate;

    public function getNet(): float
    {
        return $this->net;
    }

    public function setNet(float $net): void
    {
        $this->net = $net;
    }

    public function getGross(): float
    {
        return $this->gross;
    }

    public function setGross(float $gross): void
    {
        $this->gross = $gross;
    }

    public function getRoundedNetPrice(): float
    {
        return \round($this->getNet(), self::ROUND_PRECISION);
    }

    public function getRoundedGrossPrice(): float
    {
        return \round($this->getGross(), self::ROUND_PRECISION);
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getTaxSum(): float
    {
        return $this->getRoundedGrossPrice() - $this->getRoundedNetPrice();
    }
}
