<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagBackendOrder\Components\PriceCalculation\Calculator;

use SwagBackendOrder\Components\PriceCalculation\Context\PriceContext;
use SwagBackendOrder\Components\PriceCalculation\CurrencyConverter;
use SwagBackendOrder\Components\PriceCalculation\Result\PriceResult;
use SwagBackendOrder\Components\PriceCalculation\TaxCalculation;

class ShippingPriceCalculator
{
    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    public function __construct(TaxCalculation $taxCalculation, CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
        $this->taxCalculation = $taxCalculation;
    }

    /**
     * @return PriceResult
     */
    public function calculate(PriceContext $context)
    {
        $priceStruct = new PriceResult();

        $grossPrice = $this->currencyConverter->getCurrencyPrice($context->getPrice(), $context->getCurrencyFactor());
        $priceStruct->setGross($grossPrice);

        $netPrice = $this->taxCalculation->getNetPrice($grossPrice, $context->getTaxRate());
        $priceStruct->setNet($netPrice);

        $priceStruct->setTaxRate($context->getTaxRate());

        if ($context->isTaxFree()) {
            $priceStruct->setGross($netPrice);
            $priceStruct->setTaxRate(0);
        }

        return $priceStruct;
    }

    /**
     * @return float the base/gross shipping price in the standard currency
     */
    public function calculateBasePrice(PriceContext $context)
    {
        $basePrice = $this->currencyConverter->getBaseCurrencyPrice(
            $context->getPrice(),
            $context->getCurrencyFactor()
        );

        if ($context->isTaxFree()) {
            $basePrice = $this->taxCalculation->getGrossPrice($basePrice, $context->getTaxRate());
        }

        return $basePrice;
    }
}
