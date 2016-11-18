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

class ProductPriceCalculator
{
    /**
     * @var TaxCalculation
     */
    private $taxCalculation;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @param TaxCalculation $taxCalculation
     * @param CurrencyConverter $currencyConverter
     */
    public function __construct(TaxCalculation $taxCalculation, CurrencyConverter $currencyConverter)
    {
        $this->taxCalculation = $taxCalculation;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * @param PriceContext $context
     * @return PriceResult
     * @throws \Exception
     */
    public function calculate(PriceContext $context)
    {
        if (!$context->isNetPrice()) {
            throw new \Exception("The given price is not a net price.");
        }

        $result = new PriceResult();

        $netPrice = $this->currencyConverter->getCurrencyPrice($context->getPrice(), $context->getCurrencyFactor());
        $result->setNet($netPrice);

        $grossPrice = $this->taxCalculation->getGrossPrice($netPrice, $context->getTaxRate());
        $result->setGross($grossPrice);

        $result->setTaxRate($context->getTaxRate());
        return $result;
    }

    /**
     * @param PriceContext $priceContext
     * @return float
     */
    public function calculateBasePrice(PriceContext $priceContext)
    {
        $baseCurrencyPrice = $this->currencyConverter->getBaseCurrencyPrice(
            $priceContext->getPrice(), $priceContext->getCurrencyFactor()
        );

        $basePrice = $baseCurrencyPrice;
        if ($priceContext->isNetPrice() || $priceContext->isTaxFree()) {
            return $basePrice;
        }
        return $this->taxCalculation->getNetPrice($baseCurrencyPrice, $priceContext->getTaxRate());
    }
}