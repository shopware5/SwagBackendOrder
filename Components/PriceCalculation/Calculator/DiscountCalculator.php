<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use SwagBackendOrder\Components\PriceCalculation\DiscountType;

class DiscountCalculator
{
    /**
     * @param array $orderData
     *
     * @return array
     */
    public function calculateDiscount(array &$orderData)
    {
        foreach ($orderData['positions'] as &$position) {
            //Check for absolute discount
            if ($position['isDiscount'] && $position['discountType'] === DiscountType::DISCOUNT_ABSOLUTE) {
                $this->updateOrderDetails($orderData, $position, $position['price']);
                continue;
            }

            //Check for percentage discount
            if ($position['isDiscount'] && $position['discountType'] === DiscountType::DISCOUNT_PERCENTAGE) {
                //Don't include the shipping costs in this calculation
                $totalDiscount = (float) $orderData['sum'] / 100 * (float) $position['price'];

                $this->updateOrderDetails($orderData, $position, $totalDiscount);
            }
        }

        return $orderData;
    }

    /**
     * @param array $orderData
     * @param array $discountPosition
     * @param float $discount
     */
    private function updateOrderDetails(&$orderData, &$discountPosition, $discount)
    {
        //Update order amount
        $orderData['totalWithoutTax'] += $discount;
        $orderData['sum'] += $discount;
        $orderData['total'] += $discount;

        //If this is not a net order,
        //we have to calculate the tax value of the discount and add it to the order tax sum.
        if (!$orderData['isTaxFree']) {
            //taxValue is always a negative number
            $taxValue = $discount / 100 * $discountPosition['taxRate'];

            $orderData['taxSum'] += $taxValue;
        }

        //Update position
        $discountPosition['total'] = $discount;
    }
}
