<?php

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use SwagBackendOrder\Components\PriceCalculation\SurchargeType;

class SurchargeCalculator
{
    /**
     * @param array $orderData
     *
     * @return array
     */
    public function calculateSurcharge(array &$orderData)
    {
        foreach ($orderData['positions'] as &$position) {
            //Check for absolute discount
            if ($position['isSurcharge'] && $position['surchargeType'] === SurchargeType::SURCHARGE_ABSOLUTE) {
                $this->updateOrderDetails($orderData, $position, $position['price']);
                continue;
            }

            //Check for percentage discount
            if ($position['isSurcharge'] && $position['surchargeType'] === SurchargeType::SURCHARGE_PERCENTAGE) {
                //Don't include the shipping costs in this calculation
                $totalSurcharge = (float)$orderData['sum'] / 100 * (float)$position['price'];

                $this->updateOrderDetails($orderData, $position, $totalSurcharge);
            }
        }

        return $orderData;
    }

    /**
     * @param array $orderData
     * @param array $surchargePosition
     * @param float $surcharge
     */
    private function updateOrderDetails(&$orderData, &$surchargePosition, $surcharge)
    {
        $taxRate = $surchargePosition['taxRate'];
        $surchargeNet = round($surcharge / (100 + $taxRate) * 100, 3);

        //Update order amount
        $orderData['sum'] += $surcharge;
        $orderData['total'] += $surcharge;

        //If this is not a net order,
        //we have to calculate the tax value of the discount and add it to the order tax sum.
        if (!$orderData['isTaxFree']) {
            //taxValue is always a negative number
            $taxValue = $surchargeNet / 100 * $surchargePosition['taxRate'];

            $orderData['taxSum'] += $taxValue;
            $orderData['totalWithoutTax'] += $surchargeNet;
        } else {
            $orderData['totalWithoutTax'] += $surcharge;
        }

        //Update position
        $surchargePosition['total'] = $surcharge;
    }
}
