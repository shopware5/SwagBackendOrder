<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use SwagBackendOrder\Components\PriceCalculation\DiscountType;

class DiscountCalculator
{
    public function calculateDiscount(array &$orderData): array
    {
        foreach ($orderData['positions'] as &$position) {
            // Check for absolute discount
            if ($position['isDiscount'] && $position['discountType'] === DiscountType::DISCOUNT_ABSOLUTE) {
                $this->updateOrderDetails($orderData, $position, $position['price']);
                continue;
            }

            // Check for percentage discount
            if ($position['isDiscount'] && $position['discountType'] === DiscountType::DISCOUNT_PERCENTAGE) {
                // Don't include the shipping costs in this calculation
                $totalDiscount = (float) $orderData['sum'] / 100 * (float) $position['price'];

                $this->updateOrderDetails($orderData, $position, $totalDiscount);
            }
        }

        return $orderData;
    }

    private function updateOrderDetails(array &$orderData, array &$discountPosition, float $discount): void
    {
        $taxRate = $discountPosition['taxRate'];

        // Update order amount
        $orderData['sum'] += $discount;

        // If showing net prices, discount is implied to be a net value.
        // Add taxes now, so that the results are correct
        if (!$orderData['isDisplayNet']) {
            $orderData['total'] += $discount;
            $discountNet = round($discount / (100 + $taxRate) * 100, 3);
        } else {
            $orderData['total'] += round($discount * (1 + $taxRate / 100), 3);
            $discountNet = $discount;
        }

        // If this is not a net order,
        // we have to calculate the tax value of the discount and add it to the order tax sum.
        if (!$orderData['isTaxFree']) {
            // taxValue is always a negative number
            $taxValue = $discountNet / 100 * $discountPosition['taxRate'];

            $orderData['taxSum'] += $taxValue;
            $orderData['totalWithoutTax'] += $discountNet;
        } else {
            $orderData['totalWithoutTax'] += $discount;
        }

        // Update position
        $discountPosition['total'] = $discount;
    }
}
