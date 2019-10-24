<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\Result\TotalPricesResult;

class TotalPriceCalculator
{
    /**
     * @param PriceResult[] $positionPrices
     * @param bool          $isProportionalTaxCalculation
     *
     * @return TotalPricesResult TotalPricesResult
     */
    public function calculate(array $positionPrices, PriceResult $shippingPrice, $isProportionalTaxCalculation = false)
    {
        $totalPrice = new TotalPricesResult();

        $totalPrice->setShipping($shippingPrice);

        $sum = $this->getProductSum($positionPrices);
        $totalPrice->setSum($sum);

        $total = $this->getTotal($sum, $shippingPrice);
        $totalPrice->setTotal($total);

        if (!$isProportionalTaxCalculation) {
            $taxes = $this->getTotalTaxPrices(array_merge($positionPrices, [$shippingPrice]));
        } else {
            $taxes = $this->getTotalTaxPrices($positionPrices);
        }

        $totalPrice->setTaxes($taxes);

        return $totalPrice;
    }

    /**
     * @param PriceResult[] $positionPrices
     *
     * @return PriceResult
     */
    private function getProductSum($positionPrices)
    {
        $sum = new PriceResult();
        $sumGross = 0;
        $sumNet = 0;

        foreach ($positionPrices as $price) {
            $sumGross += $price->getRoundedGrossPrice();
            $sumNet += $price->getRoundedNetPrice();
        }

        $sum->setGross($sumGross);
        $sum->setNet($sumNet);

        return $sum;
    }

    /**
     * @return PriceResult
     */
    private function getTotal(PriceResult $sum, PriceResult $shippingPrice)
    {
        $total = new PriceResult();
        $totalNet = $sum->getNet() + $shippingPrice->getRoundedNetPrice();
        $totalGross = $sum->getGross() + $shippingPrice->getRoundedGrossPrice();

        $total->setNet($totalNet);
        $total->setGross($totalGross);

        return $total;
    }

    /**
     * @param PriceResult[] $prices
     *
     * @return array
     */
    private function getTotalTaxPrices(array $prices)
    {
        $taxes = [];

        foreach ($prices as $price) {
            $taxRate = (string) $price->getTaxRate();
            if (!array_key_exists($taxRate, $taxes)) {
                $taxes[$taxRate] = 0;
            }

            $taxes[$taxRate] += $price->getTaxSum();
        }

        return array_filter($taxes);
    }
}
