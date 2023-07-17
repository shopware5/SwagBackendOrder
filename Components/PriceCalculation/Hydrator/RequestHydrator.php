<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBackendOrder\Components\PriceCalculation\Hydrator;

use SwagBackendOrder\Components\PriceCalculation\Struct\PositionStruct;
use SwagBackendOrder\Components\PriceCalculation\Struct\RequestStruct;

class RequestHydrator
{
    /**
     * @var PositionHydrator
     */
    private $positionHydrator;

    public function __construct(PositionHydrator $positionHydrator)
    {
        $this->positionHydrator = $positionHydrator;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateFromRequest(array $data): RequestStruct
    {
        $requestStruct = new RequestStruct();

        if (!empty($data['positions'])) {
            $positionsArray = \json_decode($data['positions'], true);
            $positions = [];
            foreach ($positionsArray as $position) {
                $positions[] = $this->positionHydrator->hydrate($position, (int) $data['shippingAddressId']);
            }
            $requestStruct->setPositions($positions);
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

        $requestStruct->setBasketTaxRates([]);
        if (!empty($requestStruct->getPositions())) {
            $basketTaxRates = $this->getBasketTaxRates($requestStruct->getPositions());
            $requestStruct->setBasketTaxRates($basketTaxRates);
        }

        return $requestStruct;
    }

    private function convertBoolean(?string $value): bool
    {
        return $value === 'true';
    }

    /**
     * @param PositionStruct[] $positions
     *
     * @return array<float>
     */
    private function getBasketTaxRates(array $positions): array
    {
        $taxRates = [];
        foreach ($positions as $position) {
            $taxRates[] = (float) $position->getTaxRate();
        }

        return \array_unique($taxRates);
    }
}
