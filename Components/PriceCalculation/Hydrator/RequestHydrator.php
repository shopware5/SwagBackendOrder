<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Hydrator;

use SwagBackendOrder\Components\PriceCalculation\Struct\RequestStruct;

class RequestHydrator
{
    /**
     * @param array $data
     * @return RequestStruct
     */
    public function hydrateFromRequest(array $data)
    {
        $requestStruct = new RequestStruct();

        if (!empty($data['positions'])) {
            $requestStruct->setPositions(json_decode($data['positions']));
        }

        $requestStruct->setTaxFree($this->convertBoolean($data['taxFree']));
        $requestStruct->setPreviousTaxFree($this->convertBoolean($data['previousTaxFree']));

        $requestStruct->setDisplayNet($this->convertBoolean($data['displayNet']));
        $requestStruct->setPreviousDisplayNet($this->convertBoolean($data['previousDisplayNet']));

        $requestStruct->setPreviousShippingTaxRate((float) $data['previousDispatchTaxRate']);

        $requestStruct->setDispatchId((int) $data['dispatchId']);

        $requestStruct->setCurrencyId((int) $data['newCurrencyId']);
        $requestStruct->setPreviousCurrencyId((int) $data['oldCurrencyId']);

        $requestStruct->setShippingCosts((float) $data['shippingCosts']);
        $requestStruct->setShippingCostsNet((float) $data['shippingCostsNet']);

        if (!empty($requestStruct->getPositions())) {
            $basketTaxRates = $this->getBasketTaxRates($requestStruct->getPositions());
            $requestStruct->setBasketTaxRates($basketTaxRates);
        }

        return $requestStruct;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function convertBoolean($value)
    {
        return (boolean) ($value == 'true');
    }

    /**
     * @param array $positions
     * @return array
     */
    private function getBasketTaxRates(array $positions)
    {
        $taxRates = [];
        foreach ($positions as $position) {
            $taxRates[] = (float) $position->taxRate;
        }
        return array_unique($taxRates);
    }
}